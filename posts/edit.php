<?php
/**
 * posts/edit.php
 * Edit an existing post — Task 2 (U of CRUD)
 *
 * Only the post owner or an admin may edit.
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id  = (int) ($_GET['id'] ?? 0);
$pdo = getDBConnection();

// Fetch existing post
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    setFlash('danger', 'Post not found.');
    redirect('/apexplanet-internship/posts/index.php');
}

// Authorisation: only owner or admin can edit
if ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()) {
    setFlash('danger', 'You do not have permission to edit this post.');
    redirect('/apexplanet-internship/posts/index.php');
}

$errors   = [];
$formData = $post;   // pre-populate form with existing data

// ════════════════════════════════════════════
// HANDLE POST
// ════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $title   = sanitise($_POST['title']   ?? '');
    $content = sanitise($_POST['content'] ?? '');
    $status  = in_array($_POST['status'] ?? '', ['published', 'draft'])
               ? $_POST['status'] : 'published';

    $formData = array_merge($formData, compact('title', 'content', 'status'));

    if ($title === '')           { $errors[] = 'Title is required.'; }
    if (mb_strlen($title) > 200) { $errors[] = 'Title exceeds 200 characters.'; }
    if ($content === '')         { $errors[] = 'Content is required.'; }

    if (empty($errors)) {
        $pdo->prepare(
            'UPDATE posts
             SET title = :title, content = :content, status = :status
             WHERE id  = :id'
        )->execute([
            ':title'   => $title,
            ':content' => $content,
            ':status'  => $status,
            ':id'      => $id,
        ]);

        logActivity($pdo, 'edit_post', "post_id: $id");
        setFlash('success', 'Post updated successfully!');
        redirect("/apexplanet-internship/posts/view.php?id=$id");
    }
}

$pageTitle = 'Edit Post | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/apexplanet-internship/posts/index.php">Posts</a></li>
    <li class="breadcrumb-item">
      <a href="/apexplanet-internship/posts/view.php?id=<?= $id ?>">
        <?= e(mb_substr($post['title'], 0, 40)) ?>…
      </a>
    </li>
    <li class="breadcrumb-item active">Edit</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow-sm animate-fade-in">
      <div class="card-header">
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Post</h4>
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

          <div class="mb-3">
            <label class="form-label fw-semibold" for="title">
              Title <span class="text-danger">*</span>
            </label>
            <input type="text" id="title" name="title"
                   class="form-control form-control-lg"
                   value="<?= e($formData['title']) ?>"
                   required maxlength="200">
            <div class="invalid-feedback">Title is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="content">
              Content <span class="text-danger">*</span>
            </label>
            <textarea id="content" name="content"
                      class="form-control" rows="12"
                      required><?= e($formData['content']) ?></textarea>
            <div class="invalid-feedback">Content is required.</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Status</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status"
                       id="statusPublished" value="published"
                       <?= $formData['status'] === 'published' ? 'checked' : '' ?>>
                <label class="form-check-label" for="statusPublished">
                  <i class="bi bi-globe me-1 text-success"></i>Published
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status"
                       id="statusDraft" value="draft"
                       <?= $formData['status'] === 'draft' ? 'checked' : '' ?>>
                <label class="form-check-label" for="statusDraft">
                  <i class="bi bi-file-earmark me-1 text-warning"></i>Draft
                </label>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-circle me-1"></i>Save Changes
            </button>
            <a href="/apexplanet-internship/posts/view.php?id=<?= $id ?>"
               class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
