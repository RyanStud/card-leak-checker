-- ARQUIVO UNICO DE SINCRONIZACAO DO BANCO (estado mais recente).
-- Objetivo: atualizar estrutura sem perder dados ja existentes.
-- Este script nao usa DROP TABLE, TRUNCATE, DELETE ou comandos destrutivos.
-- Seguro para reexecucao em MariaDB 11+.

CREATE DATABASE IF NOT EXISTS u870812724_card_leak_chec
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE u870812724_card_leak_chec;

-- Garante criacao de tabelas ausentes (nao altera as existentes)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cpf CHAR(11) NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    job_title VARCHAR(120) NULL,
    cep CHAR(8) NULL,
    address_street VARCHAR(150) NULL,
    address_number VARCHAR(20) NULL,
    address_complement VARCHAR(120) NULL,
    address_neighborhood VARCHAR(120) NULL,
    address_city VARCHAR(120) NULL,
    address_state CHAR(2) NULL,
    email_verified TINYINT(1) DEFAULT 0,
    two_factor_enabled TINYINT(1) DEFAULT 0,
    two_factor_secret VARCHAR(255) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    owner_user_id INT NOT NULL,
    privacy_mode VARCHAR(30) NOT NULL DEFAULT 'private',
    justification TEXT NULL,
    approval_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    approved_at DATETIME NULL,
    approved_by INT NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_owner
        FOREIGN KEY (owner_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_approval_status (approval_status)
);

CREATE TABLE IF NOT EXISTS project_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_project_user (project_id, user_id),
    CONSTRAINT fk_membership_project
        FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_membership_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS card_check_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    card_fingerprint VARCHAR(255) NOT NULL,
    bin_masked VARCHAR(20) NOT NULL,
    last4_masked VARCHAR(10) NOT NULL,
    result_status VARCHAR(50) NOT NULL,
    source_name VARCHAR(80) NOT NULL DEFAULT 'demo-local',
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_check_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_check_project
        FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL,
    action_name VARCHAR(100) NOT NULL,
    metadata TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_audit_project
        FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_ip_attempted (ip_address, attempted_at),
    INDEX idx_login_attempts_email_attempted (email, attempted_at)
);

CREATE TABLE IF NOT EXISTS suspicious_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email VARCHAR(150) NULL,
    ip_address VARCHAR(45) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_suspicious_events_ip_created (ip_address, created_at),
    INDEX idx_suspicious_events_event_created (event_type, created_at)
);

CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_verifications_token_valid (token_hash, used_at, expires_at),
    CONSTRAINT fk_email_verifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_token_valid (token_hash, used_at, expires_at),
    INDEX idx_password_resets_user_used (user_id, used_at),
    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_logs_ip_created (ip_address, created_at),
    INDEX idx_request_logs_created (created_at)
);

CREATE TABLE IF NOT EXISTS leaked_cards_vault (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    card_lookup_hash CHAR(64) NOT NULL,
    payload_ciphertext MEDIUMBLOB NOT NULL,
    payload_iv VARBINARY(12) NOT NULL,
    payload_tag VARBINARY(16) NOT NULL,
    source_batch VARCHAR(80) NOT NULL DEFAULT 'sample-local',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_lookup_hash (card_lookup_hash),
    INDEX idx_source_batch_created (source_batch, created_at)
);

-- Ajustes de estrutura para bancos antigos
ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS justification TEXT NULL AFTER privacy_mode,
    ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER justification,
    ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approval_status,
    ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER approved_at,
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER approved_by,
    ADD INDEX IF NOT EXISTS idx_approval_status (approval_status);

-- Alinha default com o schema atual
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS cpf CHAR(11) NULL AFTER name,
    ADD COLUMN IF NOT EXISTS job_title VARCHAR(120) NULL AFTER password_hash,
    ADD COLUMN IF NOT EXISTS cep CHAR(8) NULL AFTER job_title,
    ADD COLUMN IF NOT EXISTS address_street VARCHAR(150) NULL AFTER cep,
    ADD COLUMN IF NOT EXISTS address_number VARCHAR(20) NULL AFTER address_street,
    ADD COLUMN IF NOT EXISTS address_complement VARCHAR(120) NULL AFTER address_number,
    ADD COLUMN IF NOT EXISTS address_neighborhood VARCHAR(120) NULL AFTER address_complement,
    ADD COLUMN IF NOT EXISTS address_city VARCHAR(120) NULL AFTER address_neighborhood,
    ADD COLUMN IF NOT EXISTS address_state CHAR(2) NULL AFTER address_city,
    ADD UNIQUE INDEX IF NOT EXISTS uq_users_cpf (cpf),
    ALTER COLUMN email_verified SET DEFAULT 0;

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

ALTER TABLE login_attempts
    ADD INDEX IF NOT EXISTS idx_login_attempts_ip_attempted (ip_address, attempted_at),
    ADD INDEX IF NOT EXISTS idx_login_attempts_email_attempted (email, attempted_at);

ALTER TABLE suspicious_events
    ADD INDEX IF NOT EXISTS idx_suspicious_events_ip_created (ip_address, created_at),
    ADD INDEX IF NOT EXISTS idx_suspicious_events_event_created (event_type, created_at);

ALTER TABLE request_logs
    ADD INDEX IF NOT EXISTS idx_request_logs_ip_created (ip_address, created_at),
    ADD INDEX IF NOT EXISTS idx_request_logs_created (created_at);

ALTER TABLE leaked_cards_vault
    ADD INDEX IF NOT EXISTS idx_card_lookup_hash (card_lookup_hash),
    ADD INDEX IF NOT EXISTS idx_source_batch_created (source_batch, created_at);

ALTER TABLE password_resets
    ADD INDEX IF NOT EXISTS idx_password_resets_token_valid (token_hash, used_at, expires_at),
    ADD INDEX IF NOT EXISTS idx_password_resets_user_used (user_id, used_at);

ALTER TABLE email_verifications
    ADD INDEX IF NOT EXISTS idx_email_verifications_token_valid (token_hash, used_at, expires_at);
