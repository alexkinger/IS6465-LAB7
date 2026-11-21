<?php
require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/repositories/studentPDO.php';
require_once dirname(__DIR__) . '/repositories/requestPDO.php';

if (!function_exists('escapeHtml')) {
    function escapeHtml($v)
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

// Week 4 form fields map to the Week 6 schema without adding database columns.
// topic contains JSON {reason,topics}; preferred_time stores Morning/Afternoon/Evening.
function decodeTopic($topic)
{
    $v = json_decode($topic, true);
    return is_array($v) && isset($v['reason']) && is_array($v['topics'] ?? null)
        ? $v
        : array('reason' => $topic, 'topics' => array());
}

function validateSubmission($input, $requireStudent = true)
{
    $errors = array();
    $courses = array('IS 6465', 'IS 6495', 'IS 6570');
    $name = trim((string) ($input['student_name'] ?? ''));
    $email = trim((string) ($input['student_email'] ?? ''));
    $course = trim((string) ($input['course'] ?? ''));
    $reason = trim((string) ($input['reason'] ?? ''));
    $date = (string) ($input['preferreddate'] ?? '');
    $time = (string) ($input['preferredtime'] ?? '');
    $topics = $input['topics'] ?? array();

    if (!is_array($topics)) {
        $errors[] = 'Topics must be a list.';
        $topics = array();
    }
    if ($requireStudent && $name === '') {
        $errors[] = 'Student name is required.';
    }
    if ($requireStudent && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid student email required.';
    }
    if (!in_array($course, $courses, true)) {
        $errors[] = 'Select a valid course.';
    }
    if ($msg = validatePreferredTime($time)) {
        $errors[] = $msg;
    }
    if ($msg = validateTopics($topics)) {
        $errors[] = $msg;
    }
    if ($msg = validateReason($reason)) {
        $errors[] = $msg;
    }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date || $dt < new DateTimeImmutable('today')) {
        $errors[] = 'Choose a valid date today or later.';
    }

    $payload = array(
        'student_name' => $name,
        'student_email' => strtolower($email),
        'course' => $course,
        'topic' => json_encode(array('reason' => $reason, 'topics' => array_values($topics))),
        'preferred_date' => $date,
        'preferred_time' => $time,
    );
    return array($errors, $payload);
}

function submitRequest($pdo, $input)
{
    list($errors, $data) = validateSubmission($input);
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }
    $pdo->beginTransaction();
    try {
        insertStudentIfAbsent($pdo, $data['student_name'], $data['student_email']);
        $student = findStudentByEmail($pdo, $data['student_email']);
        if (!$student) {
            throw new RuntimeException('Student lookup failed');
        }
        $id = insertTutoringRequest($pdo, (int) $student['student_id'], $data);
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function submitRequestForUser($pdo, $user, $input)
{
    $input['student_name'] = $user['full_name'];
    $input['student_email'] = $user['email'];
    list($errors, $data) = validateSubmission($input);
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $pdo->beginTransaction();
    try {
        $student = findStudentByUserId($pdo, (int) $user['user_id']);
        if (!$student) {
            $existing = findStudentByEmail($pdo, $data['student_email']);
            if ($existing) {
                $stmt = $pdo->prepare('UPDATE students SET student_name = ?, user_id = ? WHERE student_id = ?');
                $stmt->execute([$data['student_name'], (int) $user['user_id'], (int) $existing['student_id']]);
                $student = findStudentByUserId($pdo, (int) $user['user_id']);
            } else {
                insertStudentWithUser($pdo, $data['student_name'], $data['student_email'], (int) $user['user_id']);
                $student = findStudentByUserId($pdo, (int) $user['user_id']);
            }
        }
        if (!$student) {
            throw new RuntimeException('Student profile missing for authenticated user.');
        }
        $id = insertTutoringRequest($pdo, (int) $student['student_id'], $data);
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function reviseRequest($pdo, $id, $input)
{
    list($errors, $data) = validateSubmission($input, false);
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }
    return updateOpenRequest($pdo, $id, $data);
}

function reviseRequestForUser($pdo, $id, $userId, $input)
{
    list($errors, $data) = validateSubmission($input, false);
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }
    return updateOpenRequestForUser($pdo, $id, $userId, $data);
}
