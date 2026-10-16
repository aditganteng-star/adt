<?php
/**
 * Kerangka dashboard (murid, tutor, admin).
 * Pemakaian:   $u = portal_open('Judul Halaman', 'kunci-menu');  ... isi ...  portal_close();
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

/** Daftar menu sidebar menurut peran: kunci => [ikon, label, file]. */
function portal_menu(string $role): array
{
    $common = [
        'home'    => ['🏠', 'Ringkasan', 'index.php'],
    ];
    return match ($role) {
        'student' => $common + [
            'kelas'   => ['🏫', 'Kelas Saya', 'kelas.php'],
            'absensi' => ['✅', 'Absensi', 'absensi.php'],
            'materi'  => ['📚', 'Materi', 'materi.php'],
            'tugas'   => ['📝', 'Tugas', 'tugas.php'],
            'tryout'  => ['🎯', 'Tryout', 'tryout.php'],
            'profil'  => ['👤', 'Profil', 'profil.php'],
        ],
        'tutor' => $common + [
            'kelas'   => ['🏫', 'Kelas', 'kelas.php'],
            'absensi' => ['✅', 'Absensi', 'absensi.php'],
            'materi'  => ['📚', 'Materi', 'materi.php'],
            'tugas'   => ['📝', 'Tugas & Nilai', 'tugas.php'],
            'tryout'  => ['🎯', 'Kelola Tryout', 'tryout.php'],
            'profil'  => ['👤', 'Profil', 'profil.php'],
        ],
        default => $common + [
            'pengguna' => ['👥', 'Pengguna', 'admin-pengguna.php'],
            'kelas'    => ['🏫', 'Kelas', 'kelas.php'],
            'tutor'    => ['🎓', 'Profil Tutor', 'admin-tutor.php'],
            'paket'    => ['💳', 'Paket', 'admin-paket.php'],
            'pesanan'  => ['🧾', 'Pesanan', 'admin-pesanan.php'],
            'tryout'   => ['🎯', 'Tryout', 'tryout.php'],
            'profil'   => ['👤', 'Profil', 'profil.php'],
        ],
    };
}

function portal_open(string $title, string $nav = 'home'): array
{
    $u = require_login();
    $flashes = take_flash();
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · Portal <?= e(SITE_NAME) ?></title>
  <link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#1f6b4f"/><text x="32" y="44" font-size="38" font-weight="900" text-anchor="middle" fill="#fff" font-family="Arial">h</text></svg>') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/portal.css')) ?>">
  <script>window.HAKUNA = { base: <?= json_encode(base_url()) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
  <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
  <script defer src="<?= e(url('assets/js/portal.js')) ?>"></script>
</head>
<body class="portal-body">
<div class="portal-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark">h<span>!</span></span><span class="brand-name">hakuna<span>matata</span><small>PORTAL</small></span></a>
    <nav class="side-nav" aria-label="Menu portal">
      <?php foreach (portal_menu($u['role']) as $key => [$icon, $label, $file]): ?>
        <a class="<?= $key === $nav ? 'active' : '' ?>" href="<?= e(url('portal/' . $file)) ?>"><span><?= $icon ?></span><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <a class="side-link" href="<?= e(url('index.php')) ?>">🌐 Lihat website</a>
      <a class="side-link" target="_blank" rel="noopener" href="<?= e(whatsapp_url('Halo Hakuna, saya butuh bantuan terkait portal belajar.')) ?>">💬 Bantuan admin</a>
      <a class="side-link danger" href="<?= e(url('logout.php')) ?>">⎋ Keluar</a>
    </div>
  </aside>
  <div class="sidebar-scrim" id="sidebarScrim"></div>

  <div class="portal-main">
    <header class="portal-top">
      <button class="icon-btn menu-btn" id="sidebarToggle" aria-label="Buka menu">☰</button>
      <h1 class="portal-title"><?= e($title) ?></h1>
      <div class="portal-user">
        <span class="role-badge role-<?= e($u['role']) ?>"><?= e(ROLE_LABELS[$u['role']] ?? $u['role']) ?></span>
        <a href="<?= e(url('portal/profil.php')) ?>" class="user-chip"><span class="avatar-sm"><?= e(initials($u['name'])) ?></span><span class="uname"><?= e($u['name']) ?></span></a>
      </div>
    </header>
    <div class="portal-content">
      <?php foreach ($flashes as $f): ?><div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div><?php endforeach; ?>
<?php
    return $u;
}

function portal_close(): void
{
    ?>
    </div>
  </div>
</div>
<div class="toast-region" id="toastRegion" aria-live="polite"></div>
</body>
</html>
<?php
}

/** Kartu statistik kecil. */
function stat_card(string $icon, string $label, string|int $value, string $tone = 'mint'): void
{
    echo '<div class="stat-card"><span class="stat-ico bg-' . e($tone) . '">' . $icon . '</span><div><small>' . e($label) . '</small><strong>' . e((string) $value) . '</strong></div></div>';
}

/** Label status berwarna. */
function status_pill(string $status): string
{
    $map = ['Hadir' => 'ok', 'Izin' => 'info', 'Sakit' => 'warn', 'Alpa' => 'bad', 'Dikumpulkan' => 'info', 'Dinilai' => 'ok',
            'baru' => 'info', 'diproses' => 'warn', 'aktif' => 'ok', 'batal' => 'bad'];
    return '<span class="status status-' . ($map[$status] ?? 'info') . '">' . e($status) . '</span>';
}

/** Pilihan kelas untuk <select>, dibatasi oleh hak akses user. */
function class_options(array $classes, int $selected = 0): string
{
    $out = '';
    foreach ($classes as $c) {
        $out .= '<option value="' . (int) $c['id'] . '"' . ((int) $c['id'] === $selected ? ' selected' : '') . '>' . e($c['name']) . '</option>';
    }
    return $out;
}
