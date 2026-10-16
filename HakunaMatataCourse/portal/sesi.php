<?php
/** Detail satu pertemuan: tutor mengisi absensi seluruh murid. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_role('tutor', 'admin');
$id = (int) ($_GET['id'] ?? 0);
$session = row('SELECT s.*, c.name AS class_name FROM class_sessions s JOIN classes c ON c.id = s.class_id WHERE s.id = ?', [$id]);
if (!$session || !can_manage_class($u, (int) $session['class_id'])) { http_response_code(404); exit('Pertemuan tidak ditemukan.'); }

$students = rows('SELECT u.id, u.name FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.class_id = ? ORDER BY u.name', [$session['class_id']]);
$existing = [];
foreach (rows('SELECT * FROM attendance WHERE session_id = ?', [$id]) as $a) $existing[(int) $a['student_id']] = $a;
$statuses = ['Hadir', 'Izin', 'Sakit', 'Alpa'];

if (is_post()) {
    verify_csrf();
    if (post('action') === 'save') {
        foreach ($students as $st) {
            $sid = (int) $st['id'];
            $status = $_POST['status'][$sid] ?? 'Alpa';
            if (!in_array($status, $statuses, true)) $status = 'Alpa';
            $note = mb_substr(trim((string) ($_POST['note'][$sid] ?? '')), 0, 300) ?: null;
            if (isset($existing[$sid])) {
                run('UPDATE attendance SET status = ?, note = ? WHERE id = ?', [$status, $note, $existing[$sid]['id']]);
            } else {
                insert('attendance', ['session_id' => $id, 'student_id' => $sid, 'status' => $status, 'note' => $note, 'method' => 'tutor']);
            }
        }
        flash('Absensi berhasil disimpan.');
    } elseif (post('action') === 'toggle') {
        run('UPDATE class_sessions SET is_open = ? WHERE id = ?', [$session['is_open'] ? 0 : 1, $id]);
        flash($session['is_open'] ? 'Absensi kode ditutup.' : 'Absensi kode dibuka.', 'info');
    } elseif (post('action') === 'newcode') {
        run('UPDATE class_sessions SET attend_code = ? WHERE id = ?', [random_code(5), $id]);
        flash('Kode absen diganti.', 'info');
    }
    redirect('portal/sesi.php?id=' . $id);
}

portal_open($session['title'], 'absensi');
?>
<a class="back-link" href="<?= e(url('portal/absensi.php')) ?>">← Daftar pertemuan</a>

<div class="class-hero">
  <div>
    <span class="pill"><?= e($session['class_name']) ?></span>
    <h2><?= e($session['title']) ?></h2>
    <p><?= e(tanggal($session['session_date'])) ?> · <?= count($students) ?> murid terdaftar</p>
  </div>
  <div class="code-box">
    <small>Kode absen murid <?= $session['is_open'] ? '(dibuka)' : '(ditutup)' ?></small>
    <b id="sessCode"><?= e($session['attend_code']) ?></b>
    <div class="btn-row">
      <button type="button" class="btn btn-soft btn-small" data-copy="#sessCode">Salin</button>
      <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-outline btn-small"><?= $session['is_open'] ? 'Tutup' : 'Buka' ?></button></form>
      <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="newcode"><button class="btn btn-outline btn-small">Kode baru</button></form>
    </div>
  </div>
</div>

<section class="panel">
  <div class="panel-head"><h3>Daftar hadir</h3><button type="button" class="btn btn-soft btn-small" id="markAllPresent">Hadirkan semua</button></div>
  <?php if (!$students): ?>
    <div class="empty-state small">Belum ada murid di kelas ini.</div>
  <?php else: ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <div class="table-wrap"><table>
      <thead><tr><th>Murid</th><th>Status</th><th>Catatan</th><th>Metode</th></tr></thead>
      <tbody>
      <?php foreach ($students as $st): $sid = (int) $st['id']; $cur = $existing[$sid]['status'] ?? null; ?>
        <tr>
          <td><b><?= e($st['name']) ?></b></td>
          <td><div class="radio-group">
            <?php foreach ($statuses as $s): ?>
              <label class="radio-pill r-<?= strtolower($s) ?>"><input type="radio" name="status[<?= $sid ?>]" value="<?= $s ?>" <?= ($cur ?? 'Alpa') === $s ? 'checked' : '' ?>><span><?= $s ?></span></label>
            <?php endforeach; ?>
          </div></td>
          <td><input class="note-input" name="note[<?= $sid ?>]" value="<?= e((string) ($existing[$sid]['note'] ?? '')) ?>" placeholder="Opsional"></td>
          <td><?= isset($existing[$sid]) ? ($existing[$sid]['method'] === 'kode' ? '📲 Kode' : '✍️ Tutor') : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="form-actions"><button class="btn btn-primary btn-large">Simpan absensi</button></div>
  </form>
  <?php endif; ?>
</section>
<?php portal_close(); ?>
