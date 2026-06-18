-- Support ticketing. Idempotent.

CREATE TABLE IF NOT EXISTS tickets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id INT NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    status      VARCHAR(20) NOT NULL DEFAULT 'open',   -- open | answered | closed
    priority    VARCHAR(20) NOT NULL DEFAULT 'normal', -- low | normal | high
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    KEY idx_reseller (reseller_id),
    KEY idx_status (status),
    KEY idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT NOT NULL,
    sender_type VARCHAR(20) NOT NULL,   -- owner | reseller
    sender_id   INT NOT NULL,
    body        TEXT NOT NULL,
    created_at  DATETIME NOT NULL,
    KEY idx_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
