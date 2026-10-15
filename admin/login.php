<?php
/**
 * ---------------------------------------------------------------------
 *  Admin login
 * ---------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';

// Already logged in → dashboard
if (admin_user()) {
    header('Location: ' . admin_url('index.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $wait = login_throttle();
    if ($wait > 0) {
        $error = 'Too many failed attempts. Try again in ' . ceil($wait / 60) . ' minute(s).';
    } else {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = db()->prepare('SELECT id, password, role FROM users WHERE email = ? AND status = 1 LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password'])) {
            login_clear_failures();
            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $row['id'];

            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([(int) $row['id']]);

            // Re-hash if needed
            if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
            }

            header('Location: ' . admin_url('index.php'));
            exit;
        }

        login_record_failure();
        $error = 'Invalid email or password.';
        // Constant-ish delay to slow brute force
        usleep(random_int(300000, 700000));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login | <?= e(setting('site_name', SITE_NAME)) ?></title>
<link rel="icon" href="<?= e(function_exists('favicon_url') ? favicon_url() : url('/assets/img/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>?v=<?= e(function_exists('asset_version') ? asset_version() : (string) @filemtime(ROOT_PATH . '/assets/css/app.css')) ?>">
<style>
  body { background:
    radial-gradient(1000px 600px at 15% -10%, rgba(31,79,216,.14), transparent 60%),
    radial-gradient(900px 500px at 110% 110%, rgba(232,64,42,.12), transparent 55%),
    var(--color-paper); }
</style>
</head>
<body class="flex min-h-screen items-center justify-center px-5 font-sans antialiased">

<div class="w-full max-w-md reveal reveal-1">

  <!-- Brand -->
  <?php $_lLogo = function_exists('site_logo_src') ? site_logo_src() : null; ?>
  <?php if ($_lLogo): ?>
  <a href="<?= url('/') ?>" class="mb-8 flex items-center justify-center" aria-label="<?= e(setting('site_name', SITE_NAME)) ?> — home">
    <img src="<?= e($_lLogo) ?>" alt="<?= e(setting('site_name', SITE_NAME)) ?>" class="h-16 w-auto" width="1033" height="643">
  </a>
  <?php else: ?>
  <a href="<?= url('/') ?>" class="mb-8 flex items-center justify-center gap-2.5">
    <span class="font-display text-3xl font-bold tracking-tight text-ink"><?= e(setting('site_name', SITE_NAME)) ?></span>
    <span class="h-2.5 w-2.5 rounded-full bg-accent"></span>
  </a>
  <?php endif; ?>

  <div class="rounded-2xl border border-line bg-surface p-8 shadow-[0_30px_60px_-40px_rgba(18,20,26,.45)]">
    <p class="kicker text-accent">Restricted area</p>
    <h1 class="mt-2 font-display text-2xl font-semibold">Admin sign in</h1>

    <?php if ($error): ?>
      <div class="mt-5 rounded-lg border border-accent bg-accent-soft px-4 py-3 text-sm font-semibold text-accent">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= admin_url('login.php') ?>" class="mt-6 space-y-5">
      <?= csrf_field() ?>

      <div>
        <label for="email" class="kicker text-muted">Email</label>
        <input id="email" type="email" name="email" required autofocus autocomplete="username"
               value="<?= e($_POST['email'] ?? '') ?>"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none transition focus:border-primary">
      </div>

      <div>
        <label for="password" class="kicker text-muted">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none transition focus:border-primary">
      </div>

      <button class="w-full rounded-lg bg-ink py-3.5 text-sm font-bold text-paper transition hover:bg-accent">
        Sign in
      </button>
    </form>
  </div>

  <p class="mt-6 text-center text-xs text-muted">
    <a href="<?= url('/') ?>" class="transition hover:text-accent">&larr; Back to website</a>
  </p>
</div>

</body>
</html>
