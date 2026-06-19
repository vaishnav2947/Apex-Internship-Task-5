<?php
/**
 * index.php — Homepage  (Tasks 1–5 combined)
 */

require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

// Fetch 3 most recent published posts for the hero section
$recent = $pdo->query(
    'SELECT p.*, u.username
     FROM posts p
     JOIN users u ON u.id = p.user_id
     WHERE p.status = "published"
     ORDER BY p.created_at DESC
     LIMIT 3'
)->fetchAll();

$totalPosts = $pdo->query('SELECT COUNT(*) FROM posts WHERE status="published"')->fetchColumn();
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$pageTitle = 'Home | ApexPlanet Blog';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero ─────────────────────────────────────────────────────── -->
<div class="text-center py-5 animate-fade-in">
  <h1 class="display-5 fw-bold mb-3">
    <span style="color:var(--secondary)">ApexPlanet</span> Blog
  </h1>
  <p class="lead text-muted mb-4" style="max-width:560px;margin:auto">
    A full-stack PHP &amp; MySQL blog built during the 45-day
    ApexPlanet Internship — featuring CRUD, auth, search, pagination &amp; security.
  </p>
  <div class="d-flex justify-content-center gap-3 flex-wrap">
    <a href="/apexplanet-internship/posts/index.php" class="btn btn-primary btn-lg px-4">
      <i class="bi bi-journals me-2"></i>Read Posts
    </a>
    <?php if (!isLoggedIn()): ?>
    <a href="/apexplanet-internship/auth/register.php" class="btn btn-outline-primary btn-lg px-4">
      <i class="bi bi-person-plus me-2"></i>Join &amp; Write
    </a>
    <?php else: ?>
    <a href="/apexplanet-internship/posts/create.php" class="btn btn-success btn-lg px-4">
      <i class="bi bi-plus-circle me-2"></i>New Post
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Quick stats -->
<div class="row g-3 justify-content-center mb-5">
  <div class="col-auto">
    <div class="card border-0 shadow-sm px-4 py-3 text-center">
      <span class="fs-3 fw-bold text-primary"><?= $totalPosts ?></span>
      <span class="text-muted small">Published Posts</span>
    </div>
  </div>
  <div class="col-auto">
    <div class="card border-0 shadow-sm px-4 py-3 text-center">
      <span class="fs-3 fw-bold" style="color:var(--accent)"><?= $totalUsers ?></span>
      <span class="text-muted small">Authors</span>
    </div>
  </div>
  <div class="col-auto">
    <div class="card border-0 shadow-sm px-4 py-3 text-center">
      <span class="fs-3 fw-bold text-warning">5</span>
      <span class="text-muted small">Internship Tasks</span>
    </div>
  </div>
</div>

<!-- Recent posts -->
<?php if (!empty($recent)): ?>
<h4 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Recent Posts</h4>
<div class="row g-4 mb-4">
  <?php foreach ($recent as $post): ?>
  <div class="col-md-4 animate-fade-in">
    <div class="card h-100 post-card">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title">
          <a href="/apexplanet-internship/posts/view.php?id=<?= $post['id'] ?>"
             class="text-decoration-none text-dark post-title">
            <?= e($post['title']) ?>
          </a>
        </h5>
        <p class="card-text text-muted small flex-grow-1">
          <?= e(mb_substr(strip_tags($post['content']), 0, 120)) ?>…
        </p>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <small class="text-muted">
            <i class="bi bi-person me-1"></i><?= e($post['username']) ?>
          </small>
          <small class="text-muted">
            <?= date('M j', strtotime($post['created_at'])) ?>
          </small>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<div class="text-center">
  <a href="/apexplanet-internship/posts/index.php" class="btn btn-outline-primary">
    View All Posts <i class="bi bi-arrow-right ms-1"></i>
  </a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
