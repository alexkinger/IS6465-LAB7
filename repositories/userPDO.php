<?php

function findUserByEmail($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role, status, created_at FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch();
}

function findUserById($pdo, $userId)
{
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role, status, created_at FROM users WHERE user_id = ?');
    $stmt->execute([(int) $userId]);
    return $stmt->fetch();
}

function insertUser($pdo, $fullName, $email, $passwordHash, $role, $status = 'Active')
{
    $stmt = $pdo->prepare('INSERT INTO users(full_name, email, password_hash, role, status) VALUES(?,?,?,?,?)');
    $stmt->execute([$fullName, strtolower(trim($email)), $passwordHash, $role, $status]);
    return (int) $pdo->lastInsertId();
}

function updateUserProfile($pdo, $userId, $fullName, $email)
{
    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?');
    $stmt->execute([$fullName, strtolower(trim($email)), (int) $userId]);
    return $stmt->rowCount() >= 0;
}

function updateUserPassword($pdo, $userId, $passwordHash)
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
    $stmt->execute([$passwordHash, (int) $userId]);
    return $stmt->rowCount() === 1;
}

function updateUserRole($pdo, $userId, $role)
{
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE user_id = ?');
    $stmt->execute([$role, (int) $userId]);
    return $stmt->rowCount() === 1;
}

function updateUserStatus($pdo, $userId, $status)
{
    $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE user_id = ?');
    $stmt->execute([$status, (int) $userId]);
    return $stmt->rowCount() === 1;
}

function deleteUserById($pdo, $userId)
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
    $stmt->execute([(int) $userId]);
    return $stmt->rowCount() === 1;
}

function countActiveAdministrators($pdo)
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Administrator' AND status = 'Active'");
    return (int) $stmt->fetchColumn();
}

function searchUsers($pdo, $search = '', $role = '', $status = '')
{
    $sql = 'SELECT user_id, full_name, email, role, status, created_at FROM users WHERE 1=1';
    $args = array();

    if ($search !== '') {
        $sql .= ' AND (full_name LIKE ? OR email LIKE ?)';
        $like = '%' . $search . '%';
        $args[] = $like;
        $args[] = $like;
    }
    if ($role !== '') {
        $sql .= ' AND role = ?';
        $args[] = $role;
    }
    if ($status !== '') {
        $sql .= ' AND status = ?';
        $args[] = $status;
    }

    $sql .= ' ORDER BY user_id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}
