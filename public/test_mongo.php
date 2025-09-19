<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain');
echo "PHP version: " . PHP_VERSION . "\n";
echo "Loaded php.ini: " . (php_ini_loaded_file() ?: '(none)') . "\n";
echo "ext-mongodb loaded: " . (extension_loaded('mongodb') ? 'yes' : 'no') . "\n";

try {
  $client = App\Database\Mongo::client();
  $client->selectDatabase('admin')->command(['ping' => 1]);
  echo "Ping: ok\n";
  $db = App\Database\Mongo::db();
  echo "DB name: " . getenv('MONGODB_DB') . "\n";
  $names = $db->listCollections();
  foreach ($names as $c) {
    echo "- collection: " . $c->getName() . "\n";
  }
} catch (Throwable $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
}


