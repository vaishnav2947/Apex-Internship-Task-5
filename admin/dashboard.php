<?php
/**
 * admin/dashboard.php
 * Admin Dashboard — Task 4 (User Roles & Permissions)
 *
 * Features:
 *  - Site statistics overview
 *  - User management (view all, change role, toggle active)
 *  - Activity log
 *
 * Access: admin role only
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');   // only admins may access this page

$pdo = getDBConnection();

// ── Handle role change ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();

    $targetId = (int) ($_POST['user_id'] ?? 0);

    // Prevent admin from changing their own role (lock-out protection)
    if ($targetId === (int) $_SESSION['user_id']) {
        setFlash('danger', 'You cannot modify your own role.');
    } else {
        switch ($_POST['action']) {

            case 'change_role':
                $newRole = in_array($_POST['role'] ?? '', ['admin','editor','user'])
                           ? $_POST['role'] : 'user';
                $pdo->prepare('UPDATE users SET role = :role WHERE id = :id')
                    ->execute([':role' => $newRole, ':id' => $targetId]);
                logActivity($pdo, 'admin_change_role', "target_id: $targetId → $newRole");
                setFlash('success', 'Role updated.');
                break;

            case 'toggle_active':
                $pdo->prepare(
                    'UPDATE users SET is_active = NOT is_active WHERE id = :id'
                )->execute([':id' => $targetId]);
                logActivity($pdo, 'admin_toggle_active', "target_id: $targetId");
                setFlash('success', 'Account status updated.');
                break;
        }
    }
    redirect('/apexplanet-internship/admin/dashboard.php');
}

// ── Stats ─────────────────────────────────────────────────────────────
$totalUsers    = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalPosts    = $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$publishedPost = $pdo->query('SELECT COUNT(*) FROM posts WHERE status="published"')->fetchColumn();
$draftPosts    = $pdo->query('SELECT COUNT(*) FROM posts WHERE status="draft"')->fetchColumn();

// ── All users ─────────────────────────────────────────────────────────
$users = $pdo->query(
    'SELECT u.*, COUNT(p.id) AS post_count
     FROM users u
     LEFT JOIN posts p ON p.user_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC'
)->fetchAll();

// ── Recent activity log (last 20) ─────────────────────────────────────
$activities = $pdo->query(
    'SELECT a.*, u.username
     FROM activity_log a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 20'
)->fetchAll();

$pageTitle = 'Admin Dashboard | ApexPlanet Blog';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="fw-bold mb-0">
      <i class="bi bi-shield-lock me-2 text-danger"></i>Admin Dashboard
    </h1>
    <p class="text-muted mb-0">Site management &amp; user control</p>
  </div>
  <a href="/apexplanet-internship/posts/index.php" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back to Blog
  </a>
</div>

<!-- ── Stats row ─────────────────────────────────────────────────────── -->
<div class="row g-3 mb-5">
  <?php
  $stats = [
      ['label'=>'Total Users',    'value'=>$totalUsers,    'icon'=>'bi-people-fill',     'color'=>'#2e86c1'],
      ['label'=>'Total Posts',    'value'=>$totalPosts,    'icon'=>'bi-file-post-fill',  'color'=>'#27ae60'],
      ['label'=>'Published',      'value'=>$publishedPost, 'icon'=>'bi-globe',           'color'=>'#8e44ad'],
      ['label'=>'Drafts',         'value'=>$draftPosts,    'icon'=>'bi-file-earmark',    'color'=>'#e67e22'],
  ];
  foreach ($stats as $s): ?>
  <div class="col-6 col-md-3">
    <div class="card stat-card text-center"
         style="background: linear-gradient(135deg, <?= $s['color'] ?>, <?= $s['color'] ?>bb)">
      <i class="bi <?= $s['icon'] ?> fs-2 mb-2"></i>
      <span class="stat-number"><?= $s['value'] ?></span>
      <span class="stat-label"><?= $s['label'] ?></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── User Management table ─────────────────────────────────────────── -->
<div class="card shadow-sm mb-5">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-people me-2"></i>User Management</h5>
    <span class="badge bg-primary"><?= count($users) ?> users</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th><th>Username</th><th>Email</th><th>Role</th>
            <th>Posts</th><th>Status</th><th>Joined</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="text-muted"><?= $u['id'] ?></td>
            <td>
              <strong><?= e($u['username']) ?></strong>
              <?php if ($u['id'] == $_SESSION['user_id']): ?>
                <span class="badge bg-info ms-1">You</span>
              <?php endif; ?>
            </td>
            <td class="text-muted"><?= e($u['email']) ?></td>
            <td>
              <span class="badge bg-<?= $u['role']==='admin' ? 'danger' :
                                       ($u['role']==='editor' ? 'warning text-dark' : 'secondary') ?>">
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td><?= $u['post_count'] ?></td>
            <td>
              <span class="badge bg-<?= $u['is_active'] ? 'success' : 'danger' ?>">
                <?= $u['is_active'] ? 'Active' : 'Suspended' ?>
              </span>
            </td>
            <td class="text-muted small">
              <?= date('M j, Y', strtotime($u['created_at'])) ?>
            </td>
            <td>
              <?php if ($u['id'] != $_SESSION['user_id']): // can't change own account ?>
              <div class="d-flex gap-1 flex-wrap">
                <!-- Change Role form -->
                <form method="POST" class="d-inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action"  value="change_role">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <select name="role" class="form-select form-select-sm d-inline-block w-auto"
                          onchange="this.form.submit()"
                          title="Change role">
                    <?php foreach (['user','editor','admin'] as $r): ?>
                      <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>>
                        <?= ucfirst($r) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>

                <!-- Toggle active/suspend -->
                <form method="POST" class="d-inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action"  value="toggle_active">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit"
                          class="btn btn-sm btn-<?= $u['is_active'] ? 'warning' : 'success' ?>"
                          onclick="return confirm('<?= $u['is_active']
                            ? 'Suspend this account?' : 'Reactivate this account?' ?>')"
                          title="<?= $u['is_active'] ? 'Suspend' : 'Reactivate' ?>">
                    <i class="bi bi-<?= $u['is_active'] ? 'person-dash' : 'person-check' ?>"></i>
                  </button>
                </form>
              </div>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── Activity Log ──────────────────────────────────────────────────── -->
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Activity (last 20)</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Time</th><th>User</th><th>Action</th><th>IP</th><th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activities as $act): ?>
          <tr>
            <td class="text-muted small text-nowrap">
              <?= date('M j H:i', strtotime($act['created_at'])) ?>
            </td>
            <td><?= $act['username'] ? e($act['username']) : '<em class="text-muted">guest</em>' ?></td>
            <td>
              <?php
              $actionColors = [
                  'login_success'   => 'success',
                  'login_fail'      => 'danger',
                  'logout'          => 'secondary',
                  'register'        => 'info',
                  'create_post'     => 'primary',
                  'edit_post'       => 'warning',
                  'delete_post'     => 'danger',
              ];
              $col = $actionColors[$act['action']] ?? 'secondary';
              ?>
              <span class="badge bg-<?= $col ?>"><?= e($act['action']) ?></span>
            </td>
            <td class="text-muted small"><?= e($act['ip_address'] ?? '—') ?></td>
            <td class="text-muted small"><?= e(mb_substr($act['details'] ?? '', 0, 60)) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
