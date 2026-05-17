-- =============================================================================
-- Hardening MySQL: usuarios segmentados + views de apoio
-- =============================================================================
-- Estrategia: principio do menor privilegio (least privilege).
--   - A aplicacao web (UserAPP) precisa escrita completa.
--   - Analytics/BI (cardleak_analyst) ve so dados mascarados e metricas.
--   - Time de seguranca (cardleak_soc) ve eventos de seguranca sem PII pesada.
--   - UserReadOnly mantido para acesso amplo de leitura (consultas pontuais).
--
-- Views nao implementam row-level security verdadeiro no MySQL: o isolamento
-- por dono de projeto/usuario continua sendo responsabilidade da aplicacao
-- (clausulas WHERE owner_user_id = ? no PHP). As views aqui servem para:
--   1. Mascaramento de PII (LGPD)
--   2. Agregacao para metricas sem expor linhas individuais
--   3. Filtragem para reduzir superficie ("so o que importa")
--   4. Pre-junte de FKs para nao expor IDs cruas
--
-- Ajuste host/senhas antes de executar em producao.
-- =============================================================================

USE u870812724_card_leak_chec;

-- =============================================================================
-- 1. USUARIOS DE BANCO (descomente para criar)
-- =============================================================================

-- CREATE USER IF NOT EXISTS 'UserAPP'@'localhost'           IDENTIFIED BY 'ALTERE_AQUI_SENHA_FORTE';
-- CREATE USER IF NOT EXISTS 'UserReadOnly'@'localhost'      IDENTIFIED BY 'ALTERE_AQUI_SENHA_FORTE';
-- CREATE USER IF NOT EXISTS 'cardleak_analyst'@'localhost'  IDENTIFIED BY 'ALTERE_AQUI_SENHA_FORTE';
-- CREATE USER IF NOT EXISTS 'cardleak_soc'@'localhost'      IDENTIFIED BY 'ALTERE_AQUI_SENHA_FORTE';


-- =============================================================================
-- 2. VIEWS - MASCARAMENTO DE PII (LGPD)
-- =============================================================================
-- Expoem dados de usuarios/checks sem campos sensiveis (password_hash,
-- two_factor_secret, CPF/email completos). Use estas views em qualquer
-- contexto que nao precise do dado em claro: dashboards, exports, BI.

-- Usuarios sem hash de senha, sem segredo 2FA, com CPF/email mascarados
CREATE OR REPLACE VIEW vw_users_safe AS
SELECT
    id,
    SUBSTRING(name, 1, 1) AS name_initial,
    CONCAT(LEFT(email, 2), '***@', SUBSTRING_INDEX(email, '@', -1)) AS email_masked,
    CASE
        WHEN cpf IS NULL THEN NULL
        ELSE CONCAT('***.***.', RIGHT(cpf, 3), '-**')
    END AS cpf_masked,
    job_title,
    address_city,
    address_state,
    role,
    email_verified,
    two_factor_enabled,
    created_at,
    updated_at
FROM users;

-- Checks de cartao expondo apenas mascaras (PAN nunca em texto puro)
CREATE OR REPLACE VIEW vw_card_checks_safe AS
SELECT
    id,
    user_id,
    project_id,
    bin_masked,
    last4_masked,
    result_status,
    source_name,
    checked_at
FROM card_check_requests;

-- Vinculos de Telegram sem codigos de login/link em hash
CREATE OR REPLACE VIEW vw_telegram_links_safe AS
SELECT
    id,
    user_id,
    telegram_username,
    telegram_first_name,
    is_active,
    notifications_enabled,
    linked_at,
    last_interaction_at
FROM telegram_accounts
WHERE is_active = 1;


-- =============================================================================
-- 3. VIEWS - METRICAS AGREGADAS (sem expor linhas individuais)
-- =============================================================================
-- Para dashboards admin e relatorios. So agregados, sem PII.

