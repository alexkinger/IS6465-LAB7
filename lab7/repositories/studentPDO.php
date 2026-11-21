<?php

function findStudentByEmail($pdo, $email)
{
    // Lab 6-compatible column list (provided integration tests load setup-database.sql only).
    $stmt = $pdo->prepare('SELECT student_id, student_name, student_email FROM students WHERE student_email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function findStudentByEmailWithUser($pdo, $email)
{
    // Lab 7: includes user_id after upgrade-to-user-management.sql.
    $stmt = $pdo->prepare('SELECT student_id, student_name, student_email, user_id FROM students WHERE student_email = ?');
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch();
}

function findStudentByUserId($pdo, $userId)
{
    $stmt = $pdo->prepare('SELECT student_id, student_name, student_email, user_id FROM students WHERE user_id = ?');
    $stmt->execute([(int) $userId]);
    return $stmt->fetch();
}

function insertStudentIfAbsent($pdo, $name, $email)
{
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO students(student_name, student_email) VALUES(?,?)');
    $stmt->execute([$name, $email]);
}

function insertStudentWithUser($pdo, $name, $email, $userId)
{
    $stmt = $pdo->prepare('INSERT INTO students(student_name, student_email, user_id) VALUES(?,?,?)');
    $stmt->execute([$name, strtolower(trim($email)), (int) $userId]);
    return (int) $pdo->lastInsertId();
}

function linkStudentToUser($pdo, $studentId, $userId)
{
    $stmt = $pdo->prepare('UPDATE students SET user_id = ? WHERE student_id = ?');
    $stmt->execute([(int) $userId, (int) $studentId]);
    return $stmt->rowCount() === 1;
}

function updateStudentProfileByUserId($pdo, $userId, $name, $email)
{
    $stmt = $pdo->prepare('UPDATE students SET student_name = ?, student_email = ? WHERE user_id = ?');
    $stmt->execute([$name, strtolower(trim($email)), (int) $userId]);
    return $stmt->rowCount() >= 0;
}

function countStudentRequests($pdo, $userId)
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tutoring_requests r JOIN students s ON r.student_id = s.student_id WHERE s.user_id = ?');
    $stmt->execute([(int) $userId]);
    return (int) $stmt->fetchColumn();
}
