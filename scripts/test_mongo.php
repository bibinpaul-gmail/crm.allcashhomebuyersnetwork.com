<?php

declare(strict_types=1);

use App\Database\Mongo;

require __DIR__ . '/../bootstrap.php';

echo "PHP version: " . PHP_VERSION . "\n";
echo "Loaded php.ini: " . (php_ini_loaded_file() ?: '(none)') . "\n";
echo "ext-mongodb loaded: " . (extension_loaded('mongodb') ? 'yes' : 'no') . "\n";

$uri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017';
$dbName = getenv('MONGODB_DB') ?: 'callcenter_crm';
echo "MONGODB_DB: {$dbName}\n";

try {
  // Ping
  $client = App\Database\Mongo::client();
  $client->selectDatabase('admin')->command(['ping' => 1]);
  echo "Ping: ok\n";

  // Check collections
  $db = App\Database\Mongo::db();
  $leadsCount = $db->selectCollection('leads')->countDocuments();
  echo "leads count: {$leadsCount}\n";
  $agentsCount = $db->selectCollection('agents')->countDocuments();
  echo "agents count: {$agentsCount}\n";
} catch (Throwable $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
  echo $e->getTraceAsString() . "\n";
  exit(1);
}

echo "All good.\n";


