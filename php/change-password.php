<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    try {
        changeOwnPassword(
            $pdo,
            (int) $user['user_id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );
        $success = 'Password updated.';
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to update password.';
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4" style="max-width: 560px;">
  <h1 class="mb-3">Change Password</h1>
  <?php if ($success): ?><div class="alert alert-success"><?= escapeHtml($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= escapeHtml($error) ?></div><?php endif; ?>
  <form method="post" action="change-password.php" class="card card-body shadow-sm">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label" for="current_password">Current Password</label>
      <input class="form-control" id="current_password" name="current_password" type="password" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="new_password">New Password</label>
      <input class="form-control" id="new_password" name="new_password" type="password" required minlength="12">
    </div>
    <div class="mb-3">
      <label class="form-label" for="confirm_password">Confirm New Password</label>
      <input class="form-control" id="confirm_password" name="confirm_password" type="password" required minlength="12">
    </div>
    <button class="btn btn-primary" type="submit">Update Password</button>
    <a class="btn btn-link" href="profile.php">Back to Profile</a>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
