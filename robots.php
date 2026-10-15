<?php
/**
 *  Dynamic robots.txt — /robots.txt
 *  Uses SITE_URL so it stays correct on any host.
 */

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain; charset=UTF-8');

$lines = [
    'User-agent: *',
    'Allow: /',
    '',
    'Disallow: /admin/',
    'Disallow: /config/',
    'Disallow: /tools/',
    'Disallow: /includes/',
    'Disallow: /assets/uploads/tmp/',
    'Disallow: /search',
    '',
    'Clean-param: q',
    '',
    'Sitemap: ' . rtrim(SITE_URL, '/') . '/sitemap.xml',
];

echo implode("\n", $lines);
