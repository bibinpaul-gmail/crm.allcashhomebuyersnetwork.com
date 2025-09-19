<?php
declare(strict_types=1);

use App\Config\Config;

require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');

$uri = Config::string('MONGODB_URI', '');
$db  = Config::string('MONGODB_DB', '');
$debug = (getenv('APP_DEBUG') ?: '0') !== '0';

// Redact credentials in URI display
$redUri = preg_replace('/(mongodb(?:\+srv)?:\/\/[^:]*:)[^@]+(@)/', '$1***$2', (string)$uri);

echo "PHP version: " . PHP_VERSION . "\n";
echo "APP_DEBUG: " . ($debug ? '1' : '0') . "\n";
echo "ext-mongodb loaded: " . (extension_loaded('mongodb') ? 'yes' : 'no') . "\n";
echo "MONGODB_URI: " . $redUri . "\n";
echo "MONGODB_DB:  " . $db . "\n";

// Test via mongodb/mongodb Client (Composer library)
try {
  $client = new MongoDB\Client($uri);
  // listCollections will fail fast if auth/network invalid
  $iter = $client->selectDatabase($db !== '' ? $db : 'admin')->listCollections();
  $seen = 0; foreach ($iter as $_) { $seen++; if ($seen > 1) break; }
  echo "Client connect: OK (collections_seen=" . $seen . ")\n";
} catch (Throwable $e) {
  echo "Client connect: ERROR: " . $e->getMessage() . "\n";
}

// Test via low-level Driver Manager ping
try {
  $manager = new MongoDB\Driver\Manager($uri);
  $cmd = new MongoDB\Driver\Command(['ping' => 1]);
  $dbToPing = $db !== '' ? $db : 'admin';
  $result = $manager->executeCommand($dbToPing, $cmd);
  echo "Driver ping: OK\n";
} catch (Throwable $e) {
  echo "Driver ping: ERROR: " . $e->getMessage() . "\n";
}

echo "Done.\n";


