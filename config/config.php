<?php
/**
* ---------------------------------------------------------------------
*  Global configuration
*  Edit DB credentials below before importing database.sql
* ---------------------------------------------------------------------
*/

declare( strict_types = 1 );

/* ---------------------------------------------------------------
* Database credentials
* Local development uses Laragon defaults. Live credentials belong in
* the ignored config/database.hosting.php file on the hosting server.
* --------------------------------------------------------------- */
$dbConfig = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'pro_blog',
    'user' => 'root',
    'pass' => '',
];
$requestHost = strtolower( ( string ) ( $_SERVER[ 'HTTP_HOST' ] ?? 'localhost' ) );
$requestHost = parse_url( 'http://' . $requestHost, PHP_URL_HOST ) ?: 'localhost';
$isLocalRequest = in_array( $requestHost, [ 'localhost', '127.0.0.1', '::1' ], true );
$hostingDbConfig = __DIR__ . '/database.hosting.php';
if ( !$isLocalRequest && is_file( $hostingDbConfig ) ) {
    $hostingDbSettings = require $hostingDbConfig;
    if ( is_array( $hostingDbSettings ) ) {
        $dbConfig = array_replace( $dbConfig, $hostingDbSettings );
    }
}

define( 'DB_HOST', ( string ) $dbConfig[ 'host' ] );
define( 'DB_PORT', ( string ) $dbConfig[ 'port' ] );
define( 'DB_NAME', ( string ) $dbConfig[ 'name' ] );
define( 'DB_USER', ( string ) $dbConfig[ 'user' ] );
define( 'DB_PASS', ( string ) $dbConfig[ 'pass' ] );
define( 'DB_CHARSET', 'utf8mb4' );

/* ---------------------------------------------------------------
* Site
* --------------------------------------------------------------- */
if ( $isLocalRequest ) {
    define( 'SITE_URL', 'http://localhost/Blog-website' );
} else {
    // Derive the canonical site URL from the live request so re-deploys
    // (which restore this file from the repo) never leak localhost URLs
    // into sitemaps, canonicals or og:url on the production server.
    // The Hostinger edge 301s http -> https before PHP runs (and the
    // CDN -> origin leg may be plain HTTP), so production is always https.
    $siteHost = preg_replace( '/^www\./', '', strtolower( (string) ( $_SERVER[ 'HTTP_HOST' ] ?? 'silver-wolf-692661.hostingersite.com' ) ) );
    define( 'SITE_URL', 'https://' . $siteHost );
}
// no trailing slash
define( 'SITE_NAME',  'TechScope' );
define( 'SITE_LOCALE', 'en_US' );

/* ---------------------------------------------------------------
* Analytics
*  - ANALYTICS_TZ : day-bucket timezone for traffic stats so late
*    night traffic lands on the correct local date ( not UTC ).
*  - HASH_SALT    : pepper for anonymised visitor hashing
*    ( sha256( ip + ua + salt ) — raw IPs are never stored ).
* --------------------------------------------------------------- */
define( 'ANALYTICS_TZ', 'Asia/Karachi' );
define( 'HASH_SALT',    'tscope-7b1f4c9d2e6a8f05' );

/* ---------------------------------------------------------------
* Paths
* --------------------------------------------------------------- */
define( 'ROOT_PATH',      dirname( __DIR__ ) );
define( 'UPLOADS_DIR',    ROOT_PATH . '/assets/uploads' );
define( 'UPLOADS_URL',    SITE_URL . '/assets/uploads' );
define( 'MAX_UPLOAD_SIZE', 5 * 1024 * 1024 );
// 5 MB

/* ---------------------------------------------------------------
* Security
* --------------------------------------------------------------- */
define( 'CSRF_KEY',       'change-this-random-string-9f8e7d6c' );
define( 'SESSION_NAME',   'PROBLOG_SESSID' );
define( 'LOGIN_MAX_TRY',  5 );
// attempts per 15 min
define( 'LOGIN_LOCK_MIN', 15 );

/* ---------------------------------------------------------------
* Error display — OFF in production ( set false on live server )
* --------------------------------------------------------------- */
define( 'DEBUG', $isLocalRequest );

/* ---------------------------------------------------------------
* Bootstrap
* --------------------------------------------------------------- */
error_reporting( E_ALL );
ini_set( 'display_errors', DEBUG ? '1' : '0' );
ini_set( 'log_errors', '1' );

date_default_timezone_set( 'UTC' );

/*
 * Sessions are only required for auth, CSRF tokens, flash messages and
 * comment prefills. Starting one on every anonymous GET makes PHP emit
 * "Cache-Control: no-store" + Set-Cookie, which permanently prevents the
 * CDN from caching public pages (slow TTFB for readers and crawlers).
 * So: start a session only when something can actually need it —
 * non-GET requests, visitors who already carry our cookie, the admin
 * area, and the two public pages that render CSRF forms.
 */
// Match on the request path with the app's base path removed so this works
// both at the production domain root and under a local / subdirectory vhost.
$reqPath = (string) ( parse_url( (string) ( $_SERVER[ 'REQUEST_URI' ] ?? '/' ), PHP_URL_PATH ) ?: '/' );
$basePath = rtrim( (string) ( parse_url( SITE_URL, PHP_URL_PATH ) ?: '' ), '/' );
if ( $basePath !== '' && ( $reqPath === $basePath || str_starts_with( $reqPath, $basePath . '/' ) ) ) {
    $reqPath = substr( $reqPath, strlen( $basePath ) ) ?: '/';
}

$wantsSession = php_sapi_name() === 'cli'
    || ( $_SERVER[ 'REQUEST_METHOD' ] ?? 'GET' ) !== 'GET'
    || isset( $_COOKIE[ SESSION_NAME ] )
    || preg_match( '#^/(admin|contact|post)(/|$)#', $reqPath ) === 1;

if ( $wantsSession && session_status() === PHP_SESSION_NONE ) {
    session_set_cookie_params( [
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty( $_SERVER[ 'HTTPS' ] ),
    ] );
    session_name( SESSION_NAME );
    session_start();
}

// Never let the CDN cache HTML. Admin edits (posts, settings, images) must
// appear instantly — a 5-minute edge cache made saved changes look reverted
// on the public pages. Static assets (CSS, images) are served by the
// webserver directly and keep their own caching.
if ( php_sapi_name() !== 'cli' && ! headers_sent() ) {
    header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
}