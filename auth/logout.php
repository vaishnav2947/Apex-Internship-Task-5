<?php
/**
 * auth/logout.php
 * Secure logout — destroys session completely.
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    $pdo = getDBConnection();
    logActivity($pdo, 'logout', 'user: ' . ($_SESSION['username'] ?? ''));
}

// 1. Clear all session variables
$_SESSION = [];

// 2. Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Redirect to login
header('Location: /apexplanet-internship/auth/login.php');
exit;
