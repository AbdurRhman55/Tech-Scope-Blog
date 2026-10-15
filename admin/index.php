<?php
/**
 *  Admin — Dashboard
 */

require_once __DIR__ . '/auth.php';

$admin_title = 'Dashboard';
$admin_page  = 'dashboard';
require __DIR__ . '/includes/layout-top.php';

$pdo = db();

/* --- Stats ---------------------------------------------------------- */
$stats = [
    'posts'     => (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'drafts'    => (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
    'comments'  => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'messages'  => (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn(),
    'views'     => (int) $pdo->query('SELECT COUNT(*) FROM page_views')->fetchColumn(), // real tracked reads
    'categories'=> (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'users'     => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE status = 1')->fetchColumn(),
];

/* --- Analytics (accurate, rolled-up traffic data — see functions.php) -- */
$charts = analytics_dashboard_data(30);

/* Delta badge: "↑ 12.5% vs previous 30 days" (null → first period) */
$deltaHtml = static function (?float $d): string {
    if ($d === null) {
        return '<span class="text-xs font-bold text-muted">first period</span>';
    }
    $up    = $d >= 0;
    $color = $up ? 'text-emerald-600' : 'text-rose-600';
    $arrow = $up ? '&uarr;' : '&darr;';
    return '<span class="text-xs font-bold ' . $color . '">' . $arrow . ' ' . abs($d) . '%</span>'
         . ' <span class="text-xs text-muted">vs previous 30 days</span>';
};

/* --- Recent posts --------------------------------------------------- */
// "Views" = real tracked reads from the analytics log (never the seeded
// counter) — the site is going live, so every number must be genuine.
$recentPosts = $pdo->query(
    "SELECT p.id, p.title, p.slug, p.status, p.published_at, p.created_at, u.name AS author,
            (SELECT COUNT(*) FROM page_views pv WHERE pv.post_id = p.id) AS real_views
     FROM posts p JOIN users u ON u.id = p.author_id
     ORDER BY p.created_at DESC LIMIT 6"
)->fetchAll();

/* --- Pending comments ----------------------------------------------- */
$pendingComments = $pdo->query(
    "SELECT c.id, c.name, c.content, c.created_at, p.title, p.slug
     FROM comments c JOIN posts p ON p.id = c.post_id
     WHERE c.status = 'pending'
     ORDER BY c.created_at DESC LIMIT 5"
)->fetchAll();

/* --- 7-day traffic --------------------------------------------------- */
$traffic = $pdo->query(
    "SELECT DATE(published_at) AS day, COUNT(*) AS cnt FROM posts
     WHERE published_at >= CURDATE() - INTERVAL 7 DAY
     GROUP BY day ORDER BY day"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$recentMessages = (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE created_at >= CURDATE() - INTERVAL 7 DAY')->fetchColumn();
?>

<!-- ===== Welcome banner ===== -->
<div class="reveal reveal-1 relative overflow-hidden rounded-xl border border-line bg-ink px-4 py-4 text-paper grain sm:px-6 sm:py-6">
  <div class="relative z-10 flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-center">
    <div>
      <p class="kicker text-accent"><?= date('l') ?> · <?= date('M j, Y') ?></p>
      <h2 class="mt-2 font-display text-2xl font-semibold sm:text-3xl">
        Welcome back, <?= e($_SESSION['user']['name'] ?? 'Admin') ?>.
      </h2>
      <p class="mt-1.5 text-sm text-paper/60">
        <?php
        $todo = $stats['comments'] + $stats['messages'];
        echo $todo > 0
            ? "You have <strong class=\"text-paper\">{$todo} item" . ($todo !== 1 ? 's' : '') . "</strong> waiting for review."
            : 'Everything is up to date — nice work.';
        ?>
      </p>
    </div>
    <div class="flex flex-wrap gap-2.5">
      <a href="<?= admin_url('post-edit.php') ?>" class="rounded-lg bg-accent px-4 py-2.5 text-xs font-bold text-white transition hover:bg-white hover:text-ink">+ Write new post</a>
      <a href="<?= url('/') ?>" target="_blank" class="rounded-lg border border-paper/25 px-4 py-2.5 text-xs font-bold text-paper/80 transition hover:border-paper hover:text-paper">View site ↗</a>
    </div>
  </div>
</div>

<!-- ===== Stat cards ===== -->
<div class="mt-4 grid gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">

  <div class="reveal reveal-1 rounded-xl border border-line bg-surface p-4 sm:p-5">
    <div class="flex items-center justify-between">
      <p class="kicker text-muted">Pageviews &middot; 30 days</p>
      <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent/10 text-accent">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
      </span>
    </div>
    <p class="mt-3 font-display text-3xl font-bold sm:text-4xl"><?= number_format($charts['totals']['pageviews']) ?></p>
    <p class="mt-1"><?= $deltaHtml($charts['delta']['pageviews']) ?> &middot; <span class="text-xs text-muted"><?= number_format($charts['today']['pageviews']) ?> today</span></p>
  </div>

  <div class="reveal reveal-2 rounded-xl border border-line bg-surface p-4 sm:p-5">
    <div class="flex items-center justify-between">
      <p class="kicker text-muted">Visitors &middot; 30 days</p>
      <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
      </span>
    </div>
    <p class="mt-3 font-display text-3xl font-bold sm:text-4xl"><?= number_format($charts['totals']['visitors']) ?></p>
    <p class="mt-1"><?= $deltaHtml($charts['delta']['visitors']) ?> &middot; <span class="text-xs text-muted"><?= number_format($charts['today']['visitors']) ?> today</span></p>
  </div>

  <div class="reveal reveal-3 rounded-xl border border-line bg-surface p-4 sm:p-5">
    <div class="flex items-center justify-between">
      <p class="kicker text-muted">Published</p>
      <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/></svg>
      </span>
    </div>
    <p class="mt-3 font-display text-3xl font-bold sm:text-4xl"><?= $stats['posts'] ?></p>
    <p class="mt-1 text-xs text-muted"><?= $stats['drafts'] ?> draft<?= $stats['drafts'] !== 1 ? 's' : '' ?> in progress</p>
  </div>

  <div class="reveal reveal-4 rounded-xl border border-line bg-surface p-4 sm:p-5">
    <div class="flex items-center justify-between">
      <p class="kicker text-muted">Pending comments</p>
      <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
      </span>
    </div>
    <p class="mt-3 font-display text-3xl font-bold sm:text-4xl"><?= $stats['comments'] ?></p>
    <a href="<?= admin_url('comments.php') ?>" class="mt-1 inline-block text-xs font-semibold text-primary transition hover:text-accent">Review now &rarr;</a>
  </div>
</div>

<!-- ===== Analytics: traffic + comment health ===== -->
<div class="mt-5 grid gap-4 sm:gap-6 xl:grid-cols-3">

  <section class="rounded-xl border border-line bg-surface xl:col-span-2">
    <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5 sm:py-4">
      <div>
        <h2 class="font-display text-lg font-semibold">Traffic</h2>
        <p class="text-xs text-muted">Pageviews &amp; unique visitors &middot; last <?= $charts['days'] ?> days (<?= e($charts['tz']) ?> time)</p>
      </div>
      <div class="shrink-0 text-right"><?= $deltaHtml($charts['delta']['pageviews']) ?></div>
    </div>
    <div class="p-3 sm:p-5">
      <?php if (($charts['totals']['pageviews'] + $charts['totals']['prev_pv']) === 0): ?>
        <div class="flex h-56 flex-col items-center justify-center px-6 text-center sm:h-72">
          <p class="text-sm font-semibold">No traffic data yet</p>
          <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-muted">Tracking is live &mdash; real reader activity (bots, prefetches and refreshes filtered out) will appear here as the site gets visits.</p>
        </div>
      <?php else: ?>
        <div class="h-56 sm:h-72"><canvas id="traffic-chart"></canvas></div>
      <?php endif; ?>
    </div>
  </section>

  <section class="rounded-xl border border-line bg-surface">
    <div class="border-b border-line px-4 py-3 sm:px-5 sm:py-4">
      <h2 class="font-display text-lg font-semibold">Comment health</h2>
      <p class="text-xs text-muted">Moderation breakdown &middot; all time</p>
    </div>
    <div class="p-3 sm:p-5">
      <?php if (array_sum($charts['comments']) === 0): ?>
        <div class="flex h-56 items-center justify-center text-sm text-muted sm:h-72">No comments yet.</div>
      <?php else: ?>
        <div class="h-56 sm:h-72"><canvas id="comment-chart"></canvas></div>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- ===== Analytics: top articles + categories ===== -->
<div class="mt-4 grid gap-4 sm:gap-6 xl:grid-cols-3">

  <section class="rounded-xl border border-line bg-surface xl:col-span-2">
    <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-3 sm:px-5 sm:py-4">
      <div>
        <h2 class="font-display text-lg font-semibold">Top articles</h2>
        <p class="text-xs text-muted">Most read &middot; last <?= $charts['days'] ?> days</p>
      </div>
      <a href="<?= admin_url('posts.php') ?>" class="shrink-0 text-xs font-bold text-primary transition hover:text-accent">All posts &rarr;</a>
    </div>
    <div class="p-3 sm:p-5">
      <?php if (!$charts['top_posts']): ?>
        <div class="flex h-64 flex-col items-center justify-center px-6 text-center sm:h-80">
          <p class="text-sm font-semibold">No article reads yet</p>
          <p class="mt-1.5 max-w-xs text-xs text-muted">Ranked by real reads as visitors open your articles.</p>
        </div>
      <?php else: ?>
        <div class="h-72 sm:h-80"><canvas id="top-posts-chart"></canvas></div>
      <?php endif; ?>
    </div>
  </section>

  <section class="rounded-xl border border-line bg-surface">
    <div class="border-b border-line px-4 py-3 sm:px-5 sm:py-4">
      <h2 class="font-display text-lg font-semibold">Reads by category</h2>
      <p class="text-xs text-muted">Where readers spend time &middot; last <?= $charts['days'] ?> days</p>
    </div>
    <div class="p-3 sm:p-5">
      <?php if (!$charts['categories']): ?>
        <div class="flex h-64 items-center justify-center px-6 text-center text-sm text-muted sm:h-80">No category reads yet.</div>
      <?php else: ?>
        <div class="h-72 sm:h-80"><canvas id="category-chart"></canvas></div>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- ===== Two-column: recent posts + sidebar ===== -->
<div class="mt-5 grid gap-4 sm:gap-6 xl:grid-cols-3">

  <!-- Recent posts -->
  <section class="xl:col-span-2">
    <div class="rounded-xl border border-line bg-surface">
      <div class="flex items-center justify-between border-b border-line px-4 py-3 sm:px-5 sm:py-4">
        <h2 class="font-display text-lg font-semibold">Recent posts</h2>
        <a href="<?= admin_url('posts.php') ?>" class="text-xs font-bold text-primary transition hover:text-accent">View all &rarr;</a>
      </div>

      <?php if (!$recentPosts): ?>
        <div class="p-10 text-center text-sm text-muted">
          No posts yet. <a href="<?= admin_url('post-edit.php') ?>" class="font-semibold text-primary">Create your first one</a>.
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase tracking-wider text-muted">
              <tr>
                <th class="px-4 py-2.5 font-bold sm:px-5 sm:py-3">Title</th>
                <th class="px-4 py-2.5 font-bold sm:px-5 sm:py-3">Status</th>
                <th class="px-4 py-2.5 font-bold sm:px-5 sm:py-3">Views</th>
                <th class="px-4 py-2.5 font-bold sm:px-5 sm:py-3">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-line">
              <?php foreach ($recentPosts as $p): ?>
                <tr class="transition hover:bg-paper">
                  <td class="max-w-xs px-4 py-3 sm:px-5 sm:py-3.5">
                    <a href="<?= admin_url('post-edit.php?id=' . (int) $p['id']) ?>" class="block truncate font-semibold transition hover:text-accent">
                      <?= e($p['title']) ?>
                    </a>
                    <span class="text-xs text-muted">by <?= e($p['author']) ?></span>
                  </td>
                  <td class="px-4 py-3 sm:px-5 sm:py-3.5">
                    <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-extrabold uppercase tracking-wide
                      <?= $p['status'] === 'published' ? 'bg-emerald-500/10 text-emerald-700' : 'bg-amber-500/10 text-amber-700' ?>">
                      <?= e($p['status']) ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 font-semibold sm:px-5 sm:py-3.5"><?= number_format((int) $p['real_views']) ?></td>
                  <td class="px-4 py-3 text-xs text-muted sm:px-5 sm:py-3.5"><?= e($p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : date('M j, Y', strtotime($p['created_at']))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pending comments -->
    <div class="mt-6 rounded-xl border border-line bg-surface">
      <div class="flex items-center justify-between border-b border-line px-4 py-3 sm:px-5 sm:py-4">
        <h2 class="font-display text-lg font-semibold">Awaiting moderation</h2>
        <a href="<?= admin_url('comments.php') ?>" class="text-xs font-bold text-primary transition hover:text-accent">Manage &rarr;</a>
      </div>
      <?php if (!$pendingComments): ?>
        <p class="p-8 text-center text-sm text-muted">All clear — no pending comments. 🎉</p>
      <?php else: ?>
        <ul class="divide-y divide-line">
          <?php foreach ($pendingComments as $c): ?>
            <li class="flex items-start gap-3 px-4 py-3 sm:gap-4 sm:px-5 sm:py-4">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-soft text-sm font-bold text-accent">
                <?= e(strtoupper(mb_substr($c['name'], 0, 1))) ?>
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm"><strong><?= e($c['name']) ?></strong> on
                  <a href="<?= url('/post/' . $c['slug']) ?>#comments" target="_blank" class="font-semibold text-primary hover:text-accent"><?= e($c['title']) ?></a>
                </p>
                <p class="mt-0.5 truncate text-xs text-muted"><?= e(excerpt_text($c['content'], 110)) ?></p>
              </div>
              <form method="post" action="<?= admin_url('comments.php') ?>" class="shrink-0">
                <?= csrf_field() ?>
                <input type="hidden" name="ids[]" value="<?= (int) $c['id'] ?>">
                <button name="action" value="approve" class="rounded-lg border border-line px-3 py-1.5 text-xs font-bold transition hover:border-emerald-600 hover:bg-emerald-600 hover:text-white">Approve</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>

  <!-- Right rail -->
  <aside class="space-y-4 sm:space-y-6">

    <!-- Quick actions -->
    <div class="rounded-xl border border-line bg-surface p-4 sm:p-5">
      <h2 class="kicker text-accent">Quick actions</h2>
      <div class="mt-4 grid grid-cols-2 gap-2.5">
        <a href="<?= admin_url('post-edit.php') ?>" class="rounded-lg border border-line px-3 py-3 text-center text-xs font-bold transition hover:border-ink hover:bg-ink hover:text-paper">+ New post</a>
        <a href="<?= admin_url('categories.php') ?>" class="rounded-lg border border-line px-3 py-3 text-center text-xs font-bold transition hover:border-ink hover:bg-ink hover:text-paper">Categories</a>
        <a href="<?= admin_url('media.php') ?>" class="rounded-lg border border-line px-3 py-3 text-center text-xs font-bold transition hover:border-ink hover:bg-ink hover:text-paper">Upload media</a>
        <a href="<?= admin_url('settings.php') ?>" class="rounded-lg border border-line px-3 py-3 text-center text-xs font-bold transition hover:border-ink hover:bg-ink hover:text-paper">Settings</a>
      </div>
    </div>

    <!-- Publishing cadence -->
    <div class="rounded-xl border border-line bg-surface p-4 sm:p-5">
      <h2 class="kicker text-accent">Publishing cadence</h2>
      <p class="mt-1 text-xs text-muted">Posts published per month &middot; last 12 months</p>
      <?php if ((int) array_sum($charts['months']['counts']) === 0): ?>
        <p class="mt-6 text-sm text-muted">No posts published yet.</p>
      <?php else: ?>
        <div class="mt-4 h-36 sm:h-44"><canvas id="cadence-chart"></canvas></div>
      <?php endif; ?>
    </div>

    <!-- Environment -->
    <div class="rounded-xl border border-line bg-ink p-5 text-paper">
      <h2 class="kicker text-accent">System</h2>
      <ul class="mt-3.5 space-y-2 text-xs text-paper/65">
        <li class="flex justify-between"><span>PHP</span> <strong class="text-paper"><?= PHP_VERSION ?></strong></li>
        <li class="flex justify-between"><span>MySQL</span> <strong class="text-paper"><?= e(db()->query('SELECT VERSION()')->fetchColumn()) ?></strong></li>
        <li class="flex justify-between"><span>Categories</span> <strong class="text-paper"><?= $stats['categories'] ?></strong></li>
        <li class="flex justify-between"><span>Authors</span> <strong class="text-paper"><?= $stats['users'] ?></strong></li>
        <li class="flex justify-between"><span>Lifetime views</span> <strong class="text-paper"><?= number_format($stats['views']) ?></strong></li>
        <li class="flex justify-between"><span>Unread messages</span> <a href="<?= admin_url('messages.php') ?>" class="font-bold text-accent transition hover:text-paper"><?= $stats['messages'] ?></a></li>
        <li class="flex justify-between"><span>Messages (7d)</span> <strong class="text-paper"><?= $recentMessages ?></strong></li>
      </ul>
    </div>
  </aside>
</div>

<?php
// Analytics payload + self-hosted Chart.js (dashboard-only, cache-busted)
$_chartsJson = json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>
<script>window.__TS_STATS__ = <?= $_chartsJson ?>;</script>
<script src="<?= url('/assets/js/chart.umd.min.js') ?>?v=<?= e(asset_version()) ?>"></script>
<script src="<?= url('/assets/js/dashboard-charts.js') ?>?v=<?= e(asset_version()) ?>"></script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
