<?php
/** Absensi: murid absen dengan kode; tutor/admin membuat & membuka sesi. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_login();
$classes  = visible_classes($u);
$classIds = array_map(fn($c) => (int) $c['id'], $classes);
$filter   = (int) ($_GET['kelas'] ?? 0);
if ($filter && !in_array($filter, $classIds, true)) $filter = 0;

if (is_post()) {
    verify_csrf();
    $action = post('action');
    try {
        if ($action === 'checkin' && $u['role'] === 'student') {
            $s = row('SELECT * FROM class_sessions WHERE id = ?', [(int) post('session_id')]);
            if (!$s || !in_array((int) $s['class_id'], $classIds, true)) throw new RuntimeException('Sesi tidak ditemukan di kelasmu.');
            if (!$s['is_open']) throw new RuntimeException('Absensi untuk sesi ini sudah ditutup.');
            if (!hash_equals($s['attend_code'], strtoupper(post('code')))) throw new RuntimeException('Kode absen salah. Tanyakan kode ke tutormu.');
            if (val('SELECT COUNT(*) FROM attendance WHERE session_id = ? AND student_id = ?', [$s['id'], $u['id']])) throw new RuntimeException('Kamu sudah absen di sesi ini.');
            insert('attendance', ['session_id' => $s['id'], 'student_id' => $u['id'], 'status' => 'Hadir', 'method' => 'kode']);
            flash('Absen berhasil! Selamat belajar ✅');
        } elseif ($action === 'create_session' && is_staff($u)) {
            $cid = (int) post('class_id');
            if (!can_manage_class($u, $cid)) throw new RuntimeException('Kamu tidak memiliki izin untuk kelas ini.');
            if (mb_strlen(post('title')) < 2) throw new RuntimeException('Judul pertemuan wajib diisi.');
            $date = post('session_date') ?: date('Y-m-d');
            $id = insert('class_sessions', ['class_id' => $cid, 'title' => mb_substr(post('title'), 0, 190), 'session_date' => $date, 'attend_code' => random_code(5), 'is_open' => 1]);
            flash('Pertemuan dibuat & absensi dibuka. Bagikan kodenya ke murid!');
            redirect('portal/sesi.php?id=' . $id);
        } elseif ($action === 'toggle' && is_staff($u)) {
            $s = row('SELECT * FROM class_sessions WHERE id = ?', [(int) post('session_id')]);
            if (!$s || !can_manage_class($u, (int) $s['class_id'])) throw new RuntimeException('Tidak ada izin.');
            run('UPDATE class_sessions SET is_open = ? WHERE id = ?', [$s['is_open'] ? 0 : 1, $s['id']]);
            flash($s['is_open'] ? 'Absensi ditutup.' : 'Absensi dibuka.', 'info');
        } else {
            throw new RuntimeException('Aksi tidak tersedia.');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/absensi.php' . ($filter ? '?kelas=' . $filter : ''));
}

portal_open('Absensi', 'absensi');
$ids = $filter ? [$filter] : $classIds;
$in  = $ids ? implode(',', array_fill(0, count($ids), '?')) : 'NULL';
?>

<?php if (count($classes) > 1): ?>
  <form method="get" class="filter-inline">
    <label for="kelas">Kelas:</label>
    <select id="kelas" name="kelas" onchange="this.form.submit()"><option value="0">Semua kelas</option><?= class_options($classes, $filter) ?></select>
  </form>
<?php endif; ?>

<?php if ($u['role'] === 'student'):
    $open = $ids ? rows("SELECT s.*, c.name AS class_name FROM class_sessions s JOIN classes c ON c.id = s.class_id
             WHERE s.class_id IN ($in) AND s.is_open = 1 AND s.id NOT IN (SELECT session_id FROM attendance WHERE student_id = ?) ORDER BY s.session_date DESC", [...$ids, $u['id']]) : [];
    $history = $ids ? rows("SELECT a.*, s.title, s.session_date, c.name AS class_name FROM attendance a
             JOIN class_sessions s ON s.id = a.session_id JOIN classes c ON c.id = s.class_id
             WHERE a.student_id = ? AND s.class_id IN ($in) ORDER BY s.session_date DESC, a.id DESC", [$u['id'], ...$ids]) : [];
    $tot = count($history);
    $cnt = array_count_values(array_column($history, 'status'));
    $pct = $tot ? (int) round(($cnt['Hadir'] ?? 0) / $tot * 100) : 0;
?>
  <section class="panel">
    <div class="panel-head"><h3>⏰ Absen sekarang</h3></div>
    <?php if (!$open): ?>
      <div class="empty-state small">Tidak ada sesi yang sedang dibuka. Tunggu tutor membuka absensi.</div>
    <?php endif; ?>
    <?php foreach ($open as $s): ?>
      <form method="post" class="checkin-card">
        <?= csrf_field() ?><input type="hidden" name="action" value="checkin"><input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>">
        <div><b><?= e($s['title']) ?></b><small><?= e($s['class_name']) ?> · <?= e(tanggal($s['session_date'])) ?></small></div>
        <input class="code-input" name="code" placeholder="KODE" maxlength="8" autocomplete="off" required>
        <button class="btn btn-primary">Absen ✓</button>
      </form>
    <?php endforeach; ?>
  </section>

  <div class="stat-grid">
    <?php stat_card('📅', 'Total pertemuan', $tot, 'mint'); ?>
    <?php stat_card('✅', 'Hadir', $cnt['Hadir'] ?? 0, 'sun'); ?>
    <?php stat_card('📋', 'Izin / Sakit', ($cnt['Izin'] ?? 0) + ($cnt['Sakit'] ?? 0), 'lilac'); ?>
    <?php stat_card('📈', 'Persentase hadir', $pct . '%', 'peach'); ?>
  </div>

  <section class="panel">
    <div class="panel-head"><h3>Riwayat kehadiran</h3></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Tanggal</th><th>Kelas</th><th>Pertemuan</th><th>Status</th><th>Metode</th></tr></thead>
      <tbody>
      <?php foreach ($history as $h): ?>
        <tr><td><?= e(tanggal($h['session_date'])) ?></td><td><?= e($h['class_name']) ?></td><td><?= e($h['title']) ?></td><td><?= status_pill($h['status']) ?></td><td><?= $h['method'] === 'kode' ? 'Kode' : 'Tutor' ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$history): ?><tr><td colspan="5" class="empty-cell">Belum ada riwayat kehadiran.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>

<?php else:
    $sessions = $ids ? rows("SELECT s.*, c.name AS class_name,
        (SELECT COUNT(*) FROM attendance a WHERE a.session_id = s.id AND a.status = 'Hadir') AS hadir,
        (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = s.class_id) AS total
        FROM class_sessions s JOIN classes c ON c.id = s.class_id WHERE s.class_id IN ($in) ORDER BY s.session_date DESC, s.id DESC", $ids) : [];
?>
  <section class="panel">
    <div class="panel-head"><h3>+ Buat pertemuan baru</h3></div>
    <?php if (!$classes): ?><div class="empty-state small">Buat kelas dulu di menu Kelas.</div><?php else: ?>
    <form method="post" class="stack-form row-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="create_session">
      <div class="field"><label>Kelas</label><select name="class_id"><?= class_options($classes, $filter) ?></select></div>
      <div class="field grow"><label>Judul pertemuan</label><input name="title" placeholder="Pertemuan 6: Pythagoras" required></div>
      <div class="field"><label>Tanggal</label><input type="date" name="session_date" value="<?= date('Y-m-d') ?>"></div>
      <button class="btn btn-primary">Buat &amp; buka absensi</button>
    </form>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel-head"><h3>Daftar pertemuan</h3></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Tanggal</th><th>Kelas</th><th>Pertemuan</th><th>Kode</th><th>Hadir</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($sessions as $s): ?>
        <tr>
          <td><?= e(tanggal($s['session_date'])) ?></td><td><?= e($s['class_name']) ?></td><td><?= e($s['title']) ?></td>
          <td><code class="code-chip"><?= e($s['attend_code']) ?></code></td>
          <td><?= (int) $s['hadir'] ?>/<?= (int) $s['total'] ?></td>
          <td><?= $s['is_open'] ? '<span class="status status-ok">Dibuka</span>' : '<span class="status status-info">Tertutup</span>' ?></td>
          <td class="actions">
            <a class="btn btn-soft btn-small" href="<?= e(url('portal/sesi.php?id=' . $s['id'])) ?>">Absensi</a>
            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>"><button class="btn btn-outline btn-small"><?= $s['is_open'] ? 'Tutup' : 'Buka' ?></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$sessions): ?><tr><td colspan="7" class="empty-cell">Belum ada pertemuan.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
<?php endif; ?>

<?php portal_close(); ?>
