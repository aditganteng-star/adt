<?php
/** Profil & ganti password. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$u = require_login();

if (is_post()) {
    verify_csrf();
    try {
        if (post('action') === 'profile') {
            if (mb_strlen(post('name')) < 2) throw new RuntimeException('Nama wajib diisi.');
            $grade = in_array(post('grade_level'), ['SD', 'SMP', 'SMA', 'UTBK'], true) ? post('grade_level') : $u['grade_level'];
            run('UPDATE users SET name = ?, phone = ?, school = ?, grade_level = ? WHERE id = ?',
                [mb_substr(post('name'), 0, 120), mb_substr(post('phone'), 0, 30) ?: null, mb_substr(post('school'), 0, 150) ?: null, $u['role'] === 'student' ? $grade : null, $u['id']]);
            flash('Profil diperbarui.');
        } elseif (post('action') === 'password') {
            if ($u['password_hash'] && !password_verify(post('current'), $u['password_hash'])) throw new RuntimeException('Password saat ini salah.');
            if (strlen(post('new')) < 8) throw new RuntimeException('Password baru minimal 8 karakter.');
            if (post('new') !== post('new2')) throw new RuntimeException('Konfirmasi password tidak sama.');
            run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash(post('new'), PASSWORD_DEFAULT), $u['id']]);
            flash('Password berhasil diganti.');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('portal/profil.php');
}

portal_open('Profil Saya', 'profil');
?>
<div class="grid-2">
  <section class="panel">
    <div class="profile-head">
      <?php if ($u['avatar_url']): ?><img class="avatar-lg" src="<?= e($u['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><span class="avatar-lg"><?= e(initials($u['name'])) ?></span><?php endif; ?>
      <div><h3><?= e($u['name']) ?></h3><small><?= e($u['email']) ?></small><br><span class="role-badge role-<?= e($u['role']) ?>"><?= e(ROLE_LABELS[$u['role']]) ?></span>
        <?= $u['google_id'] ? '<span class="status status-ok">Terhubung Google</span>' : '' ?></div>
    </div>
    <form method="post" class="stack-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="field"><label>Nama</label><input name="name" value="<?= e($u['name']) ?>" required></div>
      <div class="two-col">
        <div class="field"><label>No. WhatsApp</label><input name="phone" value="<?= e((string) $u['phone']) ?>"></div>
        <?php if ($u['role'] === 'student'): ?>
          <div class="field"><label>Jenjang</label><select name="grade_level"><?php foreach (['SD', 'SMP', 'SMA', 'UTBK'] as $g): ?><option <?= $u['grade_level'] === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
      </div>
      <div class="field"><label>Sekolah / instansi</label><input name="school" value="<?= e((string) $u['school']) ?>"></div>
      <button class="btn btn-primary">Simpan profil</button>
    </form>
  </section>
  <section class="panel">
    <div class="panel-head"><h3>🔒 <?= $u['password_hash'] ? 'Ganti password' : 'Buat password' ?></h3></div>
    <?php if (!$u['password_hash']): ?><p class="muted small">Akunmu masuk lewat Google. Kamu bisa membuat password agar bisa login dengan email juga.</p><?php endif; ?>
    <form method="post" class="stack-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <?php if ($u['password_hash']): ?><div class="field"><label>Password saat ini</label><input type="password" name="current" required autocomplete="current-password"></div><?php endif; ?>
      <div class="field"><label>Password baru</label><input type="password" name="new" minlength="8" required autocomplete="new-password"></div>
      <div class="field"><label>Ulangi password baru</label><input type="password" name="new2" required autocomplete="new-password"></div>
      <button class="btn btn-primary">Simpan password</button>
    </form>
  </section>
</div>
<?php portal_close(); ?>
