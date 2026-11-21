<?php
require_once __DIR__ . '/auth.php';
startSecureSession();
$loggedIn = isLoggedIn();
$role = currentUserRole();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="../index.php">Peer Tutoring Hub</a>
    <div class="navbar-nav">
      <?php if ($loggedIn): ?>
        <a class="nav-link" href="profile.php">My Profile</a>
        <?php if ($role === 'Student'): ?>
          <a class="nav-link" href="request-form.php">Request a Session</a>
          <a class="nav-link" href="requests.php">My Requests</a>
        <?php endif; ?>
        <?php if ($role === 'Administrator'): ?>
          <a class="nav-link" href="manage-users.php">Manage Users</a>
          <a class="nav-link" href="requests.php">All Requests</a>
        <?php endif; ?>
        <a class="nav-link" href="change-password.php">Change Password</a>
        <a class="nav-link" href="logout.php">Log Out</a>
      <?php else: ?>
        <a class="nav-link" href="login.php">Log In</a>
        <a class="nav-link" href="register.php">Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
