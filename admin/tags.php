<?php
/**
 *  Admin — Tags (list, create, delete, merge not needed)
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_role(['admin', 'editor']);
$pdo        = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash_set('error', 'Tag name required.');
        } else {
            $slug = slugify($name);
            $chk = $pdo->prepare('SELECT id FROM tags WHERE slug = ?');
            $chk->execute([$slug]);
            if ($chk->fetch()) {
                flash_set('error', 'Tag already exists.');
            } else {
                $pdo->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)')->execute([mb_substr($name, 0, 60), $slug]);
                flash_set('success', 'Tag created.');
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM tags WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Tag deleted.');
    }
    header('Location: ' . admin_url('tags.php'));
    exit;
}

$tags = $pdo->query(
    "SELECT t.*, COUNT(pt.post_id) AS tag_count
     FROM tags t
     LEFT JOIN post_tags pt ON pt.tag_id = t.id
     GROUP BY t.id
     ORDER BY tag_count DESC, t.name ASC"
)->fetchAll();

$admin_title = 'Tags';
$admin_page  = 'tags';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="grid gap-6 lg:grid-cols-[1fr_340px]">

  <div class="overflow-hidden rounded-xl border border-line bg-surface">
    <div class="border-b border-line px-5 py-4">
      <h2 class="font-display text-lg font-semibold">All tags (<?= count($tags) ?>)</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-line bg-paper text-xs uppercase tracking-wider text-muted">
          <tr>
            <th class="px-5 py-3 font-bold">Tag</th>
            <th class="px-5 py-3 font-bold">Slug</th>
            <th class="px-5 py-3 font-bold">Posts</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line">
          <?php if (!$tags): ?>
            <tr><td colspan="4" class="px-5 py-12 text-center text-muted">No tags yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($tags as $t): ?>
            <tr class="transition hover:bg-paper">
              <td class="px-5 py-3 font-semibold">#<?= e($t['name']) ?></td>
              <td class="px-5 py-3 text-xs text-muted"><?= e($t['slug']) ?></td>
              <td class="px-5 py-3">
                <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary"><?= (int) $t['tag_count'] ?></span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex justify-end gap-1.5">
                  <a href="<?= url('/tag/' . $t['slug']) ?>" target="_blank" class="rounded-md border border-line p-1.5 transition hover:border-ink" title="View">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                  </a>
                  <form method="post" onsubmit="return confirm('Delete this tag?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                    <button name="action" value="delete" class="rounded-md border border-line p-1.5 transition hover:border-accent hover:text-accent" title="Delete">
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
  </div>

  <div class="h-fit rounded-xl border border-line bg-surface p-5">
    <h2 class="font-display text-lg font-semibold">New tag</h2>
    <form method="post" action="<?= admin_url('tags.php') ?>" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div>
        <label class="kicker text-muted" for="t-name">Name *</label>
        <input id="t-name" name="name" required maxlength="60" placeholder="machine-learning"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <button class="w-full rounded-lg bg-ink py-2.5 text-xs font-bold text-paper transition hover:bg-accent">Create tag</button>
    </form>
    <p class="mt-4 border-t border-line pt-4 text-xs leading-relaxed text-muted">
      Tags are usually created while writing a post — just type them (comma separated) in the post editor.
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
