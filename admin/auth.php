<?php
/**
 * ---------------------------------------------------------------------
 *  Admin authentication helpers  (require functions.php first)
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

/** Current logged-in admin or null */
function admin_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    if (empty($_SESSION['admin_id'])) {
        return $user = null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND status = 1 LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

/** Guard — redirect to login if not authenticated */
function require_admin(): array
{
    $user = admin_user();
    if (!$user) {
        header('Location: ' . admin_url('login.php'));
        exit;
    }
    return $user;
}

/** Guard — require one of the given roles */
function require_role(array $roles): array
{
    $user = require_admin();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo '<h1>403 — Forbidden</h1><p>Your role (<strong>' . e($user['role'])
            . '</strong>) does not have access to this area. <a href="' . e(admin_url()) . '">Go back</a>.</p>';
        exit;
    }
    return $user;
}

/** Login attempt throttle: returns seconds to wait, or 0 */
function login_throttle(): int
{
    $tries = $_SESSION['login_tries'] ?? [];
    $tries = array_values(array_filter($tries, fn($t) => $t > time() - LOGIN_LOCK_MIN * 60));
    $_SESSION['login_tries'] = $tries;
    if (count($tries) >= LOGIN_MAX_TRY) {
        return (LOGIN_LOCK_MIN * 60) - (time() - min($tries));
    }
    return 0;
}

function login_record_failure(): void
{
    $_SESSION['login_tries'][] = time();
}

function login_clear_failures(): void
{
    unset($_SESSION['login_tries']);
}