-- KPIs gerais do sistema
CREATE OR REPLACE VIEW vw_admin_kpi_summary AS
SELECT
    (SELECT COUNT(*) FROM users)                                                              AS total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'admin')                                         AS total_admins,
    (SELECT COUNT(*) FROM users WHERE two_factor_enabled = 1)                                 AS total_2fa,
    (SELECT COUNT(*) FROM users WHERE email_verified = 1)                                     AS total_verified,
    (SELECT COUNT(*) FROM projects)                                                           AS total_projects,
    (SELECT COUNT(*) FROM projects WHERE approval_status = 'pending')                         AS projects_pending,
    (SELECT COUNT(*) FROM card_check_requests)                                                AS total_checks,
    (SELECT COUNT(*) FROM suspicious_events WHERE created_at >= NOW() - INTERVAL 7 DAY)       AS suspicious_last_7d,
    (SELECT COUNT(*) FROM blocked_ips WHERE blocked_until IS NULL OR blocked_until > NOW())   AS active_ip_blocks;

-- Funil de login agregado por dia
CREATE OR REPLACE VIEW vw_login_funnel_daily AS
SELECT
    DATE(attempted_at) AS day,
    COUNT(*) AS total,
    SUM(success = 1) AS successes,
    SUM(success = 0) AS failures,
    ROUND(100 * SUM(success = 1) / COUNT(*), 2) AS success_rate
FROM login_attempts
GROUP BY DATE(attempted_at)
ORDER BY day DESC;

-- Eventos suspeitos agregados por tipo e dia
CREATE OR REPLACE VIEW vw_security_events_by_type AS
SELECT
    event_type,
    DATE(created_at) AS day,
    COUNT(*) AS total,
    COUNT(DISTINCT ip_address) AS distinct_ips,
    COUNT(DISTINCT email) AS distinct_emails
FROM suspicious_events
GROUP BY event_type, DATE(created_at)
ORDER BY day DESC, total DESC;

-- Atividade por usuario (resumo de uso, sem PII de outros)
CREATE OR REPLACE VIEW vw_user_activity_summary AS
SELECT
    u.id AS user_id,
    CONCAT(LEFT(u.email, 2), '***@', SUBSTRING_INDEX(u.email, '@', -1)) AS email_masked,
    (SELECT COUNT(*) FROM projects p WHERE p.owner_user_id = u.id)         AS owned_projects,
    (SELECT COUNT(*) FROM card_check_requests c WHERE c.user_id = u.id)    AS total_checks,
    (SELECT COUNT(*) FROM login_attempts la WHERE la.email = u.email AND la.success = 1) AS successful_logins,
    u.created_at
FROM users u;


-- =============================================================================
-- 4. VIEWS - MONITORAMENTO OPERACIONAL (so linhas "interessantes")
-- =============================================================================
-- Para o time de seguranca/SOC investigar incidentes. Filtros aplicados
-- direto na view para nao expor a tabela completa.

-- IPs bloqueados que continuam ativos agora
CREATE OR REPLACE VIEW vw_blocked_ips_active AS
SELECT
    ip_address,
    reason,
    blocked_until,
    created_at
FROM blocked_ips
WHERE blocked_until IS NULL OR blocked_until > NOW();

-- Suspeita de credential stuffing nas ultimas 24h
CREATE OR REPLACE VIEW vw_credential_stuffing_suspects AS
SELECT
    ip_address,
    COUNT(DISTINCT email) AS distinct_emails_tested,
    COUNT(*) AS total_attempts,
    MIN(attempted_at) AS first_attempt_at,
    MAX(attempted_at) AS last_attempt_at
FROM login_attempts
WHERE attempted_at >= NOW() - INTERVAL 24 HOUR
  AND success = 0
GROUP BY ip_address
HAVING distinct_emails_tested >= 3
ORDER BY distinct_emails_tested DESC, total_attempts DESC;

-- Resets de senha abusivos (mais de 3 pedidos em 1h pelo mesmo usuario)
CREATE OR REPLACE VIEW vw_password_reset_abuse AS
SELECT
    user_id,
    email,
    COUNT(*) AS total_requests_1h,
    MAX(created_at) AS last_request_at
