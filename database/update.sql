USE u870812724_card_leak_chec;

CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    reason VARCHAR(255) NOT NULL,
    blocked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    request_uri VARCHAR(255) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    user_agent TEXT NULL,
    country VARCHAR(80) NULL,
    response_code INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_history_user_changed (user_id, changed_at),
    CONSTRAINT fk_password_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

ALTER TABLE login_attempts
    ADD INDEX IF NOT EXISTS idx_login_attempts_ip_attempted (ip_address, attempted_at),
    ADD INDEX IF NOT EXISTS idx_login_attempts_email_attempted (email, attempted_at);

ALTER TABLE suspicious_events
    ADD INDEX IF NOT EXISTS idx_suspicious_events_ip_created (ip_address, created_at),
    ADD INDEX IF NOT EXISTS idx_suspicious_events_event_created (event_type, created_at);

ALTER TABLE request_logs
    ADD INDEX IF NOT EXISTS idx_request_logs_ip_created (ip_address, created_at),
    ADD INDEX IF NOT EXISTS idx_request_logs_created (created_at);

ALTER TABLE password_resets
    ADD INDEX IF NOT EXISTS idx_password_resets_token_valid (token_hash, used_at, expires_at),
    ADD INDEX IF NOT EXISTS idx_password_resets_user_used (user_id, used_at);

ALTER TABLE password_history
    ADD INDEX IF NOT EXISTS idx_password_history_user_changed (user_id, changed_at);

ALTER TABLE email_verifications
    ADD INDEX IF NOT EXISTS idx_email_verifications_token_valid (token_hash, used_at, expires_at);
