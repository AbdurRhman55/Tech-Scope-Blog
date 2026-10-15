<?php
/**
 *  Admin — Posts list (with bulk actions)
 */

require_once __DIR__ . '/auth.php';

$pdo = db();

/* --- Bulk actions ---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $ids    = array_map('intval', (array) ($_POST['ids'] ?? []));

    if ($ids && in_array($action, ['publish', 'draft', 'feature', 'unfeature', 'delete'], true)) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'publish') {
            $pdo->prepare("UPDATE posts SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id IN ($ph)")->execute($ids);
            flash_set('success', count($ids) . ' post(s) published.');
        } elseif ($action === 'draft') {
            $pdo->prepare("UPDATE posts SET status = 'draft' WHERE id IN ($ph)")->execute($ids);
            flash_set('success', count($ids) . ' post(s) moved to drafts.');
        } elseif ($action === 'feature') {
            $pdo->exec('UPDATE posts SET is_featured = 0');
            $pdo->prepare("UPDATE posts SET is_featured = 1 WHERE id IN ($ph)")->execute($ids);
            flash_set('success', 'Featured post updated.');
        } elseif ($action === 'unfeature') {
            $pdo->prepare("UPDATE posts SET is_featured = 0 WHERE id IN ($ph)")->execute($ids);
            flash_set('success', 'Feature flag cleared.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM posts WHERE id IN ($ph)")->execute($ids);
            flash_set('success', count($ids) . ' post(s) deleted.');
        }
    }
    header('Location: ' . admin_url('posts.php') . (isset($_POST['q']) ? '?q=' . urlencode((string) $_POST['q']) : ''));
    exit;
}

/* --- Filters --------------------------------------------------------- */
$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$catId  = (int) ($_GET['cat'] ?? 0);
$page   = max(1, (int) ($_GET['page'] ?? 1));
$per    = 15;

$where  = ['1=1'];
$params = [];
if ($q !== '')     { $where[] = '(p.title LIKE ? OR p.slug LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
if ($status !== ''){ $where[] = 'p.status = ?'; $params[] = $status; }
if ($catId)        { $where[] = 'p.category_id = ?'; $params[] = $catId; }
$whereSql = implode(' AND ', $where);

$st = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE $whereSql");
$st->execute($params);
$total = (int) $st->fetchColumn();
$pages  = max(1, (int) ceil($total / $per));
$page   = min($page, $pages);
$offset = ($page - 1) * $per;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, u.name AS author_name,
            eb.review_status,
            (SELECT COUNT(*) FROM comments cm WHERE cm.post_id = p.id AND cm.status = 'approved') AS comment_count
     FROM posts p
     LEFT JOIN categories c ON c.id = p.category_id
     JOIN users u ON u.id = p.author_id
     LEFT JOIN editorial_briefs eb ON eb.post_id = p.id
     WHERE {$whereSql}
     ORDER BY p.created_at DESC
     LIMIT {$per} OFFSET {$offset}"
);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$cats = get_categories();

$admin_title = 'Posts';
$admin_page  = 'posts';
require __DIR__ . '/includes/layout-top.php';
?>

<!-- Filters -->
<form method="get" action="<?= admin_url('posts.php') ?>" class="flex flex-wrap items-end gap-3">
  <div class="min-w-48 flex-1">
    <label class="kicker text-muted" for="q">Search</label>
    <input id="q" name="q" value="<?= e($q) ?>" placeholder="Title or slug…"
           class="mt-1.5 w-full rounded-lg border border-line bg-surface px-4 py-2.5 text-sm outline-none focus:border-primary">
  </div>
  <div>
    <label class="kicker text-muted" for="status">Status</label>
    <select id="status" name="status" class="mt-1.5 rounded-lg border border-line bg-surface px-3 py-2.5 text-sm outline-none focus:border-primary">
      <option value="">All</option>
      <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
      <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
    </select>
  </div>
  <div>
    <label class="kicker text-muted" for="cat">Category</label>
    <select id="cat" name="cat" class="mt-1.5 rounded-lg border border-line bg-surface px-3 py-2.5 text-sm outline-none focus:border-primary">
      <option value="0">All</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="rounded-lg bg-ink px-5 py-2.5 text-sm font-bold text-paper transition hover:bg-accent">Filter</button>
  <?php if ($q || $status || $catId): ?>
    <a href="<?= admin_url('posts.php') ?>" class="rounded-lg border border-line px-4 py-2.5 text-sm font-bold transition hover:border-ink">Reset</a>
  <?php endif; ?>
</form>

