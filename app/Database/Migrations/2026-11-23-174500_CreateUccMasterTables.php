<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUccMasterTables extends Migration
{
    public function up()
    {
        // 1. ucc_customer
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_customer (
                customer_id        INT UNSIGNED NOT NULL,
                private_key        VARCHAR(50)  NOT NULL,
                customer_name      VARCHAR(50)  NOT NULL,
                is_active          TINYINT(1)   NOT NULL DEFAULT 1,
                ucc_created_at     DATETIME     NULL,
                ucc_updated_at     DATETIME     NULL,
                row_hash           CHAR(64)     NOT NULL,
                synced_version     BIGINT UNSIGNED NOT NULL,
                synced_at          DATETIME     NOT NULL,
                deleted_at         DATETIME     NULL,
                deleted_in_version BIGINT UNSIGNED NULL,
                PRIMARY KEY (customer_id),
                UNIQUE KEY uq_private_key (private_key),
                KEY idx_active_deleted (is_active, deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // 2. ucc_mapped_customer
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_mapped_customer (
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
                synced_version       BIGINT UNSIGNED NOT NULL,
                synced_at            DATETIME     NOT NULL,
                deleted_at           DATETIME     NULL,
                deleted_in_version   BIGINT UNSIGNED NULL,
                PRIMARY KEY (mapped_customer_id),
                UNIQUE KEY uq_private_key (private_key),
                KEY idx_customer (customer_id),
                KEY idx_collection (collection_id),
                KEY idx_wastage (wastage_id),
                KEY idx_purity (purity_id),
                KEY idx_name (mapped_customer_name, customer_id, collection_id, purity_id, stone_type, gold_type),
                KEY idx_active_deleted (is_active, deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // 3. ucc_wastage
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_wastage (
                wastage_id         INT UNSIGNED NOT NULL,
                private_key        VARCHAR(50)  NOT NULL,
                wastage            DECIMAL(5,2) NOT NULL COMMENT 'percentage',
                is_active          TINYINT(1)   NOT NULL DEFAULT 1,
                row_hash           CHAR(64)     NOT NULL,
                synced_version     BIGINT UNSIGNED NOT NULL,
                synced_at          DATETIME     NOT NULL,
                deleted_at         DATETIME     NULL,
                deleted_in_version BIGINT UNSIGNED NULL,
                PRIMARY KEY (wastage_id),
                UNIQUE KEY uq_private_key (private_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // 4. ucc_purity
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_purity (
                purity_id          INT UNSIGNED NOT NULL,
                private_key        VARCHAR(50)  NOT NULL,
                purity             DECIMAL(10,2) NOT NULL,
                gold_multiplier    DECIMAL(10,3) NULL,
                is_active          TINYINT(1)   NOT NULL DEFAULT 1,
                ucc_created_at     DATETIME     NULL,
                ucc_updated_at     DATETIME     NULL,
                row_hash           CHAR(64)     NOT NULL,
                synced_version     BIGINT UNSIGNED NOT NULL,
                synced_at          DATETIME     NOT NULL,
                deleted_at         DATETIME     NULL,
                deleted_in_version BIGINT UNSIGNED NULL,
                PRIMARY KEY (purity_id),
                UNIQUE KEY uq_private_key (private_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // 5. ucc_collection
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_collection (
                collection_id      INT UNSIGNED NOT NULL,
                private_key        VARCHAR(50)  NOT NULL,
                parent_id          INT UNSIGNED NOT NULL DEFAULT 0,
                collection_name    VARCHAR(50)  NULL,
                shortcode          VARCHAR(10)  NULL,
                prefix             VARCHAR(20)  NULL,
                is_active          TINYINT(1)   NOT NULL DEFAULT 1,
                ucc_created_at     DATETIME     NULL,
                ucc_updated_at     DATETIME     NULL,
                row_hash           CHAR(64)     NOT NULL,
                synced_version     BIGINT UNSIGNED NOT NULL,
                synced_at          DATETIME     NOT NULL,
                deleted_at         DATETIME     NULL,
                deleted_in_version BIGINT UNSIGNED NULL,
                PRIMARY KEY (collection_id),
                UNIQUE KEY uq_private_key (private_key),
                KEY idx_parent (parent_id),
                KEY idx_name (collection_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // 6. ucc_sync_state
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_sync_state (
                id             TINYINT UNSIGNED NOT NULL DEFAULT 1,
                last_seq       BIGINT UNSIGNED NOT NULL DEFAULT 0,
                last_synced_at DATETIME NULL,
                last_error     TEXT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $count = $this->db->table('ucc_sync_state')->where('id', 1)->countAllResults();
        if ($count === 0) {
            $this->db->query("INSERT INTO ucc_sync_state (id, last_seq) VALUES (1, 0);");
        }

        // 7. ucc_sync_conflict
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_sync_conflict (
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
        ");

        // 8. ucc_sync_check_log
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_sync_check_log (
                check_id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                run_by       VARCHAR(100) NOT NULL,
                run_at       DATETIME     NOT NULL,
                summary_json JSON         NOT NULL,
                PRIMARY KEY (check_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS ucc_sync_check_log;");
        $this->db->query("DROP TABLE IF EXISTS ucc_sync_conflict;");
        $this->db->query("DROP TABLE IF EXISTS ucc_sync_state;");
        $this->db->query("DROP TABLE IF EXISTS ucc_collection;");
        $this->db->query("DROP TABLE IF EXISTS ucc_purity;");
        $this->db->query("DROP TABLE IF EXISTS ucc_wastage;");
        $this->db->query("DROP TABLE IF EXISTS ucc_mapped_customer;");
        $this->db->query("DROP TABLE IF EXISTS ucc_customer;");
    }
}

