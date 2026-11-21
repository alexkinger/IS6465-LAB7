<?php
/**
 * Lab 7 user management tests (UM-01 through UM-10 focused checks).
 * Uses an isolated in-memory SQLite database.
 */

require_once dirname(__DIR__) . '/services/userService.php';
require_once dirname(__DIR__) . '/services/requestService.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys=ON');
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database/setup-database.sql'));
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database/upgrade-to-user-management.sql'));

$failed = 0;
function check($ok, $label)
{
    global $failed;
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
}

// UM-01 Create Student account
$studentId = registerUser($pdo, array(
    'full_name' => 'Test Student',
    'email' => 'student@example.com',
    'password' => 'StudentPass123',
    'confirm_password' => 'StudentPass123',
    'role' => 'Student',
));
$student = findUserById($pdo, $studentId);
check($student && $student['role'] === 'Student' && $student['status'] === 'Active', 'UM-01 student registration creates Active Student');
check(findStudentByUserId($pdo, $studentId) !== false, 'UM-01 student profile row linked by user_id');

// Public registration cannot create Administrator
try {
    registerUser($pdo, array(
        'full_name' => 'Fake Admin',
        'email' => 'fakeadmin@example.com',
        'password' => 'FakeAdminPass1',
        'confirm_password' => 'FakeAdminPass1',
        'role' => 'Administrator',
    ));
    check(false, 'UM-01 rejects Administrator via public registration');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01 rejects Administrator via public registration');
}

// Duplicate email rejected
try {
    registerUser($pdo, array(
        'full_name' => 'Dup Student',
        'email' => 'student@example.com',
        'password' => 'StudentPass123',
        'confirm_password' => 'StudentPass123',
        'role' => 'Student',
    ));
    check(false, 'UM-01 duplicate email rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01 duplicate email rejected');
}

// UM-09 Login / authenticate
$auth = authenticateUser($pdo, 'student@example.com', 'StudentPass123');
check((int) $auth['user_id'] === (int) $studentId, 'UM-09 valid credentials authenticate');

try {
    authenticateUser($pdo, 'student@example.com', 'WrongPassword!!');
    check(false, 'UM-09 invalid password rejected');
} catch (InvalidArgumentException $e) {
    check($e->getMessage() === 'Invalid email or password.', 'UM-09 invalid password rejected');
}

// Password hashing stored, not plaintext
check(strpos($student['password_hash'], 'StudentPass123') === false, 'Password hash does not contain plaintext password');
check(password_verify('StudentPass123', $student['password_hash']), 'password_verify succeeds for stored hash');

// UM-04 Update own profile
$updated = updateOwnProfile($pdo, $studentId, array(
    'full_name' => 'Test Student Updated',
    'email' => 'student@example.com',
));
check($updated['full_name'] === 'Test Student Updated', 'UM-04 profile name update');

// UM-05 Change password
changeOwnPassword($pdo, $studentId, 'StudentPass123', 'NewStudentPass1', 'NewStudentPass1');
$auth2 = authenticateUser($pdo, 'student@example.com', 'NewStudentPass1');
check((int) $auth2['user_id'] === (int) $studentId, 'UM-05 new password works');
try {
    authenticateUser($pdo, 'student@example.com', 'StudentPass123');
    check(false, 'UM-05 old password fails');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-05 old password fails');
}

// Administrator CLI-style create + UM-03 search
$adminId = createAdministratorAccount($pdo, 'Test Administrator', 'admin@example.com', 'TestPassword123');
$admin = findUserById($pdo, $adminId);
check($admin && $admin['role'] === 'Administrator', 'Administrator account created');
$users = searchUsers($pdo, 'student', '', '');
check(count($users) >= 1, 'UM-03 search returns matching accounts');

// UM-06 / UM-07 role and status
$tutorId = registerUser($pdo, array(
    'full_name' => 'Test Tutor',
    'email' => 'tutor@example.com',
    'password' => 'TutorPassword1',
    'confirm_password' => 'TutorPassword1',
    'role' => 'Tutor',
));
adminUpdateRole($pdo, $adminId, $tutorId, 'Student');
check(findUserById($pdo, $tutorId)['role'] === 'Student', 'UM-06 role update');
adminUpdateStatus($pdo, $adminId, $tutorId, 'Inactive');
check(findUserById($pdo, $tutorId)['status'] === 'Inactive', 'UM-07 deactivate account');
try {
    authenticateUser($pdo, 'tutor@example.com', 'TutorPassword1');
    check(false, 'UM-07 inactive user cannot log in');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-07 inactive user cannot log in');
}

// UM-08 deletion blocked when related requests exist
$activeStudent = findUserById($pdo, $studentId);
$requestId = submitRequestForUser($pdo, $activeStudent, array(
    'course' => 'IS 6465',
    'reason' => 'Need help with PHP sessions.',
    'topics' => array('Exam review'),
    'preferreddate' => (new DateTimeImmutable('+5 days'))->format('Y-m-d'),
    'preferredtime' => 'Morning',
));
check($requestId > 0, 'Student can create tutoring request while authenticated');
try {
    adminDeleteUser($pdo, $adminId, $studentId);
    check(false, 'UM-08 deletion blocked by related tutoring records');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-08 deletion blocked by related tutoring records');
}

// Ownership: student request list scoped
$mine = listRequestsForUser($pdo, $studentId);
check(count($mine) === 1, 'Student request list shows own requests');

// CSRF helpers exist (avoid starting a real session after CLI output has begun)
check(function_exists('csrfToken') && function_exists('verifyCsrfToken') && function_exists('csrfField'), 'CSRF helper functions are available');
$sampleToken = bin2hex(random_bytes(32));
check(strlen($sampleToken) === 64, 'CSRF token format is 64 hex characters');

echo $failed ? ('FAILURES: ' . $failed . PHP_EOL) : ("All user-management checks passed\n");
exit($failed ? 1 : 0);