FROM password_resets
WHERE created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY user_id, email
HAVING total_requests_1h >= 3
ORDER BY total_requests_1h DESC;

-- Acoes administrativas recentes (audit log filtrado)
CREATE OR REPLACE VIEW vw_admin_actions_recent AS
SELECT
    a.id,
    a.user_id,
    CONCAT(LEFT(u.email, 2), '***@', SUBSTRING_INDEX(u.email, '@', -1)) AS actor_email_masked,
    a.project_id,
    a.action_name,
    a.metadata,
    a.created_at
FROM audit_logs a
JOIN users u ON u.id = a.user_id
WHERE u.role = 'admin'
  AND a.created_at >= NOW() - INTERVAL 30 DAY
ORDER BY a.created_at DESC;


-- =============================================================================
-- 5. VIEWS - FLUXO ADMINISTRATIVO (pendencias com joins prontos)
-- =============================================================================
-- Substituem joins repetidos no PHP e evitam expor IDs sem contexto.

-- Pedidos de mudanca de role aguardando revisao
CREATE OR REPLACE VIEW vw_pending_admin_role_requests AS
SELECT
    r.id,
    r.from_role,
    r.to_role,
    r.created_at,
    requester.id    AS requested_by_user_id,
    requester.email AS requested_by_email,
    target.id       AS target_user_id,
    target.email    AS target_email,
    target.role     AS target_current_role
FROM admin_role_change_requests r
JOIN users requester ON requester.id = r.requested_by_user_id
JOIN users target    ON target.id    = r.target_user_id
WHERE r.status = 'pending'
ORDER BY r.created_at ASC;

-- Projetos aguardando aprovacao
CREATE OR REPLACE VIEW vw_pending_project_approvals AS
SELECT
    p.id,
    p.name,
    p.slug,
    p.justification,
    p.privacy_mode,
    p.created_at,
    u.id    AS owner_user_id,
    u.email AS owner_email,
    u.name  AS owner_name
FROM projects p
JOIN users u ON u.id = p.owner_user_id
WHERE p.approval_status = 'pending'
ORDER BY p.created_at ASC;


-- =============================================================================
-- 6. VIEWS LEGADAS (mantidas para compatibilidade)
-- =============================================================================

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


-- =============================================================================
-- 7. GRANTS POR PERFIL DE USUARIO (descomente apos criar os usuarios)
-- =============================================================================

-- Aplicacao web: leitura + escrita em todas as tabelas
-- GRANT SELECT, INSERT, UPDATE, DELETE
--     ON u870812724_card_leak_chec.*
--     TO 'UserAPP'@'localhost';

-- Read-only amplo: util para queries ad-hoc do dono do banco
-- GRANT SELECT
--     ON u870812724_card_leak_chec.*
--     TO 'UserReadOnly'@'localhost';

-- Analista/BI: so views mascaradas e metricas, nada das tabelas brutas
-- GRANT SELECT ON u870812724_card_leak_chec.vw_users_safe              TO 'cardleak_analyst'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_card_checks_safe        TO 'cardleak_analyst'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_telegram_links_safe     TO 'cardleak_analyst'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_admin_kpi_summary       TO 'cardleak_analyst'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_login_funnel_daily      TO 'cardleak_analyst'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_security_events_by_type TO 'cardleak_analyst'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_user_activity_summary   TO 'cardleak_analyst'@'localhost';

-- SOC/seguranca: eventos de seguranca, IPs bloqueados, abuso
-- GRANT SELECT ON u870812724_card_leak_chec.vw_security_recent_events       TO 'cardleak_soc'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_login_attempts_summary       TO 'cardleak_soc'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_blocked_ips_active           TO 'cardleak_soc'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_credential_stuffing_suspects TO 'cardleak_soc'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_password_reset_abuse         TO 'cardleak_soc'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_admin_actions_recent         TO 'cardleak_soc'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_security_events_by_type      TO 'cardleak_soc'@'localhost';
-- GRANT SELECT ON u870812724_card_leak_chec.vw_pending_admin_role_requests  TO 'cardleak_soc'@'localhost';

-- FLUSH PRIVILEGES;
