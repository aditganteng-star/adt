<?php
/** Pendaftaran akun murid (peran selalu "student"). */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_layout.php';

if (logged_in()) redirect('portal/index.php');

$errors = [];
$v = ['name' => '', 'email' => '', 'grade_level' => 'SMP', 'school' => ''];

if (is_post()) {
    verify_csrf();
    foreach ($v as $k => $_) $v[$k] = mb_substr(post($k, $v[$k]), 0, 150);
    $v['email'] = mb_strtolower($v['email']);
    $pw = post('password');

    if (mb_strlen($v['name']) < 2) $errors[] = 'Nama wajib diisi.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email belum valid.';
    if (!in_array($v['grade_level'], ['SD', 'SMP', 'SMA', 'UTBK'], true)) $errors[] = 'Pilih jenjang yang valid.';
    if (strlen($pw) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($pw !== post('password2')) $errors[] = 'Konfirmasi password tidak sama.';
    if (!$errors && row('SELECT id FROM users WHERE email = ?', [$v['email']])) $errors[] = 'Email sudah terdaftar. Silakan masuk.';

    if (!$errors) {
        $id = insert('users', [
            'name' => $v['name'], 'email' => $v['email'], 'password_hash' => password_hash($pw, PASSWORD_DEFAULT),
            'role' => 'student', 'grade_level' => $v['grade_level'], 'school' => $v['school'] ?: null,
        ]);
        login_user(row('SELECT * FROM users WHERE id = ?', [$id]));
        flash('Akun berhasil dibuat. Selamat datang di Hakuna! 🎉');
        redirect('portal/index.php');
    }
}

auth_open('Daftar', 'Buat akun murid', 'Gratis. Akun tutor dibuat oleh admin Hakuna.');
?>
      <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>

      <?php google_block('Daftar dengan Google'); ?>
      <div class="divider"><span>atau isi formulir</span></div>

      <form method="post" class="stack-form" novalidate>
        <?= csrf_field() ?>
        <div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" required autocomplete="name" value="<?= e($v['name']) ?>"></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="email" value="<?= e($v['email']) ?>"></div>
        <div class="two-col">
          <div class="field"><label for="grade">Jenjang</label>
            <select id="grade" name="grade_level"><?php foreach (['SD', 'SMP', 'SMA', 'UTBK'] as $g): ?><option <?= $v['grade_level'] === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label for="school">Sekolah</label><input id="school" name="school" value="<?= e($v['school']) ?>" placeholder="opsional"></div>
        </div>
        <div class="two-col">
          <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="min. 8 karakter"></div>
          <div class="field"><label for="password2">Ulangi password</label><input id="password2" name="password2" type="password" required autocomplete="new-password"></div>
        </div>
        <button class="btn btn-primary btn-full btn-large" type="submit">Buat akun</button>
      </form>
      <p class="auth-switch">Sudah punya akun? <a href="<?= e(url('login.php')) ?>">Masuk</a></p>
<?php auth_close(); ?>
