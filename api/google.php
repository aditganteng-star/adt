<?php
/**
 * Login / daftar dengan Google.
 *  - Mode normal: verifikasi ID token Google (GIS) lewat endpoint tokeninfo.
 *  - Mode demo  : hanya jika GOOGLE_CLIENT_ID kosong DAN diakses dari localhost,
 *                 dan hanya untuk akun murid (tidak bisa masuk sebagai tutor/admin).
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_post()) json_out(['ok' => false, 'error' => 'Metode tidak diizinkan.'], 405);
verify_csrf();

$info = null;

if (GOOGLE_CLIENT_ID !== '') {
    $credential = post('credential');
    if ($credential === '') json_out(['ok' => false, 'error' => 'Token Google tidak ditemukan.'], 400);

    $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        json_out(['ok' => false, 'error' => 'Tidak bisa menghubungi Google (' . $curlError . '). Di XAMPP, pastikan curl.cainfo di php.ini mengarah ke file cacert.pem.'], 502);
    }
    $data = json_decode((string) $body, true);
    if ($code !== 200 || !is_array($data)) json_out(['ok' => false, 'error' => 'Token Google tidak valid.'], 401);
    if (($data['aud'] ?? '') !== GOOGLE_CLIENT_ID) json_out(['ok' => false, 'error' => 'Token bukan untuk aplikasi ini.'], 401);
    if (($data['email_verified'] ?? 'false') !== 'true' && ($data['email_verified'] ?? false) !== true) json_out(['ok' => false, 'error' => 'Email Google belum terverifikasi.'], 401);
    if ((int) ($data['exp'] ?? 0) < time()) json_out(['ok' => false, 'error' => 'Token kedaluwarsa. Coba lagi.'], 401);

    $info = ['sub' => (string) $data['sub'], 'email' => mb_strtolower((string) $data['email']),
             'name' => (string) ($data['name'] ?? $data['email']), 'picture' => (string) ($data['picture'] ?? '')];
} else {
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (!$local) json_out(['ok' => false, 'error' => 'Login Google belum diaktifkan.'], 403);
    $email = mb_strtolower(post('email'));
    $name  = mb_substr(post('name'), 0, 120);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) < 2) json_out(['ok' => false, 'error' => 'Isi nama dan email yang valid.'], 422);
    $info = ['sub' => 'demo-' . md5($email), 'email' => $email, 'name' => $name, 'picture' => ''];
    $existing = row('SELECT role FROM users WHERE email = ?', [$email]);
    if ($existing && $existing['role'] !== 'student') json_out(['ok' => false, 'error' => 'Mode demo hanya untuk akun murid.'], 403);
}

// Cari akun berdasarkan google_id atau email; buat akun murid baru jika belum ada.
$user = row('SELECT * FROM users WHERE google_id = ? OR email = ?', [$info['sub'], $info['email']]);
if ($user) {
    run('UPDATE users SET google_id = ?, avatar_url = ? WHERE id = ?', [$info['sub'], $info['picture'] ?: $user['avatar_url'], $user['id']]);
} else {
    $id = insert('users', ['name' => $info['name'], 'email' => $info['email'], 'google_id' => $info['sub'],
                           'avatar_url' => $info['picture'] ?: null, 'role' => 'student']);
    $user = row('SELECT * FROM users WHERE id = ?', [$id]);
}

login_user($user);
json_out(['ok' => true, 'redirect' => safe_next(post('next'))]);
