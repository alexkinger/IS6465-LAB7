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

-- Link existing student profiles to user accounts (nullable until registration/login linking).
-- SQLite may already have students from Lab 6; add user_id only if missing.
-- Run once on a fresh Lab 6 database. Re-running may error on duplicate column.

ALTER TABLE students ADD COLUMN user_id INTEGER UNIQUE REFERENCES users(user_id);
