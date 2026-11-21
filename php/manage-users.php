<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, array('Administrator'));

$search = trim((string) ($_GET['search'] ?? ''));
$role = (string) ($_GET['role'] ?? '');
$status = (string) ($_GET['status'] ?? '');
if ($role !== '' && !in_array($role, allowedAllRoles(), true)) {
    http_response_code(400);
    exit('Invalid role filter');
}
if ($status !== '' && !in_array($status, array('Active', 'Inactive'), true)) {
    http_response_code(400);
    exit('Invalid status filter');
}

$rows = searchUsers($pdo, $search, $role, $status);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <h1 class="mb-3">Manage Users</h1>
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
      <input class="form-control" name="search" placeholder="Search name or email" value="<?= escapeHtml($search) ?>">
    </div>
    <div class="col-md-3">
      <select class="form-select" name="role">
        <option value="">All roles</option>
        <?php foreach (allowedAllRoles() as $r): ?>
          <option value="<?= escapeHtml($r) ?>" <?= $r === $role ? 'selected' : '' ?>><?= escapeHtml($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="status">
        <option value="">All statuses</option>
        <?php foreach (array('Active', 'Inactive') as $st): ?>
          <option value="<?= escapeHtml($st) ?>" <?= $st === $status ? 'selected' : '' ?>><?= escapeHtml($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto"><button class="btn btn-primary">Search</button></div>
  </form>

  <?php if (!$rows): ?>
    <p>No matching accounts.</p>
  <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Manage</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= (int) $row['user_id'] ?></td>
            <td><?= escapeHtml($row['full_name']) ?></td>
            <td><?= escapeHtml($row['email']) ?></td>
            <td><?= escapeHtml($row['role']) ?></td>
            <td><?= escapeHtml($row['status']) ?></td>
            <td>
              <a href="update-role.php?user_id=<?= (int) $row['user_id'] ?>">Role</a> |
              <a href="update-status.php?user_id=<?= (int) $row['user_id'] ?>">Status</a> |
              <a href="delete-user.php?user_id=<?= (int) $row['user_id'] ?>">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
