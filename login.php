<?php
/** Halaman login: Google + email/password. */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_layout.php';

if (logged_in()) redirect('portal/index.php');

$next  = (string) ($_GET['next'] ?? $_POST['next'] ?? '');
$error = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $email = mb_strtolower(post('email'));

    // Batasi percobaan: 5x salah → tunggu 60 detik.
    $lockUntil = (int) ($_SESSION['lock_until'] ?? 0);
    if ($lockUntil > time()) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam ' . ($lockUntil - time()) . ' detik.';
    } else {
        $user = row('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user && $user['password_hash'] && password_verify(post('password'), $user['password_hash'])) {
            unset($_SESSION['fail'], $_SESSION['lock_until']);
            login_user($user);
            flash('Selamat datang kembali, ' . explode(' ', $user['name'])[0] . '! 👋');
            redirect(safe_next($next));
        }
        $_SESSION['fail'] = ($_SESSION['fail'] ?? 0) + 1;
        if ($_SESSION['fail'] >= 5) { $_SESSION['lock_until'] = time() + 60; $_SESSION['fail'] = 0; }
        $error = 'Email atau password salah.';
    }
}

auth_open('Masuk', 'Masuk ke portal', 'Murid dan tutor masuk dari sini.');
?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <?php google_block('Masuk dengan Google'); ?>
      <div class="divider"><span>atau dengan email</span></div>

      <form method="post" class="stack-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="username" value="<?= e($email) ?>" placeholder="nama@email.com"></div>
        <div class="field"><label for="password">Password</label>
          <div class="pw-wrap"><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Password kamu"><button type="button" class="pw-toggle" data-toggle-pw="password" aria-label="Tampilkan password">👁</button></div>
        </div>
        <button class="btn btn-primary btn-full btn-large" type="submit">Masuk</button>
      </form>

      <p class="auth-switch">Belum punya akun? <a href="<?= e(url('register.php')) ?>">Daftar sebagai murid</a></p>

      <?php if (SHOW_DEMO_ACCOUNTS): ?>
        <div class="demo-box">
          <b>Akun demo — klik untuk mengisi otomatis</b>
          <div class="chip-row">
            <button type="button" class="chip" data-demo="murid@hakuna.test|Murid123!">🧑‍🎓 Murid</button>
            <button type="button" class="chip" data-demo="tutor@hakuna.test|Tutor123!">👩‍🏫 Tutor</button>
            <button type="button" class="chip" data-demo="admin@hakuna.test|Admin123!">🛠 Admin</button>
          </div>
        </div>
      <?php endif; ?>
<?php auth_close(); ?>
