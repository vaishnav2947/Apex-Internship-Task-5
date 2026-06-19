<?php
/**
 * auth/register.php
 * User Registration — Task 2
 *
 * Flow:
 *   GET  → show blank registration form
 *   POST → validate → hash password → insert user → redirect to login
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

// If already logged in, send to posts list
if (isLoggedIn()) {
    redirect('/apexplanet-internship/posts/index.php');
}

$errors   = [];     // validation error messages
$formData = [];     // repopulate form fields after error

// ════════════════════════════════════════════
// HANDLE POST SUBMISSION
// ════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF verification (Task 4 security)
    verifyCsrf();

    // 2. Collect & sanitise input
    $username        = sanitise($_POST['username']        ?? '');
    $email           = sanitise($_POST['email']           ?? '');
    $password        = $_POST['password']                 ?? '';   // NOT sanitised — we hash it
    $passwordConfirm = $_POST['password_confirm']         ?? '';

    $formData = compact('username', 'email');  // save for re-display

    // 3. Server-side validation ─────────────────────────────────────
    // Username
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3-50 characters: letters, numbers, underscores only.';
    }

    // Email
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Password strength (reusable function from auth_helpers.php)
    $pwErrors = validatePassword($password);
    if (!empty($pwErrors)) {
        $errors = array_merge($errors, $pwErrors);
    }

    // Password confirmation
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    // 4. Check uniqueness in database ──────────────────────────────
    if (empty($errors)) {
        $pdo  = getDBConnection();

        // Prepared statement: ? is a placeholder replaced safely by PDO
        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $errors[] = 'Username or email is already registered.';
        }
    }

    // 5. Insert user ───────────────────────────────────────────────
    if (empty($errors)) {
        // password_hash() uses bcrypt — slow by design to resist brute-force
        // PASSWORD_DEFAULT always uses the strongest available algorithm
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password, role)
             VALUES (:username, :email, :password, :role)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hashedPassword,
            ':role'     => 'user',           // all self-registrations = 'user'
        ]);

        // Log the registration
        logActivity($pdo, 'register', "New user: $username");

        setFlash('success', 'Account created! Please log in.');
        redirect('/apexplanet-internship/auth/login.php');
    }
}

// ════════════════════════════════════════════
// RENDER
// ════════════════════════════════════════════
$pageTitle = 'Register | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">

    <div class="auth-card animate-fade-in">
      <!-- Header -->
      <div class="auth-header">
        <div class="auth-icon"><i class="bi bi-person-plus-fill"></i></div>
        <h2 class="fw-bold">Create Account</h2>
        <p class="text-muted small">Join ApexPlanet Blog today</p>
      </div>

      <!-- Validation errors -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Please fix the following:</strong>
          <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Registration form -->
      <form method="POST" action="" class="needs-validation" novalidate>
        <?= csrfField() ?>

        <!-- Username -->
        <div class="mb-3">
          <label class="form-label" for="username">
            <i class="bi bi-person me-1"></i>Username
          </label>
          <input
            type="text" id="username" name="username"
            class="form-control <?= isset($formData['username']) && $formData['username'] === '' ? 'is-invalid' : '' ?>"
            value="<?= e($formData['username'] ?? '') ?>"
            placeholder="e.g. john_doe"
            required minlength="3" maxlength="50"
            pattern="[a-zA-Z0-9_]+"
            autocomplete="username"
          >
          <div class="form-text">3–50 chars. Letters, numbers, underscores only.</div>
          <div class="invalid-feedback">Please enter a valid username.</div>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label class="form-label" for="email">
            <i class="bi bi-envelope me-1"></i>Email Address
          </label>
          <input
            type="email" id="email" name="email"
            class="form-control"
            value="<?= e($formData['email'] ?? '') ?>"
            placeholder="you@example.com"
            required autocomplete="email"
          >
          <div class="invalid-feedback">Please enter a valid email.</div>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label class="form-label" for="password">
            <i class="bi bi-lock me-1"></i>Password
          </label>
          <div class="input-group">
            <input
              type="password" id="password" name="password"
              class="form-control"
              placeholder="Min 8 chars, mixed case, number, symbol"
              required minlength="8"
              autocomplete="new-password"
            >
            <!-- Toggle visibility button -->
            <button class="btn btn-outline-secondary" type="button"
                    onclick="togglePassword('password', this)" title="Show/hide password">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <!-- Password strength bar (driven by main.js) -->
          <div class="mt-2">
            <div class="progress" style="height:6px;">
              <div id="pwStrengthBar" class="progress-bar" style="width:0%"></div>
            </div>
            <small id="pwStrengthLabel" class="text-muted">Enter a password</small>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
          <label class="form-label" for="password_confirm">
            <i class="bi bi-lock-fill me-1"></i>Confirm Password
          </label>
          <input
            type="password" id="password_confirm" name="password_confirm"
            class="form-control"
            placeholder="Repeat your password"
            required autocomplete="new-password"
          >
          <div class="invalid-feedback">Passwords must match.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
          <i class="bi bi-person-check me-2"></i>Create Account
        </button>

        <p class="text-center text-muted mb-0">
          Already have an account?
          <a href="/apexplanet-internship/auth/login.php" class="fw-semibold">Sign in</a>
        </p>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
