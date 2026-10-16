<?php
/**
* ---------------------------------------------------------------------
*  Frontend shared header
*  Expects optional: $page_title, $page_desc, $og_type, $og_image
* ---------------------------------------------------------------------
*/

require_once __DIR__ . '/functions.php';

$_page_title = $page_title ?? null;
$_page_desc  = $page_desc ?? null;
$_og_type    = $og_type ?? 'website';
$_og_image   = $og_image ?? null;
$_categories = get_categories();
$_navTree    = get_category_tree();
// parents → children ( dynamic, DB-driven )
$_current    = basename( $_SERVER[ 'SCRIPT_NAME' ] );

// Which category chain is active ( self + ancestors → parent lights up on child pages )
$_path         = app_request_path();
// base path stripped ( subdirectory-safe )
$_activeCatIds = [];
if ( preg_match( '#^/category/([^/]+)/?$#', $_path, $_cm ) ) {
    $_curCat = get_category_by_slug( urldecode( $_cm[ 1 ] ) );
    if ( $_curCat ) {
        $_activeCatIds = category_ancestry( ( int ) $_curCat[ 'id' ] );
    }
}

/* ---------------------------------------------------------------------
*  Single-row navbar data
*  • Up to 4 featured parents = main hover-dropdown buttons
*    ( falls back to first parents if none are flagged featured )
*  • Everything else folds into the 'More' dropdown
*  • 3 latest posts per main category feed the dropdown footers
* --------------------------------------------------------------------- */
$_mainCats = [];
foreach ( $_navTree as $_c ) {
    if ( ( bool ) ( $_c[ 'is_featured' ] ?? false ) ) {
        $_mainCats[] = $_c;
    }
}
if ( !$_mainCats ) {
    // no featured flags → first 4 parents
    $_mainCats = array_slice( $_navTree, 0, 4 );
}
$_mainCats = array_slice( $_mainCats, 0, 4 );

$_mainIds = array_map( static fn( array $m ): int => ( int ) $m[ 'id' ], $_mainCats );
$_moreCats = [];
foreach ( $_navTree as $_c ) {
    if ( !in_array( ( int ) $_c[ 'id' ], $_mainIds, true ) ) {
        $_moreCats[] = $_c;
    }
}

