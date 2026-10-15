<?php
/**
 *  Admin — Comments moderation
 */

require_once __DIR__ . '/auth.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $ids    = array_map('intval', (array) ($_POST['ids'] ?? []));
    $single = (int) ($_POST['id'] ?? 0);

    // Row buttons carry their target id as the button's own value — inside the
    // shared bulk form, hidden inputs would collide across rows (wrong row / no-op).
    foreach (['approve_id' => 'approve', 'pending_id' => 'pending', 'spam_id' => 'spam', 'trash_id' => 'trash', 'delete_id' => 'delete'] as $btn => $act) {
        if (isset($_POST[$btn]) && $_POST[$btn] !== '') {
            $action = $act;
            $single = (int) $_POST[$btn];
            $ids    = []; // a row button always acts on its own row only
            break;
        }
    }
    if ($single && !$ids) $ids = [$single];

    if ($ids && in_array($action, ['approve', 'spam', 'trash', 'pending'], true)) {
        $statusMap = ['approve' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'pending' => 'pending'];
        $newStatus = $statusMap[$action];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE comments SET status = ? WHERE id IN ($ph)")
            ->execute(array_merge([$newStatus], $ids));
        flash_set('success', count($ids) . ' comment(s) set to "' . $newStatus . '".');
    } elseif ($action === 'delete' && $ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM comments WHERE id IN ($ph)")->execute($ids);
        flash_set('success', count($ids) . ' comment(s) permanently deleted.');
    }
    header('Location: ' . admin_url('comments.php?status=' . urlencode((string) ($_GET['status'] ?? 'pending'))));
    exit;
}

$status = (string) ($_GET['status'] ?? 'pending');
if (!in_array($status, ['pending', 'approved', 'spam', 'trash', 'all'], true)) $status = 'pending';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per  = 20;

$where  = $status === 'all' ? '1=1' : 'c.status = ?';
$params = $status === 'all' ? [] : [$status];

$st = $pdo->prepare("SELECT COUNT(*) FROM comments c WHERE $where");
$st->execute($params);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);

$stmt = $pdo->prepare(
    "SELECT c.*, p.title AS post_title, p.slug AS post_slug
     FROM comments c
     JOIN posts p ON p.id = c.post_id
     WHERE $where
     ORDER BY c.created_at DESC
     LIMIT " . $per . " OFFSET " . (($page - 1) * $per)
);
$stmt->execute($params);
$comments = $stmt->fetchAll();

