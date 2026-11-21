# Lab 7: Authentication and Authorization for the Peer Tutoring Hub

Extends Lab 6 with user management (UM-01 through UM-10), sessions, password hashing, CSRF protection, and role-based access.

Classroom demonstration only. Use test data only. Do not deploy publicly.

## Database setup

```bash
cd lab7/database
sqlite3 tutoring.db < setup-database.sql
sqlite3 tutoring.db < upgrade-to-user-management.sql
php create-admin.php "Test Administrator" admin@example.com "TestPassword123"
```

## Run the application

From the `lab7` project root:

```bash
cd ..
php -S 0.0.0.0:8000
```

Open port 8000. Protected pages redirect to login when you are not signed in.

## Tests

From the `lab7` project root:

```bash
php tests/validation-tests.php
php tests/user-management-tests.php
php tests/integration-tests.php
```

Do not change the provided test files unless the instructor directs you to.
