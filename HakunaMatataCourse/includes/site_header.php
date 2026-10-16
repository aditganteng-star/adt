<?php
/**
 * Header halaman publik.
 * Variabel opsional sebelum include: $pageTitle, $active, $pageDesc, $bodyClass.
 */
require_once __DIR__ . '/bootstrap.php';

$pageTitle = $pageTitle ?? 'Beranda';
$pageDesc  = $pageDesc  ?? 'Hakuna Matata Course — les SD, SMP, SMA, dan persiapan UTBK di ' . SITE_CITY . '. Belajar tenang, tumbuh hebat.';
$active    = $active    ?? '';
$bodyClass = $bodyClass ?? '';
$me        = current_user();

function nav_class(string $key, string $active): string
{
    return $key === $active ? 'active' : '';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · <?= e(SITE_NAME) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <meta name="theme-color" content="#1f6b4f">
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/img/logo.svg')) ?>">
  <link rel="alternate icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#1f6b4f"/><text x="32" y="44" font-size="38" font-weight="900" text-anchor="middle" fill="#fff" font-family="Arial">h</text><circle cx="49" cy="17" r="6" fill="#ffb865"/></svg>') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <script>window.HAKUNA = { base: <?= json_encode(base_url()) ?>, csrf: <?= json_encode(csrf_token()) ?>, wa: <?= json_encode(WHATSAPP_NUMBER) ?> };</script>
  <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
</head>
<body class="<?= e($bodyClass) ?>">

<div class="topbar">
  <div class="container topbar-inner">
    <span>🎉 Konsultasi pilihan program <b>gratis</b> — langsung via WhatsApp</span>
    <a href="<?= e(url('packages.php')) ?>">Lihat promo paket →</a>
  </div>
</div>

<header class="site-header" id="siteHeader">
  <div class="container nav-wrap">
    <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="<?= e(SITE_NAME) ?> — beranda">
      <span class="brand-mark">h<span>!</span></span>
      <span class="brand-name">hakuna<span>matata</span><small>COURSE</small></span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="mainNav">
      <span></span><span></span><span></span>
    </button>

    <nav class="main-nav" id="mainNav" aria-label="Navigasi utama">
      <a class="<?= nav_class('home', $active) ?>" href="<?= e(url('index.php')) ?>">Beranda</a>

      <div class="nav-dropdown">
        <button type="button" class="nav-dropdown-btn <?= nav_class('program', $active) ?>" aria-expanded="false">Program <span class="chev">▾</span></button>
        <div class="dropdown-panel">
          <a href="<?= e(url('program.php#sd')) ?>"><span class="dd-ico bg-peach">🌱</span><span><b>SD</b><small>Fondasi belajar menyenangkan</small></span></a>
          <a href="<?= e(url('program.php#smp')) ?>"><span class="dd-ico bg-lilac">🚀</span><span><b>SMP</b><small>Konsep kuat, nilai naik</small></span></a>
          <a href="<?= e(url('program.php#sma')) ?>"><span class="dd-ico bg-mint">📘</span><span><b>SMA</b><small>Penguatan mapel & target</small></span></a>
          <a href="<?= e(url('program.php#utbk')) ?>"><span class="dd-ico bg-sun">🎯</span><span><b>UTBK</b><small>Persiapan masuk PTN</small></span></a>
        </div>
      </div>

      <a class="<?= nav_class('tutors', $active) ?>" href="<?= e(url('tutors.php')) ?>">Tutor</a>
      <a class="<?= nav_class('packages', $active) ?>" href="<?= e(url('packages.php')) ?>">Paket &amp; Tryout</a>
      <a class="<?= nav_class('contact', $active) ?>" href="<?= e(url('contact.php')) ?>">Kontak</a>

      <div class="nav-actions-mobile">
        <?php if ($me): ?>
          <a class="btn btn-primary btn-full" href="<?= e(url('portal/index.php')) ?>">Buka Dashboard</a>
        <?php else: ?>
          <a class="btn btn-outline btn-full" href="<?= e(url('login.php')) ?>">Masuk</a>
          <a class="btn btn-primary btn-full" href="<?= e(url('register.php')) ?>">Daftar Gratis</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="nav-actions">
      <?php if ($me): ?>
        <a class="user-chip" href="<?= e(url('portal/index.php')) ?>">
          <span class="avatar-sm"><?= e(initials($me['name'])) ?></span>
          <span><?= e(explode(' ', $me['name'])[0]) ?></span>
        </a>
        <a class="btn btn-primary btn-small" href="<?= e(url('portal/index.php')) ?>">Dashboard</a>
      <?php else: ?>
        <a class="login-link" href="<?= e(url('login.php')) ?>">Masuk</a>
        <a class="btn btn-primary btn-small" href="<?= e(url('register.php')) ?>">Daftar Gratis</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php $flashes = take_flash(); if ($flashes): ?>
  <div class="container flash-stack">
    <?php foreach ($flashes as $f): ?><div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<main id="main">
