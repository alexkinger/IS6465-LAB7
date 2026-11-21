# IS 6465 Lab 7: Authentication and Authorization

Peer Tutoring Hub with user management (UM-01 through UM-10), sessions, password hashing, CSRF protection, and role-based access for Student and Administrator roles.

This repository is separate from Lab 6 (`IS6465-LAB6`). Lab 6 is unchanged.

## Requirements

- PHP 8 with PDO SQLite and mbstring
- sqlite3 CLI

## Database setup

```bash
cd database
sqlite3 tutoring.db < setup-database.sql
sqlite3 tutoring.db < upgrade-to-user-management.sql
php create-admin.php "Test Administrator" admin@example.com "TestPassword123"
cd ..
```

Use test data only. Do not enter real names, emails, or passwords.

## Run the app

```bash
php -S 0.0.0.0:8000 php/router.php
```

Open the forwarded port 8000. Unauthenticated visits redirect to login.

## Tests

```bash
php tests/validation-tests.php
php tests/user-management-tests.php
php tests/integration-tests.php
```

## Notes

See `lab7-notes.md` for the lab write-up.

Classroom demonstration only. Do not deploy publicly.