<!-- Table -->
<form method="post" action="<?= admin_url('posts.php') ?>" id="bulk-form" class="mt-6">
  <?= csrf_field() ?>
  <?php if ($q): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>

  <!-- Bulk bar -->
  <div id="bulk-bar" class="hidden items-center gap-3 rounded-t-xl border border-b-0 border-line bg-ink px-5 py-3 text-paper">
    <span class="text-xs font-bold"><span id="sel-count">0</span> selected</span>
    <div class="ml-auto flex flex-wrap gap-2">
      <button name="action" value="publish" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold hover:bg-emerald-500">Publish</button>
      <button name="action" value="draft" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold hover:bg-amber-400">To draft</button>
      <button name="action" value="feature" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold hover:bg-primary-dark">Feature</button>
      <button name="action" value="unfeature" class="rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold hover:bg-white/25">Unfeature</button>
      <button name="action" value="delete" class="rounded-lg bg-accent px-3 py-1.5 text-xs font-bold hover:bg-red-500"
              onclick="return confirm('Delete selected posts permanently?')">Delete</button>
    </div>
  </div>

  <div class="overflow-hidden rounded-xl border border-line bg-surface">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-line bg-paper text-xs uppercase tracking-wider text-muted">
          <tr>
            <th class="w-10 px-4 py-3"><input type="checkbox" id="check-all" class="accent-accent"></th>
            <th class="px-4 py-3 font-bold">Title</th>
            <th class="px-4 py-3 font-bold">Category</th>
            <th class="px-4 py-3 font-bold">Status</th>
            <th class="px-4 py-3 font-bold">Views</th>
            <th class="px-4 py-3 font-bold">Comments</th>
            <th class="px-4 py-3 font-bold">Date</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line">
          <?php if (!$posts): ?>
            <tr><td colspan="8" class="px-5 py-14 text-center text-muted">
              No posts found. <a href="<?= admin_url('post-edit.php') ?>" class="font-semibold text-primary">Create one</a>.
            </td></tr>
          <?php endif; ?>
          <?php foreach ($posts as $p): ?>
            <tr class="transition hover:bg-paper">
              <td class="px-4 py-3.5"><input type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>" class="row-check accent-accent"></td>
              <td class="max-w-sm px-4 py-3.5">
                <div class="flex items-center gap-2">
                  <a href="<?= admin_url('post-edit.php?id=' . (int) $p['id']) ?>" class="block truncate font-semibold transition hover:text-accent">
                    <?= e($p['title']) ?>
                  </a>
                  <?php if ((int) $p['is_featured'] === 1): ?>
                    <span title="Featured" class="shrink-0 rounded bg-accent px-1.5 py-0.5 text-[0.6rem] font-extrabold uppercase text-white">★</span>
                  <?php endif; ?>
                  <?php $rs = (string) ($p['review_status'] ?? ''); ?>
                  <?php if ($rs !== '' && $rs !== 'none'): $rsStyles = [
                      'brief_ready'    => 'bg-blue-500/10 text-blue-700',
                      'draft_ready'    => 'bg-sky-500/10 text-sky-700',
                      'in_review'      => 'bg-amber-500/10 text-amber-700',
                      'approved'       => 'bg-emerald-500/10 text-emerald-700',
                      'needs_revision' => 'bg-red-500/10 text-red-700',
                  ]; ?>
                    <span title="Editorial review status" class="shrink-0 rounded-full px-2 py-0.5 text-[0.6rem] font-extrabold uppercase tracking-wide <?= e($rsStyles[$rs] ?? 'bg-line/40 text-muted') ?>">
                      <?= e(str_replace('_', ' ', $rs)) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <span class="text-xs text-muted">/post/<?= e($p['slug']) ?></span>
              </td>
              <td class="px-4 py-3.5 text-xs"><?= e($p['category_name'] ?? '—') ?></td>
              <td class="px-4 py-3.5">
                <span class="rounded-full px-2.5 py-1 text-[0.66rem] font-extrabold uppercase tracking-wide
                  <?= $p['status'] === 'published' ? 'bg-emerald-500/10 text-emerald-700' : ($p['status'] === 'scheduled' ? 'bg-blue-500/10 text-blue-700' : 'bg-amber-500/10 text-amber-700') ?>">
                  <?= e($p['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-xs font-semibold"><?= number_format((int) $p['views']) ?></td>
              <td class="px-4 py-3.5 text-xs font-semibold"><?= (int) $p['comment_count'] ?></td>
              <td class="px-4 py-3.5 text-xs text-muted"><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td>
              <td class="px-4 py-3.5">
                <div class="flex justify-end gap-1.5">
                  <?php if ($p['status'] === 'published'): ?>
                  <a href="<?= url('/post/' . $p['slug']) ?>" target="_blank" title="View"
                     class="rounded-md border border-line p-1.5 transition hover:border-ink">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                  </a>
                  <?php endif; ?>
                  <a href="<?= admin_url('post-edit.php?id=' . (int) $p['id']) ?>" title="Edit"
                     class="rounded-md border border-line p-1.5 transition hover:border-primary hover:text-primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<?php
$query = fn(int $p) => admin_url('posts.php') . '?' . http_build_query(array_filter([
    'q' => $q, 'status' => $status, 'cat' => $catId ?: null, 'page' => $p,
]));
render_pagination($page, $pages, $query);
?>

<script>
(function () {
  const all = document.getElementById('check-all');
  const rows = document.querySelectorAll('.row-check');
  const bar = document.getElementById('bulk-bar');
  const cnt = document.getElementById('sel-count');
  function sync() {
    const n = document.querySelectorAll('.row-check:checked').length;
    cnt.textContent = n;
    bar.classList.toggle('hidden', n === 0);
    bar.classList.toggle('flex', n !== 0);
  }
  all?.addEventListener('change', () => { rows.forEach(r => r.checked = all.checked); sync(); });
  rows.forEach(r => r.addEventListener('change', sync));
})();
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
