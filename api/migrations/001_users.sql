CREATE TABLE users
(
    id            INTEGER PRIMARY KEY,
    email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
    name          TEXT,
    is_admin      INTEGER NOT NULL DEFAULT 0 CHECK (is_admin IN (0, 1)),
    created_at    INTEGER NOT NULL, -- unix seconds
    last_login_at INTEGER
) STRICT;