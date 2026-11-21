<?php
require_once __DIR__ . '/../includes/auth.php';

startSecureSession();
logoutSession();
header('Location: login.php?logged_out=1', true, 302);
exit;
