<?php
/**
 * auth/login.php
 * User Login — Task 2 (basic), Task 4 (brute-force protection)
 *
 * Security features:
 *  - CSRF token verification
 *  - password_verify() (timing-safe)
 *  - Account lockout after 5 failed attempts (Task 4)
 *  - Session regeneration on login (prevents session fixation)
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    redirect('/apexplanet-internship/posts/index.php');
}

$errors   = [];
$formData = [];

// ════════════════════════════════════════════
// HANDLE POST
// ════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $identifier = sanitise($_POST['identifier'] ?? '');  // username OR email
    $password   = $_POST['password']             ?? '';

    $formData = ['identifier' => $identifier];

    // Basic presence validation
    if ($identifier === '') { $errors[] = 'Username or email is required.'; }
    if ($password   === '') { $errors[] = 'Password is required.'; }

    if (empty($errors)) {
        $pdo = getDBConnection();

        // Fetch user by username OR email
        $stmt = $pdo->prepare(
            'SELECT id, username, email, password, role, is_active,
                    failed_login_attempts, locked_until
             FROM users
             WHERE username = :identifier OR email = :identifier_email
             LIMIT 1'
        );
        $stmt->execute([
            ':identifier'       => $identifier,
            ':identifier_email' => $identifier,
        ]);
        $user = $stmt->fetch();

        if (!$user) {
            // Generic message — do NOT say "username not found" (information leak)
            $errors[] = 'Invalid credentials. Please try again.';

        } elseif (!$user['is_active']) {
            $errors[] = 'Your account has been deactivated. Contact admin.';

        } elseif ($user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
            // Account temporarily locked
            $remaining = (new DateTime($user['locked_until']))->diff(new DateTime());
            $errors[] = "Account locked due to too many failed attempts. "
                      . "Try again in {$remaining->i} minute(s) {$remaining->s} second(s).";

        } elseif (!password_verify($password, $user['password'])) {
            // Wrong password — increment failure counter
            $newCount = $user['failed_login_attempts'] + 1;
            $lockUntil = null;

            if ($newCount >= 5) {
                // Lock for 15 minutes
                $lockUntil = (new DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');
                $errors[]  = 'Too many failed attempts. Account locked for 15 minutes.';
            } else {
                $remaining = 5 - $newCount;
                $errors[]  = "Invalid credentials. $remaining attempt(s) remaining.";
            }

            $pdo->prepare(
                'UPDATE users
                 SET failed_login_attempts = :cnt, locked_until = :lu
                 WHERE id = :id'
            )->execute([':cnt' => $newCount, ':lu' => $lockUntil, ':id' => $user['id']]);

            logActivity($pdo, 'login_fail', "identifier: $identifier");

        } else {
            // ── SUCCESS ─────────────────────────────────────────────
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Store essential user info in session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];

            // Reset failed attempts
            $pdo->prepare(
                'UPDATE users
                 SET failed_login_attempts = 0, locked_until = NULL
                 WHERE id = :id'
            )->execute([':id' => $user['id']]);

            logActivity($pdo, 'login_success', "user: {$user['username']}");

            setFlash('success', 'Welcome back, ' . e($user['username']) . '!');
            redirect('/apexplanet-internship/posts/index.php');
        }
    }
}

// ════════════════════════════════════════════
// RENDER
// ════════════════════════════════════════════
$pageTitle = 'Login | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="auth-card animate-fade-in">

      <div class="auth-header">
        <div class="auth-icon"><i class="bi bi-box-arrow-in-right"></i></div>
        <h2 class="fw-bold">Welcome Back</h2>
        <p class="text-muted small">Sign in to your account</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-shield-exclamation me-2"></i>
          <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" class="needs-validation" novalidate>
        <?= csrfField() ?>

        <!-- Username or Email -->
        <div class="mb-3">
          <label class="form-label" for="identifier">
            <i class="bi bi-person me-1"></i>Username or Email
          </label>
          <input
            type="text" id="identifier" name="identifier"
            class="form-control"
            value="<?= e($formData['identifier'] ?? '') ?>"
            placeholder="Enter username or email"
            required autocomplete="username"
            autofocus
          >
          <div class="invalid-feedback">This field is required.</div>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="form-label" for="password">
            <i class="bi bi-lock me-1"></i>Password
          </label>
          <div class="input-group">
            <input
              type="password" id="password" name="password"
              class="form-control"
              placeholder="Enter your password"
              required autocomplete="current-password"
            >
            <button class="btn btn-outline-secondary" type="button"
                    onclick="togglePassword('password', this)">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <div class="invalid-feedback">Password is required.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>

        <p class="text-center text-muted mb-0">
          No account?
          <a href="/apexplanet-internship/auth/register.php" class="fw-semibold">Create one free</a>
        </p>
      </form>

      <!-- Demo credentials box (remove in production!) -->
      <div class="alert alert-info mt-4 mb-0 small">
        <strong><i class="bi bi-info-circle me-1"></i>Demo accounts:</strong><br>
        Admin: <code>admin</code> / <code>password</code><br>
        Editor: <code>editor</code> / <code>password</code><br>
        User: <code>john_doe</code> / <code>password</code>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
