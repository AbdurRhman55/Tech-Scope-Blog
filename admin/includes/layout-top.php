<?php
/**
* ---------------------------------------------------------------------
*  Admin layout — header + sidebar
*  Requires: auth.php included, $admin_user set by require_admin()
*  Vars: $admin_title, $admin_page ( active nav key )
* ---------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../auth.php';

$admin_user = require_admin();
$admin_title = $admin_title ?? 'Dashboard';
$admin_page  = $admin_page ?? '';

$nav = [
    'dashboard' => [ 'label' => 'Dashboard',   'href' => admin_url( 'index.php' ),       'icon' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z' ],
    'posts'     => [ 'label' => 'Posts',       'href' => admin_url( 'posts.php' ),       'icon' => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z' ],
    'new-post'  => [ 'label' => 'Add Post',    'href' => admin_url( 'post-edit.php' ),   'icon' => 'M12 4.5v15m7.5-7.5h-15' ],
    'categories'=> [ 'label' => 'Categories',  'href' => admin_url( 'categories.php' ), 'icon' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z' ],
    'tags'      => [ 'label' => 'Tags',        'href' => admin_url( 'tags.php' ),       'icon' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z' ],
    'users'     => [ 'label' => 'Authors',     'href' => admin_url( 'users.php' ),      'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z' ],
    'comments'  => [ 'label' => 'Comments',    'href' => admin_url( 'comments.php' ),   'icon' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z' ],
    'media'     => [ 'label' => 'Media',       'href' => admin_url( 'media.php' ),      'icon' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z' ],
    'messages'  => [ 'label' => 'Messages',    'href' => admin_url( 'messages.php' ),   'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75' ],
    'seo'       => [ 'label' => 'SEO',         'href' => admin_url( 'seo.php' ),        'icon' => 'M21 21m-5.197-5.197a7.5 7.5 0 1 0-10.606-10.606 7.5 7.5 0 0 0 10.606 10.606Zm-2.524-2.524a3.75 3.75 0 0 0-5.303 0' ],
    'settings'  => [ 'label' => 'Settings',    'href' => admin_url( 'settings.php' ),   'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z' ],
    'profile'   => [ 'label' => 'My Profile',  'href' => admin_url( 'profile.php' ),    'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z' ],
];

// Count pending badges
$badge = [
    'comments' => ( int ) db()->query( "SELECT COUNT(*) FROM comments WHERE status = 'pending'" )->fetchColumn(),
    'messages' => ( int ) db()->query( 'SELECT COUNT(*) FROM messages WHERE is_read = 0' )->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang='en' class='admin-root' x-data='{ side: false }'>

<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='robots' content='noindex, nofollow'>
    <title>
        <?= e( $admin_title ) ?> | Admin — <?= e( setting( 'site_name' , SITE_NAME ) ) ?>
    </title>
    <link rel='icon' href="<?= e(function_exists('favicon_url') ? favicon_url() : url('/assets/img/favicon.svg')) ?>">
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link
        href='https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap'
        rel='stylesheet'>
    <link rel='stylesheet'
        href="<?= url('/assets/css/app.css') ?>?v=<?= e(function_exists('asset_version') ? asset_version() : (string) @filemtime(ROOT_PATH . '/assets/css/app.css')) ?>">
</head>

<body class='bg-paper font-sans antialiased text-ink'>

    <div class='flex min-h-screen'>

        <!-- ===  ===  ===  ===  ===  == SIDEBAR ===  ===  ===  ===  ===  == -->
        <aside id='admin-sidebar'
            class='fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-white/10 bg-ink text-paper transition-transform duration-300 lg:translate-x-0'
            :class="side ? 'translate-x-0' : ''">

            <!-- Brand -->
            <div class='brand-box flex items-center gap-2 border-b border-white/10 px-6 py-5'>
                <?php $_sLogo = function_exists( 'site_logo_src' ) ? site_logo_src() : null;
?>
                <?php if ( $_sLogo ): ?>
                <a href="<?= url('/') ?>" class='flex shrink-0 items-center rounded-lg bg-white px-2 py-1.5 shadow-sm'
                    aria-label="<?= e(setting('site_name', SITE_NAME)) ?> — home">
                    <img src="<?= e($_sLogo) ?>" alt="<?= e(setting('site_name', SITE_NAME)) ?>" class='h-10 w-auto'
                        width='1033' height='643'>
                </a>
                <?php else: ?>
                <a href="<?= url('/') ?>" class='font-display text-xl font-bold tracking-tight'>
                    <?= e( setting( 'site_name' , SITE_NAME ) ) ?>
                </a>
                <?php endif;
?>
                <span
                    class='rounded bg-accent px-1.5 py-0.5 text-[0.6rem] font-extrabold uppercase tracking-wider'>Admin</span>
                <button type='button' @click='side = false' data-admin-side-close
                    class='ml-auto text-paper/50 hover:text-paper lg:hidden' aria-label='Close'>&times;
                </button>
            </div>

            <!-- Nav -->
            <nav class='no-scrollbar flex-1 overflow-y-auto px-3 py-4'>
                <?php foreach ( $nav as $key => $item ):
if ( $key === 'users' && !in_array( $admin_user[ 'role' ], [ 'admin' ], true ) ) continue;
$active = ( $admin_page === $key );
$hasBadge = isset( $badge[ $key ] ) && $badge[ $key ] > 0;
?>
                <a href="<?= $item['href'] ?>" class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition sm:py-2.5
                  <?= $active ? 'bg-accent text-white' : 'text-paper/65 hover:bg-white/5 hover:text-paper' ?>">
                    <svg class='h-5 w-5 shrink-0' fill='none' viewBox='0 0 24 24' stroke-width='1.7'
                        stroke='currentColor'>
                        <path stroke-linecap='round' stroke-linejoin='round' d="<?= $item['icon'] ?>" />
                    </svg>
                    <span>
                        <?= e( $item[ 'label' ] ) ?>
                    </span>
                    <?php if ( $hasBadge ): ?>
                    <span class='ml-auto rounded-full bg-accent px-2 py-0.5 text-[0.65rem] font-extrabold text-white'>
                        <?= $badge[ $key ] ?>
                    </span>
                    <?php endif;
?>
                </a>
                <?php endforeach;
?>
            </nav>

            <!-- User -->
            <div class='border-t border-white/10 px-4 py-4'>
                <div class='flex items-center gap-3'>
                    <?php $_uAvatar = !empty( $admin_user[ 'avatar' ] ) && function_exists( '_avatar_url' ) ? _avatar_url( $admin_user[ 'avatar' ] ) : null;
?>
                    <?php if ( $_uAvatar ): ?>
                    <img src="<?= e($_uAvatar) ?>" alt="<?= e($admin_user['name']) ?>"
                        class='h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-accent/50'>
                    <?php else: ?>
                    <span class='flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent font-bold'>
                        <?= e( strtoupper( mb_substr( $admin_user[ 'name' ], 0, 1 ) ) ) ?>
                    </span>
                    <?php endif;
?>
                    <div class='min-w-0 flex-1'>
                        <p class='truncate text-sm font-bold'>
                            <?= e( $admin_user[ 'name' ] ) ?>
                        </p>
                        <p class='truncate text-xs capitalize text-paper/50'>
                            <?= e( $admin_user[ 'role' ] ) ?>
                        </p>
                    </div>
                    <form method='post' action="<?= admin_url('logout.php') ?>">
                        <?= csrf_field() ?>
                            <button title='Logout'
                                class='rounded-lg border border-white/15 p-2 text-paper/60 transition hover:border-accent hover:text-accent'>
                                <svg class='h-4 w-4' fill='none' viewBox='0 0 24 24' stroke-width='1.8'
                                    stroke='currentColor'>
                                    <path stroke-linecap='round' stroke-linejoin='round'
                                        d='M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-7.5A2.25 2.25 0 0 0 3.75 5.25v13.5A2.25 2.25 0 0 0 6 21h7.5a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9' />
                                </svg>
                            </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Overlay ( mobile ) -->
        <div x-show='side' x-cloak @click='side = false' data-admin-side-overlay
            class='fixed inset-0 z-40 bg-black/50 lg:hidden'></div>

        <!-- ===  ===  ===  ===  ===  == CONTENT ===  ===  ===  ===  ===  == -->
        <div class='flex min-w-0 flex-1 flex-col lg:pl-64'>

            <!-- Topbar -->
            <header
                class='sticky top-0 z-30 flex items-center gap-3 border-b border-line bg-paper/85 px-4 py-3 backdrop-blur-lg sm:gap-4 sm:px-8 sm:py-4'>
                <button type='button' @click='side = true' data-admin-side-open
                    class='rounded-lg border border-line p-2 lg:hidden' aria-label='Menu'>
                    <svg class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor'>
                        <path stroke-linecap='round' d='M4 7h16M4 12h16M4 17h16' />
                    </svg>
                </button>

                <h1 class='font-display text-lg font-semibold sm:text-xl'>
                    <?= e( $admin_title ) ?>
                </h1>

                <div class='ml-auto flex items-center gap-2.5'>
                    <?php $_tAvatar = !empty( $admin_user[ 'avatar' ] ) && function_exists( '_avatar_url' ) ? _avatar_url( $admin_user[ 'avatar' ] ) : null;
?>
                    <a href="<?= admin_url('profile.php') ?>" title='My profile' aria-label='My profile'
                        class='shrink-0 rounded-full transition hover:ring-2 hover:ring-accent'>
                        <?php if ( $_tAvatar ): ?>
                        <img src="<?= e($_tAvatar) ?>" alt="<?= e($admin_user['name']) ?>"
                            class='h-9 w-9 rounded-full object-cover'>
                        <?php else: ?>
                        <span
                            class='flex h-9 w-9 items-center justify-center rounded-full bg-accent text-sm font-bold text-white'>
                            <?= e( strtoupper( mb_substr( $admin_user[ 'name' ], 0, 1 ) ) ) ?>
                        </span>
                        <?php endif;
?>
                    </a>
                    <a href="<?= url('/') ?>" target='_blank'
                        class='hidden items-center gap-1.5 rounded-lg border border-line bg-surface px-3.5 py-2 text-xs font-bold transition hover:border-ink sm:flex'>
                        View site
                        <svg class='h-3.5 w-3.5' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor'>
                            <path stroke-linecap='round' stroke-linejoin='round'
                                d='M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25' />
                        </svg>
                    </a>
                    <a href="<?= admin_url('post-edit.php') ?>"
                        class='flex items-center gap-1.5 rounded-lg bg-ink px-3 py-1.5 text-xs font-bold text-paper transition hover:bg-accent sm:px-4 sm:py-2'>
                        <svg class='h-4 w-4' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor'>
                            <path stroke-linecap='round' stroke-linejoin='round' d='M12 4.5v15m7.5-7.5h-15' />
                        </svg>
                        New post
                    </a>
                </div>
            </header>

            <!-- Flash -->
            <?php if ( $flash = flash_get() ): ?>
            <div class='px-4 pt-4 sm:px-8 sm:pt-5'>
                <div data-flash
                    class="rounded-xl border <?= $flash['type'] === 'error' ? 'border-accent bg-accent-soft text-accent' : 'border-primary/30 bg-primary/5 text-primary' ?> px-5 py-3.5 text-sm font-semibold">
                    <?= e( $flash[ 'message' ] ) ?>
                </div>
            </div>
            <?php endif;
?>

            <main class='flex-1 px-4 py-5 sm:px-8 sm:py-7'>