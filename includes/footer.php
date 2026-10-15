</main>

<?php
/* ------------------------------------------------------------------
 *  Footer data — self-sufficient (works even if header.php ran first)
 * ------------------------------------------------------------------ */
$_fLogo = function_exists('site_logo_src') ? site_logo_src() : null;
$_fName = setting('site_name', SITE_NAME);
$_fDesc = trim((string) setting('site_description', ''));
$_fCats = isset($_categories) && is_array($_categories)
    ? $_categories
    : (function_exists('get_categories') ? get_categories() : []);

/*
 * Social profiles — only real http(s) URLs are rendered. Empty values or
 * placeholders are silently ignored, so visitors never see admin-only
 * hints or dummy links.
 */
$_socialIcons = [
    'facebook_url'  => ['Facebook', 'M13.5 9H16V6h-2.5C11.6 6 10 7.6 10 9.5V11H8v3h2v7h3v-7h2.2l.5-3H13v-1.2c0-.5.2-.8.5-.8Z'],
    'twitter_url'   => ['X / Twitter', 'M18.9 8.2c-.5.3-1.1.5-1.7.6.6-.4 1.1-1 1.3-1.7-.6.4-1.2.6-1.9.8A3 3 0 0 0 12 10.4c0 .2 0 .5.1.7-2.5-.1-4.7-1.3-6.2-3.2-.3.5-.4 1-.4 1.6 0 1 .5 1.9 1.3 2.4-.5 0-1-.1-1.4-.4v.1c0 1.5 1 2.7 2.4 3-.3.1-.5.1-.8.1h-.6c.4 1.2 1.5 2.1 2.8 2.1A6 6 0 0 1 5 17.6a8.5 8.5 0 0 0 4.6 1.3c5.5 0 8.5-4.6 8.5-8.5v-.4c.6-.4 1.1-1 1.5-1.6Z'],
    'linkedin_url'  => ['LinkedIn', 'M8 10H5v9h3v-9ZM6.5 5.5A1.75 1.75 0 1 0 6.5 9a1.75 1.75 0 0 0 0-3.5ZM19 19v-5.2c0-2.6-1.4-3.8-3.2-3.8-1.5 0-2.2.8-2.6 1.4V10H10v9h3.2v-5c0-1.3.6-2.1 1.8-2.1s1.7.8 1.7 2.1V19H19Z'],
    'instagram_url' => ['Instagram', 'M12 8.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5Zm0 5.7a2.2 2.2 0 1 1 0-4.4 2.2 2.2 0 0 1 0 4.4ZM17.8 8.3a.9.9 0 1 1-1.8 0 .9.9 0 0 1 1.8 0ZM16 4H8a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4h8a4 4 0 0 0 4-4V8a4 4 0 0 0-4-4Zm2.7 12A2.7 2.7 0 0 1 16 18.7H8A2.7 2.7 0 0 1 5.3 16V8A2.7 2.7 0 0 1 8 5.3h8A2.7 2.7 0 0 1 18.7 8v8Z'],
    'github_url'    => ['GitHub', 'M12 4a8 8 0 0 0-2.5 15.6c.4.1.5-.2.5-.4v-1.4c-2.2.5-2.7-1-2.7-1-.4-1-1-1.3-1-1.3-.7-.5.1-.5.1-.5.8.1 1.3.9 1.3.9.7 1.3 1.9.9 2.4.7.1-.5.3-.9.5-1.1-1.8-.2-3.7-.9-3.7-4a3.2 3.2 0 0 1 .8-2.2c-.1-.2-.4-1 .1-2.2 0 0 .7-.2 2.2.8a7.6 7.6 0 0 1 4 0c1.5-1 2.2-.8 2.2-.8.5 1.2.2 2 .1 2.2a3.2 3.2 0 0 1 .8 2.2c0 3.1-1.9 3.8-3.7 4 .3.3.6.8.6 1.5v2.2c0 .2.1.5.5.4A8 8 0 0 0 12 4Z'],
    'youtube_url'   => ['YouTube', 'M21.6 8s-.2-1.4-.8-2c-.7-.8-1.6-.8-2-.9C16 4.9 12 4.9 12 4.9s-4 0-6.8.2c-.4 0-1.3 0-2 .9-.6.6-.8 2-.8 2S2.2 9.6 2.2 11.3v1.5c0 1.6.2 3.3.2 3.3s.2 1.4.8 2c.7.8 1.7.7 2.1.8 1.6.2 6.7.2 6.7.2s4 0 6.8-.2c.4-.1 1.3-.1 2-.9.6-.6.8-2 .8-2s.2-1.6.2-3.3v-1.5c0-1.6-.2-3.2-.2-3.2ZM9.9 14.6V9.4l5.2 2.6-5.2 2.6Z'],
];
$_socials = [];
foreach ($_socialIcons as $_key => $_meta) {
    $_url = trim((string) setting($_key));
    if ($_url !== '' && preg_match('#^https?://#i', $_url)) {
        $_socials[] = ['label' => $_meta[0], 'path' => $_meta[1], 'url' => $_url];
    }
}
?>

