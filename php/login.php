<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

startSecureSession();
$pdo = openDatabase();
$error = '';
$message = '';

if (!empty($_GET['logged_out'])) {
    $message = 'You have been logged out.';
}
if (!empty($_GET['expired'])) {
    $message = 'Your session expired due to inactivity. Please log in again.';
}
if (!empty($_GET['inactive'])) {
    $message = 'This account is inactive or no longer available.';
}
if (!empty($_GET['registered'])) {
    $message = 'Account created. Please log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    try {
        $user = authenticateUser($pdo, $_POST['email'] ?? '', $_POST['password'] ?? '');
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['display_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        csrfToken();
        header('Location: profile.php', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to log in right now.';
    }
}

csrfToken();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4" style="max-width: 520px;">
  <h1 class="mb-3">Log In</h1>
  <?php if ($message): ?><div class="alert alert-info"><?= escapeHtml($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= escapeHtml($error) ?></div><?php endif; ?>
  <form method="post" action="login.php" class="card card-body shadow-sm">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input class="form-control" id="email" name="email" type="email" required maxlength="254"
             value="<?= escapeHtml($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="password">Password</label>
      <input class="form-control" id="password" name="password" type="password" required>
    </div>
    <button class="btn btn-primary" type="submit">Log In</button>
    <a class="btn btn-link" href="register.php">Create Account</a>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
