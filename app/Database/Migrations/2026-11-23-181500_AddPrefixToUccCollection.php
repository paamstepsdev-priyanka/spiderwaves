<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrefixToUccCollection extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('ucc_collection') && !$this->db->fieldExists('prefix', 'ucc_collection')) {
            $this->db->query("ALTER TABLE ucc_collection ADD COLUMN prefix VARCHAR(20) NULL AFTER shortcode;");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('ucc_collection') && $this->db->fieldExists('prefix', 'ucc_collection')) {
            $this->db->query("ALTER TABLE ucc_collection DROP COLUMN prefix;");
        }
    }
}

