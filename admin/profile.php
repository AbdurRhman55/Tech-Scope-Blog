<?php
/**
 *  Admin — My profile (own account)
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_admin();
$pdo        = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name     = trim((string) ($_POST['name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $bio      = trim((string) ($_POST['bio'] ?? ''));
        $website  = trim((string) ($_POST['website'] ?? ''));
        $twitter  = trim((string) ($_POST['twitter'] ?? ''));
        $linkedin = trim((string) ($_POST['linkedin'] ?? ''));
        $github   = trim((string) ($_POST['github'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $chk->execute([$email, (int) $admin_user['id']]);
        if ($chk->fetch()) $errors[] = 'Email already in use.';

        // Avatar
        $avatar = $admin_user['avatar'];
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $f = $_FILES['avatar'];
            if ($f['size'] <= 2 * 1024 * 1024) {
                $mime = mime_content_type($f['tmp_name']);
                $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (isset($map[$mime])) {
                    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
                    $name2 = 'avatar-' . (int) $admin_user['id'] . '-' . substr(md5((string) microtime(true)), 0, 5) . '.' . $map[$mime];
                    if (move_uploaded_file($f['tmp_name'], UPLOADS_DIR . '/' . $name2)) {
                        $avatar = 'assets/uploads/' . $name2;
                    }
                } else {
                    $errors[] = 'Avatar must be JPG, PNG or WebP.';
                }
            } else {
                $errors[] = 'Avatar max 2 MB.';
            }
        }

        if (!$errors) {
            $slug = unique_slug('users', slugify($name), (int) $admin_user['id']);
            $pdo->prepare('UPDATE users SET name=?, slug=?, email=?, bio=?, website=?, twitter=?, linkedin=?, github=?, avatar=? WHERE id=?')
                ->execute([$name, $slug, $email, $bio ?: null, $website ?: null, $twitter ?: null,
                           $linkedin ?: null, $github ?: null, $avatar, (int) $admin_user['id']]);
            flash_set('success', 'Profile updated.');
            header('Location: ' . admin_url('profile.php'));
            exit;
        }
        flash_set('error', implode(' ', $errors));
        header('Location: ' . admin_url('profile.php'));
        exit;
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($current, $admin_user['password'])) {
            flash_set('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 10) {
            flash_set('error', 'New password must be at least 10 characters.');
        } elseif ($new !== $confirm) {
            flash_set('error', 'New passwords do not match.');
        } else {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $admin_user['id']]);
            session_regenerate_id(true);
            flash_set('success', 'Password changed.');
        }
        header('Location: ' . admin_url('profile.php'));
        exit;
    }
}

$admin_title = 'My Profile';
$admin_page  = 'profile';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="grid max-w-5xl gap-6 lg:grid-cols-2">

  <!-- Profile -->
  <div class="rounded-xl border border-line bg-surface p-6">
    <div class="flex items-center gap-4">
      <?php if (!empty($admin_user['avatar'])): ?>
        <img src="<?= url('/' . e($admin_user['avatar'])) ?>" alt="" class="h-16 w-16 rounded-full object-cover ring-2 ring-line">
      <?php else: ?>
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-ink font-display text-2xl font-bold text-paper">
          <?= e(strtoupper(mb_substr($admin_user['name'], 0, 1))) ?>
        </span>
      <?php endif; ?>
      <div>
        <h2 class="font-display text-xl font-semibold"><?= e($admin_user['name']) ?></h2>
        <p class="text-sm capitalize text-muted"><?= e($admin_user['role']) ?> · <?= e($admin_user['email']) ?></p>
        <p class="mt-0.5 text-xs text-muted">Last login: <?= $admin_user['last_login'] ? e(date('M j, Y g:i A', strtotime($admin_user['last_login']))) : '—' ?></p>
      </div>
    </div>

    <form method="post" action="<?= admin_url('profile.php') ?>" enctype="multipart/form-data" class="mt-6 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="profile">

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="kicker text-muted" for="p-name">Name *</label>
          <input id="p-name" name="name" required value="<?= e($admin_user['name']) ?>"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
        </div>
        <div>
          <label class="kicker text-muted" for="p-email">Email *</label>
          <input id="p-email" type="email" name="email" required value="<?= e($admin_user['email']) ?>"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
        </div>
      </div>

      <div>
        <label class="kicker text-muted" for="p-bio">Bio</label>
        <textarea id="p-bio" name="bio" rows="3" maxlength="500"
                  class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($admin_user['bio'] ?? '') ?></textarea>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="kicker text-muted" for="p-web">Website</label>
          <input id="p-web" name="website" value="<?= e($admin_user['website'] ?? '') ?>" placeholder="https://…"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
        </div>
        <div>
          <label class="kicker text-muted" for="p-tw">Twitter/X</label>
          <input id="p-tw" name="twitter" value="<?= e($admin_user['twitter'] ?? '') ?>" placeholder="username"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
        </div>
        <div>
          <label class="kicker text-muted" for="p-li">LinkedIn</label>
          <input id="p-li" name="linkedin" value="<?= e($admin_user['linkedin'] ?? '') ?>" placeholder="username"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
        </div>
        <div>
          <label class="kicker text-muted" for="p-gh">GitHub</label>
          <input id="p-gh" name="github" value="<?= e($admin_user['github'] ?? '') ?>" placeholder="username"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
        </div>
      </div>

      <div>
        <label class="kicker text-muted" for="p-avatar">Profile photo (max 2 MB)</label>
        <input id="p-avatar" type="file" name="avatar" accept="image/*"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2 text-xs file:mr-3 file:rounded file:border-0 file:bg-ink file:px-3 file:py-1 file:text-xs file:font-bold file:text-paper">
      </div>

      <button class="rounded-lg bg-ink px-7 py-3 text-sm font-bold text-paper transition hover:bg-accent">Save profile</button>
    </form>
  </div>

  <!-- Password -->
  <div class="h-fit rounded-xl border border-line bg-surface p-6">
    <h2 class="font-display text-lg font-semibold">Change password</h2>
    <p class="mt-1 text-sm text-muted">Minimum 10 characters. You will stay logged in after changing.</p>

    <form method="post" action="<?= admin_url('profile.php') ?>" class="mt-5 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">

      <div>
        <label class="kicker text-muted" for="cur-pass">Current password *</label>
        <input id="cur-pass" type="password" name="current_password" required autocomplete="current-password"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="new-pass">New password *</label>
        <input id="new-pass" type="password" name="new_password" required minlength="10" autocomplete="new-password"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="conf-pass">Confirm new password *</label>
        <input id="conf-pass" type="password" name="confirm_password" required minlength="10" autocomplete="new-password"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>

      <button class="rounded-lg bg-accent px-7 py-3 text-sm font-bold text-white transition hover:bg-red-600">Change password</button>
    </form>

    <div class="mt-6 rounded-lg border border-line bg-paper p-4 text-xs leading-relaxed text-muted">
      <strong class="text-ink">Security tips:</strong> use a unique password, enable 2FA on your email,
      and never share admin credentials. Failed logins are rate-limited automatically.
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
