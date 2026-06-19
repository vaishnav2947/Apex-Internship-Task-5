<?php
/**
 * posts/delete.php
 * Delete a post — Task 2 (D of CRUD)
 *
 * GET  → show confirmation page
 * POST → perform deletion
 *
 * We always confirm before deleting — good UX practice.
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id  = (int) ($_GET['id'] ?? 0);
$pdo = getDBConnection();

$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    setFlash('danger', 'Post not found.');
    redirect('/apexplanet-internship/posts/index.php');
}

// Only owner or admin can delete
if ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()) {
    setFlash('danger', 'You cannot delete this post.');
    redirect('/apexplanet-internship/posts/index.php');
}

// ════════════════════════════════════════════
// HANDLE POST (confirmed deletion)
// ════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $pdo->prepare('DELETE FROM posts WHERE id = :id')->execute([':id' => $id]);

    logActivity($pdo, 'delete_post', "post_id: $id, title: {$post['title']}");
    setFlash('success', 'Post "' . e($post['title']) . '" deleted.');
    redirect('/apexplanet-internship/posts/index.php');
}

// ════════════════════════════════════════════
// RENDER confirmation page
// ════════════════════════════════════════════
$pageTitle = 'Delete Post | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-danger shadow-sm animate-fade-in">
      <div class="card-header bg-danger text-white">
        <h4 class="mb-0"><i class="bi bi-trash me-2"></i>Confirm Delete</h4>
      </div>
      <div class="card-body p-4 text-center">
        <i class="bi bi-exclamation-triangle-fill display-1 text-danger mb-3"></i>
        <h5>Are you sure you want to delete this post?</h5>
        <div class="alert alert-light border my-3 text-start">
          <strong>Title:</strong> <?= e($post['title']) ?><br>
          <strong>Created:</strong> <?= date('M j, Y', strtotime($post['created_at'])) ?>
        </div>
        <p class="text-danger fw-semibold">
          <i class="bi bi-exclamation-circle me-1"></i>
          This action cannot be undone.
        </p>
        <form method="POST" action="" class="d-flex gap-2 justify-content-center mt-3">
          <?= csrfField() ?>
          <button type="submit" class="btn btn-danger px-4">
            <i class="bi bi-trash me-1"></i>Yes, Delete
          </button>
          <a href="/apexplanet-internship/posts/view.php?id=<?= $id ?>"
             class="btn btn-outline-secondary px-4">
            Cancel
          </a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
