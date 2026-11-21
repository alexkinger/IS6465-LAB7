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
$related = countStudentRequests($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    try {
        adminDeleteUser($pdo, (int) $actor['user_id'], $userId);
        header('Location: manage-users.php', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to delete account.';
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4" style="max-width: 640px;">
  <h1 class="mb-3">Delete Account</h1>
  <p>Delete account for <?= escapeHtml($target['full_name']) ?> (<?= escapeHtml($target['email']) ?>)?</p>
  <p>Related tutoring requests linked through a student profile: <?= (int) $related ?></p>
  <?php if ($related > 0): ?>
    <div class="alert alert-warning">Deletion is blocked while related tutoring request records exist.</div>
  <?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= escapeHtml($error) ?></div><?php endif; ?>
  <form method="post" class="card card-body shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="user_id" value="<?= (int) $userId ?>">
    <button class="btn btn-danger" type="submit" <?= $related > 0 ? 'disabled' : '' ?>>Confirm Delete</button>
    <a class="btn btn-secondary" href="manage-users.php">Cancel</a>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
