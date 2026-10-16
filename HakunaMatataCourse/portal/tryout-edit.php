<?php
/** Mengelola soal tryout (tambah satu per satu atau impor massal). */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_role('tutor', 'admin');
$id = (int) ($_GET['id'] ?? 0);
$t = row('SELECT * FROM tryouts WHERE id = ?', [$id]);
if (!$t || ($u['role'] !== 'admin' && (int) $t['created_by'] !== (int) $u['id'])) { http_response_code(403); exit('Kamu hanya bisa mengelola tryout buatanmu.'); }

if (is_post()) {
    verify_csrf();
    try {
        $action = post('action');
        if ($action === 'meta') {
            run('UPDATE tryouts SET title = ?, description = ?, duration_min = ? WHERE id = ?',
                [mb_substr(post('title'), 0, 190), mb_substr(post('description'), 0, 500) ?: null, max(5, min(180, (int) post('duration_min', '30'))), $id]);
            flash('Info tryout diperbarui.');
        } elseif ($action === 'add') {
            $correct = strtoupper(post('correct'));
            if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) throw new RuntimeException('Kunci jawaban harus A, B, C, atau D.');
            foreach (['question', 'option_a', 'option_b', 'option_c', 'option_d'] as $f) if (post($f) === '') throw new RuntimeException('Soal dan semua pilihan jawaban wajib diisi.');
            insert('tryout_questions', ['tryout_id' => $id, 'subtest' => mb_substr(post('subtest') ?: 'Umum', 0, 80), 'question' => post('question'),
                'option_a' => mb_substr(post('option_a'), 0, 300), 'option_b' => mb_substr(post('option_b'), 0, 300), 'option_c' => mb_substr(post('option_c'), 0, 300), 'option_d' => mb_substr(post('option_d'), 0, 300),
                'correct' => $correct, 'explanation' => mb_substr(post('explanation'), 0, 800) ?: null, 'sort_order' => (int) val('SELECT COALESCE(MAX(sort_order),0)+1 FROM tryout_questions WHERE tryout_id = ?', [$id])]);
            flash('Soal ditambahkan.');
        } elseif ($action === 'import') {
            $added = 0;
            foreach (preg_split('/\R/', post('bulk')) ?: [] as $line) {
                $p = array_map('trim', explode('|', $line));
                if (count($p) < 7) continue;
                [$sub, $q, $a, $b, $c, $d, $k] = $p;
                $k = strtoupper($k);
                if ($q === '' || !in_array($k, ['A', 'B', 'C', 'D'], true)) continue;
                insert('tryout_questions', ['tryout_id' => $id, 'subtest' => mb_substr($sub ?: 'Umum', 0, 80), 'question' => $q, 'option_a' => mb_substr($a, 0, 300), 'option_b' => mb_substr($b, 0, 300),
                    'option_c' => mb_substr($c, 0, 300), 'option_d' => mb_substr($d, 0, 300), 'correct' => $k, 'explanation' => mb_substr($p[7] ?? '', 0, 800) ?: null,
                    'sort_order' => (int) val('SELECT COALESCE(MAX(sort_order),0)+1 FROM tryout_questions WHERE tryout_id = ?', [$id])]);
                $added++;
            }
            if (!$added) throw new RuntimeException('Tidak ada baris valid. Periksa format impor.');
            flash("$added soal berhasil diimpor.");
        } elseif ($action === 'delete_q') {
            run('DELETE FROM tryout_questions WHERE id = ? AND tryout_id = ?', [(int) post('qid'), $id]);
            flash('Soal dihapus.', 'info');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/tryout-edit.php?id=' . $id);
}

$questions = rows('SELECT * FROM tryout_questions WHERE tryout_id = ? ORDER BY sort_order, id', [$id]);
portal_open('Soal: ' . $t['title'], 'tryout');
?>
<a class="back-link" href="<?= e(url('portal/tryout.php')) ?>">← Semua tryout</a>

<section class="panel">
  <div class="panel-head"><h3>Info tryout</h3><?= $t['is_published'] ? '<span class="status status-ok">Terbit</span>' : '<span class="status status-warn">Draft — terbitkan dari daftar tryout</span>' ?></div>
  <form method="post" class="stack-form row-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="meta">
    <div class="field grow"><label>Judul</label><input name="title" value="<?= e($t['title']) ?>" required></div>
    <div class="field grow"><label>Deskripsi</label><input name="description" value="<?= e((string) $t['description']) ?>"></div>
    <div class="field"><label>Durasi (menit)</label><input type="number" name="duration_min" value="<?= (int) $t['duration_min'] ?>" min="5" max="180"></div>
    <button class="btn btn-primary">Simpan</button>
  </form>
</section>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head"><h3>+ Tambah satu soal</h3></div>
    <form method="post" class="stack-form compact">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <input name="subtest" placeholder="Subtes (mis. Penalaran Umum)">
      <textarea name="question" rows="3" placeholder="Teks soal *" required></textarea>
      <input name="option_a" placeholder="Pilihan A *" required><input name="option_b" placeholder="Pilihan B *" required>
      <input name="option_c" placeholder="Pilihan C *" required><input name="option_d" placeholder="Pilihan D *" required>
      <div class="two-col"><select name="correct" required><option value="">Kunci jawaban</option><option>A</option><option>B</option><option>C</option><option>D</option></select><input name="explanation" placeholder="Pembahasan (opsional)"></div>
      <button class="btn btn-primary btn-small">Tambah soal</button>
    </form>
  </section>
  <section class="panel">
    <div class="panel-head"><h3>Impor banyak soal</h3></div>
    <p class="muted small">Satu soal per baris, dipisah tanda <code>|</code>:<br><code>Subtes | Soal | A | B | C | D | Kunci | Pembahasan</code></p>
    <form method="post" class="stack-form compact">
      <?= csrf_field() ?><input type="hidden" name="action" value="import">
      <textarea name="bulk" rows="7" placeholder="Matematika | 2 + 3 = ? | 4 | 5 | 6 | 7 | B | Hasil 2 ditambah 3 adalah 5"></textarea>
      <button class="btn btn-soft btn-small">Impor soal</button>
    </form>
  </section>
</div>

<section class="panel">
  <div class="panel-head"><h3>Daftar soal (<?= count($questions) ?>)</h3></div>
  <?php if (!$questions): ?><div class="empty-state small">Belum ada soal.</div><?php endif; ?>
  <?php foreach ($questions as $i => $q): ?>
    <article class="review-item">
      <div class="review-head"><span class="review-no"><?= $i + 1 ?></span><small><?= e($q['subtest']) ?></small><span class="status status-ok">Kunci: <?= e($q['correct']) ?></span>
        <form method="post" class="inline-form" data-confirm="Hapus soal ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_q"><input type="hidden" name="qid" value="<?= (int) $q['id'] ?>"><button class="link-danger">Hapus</button></form></div>
      <p class="review-q"><?= nl2br(e($q['question'])) ?></p>
      <ul class="review-opts"><?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $L => $col): ?><li class="<?= $L === $q['correct'] ? 'correct' : '' ?>"><b><?= $L ?>.</b> <?= e($q[$col]) ?></li><?php endforeach; ?></ul>
      <?php if ($q['explanation']): ?><div class="explain">💡 <?= e($q['explanation']) ?></div><?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php portal_close(); ?>
