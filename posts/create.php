<?php
/**
 * posts/create.php
 * Create a new blog post — Task 2 (C of CRUD)
 *
 * Only logged-in users may create posts.
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();   // redirect to login if not authenticated

$errors   = [];
$formData = [];

// ════════════════════════════════════════════
// HANDLE POST
// ════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $title   = sanitise($_POST['title']   ?? '');
    $content = sanitise($_POST['content'] ?? '');
    $status  = in_array($_POST['status'] ?? '', ['published', 'draft'])
               ? $_POST['status'] : 'published';

    $formData = compact('title', 'content', 'status');

    // Validation
    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 200) {
        $errors[] = 'Title must not exceed 200 characters.';
    }

    if ($content === '') {
        $errors[] = 'Content is required.';
    } elseif (mb_strlen($content) < 10) {
        $errors[] = 'Content must be at least 10 characters.';
    }

    if (empty($errors)) {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare(
            'INSERT INTO posts (user_id, title, content, status)
             VALUES (:uid, :title, :content, :status)'
        );
        $stmt->execute([
            ':uid'     => $_SESSION['user_id'],
            ':title'   => $title,
            ':content' => $content,
            ':status'  => $status,
        ]);
        $newId = (int) $pdo->lastInsertId();

        logActivity($pdo, 'create_post', "post_id: $newId, title: $title");
        setFlash('success', 'Post published successfully!');
        redirect("/apexplanet-internship/posts/view.php?id=$newId");
    }
}

$pageTitle = 'New Post | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/apexplanet-internship/posts/index.php">Posts</a></li>
    <li class="breadcrumb-item active">New Post</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow-sm animate-fade-in">
      <div class="card-header">
        <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Post</h4>
      </div>
      <div class="card-body p-4">

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="" class="needs-validation" novalidate>
          <?= csrfField() ?>

          <!-- Title -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="title">
              Post Title <span class="text-danger">*</span>
            </label>
            <input
              type="text" id="title" name="title"
              class="form-control form-control-lg"
              placeholder="Enter a descriptive title…"
              value="<?= e($formData['title'] ?? '') ?>"
              required maxlength="200"
              autofocus
            >
            <div class="d-flex justify-content-between">
              <div class="invalid-feedback">Title is required.</div>
              <small class="text-muted ms-auto" id="titleCount">0 / 200</small>
            </div>
          </div>

          <!-- Content -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="content">
              Content <span class="text-danger">*</span>
            </label>
            <textarea
              id="content" name="content"
              class="form-control"
              rows="12"
              placeholder="Write your post content here…"
              required minlength="10"
            ><?= e($formData['content'] ?? '') ?></textarea>
            <div class="invalid-feedback">Content is required (min 10 characters).</div>
          </div>

          <!-- Status -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Status</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status"
                       id="statusPublished" value="published"
                       <?= ($formData['status'] ?? 'published') === 'published' ? 'checked' : '' ?>>
                <label class="form-check-label" for="statusPublished">
                  <i class="bi bi-globe me-1 text-success"></i>Published
                  <small class="text-muted d-block">Visible to everyone</small>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status"
                       id="statusDraft" value="draft"
                       <?= ($formData['status'] ?? '') === 'draft' ? 'checked' : '' ?>>
                <label class="form-check-label" for="statusDraft">
                  <i class="bi bi-file-earmark me-1 text-warning"></i>Draft
                  <small class="text-muted d-block">Only visible to you</small>
                </label>
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-circle me-1"></i>Publish Post
            </button>
            <a href="/apexplanet-internship/posts/index.php"
               class="btn btn-outline-secondary">
              Cancel
            </a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
// Character counter for title
const titleInput = document.getElementById('title');
const titleCount = document.getElementById('titleCount');
titleInput?.addEventListener('input', () => {
    titleCount.textContent = titleInput.value.length + ' / 200';
});
// Trigger on load (form repopulation after error)
if (titleInput) titleCount.textContent = titleInput.value.length + ' / 200';
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
