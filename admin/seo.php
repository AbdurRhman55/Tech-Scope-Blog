<?php
/**
 *  Admin — SEO settings
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_role(['admin', 'editor']);
$pdo        = db();

$seoKeys = ['site_description', 'meta_keywords', 'ga_measurement_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'save') {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($seoKeys as $key) {
            $stmt->execute([$key, trim((string) ($_POST[$key] ?? ''))]);
        }
        flash_set('success', 'SEO settings saved.');
    }
    header('Location: ' . admin_url('seo.php'));
    exit;
}

$s = settings();

$admin_title = 'SEO Settings';
$admin_page  = 'seo';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="grid gap-6 lg:grid-cols-[1fr_360px]">

  <div class="rounded-xl border border-line bg-surface p-6">
    <h2 class="font-display text-lg font-semibold">Search engine settings</h2>
    <p class="mt-1 text-sm text-muted">These defaults apply site-wide; individual posts can override them.</p>

    <form method="post" action="<?= admin_url('seo.php') ?>" class="mt-6 space-y-5">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">

      <div>
        <label class="kicker text-muted" for="sd">Site description (meta) *</label>
        <textarea id="sd" name="site_description" rows="3" maxlength="300" required
                  class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none focus:border-primary"><?= e($s['site_description'] ?? '') ?></textarea>
        <p class="mt-1 text-right text-xs text-muted">Recommended: 140–160 characters</p>
      </div>

      <div>
        <label class="kicker text-muted" for="mk">Meta keywords</label>
        <input id="mk" name="meta_keywords" maxlength="500" value="<?= e($s['meta_keywords'] ?? '') ?>"
               placeholder="ai, technology, programming…"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none focus:border-primary">
        <p class="mt-1 text-xs text-muted">Minor ranking factor today, but harmless to include.</p>
      </div>

      <div>
        <label class="kicker text-muted" for="ga">Google Analytics measurement ID</label>
        <input id="ga" name="ga_measurement_id" value="<?= e($s['ga_measurement_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 font-mono text-sm outline-none focus:border-primary">
      </div>

      <div class="border-t border-line pt-5">
        <button class="rounded-lg bg-ink px-7 py-3 text-sm font-bold text-paper transition hover:bg-accent">Save SEO settings</button>
      </div>
    </form>
  </div>

  <aside class="space-y-5">
    <!-- Preview -->
    <div class="rounded-xl border border-line bg-surface p-5">
      <h3 class="kicker text-accent">Search preview</h3>
      <div class="mt-3 rounded-lg border border-line bg-paper p-4">
        <p class="text-sm text-primary"><?= e(setting('site_name', SITE_NAME)) ?></p>
        <p class="mt-0.5 text-sm text-emerald-700"><?= e(rtrim(SITE_URL, '/')) ?> › post › example-slug</p>
        <p class="mt-1 text-base font-medium text-ink">Example Article Title Goes Here</p>
        <p class="mt-1 text-xs leading-relaxed text-muted"><?= e(excerpt_text($s['site_description'] ?? '', 160)) ?></p>
      </div>
    </div>

    <!-- Checklist -->
    <div class="rounded-xl border border-line bg-ink p-5 text-paper">
      <h3 class="kicker text-accent">SEO checklist</h3>
      <ul class="mt-3.5 space-y-2.5 text-xs text-paper/70">
        <?php
        $checks = [
            ['Site description filled', ($s['site_description'] ?? '') !== ''],
            ['XML sitemap live', true],
            ['Clean URLs enabled', true],
            ['HTTPS active', !empty($_SERVER['HTTPS'])],
            ['Analytics configured', ($s['ga_measurement_id'] ?? '') !== ''],
        ];
        foreach ($checks as [$label, $ok]):
        ?>
          <li class="flex items-center gap-2.5">
            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[0.6rem] font-bold <?= $ok ? 'bg-emerald-500 text-white' : 'bg-accent text-white' ?>"><?= $ok ? '✓' : '!' ?></span>
            <span class="<?= $ok ? '' : 'text-accent' ?>"><?= e($label) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="mt-4 border-t border-white/10 pt-3 text-[0.7rem] text-paper/50">
        Submit <?= e(SITE_URL) ?>/sitemap.xml to Google Search Console.
      </p>
    </div>
  </aside>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
