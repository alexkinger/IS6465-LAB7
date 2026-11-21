<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

startSecureSession();
$pdo = openDatabase();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    try {
        registerUser($pdo, $_POST);
        header('Location: login.php?registered=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Unable to create the account right now.';
    }
}

csrfToken();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4" style="max-width: 560px;">
  <h1 class="mb-3">Create Account</h1>
  <?php if ($error): ?><div class="alert alert-danger"><?= escapeHtml($error) ?></div><?php endif; ?>
  <form method="post" action="register.php" class="card card-body shadow-sm">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label" for="full_name">Full Name</label>
      <input class="form-control" id="full_name" name="full_name" required maxlength="120"
             value="<?= escapeHtml($_POST['full_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input class="form-control" id="email" name="email" type="email" required maxlength="254"
             value="<?= escapeHtml($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label" for="password">Password</label>
      <input class="form-control" id="password" name="password" type="password" required minlength="12">
      <div class="form-text">At least 12 characters. Test data only.</div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="confirm_password">Confirm Password</label>
      <input class="form-control" id="confirm_password" name="confirm_password" type="password" required minlength="12">
    </div>
    <fieldset class="mb-3">
      <legend class="form-label">Account type</legend>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="role" id="role_student" value="Student"
          <?= (($_POST['role'] ?? 'Student') === 'Student') ? 'checked' : '' ?> required>
        <label class="form-check-label" for="role_student">Student</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="role" id="role_tutor" value="Tutor"
          <?= (($_POST['role'] ?? '') === 'Tutor') ? 'checked' : '' ?>>
        <label class="form-check-label" for="role_tutor">Tutor</label>
      </div>
    </fieldset>
    <button class="btn btn-primary" type="submit">Create Account</button>
    <a class="btn btn-link" href="login.php">Already have an account? Log In</a>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
