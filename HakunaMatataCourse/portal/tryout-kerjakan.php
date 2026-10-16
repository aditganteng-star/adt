<?php
/** Mengerjakan tryout bertimer. Penilaian dilakukan di server. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_role('student');
$id = (int) ($_GET['id'] ?? $_POST['tryout_id'] ?? 0);
$t = row('SELECT * FROM tryouts WHERE id = ? AND is_published = 1', [$id]);
if (!$t) { http_response_code(404); exit('Tryout tidak ditemukan.'); }

if (!has_entitlement($u, $t['package_id'] ? (int) $t['package_id'] : null)) {
    flash('Tryout ini khusus pembeli paket. Silakan beli paketnya dulu.', 'error');
    redirect('portal/tryout.php');
}
$questions = rows('SELECT * FROM tryout_questions WHERE tryout_id = ? ORDER BY sort_order, id', [$id]);
if (!$questions) { flash('Soal untuk tryout ini belum tersedia.', 'error'); redirect('portal/tryout.php'); }

// Cari percobaan yang sedang berjalan (belum selesai); buat baru jika tidak ada.
$attempt = row('SELECT * FROM tryout_attempts WHERE tryout_id = ? AND student_id = ? AND finished_at IS NULL ORDER BY id DESC LIMIT 1', [$id, $u['id']]);
// Percobaan lama yang waktunya sudah habis (murid pernah meninggalkan halaman) dibuang, mulai yang baru.
if ($attempt && !is_post() && strtotime($attempt['started_at']) + (int) $t['duration_min'] * 60 + 60 < time()) {
    run('DELETE FROM tryout_attempts WHERE id = ?', [$attempt['id']]);
    $attempt = null;
}
if (!$attempt) {
    $aid = insert('tryout_attempts', ['tryout_id' => $id, 'student_id' => $u['id'], 'total_cnt' => count($questions), 'started_at' => date('Y-m-d H:i:s')]);
    $attempt = row('SELECT * FROM tryout_attempts WHERE id = ?', [$aid]);
}

if (is_post()) {
    verify_csrf();
    $given = $_POST['ans'] ?? [];
    $answers = [];
    $correct = 0;
    foreach ($questions as $q) {
        $a = is_array($given) ? ($given[$q['id']] ?? '') : '';
        $a = in_array($a, ['A', 'B', 'C', 'D'], true) ? $a : '';
        $answers[$q['id']] = $a;
        if ($a !== '' && $a === $q['correct']) $correct++;
    }
    $score = (int) round($correct / count($questions) * 100);
    run('UPDATE tryout_attempts SET answers = ?, correct_cnt = ?, total_cnt = ?, score = ?, finished_at = ? WHERE id = ?',
        [json_encode($answers), $correct, count($questions), $score, date('Y-m-d H:i:s'), $attempt['id']]);
    redirect('portal/tryout-hasil.php?id=' . $attempt['id']);
}

$deadline = strtotime($attempt['started_at']) + (int) $t['duration_min'] * 60;
$remaining = max(0, $deadline - time());

portal_open($t['title'], 'tryout');
?>
<form method="post" id="tryoutForm" class="tryout-run" data-remaining="<?= $remaining ?>" data-total="<?= count($questions) ?>">
  <?= csrf_field() ?><input type="hidden" name="tryout_id" value="<?= $id ?>">

  <div class="tryout-bar">
    <div><b><?= e($t['title']) ?></b><small id="answeredInfo">0 / <?= count($questions) ?> terjawab</small></div>
    <div class="timer" id="timer" aria-live="off">--:--</div>
    <button type="submit" class="btn btn-primary" id="finishBtn" data-confirm-submit="Selesaikan tryout sekarang? Jawaban tidak bisa diubah setelahnya.">Selesai</button>
  </div>

  <div class="tryout-layout">
    <div class="q-area">
      <?php foreach ($questions as $i => $q): ?>
        <section class="q-card" data-q="<?= $i ?>" <?= $i ? 'hidden' : '' ?>>
          <small class="q-sub"><?= e($q['subtest']) ?> · Soal <?= $i + 1 ?> dari <?= count($questions) ?></small>
          <h3><?= nl2br(e($q['question'])) ?></h3>
          <div class="q-options">
            <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $letter => $col): ?>
              <label class="opt"><input type="radio" name="ans[<?= (int) $q['id'] ?>]" value="<?= $letter ?>"><span class="opt-letter"><?= $letter ?></span><span><?= e($q[$col]) ?></span></label>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
      <div class="q-nav">
        <button type="button" class="btn btn-soft" id="prevQ">← Sebelumnya</button>
        <button type="button" class="btn btn-primary" id="nextQ">Berikutnya →</button>
      </div>
    </div>
    <aside class="q-grid-card">
      <b>Navigasi soal</b>
      <div class="q-grid" id="qGrid">
        <?php foreach ($questions as $i => $_): ?><button type="button" class="q-dot <?= $i === 0 ? 'current' : '' ?>" data-go="<?= $i ?>"><?= $i + 1 ?></button><?php endforeach; ?>
      </div>
      <small class="muted">Biru = soal aktif · Hijau = sudah dijawab</small>
    </aside>
  </div>
</form>
<?php portal_close(); ?>
