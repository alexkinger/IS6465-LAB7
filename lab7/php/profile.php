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
        $user = updateOwnProfile($pdo, (int) $user['user_id'], $_POST);
        $_SESSION['display_name'] = $user['full_name'];
        $success = 'Profile updated.';
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to update profile.';
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4" style="max-width: 640px;">
  <h1 class="mb-3">My Profile</h1>
  <?php if ($success): ?><div class="alert alert-success"><?= escapeHtml($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= escapeHtml($error) ?></div><?php endif; ?>
  <form method="post" action="profile.php" class="card card-body shadow-sm">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label" for="full_name">Full Name</label>
      <input class="form-control" id="full_name" name="full_name" required maxlength="120"
             value="<?= escapeHtml($user['full_name']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input class="form-control" id="email" name="email" type="email" required maxlength="254"
             value="<?= escapeHtml($user['email']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Role</label>
      <input class="form-control" value="<?= escapeHtml($user['role']) ?>" readonly>
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <input class="form-control" value="<?= escapeHtml($user['status']) ?>" readonly>
    </div>
    <button class="btn btn-primary" type="submit">Save Changes</button>
    <a class="btn btn-outline-secondary" href="change-password.php">Change Password</a>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
