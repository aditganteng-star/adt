<?php
/** Admin: kelola profil tutor yang tampil di website publik. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

require_role('admin');
$colors = ['green' => 'Hijau', 'orange' => 'Oranye', 'blue' => 'Biru', 'purple' => 'Ungu', 'pink' => 'Pink', 'teal' => 'Tosca'];
$edit = null;

if (is_post()) {
    verify_csrf();
    try {
        $action = post('action');
        if ($action === 'save') {
            $id = (int) post('id');
            foreach (['name' => 'Nama', 'subject' => 'Mata pelajaran', 'university' => 'Universitas', 'major' => 'Jurusan'] as $f => $label) {
                if (mb_strlen(post($f)) < 2) throw new RuntimeException("$label wajib diisi.");
            }
            $photo = save_image('photo', 'tutors');
            $levels = array_values(array_intersect(['SD', 'SMP', 'SMA', 'UTBK'], (array) ($_POST['levels'] ?? [])));
            $data = [
                'name' => mb_substr(post('name'), 0, 120), 'subject' => mb_substr(post('subject'), 0, 120), 'university' => mb_substr(post('university'), 0, 150),
                'major' => mb_substr(post('major'), 0, 150), 'bio' => mb_substr(post('bio'), 0, 600), 'achievement' => mb_substr(post('achievement'), 0, 255) ?: null,
                'experience' => mb_substr(post('experience'), 0, 80) ?: null, 'levels' => implode(',', $levels),
                'color' => array_key_exists(post('color'), $colors) ? post('color') : 'green', 'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) post('sort_order'),
            ];
            if ($photo) {
                $data['photo'] = $photo;
                if ($id && ($old = val('SELECT photo FROM tutor_profiles WHERE id = ?', [$id]))) @unlink(ROOT_PATH . '/' . $old);
            } elseif ($id && isset($_POST['remove_photo'])) {
                if ($old = val('SELECT photo FROM tutor_profiles WHERE id = ?', [$id])) @unlink(ROOT_PATH . '/' . $old);
                $data['photo'] = null;
            }
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                run("UPDATE tutor_profiles SET $set WHERE id = ?", [...array_values($data), $id]);
                flash('Profil tutor diperbarui.');
            } else {
                insert('tutor_profiles', $data);
                flash('Profil tutor ditambahkan dan tampil di website.');
            }
        } elseif ($action === 'delete') {
            if ($old = val('SELECT photo FROM tutor_profiles WHERE id = ?', [(int) post('id')])) @unlink(ROOT_PATH . '/' . $old);
            run('DELETE FROM tutor_profiles WHERE id = ?', [(int) post('id')]);
            flash('Profil tutor dihapus.', 'info');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/admin-tutor.php');
}

if (isset($_GET['edit'])) $edit = row('SELECT * FROM tutor_profiles WHERE id = ?', [(int) $_GET['edit']]);
$tutors = rows('SELECT * FROM tutor_profiles ORDER BY sort_order, name');
$f = $edit ?: ['id' => 0, 'photo' => null, 'name' => '', 'subject' => '', 'university' => '', 'major' => '', 'bio' => '', 'achievement' => '', 'experience' => '', 'levels' => 'SMP,SMA', 'color' => 'green', 'is_active' => 1, 'sort_order' => count($tutors)];
$sel = explode(',', (string) $f['levels']);

portal_open('Profil Tutor', 'tutor');
?>
<section class="panel" id="form">
  <div class="panel-head"><h3><?= $edit ? 'Edit profil tutor' : '+ Tambah profil tutor' ?></h3><?php if ($edit): ?><a class="text-link" href="<?= e(url('portal/admin-tutor.php')) ?>">Batal edit</a><?php endif; ?></div>
  <p class="muted small">Data ini tampil di halaman Tutor &amp; beranda. Isi hanya dengan informasi yang sudah diverifikasi dan disetujui tutor.</p>
  <form method="post" enctype="multipart/form-data" class="stack-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
    <div class="two-col">
      <div class="field"><label>Nama *</label><input name="name" value="<?= e($f['name']) ?>" required></div>
      <div class="field"><label>Mata pelajaran *</label><input name="subject" value="<?= e($f['subject']) ?>" required placeholder="Matematika & Fisika"></div>
    </div>
    <div class="two-col">
      <div class="field"><label>Universitas / lulusan dari *</label><input name="university" value="<?= e($f['university']) ?>" required></div>
      <div class="field"><label>Jurusan *</label><input name="major" value="<?= e($f['major']) ?>" required placeholder="S1 Pendidikan Matematika"></div>
    </div>
    <div class="field photo-field"><label>Foto tutor (JPG/PNG/WEBP, maks. 3 MB — disarankan persegi)</label>
      <div class="photo-row"><?= $edit ? tutor_avatar($f, 'lg') : '' ?><div><input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <?php if ($edit && $f['photo']): ?><label class="check-line"><input type="checkbox" name="remove_photo"> Hapus foto saat ini</label><?php endif; ?></div></div></div>
    <div class="field"><label>Deskripsi singkat</label><textarea name="bio" rows="2"><?= e((string) $f['bio']) ?></textarea></div>
    <div class="two-col">
      <div class="field"><label>Prestasi</label><input name="achievement" value="<?= e((string) $f['achievement']) ?>"></div>
      <div class="field"><label>Pengalaman</label><input name="experience" value="<?= e((string) $f['experience']) ?>" placeholder="3 tahun mengajar"></div>
    </div>
    <div class="two-col">
      <div class="field"><label>Jenjang yang diajar</label><div class="chip-row">
        <?php foreach (['SD', 'SMP', 'SMA', 'UTBK'] as $lv): ?><label class="check-chip"><input type="checkbox" name="levels[]" value="<?= $lv ?>" <?= in_array($lv, $sel, true) ? 'checked' : '' ?>><span><?= $lv ?></span></label><?php endforeach; ?></div></div>
      <div class="field"><label>Warna avatar</label><select name="color"><?php foreach ($colors as $k => $l): ?><option value="<?= $k ?>" <?= $f['color'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="two-col">
      <div class="field"><label>Urutan tampil</label><input type="number" name="sort_order" value="<?= (int) $f['sort_order'] ?>"></div>
      <label class="check-line"><input type="checkbox" name="is_active" <?= $f['is_active'] ? 'checked' : '' ?>> Tampilkan di website</label>
    </div>
    <button class="btn btn-primary"><?= $edit ? 'Simpan perubahan' : 'Tambah tutor' ?></button>
  </form>
</section>

<section class="panel">
  <div class="panel-head"><h3>Daftar tutor (<?= count($tutors) ?>)</h3></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Tutor</th><th>Mapel</th><th>Lulusan</th><th>Jenjang</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tutors as $t): ?>
      <tr>
        <td><div class="cell-avatar"><?= tutor_avatar($t, 'sm') ?><b><?= e($t['name']) ?></b></div></td><td><?= e($t['subject']) ?></td><td><?= e($t['university']) ?><br><small class="muted"><?= e($t['major']) ?></small></td>
        <td><?= e((string) $t['levels']) ?></td><td><?= $t['is_active'] ? '<span class="status status-ok">Tampil</span>' : '<span class="status status-info">Disembunyikan</span>' ?></td>
        <td class="actions"><a class="btn btn-soft btn-small" href="<?= e(url('portal/admin-tutor.php?edit=' . $t['id'] . '#form')) ?>">Edit</a>
          <form method="post" class="inline-form" data-confirm="Hapus profil <?= e($t['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="link-danger">Hapus</button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php portal_close(); ?>
