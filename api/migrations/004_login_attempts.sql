CREATE TABLE login_attempts
(
    key          TEXT    NOT NULL, -- 'email:<lowercased>' or 'ip:<addr>' hashed
    attempted_at INTEGER NOT NULL
) STRICT;

CREATE INDEX login_attempts_key_time ON login_attempts (key, attempted_at);
