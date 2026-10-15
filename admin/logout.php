<?php
/**
 *  Admin — logout
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $_SESSION = [];
    session_destroy();
    session_start();
    flash_set('success', 'You have been logged out.');
}
header('Location: ' . admin_url('login.php'));
exit;
