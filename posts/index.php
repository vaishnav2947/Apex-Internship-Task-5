<?php
/**
 * posts/index.php
 * All published posts — with SEARCH (Task 3) and PAGINATION (Task 3)
 *
 * GET parameters:
 *   ?search=keyword   — filter posts
 *   ?page=N           — paginate results
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// ════════════════════════════════════════════
// SEARCH & PAGINATION SETUP  (Task 3)
// ════════════════════════════════════════════
$perPage = 5;                                                  // posts per page
$page    = max(1, (int) ($_GET['page'] ?? 1));                 // current page (min 1)
$offset  = ($page - 1) * $perPage;                            // SQL OFFSET

// Sanitise search term — only trim, no stripping
// We use prepared statements so there is no injection risk
$search = sanitise($_GET['search'] ?? '');

// ── Build query dynamically based on whether there's a search term ────
if ($search !== '') {
    // LIKE search: % is a wildcard meaning "anything"
    // We search in BOTH title and content columns
    $likeParam = '%' . $search . '%';

    // Count total matching rows (needed to calculate total pages)
    $countStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE p.status = "published"
           AND (p.title LIKE :s OR p.content LIKE :s2)'
    );
    $countStmt->execute([':s' => $likeParam, ':s2' => $likeParam]);

    // Fetch the matching posts for the current page
    $stmt = $pdo->prepare(
        'SELECT p.*, u.username
         FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE p.status = "published"
           AND (p.title LIKE :s OR p.content LIKE :s2)
         ORDER BY p.created_at DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':s',      $likeParam,  PDO::PARAM_STR);
    $stmt->bindValue(':s2',     $likeParam,  PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $perPage,    PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
    $stmt->execute();

} else {
    // No search — fetch all published posts
    $countStmt = $pdo->query('SELECT COUNT(*) FROM posts WHERE status = "published"');

    $stmt = $pdo->prepare(
        'SELECT p.*, u.username
         FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE p.status = "published"
         ORDER BY p.created_at DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
}

$totalPosts = (int) $countStmt->fetchColumn();
$posts      = $stmt->fetchAll();
$totalPages = (int) ceil($totalPosts / $perPage);   // round UP

// ════════════════════════════════════════════
// RENDER
// ════════════════════════════════════════════
$pageTitle = 'Posts | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Page Header ───────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0"><i class="bi bi-file-post me-2"></i>Blog Posts</h1>
    <p class="text-muted mb-0">
      <?= $totalPosts ?> post<?= $totalPosts !== 1 ? 's' : '' ?>
      <?= $search ? ' found for <strong>' . e($search) . '</strong>' : '' ?>
    </p>
  </div>
  <?php if (isLoggedIn()): ?>
    <a href="/apexplanet-internship/posts/create.php" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i>New Post
    </a>
  <?php endif; ?>
</div>

<!-- ── Search Form (Task 3) ─────────────────────────────────────────── -->
<div class="card mb-4 border-0 shadow-sm">
  <div class="card-body py-3">
    <form method="GET" action="" class="row g-2 align-items-center">
      <div class="col">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input
            type="search" name="search"
            class="form-control border-start-0 ps-0"
            placeholder="Search posts by title or content…"
            value="<?= e($search) ?>"
            autocomplete="off"
          >
        </div>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary px-4">Search</button>
        <?php if ($search): ?>
          <a href="/apexplanet-internship/posts/index.php"
             class="btn btn-outline-secondary ms-1">
            <i class="bi bi-x"></i> Clear
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- ── Posts List ───────────────────────────────────────────────────── -->
<?php if (empty($posts)): ?>
  <div class="text-center py-5">
    <i class="bi bi-journal-x display-1 text-muted"></i>
    <h3 class="mt-3 text-muted">No posts found</h3>
    <?php if ($search): ?>
      <p class="text-muted">Try a different search term.</p>
    <?php elseif (isLoggedIn()): ?>
      <p class="text-muted">Be the first to write something!</p>
      <a href="/apexplanet-internship/posts/create.php" class="btn btn-primary">
        <i class="bi bi-plus me-1"></i>Create First Post
      </a>
    <?php endif; ?>
  </div>

<?php else: ?>
  <div class="row g-4">
    <?php foreach ($posts as $post): ?>
      <div class="col-12 animate-fade-in">
        <div class="card h-100 post-card">
          <div class="card-body">

            <!-- Title & meta ───────────────────────── -->
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="flex-grow-1">
                <h4 class="card-title mb-1">
                  <a href="/apexplanet-internship/posts/view.php?id=<?= $post['id'] ?>"
                     class="text-decoration-none text-dark fw-bold post-title">
                    <!-- Highlight search term in title (Task 3 UX improvement) -->
                    <?php if ($search): ?>
                      <?= preg_replace(
                            '/(' . preg_quote(e($search), '/') . ')/i',
                            '<mark>$1</mark>',
                            e($post['title'])
                      ) ?>
                    <?php else: ?>
                      <?= e($post['title']) ?>
                    <?php endif; ?>
                  </a>
                </h4>
                <small class="text-muted">
                  <i class="bi bi-person me-1"></i><?= e($post['username']) ?>
                  &nbsp;·&nbsp;
                  <i class="bi bi-calendar3 me-1"></i>
                  <?= date('M j, Y', strtotime($post['created_at'])) ?>
                </small>
              </div>

              <!-- Action buttons (only for post owner or admin) -->
              <?php if (isLoggedIn() &&
                       ($_SESSION['user_id'] == $post['user_id'] || isAdmin())): ?>
                <div class="d-flex gap-2 flex-shrink-0">
                  <a href="/apexplanet-internship/posts/edit.php?id=<?= $post['id'] ?>"
                     class="btn btn-sm btn-outline-primary"
                     title="Edit post">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="/apexplanet-internship/posts/delete.php?id=<?= $post['id'] ?>"
                     class="btn btn-sm btn-outline-danger"
                     title="Delete post"
                     onclick="return confirmDelete('<?= e(addslashes($post['title'])) ?>')">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <!-- Excerpt ───────────────────────────── -->
            <p class="card-text mt-3 text-muted">
              <?php
              // Show first 200 characters of content as excerpt
              $excerpt = mb_substr(strip_tags($post['content']), 0, 200);
              if (mb_strlen($post['content']) > 200) $excerpt .= '…';

              // Highlight search term in excerpt too
              if ($search) {
                  echo preg_replace(
                      '/(' . preg_quote(e($search), '/') . ')/i',
                      '<mark>$1</mark>',
                      e($excerpt)
                  );
              } else {
                  echo e($excerpt);
              }
              ?>
            </p>

            <!-- Read more link -->
            <a href="/apexplanet-internship/posts/view.php?id=<?= $post['id'] ?>"
               class="btn btn-sm btn-outline-primary mt-1">
              Read more <i class="bi bi-arrow-right ms-1"></i>
            </a>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ── PAGINATION (Task 3) ─────────────────────────────────────── -->
  <?php if ($totalPages > 1): ?>
    <nav class="mt-5" aria-label="Posts navigation">
      <ul class="pagination justify-content-center flex-wrap">

        <!-- Previous button -->
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link"
             href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
            <i class="bi bi-chevron-left"></i> Prev
          </a>
        </li>

        <!-- Page number buttons -->
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link"
               href="?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
              <?= $p ?>
            </a>
          </li>
        <?php endfor; ?>

        <!-- Next button -->
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link"
             href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
            Next <i class="bi bi-chevron-right"></i>
          </a>
        </li>

      </ul>

      <!-- "Showing X–Y of Z" text -->
      <p class="text-center text-muted small mt-2">
        Showing
        <?= min($offset + 1, $totalPosts) ?>–<?= min($offset + $perPage, $totalPosts) ?>
        of <?= $totalPosts ?> posts
        <?= $totalPages > 1 ? "(Page $page of $totalPages)" : '' ?>
      </p>
    </nav>
  <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
