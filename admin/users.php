<?php
/**
 *  Admin — Authors / Users (admin only)
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_role(['admin']);
$pdo        = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $name     = trim((string) ($_POST['name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $role     = in_array($_POST['role'] ?? '', ['admin', 'editor', 'author'], true) ? $_POST['role'] : 'author';
        $password = (string) ($_POST['password'] ?? '');
        $bio      = trim((string) ($_POST['bio'] ?? ''));
        $status   = !empty($_POST['status']) ? 1 : 0;
        $website  = trim((string) ($_POST['website'] ?? ''));
        $twitter  = trim((string) ($_POST['twitter'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100)  $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

        // Unique email
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?' . ($id ? ' AND id != ?' : ''));
        $chk->execute($id ? [$email, $id] : [$email]);
        if ($chk->fetch()) $errors[] = 'Email already in use.';

        if (!$id && strlen($password) < 8) $errors[] = 'Password min 8 characters.';
        if ($id && $password !== '' && strlen($password) < 8) $errors[] = 'Password min 8 characters.';

        if (!$errors) {
            $slug = unique_slug('users', slugify($name), $id ?: null);
            if ($id) {
                $pdo->prepare('UPDATE users SET name=?, slug=?, email=?, role=?, bio=?, status=?, website=?, twitter=? WHERE id=?')
                    ->execute([$name, $slug, $email, $role, $bio ?: null, $status, $website ?: null, $twitter ?: null, $id]);
                if ($password !== '') {
                    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
                }
                flash_set('success', 'User updated.');
            } else {
                $pdo->prepare('INSERT INTO users (name, slug, email, password, role, bio, status, website, twitter) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$name, $slug, $email, password_hash($password, PASSWORD_DEFAULT), $role, $bio ?: null, $status, $website ?: null, $twitter ?: null]);
                flash_set('success', 'User created.');
            }
        } else {
            flash_set('error', implode(' ', $errors));
            header('Location: ' . admin_url('users.php') . ($id ? '?edit=' . $id : ''));
            exit;
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id !== (int) $admin_user['id']) {
            $pdo->prepare('UPDATE users SET status = 1 - status WHERE id = ?')->execute([$id]);
            flash_set('success', 'User status toggled.');
        } else {
            flash_set('error', 'You cannot disable your own account.');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $admin_user['id']) {
            flash_set('error', 'You cannot delete your own account.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash_set('success', 'User deleted (their posts were removed with them).');
        }
    }
    header('Location: ' . admin_url('users.php'));
    exit;
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$users = $pdo->query(
    'SELECT u.*, (SELECT COUNT(*) FROM posts WHERE author_id = u.id) AS post_count
     FROM users u ORDER BY FIELD(u.role,"admin","editor","author"), u.name'
)->fetchAll();

$admin_title = 'Authors';
$admin_page  = 'users';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="grid gap-6 lg:grid-cols-[1fr_380px]">

  <div class="overflow-hidden rounded-xl border border-line bg-surface">
    <div class="border-b border-line px-5 py-4">
      <h2 class="font-display text-lg font-semibold">Team (<?= count($users) ?>)</h2>
    </div>
    <table class="w-full text-left text-sm">
      <thead class="border-b border-line bg-paper text-xs uppercase tracking-wider text-muted">
        <tr>
          <th class="px-5 py-3 font-bold">Name</th>
          <th class="px-5 py-3 font-bold">Role</th>
          <th class="px-5 py-3 font-bold">Posts</th>
          <th class="px-5 py-3 font-bold">Status</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-line">
        <?php foreach ($users as $u): ?>
          <tr class="transition hover:bg-paper">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <?php $_uAv = !empty($u['avatar']) && function_exists('_avatar_url') ? _avatar_url($u['avatar']) : null; ?>
                <?php if ($_uAv): ?>
                <img src="<?= e($_uAv) ?>" alt="<?= e($u['name']) ?>" class="h-9 w-9 shrink-0 rounded-full object-cover">
                <?php else: ?>
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-ink text-sm font-bold text-paper">
                  <?= e(strtoupper(mb_substr($u['name'], 0, 1))) ?>
                </span>
                <?php endif; ?>
                <div>
                  <p class="font-semibold"><?= e($u['name']) ?> <?= (int) $u['id'] === (int) $admin_user['id'] ? '<span class="text-xs text-accent">(you)</span>' : '' ?></p>
                  <p class="text-xs text-muted"><?= e($u['email']) ?></p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <span class="rounded-full px-2.5 py-1 text-[0.66rem] font-extrabold uppercase tracking-wide
                <?= $u['role'] === 'admin' ? 'bg-accent/10 text-accent' : ($u['role'] === 'editor' ? 'bg-primary/10 text-primary' : 'bg-gray-500/10 text-gray-600') ?>">
                <?= e($u['role']) ?>
              </span>
            </td>
            <td class="px-5 py-3.5 font-semibold"><?= (int) $u['post_count'] ?></td>
            <td class="px-5 py-3.5">
              <span class="text-xs font-bold <?= $u['status'] ? 'text-emerald-600' : 'text-muted' ?>">
                <?= $u['status'] ? 'Active' : 'Disabled' ?>
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex justify-end gap-1.5">
                <a href="<?= url('/author/' . $u['slug']) ?>" target="_blank" title="View profile" class="rounded-md border border-line p-1.5 transition hover:border-ink">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                </a>
                <a href="?edit=<?= (int) $u['id'] ?>" title="Edit" class="rounded-md border border-line p-1.5 transition hover:border-primary hover:text-primary">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                </a>
                <form method="post" onsubmit="return confirm('Toggle this user&rsquo;s status?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button name="action" value="toggle" title="Enable/Disable" class="rounded-md border border-line p-1.5 transition hover:border-amber-500 hover:text-amber-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                  </button>
                </form>
                <form method="post" onsubmit="return confirm('Delete this user and ALL their posts? This cannot be undone.')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button name="action" value="delete" title="Delete" class="rounded-md border border-line p-1.5 transition hover:border-accent hover:text-accent">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Form -->
  <div class="h-fit rounded-xl border border-line bg-surface p-5">
    <h2 class="font-display text-lg font-semibold"><?= $edit ? 'Edit user' : 'New user' ?></h2>
    <form method="post" action="<?= admin_url('users.php') ?>" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>

      <div>
        <label class="kicker text-muted" for="u-name">Name *</label>
        <input id="u-name" name="name" required maxlength="100" value="<?= e($edit['name'] ?? '') ?>"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="u-email">Email *</label>
        <input id="u-email" type="email" name="email" required value="<?= e($edit['email'] ?? '') ?>"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="kicker text-muted" for="u-role">Role</label>
          <select id="u-role" name="role" class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
            <?php foreach (['admin', 'editor', 'author'] as $r): ?>
              <option value="<?= $r ?>" <?= ($edit['role'] ?? 'author') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex items-end pb-2.5">
          <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold">
            <input type="checkbox" name="status" value="1" <?= ($edit['status'] ?? 1) ? 'checked' : '' ?> class="accent-accent">
            Active
          </label>
        </div>
      </div>
      <div>
        <label class="kicker text-muted" for="u-pass">Password <?= $edit ? '<span class="normal-case tracking-normal opacity-60">(leave blank to keep)</span>' : '*' ?></label>
        <input id="u-pass" type="password" name="password" <?= $edit ? '' : 'required' ?> minlength="8" autocomplete="new-password"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="u-bio">Bio</label>
        <textarea id="u-bio" name="bio" rows="3" maxlength="500"
                  class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($edit['bio'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="kicker text-muted" for="u-web">Website</label>
        <input id="u-web" name="website" value="<?= e($edit['website'] ?? '') ?>" placeholder="https://…"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="u-tw">Twitter/X handle</label>
        <input id="u-tw" name="twitter" value="<?= e($edit['twitter'] ?? '') ?>" placeholder="username"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>

      <div class="flex gap-2.5 border-t border-line pt-4">
        <button class="flex-1 rounded-lg bg-ink py-2.5 text-xs font-bold text-paper transition hover:bg-accent"><?= $edit ? 'Update' : 'Create' ?></button>
        <?php if ($edit): ?><a href="<?= admin_url('users.php') ?>" class="rounded-lg border border-line px-4 py-2.5 text-xs font-bold transition hover:border-ink">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