$counts = [
    'pending'  => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(),
    'approved' => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status='approved'")->fetchColumn(),
    'spam'     => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status='spam'")->fetchColumn(),
    'trash'    => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status='trash'")->fetchColumn(),
    'all'      => (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn(),
];

$admin_title = 'Comments';
$admin_page  = 'comments';
require __DIR__ . '/includes/layout-top.php';
?>

<!-- Status tabs -->
<div class="flex flex-wrap gap-2">
  <?php foreach (['pending', 'approved', 'spam', 'trash', 'all'] as $s): ?>
    <a href="<?= admin_url('comments.php?status=' . $s) ?>"
       class="rounded-lg px-4 py-2 text-xs font-bold transition
              <?= $status === $s ? 'bg-ink text-paper' : 'border border-line bg-surface hover:border-ink' ?>">
      <?= ucfirst($s) ?>
      <span class="ml-1 opacity-60"><?= $counts[$s] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<form method="post" action="<?= admin_url('comments.php?status=' . urlencode($status)) ?>" class="mt-5">
  <?= csrf_field() ?>

  <?php if ($comments): ?>
    <div id="bulk-bar" class="hidden items-center gap-3 rounded-t-xl border border-b-0 border-line bg-ink px-5 py-3 text-paper">
      <span class="text-xs font-bold"><span class="sel-count">0</span> selected</span>
      <div class="ml-auto flex gap-2">
        <button name="action" value="approve" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold hover:bg-emerald-500">Approve</button>
        <button name="action" value="spam" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold hover:bg-amber-400">Mark spam</button>
        <button name="action" value="trash" class="rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold hover:bg-white/25">Trash</button>
        <button name="action" value="delete" class="rounded-lg bg-accent px-3 py-1.5 text-xs font-bold hover:bg-red-500"
                onclick="return confirm('Permanently delete selected comments?')">Delete</button>
      </div>
    </div>

    <div class="space-y-3">
      <?php if (!$comments): ?>
        <div class="rounded-xl border border-dashed border-line bg-surface py-16 text-center text-muted">
          No <?= $status === 'all' ? '' : e($status) ?> comments. Nice work.
        </div>
      <?php endif; ?>
      <?php foreach ($comments as $c): ?>
        <div class="rounded-xl border border-line bg-surface p-5 transition hover:border-ink/30">
          <div class="flex items-start gap-4">
            <input type="checkbox" name="ids[]" value="<?= (int) $c['id'] ?>" class="row-check mt-1.5 accent-accent">

            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full
              <?= $c['status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-700' : ($c['status'] === 'spam' ? 'bg-accent/10 text-accent' : 'bg-amber-500/10 text-amber-700') ?> font-bold">
              <?= e(strtoupper(mb_substr($c['name'], 0, 1))) ?>
            </span>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="text-sm font-bold"><?= e($c['name']) ?></span>
                <a href="mailto:<?= e($c['email']) ?>" class="text-xs text-muted hover:text-accent"><?= e($c['email']) ?></a>
                <?php if ($c['website']): ?>
                  <a href="<?= e($c['website']) ?>" target="_blank" rel="noopener nofollow" class="text-xs font-semibold text-primary hover:text-accent">website ↗</a>
                <?php endif; ?>
                <time class="text-xs text-muted" datetime="<?= e($c['created_at']) ?>"><?= e(time_ago($c['created_at'])) ?></time>
                <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-extrabold uppercase tracking-wide
                  <?= $c['status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-700' : ($c['status'] === 'spam' ? 'bg-accent/10 text-accent' : ($c['status'] === 'trash' ? 'bg-gray-500/10 text-gray-600' : 'bg-amber-500/10 text-amber-700')) ?>">
                  <?= e($c['status']) ?>
                </span>
              </div>

              <p class="mt-2 rounded-lg bg-paper p-3.5 text-sm leading-relaxed text-ink-soft"><?= nl2br(e($c['content'])) ?></p>

              <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                <span class="text-muted">on
                  <a href="<?= url('/post/' . e($c['post_slug']) . '#comments') ?>" target="_blank" class="font-semibold text-primary hover:text-accent"><?= e($c['post_title']) ?></a>
                </span>
                <div class="ml-auto flex gap-1.5">
                  <?php if ($c['status'] !== 'approved'): ?>
                    <button name="approve_id" value="<?= (int) $c['id'] ?>" class="rounded-md bg-emerald-600 px-3 py-1.5 font-bold text-white transition hover:bg-emerald-500"
                            formaction="<?= admin_url('comments.php?status=' . urlencode($status)) ?>">Approve</button>
                  <?php else: ?>
                    <button name="pending_id" value="<?= (int) $c['id'] ?>" class="rounded-md border border-line px-3 py-1.5 font-bold transition hover:border-ink"
                            formaction="<?= admin_url('comments.php?status=' . urlencode($status)) ?>">Unapprove</button>
                  <?php endif; ?>
                  <?php if ($c['status'] !== 'spam'): ?>
                    <button name="spam_id" value="<?= (int) $c['id'] ?>" class="rounded-md border border-line px-3 py-1.5 font-bold transition hover:border-amber-500 hover:text-amber-600"
                            formaction="<?= admin_url('comments.php?status=' . urlencode($status)) ?>">Spam</button>
                  <?php endif; ?>
                  <button name="delete_id" value="<?= (int) $c['id'] ?>" class="rounded-md border border-line px-3 py-1.5 font-bold transition hover:border-accent hover:text-accent"
                          onclick="return confirm('Delete this comment permanently?')"
                          formaction="<?= admin_url('comments.php?status=' . urlencode($status)) ?>">Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="mt-5 rounded-xl border border-dashed border-line bg-surface py-16 text-center text-muted">
      No <?= $status === 'all' ? '' : e($status) ?> comments. Nice work.
    </div>
  <?php endif; ?>
</form>

<?php
render_pagination($page, $pages, fn(int $p) => admin_url('comments.php?' . http_build_query(['status' => $status, 'page' => $p])));
?>

<script>
(function () {
  const all = document.getElementById('check-all');
  const rows = document.querySelectorAll('.row-check');
  const bar = document.getElementById('bulk-bar');
  const counts = document.querySelectorAll('.sel-count');
  function sync() {
    const n = document.querySelectorAll('.row-check:checked').length;
    counts.forEach(c => c.textContent = n);
    bar.classList.toggle('hidden', n === 0);
    bar.classList.toggle('flex', n !== 0);
  }
  rows.forEach(r => r.addEventListener('change', sync));
})();
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
