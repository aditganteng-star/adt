<?php
/**
 * Menyimpan calon murid / pesanan paket, lalu mengembalikan tautan WhatsApp.
 * Dipanggil lewat fetch() dari app.js (POST, JSON).
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_post()) json_out(['ok' => false, 'error' => 'Metode tidak diizinkan.'], 405);
verify_csrf();

$name  = mb_substr(post('name'), 0, 120);
$phone = preg_replace('/[^\d+]/', '', post('phone')) ?? '';
$email = mb_substr(post('email'), 0, 190);
$note  = mb_substr(post('note'), 0, 500);
$level = mb_substr(post('level'), 0, 20);
$source = in_array(post('source'), ['paket', 'kontak', 'hero'], true) ? post('source') : 'hero';
$duration = max(1, min(12, (int) post('duration', '1')));

$errors = [];
if (mb_strlen($name) < 2) $errors[] = 'Nama wajib diisi.';
if (strlen($phone) < 9 || strlen($phone) > 16) $errors[] = 'Nomor WhatsApp belum valid (contoh: 0812xxxxxxx).';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email belum valid.';
if ($errors) json_out(['ok' => false, 'error' => implode(' ', $errors)], 422);

$package = null;
$total = 0;
if ((int) post('package_id') > 0) {
    $package = row('SELECT * FROM packages WHERE id = ? AND is_active = 1', [(int) post('package_id')]);
    if (!$package) json_out(['ok' => false, 'error' => 'Paket tidak ditemukan.'], 404);
    // Diskon durasi dihitung ulang di server (jangan percaya angka dari browser).
    $discount = $duration >= 6 ? 0.10 : ($duration >= 3 ? 0.05 : 0);
    $multiplier = $package['unit'] === 'bulan' ? $duration : 1;
    $total = (int) round($package['price'] * $multiplier * (1 - ($package['unit'] === 'bulan' ? $discount : 0)));
}

$orderId = insert('orders', [
    'package_id'   => $package['id'] ?? null,
    'package_name' => $package['name'] ?? ('Konsultasi ' . ($level ?: 'program belajar')),
    'duration'     => $package && $package['unit'] === 'bulan' ? $duration : 1,
    'total'        => $total,
    'name'         => $name,
    'phone'        => $phone,
    'email'        => $email ?: null,
    'note'         => $note ?: null,
    'source'       => $source,
]);

if ($package) {
    $text = "Halo Hakuna Matata Course! 👋\nSaya ingin mendaftar paket:\n"
          . "• Paket: {$package['name']} ({$package['level']})\n"
          . ($package['unit'] === 'bulan' ? "• Durasi: $duration bulan\n" : '')
          . '• Total: ' . rupiah($total) . "\n"
          . "• Nama: $name\n• WhatsApp: $phone\n"
          . ($note ? "• Catatan: $note\n" : '')
          . "\nMohon info jadwal dan cara pembayarannya. (Ref #$orderId)";
} else {
    $text = "Halo Hakuna Matata Course! 👋\nSaya $name" . ($level ? " (jenjang $level)" : '')
          . " ingin konsultasi program belajar.\nWhatsApp: $phone" . ($note ? "\nKebutuhan: $note" : '') . "\n(Ref #$orderId)";
}

json_out(['ok' => true, 'order_id' => $orderId, 'whatsapp' => whatsapp_url($text)]);
