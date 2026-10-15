<?php
/**
 * ---------------------------------------------------------------------
 *  Router for PHP built-in server  (local development)
 *
 *    php -S localhost:8000 router.php
 *
 *  On Apache/cPanel use .htaccess instead — this file is never executed there.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

$url    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$url   = rtrim($url, '/') ?: '/';
$file   = __DIR__ . $url;

/* --- 1. Serve real static files as-is ------------------------------- */
if ($url !== '/' && is_file($file)) {
    // Security: never serve files from sensitive directories
    if (preg_match('#^/(config|tools|includes)/#', $url)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo 'Forbidden';
        return true;
    }
    return false; // let the built-in server handle CSS/JS/images
}

/* --- 2. Pretty route map -------------------------------------------- */
$routes = [
    '/blog'        => 'blog.php',
    '/categories'  => 'categories.php',
    '/about'       => 'about.php',
    '/contact'     => 'contact.php',
    '/privacy'     => 'privacy.php',
    '/terms'       => 'terms.php',
    '/disclaimer'  => 'disclaimer.php',
    '/sitemap'     => 'sitemap.php',
    '/sitemap.xml' => 'sitemap-xml.php',
    '/robots.txt'  => 'robots.php',
];

if (isset($routes[$url])) {
    require __DIR__ . '/' . $routes[$url];
    return true;
}

/* --- 3. Parameterised routes ---------------------------------------- */
if (preg_match('#^/post/([^/]+)$#', $url, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/post.php';
    return true;
}
if (preg_match('#^/category/([^/]+)$#', $url, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/category.php';
    return true;
}
if (preg_match('#^/tag/([^/]+)$#', $url, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/tag.php';
    return true;
}
if (preg_match('#^/author/([^/]+)$#', $url, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/author.php';
    return true;
}
if ($url === '/search') {
    require __DIR__ . '/search.php';
    return true;
}

/* --- 4. Admin panel -------------------------------------------------- */
if (str_starts_with($url, '/admin')) {
    $path = ($url === '/admin' || $url === '/admin/') ? '/admin/index.php' : $url;
    $adminFile = __DIR__ . $path;
    if (!str_ends_with($adminFile, '.php') && is_file($adminFile . '.php')) {
        $adminFile .= '.php';
    }
    if (is_file($adminFile) && str_ends_with($adminFile, '.php')) {
        require $adminFile;
        return true;
    }
}

/* --- 5. Home --------------------------------------------------------- */
if ($url === '/') {
    require __DIR__ . '/index.php';
    return true;
}

/* --- 6. 404 ---------------------------------------------------------- */
http_response_code(404);
require __DIR__ . '/404.php';
return true;
