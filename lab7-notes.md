# Lab 7 Notes: Authentication and Authorization for the Peer Tutoring Hub

Repo: https://github.com/alexkinger/IS6465-LAB7  
This is a **new** repository. Lab 6 (`IS6465-LAB6`) was not modified.

Classroom demo only. Test data only. No real names, emails, or passwords.

---

## Part 1: Generate the Lab 7 Application

### AI prompt used

```
Generate code for all user management use cases UM-01 through UM-10 in the User Management Design Document.

Use the Application Builder, Test Harness Generator, and Traceability Auditor documents as implementation guidance. Follow the authentication, authorization, session management, password hashing, CSRF protection, and role based access practices in the Authentication Reading Handout.

Use lab6.zip as the existing Peer Tutoring Hub application to extend. Preserve the Lab 6 three tier architecture and update the application as needed to support user registration, login, logout, profile management, administrator access, and protected tutoring request workflows.

Return a complete Lab 7 application package with all updated and new files.
```

### Uploaded source files

1. Peer Tutoring Hub User Management Design Document (`Peer_Tutoring_Hub_User_Management_With_Diagrams.docx`)
2. Application Builder (`application-builder.txt`)
3. Test Harness Generator (`test-harness-generator.txt`)
4. Traceability Auditor (`traceability-auditor.txt`)
5. Authentication Reading Handout (`IS6465_Authentication_Reading_Handout_v1.2_[16Nov2026].pdf`)
6. Lab 6 application starter (Module 8 `lab6/` package / lab6.zip equivalent)

### How Lab 7 extends Lab 6

Lab 7 keeps the Lab 6 three-tier layout (`php/`, `services/`, `repositories/`, `config/`, `database/`, `tests/`). It adds a `users` table, links `students.user_id`, and introduces shared auth helpers in `includes/auth.php`. Registration, login, logout, profile, password change, and admin user management pages were added. Tutoring request pages now require login, CSRF on POSTs, Student or Administrator role checks, and ownership filtering for students.

### One generated piece verified against the design / handout

`includes/auth.php` implements `requireLogin` with a 1200-second idle timeout, reloads the user from SQLite, rejects Inactive accounts, and refreshes `last_activity`, `display_name`, and `role`. That matches the Authentication Reading Handout pattern. Public registration in `userService.php` only allows Student or Tutor, which matches the design rule that public registration cannot grant Administrator.

---

## Part 2: Set Up the Database

Commands used:

```bash
cd database
sqlite3 tutoring.db < setup-database.sql
sqlite3 tutoring.db < upgrade-to-user-management.sql
sqlite3 tutoring.db
.tables
.quit
```

Confirmation: all three required tables appeared: `students`, `tutoring_requests`, and `users`.

Pasted `.tables` output:

```
students           tutoring_requests  users
```

---

## Part 3: Create an Administrator Account

Command structure used (password omitted from notes):

```bash
php create-admin.php "Test Administrator" admin@example.com "<password-at-least-12-chars>"
```

Confirmation: the script printed that the administrator account was created successfully.

- Test administrator email: `admin@example.com`
- Role assigned: `Administrator`

---

## Part 4: Run the Application

```bash
cd ..
php -S 0.0.0.0:8000 php/router.php
```

Browser URL opened: `http://127.0.0.1:8000/` (and Codespaces equivalent on forwarded port 8000).

The application opened successfully. Visiting `/` or a protected page such as `/php/profile.php` without being logged in redirected to `/php/login.php`.

---

## Part 5: Test the Student Workflow

Test account used: `student@example.com` (fictional).

| Test | What I Did | Expected Result | Actual Result | Pass or Fail |
|------|------------|-----------------|---------------|--------------|
| Register student account | Opened Create Account, submitted Student role with test data | Student account is created | Redirected to login with registered confirmation; user row created | Pass |
| Log in as student | Submitted email/password on login form | Student can access protected student pages | Redirected to My Profile; student nav links available | Pass |
| View or update profile | Opened My Profile and confirmed name/email/role/status | Profile information is displayed or saved | Profile showed Test Student / student@example.com / Student / Active | Pass |
| Create tutoring request | Submitted request form (identity read-only from session) | A new request is created with status Open | Request #2 created with status Open | Pass |
| Edit Open request | Edited reason/topics/time while status Open | Updated request information is displayed | Reason updated to “Updated help with PHP authentication now.” | Pass |
| View my requests | Opened My Requests | Student sees the student’s own requests | List titled “My tutoring requests” showed the student’s request | Pass |
| Log out | Clicked Log Out, then tried profile again | Session ends and login page appears | Redirect to login; later profile visit redirected to login | Pass |

Screenshot / evidence: `screenshots/student-profile.html` and `screenshots/student-requests.html` (saved page captures from the successful student run).

---

## Part 6: Test the Administrator Workflow

Logged in as `admin@example.com`. Viewed Manage Users and All Requests. Confirmed the administrator can see more than one student’s account and request data (admin user + student user; seed Maria Lopez request plus the student’s request). Logged out successfully.

### Authentication vs authorization answers

