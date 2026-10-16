<?php
/** Materi belajar: murid melihat/unduh; tutor/admin mengunggah. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_login();
$classes  = visible_classes($u);
$classIds = array_map(fn($c) => (int) $c['id'], $classes);
$filter   = (int) ($_GET['kelas'] ?? 0);
if ($filter && !in_array($filter, $classIds, true)) $filter = 0;

if (is_post() && is_staff($u)) {
    verify_csrf();
    try {
        if (post('action') === 'add') {
            $cid = (int) post('class_id');
            if (!can_manage_class($u, $cid)) throw new RuntimeException('Kamu tidak memiliki izin untuk kelas ini.');
            if (mb_strlen(post('title')) < 2) throw new RuntimeException('Judul materi wajib diisi.');
            $kind = in_array(post('kind'), ['file', 'link', 'video'], true) ? post('kind') : 'link';
            $data = ['class_id' => $cid, 'title' => mb_substr(post('title'), 0, 190), 'description' => mb_substr(post('description'), 0, 600) ?: null, 'kind' => $kind, 'created_by' => $u['id']];
            if ($kind === 'file') {
                $up = save_upload('file', 'materials');
                if (!$up) throw new RuntimeException('Pilih file yang akan diunggah.');
                [$data['file_name'], $data['file_path']] = $up;
            } else {
                $link = post('url');
                if (!filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $link)) throw new RuntimeException('Masukkan tautan yang valid (diawali http:// atau https://).');
                $data['url'] = mb_substr($link, 0, 1000);
            }
            insert('materials', $data);
            flash('Materi berhasil ditambahkan.');
        } elseif (post('action') === 'delete') {
            $m = row('SELECT * FROM materials WHERE id = ?', [(int) post('id')]);
            if ($m && can_manage_class($u, (int) $m['class_id'])) {
                if ($m['file_path']) @unlink(STORAGE_PATH . '/' . $m['file_path']);
                run('DELETE FROM materials WHERE id = ?', [$m['id']]);
                flash('Materi dihapus.', 'info');
            }
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/materi.php' . ($filter ? '?kelas=' . $filter : ''));
}

portal_open('Materi Belajar', 'materi');
$ids = $filter ? [$filter] : $classIds;
$in  = $ids ? implode(',', array_fill(0, count($ids), '?')) : 'NULL';
$materials = $ids ? rows("SELECT m.*, c.name AS class_name, u.name AS author FROM materials m
    JOIN classes c ON c.id = m.class_id JOIN users u ON u.id = m.created_by WHERE m.class_id IN ($in) ORDER BY m.id DESC", $ids) : [];
$icons = ['file' => '📄', 'link' => '🔗', 'video' => '▶️'];
?>

<?php if (count($classes) > 1): ?>
  <form method="get" class="filter-inline"><label for="kelas">Kelas:</label>
    <select id="kelas" name="kelas" onchange="this.form.submit()"><option value="0">Semua kelas</option><?= class_options($classes, $filter) ?></select></form>
<?php endif; ?>

<?php if (is_staff($u) && $classes): ?>
  <section class="panel">
    <div class="panel-head"><h3>+ Tambah materi</h3></div>
    <form method="post" enctype="multipart/form-data" class="stack-form" id="materialForm">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <div class="two-col">
        <div class="field"><label>Kelas</label><select name="class_id"><?= class_options($classes, $filter) ?></select></div>
        <div class="field"><label>Jenis</label>
          <select name="kind" id="kindSel"><option value="file">📄 Unggah file</option><option value="link">🔗 Tautan</option><option value="video">▶️ Video (YouTube/Drive)</option></select></div>
      </div>
      <div class="field"><label>Judul materi *</label><input name="title" required placeholder="Contoh: Rangkuman Bab 3"></div>
      <div class="field"><label>Deskripsi</label><textarea name="description" rows="2"></textarea></div>
      <div class="field" id="fileField"><label>File (maks. <?= UPLOAD_MAX_MB ?> MB: <?= e(implode(', ', UPLOAD_ALLOWED)) ?>)</label><input type="file" name="file"></div>
      <div class="field" id="urlField" hidden><label>Tautan</label><input type="url" name="url" placeholder="https://…"></div>
      <button class="btn btn-primary">Simpan materi</button>
    </form>
  </section>
<?php endif; ?>

<section class="panel">
  <div class="panel-head"><h3>Daftar materi (<?= count($materials) ?>)</h3></div>
  <?php if (!$materials): ?><div class="empty-state small">Belum ada materi<?= $u['role'] === 'student' ? '. Bergabunglah ke kelas untuk melihat materi.' : '.' ?></div><?php endif; ?>
  <div class="resource-list">
    <?php foreach ($materials as $m): ?>
      <article class="resource-item">
        <span class="row-ico big"><?= $icons[$m['kind']] ?? '📄' ?></span>
        <div class="grow">
          <b><?= e($m['title']) ?></b>
          <?php if ($m['description']): ?><p><?= e($m['description']) ?></p><?php endif; ?>
          <small><?= e($m['class_name']) ?> · <?= e($m['author']) ?> · <?= e(tanggal($m['created_at'])) ?><?= $m['file_name'] ? ' · ' . e($m['file_name']) : '' ?></small>
        </div>
        <div class="actions">
          <?php if ($m['kind'] === 'file'): ?>
            <a class="btn btn-soft btn-small" href="<?= e(url('portal/file.php?t=material&id=' . $m['id'])) ?>" target="_blank" rel="noopener">Unduh</a>
          <?php else: ?>
            <a class="btn btn-soft btn-small" href="<?= e((string) $m['url']) ?>" target="_blank" rel="noopener noreferrer">Buka ↗</a>
          <?php endif; ?>
          <?php if (is_staff($u) && can_manage_class($u, (int) $m['class_id'])): ?>
            <form method="post" class="inline-form" data-confirm="Hapus materi ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="link-danger">Hapus</button></form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php portal_close(); ?>
