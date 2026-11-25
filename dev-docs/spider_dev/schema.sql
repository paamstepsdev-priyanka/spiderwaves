-- Spider: read-only copy of five UCC masters (MariaDB, needs 10.2.7+ for JSON)
-- Rules:
--   * ID is copied from UCC. NO AUTO_INCREMENT. Spider never generates IDs or private_keys.
--   * private_key is unique.
--   * No foreign keys: a conflict-skipped row must not block the rest of the sync.
--   * Only the sync service writes to these tables. Queries must filter deleted_at IS NULL.
--   * Never hard delete. Soft delete = set deleted_at (cleared again if the key reappears).

CREATE TABLE ucc_customer (
  customer_id        INT UNSIGNED NOT NULL,
  private_key        VARCHAR(50)  NOT NULL,
  customer_name      VARCHAR(50)  NOT NULL,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  ucc_created_at     DATETIME     NULL,
  ucc_updated_at     DATETIME     NULL,
  row_hash           CHAR(64)     NOT NULL,
  synced_at          DATETIME     NOT NULL,
  deleted_at         DATETIME     NULL,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_private_key (private_key),
  KEY idx_active_deleted (is_active, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ucc_mapped_customer (
  mapped_customer_id   INT UNSIGNED NOT NULL,
  private_key          VARCHAR(50)  NOT NULL,
  mapped_customer_name VARCHAR(50)  NOT NULL,
  customer_id          INT UNSIGNED NOT NULL,
  collection_id        INT UNSIGNED NOT NULL,
  wastage_id           INT UNSIGNED NOT NULL,
  purity_id            INT UNSIGNED NOT NULL,
  stone_type           VARCHAR(20)  NOT NULL,
  gold_type            VARCHAR(20)  NOT NULL,
  is_active            TINYINT(1)   NOT NULL DEFAULT 1,
  ucc_created_at       DATETIME     NULL,
  ucc_updated_at       DATETIME     NULL,
  row_hash             CHAR(64)     NOT NULL,
  synced_at            DATETIME     NOT NULL,
  deleted_at           DATETIME     NULL,
  PRIMARY KEY (mapped_customer_id),
  UNIQUE KEY uq_private_key (private_key),
  KEY idx_customer (customer_id),
  KEY idx_collection (collection_id),
  KEY idx_wastage (wastage_id),
  KEY idx_purity (purity_id),
  KEY idx_name (mapped_customer_name, customer_id, collection_id, purity_id, stone_type, gold_type),
  KEY idx_active_deleted (is_active, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ucc_wastage (
  wastage_id         INT UNSIGNED NOT NULL,
  private_key        VARCHAR(50)  NOT NULL,
  wastage            DECIMAL(5,2) NOT NULL COMMENT 'percentage',
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  row_hash           CHAR(64)     NOT NULL,
  synced_at          DATETIME     NOT NULL,
  deleted_at         DATETIME     NULL,
  PRIMARY KEY (wastage_id),
  UNIQUE KEY uq_private_key (private_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ucc_purity (
  purity_id          INT UNSIGNED NOT NULL,
  private_key        VARCHAR(50)  NOT NULL,
  purity             DECIMAL(10,2) NOT NULL,
  gold_multiplier    DECIMAL(10,3) NULL,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  ucc_created_at     DATETIME     NULL,
  ucc_updated_at     DATETIME     NULL,
  row_hash           CHAR(64)     NOT NULL,
  synced_at          DATETIME     NOT NULL,
  deleted_at         DATETIME     NULL,
  PRIMARY KEY (purity_id),
  UNIQUE KEY uq_private_key (private_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ucc_collection (
  collection_id      INT UNSIGNED NOT NULL,
  private_key        VARCHAR(50)  NOT NULL,
  parent_id          INT UNSIGNED NOT NULL DEFAULT 0,
  collection_name    VARCHAR(50)  NULL,
  shortcode          VARCHAR(10)  NULL,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  ucc_created_at     DATETIME     NULL,
  ucc_updated_at     DATETIME     NULL,
  row_hash           CHAR(64)     NOT NULL,
  synced_at          DATETIME     NOT NULL,
  deleted_at         DATETIME     NULL,
  PRIMARY KEY (collection_id),
  UNIQUE KEY uq_private_key (private_key),
  KEY idx_parent (parent_id),
  KEY idx_name (collection_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Supporting tables

CREATE TABLE ucc_sync_state (
  id             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  last_version   CHAR(64) NULL COMMENT 'master_version token of the last fully applied sync (equality only)',
  last_synced_at DATETIME NULL,
  last_error     TEXT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ucc_sync_state (id, last_version) VALUES (1, NULL);

CREATE TABLE ucc_sync_conflict (
  conflict_id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity            VARCHAR(30)  NOT NULL,
  ucc_id            INT UNSIGNED NOT NULL,
  ucc_private_key   VARCHAR(50)  NOT NULL,
  local_id          INT UNSIGNED NULL,
  local_private_key VARCHAR(50)  NULL,
  reason            VARCHAR(100) NOT NULL,
  detected_at       DATETIME     NOT NULL,
  resolved_at       DATETIME     NULL,
  PRIMARY KEY (conflict_id),
  KEY idx_open (resolved_at, entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ucc_sync_check_log (
  check_id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_by       VARCHAR(100) NOT NULL,
  run_at       DATETIME     NOT NULL,
  summary_json JSON         NOT NULL,
  PRIMARY KEY (check_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
