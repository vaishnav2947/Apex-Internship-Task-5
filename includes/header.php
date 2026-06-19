<?php
/**
 * includes/header.php
 * Shared HTML <head> + navbar rendered on every page.
 *
 * USAGE:
 *   $pageTitle = 'My Page';        // optional — set BEFORE require
 *   require_once __DIR__ . '/../includes/header.php';
 */

// ── Ensure session is running ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Resolve base URL dynamically so links work in any sub-folder ──────
$base = '/apexplanet-internship';   // change if you rename the folder
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ApexPlanet Blog') ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>

<!-- ── NAVBAR ────────────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">

    <!-- Brand -->
    <a class="navbar-brand fw-bold" href="<?= $base ?>/index.php">
      <i class="bi bi-journal-richtext me-2"></i>ApexPlanet Blog
    </a>

    <!-- Mobile toggle -->
    <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Links -->
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="<?= $base ?>/index.php">
            <i class="bi bi-house me-1"></i>Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= $base ?>/posts/index.php">
            <i class="bi bi-file-post me-1"></i>Posts
          </a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Logged-in links -->
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>/posts/create.php">
              <i class="bi bi-plus-circle me-1"></i>New Post
            </a>
          </li>

          <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>/admin/dashboard.php">
              <i class="bi bi-shield-lock me-1"></i>Admin
            </a>
          </li>
          <?php endif; ?>

          <!-- Username dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#"
               data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-1"></i>
              <?= htmlspecialchars($_SESSION['username']) ?>
              <span class="badge bg-<?= $_SESSION['role'] === 'admin' ? 'danger' :
                                       ($_SESSION['role'] === 'editor' ? 'warning text-dark' : 'secondary') ?>
                           ms-1 small">
                <?= ucfirst($_SESSION['role'] ?? 'user') ?>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
              <li>
                <a class="dropdown-item" href="<?= $base ?>/auth/logout.php">
                  <i class="bi bi-box-arrow-right me-2 text-danger"></i>Logout
                </a>
              </li>
            </ul>
          </li>

        <?php else: ?>
          <!-- Guest links -->
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>/auth/login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-nav-outline" href="<?= $base ?>/auth/register.php">
              <i class="bi bi-person-plus me-1"></i>Register
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<!-- ── END NAVBAR ─────────────────────────────────────────────────────── -->

<!-- Flash messages displayed once then cleared -->
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="container mt-3">
    <?php foreach ($_SESSION['flash'] as $type => $msg): ?>
      <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
  </div>
  <?php unset($_SESSION['flash']); // show once only ?>
<?php endif; ?>

<main class="py-4 flex-grow-1">
<div class="container">
