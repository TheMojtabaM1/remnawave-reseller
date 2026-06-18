-- #137 forced squads (lock) on resellers, #165 plan version history. Idempotent.

ALTER TABLE resellers ADD COLUMN IF NOT EXISTS forced_squads JSON NULL;

CREATE TABLE IF NOT EXISTS plan_history (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    plan_id    INT NOT NULL,
    snapshot   JSON NOT NULL,
    action     VARCHAR(20) NOT NULL,  -- update | delete
    admin_id   INT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_plan (plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
