<?php
require_once dirname(__DIR__) . '/repositories/userPDO.php';
require_once dirname(__DIR__) . '/repositories/studentPDO.php';

function escapeHtml($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function allowedPublicRoles()
{
    return array('Student', 'Tutor');
}

function allowedAllRoles()
{
    return array('Student', 'Tutor', 'Administrator');
}

function validatePasswordStrength($password)
{
    if (strlen((string) $password) < 12) {
        return 'Password must be at least 12 characters.';
    }
    return '';
}

function validateRegistration($input)
{
    $errors = array();
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $confirm = (string) ($input['confirm_password'] ?? '');
    $role = (string) ($input['role'] ?? 'Student');

    if ($fullName === '' || mb_strlen($fullName) > 120) {
        $errors[] = 'Full name is required (max 120 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($msg = validatePasswordStrength($password)) {
        $errors[] = $msg;
    }
    if ($password !== $confirm) {
        $errors[] = 'Password and confirmation do not match.';
    }
    if (!in_array($role, allowedPublicRoles(), true)) {
        $errors[] = 'Select Student or Tutor. Administrator accounts cannot be created through public registration.';
    }

    return array($errors, array(
        'full_name' => $fullName,
        'email' => $email,
        'password' => $password,
        'role' => $role,
    ));
}

function registerUser($pdo, $input)
{
    list($errors, $data) = validateRegistration($input);
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    if (findUserByEmail($pdo, $data['email'])) {
        throw new InvalidArgumentException('An account with that email already exists.');
    }

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $userId = insertUser($pdo, $data['full_name'], $data['email'], $hash, $data['role'], 'Active');
        if ($data['role'] === 'Student') {
            $existing = findStudentByEmail($pdo, $data['email']);
            if ($existing) {
                if (!empty($existing['user_id']) && (int) $existing['user_id'] !== $userId) {
                    throw new RuntimeException('Student profile is already linked to another account.');
                }
                $stmt = $pdo->prepare('UPDATE students SET student_name = ?, user_id = ? WHERE student_id = ?');
                $stmt->execute([$data['full_name'], $userId, (int) $existing['student_id']]);
            } else {
                insertStudentWithUser($pdo, $data['full_name'], $data['email'], $userId);
            }
        }
        $pdo->commit();
        return $userId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function authenticateUser($pdo, $email, $password)
{
    $user = findUserByEmail($pdo, $email);
    if (!$user || $user['status'] !== 'Active' || !password_verify((string) $password, $user['password_hash'])) {
        throw new InvalidArgumentException('Invalid email or password.');
    }
    return $user;
}

function updateOwnProfile($pdo, $userId, $input)
{
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $errors = array();

    if ($fullName === '' || mb_strlen($fullName) > 120) {
        $errors[] = 'Full name is required (max 120 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $existing = findUserByEmail($pdo, $email);
    if ($existing && (int) $existing['user_id'] !== (int) $userId) {
        throw new InvalidArgumentException('An account with that email already exists.');
    }

    $pdo->beginTransaction();
    try {
        updateUserProfile($pdo, $userId, $fullName, $email);
        $user = findUserById($pdo, $userId);
        if ($user && $user['role'] === 'Student') {
            updateStudentProfileByUserId($pdo, $userId, $fullName, $email);
        }
        $pdo->commit();
        return findUserById($pdo, $userId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function changeOwnPassword($pdo, $userId, $currentPassword, $newPassword, $confirmPassword)
{
    $user = findUserById($pdo, $userId);
    if (!$user) {
        throw new InvalidArgumentException('Account not found.');
    }
    if (!password_verify((string) $currentPassword, $user['password_hash'])) {
        throw new InvalidArgumentException('Current password is incorrect.');
    }
    if ($msg = validatePasswordStrength($newPassword)) {
        throw new InvalidArgumentException($msg);
    }
    if ((string) $newPassword !== (string) $confirmPassword) {
        throw new InvalidArgumentException('New password and confirmation do not match.');
    }

    $hash = password_hash((string) $newPassword, PASSWORD_DEFAULT);
    if (!updateUserPassword($pdo, $userId, $hash)) {
        throw new RuntimeException('Unable to update password.');
    }
    return true;
}

function adminUpdateRole($pdo, $actorUserId, $targetUserId, $newRole)
{
    if (!in_array($newRole, allowedAllRoles(), true)) {
        throw new InvalidArgumentException('Invalid role.');
    }

    $target = findUserById($pdo, $targetUserId);
    if (!$target) {
        throw new InvalidArgumentException('User not found.');
    }

    if ($target['role'] === 'Administrator' && $newRole !== 'Administrator') {
        if (countActiveAdministrators($pdo) <= 1) {
            throw new InvalidArgumentException('Cannot change role of the last active administrator.');
        }
    }

    if (!updateUserRole($pdo, $targetUserId, $newRole)) {
        throw new RuntimeException('Unable to update role.');
    }
    return true;
}

function adminUpdateStatus($pdo, $actorUserId, $targetUserId, $newStatus)
{
    if (!in_array($newStatus, array('Active', 'Inactive'), true)) {
        throw new InvalidArgumentException('Invalid status.');
    }

    $target = findUserById($pdo, $targetUserId);
    if (!$target) {
        throw new InvalidArgumentException('User not found.');
    }

    if ($target['role'] === 'Administrator' && $target['status'] === 'Active' && $newStatus === 'Inactive') {
        if (countActiveAdministrators($pdo) <= 1) {
            throw new InvalidArgumentException('Cannot deactivate the last active administrator.');
        }
    }

    if (!updateUserStatus($pdo, $targetUserId, $newStatus)) {
        throw new RuntimeException('Unable to update status.');
    }
    return true;
}

function adminDeleteUser($pdo, $actorUserId, $targetUserId)
{
    $target = findUserById($pdo, $targetUserId);
    if (!$target) {
        throw new InvalidArgumentException('User not found.');
    }
    if ((int) $actorUserId === (int) $targetUserId) {
        throw new InvalidArgumentException('You cannot delete your own account while logged in.');
    }
    if ($target['role'] === 'Administrator' && $target['status'] === 'Active' && countActiveAdministrators($pdo) <= 1) {
        throw new InvalidArgumentException('Cannot delete the last active administrator.');
    }

    $related = countStudentRequests($pdo, $targetUserId);
    if ($related > 0) {
        throw new InvalidArgumentException('Deletion blocked: this account has related tutoring request records.');
    }

    $student = findStudentByUserId($pdo, $targetUserId);
    $pdo->beginTransaction();
    try {
        if ($student) {
            $stmt = $pdo->prepare('DELETE FROM students WHERE student_id = ?');
            $stmt->execute([(int) $student['student_id']]);
        }
        if (!deleteUserById($pdo, $targetUserId)) {
            throw new RuntimeException('Unable to delete user.');
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function createAdministratorAccount($pdo, $fullName, $email, $password)
{
    $fullName = trim((string) $fullName);
    $email = strtolower(trim((string) $email));
    if ($fullName === '') {
        throw new InvalidArgumentException('Full name is required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Valid email is required.');
    }
    if ($msg = validatePasswordStrength($password)) {
        throw new InvalidArgumentException($msg);
    }
    if (findUserByEmail($pdo, $email)) {
        throw new InvalidArgumentException('An account with that email already exists.');
    }
    $hash = password_hash((string) $password, PASSWORD_DEFAULT);
    return insertUser($pdo, $fullName, $email, $hash, 'Administrator', 'Active');
}
