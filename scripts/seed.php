<?php

declare(strict_types=1);

use App\Database\Mongo;
use App\Security\Password;
use MongoDB\BSON\UTCDateTime;

require __DIR__ . '/../bootstrap.php';

$agents = Mongo::collection('agents');
$adminEmail = 'admin@test.com';

if (!$agents->findOne(['email' => $adminEmail])) {
  $agents->insertOne([
    'name' => 'Admin User',
    'email' => $adminEmail,
    'role' => 'admin',
    'active' => true,
    'password_hash' => Password::hash('ChangeMe123!'),
    'created_at' => new UTCDateTime(time() * 1000),
  ]);
  echo "Created admin user: {$adminEmail} / ChangeMe123!\n";
} else {
  echo "Admin user already exists.\n";
}

echo "Seed complete.\n";


