<?php
/** Tugas: murid mengumpulkan & melihat nilai; tutor/admin membuat tugas & menilai. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_login();
$classes  = visible_classes($u);
$classIds = array_map(fn($c) => (int) $c['id'], $classes);
$id       = (int) ($_GET['id'] ?? 0);
$filter   = (int) ($_GET['kelas'] ?? 0);
if ($filter && !in_array($filter, $classIds, true)) $filter = 0;

/* ---------------- Aksi POST ---------------- */
if (is_post()) {
    verify_csrf();
    $action = post('action');
    $back = 'portal/tugas.php' . ($id ? '?id=' . $id : ($filter ? '?kelas=' . $filter : ''));
    try {
        if ($action === 'create' && is_staff($u)) {
            $cid = (int) post('class_id');
            if (!can_manage_class($u, $cid)) throw new RuntimeException('Kamu tidak memiliki izin untuk kelas ini.');
            if (mb_strlen(post('title')) < 2) throw new RuntimeException('Judul tugas wajib diisi.');
            $due = post('due_date');
            $newId = insert('assignments', ['class_id' => $cid, 'title' => mb_substr(post('title'), 0, 190), 'description' => mb_substr(post('description'), 0, 4000) ?: null,
                'due_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) ? $due : null, 'max_score' => 100, 'created_by' => $u['id']]);
            flash('Tugas dibuat dan langsung tampil di dashboard murid.');
            redirect('portal/tugas.php?id=' . $newId);
        }

        if ($action === 'submit' && $u['role'] === 'student') {
            $a = row('SELECT * FROM assignments WHERE id = ?', [(int) post('assignment_id')]);
            if (!$a || !in_array((int) $a['class_id'], $classIds, true)) throw new RuntimeException('Tugas tidak ditemukan.');
            $old = row('SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?', [$a['id'], $u['id']]);
            if ($old && $old['status'] === 'Dinilai') throw new RuntimeException('Tugas sudah dinilai dan tidak bisa diubah.');
            $answer = mb_substr(post('answer'), 0, 5000);
            $up = save_upload('file', 'submissions');
            if ($answer === '' && !$up && !($old && $old['file_path'])) throw new RuntimeException('Isi jawaban atau lampirkan file.');
            $fileName = $up[0] ?? ($old['file_name'] ?? null);
            $filePath = $up[1] ?? ($old['file_path'] ?? null);
            if ($old) {
                if ($up && $old['file_path']) @unlink(STORAGE_PATH . '/' . $old['file_path']);
                run('UPDATE submissions SET answer = ?, file_name = ?, file_path = ?, submitted_at = ? WHERE id = ?', [$answer ?: null, $fileName, $filePath, date('Y-m-d H:i:s'), $old['id']]);
            } else {
                insert('submissions', ['assignment_id' => $a['id'], 'student_id' => $u['id'], 'answer' => $answer ?: null, 'file_name' => $fileName, 'file_path' => $filePath, 'submitted_at' => date('Y-m-d H:i:s')]);
            }
            flash('Tugas berhasil dikumpulkan. ✅');
            redirect('portal/tugas.php?id=' . $a['id']);
        }

        if ($action === 'grade' && is_staff($u)) {
            $s = row('SELECT s.*, a.class_id, a.max_score FROM submissions s JOIN assignments a ON a.id = s.assignment_id WHERE s.id = ?', [(int) post('submission_id')]);
            if (!$s || !can_manage_class($u, (int) $s['class_id'])) throw new RuntimeException('Tidak ada izin.');
            $score = (int) post('score');
            if ($score < 0 || $score > (int) $s['max_score']) throw new RuntimeException('Nilai harus antara 0 dan ' . $s['max_score'] . '.');
            run("UPDATE submissions SET score = ?, feedback = ?, status = 'Dinilai' WHERE id = ?", [$score, mb_substr(post('feedback'), 0, 600) ?: null, $s['id']]);
            flash('Nilai disimpan.');
        }

        if ($action === 'delete' && is_staff($u)) {
            $a = row('SELECT * FROM assignments WHERE id = ?', [(int) post('assignment_id')]);
            if ($a && can_manage_class($u, (int) $a['class_id'])) {
                foreach (rows('SELECT file_path FROM submissions WHERE assignment_id = ?', [$a['id']]) as $f) if ($f['file_path']) @unlink(STORAGE_PATH . '/' . $f['file_path']);
                run('DELETE FROM submissions WHERE assignment_id = ?', [$a['id']]);
                run('DELETE FROM assignments WHERE id = ?', [$a['id']]);
                flash('Tugas dihapus.', 'info');
                redirect('portal/tugas.php');
            }
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect($back);
}

/* ---------------- Detail tugas ---------------- */
if ($id) {
    $a = row('SELECT a.*, c.name AS class_name, u.name AS author FROM assignments a JOIN classes c ON c.id = a.class_id JOIN users u ON u.id = a.created_by WHERE a.id = ?', [$id]);
    if (!$a || !can_access_class($u, (int) $a['class_id'])) { http_response_code(404); exit('Tugas tidak ditemukan.'); }
    $manage = is_staff($u) && can_manage_class($u, (int) $a['class_id']);
    portal_open($a['title'], 'tugas');
    $late = $a['due_date'] && $a['due_date'] < date('Y-m-d');
    ?>
    <a class="back-link" href="<?= e(url('portal/tugas.php')) ?>">← Semua tugas</a>
    <section class="panel">
      <div class="panel-head">
        <div><span class="pill"><?= e($a['class_name']) ?></span><h2 class="panel-h2"><?= e($a['title']) ?></h2></div>
        <div class="meta-col"><small>Tenggat</small><b class="<?= $late ? 'text-bad' : '' ?>"><?= e(tanggal($a['due_date'])) ?></b></div>
      </div>
      <div class="prose"><?= nl2br(e((string) $a['description'])) ?></div>
      <small class="muted">Dibuat oleh <?= e($a['author']) ?> · <?= e(tanggal($a['created_at'])) ?></small>
    </section>

    <?php if ($u['role'] === 'student'):
        $sub = row('SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?', [$id, $u['id']]); ?>
      <section class="panel">
        <div class="panel-head"><h3>Pengumpulan kamu</h3><?= $sub ? status_pill($sub['status']) : '<span class="status status-warn">Belum dikumpulkan</span>' ?></div>
        <?php if ($sub && $sub['status'] === 'Dinilai'): ?>
          <div class="score-box"><div class="score-big"><?= (int) $sub['score'] ?><small>/<?= (int) $a['max_score'] ?></small></div><div><b>Feedback tutor</b><p><?= e((string) ($sub['feedback'] ?: 'Tidak ada catatan.')) ?></p></div></div>
          <div class="prose"><b>Jawabanmu:</b><br><?= nl2br(e((string) $sub['answer'])) ?></div>
          <?php if ($sub['file_path']): ?><a class="btn btn-soft btn-small" href="<?= e(url('portal/file.php?t=submission&id=' . $sub['id'])) ?>" target="_blank">📎 <?= e((string) $sub['file_name']) ?></a><?php endif; ?>
        <?php else: ?>
          <form method="post" enctype="multipart/form-data" class="stack-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="submit"><input type="hidden" name="assignment_id" value="<?= $id ?>">
            <div class="field"><label>Jawaban</label><textarea name="answer" rows="5" placeholder="Tulis jawabanmu di sini…"><?= e((string) ($sub['answer'] ?? '')) ?></textarea></div>
            <div class="field"><label>Lampiran file (opsional, maks. <?= UPLOAD_MAX_MB ?> MB)</label><input type="file" name="file">
              <?php if ($sub && $sub['file_path']): ?><small>File saat ini: <a href="<?= e(url('portal/file.php?t=submission&id=' . $sub['id'])) ?>" target="_blank"><?= e((string) $sub['file_name']) ?></a> (unggah baru untuk mengganti)</small><?php endif; ?></div>
            <button class="btn btn-primary"><?= $sub ? 'Perbarui pengumpulan' : 'Kumpulkan tugas' ?></button>
            <?php if ($late): ?><small class="text-bad">Tenggat sudah lewat — tugas tetap bisa dikumpulkan, tutor akan melihat waktunya.</small><?php endif; ?>
          </form>
        <?php endif; ?>
      </section>

    <?php elseif ($manage):
        $students = rows('SELECT u.id, u.name FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.class_id = ? ORDER BY u.name', [$a['class_id']]);
        $subs = [];
        foreach (rows('SELECT * FROM submissions WHERE assignment_id = ?', [$id]) as $s) $subs[(int) $s['student_id']] = $s;
    ?>
      <section class="panel">
        <div class="panel-head"><h3>Pengumpulan murid (<?= count($subs) ?>/<?= count($students) ?>)</h3>
          <form method="post" class="inline-form" data-confirm="Hapus tugas beserta semua pengumpulannya?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="assignment_id" value="<?= $id ?>"><button class="link-danger">Hapus tugas</button></form></div>
        <div class="table-wrap"><table>
          <thead><tr><th>Murid</th><th>Jawaban</th><th>Waktu</th><th style="min-width:260px">Nilai &amp; feedback</th></tr></thead>
          <tbody>
          <?php foreach ($students as $st): $s = $subs[(int) $st['id']] ?? null; ?>
            <tr>
              <td><b><?= e($st['name']) ?></b><br><?= $s ? status_pill($s['status']) : '<span class="status status-bad">Belum</span>' ?></td>
              <?php if ($s): ?>
                <td class="wrap"><?= nl2br(e(mb_strimwidth((string) $s['answer'], 0, 300, '…'))) ?>
                  <?php if ($s['file_path']): ?><br><a href="<?= e(url('portal/file.php?t=submission&id=' . $s['id'])) ?>" target="_blank">📎 <?= e((string) $s['file_name']) ?></a><?php endif; ?></td>
                <td><?= e(tanggal($s['submitted_at'], true)) ?></td>
                <td><form method="post" class="grade-form"><?= csrf_field() ?><input type="hidden" name="action" value="grade"><input type="hidden" name="submission_id" value="<?= (int) $s['id'] ?>">
                  <input type="number" name="score" min="0" max="<?= (int) $a['max_score'] ?>" value="<?= e((string) $s['score']) ?>" placeholder="0–<?= (int) $a['max_score'] ?>" required>
                  <input name="feedback" value="<?= e((string) $s['feedback']) ?>" placeholder="Feedback">
                  <button class="btn btn-primary btn-small">Simpan</button></form></td>
              <?php else: ?><td colspan="3" class="muted">Belum mengumpulkan.</td><?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (!$students): ?><tr><td colspan="4" class="empty-cell">Belum ada murid di kelas ini.</td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </section>
    <?php endif;
    portal_close();
    exit;
}

/* ---------------- Daftar tugas ---------------- */
portal_open($u['role'] === 'student' ? 'Tugas' : 'Tugas & Penilaian', 'tugas');
$ids = $filter ? [$filter] : $classIds;
$in  = $ids ? implode(',', array_fill(0, count($ids), '?')) : 'NULL';
$assignments = $ids ? rows("SELECT a.*, c.name AS class_name,
    (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS sub_count,
    (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.status = 'Dikumpulkan') AS ungraded,
    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = a.class_id) AS stu_count
    FROM assignments a JOIN classes c ON c.id = a.class_id WHERE a.class_id IN ($in)
    ORDER BY (a.due_date IS NULL), a.due_date DESC, a.id DESC", $ids) : [];
$mine = [];
if ($u['role'] === 'student') foreach (rows('SELECT * FROM submissions WHERE student_id = ?', [$u['id']]) as $s) $mine[(int) $s['assignment_id']] = $s;
?>

<?php if (count($classes) > 1): ?>
  <form method="get" class="filter-inline"><label for="kelas">Kelas:</label>
    <select id="kelas" name="kelas" onchange="this.form.submit()"><option value="0">Semua kelas</option><?= class_options($classes, $filter) ?></select></form>
<?php endif; ?>

<?php if (is_staff($u) && $classes): ?>
  <section class="panel">
    <div class="panel-head"><h3>+ Buat tugas baru</h3></div>
    <form method="post" class="stack-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="two-col">
        <div class="field"><label>Kelas</label><select name="class_id"><?= class_options($classes, $filter) ?></select></div>
        <div class="field"><label>Tenggat</label><input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>"></div>
      </div>
      <div class="field"><label>Judul tugas *</label><input name="title" required placeholder="Contoh: Latihan Bab 4"></div>
      <div class="field"><label>Instruksi</label><textarea name="description" rows="4" placeholder="Jelaskan apa yang harus dikerjakan murid…"></textarea></div>
      <button class="btn btn-primary">Buat tugas</button>
    </form>
  </section>
<?php endif; ?>

<section class="panel">
  <div class="panel-head"><h3>Daftar tugas (<?= count($assignments) ?>)</h3></div>
  <?php if (!$assignments): ?><div class="empty-state small">Belum ada tugas.</div><?php endif; ?>
  <?php foreach ($assignments as $a):
    $late = $a['due_date'] && $a['due_date'] < date('Y-m-d');
    $s = $mine[(int) $a['id']] ?? null; ?>
    <a class="list-row" href="<?= e(url('portal/tugas.php?id=' . $a['id'])) ?>">
      <span class="row-ico">📝</span>
      <div class="grow"><b><?= e($a['title']) ?></b><small><?= e($a['class_name']) ?> · Tenggat <?= e(tanggal($a['due_date'])) ?></small></div>
      <?php if ($u['role'] === 'student'): ?>
        <?php if ($s && $s['status'] === 'Dinilai'): ?><span class="status status-ok">Nilai <?= (int) $s['score'] ?></span>
        <?php elseif ($s): ?><span class="status status-info">Dikumpulkan</span>
        <?php else: ?><span class="status <?= $late ? 'status-bad' : 'status-warn' ?>"><?= $late ? 'Terlambat' : 'Belum' ?></span><?php endif; ?>
      <?php else: ?>
        <span class="status status-info"><?= (int) $a['sub_count'] ?>/<?= (int) $a['stu_count'] ?> terkumpul</span>
        <?php if ((int) $a['ungraded'] > 0): ?><span class="status status-warn"><?= (int) $a['ungraded'] ?> perlu dinilai</span><?php endif; ?>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</section>

<?php portal_close(); ?>
