<?php
/**
 * ---------------------------------------------------------------------
 *  Global configuration
 *  Edit DB credentials below before importing database.sql
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

/* ---------------------------------------------------------------
 * Database credentials (Laragon local defaults)
 * --------------------------------------------------------------- */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'pro_blog');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/* ---------------------------------------------------------------
 * Site
 * --------------------------------------------------------------- */
define('SITE_URL',   'http://localhost/Blog-website');   // no trailing slash
define('SITE_NAME',  'TechScope');
define('SITE_LOCALE','en_US');

/* ---------------------------------------------------------------
 * Analytics
 *  - ANALYTICS_TZ : day-bucket timezone for traffic stats so late
 *    night traffic lands on the correct local date (not UTC).
 *  - HASH_SALT    : pepper for anonymised visitor hashing
 *    (sha256(ip + ua + salt) — raw IPs are never stored).
 * --------------------------------------------------------------- */
define('ANALYTICS_TZ', 'Asia/Karachi');
define('HASH_SALT',    'tscope-7b1f4c9d2e6a8f05');

/* ---------------------------------------------------------------
 * Paths
 * --------------------------------------------------------------- */
define('ROOT_PATH',      dirname(__DIR__));
define('UPLOADS_DIR',    ROOT_PATH . '/assets/uploads');
define('UPLOADS_URL',    SITE_URL . '/assets/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);      // 5 MB

/* ---------------------------------------------------------------
 * Security
 * --------------------------------------------------------------- */
define('CSRF_KEY',       'change-this-random-string-9f8e7d6c');
define('SESSION_NAME',   'PROBLOG_SESSID');
define('LOGIN_MAX_TRY',  5);                      // attempts per 15 min
define('LOGIN_LOCK_MIN', 15);

/* ---------------------------------------------------------------
 * Error display — OFF in production (set false on live server)
 * --------------------------------------------------------------- */
define('DEBUG', true);

/* ---------------------------------------------------------------
 * Bootstrap
 * --------------------------------------------------------------- */
error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_name(SESSION_NAME);
    session_start();
}
