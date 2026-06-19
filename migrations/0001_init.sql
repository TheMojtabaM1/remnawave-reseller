-- Remnawave Reseller initial schema. Idempotent: safe to re-run.
-- All money is INT Toman. All timestamps stored UTC.

CREATE TABLE IF NOT EXISTS migrations (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    filename   VARCHAR(191) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(20) NOT NULL DEFAULT 'owner',
    status        VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resellers (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    username            VARCHAR(64) NOT NULL UNIQUE,
    password_hash       VARCHAR(255) NOT NULL,
    display_name        VARCHAR(120) NOT NULL DEFAULT '',
    prefix              VARCHAR(20) NOT NULL,
    telegram_id         VARCHAR(40) NULL,
    notes               TEXT NULL,
    status              VARCHAR(20) NOT NULL DEFAULT 'active',  -- active | suspended
    access_expires_at   DATETIME NULL,
    balance             BIGINT NOT NULL DEFAULT 0,             -- signed Toman
    allow_debt          TINYINT(1) NOT NULL DEFAULT 0,
    debt_limit          BIGINT NULL,                            -- max negative; NULL = unlimited
    max_users           INT NOT NULL DEFAULT 0,                 -- 0 = unlimited
    max_users_per_day   INT NOT NULL DEFAULT 0,
    min_volume_gb       INT NOT NULL DEFAULT 0,
    max_volume_gb       INT NOT NULL DEFAULT 0,                 -- 0 = unlimited
    min_days            INT NOT NULL DEFAULT 0,
    max_days            INT NOT NULL DEFAULT 0,                 -- 0 = unlimited
    max_total_traffic_gb BIGINT NOT NULL DEFAULT 0,            -- 0 = unlimited pool
    hwid_device_limit   INT NOT NULL DEFAULT 0,
    allowed_squads      JSON NULL,
    permissions         JSON NULL,
    price_per_gb        INT NULL,
    price_per_day       INT NULL,
    discount_percent    INT NULL,
    created_at          DATETIME NOT NULL,
    UNIQUE KEY uniq_prefix (prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plans (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120) NOT NULL,
    volume_gb        INT NOT NULL,
    duration_days    INT NOT NULL,
    price            INT NOT NULL,
    allowed_squads   JSON NULL,
    hwid_limit       INT NOT NULL DEFAULT 0,
    traffic_strategy VARCHAR(20) NOT NULL DEFAULT 'NO_RESET',
    status           VARCHAR(20) NOT NULL DEFAULT 'active',
    is_trial         TINYINT(1) NOT NULL DEFAULT 0,
    trial_caps       JSON NULL,
    created_at       DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS config_templates (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120) NOT NULL,
    volume_gb        INT NOT NULL,
    duration_days    INT NOT NULL,
    squads           JSON NULL,
    hwid_limit       INT NOT NULL DEFAULT 0,
    traffic_strategy VARCHAR(20) NOT NULL DEFAULT 'NO_RESET',
    naming_pattern   VARCHAR(120) NOT NULL DEFAULT '',
    created_at       DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_tiers (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    scope        VARCHAR(20) NOT NULL DEFAULT 'global',  -- global | plan
    plan_id      INT NULL,
    min_gb       INT NOT NULL,
    price_per_gb INT NOT NULL,
    KEY idx_scope (scope, plan_id, min_gb)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configs (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id       INT NOT NULL,
    plan_id           INT NULL,
    template_id       INT NULL,
    remnawave_uuid    VARCHAR(64) NULL,
    remnawave_username VARCHAR(120) NULL,
    subscription_url  TEXT NULL,
    volume_gb         INT NOT NULL,
    duration_days     INT NOT NULL,
    per_gb_rate       INT NOT NULL DEFAULT 0,
    price_charged     INT NOT NULL DEFAULT 0,
    is_trial          TINYINT(1) NOT NULL DEFAULT 0,
    status            VARCHAR(20) NOT NULL DEFAULT 'active',  -- active|disabled|expired|deleted
    created_at        DATETIME NOT NULL,
    expires_at        DATETIME NULL,
    last_used_bytes   BIGINT NOT NULL DEFAULT 0,
    last_synced_at    DATETIME NULL,
    KEY idx_reseller (reseller_id),
    KEY idx_status (status),
    KEY idx_uuid (remnawave_uuid),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id       INT NOT NULL,
    type              VARCHAR(20) NOT NULL,  -- topup|charge|refund|manual_adjust|gift
    amount            BIGINT NOT NULL,       -- signed
    balance_after     BIGINT NOT NULL,
    related_config_id INT NULL,
    description       VARCHAR(255) NULL,
    admin_id          INT NULL,
    created_at        DATETIME NOT NULL,
    KEY idx_reseller (reseller_id),
    KEY idx_type (type),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    actor_type  VARCHAR(20) NOT NULL,  -- owner | reseller
    actor_id    INT NOT NULL,
    action      VARCHAR(80) NOT NULL,
    target_type VARCHAR(40) NULL,
    target_id   VARCHAR(64) NULL,
    details     JSON NULL,
    ip          VARCHAR(64) NULL,
    created_at  DATETIME NOT NULL,
    KEY idx_actor (actor_type, actor_id),
    KEY idx_action (action),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monthly_statements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id     INT NOT NULL,
    period          VARCHAR(7) NOT NULL,  -- YYYY-MM
    opening_balance BIGINT NOT NULL DEFAULT 0,
    closing_balance BIGINT NOT NULL DEFAULT 0,
    total_sales     BIGINT NOT NULL DEFAULT 0,
    total_refunds   BIGINT NOT NULL DEFAULT 0,
    configs_count   INT NOT NULL DEFAULT 0,
    pdf_path        VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL,
    UNIQUE KEY uniq_period (reseller_id, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alerts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(40) NOT NULL,  -- low_balance|node_down|traffic_spike|reseller_suspended
    severity   VARCHAR(20) NOT NULL DEFAULT 'info',
    message    VARCHAR(500) NOT NULL,
    target_ref VARCHAR(120) NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    KEY idx_read (is_read),
    KEY idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(80) PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
