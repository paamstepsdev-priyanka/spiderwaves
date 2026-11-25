<?php
require 'vendor/autoload.php';
// Boot CI4 minimal
$paths = new Config\Paths();
require SYSTEMPATH . 'Test/bootstrap.php';
$db = \Config\Database::connect();
$users = $db->table('user')->get()->getResultArray();
echo "--- USERS IN new-spiderwaves (Port: " . $db->port . ") ---" . PHP_EOL;
foreach ($users as $u) {
    echo sprintf("%-15s | %-20s | %-5s | status: %s | key: %s\n", $u['username'], $u['access_level'], $u['location'], $u['status'], substr($u['private_key'], 0, 8).'...');
}

