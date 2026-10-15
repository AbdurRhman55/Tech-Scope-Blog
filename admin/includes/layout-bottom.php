    </main>

    <footer class="border-t border-line px-5 py-5 text-xs text-muted sm:px-8">
      &copy; <?= date('Y') ?> <?= e(setting('site_name', SITE_NAME)) ?> Admin &middot; PHP <?= PHP_VERSION ?> &middot;
      <a href="<?= url('/') ?>" class="transition hover:text-accent">View site</a>
    </footer>
  </div>
</div>

<?php $_assetVer = function_exists('asset_version') ? asset_version() : '1'; ?>
<script src="<?= url('/assets/js/alpine.min.js') ?>?v=<?= e($_assetVer) ?>"></script>
<script>
  if (!window.Alpine) {
    document.write('<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"><\/script>');
  }
</script>
<script src="<?= url('/assets/js/main.js') ?>?v=<?= e($_assetVer) ?>"></script>
<script src="<?= url('/assets/js/admin.js') ?>?v=<?= e($_assetVer) ?>"></script>
</body>
</html>
