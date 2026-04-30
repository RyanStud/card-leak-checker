-- Script opcional de hardening MySQL (usuarios/grants/views)
-- Ajuste host/senhas antes de executar em producao.

USE u870812724_card_leak_chec;

-- Usuarios de aplicacao (exemplo)
-- CREATE USER IF NOT EXISTS 'clc_app_rw'@'localhost' IDENTIFIED BY 'ALTERE_AQUI_SENHA_FORTE';
-- CREATE USER IF NOT EXISTS 'clc_readonly'@'localhost' IDENTIFIED BY 'ALTERE_AQUI_SENHA_FORTE';

-- Permissoes sugeridas para aplicacao
-- GRANT SELECT, INSERT, UPDATE, DELETE ON u870812724_card_leak_chec.* TO 'clc_app_rw'@'localhost';

-- Permissoes sugeridas para leitura analitica
-- GRANT SELECT ON u870812724_card_leak_chec.* TO 'clc_readonly'@'localhost';

-- Views de apoio (somente leitura)
CREATE OR REPLACE VIEW vw_security_recent_events AS
SELECT
    id,
    user_id,
    email,
    ip_address,
    event_type,
    created_at
FROM suspicious_events
ORDER BY created_at DESC;

CREATE OR REPLACE VIEW vw_login_attempts_summary AS
SELECT
    ip_address,
    COUNT(*) AS total_attempts,
    SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) AS total_success,
    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) AS total_failed
FROM login_attempts
GROUP BY ip_address;

-- Se criar usuarios, ative grants e flush
-- FLUSH PRIVILEGES;
