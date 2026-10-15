<?php
/**
 *  Admin — Contact messages inbox
 */

require_once __DIR__ . '/auth.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $ids    = array_map('intval', (array) ($_POST['ids'] ?? []));
    if ($id) $ids[] = $id;

    // Row buttons carry their target id as the button's own value — inside the
    // shared form, hidden inputs would collide across rows (wrong row / no-op).
    foreach (['read_id' => 'read', 'unread_id' => 'unread', 'delete_id' => 'delete'] as $btn => $act) {
        if (isset($_POST[$btn]) && $_POST[$btn] !== '') {
            $action = $act;
            $ids    = [(int) $_POST[$btn]];
            break;
        }
    }
    $ids = array_unique($ids);

    if ($ids && $action === 'read') {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id IN ($ph)")->execute($ids);
        flash_set('success', count($ids) . ' message(s) marked as read.');
    } elseif ($ids && $action === 'unread') {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE messages SET is_read = 0 WHERE id IN ($ph)")->execute($ids);
        flash_set('success', 'Marked as unread.');
    } elseif ($ids && $action === 'delete') {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM messages WHERE id IN ($ph)")->execute($ids);
        flash_set('success', count($ids) . ' message(s) deleted.');
    }
    header('Location: ' . admin_url('messages.php'));
    exit;
}

$filter = ($_GET['box'] ?? 'inbox') === 'unread' ? 'WHERE is_read = 0' : '';
$page   = max(1, (int) ($_GET['page'] ?? 1));
$per    = 15;

$st = $pdo->query('SELECT COUNT(*) FROM messages ' . $filter);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);

$messages = $pdo->query(
    'SELECT * FROM messages ' . $filter . ' ORDER BY created_at DESC LIMIT ' . $per .
    ' OFFSET ' . (($page - 1) * $per)
)->fetchAll();

$unread = (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();

$admin_title = 'Messages';
$admin_page  = 'messages';
require __DIR__ . '/includes/layout-top.php';
?>

<!-- Tabs -->
<div class="flex gap-2">
  <a href="<?= admin_url('messages.php') ?>"
     class="rounded-lg px-4 py-2 text-xs font-bold transition <?= ($_GET['box'] ?? 'inbox') !== 'unread' ? 'bg-ink text-paper' : 'border border-line bg-surface hover:border-ink' ?>">
    Inbox <span class="ml-1 opacity-60"><?= $total ?></span>
  </a>
  <a href="<?= admin_url('messages.php?box=unread') ?>"
     class="rounded-lg px-4 py-2 text-xs font-bold transition <?= ($_GET['box'] ?? '') === 'unread' ? 'bg-ink text-paper' : 'border border-line bg-surface hover:border-ink' ?>">
    Unread <span class="ml-1 opacity-60"><?= $unread ?></span>
  </a>
</div>

<form method="post" action="<?= admin_url('messages.php') ?>" class="mt-5">
  <?= csrf_field() ?>

  <?php if ($messages): ?>
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <span class="text-xs font-semibold text-muted"><?= count($messages) ?> message(s)</span>
      <div class="ml-auto flex gap-2">
        <button name="action" value="read" class="rounded-lg border border-line bg-surface px-3.5 py-2 text-xs font-bold transition hover:border-ink">Mark read</button>
        <button name="action" value="unread" class="rounded-lg border border-line bg-surface px-3.5 py-2 text-xs font-bold transition hover:border-ink">Mark unread</button>
        <button name="action" value="delete" class="rounded-lg border border-line bg-surface px-3.5 py-2 text-xs font-bold transition hover:border-accent hover:text-accent"
                onclick="return confirm('Delete selected messages?')">Delete</button>
      </div>
    </div>

    <div class="space-y-3">
      <?php foreach ($messages as $m): ?>
        <div class="rounded-xl border <?= $m['is_read'] ? 'border-line bg-surface' : 'border-primary/40 bg-primary/5' ?> p-5 transition hover:border-ink/30">
          <div class="flex flex-wrap items-center gap-3">
            <input type="checkbox" name="ids[]" value="<?= (int) $m['id'] ?>" class="accent-accent">
            <?php if (!$m['is_read']): ?>
              <span class="h-2 w-2 rounded-full bg-primary" title="Unread"></span>
            <?php endif; ?>
            <span class="text-sm font-bold"><?= e($m['name']) ?></span>
            <a href="mailto:<?= e($m['email']) ?>" class="text-xs text-muted hover:text-accent"><?= e($m['email']) ?></a>
            <time class="ml-auto text-xs text-muted" datetime="<?= e($m['created_at']) ?>"><?= e(date('M j, Y g:i A', strtotime($m['created_at']))) ?></time>
          </div>

          <p class="mt-2.5 font-display text-lg font-semibold"><?= e($m['subject']) ?></p>
          <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-soft"><?= e($m['body']) ?></p>

          <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-3.5">
            <a href="mailto:<?= e($m['email']) ?>?subject=Re: <?= e($m['subject']) ?>"
               class="rounded-lg bg-ink px-4 py-2 text-xs font-bold text-paper transition hover:bg-accent">Reply by email</a>
            <?php if (!$m['is_read']): ?>
              <button name="read_id" value="<?= (int) $m['id'] ?>" class="rounded-lg border border-line px-4 py-2 text-xs font-bold transition hover:border-ink">
                Mark read
              </button>
            <?php endif; ?>
            <button name="delete_id" value="<?= (int) $m['id'] ?>" class="rounded-lg border border-line px-4 py-2 text-xs font-bold transition hover:border-accent hover:text-accent"
                    onclick="return confirm('Delete this message?')">Delete
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="rounded-xl border border-dashed border-line bg-surface py-16 text-center text-muted">
      Your inbox is empty.
    </div>
  <?php endif; ?>
</form>

<?php
render_pagination($page, $pages, fn(int $p) => admin_url('messages.php?' . http_build_query(['box' => $_GET['box'] ?? '', 'page' => $p])));
?>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
