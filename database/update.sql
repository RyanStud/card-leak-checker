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

CREATE TABLE IF NOT EXISTS telegram_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    telegram_user_id BIGINT NULL UNIQUE,
    telegram_username VARCHAR(32) NULL,
    telegram_first_name VARCHAR(120) NULL,
    telegram_last_name VARCHAR(120) NULL,
    telegram_phone VARCHAR(20) NULL,
    notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    link_code_hash CHAR(64) NULL,
    link_code_expires_at DATETIME NULL,
    login_code_hash CHAR(64) NULL,
    login_code_expires_at DATETIME NULL,
    login_code_sent_at DATETIME NULL,
    linked_at DATETIME NULL,
    last_interaction_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_telegram_accounts_user (user_id),
    UNIQUE KEY uq_telegram_accounts_link_code (link_code_hash),
    INDEX idx_telegram_accounts_login_code_expires (login_code_expires_at),
    INDEX idx_telegram_accounts_is_active (is_active),
    INDEX idx_telegram_accounts_expires (link_code_expires_at),
    CONSTRAINT fk_telegram_accounts_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_role_change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requested_by_user_id INT NOT NULL,
    target_user_id INT NOT NULL,
    from_role VARCHAR(20) NOT NULL,
    to_role VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by_user_id INT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_role_change_status_created (status, created_at),
    INDEX idx_admin_role_change_target_status (target_user_id, status),
    CONSTRAINT fk_admin_role_change_requested_by
        FOREIGN KEY (requested_by_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_admin_role_change_target
        FOREIGN KEY (target_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_admin_role_change_reviewed_by
        FOREIGN KEY (reviewed_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
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

ALTER TABLE email_verifications
    ADD INDEX IF NOT EXISTS idx_email_verifications_token_valid (token_hash, used_at, expires_at);

ALTER TABLE telegram_accounts
    ADD COLUMN IF NOT EXISTS telegram_user_id BIGINT NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS telegram_username VARCHAR(32) NULL AFTER telegram_user_id,
    ADD COLUMN IF NOT EXISTS telegram_first_name VARCHAR(120) NULL AFTER telegram_username,
    ADD COLUMN IF NOT EXISTS telegram_last_name VARCHAR(120) NULL AFTER telegram_first_name,
    ADD COLUMN IF NOT EXISTS telegram_phone VARCHAR(20) NULL AFTER telegram_last_name,
    ADD COLUMN IF NOT EXISTS notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER telegram_phone,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 0 AFTER notifications_enabled,
    ADD COLUMN IF NOT EXISTS link_code_hash CHAR(64) NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS link_code_expires_at DATETIME NULL AFTER link_code_hash,
    ADD COLUMN IF NOT EXISTS login_code_hash CHAR(64) NULL AFTER link_code_expires_at,
    ADD COLUMN IF NOT EXISTS login_code_expires_at DATETIME NULL AFTER login_code_hash,
    ADD COLUMN IF NOT EXISTS login_code_sent_at DATETIME NULL AFTER login_code_expires_at,
    ADD COLUMN IF NOT EXISTS linked_at DATETIME NULL AFTER login_code_sent_at,
    ADD COLUMN IF NOT EXISTS last_interaction_at DATETIME NULL AFTER linked_at,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD UNIQUE INDEX IF NOT EXISTS uq_telegram_accounts_user (user_id),
    ADD UNIQUE INDEX IF NOT EXISTS uq_telegram_accounts_link_code (link_code_hash),
    ADD UNIQUE INDEX IF NOT EXISTS uq_telegram_accounts_telegram_user (telegram_user_id),
    ADD INDEX IF NOT EXISTS idx_telegram_accounts_login_code_expires (login_code_expires_at),
    ADD INDEX IF NOT EXISTS idx_telegram_accounts_is_active (is_active),
    ADD INDEX IF NOT EXISTS idx_telegram_accounts_expires (link_code_expires_at);

ALTER TABLE admin_role_change_requests
    ADD COLUMN IF NOT EXISTS requested_by_user_id INT NOT NULL,
    ADD COLUMN IF NOT EXISTS target_user_id INT NOT NULL,
    ADD COLUMN IF NOT EXISTS from_role VARCHAR(20) NOT NULL,
    ADD COLUMN IF NOT EXISTS to_role VARCHAR(20) NOT NULL,
    ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS reviewed_by_user_id INT NULL,
    ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD INDEX IF NOT EXISTS idx_admin_role_change_status_created (status, created_at),
    ADD INDEX IF NOT EXISTS idx_admin_role_change_target_status (target_user_id, status);