**What is authentication in this application?**  
Authentication verifies who the user is. Login checks the email, Active status, and password hash, then creates a server session with `user_id`, display name, and role.

**What is authorization in this application?**  
Authorization decides what that authenticated user may do. Role checks (`requireRole`) and ownership queries (for example `students.user_id = current user`) limit pages and records.

**How is student access different from administrator access?**  
Students manage their own profile and only their own tutoring requests. Administrators can search all users and view all tutoring requests.

**Which functions or pages should be limited to administrators?**  
Manage Users, Update Role, Update Status, Delete User, and the all-requests admin view of every student’s requests.

**What happens after logout if a user tries to visit a protected page directly?**  
`requireLogin` finds no valid session `user_id` and redirects to `login.php`.

Screenshot / evidence: `screenshots/admin-manage-users.html` and `screenshots/admin-all-requests.html`.

---

## Part 7: Trace Registration and Login

### Student registration workflow

| Question | Answer |
|----------|--------|
| Which file displays the Create Account form? | `php/register.php` |
| Which file receives the submitted registration form? | `php/register.php` (POST to itself) |
| Which file or function creates and verifies the CSRF token? | `includes/auth.php` (`csrfToken`, `csrfField`, `verifyCsrfToken`) |
| Which service validates the registration information? | `services/userService.php` (`validateRegistration` / `registerUser`) |
| Where is the password hashed? | `services/userService.php` inside `registerUser` using `password_hash` |
| Which repository files insert user and student data? | `repositories/userPDO.php` (`insertUser`) and `repositories/studentPDO.php` (`insertStudentWithUser` or link existing student) |
| Which database tables receive records when a student registers? | `users` and `students` |

Flow: Browser → `register.php` → authentication/CSRF checks → registration controller logic in `register.php` → `userService` → user/student repositories → SQLite.

### User login workflow

| Question | Answer |
|----------|--------|
| Which file displays the login form? | `php/login.php` |
| Which file receives the submitted login credentials? | `php/login.php` (POST to itself) |
| Which repository retrieves a user by email address? | `repositories/userPDO.php` (`findUserByEmail`) |
| Where is the submitted password verified? | `services/userService.php` (`authenticateUser` with `password_verify`) |
| What session actions occur after successful login? | `session_regenerate_id(true)`; store `user_id`, `display_name`, `role`, `last_activity`; create CSRF token; redirect to profile |
| Why should the application regenerate the session ID after login? | To prevent session fixation (an attacker cannot keep using a pre-login session ID) |
| How does the application determine whether the logged in user is a student or administrator? | From the `role` column loaded from the `users` table into `$_SESSION['role']`, refreshed on each `requireLogin` |

Flow: Browser → `login.php` → authentication/CSRF checks → login controller logic in `login.php` → `userService` → `userPDO` → SQLite.

### Why hash and verify in the application layer

Password hashing and verification belong in the application layer because that layer owns business rules and secrets handling. Presentation pages should collect input and show results, not decide how credentials are stored. The database should store the hash, not invent hashing logic in SQL or setup scripts. Keeping `password_hash` / `password_verify` in `userService` makes the control easy to test, reuse, and audit.

---

## Part 8: Run the Automated Tests

Commands:

```bash
php tests/validation-tests.php
php tests/user-management-tests.php
php tests/integration-tests.php
```

### Validation tests: PASS

```
Valid time: PASS
Missing time: PASS
Altered value: PASS
Multiple valid topics: PASS
Missing topics: PASS
Altered topic: PASS
Minimum length: PASS
Maximum length: PASS
Too short: PASS
Too long: PASS
```

### User management tests: PASS

```
PASS UM-01 student registration creates Active Student
PASS UM-01 student profile row linked by user_id
PASS UM-01 rejects Administrator via public registration
PASS UM-01 duplicate email rejected
PASS UM-09 valid credentials authenticate
PASS UM-09 invalid password rejected
PASS Password hash does not contain plaintext password
PASS password_verify succeeds for stored hash
PASS UM-04 profile name update
PASS UM-05 new password works
PASS UM-05 old password fails
PASS Administrator account created
PASS UM-03 search returns matching accounts
PASS UM-06 role update
PASS UM-07 deactivate account
PASS UM-07 inactive user cannot log in
PASS Student can create tutoring request while authenticated
PASS UM-08 deletion blocked by related tutoring records
PASS Student request list shows own requests
PASS CSRF helper functions are available
PASS CSRF token format is 64 hex characters
All user-management checks passed
```

### Integration tests: PASS

```
PASS UC01 create and Open
PASS Lab 4 checkbox values preserved
PASS Duplicate email reuses student
PASS UC02 course and status filter
PASS Invalid reason rejected
PASS Invalid submission writes nothing
PASS Edit Open request
PASS Updated reason persisted
PASS Cancel Open request
PASS Cancelled row retained
PASS Second cancellation rejected
PASS Unrelated request unchanged
PASS FK enforced
PASS UC02 empty filter result
All integration checks passed
```

No failing integration tests after clearing seed rows inside the isolated in-memory test database. Full saved outputs are also under `screenshots/`.

