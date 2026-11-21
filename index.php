<?php
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (isLoggedIn()) {
    $role = currentUserRole();
    if ($role === 'Administrator') {
        header('Location: php/manage-users.php', true, 302);
        exit;
    }
    if ($role === 'Student') {
        header('Location: php/profile.php', true, 302);
        exit;
    }
    header('Location: php/profile.php', true, 302);
    exit;
}
header('Location: php/login.php', true, 302);
exit;
