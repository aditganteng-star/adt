<?php
/** Ringkasan dashboard sesuai peran. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = portal_open('Ringkasan', 'home');
$today = date('Y-m-d');
$classes = visible_classes($u);
$classIds = array_map(fn($c) => (int) $c['id'], $classes);
$in = $classIds ? implode(',', array_fill(0, count($classIds), '?')) : 'NULL';
?>

<div class="welcome-card">
  <div>
    <span class="eyebrow eyebrow-light">Portal <?= e(ROLE_LABELS[$u['role']]) ?></span>
    <h2>Halo, <?= e(explode(' ', $u['name'])[0]) ?>! 👋</h2>
    <p><?= match ($u['role']) {
        'student' => 'Siap melanjutkan langkah belajarmu hari ini?',
        'tutor'   => 'Kelas dan murid kamu menunggu. Semangat mengajar!',
        default   => 'Ringkasan aktivitas Hakuna Matata Course.',
    } ?></p>
  </div>
  <div class="today-chip">📅 <?= e(tanggal($today)) ?></div>
</div>

<?php if ($u['role'] === 'student'):
    $myId = (int) $u['id'];
    $att = rows('SELECT status FROM attendance WHERE student_id = ?', [$myId]);
    $present = count(array_filter($att, fn($a) => $a['status'] === 'Hadir'));
    $attPct = $att ? (int) round($present / count($att) * 100) : 0;
    $pending = $classIds ? rows("SELECT a.*, c.name AS class_name FROM assignments a JOIN classes c ON c.id = a.class_id
        WHERE a.class_id IN ($in) AND a.id NOT IN (SELECT assignment_id FROM submissions WHERE student_id = ?)
        ORDER BY (a.due_date IS NULL), a.due_date LIMIT 5", [...$classIds, $myId]) : [];
    $openSessions = $classIds ? rows("SELECT s.*, c.name AS class_name FROM class_sessions s JOIN classes c ON c.id = s.class_id
        WHERE s.class_id IN ($in) AND s.is_open = 1 AND s.id NOT IN (SELECT session_id FROM attendance WHERE student_id = ?)", [...$classIds, $myId]) : [];
    $avgScore = val('SELECT AVG(score) FROM submissions WHERE student_id = ? AND score IS NOT NULL', [$myId]);
    $lastTryout = row('SELECT a.*, t.title FROM tryout_attempts a JOIN tryouts t ON t.id = a.tryout_id WHERE a.student_id = ? AND a.finished_at IS NOT NULL ORDER BY a.id DESC LIMIT 1', [$myId]);
    $materials = $classIds ? rows("SELECT m.*, c.name AS class_name FROM materials m JOIN classes c ON c.id = m.class_id WHERE m.class_id IN ($in) ORDER BY m.id DESC LIMIT 4", $classIds) : [];
    $news = $classIds ? rows("SELECT n.*, c.name AS class_name FROM announcements n JOIN classes c ON c.id = n.class_id WHERE n.class_id IN ($in) ORDER BY n.id DESC LIMIT 3", $classIds) : [];
?>
  <div class="stat-grid">
    <?php stat_card('🏫', 'Kelas diikuti', count($classes), 'mint'); ?>
    <?php stat_card('✅', 'Kehadiran', $attPct . '%', 'sun'); ?>
    <?php stat_card('📝', 'Rata-rata nilai tugas', $avgScore === null ? '—' : (string) round((float) $avgScore), 'lilac'); ?>
    <?php stat_card('🎯', 'Skor tryout terakhir', $lastTryout ? (string) $lastTryout['score'] : '—', 'peach'); ?>
  </div>

  <?php if ($openSessions): ?>
    <div class="callout">
      <div><b>⏰ Absensi sedang dibuka!</b><p><?= e($openSessions[0]['class_name']) ?> — <?= e($openSessions[0]['title']) ?></p></div>
      <a class="btn btn-primary" href="<?= e(url('portal/absensi.php')) ?>">Absen sekarang</a>
    </div>
  <?php endif; ?>

  <div class="grid-2">
    <section class="panel">
      <div class="panel-head"><h3>Tugas yang belum dikumpulkan</h3><a class="text-link" href="<?= e(url('portal/tugas.php')) ?>">Semua →</a></div>
      <?php if (!$pending): ?><div class="empty-state small">🎉 Tidak ada tugas tertunda.</div><?php endif; ?>
      <?php foreach ($pending as $t): $late = $t['due_date'] && $t['due_date'] < $today; ?>
        <a class="list-row" href="<?= e(url('portal/tugas.php?id=' . $t['id'])) ?>">
          <span class="row-ico">📝</span>
          <div><b><?= e($t['title']) ?></b><small><?= e($t['class_name']) ?> · Tenggat <?= e(tanggal($t['due_date'])) ?></small></div>
          <?= $late ? '<span class="status status-bad">Terlambat</span>' : '<span class="status status-warn">Belum</span>' ?>
        </a>
      <?php endforeach; ?>
    </section>

    <section class="panel">
      <div class="panel-head"><h3>Materi terbaru</h3><a class="text-link" href="<?= e(url('portal/materi.php')) ?>">Semua →</a></div>
      <?php if (!$materials): ?><div class="empty-state small">Belum ada materi.</div><?php endif; ?>
      <?php foreach ($materials as $m): ?>
        <a class="list-row" href="<?= e(url('portal/materi.php?kelas=' . $m['class_id'])) ?>">
          <span class="row-ico"><?= ['file' => '📄', 'link' => '🔗', 'video' => '▶️'][$m['kind']] ?? '📄' ?></span>
          <div><b><?= e($m['title']) ?></b><small><?= e($m['class_name']) ?></small></div>
        </a>
      <?php endforeach; ?>
    </section>
  </div>

  <?php if ($news): ?>
    <section class="panel">
      <div class="panel-head"><h3>📢 Pengumuman</h3></div>
      <?php foreach ($news as $n): ?>
        <div class="announce"><b><?= e($n['title']) ?></b><p><?= e($n['body']) ?></p><small><?= e($n['class_name']) ?> · <?= e(tanggal($n['created_at'])) ?></small></div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

<?php elseif ($u['role'] === 'tutor'):
    $studentCount = $classIds ? (int) val("SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE class_id IN ($in)", $classIds) : 0;
    $toGrade = $classIds ? rows("SELECT s.id, s.submitted_at, a.title, a.id AS aid, u.name AS student_name FROM submissions s
        JOIN assignments a ON a.id = s.assignment_id JOIN users u ON u.id = s.student_id
        WHERE a.class_id IN ($in) AND s.status = 'Dikumpulkan' ORDER BY s.submitted_at LIMIT 6", $classIds) : [];
    $todaySessions = $classIds ? rows("SELECT s.*, c.name AS class_name FROM class_sessions s JOIN classes c ON c.id = s.class_id
        WHERE s.class_id IN ($in) AND s.session_date = ? ORDER BY s.id", [...$classIds, $today]) : [];
?>
  <div class="stat-grid">
    <?php stat_card('🏫', 'Kelas diampu', count($classes), 'mint'); ?>
    <?php stat_card('🧑‍🎓', 'Murid', $studentCount, 'sun'); ?>
    <?php stat_card('📝', 'Menunggu dinilai', count($toGrade), 'lilac'); ?>
    <?php stat_card('📅', 'Sesi hari ini', count($todaySessions), 'peach'); ?>
  </div>
  <div class="grid-2">
    <section class="panel">
      <div class="panel-head"><h3>Sesi hari ini</h3><a class="text-link" href="<?= e(url('portal/absensi.php')) ?>">Kelola absensi →</a></div>
      <?php if (!$todaySessions): ?><div class="empty-state small">Belum ada sesi hari ini. Buat sesi di menu Absensi.</div><?php endif; ?>
      <?php foreach ($todaySessions as $s): ?>
        <a class="list-row" href="<?= e(url('portal/sesi.php?id=' . $s['id'])) ?>">
          <span class="row-ico">🗓</span><div><b><?= e($s['title']) ?></b><small><?= e($s['class_name']) ?></small></div>
          <?= $s['is_open'] ? '<span class="status status-ok">Dibuka · ' . e($s['attend_code']) . '</span>' : '<span class="status status-info">Tertutup</span>' ?>
        </a>
      <?php endforeach; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h3>Perlu dinilai</h3><a class="text-link" href="<?= e(url('portal/tugas.php')) ?>">Semua →</a></div>
      <?php if (!$toGrade): ?><div class="empty-state small">✅ Semua tugas sudah dinilai.</div><?php endif; ?>
      <?php foreach ($toGrade as $g): ?>
        <a class="list-row" href="<?= e(url('portal/tugas.php?id=' . $g['aid'])) ?>">
          <span class="row-ico">📝</span><div><b><?= e($g['student_name']) ?></b><small><?= e($g['title']) ?> · <?= e(tanggal($g['submitted_at'], true)) ?></small></div><span class="status status-warn">Nilai</span>
        </a>
      <?php endforeach; ?>
    </section>
  </div>
  <section class="panel">
    <div class="panel-head"><h3>Kelas saya</h3><a class="text-link" href="<?= e(url('portal/kelas.php')) ?>">Kelola →</a></div>
    <div class="class-grid">
      <?php foreach ($classes as $c): ?>
        <a class="class-card" href="<?= e(url('portal/kelas.php?id=' . $c['id'])) ?>"><span class="pill"><?= e($c['level']) ?></span><h4><?= e($c['name']) ?></h4><small>🗓 <?= e((string) $c['schedule']) ?></small><small>Kode gabung: <b><?= e($c['join_code']) ?></b></small></a>
      <?php endforeach; ?>
      <?php if (!$classes): ?><div class="empty-state small">Belum ada kelas. Buat kelas pertamamu di menu Kelas.</div><?php endif; ?>
    </div>
  </section>

<?php else:
    $cnt = fn(string $sql, array $p = []) => (int) val($sql, $p);
    $newOrders = rows("SELECT * FROM orders WHERE status = 'baru' ORDER BY id DESC LIMIT 6");
?>
  <div class="stat-grid">
    <?php stat_card('🧑‍🎓', 'Murid', $cnt("SELECT COUNT(*) FROM users WHERE role='student'"), 'mint'); ?>
    <?php stat_card('🎓', 'Tutor', $cnt("SELECT COUNT(*) FROM users WHERE role='tutor'"), 'sun'); ?>
    <?php stat_card('🏫', 'Kelas', count($classes), 'lilac'); ?>
    <?php stat_card('🧾', 'Pesanan baru', $cnt("SELECT COUNT(*) FROM orders WHERE status='baru'"), 'peach'); ?>
  </div>
  <div class="grid-2">
    <section class="panel">
      <div class="panel-head"><h3>Pesanan & konsultasi terbaru</h3><a class="text-link" href="<?= e(url('portal/admin-pesanan.php')) ?>">Kelola →</a></div>
      <?php if (!$newOrders): ?><div class="empty-state small">Belum ada pesanan baru.</div><?php endif; ?>
      <?php foreach ($newOrders as $o): ?>
        <div class="list-row"><span class="row-ico">🧾</span><div><b><?= e($o['name']) ?></b><small><?= e($o['package_name']) ?> · <?= e(tanggal($o['created_at'], true)) ?></small></div><?= status_pill($o['status']) ?></div>
      <?php endforeach; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h3>Pintasan admin</h3></div>
      <div class="quick-grid">
        <a href="<?= e(url('portal/admin-pengguna.php')) ?>">👥<b>Kelola pengguna</b></a>
        <a href="<?= e(url('portal/admin-tutor.php')) ?>">🎓<b>Profil tutor</b></a>
        <a href="<?= e(url('portal/admin-paket.php')) ?>">💳<b>Paket &amp; harga</b></a>
        <a href="<?= e(url('portal/tryout.php')) ?>">🎯<b>Tryout</b></a>
      </div>
      <p class="fine-print">Pengunjung website: <b><?= number_format((int) @file_get_contents(ROOT_PATH . '/data/counter.txt'), 0, ',', '.') ?></b></p>
    </section>
  </div>
<?php endif; ?>

<?php portal_close(); ?>
