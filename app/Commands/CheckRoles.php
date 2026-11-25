<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckRoles extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'roles:check';
    protected $description = 'Inspect users and roles in database';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        CLI::write("Connected to: " . $db->database . " on port: " . $db->port, 'green');
        
        $users = $db->table('user')->get()->getResultArray();
        CLI::write("Found " . count($users) . " users:");
        foreach ($users as $u) {
            CLI::write(sprintf("%-15s | %-20s | %-5s | status: %s | key: %s", 
                $u['username'], 
                $u['access_level'], 
                $u['location'], 
                $u['status'], 
                substr($u['private_key'], 0, 8).'...'
            ));
        }

        // Also check if any customers exist with access_level or cust accounts
        $fieldData = $db->query("SHOW COLUMNS FROM user LIKE 'access_level'")->getRowArray();
        CLI::write("user.access_level definition: " . ($fieldData['Type'] ?? ''));

        $accounts = $db->table('mst_process')->get()->getResultArray();
        CLI::write("Accounts (mst_process):");
        foreach ($accounts as $a) {
            CLI::write(sprintf("%-5s | %-20s | type: %-8s | loss_type: %-15s | status: %s | %s", 
                $a['account_id'], $a['account_name'], $a['account_type'], $a['loss_type'] ?? '', $a['account_status'], $a['private_key']));
        }
    }
}
