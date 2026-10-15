<?php
/**
 *  Admin — Categories (CRUD)
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_role(['admin', 'editor']);
$pdo        = db();

/* ---------------------------------------------------------------------
 *  AJAX endpoint — quick-create a category from the post editor
 *  POST categories.php?ajax=1  (csrf_token, name, parent_id)
 *  → JSON { ok:true, id, name, slug } | { ok:false, error }
 * --------------------------------------------------------------------- */
if (($_GET['ajax'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $out = ['ok' => false, 'error' => 'Invalid request.'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            $out['error'] = 'Security token expired — reload the page and try again.';
        } else {
            $name   = trim((string) ($_POST['name'] ?? ''));
            $parent = (int) ($_POST['parent_id'] ?? 0);

            if ($name === '') {
                $out['error'] = 'Category name is required.';
            } elseif (mb_strlen($name) > 80) {
                $out['error'] = 'Name must be 80 characters or fewer.';
            } else {
                if ($parent > 0) {
                    $chk = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
                    $chk->execute([$parent]);
                    if (!$chk->fetchColumn()) {
                        $parent = 0;
                    }
                }
                $dup = $pdo->prepare('SELECT id FROM categories WHERE name = ? AND COALESCE(parent_id, 0) = ? LIMIT 1');
                $dup->execute([$name, $parent]);
                if ($dup->fetchColumn()) {
                    $out['error'] = '“' . $name . '” already exists at this level.';
                } else {
                    $slug = unique_slug('categories', slugify($name), null);
                    $next = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories')->fetchColumn();
                    $pdo->prepare('INSERT INTO categories (name, slug, parent_id, sort_order) VALUES (?,?,?,?)')
                        ->execute([$name, $slug, $parent ?: null, $next]);
                    $out = [
                        'ok'     => true,
                        'id'     => (int) $pdo->lastInsertId(),
                        'name'   => $name,
                        'slug'   => $slug,
                        'parent' => $parent,
                    ];
                }
            }
        }
    }
    echo json_encode($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id     = (int) ($_POST['id'] ?? 0);
        $name   = trim((string) ($_POST['name'] ?? ''));
        $desc   = trim((string) ($_POST['description'] ?? ''));
        $metaT  = trim((string) ($_POST['meta_title'] ?? ''));
        $metaD  = trim((string) ($_POST['meta_desc'] ?? ''));
        $sort   = (int) ($_POST['sort_order'] ?? 0);
        $parent = (int) ($_POST['parent_id'] ?? 0);
        $featured = !empty($_POST['is_featured']) ? 1 : 0;

        // Parent must exist (0 = top level)
        if ($parent > 0) {
            $chk = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
            $chk->execute([$parent]);
            if (!$chk->fetchColumn()) {
                $parent = 0;
            }
        }
        // Cycle guard: a category can never sit under itself or its own subcategory
        $isCycle = false;
        if ($parent > 0) {
            if ($parent === $id) {
                $isCycle = true;
            } elseif ($id && in_array($parent, category_descendant_ids($id), true)) {
                // $parent is (transitively) under $id -> assigning it as $id's parent closes a loop
                $isCycle = true;
            }
        }

        if ($name === '') {
            flash_set('error', 'Category name is required.');
        } elseif ($isCycle) {
            flash_set('error', 'Invalid parent — a category cannot be placed under itself or one of its own subcategories.');
        } else {
            $slug = unique_slug('categories', slugify($name), $id ?: null);
            if ($id) {
                $pdo->prepare('UPDATE categories SET name=?, slug=?, description=?, parent_id=?, meta_title=?, meta_desc=?, sort_order=?, is_featured=? WHERE id=?')
                    ->execute([$name, $slug, $desc ?: null, $parent ?: null, $metaT ?: null, $metaD ?: null, $sort, $featured, $id]);
                flash_set('success', 'Category updated.');
            } else {
                $pdo->prepare('INSERT INTO categories (name, slug, description, parent_id, meta_title, meta_desc, sort_order, is_featured) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$name, $slug, $desc ?: null, $parent ?: null, $metaT ?: null, $metaD ?: null, $sort, $featured]);
                flash_set('success', $parent ? 'Subcategory created.' : 'Category created.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE posts SET category_id = NULL WHERE category_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash_set('success', 'Category deleted (posts kept, unassigned).');
    } elseif ($action === 'edit') {
        // just falls through to form prefill below
    }

    header('Location: ' . admin_url('categories.php') . (($_POST['action'] ?? '') === 'edit' ? '?edit=' . (int) ($_POST['id'] ?? 0) : ''));
    exit;
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$nested   = get_categories_nested();   // tree order + depth
$hasKids  = [];
foreach ($nested as $c) {
    if ((int) ($c['parent_id'] ?? 0) > 0) {
        $hasKids[(int) $c['parent_id']] = true;
    }
}
// Valid parents for the edit form: everything except self + own descendants
$blockedParents = !empty($edit) ? category_descendant_ids((int) $edit['id']) : [];

$admin_title = 'Categories';
$admin_page  = 'categories';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="grid gap-6 lg:grid-cols-[1fr_380px]">

  <!-- List -->
  <div class="overflow-hidden rounded-xl border border-line bg-surface">
    <div class="border-b border-line px-5 py-4">
      <h2 class="font-display text-lg font-semibold">All categories (<?= count($nested) ?>)</h2>
    </div>
    <table class="w-full text-left text-sm">
      <thead class="border-b border-line bg-paper text-xs uppercase tracking-wider text-muted">
        <tr>
          <th class="px-5 py-3 font-bold">Name</th>
          <th class="px-5 py-3 font-bold">Slug</th>
          <th class="px-5 py-3 font-bold">Posts</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-line">
        <?php if (!$nested): ?>
          <tr><td colspan="4" class="px-5 py-12 text-center text-muted">No categories yet — create one.</td></tr>
        <?php endif; ?>
        <?php foreach ($nested as $c): ?>
          <tr class="transition hover:bg-paper">
            <td class="px-5 py-3.5">
              <p class="flex items-center gap-2 font-semibold" style="margin-left: <?= (int) $c['depth'] * 22 ?>px">
                <?php if ((int) $c['depth'] > 0): ?>
                  <svg class="h-3.5 w-3.5 shrink-0 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <?php endif; ?>
                <?= e($c['name']) ?>
                <?php if (!empty($hasKids[(int) $c['id']])): ?>
                  <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[0.6rem] font-extrabold uppercase tracking-wide text-primary">parent</span>
                <?php elseif ((int) $c['depth'] > 0): ?>
                  <span class="rounded-full bg-line/50 px-2 py-0.5 text-[0.6rem] font-extrabold uppercase tracking-wide text-muted">sub</span>
                <?php endif; ?>
              </p>
              <?php if ($c['description']): ?>
                <p class="mt-0.5 max-w-md truncate text-xs text-muted" style="margin-left: <?= (int) $c['depth'] * 22 ?>px"><?= e($c['description']) ?></p>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3.5 text-xs text-muted"><?= e($c['slug']) ?></td>
            <td class="px-5 py-3.5">
              <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary"><?= (int) $c['post_count'] ?></span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex justify-end gap-1.5">
                <a href="<?= url('/category/' . $c['slug']) ?>" target="_blank" title="View" class="rounded-md border border-line p-1.5 transition hover:border-ink">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                </a>
                <a href="?edit=<?= (int) $c['id'] ?>" title="Edit" class="rounded-md border border-line p-1.5 transition hover:border-primary hover:text-primary">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                </a>
                <form method="post" onsubmit="return confirm('Delete this category? Posts will remain but become uncategorized.')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
    <h2 class="font-display text-lg font-semibold"><?= $edit ? 'Edit category' : 'New category' ?></h2>

    <form method="post" action="<?= admin_url('categories.php') ?>" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>

      <div>
        <label class="kicker text-muted" for="c-name">Name *</label>
        <input id="c-name" name="name" required maxlength="80" value="<?= e($edit['name'] ?? '') ?>"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="c-parent">Parent category <span class="normal-case tracking-normal opacity-60">(subcategories show in the navbar dropdown)</span></label>
        <select id="c-parent" name="parent_id"
                class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
          <option value="0">— Top level (no parent) —</option>
          <?php foreach ($nested as $c): ?>
            <?php if (in_array((int) $c['id'], $blockedParents, true)) continue; ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) ($edit['parent_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
              <?= str_repeat('— ', (int) $c['depth']) . e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1.5 text-[0.7rem] leading-relaxed text-muted">Subcategory pages live at their own URL; parent pages automatically show all posts from their subcategories.</p>
      </div>
      <div>
        <label class="kicker text-muted" for="c-featured">Featured <span class="normal-case tracking-normal opacity-60">(shows in navbar prominently)</span></label>
        <input type="checkbox" id="c-featured" name="is_featured" value="1" <?= ((bool) ($edit['is_featured'] ?? false)) ? 'checked' : '' ?> class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="c-desc">Description</label>
        <textarea id="c-desc" name="description" rows="2" maxlength="255"
                  class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($edit['description'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="kicker text-muted" for="c-mt">Meta title</label>
        <input id="c-mt" name="meta_title" maxlength="190" value="<?= e($edit['meta_title'] ?? '') ?>"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
      </div>
      <div>
        <label class="kicker text-muted" for="c-md">Meta description</label>
        <textarea id="c-md" name="meta_desc" rows="2" maxlength="300"
                  class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($edit['meta_desc'] ?? '') ?></textarea>
      </div>
      <div class="w-32">
        <label class="kicker text-muted" for="c-sort">Sort order</label>
        <input id="c-sort" type="number" name="sort_order" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
      </div>

      <div class="flex gap-2.5 border-t border-line pt-4">
        <button class="flex-1 rounded-lg bg-ink py-2.5 text-xs font-bold text-paper transition hover:bg-accent">
          <?= $edit ? 'Update' : 'Create' ?>
        </button>
        <?php if ($edit): ?>
          <a href="<?= admin_url('categories.php') ?>" class="rounded-lg border border-line px-4 py-2.5 text-xs font-bold transition hover:border-ink">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