---

## Part 9: AI Skills Pack Process

### Application Builder

**Prompt:**  
Explain how a student registration request moves from `register.php` through authentication checks, PHP services, repositories, PDO, and SQLite in the Peer Tutoring Hub application. Identify the responsibility of each file or layer.

**AI response (summary):**  
`register.php` renders the form and accepts POST. `auth.php` starts the session and verifies the CSRF token. `registerUser` in `userService.php` validates fields, rejects Administrator role for public signup, and hashes the password. `userPDO.php` inserts into `users`. For Student role, `studentPDO.php` inserts or links a `students` row with `user_id`. PDO opens `tutoring.db` through `config/db.php`.

**Accurate statement verified in code:**  
Password hashing happens in the service layer (`registerUser` calls `password_hash`) before the repository insert. Confirmed in `services/userService.php`.

### Traceability Auditor

**Prompt:**  
Trace the requirement “a student can register, log in, create a tutoring request, and log out” through the presentation pages, authentication code, services, repositories, SQLite tables, and automated tests in this project.

| Requirement | Presentation Page | Authentication or Authorization | Service or Validation | Repository or SQL | Database Table | Test Evidence |
|-------------|-------------------|----------------------------------|-----------------------|-------------------|----------------|---------------|
| Student registers an account | `php/register.php` | CSRF via `auth.php`; role limited to Student/Tutor | `registerUser` / `validateRegistration` | `insertUser`, `insertStudentWithUser` | `users`, `students` | UM-01 cases in `user-management-tests.php` |
| Student logs in | `php/login.php` | CSRF; session regenerate after success | `authenticateUser` + `password_verify` | `findUserByEmail` | `users` | UM-09 cases in `user-management-tests.php` |
| Student creates a tutoring request | `php/request-form.php` → `process-request.php` | `requireLogin`, `requireRole(Student)`, CSRF | `submitRequestForUser` / `validateSubmission` | `insertTutoringRequest`, student lookup by `user_id` | `students`, `tutoring_requests` | “Student can create tutoring request” + UC01 integration |
| User logs out | `php/logout.php` | `logoutSession` clears session and cookie | N/A (session cleanup) | N/A | N/A (session only) | Manual workflow; after logout protected pages redirect |

### Test Harness Generator

**Prompt:**  
Suggest one useful test case for authentication or authorization in the Peer Tutoring Hub application. The test must relate to registration, login, logout, session protection, role based access, password validation, CSRF protection, or account status.

**AI suggestion:**  
Attempt public registration with role `Administrator` and assert the account is rejected and no Administrator row is created.

**Evaluation:**

- Already covered: yes, in `tests/user-management-tests.php` (“UM-01 rejects Administrator via public registration”).
- Manual / automated: automated already; manually, open Create Account and confirm only Student/Tutor radios exist, then attempt a forged POST with role=Administrator and confirm the error.
- Appropriate: yes. It matches an approved design rule and is a real authorization boundary, not just a UI check.

### Skills Pack reflection

The Skills Pack helped keep Lab 7 aligned with the Lab 6 three-tier layout instead of inventing a new structure. The Application Builder explanation made the registration path easier to narrate for Part 7. The Traceability Auditor table forced a file-by-file check against UM requirements. Developers still have to verify AI output against the design document and source code, because Skills Pack text can lag the week’s auth requirements and generated code can miss ownership, CSRF, or schema details.

---

## Part 10: Inspect the Database

Command:

```bash
sqlite3 database/tutoring.db
```

Query:

```sql
SELECT
    u.user_id,
    u.full_name,
    u.email,
    u.role,
    u.status,
    s.student_id,
    s.student_name,
    s.student_email
FROM users AS u
LEFT JOIN students AS s
    ON u.user_id = s.user_id
ORDER BY u.user_id;
```

Result:

```
1|Test Administrator|admin@example.com|Administrator|Active|||
2|Test Student|student@example.com|Student|Active|2|Test Student|student@example.com
```

### Explanations

**What the LEFT JOIN does:**  
It returns every row from `users`, and adds matching `students` columns when a linked student profile exists. Users without a student row still appear, with NULL student fields.

**How `users` connects to `students`:**  
`students.user_id` is a foreign key to `users.user_id` (unique when set). One user account can link to at most one student profile.

**Why account info is stored separately from student profile info:**  
Login credentials, role, and status belong to the account (`users`). Tutoring workflow identity lives in `students` / `tutoring_requests`. Separating them lets Administrators and Tutors exist without forcing a tutoring-request profile, and keeps password hashes out of tutoring tables.

**Why an administrator may not have a matching students row:**  
Administrators are created for management, not tutoring requests. The CLI admin script inserts only into `users`, so the LEFT JOIN correctly shows NULL student columns for that account.

```
.quit
```

---

## Submission

- GitHub repo / Codespaces source: https://github.com/alexkinger/IS6465-LAB7
- Notes file: `lab7-notes.md` (this file)

Open the repo in GitHub Codespaces, sync if needed, then run the database and `php -S 0.0.0.0:8000 php/router.php` steps from the README for the live demo link.
