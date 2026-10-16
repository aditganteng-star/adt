<?php
/**
 * Setup sekali jalan: membuat database, tabel, dan data contoh.
 * Buka: http://localhost/HakunaMatataCourse/setup.php
 * Setelah selesai, HAPUS file ini sebelum website dipublikasikan.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/seed.php';

$log = [];
$error = '';
$done = false;

try {
    // 1) Buat database jika belum ada (khusus MySQL).
    if (!getenv('HAKUNA_DSN')) {
        $server = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $log[] = 'Database "' . DB_NAME . '" siap.';
    }

    // 2) Jalankan skema (setiap pernyataan dipisah tanda titik koma).
    $sql = (string) file_get_contents(__DIR__ . '/database/schema.sql');
    $sql = preg_replace('/--[^\n]*/', '', $sql);
    $count = 0;
    foreach (array_filter(array_map('trim', explode(';', (string) $sql))) as $statement) {
        db()->exec($statement);
        $count++;
    }
    $log[] = "$count tabel dibuat / sudah ada.";

    // 2b) Database lama: tambahkan kolom foto tutor jika belum ada.
    try { db()->exec('ALTER TABLE tutor_profiles ADD COLUMN photo VARCHAR(255) NULL'); } catch (Throwable $ignore) { /* sudah ada */ }

    // 3) Isi data contoh jika belum ada pengguna.
    if ((int) val('SELECT COUNT(*) FROM users') === 0) {
        seed_demo_data();
        $log[] = 'Data contoh (akun demo, tutor, paket, kelas, tryout) ditambahkan.';
    } else {
        $log[] = 'Data sudah ada — data contoh tidak ditambahkan lagi.';
    }

    // 4) Folder penyimpanan file.
    foreach ([STORAGE_PATH, ROOT_PATH . '/data'] as $dir) if (!is_dir($dir)) mkdir($dir, 0775, true);
    $done = true;
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="auth-body">
  <main class="auth-card setup-card">
    <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark">h<span>!</span></span><span class="brand-name">hakuna<span>matata</span><small>COURSE</small></span></a>
    <h1>Setup website</h1>
    <?php if ($done): ?>
      <div class="alert alert-success">Berhasil! Website siap digunakan.</div>
      <ul class="check-list"><?php foreach ($log as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
      <h3>Akun demo</h3>
      <div class="table-wrap"><table>
        <tr><th>Peran</th><th>Email</th><th>Password</th></tr>
        <tr><td>Murid</td><td>murid@hakuna.test</td><td>Murid123!</td></tr>
        <tr><td>Tutor</td><td>tutor@hakuna.test</td><td>Tutor123!</td></tr>
        <tr><td>Admin</td><td>admin@hakuna.test</td><td>Admin123!</td></tr>
      </table></div>
      <a class="btn btn-primary btn-full" href="<?= e(url('index.php')) ?>">Buka website</a>
      <p class="fine-print">Keamanan: hapus setup.php, ganti semua password demo, dan set SHOW_DEMO_ACCOUNTS = false di config.php sebelum online.</p>
    <?php else: ?>
      <div class="alert alert-error">Setup gagal: <?= e($error) ?></div>
      <p>Pastikan <b>Apache</b> dan <b>MySQL</b> sudah Start di XAMPP, lalu cek pengaturan database di <code>config.php</code>.</p>
      <a class="btn btn-primary" href="setup.php">Coba lagi</a>
    <?php endif; ?>
  </main>
</body>
</html>
