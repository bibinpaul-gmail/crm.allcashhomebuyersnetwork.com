<?php

declare(strict_types=1);

use App\Database\Mongo;

require __DIR__ . '/../bootstrap.php';

$collections = [
  'agents' => [
    ['key' => ['email' => 1], 'unique' => true],
    ['key' => ['active' => 1]],
  ],
  'contacts' => [
    ['key' => ['phone' => 1], 'unique' => true],
    ['key' => ['created_at' => -1]],
  ],
  'campaigns' => [
    ['key' => ['status' => 1]],
  ],
  'calls' => [
    ['key' => ['direction' => 1, 'started_at' => -1]],
    ['key' => ['agent_id' => 1, 'started_at' => -1]],
    ['key' => ['campaign_id' => 1, 'started_at' => -1]],
    ['key' => ['contact_phone' => 1, 'started_at' => -1]],
  ],
  'dnc' => [
    ['key' => ['phone' => 1], 'unique' => true],
  ],
  'audit_logs' => [
    ['key' => ['actor_id' => 1, 'created_at' => -1]],
  ],
];

foreach ($collections as $name => $indexes) {
  $col = Mongo::collection($name);
  foreach ($indexes as $idx) {
    $keys = $idx['key'];
    $opts = $idx;
    unset($opts['key']);
    $col->createIndex($keys, $opts);
  }
  echo "Created indexes for {$name}\n";
}

echo "Done.\n";