<!-- ================= FOOTER ================= -->
<footer class="relative overflow-hidden bg-[#0d1117] text-paper" aria-label="Site footer">

  <!-- Subtle gradient glow top -->
  <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-accent/60 to-transparent"></div>
  <div class="pointer-events-none absolute left-1/2 top-0 h-64 w-[600px] -translate-x-1/2 rounded-full bg-accent/5 blur-3xl"></div>

  <!-- ── Newsletter CTA band ── -->
  <div class="relative border-b border-paper/8">
    <div class="mx-auto max-w-[96rem] px-6 py-12 sm:py-14">
      <div class="flex flex-col items-center gap-8 text-center lg:flex-row lg:items-center lg:justify-between lg:text-left">
        <div class="max-w-lg">
          <span class="kicker text-accent">Stay in the loop</span>
          <h2 class="mt-3 font-display text-2xl font-semibold leading-snug tracking-tight text-paper sm:text-3xl">
            Get the best articles<br class="hidden sm:block"> delivered to your inbox
          </h2>
          <p class="mt-3 text-sm leading-relaxed text-paper/50">No spam, ever. Unsubscribe any time.</p>
        </div>
        <form action="<?= url('/contact') ?>" method="get"
              class="flex w-full max-w-md flex-col gap-3 sm:flex-row">
          <label for="footer-email" class="sr-only">Your email address</label>
          <input id="footer-email" type="email" name="email" placeholder="your@email.com"
                 class="flex-1 rounded-xl border border-paper/15 bg-paper/8 px-4 py-3 text-sm text-paper placeholder-paper/35 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
          <button type="submit"
                  class="shrink-0 rounded-xl bg-accent px-6 py-3 text-sm font-bold text-white transition hover:bg-accent/85 active:scale-[0.97]">
            Subscribe
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Main footer grid ── -->
  <div class="relative mx-auto max-w-[96rem] px-6 pt-14 pb-10">
    <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-[1.8fr_1fr_1fr_1fr]">

      <!-- Brand column -->
      <div class="sm:col-span-2 lg:col-span-1">
        <?php if ($_fLogo): ?>
        <a href="<?= url('/') ?>"
           class="inline-flex rounded-2xl bg-white/10 p-4 backdrop-blur-sm transition hover:-translate-y-0.5 hover:bg-white/15"
           aria-label="<?= e($_fName) ?> — home">
          <img src="<?= e($_fLogo) ?>" alt="<?= e($_fName) ?>" class="h-10 w-auto sm:h-12"
               width="1033" height="643" loading="lazy">
        </a>
        <?php else: ?>
        <a href="<?= url('/') ?>"
           class="group inline-flex items-baseline gap-2"
           aria-label="<?= e($_fName) ?> — home">
          <span class="font-display text-2xl font-bold leading-none tracking-tight text-paper sm:text-3xl">
            <?= e($_fName) ?>
          </span>
          <span class="h-2 w-2 rounded-full bg-accent transition-transform duration-300 group-hover:scale-150"></span>
        </a>
        <?php endif; ?>

        <?php if ($_fDesc !== ''): ?>
        <p class="mt-4 max-w-xs text-[0.83rem] leading-relaxed text-paper/45"><?= e($_fDesc) ?></p>
        <?php endif; ?>

        <!-- Social row (icons only) — shown when profiles are configured -->
        <?php if ($_socials): ?>
        <div class="mt-6 flex flex-wrap gap-2">
          <?php foreach ($_socials as $s): ?>
          <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer"
             aria-label="Follow us on <?= e($s['label']) ?>"
             class="group inline-flex h-9 w-9 items-center justify-center rounded-lg border border-paper/12 bg-paper/6 text-paper/55 transition hover:border-accent/60 hover:bg-accent hover:text-white">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="<?= $s['path'] ?>"/>
            </svg>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Explore column -->
      <div>
        <h3 class="kicker text-accent/90">Explore</h3>
        <ul class="mt-5 space-y-3">
          <?php
          $exploreLinks = [
            ['label' => 'Home',          'href' => url('/')],
            ['label' => 'All Articles',  'href' => url('/blog')],
            ['label' => 'Categories',    'href' => url('/categories')],
            ['label' => 'About Us',      'href' => url('/about')],
            ['label' => 'Contact',       'href' => url('/contact')],
            ['label' => 'Sitemap',       'href' => url('/sitemap')],
          ];
          foreach ($exploreLinks as $lnk): ?>
          <li>
            <a href="<?= $lnk['href'] ?>"
               class="group inline-flex items-center gap-2 text-sm text-paper/55 transition hover:text-paper">
              <span class="h-px w-0 bg-accent transition-all duration-300 group-hover:w-4"></span>
              <?= e($lnk['label']) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Topics column -->
      <div>
        <h3 class="kicker text-accent/90">Topics</h3>
        <ul class="mt-5 space-y-3">
          <?php foreach (array_slice($_fCats, 0, 7) as $cat): ?>
          <li>
            <a href="<?= url('/category/' . $cat['slug']) ?>"
               class="group inline-flex items-center gap-2 text-sm text-paper/55 transition hover:text-paper">
              <span class="h-px w-0 bg-accent transition-all duration-300 group-hover:w-4"></span>
              <?= e($cat['name']) ?>
            </a>
          </li>
          <?php endforeach; ?>
          <?php if (count($_fCats) > 7): ?>
          <li>
            <a href="<?= url('/categories') ?>"
               class="inline-flex items-center gap-1 text-xs font-bold text-accent/70 transition hover:text-accent">
              View all
              <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
              </svg>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Legal / Company column -->
      <div>
        <h3 class="kicker text-accent/90">Company</h3>
        <ul class="mt-5 space-y-3">
          <?php
          $legalLinks = [
            ['label' => 'Privacy Policy',     'href' => url('/privacy')],
            ['label' => 'Terms & Conditions', 'href' => url('/terms')],
            ['label' => 'Disclaimer',         'href' => url('/disclaimer')],
            ['label' => 'Contact Us',         'href' => url('/contact')],
          ];
          foreach ($legalLinks as $lnk): ?>
          <li>
            <a href="<?= $lnk['href'] ?>"
               class="group inline-flex items-center gap-2 text-sm text-paper/55 transition hover:text-paper">
              <span class="h-px w-0 bg-accent transition-all duration-300 group-hover:w-4"></span>
              <?= e($lnk['label']) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- ── Bottom bar ── -->
    <div class="mt-14 flex flex-col items-center justify-between gap-4 border-t border-paper/8 pt-7 sm:flex-row">

      <!-- Copyright -->
      <p class="text-center text-xs text-paper/35 sm:text-left">
        &copy; <?= date('Y') ?> <?= e($_fName) ?>.
        All rights reserved. Crafted with&nbsp;
        <svg class="inline-block h-3 w-3 text-accent" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402C1 3.518 3.51 1 6.6 1c2.023 0 3.9 1.197 5.4 2.855C13.5 2.197 15.377 1 17.4 1 20.49 1 23 3.518 23 7.191c0 4.105-5.37 8.863-11 14.402Z"/>
        </svg>
      </p>

      <!-- Quick nav -->
      <nav class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1" aria-label="Footer quick links">
        <a href="<?= url('/') ?>"          class="text-xs text-paper/35 transition hover:text-paper/70">Home</a>
        <a href="<?= url('/blog') ?>"      class="text-xs text-paper/35 transition hover:text-paper/70">Blog</a>
        <a href="<?= url('/about') ?>"     class="text-xs text-paper/35 transition hover:text-paper/70">About</a>
        <a href="<?= url('/contact') ?>"   class="text-xs text-paper/35 transition hover:text-paper/70">Contact</a>
        <a href="<?= url('/privacy') ?>"   class="text-xs text-paper/35 transition hover:text-paper/70">Privacy</a>
        <a href="<?= url('/sitemap') ?>"   class="text-xs text-paper/35 transition hover:text-paper/70">Sitemap</a>
      </nav>

      <!-- Back to top -->
      <button id="back-to-top" type="button"
              class="group inline-flex items-center gap-2 rounded-full border border-paper/15 px-4 py-2 text-xs font-bold uppercase tracking-widest text-paper/45 transition hover:border-accent hover:bg-accent hover:text-white">
        Top
        <svg class="h-3 w-3 transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24"
             stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
        </svg>
      </button>
    </div>
  </div>
</footer>

<?php
// Cache-busting for JS (matches the stylesheet version strategy in header.php)
$_jsVer = function_exists('asset_version') ? asset_version() : (string) @filemtime(ROOT_PATH . '/assets/js/main.js');
?>
<script src="<?= url('/assets/js/alpine.min.js') ?>?v=<?= e($_jsVer) ?>"></script>
<script>
  if (!window.Alpine) {
    document.write('<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"><\/script>');
  }
</script>
<script src="<?= url('/assets/js/main.js') ?>?v=<?= e($_jsVer) ?>"></script>
</body>
</html>
