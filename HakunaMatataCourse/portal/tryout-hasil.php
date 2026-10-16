<?php
/** Hasil & pembahasan tryout. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_login();
$a = row('SELECT a.*, t.title, t.category FROM tryout_attempts a JOIN tryouts t ON t.id = a.tryout_id WHERE a.id = ?', [(int) ($_GET['id'] ?? 0)]);
if (!$a || !$a['finished_at'] || ((int) $a['student_id'] !== (int) $u['id'] && !is_staff($u))) { http_response_code(404); exit('Hasil tidak ditemukan.'); }

$questions = rows('SELECT * FROM tryout_questions WHERE tryout_id = ? ORDER BY sort_order, id', [$a['tryout_id']]);
$answers = json_decode((string) $a['answers'], true) ?: [];

$bySub = [];
foreach ($questions as $q) {
    $s = $q['subtest'];
    $bySub[$s] ??= ['total' => 0, 'ok' => 0];
    $bySub[$s]['total']++;
    if (($answers[$q['id']] ?? '') === $q['correct']) $bySub[$s]['ok']++;
}
$score = (int) $a['score'];
$msg = $score >= 80 ? 'Luar biasa! 🎉' : ($score >= 60 ? 'Bagus, terus tingkatkan! 💪' : 'Jangan menyerah, pelajari pembahasannya! 📖');

portal_open('Hasil: ' . $a['title'], 'tryout');
?>
<a class="back-link" href="<?= e(url('portal/tryout.php')) ?>">← Kembali ke tryout</a>

<section class="result-hero">
  <div class="score-ring" style="--p:<?= $score ?>"><b><?= $score ?></b><small>/ 100</small></div>
  <div>
    <h2><?= e($msg) ?></h2>
    <p>Kamu menjawab benar <b><?= (int) $a['correct_cnt'] ?></b> dari <b><?= (int) $a['total_cnt'] ?></b> soal pada <?= e(tanggal($a['finished_at'], true)) ?>.</p>
    <div class="hero-actions"><a class="btn btn-primary" href="<?= e(url('portal/tryout-kerjakan.php?id=' . $a['tryout_id'])) ?>">Kerjakan lagi</a></div>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h3>Hasil per subtes</h3></div>
  <?php foreach ($bySub as $name => $s): $pct = (int) round($s['ok'] / $s['total'] * 100); ?>
    <div class="progress-row"><span><?= e($name) ?></span><b><?= $s['ok'] ?>/<?= $s['total'] ?></b><div class="progress"><i style="width:<?= $pct ?>%"></i></div></div>
  <?php endforeach; ?>
</section>

<section class="panel">
  <div class="panel-head"><h3>Pembahasan soal</h3></div>
  <?php foreach ($questions as $i => $q):
    $mine = $answers[$q['id']] ?? '';
    $right = $mine === $q['correct']; ?>
    <article class="review-item <?= $right ? 'is-right' : 'is-wrong' ?>">
      <div class="review-head"><span class="review-no"><?= $i + 1 ?></span><small><?= e($q['subtest']) ?></small><?= $right ? '<span class="status status-ok">Benar</span>' : ($mine === '' ? '<span class="status status-warn">Kosong</span>' : '<span class="status status-bad">Salah</span>') ?></div>
      <p class="review-q"><?= nl2br(e($q['question'])) ?></p>
      <ul class="review-opts">
        <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $L => $col): ?>
          <li class="<?= $L === $q['correct'] ? 'correct' : '' ?> <?= ($L === $mine && !$right) ? 'chosen-wrong' : '' ?>"><b><?= $L ?>.</b> <?= e($q[$col]) ?><?= $L === $q['correct'] ? ' ✓' : '' ?><?= ($L === $mine && !$right) ? ' (jawabanmu)' : '' ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($q['explanation']): ?><div class="explain">💡 <?= e($q['explanation']) ?></div><?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php portal_close(); ?>
