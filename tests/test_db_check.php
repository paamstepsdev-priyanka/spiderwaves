<?php
require 'vendor/codeigniter4/framework/system/Test/bootstrap.php';
$db = \Config\Database::connect('default');

echo "Database: " . $db->database . " on port " . $db->port . "\n";
echo "waves count: " . $db->table('waves')->countAllResults() . "\n";
echo "waves unapproved (approved_data = '0', is_deleted = '0'): " . $db->table('waves')->where('approved_data', '0')->where('is_deleted', '0')->countAllResults() . "\n";
echo "raw_waves count: " . $db->table('raw_waves')->countAllResults() . "\n";
echo "raw_waves pending (approved_data = '0', is_deleted = '0'): " . $db->table('raw_waves')->where('approved_data', '0')->where('is_deleted', '0')->countAllResults() . "\n";
echo "process_waves count: " . $db->table('process_waves')->countAllResults() . "\n";
