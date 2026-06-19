<?php
/**
 * posts/view.php
 * Display a single blog post.
 *
 * GET: ?id=N
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);   // cast to int — prevents any string injection

if ($id <= 0) {
    setFlash('danger', 'Invalid post ID.');
    redirect('/apexplanet-internship/posts/index.php');
}

$pdo = getDBConnection();

// Fetch post + author username with a JOIN
$stmt = $pdo->prepare(
    'SELECT p.*, u.username
     FROM posts p
     JOIN users u ON u.id = p.user_id
     WHERE p.id = :id
     LIMIT 1'
);
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

// 404-like behaviour if post doesn't exist
if (!$post) {
    setFlash('danger', 'Post not found.');
    redirect('/apexplanet-internship/posts/index.php');
}

// Non-admins cannot view drafts that aren't theirs
if ($post['status'] === 'draft'
    && (!isLoggedIn() || ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()))) {
    setFlash('danger', 'That post is not available.');
    redirect('/apexplanet-internship/posts/index.php');
}

$pageTitle = e($post['title']) . ' | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/apexplanet-internship/posts/index.php">Posts</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($post['title']) ?></li>
  </ol>
</nav>

<article class="card shadow-sm animate-fade-in">
  <div class="card-body p-4 p-md-5">

    <!-- Status badge for draft -->
    <?php if ($post['status'] === 'draft'): ?>
      <span class="badge bg-warning text-dark mb-3">
        <i class="bi bi-pencil me-1"></i>Draft (only you and admin can see this)
      </span>
    <?php endif; ?>

    <!-- Title -->
    <h1 class="fw-bold mb-2"><?= e($post['title']) ?></h1>

    <!-- Meta -->
    <p class="text-muted mb-4 pb-3 border-bottom">
      <i class="bi bi-person-circle me-1"></i>
      <strong><?= e($post['username']) ?></strong>
      &nbsp;·&nbsp;
      <i class="bi bi-calendar3 me-1"></i>
      Published <?= date('F j, Y \a\t g:i A', strtotime($post['created_at'])) ?>
      <?php if ($post['updated_at'] !== $post['created_at']): ?>
        &nbsp;·&nbsp;
        <i class="bi bi-pencil me-1"></i>
        Updated <?= date('M j, Y', strtotime($post['updated_at'])) ?>
      <?php endif; ?>
    </p>

    <!-- Content: nl2br converts newlines to <br> for display -->
    <div class="post-content lh-lg">
      <?= nl2br(e($post['content'])) ?>
    </div>

    <!-- Action buttons -->
    <?php if (isLoggedIn() &&
             ($_SESSION['user_id'] == $post['user_id'] || isAdmin())): ?>
      <hr class="mt-5">
      <div class="d-flex gap-2 flex-wrap">
        <a href="/apexplanet-internship/posts/edit.php?id=<?= $post['id'] ?>"
           class="btn btn-primary">
          <i class="bi bi-pencil me-1"></i>Edit Post
        </a>
        <a href="/apexplanet-internship/posts/delete.php?id=<?= $post['id'] ?>"
           class="btn btn-danger"
           onclick="return confirmDelete('<?= e(addslashes($post['title'])) ?>')">
          <i class="bi bi-trash me-1"></i>Delete Post
        </a>
        <a href="/apexplanet-internship/posts/index.php"
           class="btn btn-outline-secondary ms-auto">
          <i class="bi bi-arrow-left me-1"></i>All Posts
        </a>
      </div>
    <?php else: ?>
      <div class="mt-4">
        <a href="/apexplanet-internship/posts/index.php" class="btn btn-outline-primary">
          <i class="bi bi-arrow-left me-1"></i>Back to Posts
        </a>
      </div>
    <?php endif; ?>

  </div>
</article>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
