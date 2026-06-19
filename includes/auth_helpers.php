<?php
/**
 * includes/auth_helpers.php
 * Authentication & security utility functions.
 *
 * Include at the top of any PHP file that needs auth checks:
 *   require_once __DIR__ . '/../includes/auth_helpers.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ════════════════════════════════════════════════════════════
// SESSION & REDIRECT HELPERS
// ════════════════════════════════════════════════════════════

/**
 * Redirect to a URL and stop execution.
 * @param string $url
 */
function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

/**
 * Store a one-time flash message in the session.
 * Displayed by header.php then cleared.
 *
 * @param string $type    Bootstrap contextual class: success|danger|warning|info
 * @param string $message Human-readable message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

// ════════════════════════════════════════════════════════════
// AUTHENTICATION CHECKS
// ════════════════════════════════════════════════════════════

/**
 * Return true if the visitor is authenticated.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 *
 * @param string $redirect  Where to go after login (passed as GET param)
 */
function requireLogin(string $redirect = '/apexplanet-internship/auth/login.php'): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access that page.');
        redirect($redirect);
    }
}

/**
 * Require a specific role (or higher) to access a page.
 *
 * Role hierarchy: admin > editor > user
 * Redirects to index if insufficient permissions.
 *
 * @param string|array $roles  Allowed role(s)
 */
function requireRole(string|array $roles): void
{
    requireLogin();  // must be logged in first

    $roles = (array) $roles;   // normalise to array

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        setFlash('danger', 'You do not have permission to access that page.');
        redirect('/apexplanet-internship/index.php');
    }
}

/**
 * Convenience check — is the current user an admin?
 */
function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Convenience check — is the current user an editor or admin?
 */
function isEditor(): bool
{
    return in_array($_SESSION['role'] ?? '', ['admin', 'editor'], true);
}

// ════════════════════════════════════════════════════════════
// CSRF PROTECTION  (Task 4)
// Cross-Site Request Forgery: an attacker tricks a logged-in
// user's browser into submitting a form on a malicious site.
// We prevent this by embedding a unique secret token in every
// form and verifying it on submission.
// ════════════════════════════════════════════════════════════

/**
 * Generate (or reuse) a CSRF token for the current session.
 * @return string  64-character hex token
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes() generates cryptographically secure random bytes
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field — place inside every <form>.
 * Usage: <?= csrfField() ?>
 */
function csrfField(): string
{
    $token = htmlspecialchars(getCsrfToken());
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"$token\">";
}

/**
 * Verify the submitted CSRF token matches the session token.
 * Call at the top of every POST handler.
 * Terminates with 403 if token is invalid.
 */
function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';

    // hash_equals() prevents timing attacks
    if (!hash_equals(getCsrfToken(), $submitted)) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif;padding:2rem;color:#c0392b;">
             403 — Invalid CSRF token. Please go back and try again.</h2>');
    }
}

// ════════════════════════════════════════════════════════════
// INPUT SANITISATION HELPERS
// ════════════════════════════════════════════════════════════

/**
 * Sanitise a string from user input.
 * Removes leading/trailing whitespace; does NOT strip HTML
 * (use htmlspecialchars() when outputting to prevent XSS).
 *
 * @param string $value
 * @return string
 */
function sanitise(string $value): string
{
    return trim($value);
}

/**
 * Escape a value for safe HTML output (prevents XSS).
 * Alias of htmlspecialchars() with sensible defaults.
 *
 * Usage: echo e($untrusted_string);
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ════════════════════════════════════════════════════════════
// ACTIVITY LOGGING  (Task 4)
// ════════════════════════════════════════════════════════════

/**
 * Log a user action to the activity_log table.
 *
 * @param PDO    $pdo
 * @param string $action   e.g. 'login', 'create_post', 'delete_post'
 * @param string $details  optional extra info (JSON or text)
 */
function logActivity(PDO $pdo, string $action, string $details = ''): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO activity_log (user_id, action, ip_address, user_agent, details)
         VALUES (:uid, :action, :ip, :ua, :details)'
    );
    $stmt->execute([
        ':uid'     => $_SESSION['user_id'] ?? null,
        ':action'  => $action,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
        ':details' => $details,
    ]);
}

// ════════════════════════════════════════════════════════════
// PASSWORD POLICY VALIDATOR (Task 4)
// ════════════════════════════════════════════════════════════

/**
 * Validate a password against the site's policy.
 * Returns an array of error strings (empty = valid).
 *
 * Policy: min 8 chars, at least one uppercase, one lowercase,
 *         one digit, one special character.
 *
 * @param string $password
 * @return string[]
 */
function validatePassword(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (!preg_match('/[\W_]/', $password)) {
        $errors[] = 'Password must contain at least one special character (!@#$%^&* etc.).';
    }

    return $errors;
}
