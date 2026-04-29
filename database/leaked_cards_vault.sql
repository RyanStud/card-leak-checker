USE u870812724_card_leak_chec;

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
