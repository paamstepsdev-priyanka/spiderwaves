<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUccWebhookLogTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ucc_webhook_log (
                log_id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                source_ip         VARCHAR(45)  NULL,
                master_version    CHAR(64)     NULL,
                is_test           TINYINT(1)   NOT NULL DEFAULT 0,
                request_payload   LONGTEXT     NULL,
                http_status       SMALLINT UNSIGNED NOT NULL DEFAULT 200,
                processing_status VARCHAR(20)  NOT NULL DEFAULT 'pending',
                error_details     TEXT         NULL,
                received_at       DATETIME     NOT NULL,
                processed_at      DATETIME     NULL,
                PRIMARY KEY (log_id),
                KEY idx_received_at (received_at),
                KEY idx_status_version (processing_status, master_version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS ucc_webhook_log;");
    }
}

