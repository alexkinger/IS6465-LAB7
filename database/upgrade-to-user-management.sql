PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER PRIMARY KEY,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('Student', 'Tutor', 'Administrator')),
    status TEXT NOT NULL DEFAULT 'Active' CHECK(status IN ('Active', 'Inactive')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Link existing student profiles to user accounts (nullable until registration).
-- SQLite cannot add a UNIQUE column with ALTER TABLE, so uniqueness is via index.
ALTER TABLE students ADD COLUMN user_id INTEGER REFERENCES users(user_id);

CREATE UNIQUE INDEX IF NOT EXISTS idx_students_user_id ON students(user_id);
