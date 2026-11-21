<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$actor = requireLogin($pdo);
requireRole($actor, array('Administrator'));

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$userId && isset($_POST['user_id'])) {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
}
if (!$userId || $userId < 1) {
    http_response_code(400);
    exit('Invalid user ID');
}

$target = findUserById($pdo, $userId);
if (!$target) {
    http_response_code(404);
    exit('User not found');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    try {
        adminUpdateRole($pdo, (int) $actor['user_id'], $userId, $_POST['role'] ?? '');
        $target = findUserById($pdo, $userId);
        $success = 'Role updated.';
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to update role.';
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4" style="max-width: 560px;">
  <h1 class="mb-3">Update Role</h1>
  <p>User: <?= escapeHtml($target['full_name']) ?> (<?= escapeHtml($target['email']) ?>)</p>
  <?php if ($success): ?><div class="alert alert-success"><?= escapeHtml($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= escapeHtml($error) ?></div><?php endif; ?>
  <form method="post" class="card card-body shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="user_id" value="<?= (int) $userId ?>">
    <div class="mb-3">
      <label class="form-label" for="role">New role</label>
      <select class="form-select" id="role" name="role" required>
        <?php foreach (allowedAllRoles() as $r): ?>
          <option value="<?= escapeHtml($r) ?>" <?= $r === $target['role'] ? 'selected' : '' ?>><?= escapeHtml($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Save Role</button>
    <a class="btn btn-secondary" href="manage-users.php">Cancel</a>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
