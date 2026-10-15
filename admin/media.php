<?php
/**
 *  Admin — Media library (upload / delete)
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_admin();
$pdo        = db();

/* --- Upload / delete -------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload' && isset($_FILES['file'])) {
        $f = $_FILES['file'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            if ($f['size'] > MAX_UPLOAD_SIZE) {
                flash_set('error', 'File too large (max 5 MB).');
            } else {
                $mime = mime_content_type($f['tmp_name']);
                $allowed = [
                    'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                    'image/gif' => 'gif', 'image/svg+xml' => 'svg',
                ];
                if (!isset($allowed[$mime])) {
                    flash_set('error', 'Only JPG, PNG, WebP, GIF or SVG allowed.');
                } else {
                    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
                    $name = slugify(pathinfo($f['name'], PATHINFO_FILENAME)) . '-' . substr(md5((string) microtime(true)), 0, 6) . '.' . $allowed[$mime];
                    if (move_uploaded_file($f['tmp_name'], UPLOADS_DIR . '/' . $name)) {
                        $pdo->prepare('INSERT INTO media (filename, path, mime_type, size_bytes, alt_text, uploaded_by) VALUES (?,?,?,?,?,?)')
                            ->execute([$f['name'], $name, $mime, $f['size'], trim((string) ($_POST['alt'] ?? '')) ?: null, (int) $admin_user['id']]);
                        flash_set('success', 'Image uploaded.');
                    } else {
                        flash_set('error', 'Could not save file — check directory permissions.');
                    }
                }
            }
        } else {
            flash_set('error', 'Upload error code: ' . $f['error']);
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT path FROM media WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $file = UPLOADS_DIR . '/' . $row['path'];
            if (is_file($file)) @unlink($file);
            $pdo->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
            flash_set('success', 'Media deleted.');
        }
    }
    header('Location: ' . admin_url('media.php'));
    exit;
}

$media = $pdo->query(
    'SELECT m.*, u.name AS uploader FROM media m
     LEFT JOIN users u ON u.id = m.uploaded_by
     ORDER BY m.created_at DESC'
)->fetchAll();

$admin_title = 'Media Library';
$admin_page  = 'media';
require __DIR__ . '/includes/layout-top.php';
?>

<!-- Upload -->
<div class="rounded-xl border border-line bg-surface p-5">
  <h2 class="font-display text-lg font-semibold">Upload image</h2>
  <form method="post" action="<?= admin_url('media.php') ?>" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">

    <div class="min-w-64 flex-1">
      <label class="kicker text-muted" for="file">File (JPG, PNG, WebP, GIF, SVG — max 5 MB)</label>
      <input id="file" type="file" name="file" accept="image/*" required
             class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-ink file:px-3 file:py-1 file:text-xs file:font-bold file:text-paper">
    </div>
    <div class="w-56">
      <label class="kicker text-muted" for="alt">Alt text (optional)</label>
      <input id="alt" name="alt" maxlength="200" placeholder="Describe the image"
             class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
    </div>
    <button class="rounded-lg bg-ink px-6 py-2.5 text-sm font-bold text-paper transition hover:bg-accent">Upload</button>
  </form>
</div>

<!-- Grid -->
<div class="mt-6">
  <?php if (!$media): ?>
    <div class="rounded-xl border border-dashed border-line bg-surface py-20 text-center text-muted">
      No images yet — upload your first one above.
    </div>
  <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
      <?php foreach ($media as $m): ?>
        <div class="group overflow-hidden rounded-xl border border-line bg-surface transition hover:border-ink/40">
          <div class="relative">
            <img src="<?= url('/assets/uploads/' . e($m['path'])) ?>" alt="<?= e($m['alt_text'] ?? '') ?>" loading="lazy"
                 class="h-36 w-full object-cover">
            <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/60 opacity-0 transition group-hover:opacity-100">
              <button type="button" data-copy="<?= url('/assets/uploads/' . e($m['path'])) ?>"
                      class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-ink hover:bg-accent hover:text-white">Copy URL</button>
            </div>
          </div>
          <div class="p-3">
            <p class="truncate text-xs font-semibold" title="<?= e($m['filename']) ?>"><?= e($m['filename']) ?></p>
            <p class="mt-0.5 text-[0.68rem] text-muted"><?= number_format((int) $m['size_bytes'] / 1024, 1) ?> KB · <?= e($m['uploader'] ?? 'unknown') ?></p>
            <form method="post" action="<?= admin_url('media.php') ?>" class="mt-2" onsubmit="return confirm('Delete this image? Posts using it will show a placeholder.')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button class="w-full rounded-md border border-line py-1.5 text-[0.7rem] font-bold transition hover:border-accent hover:text-accent">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', () => {
  navigator.clipboard.writeText(location.origin + b.dataset.copy).then(() => {
    b.textContent = 'Copied!';
    setTimeout(() => b.textContent = 'Copy URL', 1500);
  });
}));
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
