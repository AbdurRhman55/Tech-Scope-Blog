<?php
/**
 *  Admin — Website settings (branding, contact, socials, reading)
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_role(['admin']);
$pdo        = db();

$groups = [
    'General'   => ['site_name', 'site_tagline', 'site_url', 'site_logo', 'site_favicon', 'site_description'],
    'Contact'   => ['contact_email', 'contact_phone', 'contact_address'],
    'Social'    => ['facebook_url', 'twitter_url', 'linkedin_url', 'instagram_url', 'github_url', 'youtube_url'],
    'Reading'   => ['posts_per_page', 'allow_comments', 'comments_moderation'],
    'Advertising' => ['ads_header', 'ads_inpost', 'ads_footer'],
    'Footer'    => ['footer_text'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'save') {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($groups as $keys) {
            foreach ($keys as $key) {
                if (isset($_POST[$key]) || $key === 'site_logo' || $key === 'site_favicon') {
                    $stmt->execute([$key, trim((string) ($_POST[$key] ?? ''))]);
                }
            }
        }
        flash_set('success', 'Settings saved.');
    } elseif (($_POST['action'] ?? '') === 'logo' && isset($_FILES['logo_file'])) {
        $f = $_FILES['logo_file'];
        if ($f['error'] === UPLOAD_ERR_OK && $f['size'] <= MAX_UPLOAD_SIZE) {
            $mime = mime_content_type($f['tmp_name']);
            $map = ['image/png' => 'png', 'image/svg+xml' => 'svg', 'image/webp' => 'webp', 'image/jpeg' => 'jpg'];
            if (isset($map[$mime])) {
                if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
                $name = 'logo-' . substr(md5((string) microtime(true)), 0, 6) . '.' . $map[$mime];
                if (move_uploaded_file($f['tmp_name'], UPLOADS_DIR . '/' . $name)) {
                    $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES ("site_logo", ?)
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
                        ->execute(['assets/uploads/' . $name]);
                    flash_set('success', 'Logo updated.');
                }
            } else {
                flash_set('error', 'Logo must be PNG, SVG, WebP or JPG.');
            }
        } else {
            flash_set('error', 'Upload failed (max 5 MB).');
        }
    }
    header('Location: ' . admin_url('settings.php'));
    exit;
}

$s = settings();

$admin_title = 'Website Settings';
$admin_page  = 'settings';
require __DIR__ . '/includes/layout-top.php';
?>

<form method="post" action="<?= admin_url('settings.php') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">

  <div class="grid gap-6 xl:grid-cols-2">

    <?php foreach ($groups as $title => $keys): ?>
      <div class="h-fit rounded-xl border border-line bg-surface p-6">
        <h2 class="font-display text-lg font-semibold"><?= e($title) ?></h2>

        <div class="mt-5 space-y-4">
          <?php foreach ($keys as $key):
              $val = $s[$key] ?? '';
              $label = ucwords(str_replace('_', ' ', $key));
          ?>
            <div>
              <label class="kicker text-muted" for="s-<?= e($key) ?>"><?= e($label) ?></label>

              <?php if (in_array($key, ['site_description', 'footer_text', 'ads_header', 'ads_inpost', 'ads_footer'], true)): ?>
                <textarea id="s-<?= e($key) ?>" name="<?= e($key) ?>" rows="3"
                          class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($val) ?></textarea>

              <?php elseif (in_array($key, ['allow_comments', 'comments_moderation'], true)): ?>
                <label class="mt-2 flex cursor-pointer items-center gap-2.5 text-sm font-semibold">
                  <input type="checkbox" name="<?= e($key) ?>" value="1" <?= $val === '1' ? 'checked' : '' ?> class="accent-accent" id="s-<?= e($key) ?>">
                  Enabled
                </label>

              <?php elseif ($key === 'posts_per_page'): ?>
                <input id="s-<?= e($key) ?>" type="number" min="1" max="48" name="<?= e($key) ?>" value="<?= e($val ?: '9') ?>"
                       class="mt-1.5 w-28 rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">

              <?php else: ?>
                <input id="s-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>"
                       placeholder="<?= e($label) ?>"
                       class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <?php if ($title === 'General'): ?>
            <!-- Logo upload -->
            <div class="border-t border-line pt-4">
              <p class="kicker text-muted">Site logo</p>
              <?php if (!empty($s['site_logo'])): ?>
                <img src="<?= url('/' . e($s['site_logo'])) ?>" alt="Logo" class="mt-2 max-h-14 rounded border border-line bg-paper p-1.5">
              <?php endif; ?>
              <div class="mt-2 flex gap-2">
                <input type="file" name="logo_file" accept="image/*"
                       class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-xs file:mr-2 file:rounded file:border-0 file:bg-ink file:px-2.5 file:py-1 file:text-[0.65rem] file:font-bold file:text-paper">
                <button name="action" value="logo" class="shrink-0 rounded-lg border border-line px-4 py-2 text-xs font-bold transition hover:border-ink">Upload</button>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="sticky bottom-0 mt-6 flex justify-end gap-3 border-t border-line bg-paper/90 py-4 backdrop-blur">
    <a href="<?= url('/') ?>" target="_blank" class="rounded-lg border border-line bg-surface px-6 py-3 text-sm font-bold transition hover:border-ink">View site</a>
    <button class="rounded-lg bg-ink px-8 py-3 text-sm font-bold text-paper transition hover:bg-accent">Save settings</button>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
