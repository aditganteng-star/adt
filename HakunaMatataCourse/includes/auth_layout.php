<?php
/** Kerangka halaman login & daftar (dua kolom). Gunakan auth_open() / auth_close(). */
declare(strict_types=1);

function auth_open(string $title, string $heading, string $sub): void
{
    $flashes = take_flash();
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · <?= e(SITE_NAME) ?></title>
  <link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#1f6b4f"/><text x="32" y="44" font-size="38" font-weight="900" text-anchor="middle" fill="#fff" font-family="Arial">h</text></svg>') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <script>window.HAKUNA = { base: <?= json_encode(base_url()) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
  <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
  <script defer src="<?= e(url('assets/js/auth.js')) ?>"></script>
</head>
<body class="auth-body">
<div class="auth-layout">
  <aside class="auth-aside">
    <a class="brand brand-light" href="<?= e(url('index.php')) ?>"><span class="brand-mark">h<span>!</span></span><span class="brand-name">hakuna<span>matata</span><small>COURSE</small></span></a>
    <div class="auth-aside-copy">
      <h2>Portal belajar yang bikin semangat.</h2>
      <ul class="feature-list">
        <li>Absen online dengan kode dari tutor</li>
        <li>Materi &amp; tugas kapan saja</li>
        <li>Tryout bertimer dengan pembahasan</li>
        <li>Pantau nilai dan progres belajar</li>
      </ul>
    </div>
    <span class="blob b1" aria-hidden="true"></span><span class="blob b2" aria-hidden="true"></span>
  </aside>
  <main class="auth-main">
    <div class="auth-card">
      <a class="back-link" href="<?= e(url('index.php')) ?>">← Kembali ke beranda</a>
      <h1><?= e($heading) ?></h1>
      <p class="muted"><?= e($sub) ?></p>
      <?php foreach ($flashes as $f): ?><div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
<?php
}

function auth_close(): void
{
    ?>
    </div>
  </main>
</div>
<div class="toast-region" id="toastRegion" aria-live="polite"></div>
</body>
</html>
<?php
}

/** Blok tombol Google (dipakai di login & daftar). */
function google_block(string $label): void
{
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    ?>
      <div class="google-block" data-client-id="<?= e(GOOGLE_CLIENT_ID) ?>" data-next="<?= e((string) ($_GET['next'] ?? '')) ?>">
        <?php if (GOOGLE_CLIENT_ID !== ''): ?>
          <div id="googleBtn" data-text="<?= e($label) ?>"></div>
        <?php elseif ($local): ?>
          <button type="button" class="btn-google" id="googleDemoBtn">
            <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.100 3.600l6.800-6.800C35.800 2.400 30.300 0 24 0 14.600 0 6.500 5.400 2.600 13.200l7.900 6.100C12.400 13.600 17.700 9.500 24 9.500z"/><path fill="#4285F4" d="M46.500 24.500c0-1.600-.1-3.100-.4-4.500H24v9h12.700c-.6 3-2.300 5.500-4.800 7.200l7.500 5.800c4.400-4.100 7.100-10.100 7.100-17.500z"/><path fill="#FBBC05" d="M10.500 28.700A14.500 14.500 0 0 1 9.500 24c0-1.600.3-3.200.8-4.700l-7.900-6.100A24 24 0 0 0 0 24c0 3.900.9 7.500 2.600 10.800l7.900-6.100z"/><path fill="#34A853" d="M24 48c6.500 0 11.900-2.100 15.900-5.800l-7.500-5.800c-2.100 1.400-4.800 2.300-8.400 2.300-6.300 0-11.600-4.100-13.500-9.800l-7.900 6.100C6.500 42.600 14.600 48 24 48z"/></svg>
            <?= e($label) ?>
          </button>
          <div class="demo-google" id="demoGoogleBox" hidden>
            <p class="fine-print"><b>Mode demo (localhost):</b> Client ID Google belum diisi di config.php, jadi ini simulasi. Masukkan nama &amp; email untuk membuat/masuk ke akun murid.</p>
            <input id="demoName" placeholder="Nama" aria-label="Nama">
            <input id="demoEmail" type="email" placeholder="email@gmail.com" aria-label="Email">
            <button type="button" class="btn btn-primary btn-full btn-small" id="demoGoogleGo">Lanjut</button>
          </div>
        <?php else: ?>
          <div class="alert alert-info">Login Google belum diaktifkan oleh admin. Gunakan email &amp; password.</div>
        <?php endif; ?>
        <small class="form-msg" role="alert"></small>
      </div>
    <?php
}