// Latest published posts for each main category ( dropdown footer links ).
// Includes subcategories' posts — parents usually hold only their children,
// so a direct category_id match alone would leave the dropdown empty.
$_latestByCat = [];
foreach ($_mainCats as $_mc) {
    $_ids   = category_descendant_ids((int) $_mc['id']);
    $_ph    = implode(', ', array_fill(0, count($_ids), '?'));
    $_stmt  = db()->prepare(
        "SELECT slug, title FROM posts
         WHERE status = 'published' AND category_id IN ($_ph)
         ORDER BY published_at DESC LIMIT 3"
    );
    $_stmt->execute($_ids);
    $_latestByCat[(int) $_mc['id']] = $_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php seo_head($_page_title, $_page_desc, $_og_type, $_og_image); ?>
    <?php
// Cache-busting: unique version per build so browsers never serve stale CSS/JS
$_assetVer = function_exists('asset_version') ? asset_version() : (string) @filemtime(ROOT_PATH . '/assets/css/app.css');
?>
    <link rel="icon" href="<?= e(function_exists('favicon_url') ? favicon_url() : url('/assets/img/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>?v=<?= e($_assetVer) ?>">
    <!-- Google Search Console HTML-tag verification (keep permanently; removing it un-verifies the property) -->
    <meta name="google-site-verification" content="4igcp7jEnpYf5FrozZHeUR1_3cb_1W9yV1bmzwmcJD0" />
</head>

<body class="antialiased"
      x-data="{ open: false }"
      x-init="$watch('open', o => { document.body.style.overflow = o ? 'hidden' : ''; if (o) $nextTick(() => $refs.navClose.focus()); })"
      @keydown.escape.window="if (open) { open = false; $refs.navToggle.focus(); }"
      @resize.window="window.innerWidth >= 1024 && (open = false)">

    <!-- keyboard accessibility: skip straight to the content -->
    <a href="#main-content" class="skip-link">Skip to content</a>

    <!-- gradient hairline -->
    <div class="hairline-grad"></div>

    <!-- ================= TICKER BAR ================= -->
    <div class="border-b border-line bg-ink text-paper">
        <div class="mx-auto flex max-w-[96rem] items-center gap-6 px-6 py-2">
            <span class="kicker shrink-0 text-accent">Latest</span>
            <span class="hidden shrink-0 text-xs font-semibold text-paper/40 sm:block"><?= date('D, M j, Y') ?></span>
            <div class="ticker-wrap min-w-0 flex-1">
                <div class="ticker text-[0.76rem] font-semibold tracking-wide text-paper/55">
                    <?php
        // Ticker: latest post titles (slim query — slug/title only)
        $tickerItems = [];
        foreach (db()->query("SELECT slug, title FROM posts WHERE status = 'published'
                              ORDER BY published_at DESC LIMIT 5") as $tp) {
            $tickerItems[] = '<a href="' . url('/post/' . $tp['slug']) . '" class="transition hover:text-paper">' . e($tp['title']) . '</a>';
        }
        $tickerItems[] = '<span class="text-paper/35">' . e(setting('site_tagline')) . '</span>';
        $tickerItems[] = '<a href="' . url('/blog') . '" class="text-accent/90">Explore the library →</a>';
        $line = implode('<span class="px-0.5 text-paper/25">·</span>', $tickerItems);
        echo $line . '<span class="px-0.5 text-paper/25">·</span>' . $line; // duplicated for seamless loop
        ?>
                </div>
            </div>
            <a href="<?= url('/contact') ?>"
                class="hidden shrink-0 text-xs font-bold text-paper/60 transition hover:text-accent md:block">Contact</a>
        </div>
    </div>

    <!-- ================= NAVBAR (single row) ================= -->
    <header class="sticky top-0 z-50 border-b border-line bg-paper/90 backdrop-blur-lg">
        <div class="mx-auto flex max-w-[96rem] items-center gap-4 px-6 py-3.5">

            <!-- ── Logo (row start) ── -->
            <?php $_logoSrc = function_exists('site_logo_src') ? site_logo_src() : null; ?>
            <?php if ($_logoSrc): ?>
            <a href="<?= url('/') ?>" class="group flex shrink-0 items-center"
                aria-label="<?= e(setting('site_name', SITE_NAME)) ?> — home">
                <img src="<?= e($_logoSrc) ?>" alt="<?= e(setting('site_name', SITE_NAME)) ?>"
                    class="h-10 w-auto transition-transform duration-300 group-hover:-translate-y-0.5 xl:h-11"
                    width="1033" height="643" fetchpriority="high">
            </a>
            <?php else: ?>
            <a href="<?= url('/') ?>" class="group flex shrink-0 items-baseline gap-2"
                aria-label="<?= e(setting('site_name', SITE_NAME)) ?> — home">
                <span class="font-display text-[1.2rem] font-bold leading-none tracking-tight text-ink xl:text-[1.55rem]">
                    <?= e(setting('site_name', SITE_NAME)) ?>
                </span>
                <span class="h-2 w-2 rounded-full bg-accent transition-transform duration-300 group-hover:scale-150"></span>
            </a>
            <?php endif; ?>

            <!-- ── Desktop nav: main categories with hover dropdowns ── -->
            <nav aria-label="Main" class="hidden min-w-0 flex-1 items-center gap-0 lg:flex xl:gap-0.5">
                <?php $_homeActive = $_path === '/'; ?>
                <a href="<?= url('/') ?>"
                    class="relative shrink-0 rounded-lg px-2 py-2 text-[0.78rem] font-bold transition xl:px-3 xl:text-[0.84rem] <?= $_homeActive ? 'text-accent' : 'text-ink-soft hover:text-accent' ?>">
                    Home
                    <?php if ($_homeActive): ?><span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded bg-accent"></span><?php endif; ?>
                </a>

                <?php foreach ($_mainCats as $cat): ?>
                <?php
        $catPath   = '/category/' . $cat['slug'];
        $catActive = in_array((int) $cat['id'], $_activeCatIds, true);
        $kids      = $cat['children'] ?? [];
        $navLatest = $_latestByCat[(int) $cat['id']] ?? [];
        ?>
                <div class="group relative shrink-0">
                    <a href="<?= url($catPath) ?>" aria-haspopup="true"
                        class="relative flex items-center gap-1.5 rounded-lg px-2 py-2 text-[0.78rem] font-bold transition xl:px-3 xl:text-[0.84rem] <?= $catActive ? 'text-accent' : 'text-ink-soft hover:text-accent' ?>">
                        <?= e($cat['name']) ?>
                        <svg class="h-3 w-3 opacity-40 transition-transform duration-200 group-hover:rotate-180"
                            fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                        <?php if ($catActive): ?><span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded bg-accent"></span><?php endif; ?>
                    </a>
                    <div class="invisible absolute left-0 top-full z-50 pt-2 opacity-0 transition-all duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                        <div class="w-[16.5rem] overflow-hidden rounded-2xl border border-line bg-paper shadow-[0_24px_60px_-24px_rgba(16,19,25,0.35)]">
                            <?php if ($kids): ?>
                            <div class="p-2">
                                <p class="kicker px-2.5 pb-2 pt-1.5 text-muted">Sub-topics</p>
                                <?php foreach ($kids as $ch): ?>
                                <a href="<?= url('/category/' . $ch['slug']) ?>"
                                    class="flex items-center justify-between gap-4 rounded-xl px-2.5 py-2 text-sm font-semibold text-ink-soft transition hover:bg-surface hover:text-accent">
                                    <span><?= e($ch['name']) ?></span>
                                    <?php if ((int) $ch['post_count'] > 0): ?>
                                    <span class="rounded-full bg-surface px-2 py-0.5 text-[0.62rem] font-extrabold text-muted"><?= (int) $ch['post_count'] ?></span>
                                    <?php endif; ?>
                                </a>
                                <?php foreach ($ch['children'] ?? [] as $gch): ?>
                                <a href="<?= url('/category/' . $gch['slug']) ?>"
                                    class="flex items-center gap-2 py-1.5 pl-7 pr-2.5 text-[0.78rem] font-semibold text-muted transition hover:text-accent">
                                    <?= e($gch['name']) ?>
                                </a>
                                <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($navLatest): ?>
                            <div class="border-t border-line-soft p-2">
                                <p class="kicker px-2.5 pb-2 pt-1.5 text-muted">Latest</p>
                                <?php foreach ($navLatest as $lp): ?>
                                <a href="<?= url('/post/' . $lp['slug']) ?>" title="<?= e($lp['title']) ?>"
                                    class="block truncate rounded-xl px-2.5 py-2 text-[0.82rem] font-semibold leading-snug text-ink-soft transition hover:bg-surface hover:text-accent">
                                    <?= e($lp['title']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <a href="<?= url($catPath) ?>"
                                class="flex items-center justify-between border-t border-line-soft bg-surface px-4 py-3 text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-ink transition hover:text-accent">
                                View all <?= e($cat['name']) ?>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($_moreCats): ?>
                <div class="group relative shrink-0">
                    <button type="button" aria-haspopup="true"
                        class="relative flex items-center gap-1.5 rounded-lg px-2 py-2 text-[0.78rem] font-bold text-ink-soft transition hover:text-accent xl:px-3 xl:text-[0.84rem]">
                        More
                        <svg class="h-3 w-3 opacity-40 transition-transform duration-200 group-hover:rotate-180"
                            fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div class="invisible absolute right-0 top-full z-50 pt-2 opacity-0 transition-all duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                        <div class="w-60 overflow-hidden rounded-2xl border border-line bg-paper py-2 shadow-[0_24px_60px_-24px_rgba(16,19,25,0.35)]">
                            <?php foreach ($_moreCats as $mc): ?>
                            <?php $mcActive = in_array((int) $mc['id'], $_activeCatIds, true); ?>
                            <a href="<?= url('/category/' . $mc['slug']) ?>"
                                class="flex items-center justify-between gap-4 px-4 py-2 text-sm font-semibold transition hover:bg-surface hover:text-accent <?= $mcActive ? 'text-accent' : 'text-ink-soft' ?>">
                                <span><?= e($mc['name']) ?></span>
                                <?php if ((int) $mc['post_count'] > 0): ?>
                                <span class="text-[0.62rem] font-extrabold text-muted"><?= (int) $mc['post_count'] ?></span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                            <div class="mt-1 border-t border-line-soft pt-1">
                                <a href="<?= url('/categories') ?>"
                                    class="block px-4 py-2 text-sm font-semibold text-ink-soft transition hover:bg-surface hover:text-accent">All categories</a>
                                <a href="<?= url('/blog') ?>"
                                    class="block px-4 py-2 text-sm font-semibold text-ink-soft transition hover:bg-surface hover:text-accent">All articles</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php $_blogActive = str_starts_with($_path, '/blog') || $_current === 'blog.php' || $_current === 'search.php'; ?>
                <a href="<?= url('/blog') ?>"
                    class="relative hidden shrink-0 rounded-lg px-2 py-2 text-[0.78rem] font-bold transition xl:block xl:px-3 xl:text-[0.84rem] <?= $_blogActive ? 'text-accent' : 'text-ink-soft hover:text-accent' ?>">
                    Articles
                    <?php if ($_blogActive): ?><span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded bg-accent"></span><?php endif; ?>
                </a>

                <?php $_aboutActive = str_starts_with($_path, '/about') || $_current === 'about.php'; ?>
                <a href="<?= url('/about') ?>"
                    class="relative shrink-0 rounded-lg px-3 py-2 text-[0.84rem] font-bold transition <?= $_aboutActive ? 'text-accent' : 'text-ink-soft hover:text-accent' ?>">
                    About
                    <?php if ($_aboutActive): ?><span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded bg-accent"></span><?php endif; ?>
                </a>

                <?php $_contactActive = str_starts_with($_path, '/contact') || $_current === 'contact.php'; ?>
                <a href="<?= url('/contact') ?>"
                    class="relative shrink-0 rounded-lg px-3 py-2 text-[0.84rem] font-bold transition <?= $_contactActive ? 'text-accent' : 'text-ink-soft hover:text-accent' ?>">
                    Contact
                    <?php if ($_contactActive): ?><span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded bg-accent"></span><?php endif; ?>
                </a>
            </nav>

            <!-- ── Search + Hamburger toggle ── -->
            <div class="ml-auto flex items-center gap-2.5">
                <form action="<?= url('/search') ?>" method="get" role="search" class="relative hidden sm:block" data-nav-search>
                    <label for="nav-search-input" class="sr-only">Search articles</label>
                    <input id="nav-search-input" type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>"
                        placeholder="Search…" autocomplete="off" aria-autocomplete="list" aria-expanded="false"
                        aria-controls="nav-search-suggest"
                        class="w-36 rounded-full border border-line bg-surface py-2 pl-4 pr-9 text-sm outline-none transition focus:w-52 focus:border-primary lg:w-40 lg:focus:w-60 xl:w-48 xl:focus:w-72">
                    <button type="submit" aria-label="Search"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted transition hover:text-ink">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.34-4.34M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
                        </svg>
                    </button>
                    <div id="nav-search-suggest" data-nav-suggest hidden
                        class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-line bg-paper shadow-[0_24px_60px_-24px_rgba(16,19,25,0.35)]">
                    </div>
                </form>

                <!-- ══ PROFESSIONAL ANIMATED HAMBURGER BUTTON ══ -->
                <button type="button"
                        id="mobile-nav-toggle"
                        data-nav-toggle
                        x-ref="navToggle"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-controls="mobile-nav-drawer"
                        aria-label="Toggle menu"
                        class="relative z-[60] flex h-10 w-10 flex-col items-center justify-center gap-[5px] rounded-xl border border-line bg-surface transition-all duration-300 hover:border-accent hover:bg-accent/5 lg:hidden"
                        :class="open ? 'border-accent bg-accent/8' : ''">
                    <span class="pointer-events-none block h-[2px] w-5 origin-center rounded-full bg-ink transition-all duration-300"
                          :class="open ? 'translate-y-[7px] rotate-45 !bg-accent' : ''"></span>
                    <span class="pointer-events-none block h-[2px] w-5 rounded-full bg-ink transition-all duration-300"
                          :class="open ? 'scale-x-0 opacity-0' : ''"></span>
                    <span class="pointer-events-none block h-[2px] w-5 origin-center rounded-full bg-ink transition-all duration-300"
                          :class="open ? '-translate-y-[7px] -rotate-45 !bg-accent' : ''"></span>
                </button>
            </div>
        </div>

    </header>

    <!-- ══════════════════════════════════════════════════════
         MOBILE DRAWER — slide from right, full-height panel
         ══════════════════════════════════════════════════════ -->

    <!-- Blurred backdrop -->
    <div x-show="open"
         x-cloak
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 z-[55] bg-ink/40 backdrop-blur-sm lg:hidden"
         aria-hidden="true"></div>

    <!-- Panel -->
    <div id="mobile-nav-drawer"
         data-nav-drawer
         x-show="open"
         x-cloak
         x-transition:enter="transition duration-[350ms] ease-[cubic-bezier(0.22,1,0.36,1)]"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition duration-[250ms] ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[58] flex w-full max-w-sm flex-col bg-paper shadow-[-20px_0_60px_-12px_rgba(16,19,25,0.25)] lg:hidden"
         role="dialog"
         aria-modal="true"
         aria-label="Navigation menu">

        <!-- Panel header: logo + close -->
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <?php if ($_logoSrc): ?>
            <a href="<?= url('/') ?>" @click="open = false" aria-label="<?= e(setting('site_name', SITE_NAME)) ?> — home">
                <img src="<?= e($_logoSrc) ?>" alt="<?= e(setting('site_name', SITE_NAME)) ?>"
                     class="h-8 w-auto" width="1033" height="643" loading="lazy">
            </a>
            <?php else: ?>
            <a href="<?= url('/') ?>" @click="open = false"
               class="flex items-baseline gap-1.5"
               aria-label="<?= e(setting('site_name', SITE_NAME)) ?> — home">
                <span class="font-display text-lg font-bold leading-none tracking-tight text-ink"><?= e(setting('site_name', SITE_NAME)) ?></span>
                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
            </a>
            <?php endif; ?>
            <button type="button" x-ref="navClose" @click="open = false" aria-label="Close menu"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-line text-muted transition hover:border-accent hover:bg-accent/5 hover:text-accent">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Search inside drawer -->
        <div class="border-b border-line px-5 py-3">
            <form action="<?= url('/search') ?>" method="get" role="search" class="relative" data-nav-search>
                <label for="mobile-search-input" class="sr-only">Search articles</label>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted"
                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.34-4.34M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
                <input id="mobile-search-input" type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>"
                       placeholder="Search articles…" autocomplete="off"
                       aria-autocomplete="list" aria-expanded="false" aria-controls="mobile-search-suggest"
                       class="w-full rounded-xl border border-line bg-surface py-2.5 pl-10 pr-10 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15">
                <button type="submit" aria-label="Search"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted transition hover:text-ink">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </button>
                <div id="mobile-search-suggest" data-nav-suggest hidden
                     class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-line bg-paper shadow-[0_24px_60px_-24px_rgba(16,19,25,0.35)]"></div>
            </form>
        </div>

        <!-- Scrollable nav links -->
        <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Mobile navigation">

            <?php
            $mActiveHome    = $_path === '/';
            $mActiveBlog    = str_starts_with($_path, '/blog') || $_current === 'blog.php' || $_current === 'search.php';
            $mActiveAbout   = str_starts_with($_path, '/about');
            $mActiveContact = str_starts_with($_path, '/contact');
            ?>

            <!-- Home -->
            <a href="<?= url('/') ?>" @click="open = false"
               class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition hover:bg-surface <?= $mActiveHome ? 'bg-accent/8 text-accent' : 'text-ink-soft' ?>">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?= $mActiveHome ? 'bg-accent text-white' : 'bg-surface text-muted' ?>">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 22V12h6v10"/>
                    </svg>
                </span>
                Home
                <?php if ($mActiveHome): ?><span class="ml-auto h-1.5 w-1.5 rounded-full bg-accent"></span><?php endif; ?>
            </a>

            <!-- All Articles -->
            <a href="<?= url('/blog') ?>" @click="open = false"
               class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition hover:bg-surface <?= $mActiveBlog ? 'bg-accent/8 text-accent' : 'text-ink-soft' ?>">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?= $mActiveBlog ? 'bg-accent text-white' : 'bg-surface text-muted' ?>">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h6M7 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8l-5-4H7z"/>
                    </svg>
                </span>
                All Articles
                <?php if ($mActiveBlog): ?><span class="ml-auto h-1.5 w-1.5 rounded-full bg-accent"></span><?php endif; ?>
            </a>

            <!-- Categories heading -->
            <?php if ($_navTree): ?>
            <div class="mb-2 mt-4 px-3">
                <p class="kicker text-muted">Categories</p>
            </div>

            <?php foreach ($_navTree as $cat):
                $catActive = in_array((int) $cat['id'], $_activeCatIds, true);
                $hasKids   = !empty($cat['children']);
            ?>
            <?php if ($hasKids): ?>
            <div x-data="{ sub: false }" class="mb-0.5">
                <div class="flex items-center gap-1 rounded-xl <?= $catActive ? 'bg-accent/8' : '' ?> transition hover:bg-surface">
                    <a href="<?= url('/category/' . $cat['slug']) ?>" @click="open = false"
                       class="flex flex-1 items-center gap-3 px-3 py-3 text-sm font-semibold <?= $catActive ? 'text-accent' : 'text-ink-soft' ?>">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?= $catActive ? 'bg-accent text-white' : 'bg-surface text-muted' ?> text-[0.62rem] font-extrabold uppercase tracking-wider">
                            <?= mb_substr(e($cat['name']), 0, 2) ?>
                        </span>
                        <?= e($cat['name']) ?>
                    </a>
                    <button type="button" @click="sub = !sub" :aria-expanded="sub"
                            aria-label="Toggle subcategories"
                            class="mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-muted transition hover:bg-line hover:text-ink">
                        <svg class="h-3.5 w-3.5 transition-transform duration-300"
                             :class="sub ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                </div>
                <div x-show="sub" x-cloak data-sub-menu
                     x-transition:enter="transition duration-200 ease-out"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition duration-150 ease-in"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="ml-5 mt-0.5 border-l-2 border-line pb-1 pl-3">
                    <?php foreach ($cat['children'] as $ch):
                        $chActive = in_array((int) $ch['id'], $_activeCatIds, true);
                    ?>
                    <a href="<?= url('/category/' . $ch['slug']) ?>" @click="open = false"
                       class="mb-0.5 flex items-center justify-between rounded-lg px-3 py-2 text-[0.82rem] font-semibold transition hover:bg-surface hover:text-accent <?= $chActive ? 'text-accent' : 'text-muted' ?>">
                        <span><?= e($ch['name']) ?></span>
                        <?php if ((int)($ch['post_count'] ?? 0) > 0): ?>
                        <span class="rounded-full bg-line px-1.5 py-0.5 text-[0.6rem] font-extrabold text-muted"><?= (int) $ch['post_count'] ?></span>
                        <?php endif; ?>
                    </a>
                    <?php foreach ($ch['children'] ?? [] as $gch): ?>
                    <a href="<?= url('/category/' . $gch['slug']) ?>" @click="open = false"
                       class="mb-0.5 block rounded-lg px-3 py-1.5 pl-5 text-xs font-semibold text-muted/70 transition hover:text-accent">
                        <?= e($gch['name']) ?>
                    </a>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= url('/category/' . $cat['slug']) ?>" @click="open = false"
               class="mb-0.5 flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition hover:bg-surface <?= $catActive ? 'bg-accent/8 text-accent' : 'text-ink-soft' ?>">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?= $catActive ? 'bg-accent text-white' : 'bg-surface text-muted' ?> text-[0.62rem] font-extrabold uppercase tracking-wider">
                    <?= mb_substr(e($cat['name']), 0, 2) ?>
                </span>
                <?= e($cat['name']) ?>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php endif; ?>

            <div class="my-4 border-t border-line"></div>

            <!-- About -->
            <a href="<?= url('/about') ?>" @click="open = false"
               class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition hover:bg-surface <?= $mActiveAbout ? 'bg-accent/8 text-accent' : 'text-ink-soft' ?>">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?= $mActiveAbout ? 'bg-accent text-white' : 'bg-surface text-muted' ?>">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <circle cx="12" cy="8" r="4"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                </span>
                About Us
                <?php if ($mActiveAbout): ?><span class="ml-auto h-1.5 w-1.5 rounded-full bg-accent"></span><?php endif; ?>
            </a>

            <!-- Contact -->
            <a href="<?= url('/contact') ?>" @click="open = false"
               class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition hover:bg-surface <?= $mActiveContact ? 'bg-accent/8 text-accent' : 'text-ink-soft' ?>">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?= $mActiveContact ? 'bg-accent text-white' : 'bg-surface text-muted' ?>">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                </span>
                Contact
                <?php if ($mActiveContact): ?><span class="ml-auto h-1.5 w-1.5 rounded-full bg-accent"></span><?php endif; ?>
            </a>
        </nav>

        <!-- Panel footer CTA -->
        <div class="border-t border-line px-5 py-4">
            <a href="<?= url('/blog') ?>" @click="open = false"
               class="flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 py-3 text-sm font-bold text-white transition hover:bg-accent/85 active:scale-[0.98]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
                Explore All Articles
            </a>
            <p class="mt-3 text-center text-[0.7rem] text-muted">
                &copy; <?= date('Y') ?> <?= e(setting('site_name', SITE_NAME)) ?>
            </p>
        </div>
    </div>

    <?php if ($flash = flash_get()): ?>
    <div class="mx-auto max-w-[96rem] px-6 pt-6">
        <div class="rounded-xl border <?= $flash['type'] === 'error' ? 'border-accent bg-accent-soft' : 'border-primary/30 bg-primary/5' ?> px-5 py-3.5 text-sm font-semibold">
            <?= e($flash['message']) ?>
        </div>
    </div>
    <?php endif; ?>

    <main id='main-content' tabindex='-1'>
