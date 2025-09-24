<?php

declare(strict_types=1);

use App\Http\Router;
use App\Database\Mongo;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use App\Services\AuditLogger;
use App\Config\Config as AppConfig;
use App\Security\Jwt as AppJwt;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/auth.php';

send_cors_headers();

$router = new Router();

$router->add('GET', '/api/index.php/health', function () {
  json_response(['status' => 'ok', 'time' => time()]);
});

$router->add('POST', '/api/index.php/login', function () {
  api_auth_login();
});

// Current user info
$router->add('GET', '/api/index.php/me', function () {
  $claims = require_auth(['admin','supervisor','agent']);
  json_response([
    'id' => (string)($claims['sub'] ?? ''),
    'email' => (string)($claims['email'] ?? ''),
    'role' => (string)($claims['role'] ?? 'agent'),
  ]);
});

$router->add('GET', '/api/index.php/metrics', function () {
  require_auth(['admin', 'supervisor', 'agent']);
  $calls = Mongo::collection('calls');
  $since = new UTCDateTime((time() - 86400) * 1000);
  $inbound = $calls->countDocuments(['direction' => 'inbound', 'started_at' => ['$gte' => $since]]);
  $outbound = $calls->countDocuments(['direction' => 'outbound', 'started_at' => ['$gte' => $since]]);
  $agg = $calls->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => null, 'avg' => ['$avg' => '$handle_time_s']]],
  ])->toArray();
  $avg = isset($agg[0]['avg']) ? (int)round($agg[0]['avg']) : 0;
  json_response([
    'inbound_today' => (int)$inbound,
    'outbound_today' => (int)$outbound,
    'avg_handle_time_s' => $avg,
  ]);
});

// Diagnostics (admin only, returns detailed errors only if APP_DEBUG is true)
$router->add('GET', '/api/index.php/diag', function () {
  $claims = require_auth(['admin']);
  $debug = (getenv('APP_DEBUG') ?: '0') !== '0';
  $uri = App\Config\Config::string('MONGODB_URI', '');
  $dbName = App\Config\Config::string('MONGODB_DB', '');
  // Redact password in URI if present
  $redactedUri = preg_replace('/(mongodb(?:\+srv)?:\/\/[^:]*:)[^@]+(@)/', '$1***$2', $uri);
  $diag = [
    'php_version' => PHP_VERSION,
    'ext_mongodb_loaded' => extension_loaded('mongodb'),
    'mongodb_uri' => $redactedUri,
    'mongodb_db' => $dbName,
    'app_debug' => $debug,
  ];
  try {
    // Simple connectivity check
    $collections = Mongo::db()->listCollections();
    $count = 0; foreach ($collections as $_) { $count++; if ($count > 50) break; }
    $diag['mongo_connect_ok'] = true;
    $diag['collections_seen'] = $count;
  } catch (Throwable $e) {
    $diag['mongo_connect_ok'] = false;
    if ($debug) { $diag['mongo_error'] = $e->getMessage(); }
  }
  json_response($diag);
});

// Minimal CRUD examples (expand later)
$router->add('GET', '/api/index.php/contacts', function () {
  // Public read for lookups by phone or search; restrict fields
  $query = [];
  $search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
  if ($search !== '') {
    $query['$or'] = [
      ['name' => ['$regex' => $search, '$options' => 'i']],
      ['phone' => ['$regex' => preg_replace('/[^0-9\+]/', '', $search)]]
    ];
  }
  $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
  $projection = ['name' => 1, 'phone' => 1, 'created_at' => 1];
  $cursor = Mongo::collection('contacts')->find($query, ['limit' => $limit, 'sort' => ['created_at' => -1], 'projection' => $projection]);
  $items = array_map(fn($d) => [
    'id' => (string)$d['_id'],
    'name' => $d['name'] ?? '',
    'phone' => $d['phone'] ?? ''
  ], $cursor->toArray());
  json_response(['items' => $items]);
});

// Admin migration: backfill split names in script_responses.answers
$router->add('POST', '/api/index.php/migrations/split-names', function () {
  $claims = require_auth(['admin']);
  $col = Mongo::collection('script_responses');
  $filter = ['$or' => [
    ['answers.customer_first_name' => ['$exists' => false], 'answers.customer_name' => ['$type' => 'string', '$ne' => '']],
    ['answers.customer_last_name' => ['$exists' => false], 'answers.customer_name' => ['$type' => 'string', '$ne' => '']],
    ['answers.agent_first_name' => ['$exists' => false], 'answers.agent_name' => ['$type' => 'string', '$ne' => '']],
    ['answers.agent_last_name' => ['$exists' => false], 'answers.agent_name' => ['$type' => 'string', '$ne' => '']],
  ]];
  $cursor = $col->find($filter, ['projection' => ['answers' => 1]]);
  $updated = 0; $scanned = 0;
  foreach ($cursor as $doc) {
    $scanned++;
    $answers = is_array($doc['answers'] ?? null) ? $doc['answers'] : [];
    $set = [];
    // Customer split
    $custName = trim((string)($answers['customer_name'] ?? ''));
    if ($custName !== '') {
      if (!array_key_exists('customer_first_name', $answers) || $answers['customer_first_name'] === '') {
        $parts = preg_split('/\s+/', $custName);
        $set['answers.customer_first_name'] = (string)($parts[0] ?? '');
      }
      if (!array_key_exists('customer_last_name', $answers) || $answers['customer_last_name'] === '') {
        $parts = preg_split('/\s+/', $custName);
        array_shift($parts);
        $set['answers.customer_last_name'] = trim(implode(' ', $parts));
      }
    }
    // Agent split
    $agentName = trim((string)($answers['agent_name'] ?? ''));
    if ($agentName !== '') {
      if (!array_key_exists('agent_first_name', $answers) || $answers['agent_first_name'] === '') {
        $parts = preg_split('/\s+/', $agentName);
        $set['answers.agent_first_name'] = (string)($parts[0] ?? '');
      }
      if (!array_key_exists('agent_last_name', $answers) || $answers['agent_last_name'] === '') {
        $parts = preg_split('/\s+/', $agentName);
        array_shift($parts);
        $set['answers.agent_last_name'] = trim(implode(' ', $parts));
      }
    }
    if (!empty($set)) {
      $col->updateOne(['_id' => $doc['_id']], ['$set' => $set]);
      $updated++;
    }
  }
  json_response(['ok' => true, 'scanned' => $scanned, 'updated' => $updated]);
});

// Leads (from existing CRM form submissions saved into 'leads' collection)
$router->add('GET', '/api/index.php/leads', function () {
  // Public read for lookups by phone; restrict fields
  $query = [];
  $search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
  if ($search !== '') {
    $query['$or'] = [
      ['customer_name' => ['$regex' => $search, '$options' => 'i']],
      ['customer_phone' => ['$regex' => preg_replace('/[^0-9\+]/', '', $search)]],
      ['property.city' => ['$regex' => $search, '$options' => 'i']],
      ['property.state' => ['$regex' => $search, '$options' => 'i']],
    ];
  }
  $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
  $projection = ['agent_name' => 1, 'customer_name' => 1, 'customer_phone' => 1, 'property' => 1, 'created_at' => 1, 'script_meta' => 1];
  $cursor = Mongo::collection('leads')->find($query, ['limit' => $limit, 'sort' => ['created_at' => -1], 'projection' => $projection]);
  $items = array_map(function($d){
    $created = null;
    if (isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime) {
      $created = $d['created_at']->toDateTime()->format(DATE_ATOM);
    }
    $propertyCity = is_array($d['property'] ?? null) || $d['property'] instanceof ArrayAccess ? ($d['property']['city'] ?? '') : '';
    $propertyState = is_array($d['property'] ?? null) || $d['property'] instanceof ArrayAccess ? ($d['property']['state'] ?? '') : '';
    $disposition = is_array($d['script_meta'] ?? null) || $d['script_meta'] instanceof ArrayAccess ? ($d['script_meta']['disposition'] ?? '') : '';
    return [
      'id' => (string)$d['_id'],
      'agent_name' => (string)($d['agent_name'] ?? ''),
      'customer_name' => (string)($d['customer_name'] ?? ''),
      'customer_phone' => (string)($d['customer_phone'] ?? ''),
      'property_city' => (string)$propertyCity,
      'property_state' => (string)$propertyState,
      'disposition' => (string)$disposition,
      'created_at' => $created,
    ];
  }, $cursor->toArray());
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/campaigns', function () {
  require_auth(['admin', 'supervisor']);
  $cursor = Mongo::collection('campaigns')->find([], ['sort' => ['created_at' => -1]]);
  $items = array_map(fn($d) => [
    'id' => (string)$d['_id'],
    'name' => (string)($d['name'] ?? ''),
    'status' => (string)($d['status'] ?? 'draft'),
    'created_at' => isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime ? $d['created_at']->toDateTime()->format(DATE_ATOM) : null,
  ], $cursor->toArray());
  json_response(['items' => $items]);
});

// App settings (RBAC + misc) - single document pattern
$router->add('GET', '/api/index.php/settings', function () {
  // Publicly readable (used by Admin UI to enforce RBAC client-side)
  $doc = Mongo::collection('settings')->findOne(['_id' => 'app']);
  if (!$doc) { $doc = ['_id' => 'app']; }

  // Defaults
  $defaults = [
    'admin' => ['dashboard','contacts','leads','agents','scripts','campaigns','calls','dnc','reports','data','schedule','callbacks','qa-rubrics','howto','settings','geo','suppression','billing','accounts','payments','payments-admin','subscriptions-admin','magic'],
    'supervisor' => [],
    'agent' => [],
  ];
  // Coerce RBAC from Mongo (BSONDocument/ArrayAccess) to plain arrays
  $rbacRaw = $doc['rbac_allowed_views'] ?? null;
  $rbac = $defaults;
  if (is_array($rbacRaw) || ($rbacRaw instanceof \ArrayAccess) || ($rbacRaw instanceof \Traversable)) {
    $rbac = [];
    foreach (['admin','supervisor','agent'] as $role) {
      $vals = $rbacRaw[$role] ?? [];
      if ($vals instanceof \Traversable) {
        $tmp = [];
        foreach ($vals as $x) { $tmp[] = (string)$x; }
        $rbac[$role] = $tmp;
      } else {
        $rbac[$role] = array_values(array_map('strval', (array)$vals));
      }
    }
  }
  // Known views we want admins to always have, even if settings doc is stale
  $knownViews = ['dashboard','contacts','leads','agents','scripts','campaigns','calls','dnc','reports','data','schedule','callbacks','qa-rubrics','howto','settings','geo','suppression','billing','accounts','payments','payments-admin','subscriptions-admin','magic'];
  $all = [];
  foreach (['admin','supervisor','agent'] as $role) {
    foreach ((array)($rbac[$role] ?? []) as $v) { $v = (string)$v; if ($v !== '') $all[$v] = true; }
  }
  foreach ($knownViews as $v) { $all[$v] = true; }
  $rbac['admin'] = array_values(array_keys($all));

  $res = [
    'jwt_issuer' => (string)($doc['jwt_issuer'] ?? (getenv('JWT_ISSUER') ?: 'app')),
    'cors_allowed_origins' => (array)($doc['cors_allowed_origins'] ?? []),
    'rbac_allowed_views' => $rbac,
    // New: geo allowlist and phone suppression lists
    // Legacy flat lists (kept for compatibility)
    'geo_allowed_zips' => array_values(array_unique(array_map('strval', (array)($doc['geo_allowed_zips'] ?? [])))),
    'suppressed_phones' => array_values(array_unique(array_map('strval', (array)($doc['suppressed_phones'] ?? [])))),
    // New: named lists
    'geo_lists' => array_values(array_map(function($it){ return [
      'name' => (string)($it['name'] ?? ''),
      'zips' => array_values(array_map('strval', (array)($it['zips'] ?? [])))
    ]; }, (array)($doc['geo_lists'] ?? []))),
    'suppression_lists' => array_values(array_map(function($it){ return [
      'name' => (string)($it['name'] ?? ''),
      'phones' => array_values(array_map('strval', (array)($it['phones'] ?? [])))
    ]; }, (array)($doc['suppression_lists'] ?? []))),
  ];
  json_response($res);
});

$router->add('POST', '/api/index.php/settings', function () {
  $claims = require_auth(['admin']);
  $data = json_input();
  $update = [
    'updated_at' => new UTCDateTime(time()*1000),
  ];
  if (array_key_exists('jwt_issuer', $data)) $update['jwt_issuer'] = (string)$data['jwt_issuer'];
  if (array_key_exists('cors_allowed_origins', $data)) $update['cors_allowed_origins'] = is_array($data['cors_allowed_origins']) ? $data['cors_allowed_origins'] : [];
  if (array_key_exists('rbac_allowed_views', $data)) $update['rbac_allowed_views'] = is_array($data['rbac_allowed_views']) ? $data['rbac_allowed_views'] : [];
  if (array_key_exists('geo_allowed_zips', $data)) $update['geo_allowed_zips'] = is_array($data['geo_allowed_zips']) ? array_values(array_unique(array_map('strval', $data['geo_allowed_zips']))) : [];
  if (array_key_exists('suppressed_phones', $data)) $update['suppressed_phones'] = is_array($data['suppressed_phones']) ? array_values(array_unique(array_map(function($p){ return preg_replace('/[^0-9\+]/','', (string)$p); }, $data['suppressed_phones']))) : [];
  if (array_key_exists('geo_lists', $data) || array_key_exists('suppression_lists', $data)) {
    $doc = Mongo::collection('settings')->findOne(['_id' => 'app']);
    // Keep BSONDocument (ArrayAccess) intact; only fallback to empty array if truly unusable
    if (!is_array($doc) && !($doc instanceof \ArrayAccess)) { $doc = []; }
    $currentGeo = is_array($doc['geo_lists'] ?? null) ? $doc['geo_lists'] : [];
    $currentSupp = is_array($doc['suppression_lists'] ?? null) ? $doc['suppression_lists'] : [];
    if (array_key_exists('geo_lists', $data)) {
      $incoming = is_array($data['geo_lists']) ? $data['geo_lists'] : [];
      $map = [];
      foreach ($currentGeo as $it) { $k = strtolower(trim((string)($it['name'] ?? ''))); if ($k!=='') $map[$k] = ['name'=>$it['name']??'', 'zips'=>array_values(array_map('strval',(array)($it['zips']??[])))]; }
      foreach ($incoming as $it) { $k = strtolower(trim((string)($it['name'] ?? ''))); if ($k!=='') $map[$k] = ['name'=>$it['name']??'', 'zips'=>array_values(array_map('strval',(array)($it['zips']??[])))]; }
      $update['geo_lists'] = array_values($map);
    }
    if (array_key_exists('suppression_lists', $data)) {
      $incoming = is_array($data['suppression_lists']) ? $data['suppression_lists'] : [];
      $map = [];
      foreach ($currentSupp as $it) { $k = strtolower(trim((string)($it['name'] ?? ''))); if ($k!=='') $map[$k] = ['name'=>$it['name']??'', 'phones'=>array_values(array_map('strval',(array)($it['phones']??[])))]; }
      foreach ($incoming as $it) { $k = strtolower(trim((string)($it['name'] ?? ''))); if ($k!=='') $map[$k] = ['name'=>$it['name']??'', 'phones'=>array_values(array_map(function($p){ return preg_replace('/[^0-9\+]/','', (string)$p); }, (array)($it['phones']??[])))]; }
      $update['suppression_lists'] = array_values($map);
    }
  }
  Mongo::collection('settings')->updateOne(['_id' => 'app'], ['$set' => $update], ['upsert' => true]);
  AuditLogger::log((string)$claims['sub'], 'settings.update', ['fields' => array_keys($update)]);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/campaigns', function () {
  $claims = require_auth(['admin', 'supervisor']);
  $data = json_input();
  $doc = [
    'name' => (string)($data['name'] ?? ''),
    'description' => (string)($data['description'] ?? ''),
    'status' => in_array(($data['status'] ?? 'draft'), ['draft','active','paused','completed'], true) ? $data['status'] : 'draft',
    'created_at' => new UTCDateTime(time() * 1000),
  ];
  Mongo::collection('campaigns')->insertOne($doc);
  App\Services\AuditLogger::log((string)$claims['sub'], 'campaign.create', ['name' => $doc['name'], 'status' => $doc['status']]);
  json_response(['ok' => true]);
});

$router->add('PUT', '/api/index.php/campaigns/{id}', function ($id) {
  $claims = require_auth(['admin', 'supervisor']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  $data = json_input();
  $update = [];
  foreach (['name','description','status'] as $f) if (array_key_exists($f, $data)) $update[$f] = $data[$f];
  if (!$update) { json_response(['error' => 'no changes'], 400); return; }
  if (isset($update['status']) && !in_array($update['status'], ['draft','active','paused','completed'], true)) unset($update['status']);
  $update['updated_at'] = new UTCDateTime(time() * 1000);
  Mongo::collection('campaigns')->updateOne(['_id' => $oid], ['$set' => $update]);
  App\Services\AuditLogger::log((string)$claims['sub'], 'campaign.update', ['id' => $id, 'fields' => array_keys($update)]);
  json_response(['ok' => true]);
});

$router->add('DELETE', '/api/index.php/campaigns/{id}', function ($id) {
  $claims = require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  Mongo::collection('campaigns')->deleteOne(['_id' => $oid]);
  App\Services\AuditLogger::log((string)$claims['sub'], 'campaign.delete', ['id' => $id]);
  json_response(['ok' => true]);
});
$router->add('POST', '/api/index.php/contacts', function () {
  $claims = require_auth(['admin', 'supervisor', 'agent']);
  $data = json_input();
  $doc = [
    'name' => (string)($data['name'] ?? ''),
    'phone' => (string)($data['phone'] ?? ''),
    'created_at' => new UTCDateTime(time() * 1000),
  ];
  Mongo::collection('contacts')->insertOne($doc);
  AuditLogger::log((string)$claims['sub'], 'contact.create', ['name' => $doc['name'], 'phone' => $doc['phone']]);
  json_response(['ok' => true, 'id' => (string)$doc['_id'] ?? null], 201);
});

// Agents CRUD (admin only)
$router->add('GET', '/api/index.php/agents', function () {
  require_auth(['admin']);
  $cursor = Mongo::collection('agents')->find([], ['projection' => ['password_hash' => 0]]);
  $items = array_map(fn($d) => [
    'id' => (string)$d['_id'],
    'name' => $d['name'] ?? '',
    'email' => $d['email'] ?? '',
    'role' => $d['role'] ?? 'agent',
    'active' => (bool)($d['active'] ?? true),
  ], $cursor->toArray());
  json_response(['items' => $items]);
});

$router->add('POST', '/api/index.php/agents', function () {
  require_auth(['admin']);
  $data = json_input();
  $doc = [
    'name' => (string)($data['name'] ?? ''),
    'email' => strtolower(trim((string)($data['email'] ?? ''))),
    'role' => (string)($data['role'] ?? 'agent'),
    'active' => true,
    'password_hash' => App\Security\Password::hash((string)($data['password'] ?? 'TempPass123!')),
    'created_at' => new UTCDateTime(time() * 1000),
  ];
  Mongo::collection('agents')->insertOne($doc);
  AuditLogger::log((string)require_auth(['admin'])['sub'], 'agent.create', ['email' => $doc['email'], 'role' => $doc['role']]);
  json_response(['ok' => true]);
});

$router->add('PUT', '/api/index.php/agents/{id}', function ($id) {
  $claims = require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  $data = json_input();
  $update = [];
  foreach (['name','email','role','active'] as $f) if (array_key_exists($f, $data)) $update[$f] = $data[$f];
  if (isset($data['password']) && $data['password'] !== '') {
    $update['password_hash'] = App\Security\Password::hash((string)$data['password']);
  }
  if (!$update) { json_response(['error' => 'no changes'], 400); return; }
  if (isset($update['email'])) $update['email'] = strtolower(trim((string)$update['email']));
  Mongo::collection('agents')->updateOne(['_id' => $oid], ['$set' => $update]);
  AuditLogger::log((string)$claims['sub'], 'agent.update', ['id' => $id, 'fields' => array_keys($update)]);
  json_response(['ok' => true]);
});

$router->add('DELETE', '/api/index.php/agents/{id}', function ($id) {
  $claims = require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  Mongo::collection('agents')->deleteOne(['_id' => $oid]);
  AuditLogger::log((string)$claims['sub'], 'agent.delete', ['id' => $id]);
  json_response(['ok' => true]);
});

// Contact detail/update/delete
$router->add('GET', '/api/index.php/contacts/{id}', function ($id) {
  require_auth(['admin', 'supervisor', 'agent']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  $d = Mongo::collection('contacts')->findOne(['_id' => $oid]);
  if (!$d) { json_response(['error' => 'not found'], 404); return; }
  json_response([
    'id' => (string)$d['_id'],
    'name' => $d['name'] ?? '',
    'phone' => $d['phone'] ?? '',
    'created_at' => isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime ? $d['created_at']->toDateTime()->format(DATE_ATOM) : null,
  ]);
});

$router->add('PUT', '/api/index.php/contacts/{id}', function ($id) {
  $claims = require_auth(['admin', 'supervisor', 'agent']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  $data = json_input();
  $update = [];
  if (isset($data['name'])) $update['name'] = (string)$data['name'];
  if (isset($data['phone'])) $update['phone'] = preg_replace('/[^0-9\+]/', '', (string)$data['phone']);
  if (!$update) { json_response(['error' => 'no changes'], 400); return; }
  Mongo::collection('contacts')->updateOne(['_id' => $oid], ['$set' => $update]);
  AuditLogger::log((string)$claims['sub'], 'contact.update', ['id' => $id, 'fields' => array_keys($update)]);
  json_response(['ok' => true]);
});

$router->add('DELETE', '/api/index.php/contacts/{id}', function ($id) {
  $claims = require_auth(['admin', 'supervisor']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  Mongo::collection('contacts')->deleteOne(['_id' => $oid]);
  AuditLogger::log((string)$claims['sub'], 'contact.delete', ['id' => $id]);
  json_response(['ok' => true]);
});

// DNC add
$router->add('POST', '/api/index.php/dnc', function () {
  require_auth(['admin', 'supervisor']);
  $data = json_input();
  $phone = preg_replace('/[^0-9\+]/', '', (string)($data['phone'] ?? ''));
  if ($phone === '') {
    json_response(['error' => 'phone required'], 400);
    return;
  }
  Mongo::collection('dnc')->updateOne(['phone' => $phone], ['$set' => ['phone' => $phone, 'created_at' => new UTCDateTime(time() * 1000)]], ['upsert' => true]);
  json_response(['ok' => true]);
});

$router->add('GET', '/api/index.php/dnc/check', function () {
  require_auth(['admin', 'supervisor', 'agent']);
  $phone = preg_replace('/[^0-9\+]/', '', (string)($_GET['phone'] ?? ''));
  if ($phone === '') { json_response(['listed' => false]); return; }
  $found = Mongo::collection('dnc')->findOne(['phone' => $phone]);
  json_response(['listed' => (bool)$found]);
});

$router->add('GET', '/api/index.php/dnc', function () {
  require_auth(['admin', 'supervisor']);
  $limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));
  $cursor = Mongo::collection('dnc')->find([], ['limit' => $limit, 'sort' => ['created_at' => -1]]);
  $items = array_map(fn($d) => [
    'phone' => (string)($d['phone'] ?? ''),
    'created_at' => isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime ? $d['created_at']->toDateTime()->format(DATE_ATOM) : null,
  ], $cursor->toArray());
  json_response(['items' => $items]);
});

$router->add('DELETE', '/api/index.php/dnc/{phone}', function ($phone) {
  require_auth(['admin']);
  $norm = preg_replace('/[^0-9\+]/', '', (string)$phone);
  if ($norm === '') { json_response(['error' => 'invalid phone'], 400); return; }
  Mongo::collection('dnc')->deleteOne(['phone' => $norm]);
  json_response(['ok' => true]);
});

// Calls list
$router->add('GET', '/api/index.php/calls', function () {
  require_auth(['admin', 'supervisor']);
  $query = [];
  if (!empty($_GET['direction'])) $query['direction'] = $_GET['direction'];
  $limit = max(1, min(200, (int)($_GET['limit'] ?? 100))); 
  $cursor = Mongo::collection('calls')->find($query, ['limit' => $limit, 'sort' => ['started_at' => -1]]);
  $items = array_map(fn($d) => [
    'id' => (string)$d['_id'],
    'direction' => $d['direction'] ?? '',
    'agent_id' => $d['agent_id'] ?? '',
    'contact_phone' => $d['contact_phone'] ?? '',
    'is_dnc' => (bool)($d['is_dnc'] ?? false),
    'handle_time_s' => (int)($d['handle_time_s'] ?? 0),
    'started_at' => isset($d['started_at']) && $d['started_at'] instanceof UTCDateTime ? $d['started_at']->toDateTime()->format(DATE_ATOM) : null,
  ], $cursor->toArray());
  json_response(['items' => $items]);
});

// Webhook to ingest call events (direction, handle_time_s, etc.)
$router->add('POST', '/api/index.php/webhooks/call-event', function () {
  // Protected by shared secret if configured
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) {
      json_response(['error' => 'unauthorized'], 401);
      return;
    }
  }
  $data = json_input();
  $doc = [
    'direction' => in_array(($data['direction'] ?? ''), ['inbound','outbound'], true) ? $data['direction'] : 'inbound',
    'agent_id' => (string)($data['agent_id'] ?? ''),
    'campaign_id' => (string)($data['campaign_id'] ?? ''),
    'contact_phone' => preg_replace('/[^0-9\+]/', '', (string)($data['contact_phone'] ?? '')),
    'handle_time_s' => (int)($data['handle_time_s'] ?? 0),
    'started_at' => new UTCDateTime((int)(($data['started_at_ts'] ?? time()) * 1000)),
  ];
  $doc['is_dnc'] = (bool)Mongo::collection('dnc')->findOne(['phone' => $doc['contact_phone']]);
  Mongo::collection('calls')->insertOne($doc);
  json_response(['ok' => true]);
});

// Reports
$router->add('GET', '/api/index.php/reports/agent-performance', function () {
  require_auth(['admin', 'supervisor']);
  $since = new UTCDateTime((time() - 7*86400) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$agent_id', 'calls' => ['$sum' => 1], 'avg_aht' => ['$avg' => '$handle_time_s']]],
    ['$sort' => ['calls' => -1]]
  ])->toArray();
  $items = array_map(fn($r) => [
    'agent_id' => (string)($r['_id'] ?? ''),
    'calls' => (int)($r['calls'] ?? 0),
    'avg_handle_time_s' => (int)round($r['avg_aht'] ?? 0),
  ], $agg);
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/campaign-effectiveness', function () {
  require_auth(['admin', 'supervisor']);
  $since = new UTCDateTime((time() - 30*86400) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$campaign_id', 'calls' => ['$sum' => 1]]],
    ['$sort' => ['calls' => -1]]
  ])->toArray();
  $items = array_map(fn($r) => [
    'campaign_id' => (string)($r['_id'] ?? ''),
    'calls' => (int)($r['calls'] ?? 0),
  ], $agg);
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/sla', function () {
  require_auth(['admin', 'supervisor']);
  // Placeholder: return call counts per direction in last hour
  $since = new UTCDateTime((time() - 3600) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$direction', 'count' => ['$sum' => 1]]]
  ])->toArray();
  $items = array_map(fn($r) => [
    'direction' => (string)($r['_id'] ?? ''),
    'count' => (int)($r['count'] ?? 0),
  ], $agg);
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/agent-summary', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $pipe = [
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => [
      '_id' => '$agent_id',
      'calls' => ['$sum' => 1],
      'avg_handle_time_s' => ['$avg' => '$handle_time_s'],
      'dnc_hits' => ['$sum' => ['$cond' => [['$eq' => ['$is_dnc', true]], 1, 0]]]
    ]],
    ['$sort' => ['calls' => -1]]
  ];
  $agg = Mongo::collection('calls')->aggregate($pipe);
  $items = array_map(fn($d)=>[
    'agent_id' => (string)($d['_id'] ?? ''),
    'calls' => (int)($d['calls'] ?? 0),
    'avg_handle_time_s' => (int)round((float)($d['avg_handle_time_s'] ?? 0)),
    'dnc_hits' => (int)($d['dnc_hits'] ?? 0),
  ], iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/daily-volume', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => [
      '_id' => [ 'y' => ['$year' => '$started_at'], 'm' => ['$month' => '$started_at'], 'd' => ['$dayOfMonth' => '$started_at'] ],
      'calls' => ['$sum' => 1],
      'avg_handle_time_s' => ['$avg' => '$handle_time_s']
    ]],
    ['$sort' => ['_id.y' => 1, '_id.m' => 1, '_id.d' => 1]]
  ]);
  $items = array_map(function($d){
    $y = (int)($d['_id']['y'] ?? 0); $m = (int)($d['_id']['m'] ?? 0); $day = (int)($d['_id']['d'] ?? 0);
    return [
      'date' => sprintf('%04d-%02d-%02d', $y, $m, $day),
      'calls' => (int)($d['calls'] ?? 0),
      'avg_handle_time_s' => (int)round((float)($d['avg_handle_time_s'] ?? 0))
    ];
  }, iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/script-performance', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(180, (int)($_GET['since_days'] ?? 90)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  // Use leads as proxy for outcomes by script slug if present in script_meta.slug
  $agg = Mongo::collection('leads')->aggregate([
    ['$match' => ['created_at' => ['$gte' => $since]]],
    ['$group' => [ '_id' => '$script_meta.slug', 'leads' => ['$sum' => 1] ]],
    ['$sort' => ['leads' => -1]]
  ]);
  $items = array_map(fn($d)=>[
    'slug' => (string)($d['_id'] ?? ''),
    'leads' => (int)($d['leads'] ?? 0)
  ], iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/dnc-impact', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => [
      '_id' => '$is_dnc',
      'calls' => ['$sum' => 1],
      'avg_handle_time_s' => ['$avg' => '$handle_time_s']
    ]],
    ['$sort' => ['_id' => 1]]
  ]);
  $items = array_map(fn($d)=>[
    'is_dnc' => (bool)($d['_id'] ?? false),
    'calls' => (int)($d['calls'] ?? 0),
    'avg_handle_time_s' => (int)round((float)($d['avg_handle_time_s'] ?? 0))
  ], iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/hourly-heatmap', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(30, (int)($_GET['since_days'] ?? 7)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => [
      '_id' => [ 'dow' => ['$dayOfWeek' => '$started_at'], 'h' => ['$hour' => '$started_at'] ],
      'calls' => ['$sum' => 1]
    ]],
    ['$sort' => ['_id.dow' => 1, '_id.h' => 1]]
  ]);
  $items = array_map(fn($d)=>[
    'dow' => (int)($d['_id']['dow'] ?? 0),
    'hour' => (int)($d['_id']['h'] ?? 0),
    'calls' => (int)($d['calls'] ?? 0)
  ], iterator_to_array($agg));
  json_response(['items' => $items]);
});

// Scripts CRUD (admin)
$router->add('GET', '/api/index.php/scripts', function () {
  require_auth(['admin', 'supervisor']);
  $cursor = Mongo::collection('scripts')->find([], ['sort' => ['updated_at' => -1]]);
  $items = array_map(fn($d) => [
    'id' => (string)$d['_id'],
    'slug' => (string)($d['slug'] ?? ''),
    'title' => (string)($d['title'] ?? ''),
    'geo_list' => (string)($d['geo_list'] ?? ''),
    'suppression_list' => (string)($d['suppression_list'] ?? ''),
    'geo_mode' => (string)($d['geo_mode'] ?? 'allow'),
    'published' => (bool)($d['published'] ?? false),
    'version' => (int)($d['version'] ?? 1),
    'updated_at' => isset($d['updated_at']) && $d['updated_at'] instanceof UTCDateTime ? $d['updated_at']->toDateTime()->format(DATE_ATOM) : null,
  ], $cursor->toArray());
  json_response(['items' => $items]);
});

$router->add('POST', '/api/index.php/scripts', function () {
  $claims = require_auth(['admin']);
  $data = json_input();
  $doc = [
    'slug' => strtolower(trim((string)($data['slug'] ?? ''))),
    'title' => (string)($data['title'] ?? ''),
    'intro' => (string)($data['intro'] ?? ''),
    'geo_list' => (string)($data['geo_list'] ?? ''),
    'suppression_list' => (string)($data['suppression_list'] ?? ''),
    'geo_mode' => in_array(($data['geo_mode'] ?? 'allow'), ['allow','deny'], true) ? $data['geo_mode'] : 'allow',
    'sections' => is_array($data['sections'] ?? null) ? $data['sections'] : [],
    'published' => (bool)($data['published'] ?? false),
    'version' => (int)($data['version'] ?? 0),
    'updated_at' => new UTCDateTime(time() * 1000),
  ];
  if ($doc['slug'] === '' || $doc['title'] === '') { json_response(['error' => 'slug and title required'], 400); return; }
  $existing = Mongo::collection('scripts')->findOne(['slug' => $doc['slug']]);
  if ($existing) {
    $doc['version'] = (int)(($existing['version'] ?? 0) + 1);
  } else {
    $doc['version'] = 1;
    $doc['created_at'] = new UTCDateTime(time() * 1000);
  }
  Mongo::collection('scripts')->updateOne(['slug' => $doc['slug']], ['$set' => $doc], ['upsert' => true]);
  AuditLogger::log((string)$claims['sub'], 'script.upsert', ['slug' => $doc['slug']]);
  json_response(['ok' => true]);
});

$router->add('GET', '/api/index.php/scripts/{id}', function ($id) {
  require_auth(['admin', 'supervisor']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  $d = Mongo::collection('scripts')->findOne(['_id' => $oid]);
  if (!$d) { json_response(['error' => 'not found'], 404); return; }
  $d['id'] = (string)$d['_id']; unset($d['_id']);
  json_response($d);
});

$router->add('PUT', '/api/index.php/scripts/{id}', function ($id) {
  $claims = require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  $data = json_input();
  $update = [];
  foreach (['slug','title','sections','intro','geo_list','suppression_list','geo_mode'] as $f) if (array_key_exists($f, $data)) $update[$f] = $data[$f];
  if (!$update) { json_response(['error' => 'no changes'], 400); return; }
  Mongo::collection('scripts')->updateOne(['_id' => $oid], ['$set' => $update]);
  AuditLogger::log((string)$claims['sub'], 'script.update', ['id' => $id]);
  json_response(['ok' => true]);
});

$router->add('DELETE', '/api/index.php/scripts/{id}', function ($id) {
  $claims = require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  Mongo::collection('scripts')->deleteOne(['_id' => $oid]);
  AuditLogger::log((string)$claims['sub'], 'script.delete', ['id' => $id]);
  json_response(['ok' => true]);
});

// Fetch by slug (agents)
$router->add('GET', '/api/index.php/scripts/slug/{slug}', function ($slug) {
  // Optional auth: if token provided, verify and allow roles; otherwise allow public access to published scripts only
  $claims = null;
  do {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($hdr === '' && function_exists('getallheaders')) {
      foreach (getallheaders() as $k => $v) { if (strcasecmp($k, 'Authorization') === 0) { $hdr = (string)$v; break; } }
    }
    if (!preg_match('/Bearer\s+(.+)/i', $hdr, $m)) break;
    try { $claims = \App\Security\Jwt::verify($m[1]); } catch (\Throwable $e) { $claims = null; }
  } while (false);

  $filter = ['slug' => (string)$slug];
  // Without auth or with agent role, only published scripts are visible
  if ($claims === null || (($claims['role'] ?? 'agent') === 'agent')) {
    $filter['published'] = true;
  }
  $d = Mongo::collection('scripts')->findOne($filter);
  if (!$d) { json_response(['error' => 'not found'], 404); return; }
  $d['id'] = (string)$d['_id']; unset($d['_id']);
  json_response($d);
});

// Publish/unpublish by id
$router->add('POST', '/api/index.php/scripts/{id}/publish', function ($id) {
  $claims = require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  $data = json_input();
  $published = (bool)($data['published'] ?? true);
  Mongo::collection('scripts')->updateOne(['_id' => $oid], ['$set' => ['published' => $published, 'updated_at' => new UTCDateTime(time()*1000)]]);
  AuditLogger::log((string)$claims['sub'], 'script.publish', ['id' => $id, 'published' => $published]);
  json_response(['ok' => true]);
});

// Agent notes per script section
$router->add('GET', '/api/index.php/script-notes', function () {
  $claims = require_auth(['admin', 'supervisor', 'agent']);
  $slug = (string)($_GET['slug'] ?? '');
  if ($slug === '') { json_response(['items' => []]); return; }
  $cursor = Mongo::collection('script_notes')->find(['slug' => $slug, 'agent_id' => (string)$claims['sub']], ['sort' => ['updated_at' => -1]]);
  $items = array_map(fn($d) => [
    'id' => (string)$d['_id'],
    'section_key' => (string)($d['section_key'] ?? ''),
    'note' => (string)($d['note'] ?? ''),
    'updated_at' => isset($d['updated_at']) && $d['updated_at'] instanceof UTCDateTime ? $d['updated_at']->toDateTime()->format(DATE_ATOM) : null,
  ], $cursor->toArray());
  json_response(['items' => $items]);
});

$router->add('POST', '/api/index.php/script-notes', function () {
  $claims = require_auth(['admin', 'supervisor', 'agent']);
  $data = json_input();
  $slug = (string)($data['slug'] ?? '');
  $section = (string)($data['section_key'] ?? '');
  $note = (string)($data['note'] ?? '');
  if ($slug === '' || $section === '') { json_response(['error' => 'slug and section_key required'], 400); return; }
  Mongo::collection('script_notes')->updateOne(
    ['slug' => $slug, 'section_key' => $section, 'agent_id' => (string)$claims['sub']],
    ['$set' => ['note' => $note, 'updated_at' => new UTCDateTime(time() * 1000)]],
    ['upsert' => true]
  );
  json_response(['ok' => true]);
});

// Script responses (runtime capture)
$router->add('POST', '/api/index.php/script-responses', function () {
  // Optional auth: allow public submissions; capture claims if provided
  $claims = [];
  try {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($hdr === '' && function_exists('getallheaders')) {
      foreach (getallheaders() as $k => $v) { if (strcasecmp($k, 'Authorization') === 0) { $hdr = (string)$v; break; } }
    }
    if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $claims = App\Security\Jwt::verify($m[1]); }
  } catch (Throwable $e) { $claims = []; }
  $data = json_input();
  $slug = (string)($data['slug'] ?? '');
  $answers = is_array($data['answers'] ?? null) ? $data['answers'] : [];
  if ($slug === '') { json_response(['error' => 'slug required'], 400); return; }
  $res = Mongo::collection('script_responses')->insertOne([
    'slug' => $slug,
    'answers' => $answers,
    'agent_id' => (string)($claims['sub'] ?? ''),
    'created_at' => new UTCDateTime(time() * 1000)
  ]);
  // Also persist to contacts and leads so Admin tabs show data
  try {
    // Build customer full name from split fields if provided
    $customerFirst = trim((string)($answers['customer_first_name'] ?? ''));
    $customerLast = trim((string)($answers['customer_last_name'] ?? ''));
    $customerName = trim((string)($answers['customer_name'] ?? ''));
    if ($customerName === '') {
      $tmp = trim($customerFirst . ' ' . $customerLast);
      if ($tmp !== '') $customerName = $tmp;
    }
    $customerPhone = preg_replace('/[^0-9\+]/', '', (string)($answers['customer_phone'] ?? ''));
    $propertyCity = (string)($answers['property_city'] ?? '');
    $propertyState = (string)($answers['property_state'] ?? '');
    // Build agent full name from split fields or fallback
    $agentFirst = trim((string)($answers['agent_first_name'] ?? ''));
    $agentLast = trim((string)($answers['agent_last_name'] ?? ''));
    $agentName = trim((string)($answers['agent_name'] ?? ''));
    if ($agentName === '') {
      $tmpA = trim($agentFirst . ' ' . $agentLast);
      if ($tmpA !== '') $agentName = $tmpA;
    }
    if ($agentName === '') { $agentName = (string)($claims['email'] ?? ''); }
    if ($customerPhone !== '') {
      // Upsert contact by phone
      Mongo::collection('contacts')->updateOne(
        ['phone' => $customerPhone],
        ['$set' => ['name' => $customerName, 'phone' => $customerPhone], '$setOnInsert' => ['created_at' => new UTCDateTime(time()*1000)]],
        ['upsert' => true]
      );
      // Insert lead row (append-only minimal projection)
      Mongo::collection('leads')->insertOne([
        'agent_name' => $agentName,
        'customer_name' => $customerName,
        'customer_phone' => $customerPhone,
        'property' => ['city' => $propertyCity, 'state' => $propertyState],
        'script_meta' => ['slug' => $slug],
        'created_at' => new UTCDateTime(time()*1000),
      ]);
    }
  } catch (\Throwable $e) { /* ignore enrichment errors */ }
  json_response(['ok' => true, 'id' => (string)$res->getInsertedId()]);
});

// Lookup last answers by phone (and optional slug)
$router->add('GET', '/api/index.php/script-responses/lookup', function () {
  // Public read for last answers by phone
  $phone = preg_replace('/[^0-9\+]/', '', (string)($_GET['phone'] ?? ''));
  $slug = (string)($_GET['slug'] ?? '');
  if ($phone === '') { json_response(['error' => 'phone required'], 400); return; }
  // Match by either canonical or legacy phone key
  $filter = [
    '$or' => [
      ['answers.customer_phone' => $phone],
      ['answers.best_callback_number' => $phone],
    ]
  ];
  if ($slug !== '') { $filter['slug'] = $slug; }
  $doc = Mongo::collection('script_responses')->findOne($filter, ['sort' => ['created_at' => -1]]);
  if (!$doc) { json_response(['answers' => null]); return; }
  json_response(['answers' => $doc['answers'] ?? null, 'slug' => (string)($doc['slug'] ?? '')]);
});

// Verify saved responses (admin/supervisor)
$router->add('GET', '/api/index.php/script-responses', function () {
  $claims = require_auth(['admin','supervisor']);
  $slug = (string)($_GET['slug'] ?? '');
  $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
  $filter = $slug ? ['slug' => $slug] : [];
  $cursor = Mongo::collection('script_responses')->find($filter, ['limit' => $limit, 'sort' => ['created_at' => -1]]);
  $items = array_map(function($d){
    return [
      'id' => (string)$d['_id'],
      'slug' => (string)($d['slug'] ?? ''),
      'agent_id' => (string)($d['agent_id'] ?? ''),
      'created_at' => isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime ? $d['created_at']->toDateTime()->format(DATE_ATOM) : null,
    ];
  }, $cursor->toArray());
  json_response(['items' => $items]);
});

// Script templates (reusable question templates)
$router->add('GET', '/api/index.php/script-templates', function () {
  require_auth(['admin', 'supervisor']);
  $filter = [];
  if (!empty($_GET['category'])) $filter['category'] = (string)$_GET['category'];
  $cursor = Mongo::collection('script_templates')->find($filter, ['sort' => ['updated_at' => -1]]);
  $items = array_map(function($d){
    return [
      'id' => (string)$d['_id'],
      'name' => (string)($d['name'] ?? ''),
      'category' => (string)($d['category'] ?? ''),
      'question' => $d['question'] ?? [],
      'updated_at' => isset($d['updated_at']) && $d['updated_at'] instanceof UTCDateTime ? $d['updated_at']->toDateTime()->format(DATE_ATOM) : null,
    ];
  }, $cursor->toArray());
  json_response(['items' => $items]);
});

$router->add('POST', '/api/index.php/script-templates', function () {
  $claims = require_auth(['admin']);
  $data = json_input();
  $name = trim((string)($data['name'] ?? ''));
  $category = trim((string)($data['category'] ?? 'General'));
  $question = is_array($data['question'] ?? null) ? $data['question'] : null;
  if ($name === '' || $question === null) { json_response(['error' => 'name and question required'], 400); return; }
  $doc = [
    'name' => $name,
    'category' => $category,
    'question' => $question,
    'updated_at' => new UTCDateTime(time() * 1000),
  ];
  // Upsert by name+category
  Mongo::collection('script_templates')->updateOne(['name' => $name, 'category' => $category], ['$set' => $doc], ['upsert' => true]);
  AuditLogger::log((string)$claims['sub'], 'template.upsert', ['name' => $name, 'category' => $category]);
  json_response(['ok' => true]);
});

$router->add('DELETE', '/api/index.php/script-templates/{id}', function ($id) {
  $claims = require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error' => 'invalid id'], 400); return; }
  Mongo::collection('script_templates')->deleteOne(['_id' => $oid]);
  AuditLogger::log((string)$claims['sub'], 'template.delete', ['id' => $id]);
  json_response(['ok' => true]);
});

$router->add('GET', '/api/index.php/reports/script-responses/summary', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(365, (int)($_GET['since_days'] ?? 90)));
  $slug = (string)($_GET['slug'] ?? '');
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $match = ['created_at' => ['$gte' => $since]];
  if ($slug !== '') $match['slug'] = $slug;
  $agg = Mongo::collection('script_responses')->aggregate([
    ['$match' => $match],
    ['$group' => [
      '_id' => [ 'slug' => '$slug', 'y' => ['$year' => '$created_at'], 'm' => ['$month' => '$created_at'], 'd' => ['$dayOfMonth' => '$created_at'] ],
      'responses' => ['$sum' => 1]
    ]],
    ['$sort' => ['_id.slug' => 1, '_id.y' => 1, '_id.m' => 1, '_id.d' => 1]]
  ]);
  $items = array_map(function($d){
    $y=(int)($d['_id']['y']??0); $m=(int)($d['_id']['m']??0); $day=(int)($d['_id']['d']??0);
    return [
      'slug' => (string)($d['_id']['slug'] ?? ''),
      'date' => sprintf('%04d-%02d-%02d', $y, $m, $day),
      'responses' => (int)($d['responses'] ?? 0)
    ];
  }, iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/script-responses/distribution', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(365, (int)($_GET['since_days'] ?? 90)));
  $slug = (string)($_GET['slug'] ?? '');
  $key = (string)($_GET['key'] ?? '');
  if ($key === '') { json_response(['error' => 'key required'], 400); return; }
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $match = ['created_at' => ['$gte' => $since], ('answers.' . $key) => ['$exists' => true]];
  if ($slug !== '') $match['slug'] = $slug;
  $groupField = '$answers.' . $key;
  $agg = Mongo::collection('script_responses')->aggregate([
    ['$match' => $match],
    ['$group' => ['_id' => $groupField, 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
  ]);
  $items = array_map(fn($d)=>[
    'value' => is_scalar($d['_id'] ?? null) ? (string)$d['_id'] : json_encode($d['_id']),
    'count' => (int)($d['count'] ?? 0)
  ], iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/script-responses/coverage', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(365, (int)($_GET['since_days'] ?? 90)));
  $slug = (string)($_GET['slug'] ?? '');
  $keys = isset($_GET['keys']) ? explode(',', (string)$_GET['keys']) : [];
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $match = ['created_at' => ['$gte' => $since]];
  if ($slug !== '') $match['slug'] = $slug;
  $cursor = Mongo::collection('script_responses')->find($match, ['projection' => ['answers' => 1]]);
  $tot = 0; $counts = array_fill_keys($keys, 0);
  foreach ($cursor as $doc) {
    $tot++;
    $ans = is_array($doc['answers'] ?? null) ? $doc['answers'] : [];
    foreach ($keys as $k) {
      if (array_key_exists($k, $ans) && (string)$ans[$k] !== '') $counts[$k]++;
    }
  }
  $items = [];
  foreach ($counts as $k=>$v) { $items[] = ['key' => $k, 'filled' => (int)$v, 'total' => (int)$tot, 'pct' => $tot ? round(100.0*$v/$tot, 1) : 0]; }
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/script-responses/all', function(){
  require_auth(['admin','supervisor']);
  $slug = (string)($_GET['slug'] ?? '');
  $sinceDays = max(1, min(365, (int)($_GET['since_days'] ?? 30)));
  $limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));
  $after = (string)($_GET['after'] ?? '');
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $filter = ['created_at' => ['$gte' => $since]];
  if ($slug !== '') $filter['slug'] = $slug;
  if ($after !== '') { try { $filter['_id'] = ['$gt' => new ObjectId($after)]; } catch (Throwable $e) {} }
  $cursor = Mongo::collection('script_responses')->find($filter, ['limit' => $limit, 'sort' => ['_id' => 1]]);
  $items = [];
  foreach ($cursor as $d) {
    $items[] = [
      'id' => (string)$d['_id'],
      'slug' => (string)($d['slug'] ?? ''),
      'created_at' => isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime ? $d['created_at']->toDateTime()->format(DATE_ATOM) : null,
      'answers' => $d['answers'] ?? []
    ];
  }
  $next = end($items)['id'] ?? null;
  json_response(['items' => $items, 'next' => $next]);
});

$router->add('GET', '/api/index.php/reports/script-responses/export.csv', function(){
  require_auth(['admin','supervisor']);
  $slug = (string)($_GET['slug'] ?? '');
  $start = (string)($_GET['start'] ?? '');
  $end = (string)($_GET['end'] ?? '');
  $agent = trim((string)($_GET['agent'] ?? ''));
  $q = trim((string)($_GET['q'] ?? ''));
  $filter = [];
  if ($slug !== '') $filter['slug'] = $slug;
  // Date range
  $range = [];
  if ($start !== '') { $ts = strtotime($start.' 00:00:00'); if ($ts) $range['$gte'] = new UTCDateTime($ts*1000); }
  if ($end !== '') { $ts = strtotime($end.' 23:59:59'); if ($ts) $range['$lte'] = new UTCDateTime($ts*1000); }
  if ($range) $filter['created_at'] = $range;
  if ($agent !== '') {
    // Match either agent_id or answers.agent_name contains
    $filter['$or'] = [ ['agent_id' => $agent], ['answers.agent_name' => ['$regex' => $agent, '$options' => 'i']] ];
  }
  // Stream headers
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="script-responses.csv"');
  $out = fopen('php://output', 'w');
  // Build dynamic columns in first pass (limit memory: scan first N docs)
  $cursor = Mongo::collection('script_responses')->find($filter, ['sort' => ['created_at' => 1]]);
  $keys = [];
  $buffer = [];
  foreach ($cursor as $d) {
    $answers = is_array($d['answers'] ?? null) ? $d['answers'] : [];
    if ($q !== '') { $found=false; foreach ($answers as $k=>$v){ if (stripos((string)$v, $q) !== false) { $found=true; break; } } if (!$found) continue; }
    foreach (array_keys($answers) as $k) { $keys[$k] = true; }
    $buffer[] = $d; if (count($keys) > 2000 || count($buffer) > 1000) break; // safety caps
  }
  $keys = array_keys($keys);
  $header = array_merge(['created_at','slug'], $keys);
  fputcsv($out, $header);
  $writeRow = function($d) use ($out, $keys){
    $row = [];
    $row[] = isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime ? $d['created_at']->toDateTime()->format(DATE_ATOM) : '';
    $row[] = (string)($d['slug'] ?? '');
    $answers = is_array($d['answers'] ?? null) ? $d['answers'] : [];
    foreach ($keys as $k) {
      $v = $answers[$k] ?? '';
      $row[] = is_scalar($v) ? (string)$v : json_encode($v);
    }
    fputcsv($out, $row);
  };
  foreach ($buffer as $d) { $writeRow($d); }
  // Continue streaming remaining
  $filter2 = $filter;
  if (!empty($buffer)) { $last = end($buffer); $filter2['_id'] = ['$gt' => $last['_id']]; }
  $cursor2 = Mongo::collection('script_responses')->find($filter2, ['sort' => ['_id' => 1]]);
  foreach ($cursor2 as $d) { $writeRow($d); }
  fclose($out);
  exit;
});

$router->add('GET', '/api/index.php/reports/agent-leaderboard', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(180, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  // Gather times per agent
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => [
      '_id' => '$agent_id',
      'calls' => ['$sum' => 1],
      'times' => ['$push' => '$handle_time_s']
    ]],
    ['$sort' => ['calls' => -1]]
  ]);
  $items = [];
  foreach (iterator_to_array($agg) as $d) {
    $times = array_values(array_map('intval', (array)($d['times'] ?? [])));
    sort($times);
    $n = max(1, count($times));
    $avg = (int)round(array_sum($times) / $n);
    $p50 = (int)$times[(int)floor(0.5*($n-1))];
    $p90 = (int)$times[(int)floor(0.9*($n-1))];
    $items[] = [
      'agent_id' => (string)($d['_id'] ?? ''),
      'calls' => (int)($d['calls'] ?? 0),
      'avg_handle_time_s' => $avg,
      'p50_handle_time_s' => $p50,
      'p90_handle_time_s' => $p90,
    ];
  }
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/dnc-trend', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(180, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => [
      '_id' => [ 'y' => ['$year' => '$started_at'], 'm' => ['$month' => '$started_at'], 'd' => ['$dayOfMonth' => '$started_at'], 'is_dnc' => '$is_dnc' ],
      'calls' => ['$sum' => 1]
    ]],
    ['$sort' => ['_id.y' => 1, '_id.m' => 1, '_id.d' => 1, '_id.is_dnc' => 1]]
  ]);
  $items = array_map(function($d){
    $y=(int)($d['_id']['y']??0); $m=(int)($d['_id']['m']??0); $day=(int)($d['_id']['d']??0);
    return [
      'date' => sprintf('%04d-%02d-%02d', $y, $m, $day),
      'is_dnc' => (bool)($d['_id']['is_dnc'] ?? false),
      'calls' => (int)($d['calls'] ?? 0)
    ];
  }, iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/script-effectiveness', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(365, (int)($_GET['since_days'] ?? 90)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  // Responses per slug
  $respAgg = Mongo::collection('script_responses')->aggregate([
    ['$match' => ['created_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$slug', 'responses' => ['$sum' => 1]]]
  ]);
  $resp = [];
  foreach (iterator_to_array($respAgg) as $r) { $resp[(string)($r['_id'] ?? '')] = (int)($r['responses'] ?? 0); }
  // Leads per slug (if script_meta.slug set)
  $leadAgg = Mongo::collection('leads')->aggregate([
    ['$match' => ['created_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$script_meta.slug', 'leads' => ['$sum' => 1]]]
  ]);
  $items = [];
  $leadMap = [];
  foreach (iterator_to_array($leadAgg) as $l) { $leadMap[(string)($l['_id'] ?? '')] = (int)($l['leads'] ?? 0); }
  $slugs = array_unique(array_merge(array_keys($resp), array_keys($leadMap)));
  foreach ($slugs as $slug) {
    $responses = (int)($resp[$slug] ?? 0);
    $leads = (int)($leadMap[$slug] ?? 0);
    $conv = $responses > 0 ? round(100.0 * $leads / $responses, 1) : 0.0;
    $items[] = ['slug' => $slug, 'responses' => $responses, 'leads' => $leads, 'conversion_pct' => $conv];
  }
  usort($items, fn($a,$b)=>($b['leads']<=>$a['leads']));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/campaign-pacing', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $goalPerDay = max(0, (int)($_GET['goal_per_day'] ?? 0));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => [
      '_id' => [ 'campaign_id' => '$campaign_id', 'y' => ['$year' => '$started_at'], 'm' => ['$month' => '$started_at'], 'd' => ['$dayOfMonth' => '$started_at'] ],
      'calls' => ['$sum' => 1]
    ]],
    ['$sort' => ['_id.campaign_id' => 1, '_id.y' => 1, '_id.m' => 1, '_id.d' => 1]]
  ]);
  $rows = iterator_to_array($agg);
  $items = [];
  foreach ($rows as $r) {
    $y=(int)($r['_id']['y']??0); $m=(int)($r['_id']['m']??0); $d=(int)($r['_id']['d']??0);
    $date = sprintf('%04d-%02d-%02d', $y, $m, $d);
    $cid = (string)($r['_id']['campaign_id'] ?? '');
    $calls = (int)($r['calls'] ?? 0);
    $ach = ($goalPerDay > 0) ? round(100.0 * $calls / $goalPerDay, 1) : null;
    $items[] = ['campaign_id' => $cid, 'date' => $date, 'calls' => $calls, 'goal_per_day' => $goalPerDay ?: null, 'achieved_pct' => $ach];
  }
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/disposition-analysis', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(365, (int)($_GET['since_days'] ?? 90)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $by = (string)($_GET['by'] ?? 'disposition'); // 'disposition' or 'agent_name'
  $groupField = $by === 'agent_name' ? '$agent_name' : '$disposition';
  $agg = Mongo::collection('leads')->aggregate([
    ['$match' => ['created_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => $groupField, 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
  ]);
  $items = array_map(fn($d)=>[
    'value' => (string)($d['_id'] ?? ''),
    'count' => (int)($d['count'] ?? 0)
  ], iterator_to_array($agg));
  json_response(['items' => $items]);
});

// Additional Webhooks for extended reporting
$router->add('POST', '/api/index.php/webhooks/staffing', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'agent_id' => (string)($d['agent_id'] ?? ''),
    'state' => (string)($d['state'] ?? ''), // staffed, aux, break
    'ts' => new UTCDateTime(((int)($d['ts'] ?? time())) * 1000),
  ];
  Mongo::collection('staffing_events')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/schedule', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'agent_id' => (string)($d['agent_id'] ?? ''),
    'shift_start' => new UTCDateTime(((int)($d['shift_start_ts'] ?? time())) * 1000),
    'shift_end' => new UTCDateTime(((int)($d['shift_end_ts'] ?? time())) * 1000),
  ];
  Mongo::collection('schedules')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/callback', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'callback_id' => (string)($d['id'] ?? ''),
    'agent_id' => (string)($d['agent_id'] ?? ''),
    'due' => new UTCDateTime(((int)($d['due_ts'] ?? time())) * 1000),
    'completed' => isset($d['completed_ts']) ? new UTCDateTime(((int)$d['completed_ts']) * 1000) : null,
  ];
  Mongo::collection('callbacks')->updateOne(['callback_id' => $doc['callback_id'] ?: md5(json_encode($doc))], ['$set' => $doc], ['upsert' => true]);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/queue-event', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'call_id' => (string)($d['call_id'] ?? ''),
    'queue' => (string)($d['queue'] ?? ''),
    'event' => (string)($d['event'] ?? ''), // enqueued, answered, abandoned
    'ts' => new UTCDateTime(((int)($d['ts'] ?? time())) * 1000),
  ];
  Mongo::collection('queue_events')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/dial-result', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'call_id' => (string)($d['call_id'] ?? ''),
    'result' => (string)($d['result'] ?? ''), // connected, rna, busy, fail
    'ts' => new UTCDateTime(((int)($d['ts'] ?? time())) * 1000),
  ];
  Mongo::collection('dial_results')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/resolution', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'call_id' => (string)($d['call_id'] ?? ''),
    'resolved' => (bool)($d['resolved'] ?? false),
    'ts' => new UTCDateTime(((int)($d['ts'] ?? time())) * 1000),
  ];
  Mongo::collection('resolutions')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/qa', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'call_id' => (string)($d['call_id'] ?? ''),
    'agent_id' => (string)($d['agent_id'] ?? ''),
    'score' => (float)($d['score'] ?? 0),
    'rubric' => $d['rubric'] ?? null,
    'ts' => new UTCDateTime(((int)($d['ts'] ?? time())) * 1000),
  ];
  Mongo::collection('qa')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/funnel', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'lead_id' => (string)($d['lead_id'] ?? ''),
    'stage' => (string)($d['stage'] ?? ''), // contacted, qualified, appointment, offer, deal
    'ts' => new UTCDateTime(((int)($d['ts'] ?? time())) * 1000),
  ];
  Mongo::collection('funnel_events')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('POST', '/api/index.php/webhooks/geo-check', function () {
  $expected = getenv('WEBHOOK_SECRET') ?: '';
  if ($expected !== '') {
    $provided = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if (!hash_equals($expected, $provided)) { json_response(['error' => 'unauthorized'], 401); return; }
  }
  $d = json_input();
  $doc = [
    'call_id' => (string)($d['call_id'] ?? ''),
    'phone_geo' => (string)($d['phone_geo'] ?? ''),
    'allowed' => (bool)($d['allowed'] ?? false),
    'ts' => new UTCDateTime(((int)($d['ts'] ?? time())) * 1000),
  ];
  Mongo::collection('geo_checks')->insertOne($doc);
  json_response(['ok' => true]);
});

$router->add('GET', '/api/index.php/reports/schedule-adherence', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(30, (int)($_GET['since_days'] ?? 7)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  // Approx adherence = staffed time during scheduled windows / scheduled time
  $schedules = iterator_to_array(Mongo::collection('schedules')->find(['shift_start' => ['$gte' => $since]]));
  $byAgent = [];
  foreach ($schedules as $s) {
    $aid = (string)($s['agent_id'] ?? '');
    $sch = max(0, (($s['shift_end']->toDateTime()->getTimestamp() ?? 0) - ($s['shift_start']->toDateTime()->getTimestamp() ?? 0)));
    if (!isset($byAgent[$aid])) $byAgent[$aid] = ['scheduled_s' => 0, 'staffed_s' => 0];
    $byAgent[$aid]['scheduled_s'] += $sch;
  }
  $events = iterator_to_array(Mongo::collection('staffing_events')->find(['ts' => ['$gte' => $since]], ['sort' => ['agent_id' => 1, 'ts' => 1]]));
  $last = [];
  foreach ($events as $e) {
    $aid = (string)($e['agent_id'] ?? '');
    $state = (string)($e['state'] ?? '');
    $ts = $e['ts']->toDateTime()->getTimestamp();
    if (!isset($last[$aid])) { $last[$aid] = ['state' => $state, 'ts' => $ts]; continue; }
    $delta = max(0, $ts - $last[$aid]['ts']);
    if (!isset($byAgent[$aid])) $byAgent[$aid] = ['scheduled_s' => 0, 'staffed_s' => 0];
    if ($last[$aid]['state'] === 'staffed') $byAgent[$aid]['staffed_s'] += $delta;
    $last[$aid] = ['state' => $state, 'ts' => $ts];
  }
  $items = [];
  foreach ($byAgent as $aid => $v) {
    $pct = $v['scheduled_s']>0 ? round(100.0 * $v['staffed_s'] / $v['scheduled_s'], 1) : null;
    $items[] = ['agent_id' => $aid, 'scheduled_s' => $v['scheduled_s'], 'staffed_s' => $v['staffed_s'], 'adherence_pct' => $pct];
  }
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/callback-compliance', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $cursor = Mongo::collection('callbacks')->find(['due' => ['$gte' => $since]]);
  $byAgent = [];
  foreach ($cursor as $c) {
    $aid = (string)($c['agent_id'] ?? '');
    if (!isset($byAgent[$aid])) $byAgent[$aid] = ['due' => 0, 'completed' => 0, 'on_time' => 0];
    $byAgent[$aid]['due']++;
    if (!empty($c['completed'])) { $byAgent[$aid]['completed']++; if ($c['completed'] >= $c['due']) $byAgent[$aid]['on_time']++; }
  }
  $items = [];
  foreach ($byAgent as $aid=>$v) {
    $items[] = ['agent_id' => $aid, 'due' => $v['due'], 'completed' => $v['completed'], 'on_time_pct' => $v['due']?round(100.0*$v['on_time']/$v['due'],1):0];
  }
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/service-level', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(30, (int)($_GET['since_days'] ?? 7)));
  $threshold = max(1, min(120, (int)($_GET['threshold_s'] ?? 20)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  // Need enqueued and answered events to compute wait time
  $ev = iterator_to_array(Mongo::collection('queue_events')->find(['ts' => ['$gte' => $since]], ['sort' => ['call_id' => 1, 'ts' => 1]]));
  $enq = [];
  $served = 0; $within = 0; $abandoned = 0;
  foreach ($ev as $e) {
    $cid = (string)($e['call_id'] ?? '');
    $ts = $e['ts']->toDateTime()->getTimestamp();
    if ($e['event'] === 'enqueued') $enq[$cid] = $ts;
    if ($e['event'] === 'answered' && isset($enq[$cid])) { $served++; if (($ts - $enq[$cid]) <= $threshold) $within++; unset($enq[$cid]); }
    if ($e['event'] === 'abandoned' && isset($enq[$cid])) { $abandoned++; unset($enq[$cid]); }
  }
  $sl = $served>0 ? round(100.0 * $within / $served, 1) : 0.0;
  json_response(['served' => $served, 'within_threshold' => $within, 'abandoned' => $abandoned, 'service_level_pct' => $sl, 'threshold_s' => $threshold]);
});

$router->add('GET', '/api/index.php/reports/abandon-rate', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(30, (int)($_GET['since_days'] ?? 7)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $ev = iterator_to_array(Mongo::collection('queue_events')->find(['ts' => ['$gte' => $since]], ['sort' => ['call_id' => 1, 'ts' => 1]]));
  $enq = [];
  $served = 0; $abandoned = 0;
  foreach ($ev as $e) {
    $cid = (string)($e['call_id'] ?? '');
    if ($e['event'] === 'enqueued') $enq[$cid] = true;
    if ($e['event'] === 'answered' && isset($enq[$cid])) { $served++; unset($enq[$cid]); }
    if ($e['event'] === 'abandoned' && isset($enq[$cid])) { $abandoned++; unset($enq[$cid]); }
  }
  $rate = ($served + $abandoned) > 0 ? round(100.0 * $abandoned / ($served + $abandoned), 1) : 0.0;
  json_response(['served' => $served, 'abandoned' => $abandoned, 'abandon_rate_pct' => $rate]);
});

$router->add('GET', '/api/index.php/reports/rna-rate', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(30, (int)($_GET['since_days'] ?? 7)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $dr = iterator_to_array(Mongo::collection('dial_results')->find(['ts' => ['$gte' => $since]]));
  $tot = 0; $rna = 0;
  foreach ($dr as $d) { $tot++; if (($d['result'] ?? '') === 'rna') $rna++; }
  $rate = $tot>0 ? round(100.0 * $rna / $tot, 1) : 0.0;
  json_response(['attempts' => $tot, 'rna' => $rna, 'rna_rate_pct' => $rate]);
});

$router->add('GET', '/api/index.php/reports/occupancy', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(30, (int)($_GET['since_days'] ?? 7)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  // Approx occupancy = (sum handle_time_s) / staffed_time
  $callsAgg = Mongo::collection('calls')->aggregate([
    ['$match' => ['started_at' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$agent_id', 'talk_s' => ['$sum' => '$handle_time_s']]]
  ]);
  $talk = []; foreach (iterator_to_array($callsAgg) as $r) { $talk[(string)($r['_id'] ?? '')] = (int)($r['talk_s'] ?? 0); }
  $staff = [];
  $events = iterator_to_array(Mongo::collection('staffing_events')->find(['ts' => ['$gte' => $since]], ['sort' => ['agent_id' => 1, 'ts' => 1]]));
  $last = [];
  foreach ($events as $e) {
    $aid = (string)($e['agent_id'] ?? '');
    $state = (string)($e['state'] ?? '');
    $ts = $e['ts']->toDateTime()->getTimestamp();
    if (!isset($last[$aid])) { $last[$aid] = ['state' => $state, 'ts' => $ts]; continue; }
    $delta = max(0, $ts - $last[$aid]['ts']);
    if (!isset($staff[$aid])) $staff[$aid] = 0;
    if ($last[$aid]['state'] === 'staffed') $staff[$aid] += $delta;
    $last[$aid] = ['state' => $state, 'ts' => $ts];
  }
  $items = [];
  foreach ($talk as $aid=>$t) {
    $st = (int)($staff[$aid] ?? 0);
    $occ = $st>0 ? round(100.0 * $t / $st, 1) : null;
    $items[] = ['agent_id' => $aid, 'talk_s' => $t, 'staffed_s' => $st, 'occupancy_pct' => $occ];
  }
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/wait-time-distribution', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(30, (int)($_GET['since_days'] ?? 7)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $ev = iterator_to_array(Mongo::collection('queue_events')->find(['ts' => ['$gte' => $since]], ['sort' => ['call_id' => 1, 'ts' => 1]]));
  $enq = []; $waits = [];
  foreach ($ev as $e) {
    $cid = (string)($e['call_id'] ?? '');
    $ts = $e['ts']->toDateTime()->getTimestamp();
    if ($e['event'] === 'enqueued') $enq[$cid] = $ts;
    if ($e['event'] === 'answered' && isset($enq[$cid])) { $waits[] = $ts - $enq[$cid]; unset($enq[$cid]); }
    if ($e['event'] === 'abandoned') { unset($enq[$cid]); }
  }
  sort($waits); $n = count($waits);
  $p50 = $n? (int)$waits[(int)floor(0.5*($n-1))] : 0;
  $p90 = $n? (int)$waits[(int)floor(0.9*($n-1))] : 0;
  json_response(['count' => $n, 'p50_s' => $p50, 'p90_s' => $p90]);
});

$router->add('GET', '/api/index.php/reports/fcr', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $r = iterator_to_array(Mongo::collection('resolutions')->find(['ts' => ['$gte' => $since]]));
  $tot = count($r); $resolved = 0;
  foreach ($r as $x) if (!empty($x['resolved'])) $resolved++;
  $pct = $tot? round(100.0*$resolved/$tot,1) : 0.0;
  json_response(['items' => [['metric' => 'FCR', 'total' => $tot, 'resolved' => $resolved, 'pct' => $pct]]]);
});

$router->add('GET', '/api/index.php/reports/qa-scorecards', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('qa')->aggregate([
    ['$match' => ['ts' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$agent_id', 'avg_score' => ['$avg' => '$score'], 'count' => ['$sum' => 1]]],
    ['$sort' => ['avg_score' => -1]]
  ]);
  $items = array_map(fn($d)=>['agent_id'=>(string)($d['_id']??''), 'avg_score'=>round((float)($d['avg_score']??0),2), 'count'=>(int)($d['count']??0)], iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/funnel', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(180, (int)($_GET['since_days'] ?? 90)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('funnel_events')->aggregate([
    ['$match' => ['ts' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$stage', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
  ]);
  $items = array_map(fn($d)=>['stage'=>(string)($d['_id']??''), 'count'=>(int)($d['count']??0)], iterator_to_array($agg));
  json_response(['items' => $items]);
});

$router->add('GET', '/api/index.php/reports/geo-compliance', function(){
  require_auth(['admin','supervisor']);
  $sinceDays = max(1, min(90, (int)($_GET['since_days'] ?? 30)));
  $since = new UTCDateTime((time() - $sinceDays*86400) * 1000);
  $agg = Mongo::collection('geo_checks')->aggregate([
    ['$match' => ['ts' => ['$gte' => $since]]],
    ['$group' => ['_id' => '$allowed', 'count' => ['$sum' => 1]]],
    ['$sort' => ['_id' => -1]]
  ]);
  $items = array_map(fn($d)=>['allowed'=> (bool)($d['_id']??false), 'count'=>(int)($d['count']??0)], iterator_to_array($agg));
  json_response(['items' => $items]);
});

// Admin CRUD for seed collections
$router->add('GET', '/api/index.php/admin/calls', function(){
  require_auth(['admin','supervisor']);
  $limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));
  $cursor = Mongo::collection('calls')->find([], ['limit' => $limit, 'sort' => ['started_at' => -1]]);
  $items = array_map(fn($d)=>[
    'id'=>(string)$d['_id'], 'direction'=>$d['direction']??'', 'agent_id'=>$d['agent_id']??'', 'campaign_id'=>$d['campaign_id']??'', 'contact_phone'=>$d['contact_phone']??'', 'handle_time_s'=>(int)($d['handle_time_s']??0), 'started_at'=>($d['started_at'] instanceof UTCDateTime)?$d['started_at']->toDateTime()->format(DATE_ATOM):null
  ], $cursor->toArray());
  json_response(['items'=>$items]);
});
$router->add('POST', '/api/index.php/admin/calls', function(){
  require_auth(['admin']);
  $d = json_input();
  $doc = [
    'direction' => in_array(($d['direction'] ?? ''), ['inbound','outbound'], true) ? $d['direction'] : 'inbound',
    'agent_id' => (string)($d['agent_id'] ?? ''),
    'campaign_id' => (string)($d['campaign_id'] ?? ''),
    'contact_phone' => preg_replace('/[^0-9\+]/', '', (string)($d['contact_phone'] ?? '')),
    'handle_time_s' => (int)($d['handle_time_s'] ?? 0),
    'started_at' => new UTCDateTime((int)(($d['started_at_ts'] ?? time()) * 1000)),
  ];
  $doc['is_dnc'] = (bool)Mongo::collection('dnc')->findOne(['phone' => $doc['contact_phone']]);
  Mongo::collection('calls')->insertOne($doc);
  json_response(['ok'=>true]);
});

$router->add('GET', '/api/index.php/admin/staffing', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('staffing_events')->find([], ['limit'=>100, 'sort'=>['ts'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'agent_id'=>$d['agent_id']??'','state'=>$d['state']??'','ts'=>($d['ts'] instanceof UTCDateTime)?$d['ts']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/staffing', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('staffing_events')->insertOne(['agent_id'=>(string)($d['agent_id']??''),'state'=>(string)($d['state']??''),'ts'=>new UTCDateTime((int)(($d['ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/schedules', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('schedules')->find([], ['limit'=>100, 'sort'=>['shift_start'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'agent_id'=>$d['agent_id']??'','shift_start'=>($d['shift_start'] instanceof UTCDateTime)?$d['shift_start']->toDateTime()->format(DATE_ATOM):null,'shift_end'=>($d['shift_end'] instanceof UTCDateTime)?$d['shift_end']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/schedules', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('schedules')->insertOne(['agent_id'=>(string)($d['agent_id']??''),'shift_start'=>new UTCDateTime((int)(($d['shift_start_ts']??time())*1000)),'shift_end'=>new UTCDateTime((int)(($d['shift_end_ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/callbacks', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('callbacks')->find([], ['limit'=>100, 'sort'=>['due'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'callback_id'=>$d['callback_id']??'','agent_id'=>$d['agent_id']??'','due'=>($d['due'] instanceof UTCDateTime)?$d['due']->toDateTime()->format(DATE_ATOM):null,'completed'=>($d['completed'] instanceof UTCDateTime)?$d['completed']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/callbacks', function(){ require_auth(['admin']); $d=json_input(); $doc=['callback_id'=>(string)($d['id']??''),'agent_id'=>(string)($d['agent_id']??''),'due'=>new UTCDateTime((int)(($d['due_ts']??time())*1000))]; if (!empty($d['completed_ts'])) $doc['completed']=new UTCDateTime((int)$d['completed_ts']*1000); Mongo::collection('callbacks')->updateOne(['callback_id'=>$doc['callback_id']?:md5(json_encode($doc))], ['$set'=>$doc], ['upsert'=>true]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/queue-events', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('queue_events')->find([], ['limit'=>100, 'sort'=>['ts'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'call_id'=>$d['call_id']??'','queue'=>$d['queue']??'','event'=>$d['event']??'','ts'=>($d['ts'] instanceof UTCDateTime)?$d['ts']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/queue-events', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('queue_events')->insertOne(['call_id'=>(string)($d['call_id']??''),'queue'=>(string)($d['queue']??''),'event'=>(string)($d['event']??''),'ts'=>new UTCDateTime((int)(($d['ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/dial-results', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('dial_results')->find([], ['limit'=>100, 'sort'=>['ts'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'call_id'=>$d['call_id']??'','result'=>$d['result']??'','ts'=>($d['ts'] instanceof UTCDateTime)?$d['ts']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/dial-results', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('dial_results')->insertOne(['call_id'=>(string)($d['call_id']??''),'result'=>(string)($d['result']??''),'ts'=>new UTCDateTime((int)(($d['ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/resolutions', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('resolutions')->find([], ['limit'=>100, 'sort'=>['ts'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'call_id'=>$d['call_id']??'','resolved'=>(bool)($d['resolved']??false),'ts'=>($d['ts'] instanceof UTCDateTime)?$d['ts']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/resolutions', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('resolutions')->insertOne(['call_id'=>(string)($d['call_id']??''),'resolved'=>(bool)($d['resolved']??false),'ts'=>new UTCDateTime((int)(($d['ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/qa', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('qa')->find([], ['limit'=>100, 'sort'=>['ts'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'call_id'=>$d['call_id']??'','agent_id'=>$d['agent_id']??'','score'=>(float)($d['score']??0),'rubric'=>$d['rubric']??null,'ts'=>($d['ts'] instanceof UTCDateTime)?$d['ts']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/qa', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('qa')->insertOne(['call_id'=>(string)($d['call_id']??''),'agent_id'=>(string)($d['agent_id']??''),'score'=>(float)($d['score']??0),'rubric'=>($d['rubric'] ?? null),'ts'=>new UTCDateTime((int)(($d['ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/funnel', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('funnel_events')->find([], ['limit'=>100, 'sort'=>['ts'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'lead_id'=>$d['lead_id']??'','stage'=>$d['stage']??'','ts'=>($d['ts'] instanceof UTCDateTime)?$d['ts']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/funnel', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('funnel_events')->insertOne(['lead_id'=>(string)($d['lead_id']??''),'stage'=>(string)($d['stage']??''),'ts'=>new UTCDateTime((int)(($d['ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/geo-checks', function(){ require_auth(['admin','supervisor']); $c = Mongo::collection('geo_checks')->find([], ['limit'=>100, 'sort'=>['ts'=>-1]]); $items = array_map(fn($d)=>['id'=>(string)$d['_id'],'call_id'=>$d['call_id']??'','phone_geo'=>$d['phone_geo']??'','allowed'=>(bool)($d['allowed']??false),'ts'=>($d['ts'] instanceof UTCDateTime)?$d['ts']->toDateTime()->format(DATE_ATOM):null], $c->toArray()); json_response(['items'=>$items]); });
$router->add('POST', '/api/index.php/admin/geo-checks', function(){ require_auth(['admin']); $d=json_input(); Mongo::collection('geo_checks')->insertOne(['call_id'=>(string)($d['call_id']??''),'phone_geo'=>(string)($d['phone_geo']??''),'allowed'=>(bool)($d['allowed']??false),'ts'=>new UTCDateTime((int)(($d['ts']??time())*1000))]); json_response(['ok'=>true]); });

$router->add('GET', '/api/index.php/admin/qa-rubrics', function(){
  require_auth(['admin','supervisor']);
  $c = Mongo::collection('qa_rubrics')->find([], ['sort'=>['updated_at'=>-1]]);
  $items = array_map(fn($d)=>[
    'id'=>(string)$d['_id'],
    'name'=>(string)($d['name']??''),
    'rubric'=>$d['rubric']??null,
    'updated_at'=>isset($d['updated_at']) && $d['updated_at'] instanceof UTCDateTime ? $d['updated_at']->toDateTime()->format(DATE_ATOM) : null,
  ], $c->toArray());
  json_response(['items'=>$items]);
});
$router->add('POST', '/api/index.php/admin/qa-rubrics', function(){
  require_auth(['admin']);
  $d = json_input();
  $name = trim((string)($d['name'] ?? ''));
  $rubric = $d['rubric'] ?? null;
  if ($name === '' || !is_array($rubric)) { json_response(['error'=>'name and rubric (object) required'], 400); return; }
  $doc = ['name'=>$name,'rubric'=>$rubric,'updated_at'=>new UTCDateTime(time()*1000)];
  Mongo::collection('qa_rubrics')->updateOne(['name'=>$name], ['$set'=>$doc], ['upsert'=>true]);
  json_response(['ok'=>true]);
});
$router->add('DELETE', '/api/index.php/admin/qa-rubrics/{id}', function($id){
  require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch(\Throwable $e){ json_response(['error'=>'invalid id'], 400); return; }
  Mongo::collection('qa_rubrics')->deleteOne(['_id'=>$oid]);
  json_response(['ok'=>true]);
});

// Admin: mark callback complete
$router->add('POST', '/api/index.php/admin/callbacks/complete', function(){
  require_auth(['admin','supervisor']);
  $d = json_input();
  $cid = (string)($d['id'] ?? ($d['callback_id'] ?? ''));
  if ($cid === '') { json_response(['error'=>'id required'], 400); return; }
  $ts = (int)($d['completed_ts'] ?? time());
  Mongo::collection('callbacks')->updateOne(['callback_id' => $cid], ['$set' => ['completed' => new UTCDateTime($ts*1000)]], ['upsert' => false]);
  json_response(['ok'=>true]);
});

// Admin DELETE endpoints for seed collections
$router->add('DELETE', '/api/index.php/admin/calls/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('calls')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/staffing/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('staffing_events')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/schedules/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('schedules')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/callbacks/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('callbacks')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/queue-events/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('queue_events')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/dial-results/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('dial_results')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/resolutions/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('resolutions')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/qa/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('qa')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/funnel/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('funnel_events')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });
$router->add('DELETE', '/api/index.php/admin/geo-checks/{id}', function($id){ require_auth(['admin']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } Mongo::collection('geo_checks')->deleteOne(['_id'=>$oid]); json_response(['ok'=>true]); });

$router->add('PUT', '/api/index.php/admin/schedules/{id}', function($id){ require_auth(['admin','supervisor']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } $d=json_input(); $upd=[]; if(isset($d['agent_id'])) $upd['agent_id']=(string)$d['agent_id']; if(isset($d['shift_start_ts'])) $upd['shift_start']=new UTCDateTime((int)$d['shift_start_ts']*1000); if(isset($d['shift_end_ts'])) $upd['shift_end']=new UTCDateTime((int)$d['shift_end_ts']*1000); if(!$upd){ json_response(['error'=>'no changes'],400); return; } Mongo::collection('schedules')->updateOne(['_id'=>$oid], ['$set'=>$upd]); json_response(['ok'=>true]); });

$router->add('PUT', '/api/index.php/admin/callbacks/{id}', function($id){ require_auth(['admin','supervisor']); try{ $oid=new ObjectId($id);}catch(\Throwable $e){ json_response(['error'=>'invalid id'],400); return; } $d=json_input(); $upd=[]; if(isset($d['agent_id'])) $upd['agent_id']=(string)$d['agent_id']; if(isset($d['due_ts'])) $upd['due']=new UTCDateTime((int)$d['due_ts']*1000); if(isset($d['completed_ts'])) $upd['completed']=new UTCDateTime((int)$d['completed_ts']*1000); if(!$upd){ json_response(['error'=>'no changes'],400); return; } Mongo::collection('callbacks')->updateOne(['_id'=>$oid], ['$set'=>$upd]); json_response(['ok'=>true]); });

// ===== Payments & Accounts (Stripe) =====

// Admin: list accounts
$router->add('GET', '/api/index.php/admin/accounts', function(){
  require_auth(['admin','supervisor']);
  $c = Mongo::collection('accounts')->find([], ['sort'=>['created_at'=>-1]]);
  $items = array_map(function($d){
    return [
      'id'=>(string)$d['_id'],
      'name'=>(string)($d['name']??''),
      'email'=>(string)($d['email']??''),
      'type'=>(string)($d['type']??'client'),
      'balance_cents'=>(int)($d['balance_cents']??0),
      'portal_token'=> (string)($d['portal_token'] ?? ''),
      'created_at'=> isset($d['created_at']) && $d['created_at'] instanceof UTCDateTime ? $d['created_at']->toDateTime()->format(DATE_ATOM) : null,
    ];
  }, $c->toArray());
  json_response(['items'=>$items]);
});

// Admin: create/update account
$router->add('POST', '/api/index.php/admin/accounts', function(){
  require_auth(['admin']);
  $d = json_input();
  $name = trim((string)($d['name'] ?? ''));
  $email = strtolower(trim((string)($d['email'] ?? '')));
  $type = in_array(($d['type'] ?? 'client'), ['client','builder'], true) ? $d['type'] : 'client';
  if ($name === '' || $email === '') { json_response(['error'=>'name and email required'], 400); return; }
  $token = bin2hex(random_bytes(16));
  $doc = [
    'name'=>$name,
    'email'=>$email,
    'type'=>$type,
    'balance_cents'=>0,
    'portal_token'=>$token,
    'status'=>'active',
    'created_at'=> new UTCDateTime(time()*1000),
  ];
  Mongo::collection('accounts')->insertOne($doc);
  json_response(['ok'=>true, 'id'=>(string)$doc['_id']]);
});

$router->add('PUT', '/api/index.php/admin/accounts/{id}', function($id){
  require_auth(['admin']);
  try { $oid = new ObjectId($id); } catch (\Throwable $e) { json_response(['error'=>'invalid id'], 400); return; }
  $d = json_input();
  $upd = [];
  foreach (['name','email','type','status'] as $f) if (array_key_exists($f,$d)) $upd[$f] = $d[$f];
  if (isset($d['reset_portal_token']) && $d['reset_portal_token']) { $upd['portal_token'] = bin2hex(random_bytes(16)); }
  if (!$upd) { json_response(['error'=>'no changes'],400); return; }
  Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$set'=>$upd]);
  json_response(['ok'=>true]);
});

// Public: portal fetch by token
$router->add('GET', '/api/index.php/accounts/portal', function(){
  $token = (string)($_GET['token'] ?? '');
  if ($token === '') { json_response(['error'=>'token required'], 400); return; }
  $acc = Mongo::collection('accounts')->findOne(['portal_token'=>$token]);
  if (!$acc) { json_response(['error'=>'not found'], 404); return; }
  $payments = Mongo::collection('payments')->find(['account_id'=>(string)$acc['_id']], ['limit'=>100, 'sort'=>['ts'=>-1]]);
  $items = array_map(function($p){ return [
    'amount_cents'=>(int)($p['amount_cents']??0),
    'currency'=>(string)($p['currency']??'usd'),
    'ts'=> isset($p['ts']) && $p['ts'] instanceof UTCDateTime ? $p['ts']->toDateTime()->format(DATE_ATOM) : null,
  ]; }, $payments->toArray());
  json_response([
    'account'=>[
      'id'=>(string)$acc['_id'],
      'name'=>(string)($acc['name']??''),
      'email'=>(string)($acc['email']??''),
      'type'=>(string)($acc['type']??'client'),
      'balance_cents'=>(int)($acc['balance_cents']??0),
    ],
    'payments'=>$items
  ]);
});

// Create Stripe Checkout session (admin or portal)
$router->add('POST', '/api/index.php/payments/checkout', function(){
  // Allow either admin token OR portal token OR client cookie JWT
  $claims = null; $isAdmin = false;
  // Soft-check admin bearer token without exiting on failure
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) {
    $maybeJwt = (string)$m[1];
    try {
      $c = AppJwt::verify($maybeJwt);
      if (in_array((string)($c['role'] ?? ''), ['admin','supervisor'], true)) {
        $claims = $c; $isAdmin = true;
      }
    } catch (\Throwable $e) { /* ignore */ }
  }
  $d = json_input();
  $portalToken = (string)($d['portal_token'] ?? '');
  $accountId = (string)($d['account_id'] ?? '');
  $amountCents = (int)($d['amount_cents'] ?? 0);
  $currency = strtolower((string)($d['currency'] ?? 'usd'));
  if ($amountCents < 100) { json_response(['error'=>'minimum amount is 100 cents'], 400); return; }
  $acc = null;
  if ($isAdmin) {
    if ($accountId === '') { json_response(['error'=>'account_id required'], 400); return; }
    try { $oid = new ObjectId($accountId); } catch (\Throwable $e) { json_response(['error'=>'invalid account_id'],400); return; }
    $acc = Mongo::collection('accounts')->findOne(['_id'=>$oid]);
  } else {
    if ($portalToken !== '') {
      $acc = Mongo::collection('accounts')->findOne(['portal_token'=>$portalToken]);
    } else {
      // Support client JWT from cookie or Authorization header
      $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
      $cookie = $_COOKIE['client_jwt'] ?? '';
      $jwt = '';
      if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $jwt = (string)$m[1]; }
      if ($jwt === '' && $cookie !== '') { $jwt = $cookie; }
      if ($jwt !== '') {
        try { $c = AppJwt::verify($jwt); if (($c['role'] ?? '') === 'client') { $aid = (string)($c['sub'] ?? ''); try { $oid = new ObjectId($aid);} catch(\Throwable $e){ $oid=null;} if ($oid) $acc = Mongo::collection('accounts')->findOne(['_id'=>$oid]); } } catch(\Throwable $e) { /* ignore */ }
      }
    }
  }
  if (!$acc) { json_response(['error'=>'account not found'], 404); return; }

  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  $pub = AppConfig::string('STRIPE_PUBLISHABLE_KEY', '');
  $base = rtrim((string)($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/');
  $successUrl = $isAdmin ? ($base . '/payments_admin.php?status=success') : ($base . '/client-portal.php?status=success');
  $cancelUrl = $isAdmin ? ($base . '/payments_admin.php?status=cancel') : ($base . '/client-portal.php?status=cancel');
  $stripe = new \Stripe\StripeClient($secret);
  $session = $stripe->checkout->sessions->create([
    'mode' => 'payment',
    'payment_method_types' => ['card'],
    'line_items' => [[
      'price_data' => [
        'currency' => $currency,
        'product_data' => ['name' => 'Account funding for ' . ((string)$acc['name'] ?? '')],
        'unit_amount' => $amountCents,
      ],
      'quantity' => 1,
    ]],
    'success_url' => $successUrl . '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $cancelUrl,
    'metadata' => [
      'account_id' => (string)$acc['_id'],
      'account_email' => (string)($acc['email'] ?? ''),
    ],
  ]);
  json_response(['id' => $session->id, 'url' => $session->url, 'publishable_key' => $pub]);
});

// Admin: list active recurring Stripe prices (for subscriptions)
$router->add('GET', '/api/index.php/admin/stripe/prices', function(){
  require_auth(['admin','supervisor']);
  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $prices = $stripe->prices->all([
      'active' => true,
      'limit' => 100,
      'expand' => ['data.product']
    ]);
    $items = [];
    foreach ($prices->data as $p) {
      $isRecurring = isset($p->recurring) && $p->recurring;
      if (!$isRecurring) { continue; }
      $productName = is_object($p->product) ? (string)($p->product->name ?? $p->product->id) : (string)$p->product;
      $items[] = [
        'id' => (string)$p->id,
        'product' => $productName,
        'nickname' => (string)($p->nickname ?? ''),
        'unit_amount' => (int)($p->unit_amount ?? 0),
        'currency' => (string)($p->currency ?? 'usd'),
        'interval' => (string)($p->recurring->interval ?? ''),
        'interval_count' => (int)($p->recurring->interval_count ?? 1),
      ];
    }
    json_response(['items'=>$items]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe list failed'], 500);
  }
});

// Client: list active recurring Stripe prices
$router->add('GET', '/api/index.php/client/stripe/prices', function(){
  // Auth via client cookie/bearer
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $cookie = $_COOKIE['client_jwt'] ?? '';
  $jwt = '';
  if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $jwt = (string)$m[1]; }
  if ($jwt === '' && $cookie !== '') { $jwt = $cookie; }
  if ($jwt === '') { json_response(['error'=>'missing token'], 401); return; }
  try { $claims = AppJwt::verify($jwt); } catch(\Throwable $e){ json_response(['error'=>'invalid token'], 401); return; }
  if (($claims['role'] ?? '') !== 'client') { json_response(['error'=>'forbidden'], 403); return; }
  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $prices = $stripe->prices->all(['active'=>true,'limit'=>100,'expand'=>['data.product']]);
    $items = [];
    foreach ($prices->data as $p) {
      $isRecurring = isset($p->recurring) && $p->recurring;
      if (!$isRecurring) continue;
      $productName = is_object($p->product) ? (string)($p->product->name ?? $p->product->id) : (string)$p->product;
      $items[] = [
        'id' => (string)$p->id,
        'product' => $productName,
        'nickname' => (string)($p->nickname ?? ''),
        'unit_amount' => (int)($p->unit_amount ?? 0),
        'currency' => (string)($p->currency ?? 'usd'),
        'interval' => (string)($p->recurring->interval ?? ''),
        'interval_count' => (int)($p->recurring->interval_count ?? 1),
      ];
    }
    json_response(['items'=>$items]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe list failed'], 500);
  }
});

// Client: create a subscription via Stripe Checkout for self
$router->add('POST', '/api/index.php/client/subscriptions/checkout', function(){
  // Auth via client cookie/bearer
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $cookie = $_COOKIE['client_jwt'] ?? '';
  $jwt = '';
  if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $jwt = (string)$m[1]; }
  if ($jwt === '' && $cookie !== '') { $jwt = $cookie; }
  if ($jwt === '') { json_response(['error'=>'missing token'], 401); return; }
  try { $claims = AppJwt::verify($jwt); } catch(\Throwable $e){ json_response(['error'=>'invalid token'], 401); return; }
  if (($claims['role'] ?? '') !== 'client') { json_response(['error'=>'forbidden'], 403); return; }
  $aid = (string)($claims['sub'] ?? '');
  try { $oid = new ObjectId($aid); } catch (\Throwable $e) { json_response(['error'=>'invalid account'], 400); return; }
  $acc = Mongo::collection('accounts')->findOne(['_id'=>$oid]);
  if (!$acc) { json_response(['error'=>'account not found'], 404); return; }
  $d = json_input();
  $priceId = (string)($d['price_id'] ?? '');
  $quantity = (int)($d['quantity'] ?? 1);
  if ($priceId === '') { json_response(['error'=>'price_id required'], 400); return; }
  if ($quantity < 1) { $quantity = 1; }
  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  $pub = AppConfig::string('STRIPE_PUBLISHABLE_KEY', '');
  $base = rtrim((string)($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/');
  $successUrl = $base . '/client-portal.php?status=success&session_id={CHECKOUT_SESSION_ID}';
  $cancelUrl = $base . '/client-portal.php?status=cancel';
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $session = $stripe->checkout->sessions->create([
      'mode' => 'subscription',
      'payment_method_types' => ['card'],
      'line_items' => [[ 'price' => $priceId, 'quantity' => $quantity ]],
      'success_url' => $successUrl,
      'cancel_url' => $cancelUrl,
      'client_reference_id' => (string)$acc['_id'],
      'subscription_data' => [ 'metadata' => [ 'account_id' => (string)$acc['_id'], 'account_email' => (string)($acc['email'] ?? '') ]],
      'metadata' => [ 'account_id' => (string)$acc['_id'], 'account_email' => (string)($acc['email'] ?? '') ],
      'customer_email' => (string)($acc['email'] ?? ''),
    ]);
    json_response(['id'=>$session->id, 'url'=>$session->url, 'publishable_key'=>$pub]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe checkout failed'], 500);
  }
});

// Client: reconcile a subscription created via Checkout (deduct balance by first invoice)
$router->add('POST', '/api/index.php/client/subscriptions/reconcile', function(){
  // Auth via client cookie/bearer
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $cookie = $_COOKIE['client_jwt'] ?? '';
  $jwt = '';
  if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $jwt = (string)$m[1]; }
  if ($jwt === '' && $cookie !== '') { $jwt = $cookie; }
  if ($jwt === '') { json_response(['error'=>'missing token'], 401); return; }
  try { $claims = AppJwt::verify($jwt); } catch(\Throwable $e){ json_response(['error'=>'invalid token'], 401); return; }
  if (($claims['role'] ?? '') !== 'client') { json_response(['error'=>'forbidden'], 403); return; }
  $d = json_input();
  $sessionId = (string)($d['session_id'] ?? '');
  if ($sessionId === '') { json_response(['error'=>'session_id required'], 400); return; }
  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $sess = $stripe->checkout->sessions->retrieve($sessionId, [ 'expand' => ['subscription.latest_invoice'] ]);
    $accountId = '';
    if ($sess && $sess->subscription) { $sub = $sess->subscription; $accountId = (string)($sub->metadata['account_id'] ?? ''); }
    if ($accountId === '') { $accountId = (string)($sess->metadata['account_id'] ?? ''); }
    $invoiceId = '';
    $amountTotal = 0; $currency = 'usd'; $created = time(); $paid = false;
    if ($sess && $sess->subscription && $sess->subscription->latest_invoice) {
      $inv = $sess->subscription->latest_invoice;
      $invoiceId = (string)($inv->id ?? '');
      $amountTotal = (int)($inv->total ?? 0);
      $currency = (string)($inv->currency ?? 'usd');
      $created = (int)($inv->created ?? time());
      $paid = (bool)($inv->paid ?? false) || (string)($inv->status ?? '') === 'paid';
    }
    if ($accountId === '' || $invoiceId === '' || $amountTotal <= 0 || !$paid) { json_response(['ok'=>false, 'reason'=>'not ready'], 200); return; }
    try { $oid = new ObjectId($accountId); } catch (\Throwable $e) { json_response(['ok'=>false, 'reason'=>'invalid account'], 200); return; }
    $existing = Mongo::collection('payments')->findOne(['stripe_invoice_id' => $invoiceId]);
    if (!$existing) {
      Mongo::collection('payments')->insertOne([
        'account_id' => $accountId,
        'amount_cents' => -1 * $amountTotal,
        'currency' => $currency,
        'stripe_invoice_id' => $invoiceId,
        'stripe_subscription_id' => (string)($sess->subscription?->id ?? ''),
        'ts' => new UTCDateTime((($created>0?$created:time())*1000)),
        'type' => 'subscription_charge'
      ]);
      Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$inc' => ['balance_cents' => -1 * $amountTotal]]);
    }
    json_response(['ok'=>true, 'recorded'=>true]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe reconcile failed'], 500);
  }
});
// Admin: create a subscription via Stripe Checkout for an account
$router->add('POST', '/api/index.php/admin/subscriptions/checkout', function(){
  require_auth(['admin','supervisor']);
  $d = json_input();
  $accountId = (string)($d['account_id'] ?? '');
  $priceId = (string)($d['price_id'] ?? '');
  $quantity = (int)($d['quantity'] ?? 1);
  if ($accountId === '' || $priceId === '') { json_response(['error'=>'account_id and price_id required'], 400); return; }
  if ($quantity < 1) { $quantity = 1; }
  try { $oid = new ObjectId($accountId); } catch (\Throwable $e) { json_response(['error'=>'invalid account_id'],400); return; }
  $acc = Mongo::collection('accounts')->findOne(['_id'=>$oid]);
  if (!$acc) { json_response(['error'=>'account not found'], 404); return; }

  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  $pub = AppConfig::string('STRIPE_PUBLISHABLE_KEY', '');
  $base = rtrim((string)($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/');
  $successUrl = $base . '/subscriptions_admin.php?status=success&session_id={CHECKOUT_SESSION_ID}';
  $cancelUrl = $base . '/subscriptions_admin.php?status=cancel';
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $session = $stripe->checkout->sessions->create([
      'mode' => 'subscription',
      'payment_method_types' => ['card'],
      'line_items' => [[ 'price' => $priceId, 'quantity' => $quantity ]],
      'success_url' => $successUrl,
      'cancel_url' => $cancelUrl,
      'client_reference_id' => (string)$acc['_id'],
      'subscription_data' => [ 'metadata' => [ 'account_id' => (string)$acc['_id'], 'account_email' => (string)($acc['email'] ?? '') ]],
      'metadata' => [ 'account_id' => (string)$acc['_id'], 'account_email' => (string)($acc['email'] ?? '') ],
      'customer_email' => (string)($acc['email'] ?? ''),
    ]);
    json_response(['id'=>$session->id, 'url'=>$session->url, 'publishable_key'=>$pub]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe checkout failed'], 500);
  }
});

// Admin: reconcile a subscription created via Checkout (deduct balance by first invoice)
$router->add('POST', '/api/index.php/admin/subscriptions/reconcile', function(){
  require_auth(['admin','supervisor']);
  $d = json_input();
  $sessionId = (string)($d['session_id'] ?? '');
  if ($sessionId === '') { json_response(['error'=>'session_id required'], 400); return; }
  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $sess = $stripe->checkout->sessions->retrieve($sessionId, [ 'expand' => ['subscription.latest_invoice'] ]);
    // Determine account id from metadata (subscription preferred, session as fallback)
    $accountId = '';
    if ($sess && $sess->subscription) {
      $sub = $sess->subscription;
      $accountId = (string)($sub->metadata['account_id'] ?? '');
    }
    if ($accountId === '') { $accountId = (string)($sess->metadata['account_id'] ?? ''); }
    // Resolve invoice
    $invoiceId = '';
    $amountTotal = 0; $currency = 'usd'; $created = time(); $paid = false;
    if ($sess && $sess->subscription && $sess->subscription->latest_invoice) {
      $inv = $sess->subscription->latest_invoice;
      $invoiceId = (string)($inv->id ?? '');
      $amountTotal = (int)($inv->total ?? 0);
      $currency = (string)($inv->currency ?? 'usd');
      $created = (int)($inv->created ?? time());
      $paid = (bool)($inv->paid ?? false) || (string)($inv->status ?? '') === 'paid';
    } else if ($sess && $sess->latest_invoice) {
      // Fallback if expanded on session root
      $inv = $sess->latest_invoice;
      if (is_object($inv)) {
        $invoiceId = (string)($inv->id ?? '');
        $amountTotal = (int)($inv->total ?? 0);
        $currency = (string)($inv->currency ?? 'usd');
        $created = (int)($inv->created ?? time());
        $paid = (bool)($inv->paid ?? false) || (string)($inv->status ?? '') === 'paid';
      } else if (is_string($inv) && $inv !== '') {
        $invoiceId = $inv;
        $invObj = $stripe->invoices->retrieve($inv);
        $amountTotal = (int)($invObj->total ?? 0);
        $currency = (string)($invObj->currency ?? 'usd');
        $created = (int)($invObj->created ?? time());
        $paid = (bool)($invObj->paid ?? false) || (string)($invObj->status ?? '') === 'paid';
      }
    }
    if ($accountId === '' || $invoiceId === '' || $amountTotal <= 0 || !$paid) { json_response(['ok'=>false, 'reason'=>'not ready'], 200); return; }
    try { $oid = new ObjectId($accountId); } catch (\Throwable $e) { json_response(['ok'=>false, 'reason'=>'invalid account'], 200); return; }
    // Idempotent: if invoice already recorded, do nothing
    $existing = Mongo::collection('payments')->findOne(['stripe_invoice_id' => $invoiceId]);
    if ($existing) { json_response(['ok'=>true, 'recorded'=>true]); return; }
    // Insert negative ledger and decrement balance
    Mongo::collection('payments')->insertOne([
      'account_id' => $accountId,
      'amount_cents' => -1 * $amountTotal,
      'currency' => $currency,
      'stripe_invoice_id' => $invoiceId,
      'stripe_subscription_id' => (string)($sess->subscription?->id ?? ''),
      'ts' => new UTCDateTime((($created>0?$created:time())*1000)),
      'type' => 'subscription_charge'
    ]);
    Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$inc' => ['balance_cents' => -1 * $amountTotal]]);
    json_response(['ok'=>true, 'recorded'=>true]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe reconcile failed'], 500);
  }
});

// Stripe webhook to record payments and subscription invoices
$router->add('POST', '/api/index.php/payments/webhook', function(){
  $secret = AppConfig::string('STRIPE_WEBHOOK_SECRET', '');
  $payload = file_get_contents('php://input');
  $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
  $event = null;
  try {
    if ($secret !== '') {
      $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
    } else {
      $event = json_decode($payload, true);
    }
  } catch (\Throwable $e) {
    http_response_code(400); echo 'Invalid'; return; 
  }
  $type = is_array($event) ? ($event['type'] ?? '') : $event->type;
  $obj = is_array($event) ? ($event['data']['object'] ?? []) : $event->data->object;
  if ($type === 'checkout.session.completed') {
    $mode = (string)($obj['mode'] ?? '');
    if ($mode !== 'payment') { http_response_code(200); echo 'OK'; return; }
    $accountId = (string)($obj['metadata']['account_id'] ?? '');
    $amountTotal = (int)($obj['amount_total'] ?? 0);
    $currency = (string)($obj['currency'] ?? 'usd');
    $created = (int)($obj['created'] ?? 0);
    if ($accountId !== '' && $amountTotal > 0) {
      try { $oid = new ObjectId($accountId); } catch (\Throwable $e) { http_response_code(200); return; }
      // Record payment and update balance
      Mongo::collection('payments')->insertOne([
        'account_id' => $accountId,
        'amount_cents' => $amountTotal,
        'currency' => $currency,
        'stripe_session_id' => (string)($obj['id'] ?? ''),
        'stripe_payment_intent' => (string)($obj['payment_intent'] ?? ''),
        'ts' => new UTCDateTime((($created>0?$created:time())*1000)),
      ]);
      Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$inc' => ['balance_cents' => $amountTotal]]);
    }
  } elseif ($type === 'invoice.payment_succeeded') {
    // Subscription invoice paid: deduct account balance by the invoice total
    $accountId = (string)($obj['metadata']['account_id'] ?? '');
    $amountTotal = (int)($obj['total'] ?? 0);
    $currency = (string)($obj['currency'] ?? 'usd');
    $created = (int)($obj['created'] ?? 0);
    $invoiceId = (string)($obj['id'] ?? '');
    $subscriptionId = (string)($obj['subscription'] ?? '');
    // Persist invoice record
    Mongo::collection('invoices')->updateOne(
      ['stripe_invoice_id' => (string)($obj['id'] ?? '')],
      ['$set' => [
        'stripe_invoice_id' => (string)($obj['id'] ?? ''),
        'stripe_subscription_id' => $subscriptionId,
        'account_id' => $accountId,
        'total_cents' => $amountTotal,
        'currency' => $currency,
        'created' => new UTCDateTime((($created>0?$created:time())*1000)),
        'raw' => $obj,
      ]],
      ['upsert' => true]
    );
    if ($accountId === '' && $subscriptionId !== '') {
      $secretKey = AppConfig::string('STRIPE_SECRET_KEY', '');
      if ($secretKey !== '') {
        try {
          $sc = new \Stripe\StripeClient($secretKey);
          $sub = $sc->subscriptions->retrieve($subscriptionId);
          $accountId = (string)($sub->metadata['account_id'] ?? '');
          // Optionally persist subscription linkage
          if ($accountId !== '') {
            Mongo::collection('subscriptions')->updateOne(
              ['stripe_subscription_id' => $subscriptionId],
              ['$set' => [
                'account_id' => $accountId,
                'stripe_subscription_id' => $subscriptionId,
                'status' => (string)($sub->status ?? ''),
                'price_id' => (string)($sub->items->data[0]->price->id ?? ''),
                'updated_at' => new UTCDateTime(time()*1000),
              ]],
              ['upsert' => true]
            );
          }
        } catch (\Throwable $e) { /* ignore */ }
      }
    }
    if ($accountId !== '' && $amountTotal > 0) {
      try { $oid = new ObjectId($accountId); } catch (\Throwable $e) { http_response_code(200); return; }
      // Record a negative ledger entry to reflect deduction
      Mongo::collection('payments')->insertOne([
        'account_id' => $accountId,
        'amount_cents' => -1 * $amountTotal,
        'currency' => $currency,
        'stripe_invoice_id' => $invoiceId,
        'stripe_subscription_id' => $subscriptionId,
        'ts' => new UTCDateTime((($created>0?$created:time())*1000)),
        'type' => 'subscription_charge'
      ]);
      Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$inc' => ['balance_cents' => -1 * $amountTotal]]);
    }
  }
  http_response_code(200); echo 'OK';
});

// ===== Client Magic-Link Auth =====
// Start: POST /client/magic/start { email, expires_minutes? }
$router->add('POST', '/api/index.php/client/magic/start', function(){
  $d = json_input();
  $email = strtolower(trim((string)($d['email'] ?? '')));
  if ($email === '') { json_response(['error'=>'email required'], 400); return; }
  $acc = Mongo::collection('accounts')->findOne(['email'=>$email]);
  if (!$acc) { json_response(['ok'=>true]); return; } // do not leak existence
  $mins = (int)($d['expires_minutes'] ?? 15);
  if ($mins < 1) { $mins = 1; }
  if ($mins > 1440) { $mins = 1440; }
  $expires = time() + $mins*60;
  $token = AppJwt::issue(['sub'=>(string)$acc['_id'], 'role'=>'client', 'email'=>$email, 'exp'=>$expires]);
  // For demo: return the link instead of sending email
  $base = rtrim((string)($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/');
  $link = $base . '/client-portal.php?magic=' . urlencode($token);
  // Optional email
  $from = AppConfig::string('MAIL_FROM', '');
  $sesRegion = AppConfig::string('SES_REGION', '');
  $sesKey = AppConfig::string('SES_ACCESS_KEY', '');
  $sesSecret = AppConfig::string('SES_SECRET_KEY', '');
  $emailed = false; $messageId = '';
  if ($from !== '' && $sesRegion !== '' && $sesKey !== '' && $sesSecret !== '') {
    try {
      $client = new \Aws\Ses\SesClient([
        'version' => '2010-12-01',
        'region' => $sesRegion,
        'credentials' => [ 'key' => $sesKey, 'secret' => $sesSecret ],
      ]);
  $subject = AppConfig::string('MAIL_SUBJECT_MAGIC', 'Your secure login link');
  $body = "Click to login: $link\n\nThis link expires in $mins minutes.";
      $result = $client->sendEmail([
        'Source' => $from,
        'Destination' => ['ToAddresses' => [$email]],
        'Message' => [
          'Subject' => [ 'Charset' => 'UTF-8', 'Data' => $subject ],
          'Body' => [ 'Text' => [ 'Charset' => 'UTF-8', 'Data' => $body ]],
        ],
      ]);
      $messageId = (string)($result['MessageId'] ?? '');
      $emailed = $messageId !== '';
      error_log('[magic.start] emailed='.$emailed.' messageId='.$messageId.' to='.$email);
    } catch (\Throwable $e) {
      error_log('[magic.start] email error: '.$e->getMessage());
    }
  }
  json_response(['ok'=>true, 'link'=>$link, 'emailed' => $emailed, 'message_id' => $messageId, 'expires_minutes'=>$mins]);
});

// Verify: GET /client/magic/verify?token=...
$router->add('GET', '/api/index.php/client/magic/verify', function(){
  $token = (string)($_GET['token'] ?? '');
  if ($token === '') { json_response(['error'=>'token required'], 400); return; }
  try { $claims = AppJwt::verify($token); } catch(\Throwable $e){ json_response(['error'=>'invalid'], 401); return; }
  if (($claims['role'] ?? '') !== 'client') { json_response(['error'=>'invalid'], 401); return; }
  // Set HttpOnly cookie for client auth
  setcookie('client_jwt', $token, [ 'expires'=>time()+14*24*3600, 'path'=>'/', 'secure'=>true, 'httponly'=>true, 'samesite'=>'Lax' ]);
  json_response(['ok'=>true]);
});

// Me: GET /client/me -> account basic info
$router->add('GET', '/api/index.php/client/me', function(){
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $cookie = $_COOKIE['client_jwt'] ?? '';
  $jwt = '';
  if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $jwt = (string)$m[1]; }
  if ($jwt === '' && $cookie !== '') { $jwt = $cookie; }
  if ($jwt === '') { json_response(['error'=>'missing token'], 401); return; }
  try { $claims = AppJwt::verify($jwt); } catch(\Throwable $e){ json_response(['error'=>'invalid token'], 401); return; }
  $aid = (string)($claims['sub'] ?? '');
  try { $oid = new ObjectId($aid); } catch(\Throwable $e){ json_response(['error'=>'invalid account'], 400); return; }
  $acc = Mongo::collection('accounts')->findOne(['_id'=>$oid]);
  if (!$acc) { json_response(['error'=>'not found'], 404); return; }
  json_response(['id'=>$aid,'name'=>(string)($acc['name']??''),'email'=>(string)($acc['email']??''),'balance_cents'=>(int)($acc['balance_cents']??0)]);
});

// Client: list recent payments (from Stripe) for authenticated client
$router->add('GET', '/api/index.php/client/payments', function(){
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $cookie = $_COOKIE['client_jwt'] ?? '';
  $jwt = '';
  if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $jwt = (string)$m[1]; }
  if ($jwt === '' && $cookie !== '') { $jwt = $cookie; }
  if ($jwt === '') { json_response(['error'=>'missing token'], 401); return; }
  try { $claims = AppJwt::verify($jwt); } catch(\Throwable $e){ json_response(['error'=>'invalid token'], 401); return; }
  if (($claims['role'] ?? '') !== 'client') { json_response(['error'=>'forbidden'], 403); return; }
  $aid = (string)($claims['sub'] ?? '');

  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $sessions = $stripe->checkout->sessions->all(['limit' => 50]);
    $items = [];
    foreach ($sessions->data as $sess) {
      if ((string)($sess->mode ?? '') !== 'payment') continue;
      $metaAid = (string)($sess->metadata['account_id'] ?? '');
      if ($metaAid !== $aid) continue;
      if ((string)($sess->payment_status ?? '') !== 'paid') continue;
      $items[] = [
        'amount_cents' => (int)($sess->amount_total ?? 0),
        'currency' => (string)($sess->currency ?? 'usd'),
        'ts' => date(DATE_ATOM, (int)($sess->created ?? time())),
      ];
    }
    usort($items, function($a,$b){ return strcmp((string)($b['ts']??''), (string)($a['ts']??'')); });
    json_response(['items'=>$items]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe fetch failed'], 500);
  }
});

// Reconcile: POST /client/reconcile-balance -> fetch recent Stripe sessions for this client and update balance
$router->add('POST', '/api/index.php/client/reconcile-balance', function(){
  // Authenticate via client cookie or bearer
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $cookie = $_COOKIE['client_jwt'] ?? '';
  $jwt = '';
  if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) { $jwt = (string)$m[1]; }
  if ($jwt === '' && $cookie !== '') { $jwt = $cookie; }
  if ($jwt === '') { json_response(['error'=>'missing token'], 401); return; }
  try { $claims = AppJwt::verify($jwt); } catch(\Throwable $e){ json_response(['error'=>'invalid token'], 401); return; }
  if (($claims['role'] ?? '') !== 'client') { json_response(['error'=>'forbidden'], 403); return; }
  $aid = (string)($claims['sub'] ?? '');
  try { $oid = new ObjectId($aid); } catch(\Throwable $e){ json_response(['error'=>'invalid account'], 400); return; }

  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['ok'=>false, 'reason'=>'stripe not configured']); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    // Pull recent sessions and upsert any paid ones for this account
    $sessions = $stripe->checkout->sessions->all(['limit' => 100]);
    foreach ($sessions->data as $sess) {
      if ((string)($sess->mode ?? '') !== 'payment') continue;
      $metaAid = (string)($sess->metadata['account_id'] ?? '');
      if ($metaAid !== $aid) continue;
      if ((string)($sess->payment_status ?? '') !== 'paid') continue;
      $amount = (int)($sess->amount_total ?? 0);
      $currency = (string)($sess->currency ?? 'usd');
      $sessionId = (string)($sess->id ?? '');
      if ($sessionId === '' || $amount <= 0) continue;
      Mongo::collection('payments')->updateOne(
        ['stripe_session_id' => $sessionId],
        ['$set' => [
          'account_id' => $aid,
          'amount_cents' => $amount,
          'currency' => $currency,
          'stripe_session_id' => $sessionId,
          'ts' => new UTCDateTime(((int)($sess->created ?? time()))*1000),
        ]],
        ['upsert' => true]
      );
    }
    // Pull paid invoices (subscriptions) and upsert as negative entries
    $invoices = $stripe->invoices->all(['limit' => 100, 'status' => 'paid']);
    foreach ($invoices->data as $inv) {
      $metaAid = (string)($inv->metadata['account_id'] ?? '');
      // If missing on invoice, try subscription metadata
      if ($metaAid === '' && $inv->subscription) {
        try { $sub = $stripe->subscriptions->retrieve((string)$inv->subscription); $metaAid = (string)($sub->metadata['account_id'] ?? ''); } catch (\Throwable $e) { $metaAid = ''; }
      }
      if ($metaAid !== $aid) continue;
      $amount = (int)($inv->total ?? 0);
      if ($amount <= 0) continue;
      $invoiceId = (string)($inv->id ?? '');
      if ($invoiceId === '') continue;
      $currency = (string)($inv->currency ?? 'usd');
      Mongo::collection('payments')->updateOne(
        ['stripe_invoice_id' => $invoiceId],
        ['$set' => [
          'account_id' => $aid,
          'amount_cents' => -1 * $amount,
          'currency' => $currency,
          'stripe_invoice_id' => $invoiceId,
          'stripe_subscription_id' => (string)($inv->subscription ?? ''),
          'type' => 'subscription_charge',
          'ts' => new UTCDateTime(((int)($inv->created ?? time()))*1000),
        ]],
        ['upsert' => true]
      );
    }
    // Recompute balance as sum of payments
    $agg = Mongo::collection('payments')->aggregate([
      ['$match' => ['account_id' => $aid]],
      ['$group' => ['_id' => '$account_id', 'sum' => ['$sum' => '$amount_cents']]],
    ])->toArray();
    $sum = 0; if (!empty($agg)) { $sum = (int)($agg[0]['sum'] ?? 0); }
    Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$set' => ['balance_cents' => $sum]]);
    json_response(['ok'=>true, 'balance_cents'=>$sum]);
  } catch (\Throwable $e) {
    json_response(['ok'=>false], 500);
  }
});

// Fallback: Reconcile a successful Checkout session when redirected back (idempotent)
$router->add('POST', '/api/index.php/payments/reconcile', function(){
  // No admin auth required; reconcile by session_id only (idempotent)
  $d = json_input();
  $sessionId = (string)($d['session_id'] ?? '');
  if ($sessionId === '') { json_response(['error'=>'session_id required'], 400); return; }
  // If we already recorded it, return ok
  $existing = Mongo::collection('payments')->findOne(['stripe_session_id' => $sessionId]);
  if ($existing) { json_response(['ok'=>true, 'recorded'=>true]); return; }

  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $sess = $stripe->checkout->sessions->retrieve($sessionId, [ 'expand' => ['payment_intent'] ]);
    if ((string)($sess->mode ?? '') !== 'payment') { json_response(['ok'=>true, 'skipped'=>'subscription session'], 200); return; }
    $accountId = (string)($sess->metadata['account_id'] ?? '');
    $amount = (int)($sess->amount_total ?? 0);
    $currency = (string)($sess->currency ?? 'usd');
    $created = (int)($sess->created ?? 0);
    if ($accountId === '' || $amount <= 0) { json_response(['error'=>'incomplete session'], 400); return; }
    // Record and update balance (idempotent on session id)
    Mongo::collection('payments')->updateOne(
      ['stripe_session_id' => $sessionId],
      ['$set' => [
        'account_id' => $accountId,
        'amount_cents' => $amount,
        'currency' => $currency,
        'stripe_session_id' => $sessionId,
        'stripe_payment_intent' => (string)($sess->payment_intent?->id ?? ''),
        'ts' => new UTCDateTime((($created>0?$created:time())*1000)),
      ]],
      ['upsert' => true]
    );
    // Increment balance only if we just inserted
    $updated = Mongo::collection('payments')->findOne(['stripe_session_id'=>$sessionId]);
    if ($updated && empty($existing)) {
      try { $oid = new ObjectId($accountId); Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$inc' => ['balance_cents' => $amount]]); } catch(\Throwable $e) { /* ignore */ }
    }
    json_response(['ok'=>true, 'recorded'=>true]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe fetch failed'], 500);
  }
});

// Admin: reconcile balances from Stripe (fetch recent sessions, upsert payments, recompute balances)
$router->add('POST', '/api/index.php/admin/reconcile-balances', function(){
  require_auth(['admin','supervisor']);
  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $sessions = $stripe->checkout->sessions->all(['limit' => 100]);
    foreach ($sessions->data as $sess) {
      $aid = (string)($sess->metadata['account_id'] ?? '');
      if ($aid === '') continue;
      if ((string)($sess->payment_status ?? '') !== 'paid') continue;
      $amount = (int)($sess->amount_total ?? 0);
      $currency = (string)($sess->currency ?? 'usd');
      $sessionId = (string)($sess->id ?? '');
      if ($amount <= 0 || $sessionId === '') continue;
      Mongo::collection('payments')->updateOne(
        ['stripe_session_id' => $sessionId],
        ['$set' => [
          'account_id' => $aid,
          'amount_cents' => $amount,
          'currency' => $currency,
          'stripe_session_id' => $sessionId,
          'ts' => new UTCDateTime(((int)($sess->created ?? time()))*1000),
        ]],
        ['upsert' => true]
      );
    }
    // Also reconcile paid invoices (subscriptions) as negative charges
    $subsCache = [];
    $invoices = $stripe->invoices->all(['limit' => 100, 'status' => 'paid']);
    foreach ($invoices->data as $inv) {
      $subscriptionId = (string)($inv->subscription ?? '');
      $invoiceId = (string)($inv->id ?? '');
      if ($invoiceId === '') continue;
      // Determine account id
      $aid = (string)($inv->metadata['account_id'] ?? '');
      if ($aid === '' && $subscriptionId !== '') {
        if (!array_key_exists($subscriptionId, $subsCache)) {
          try { $subsCache[$subscriptionId] = $stripe->subscriptions->retrieve($subscriptionId); } catch (\Throwable $e) { $subsCache[$subscriptionId] = null; }
        }
        $subObj = $subsCache[$subscriptionId];
        if ($subObj) { $aid = (string)($subObj->metadata['account_id'] ?? ''); }
      }
      if ($aid === '') continue;
      $amount = (int)($inv->total ?? 0);
      if ($amount <= 0) continue;
      $currency = (string)($inv->currency ?? 'usd');
      Mongo::collection('payments')->updateOne(
        ['stripe_invoice_id' => $invoiceId],
        ['$set' => [
          'account_id' => $aid,
          'amount_cents' => -1 * $amount,
          'currency' => $currency,
          'stripe_invoice_id' => $invoiceId,
          'stripe_subscription_id' => $subscriptionId,
          'type' => 'subscription_charge',
          'ts' => new UTCDateTime(((int)($inv->created ?? time()))*1000),
        ]],
        ['upsert' => true]
      );
    }
    // Recompute balances per account
    $agg = Mongo::collection('payments')->aggregate([
      ['$group' => ['_id' => '$account_id', 'sum' => ['$sum' => '$amount_cents']]],
    ]);
    foreach ($agg as $row) {
      $aid = (string)($row['_id'] ?? '');
      $sum = (int)($row['sum'] ?? 0);
      try { $oid = new ObjectId($aid); Mongo::collection('accounts')->updateOne(['_id'=>$oid], ['$set' => ['balance_cents' => $sum]]); } catch (\Throwable $e) { /* ignore */ }
    }
    json_response(['ok'=>true]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe fetch failed'], 500);
  }
});

// Admin: list recent payments
$router->add('GET', '/api/index.php/admin/payments', function(){
  require_auth(['admin','supervisor']);
  $limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));
  $cursor = Mongo::collection('payments')->find([], ['limit'=>$limit, 'sort'=>['ts'=>-1]]);
  $items = [];
  foreach ($cursor as $d) {
    $items[] = [
      'id' => (string)$d['_id'],
      'account_id' => (string)($d['account_id'] ?? ''),
      'amount_cents' => (int)($d['amount_cents'] ?? 0),
      'currency' => (string)($d['currency'] ?? 'usd'),
      'stripe_session_id' => (string)($d['stripe_session_id'] ?? ''),
      'ts' => isset($d['ts']) && $d['ts'] instanceof UTCDateTime ? $d['ts']->toDateTime()->format(DATE_ATOM) : null,
    ];
  }
  json_response(['items'=>$items]);
});

// Admin: list recent payments LIVE from Stripe (no DB dependency)
$router->add('GET', '/api/index.php/admin/payments/stripe', function(){
  require_auth(['admin','supervisor']);
  $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
  $secret = AppConfig::string('STRIPE_SECRET_KEY', '');
  if ($secret === '') { json_response(['error'=>'stripe not configured'], 500); return; }
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $sessions = $stripe->checkout->sessions->all(['limit' => $limit]);
    $items = [];
    foreach ($sessions->data as $sess) {
      if ((string)($sess->mode ?? '') !== 'payment') continue;
      $aid = (string)($sess->metadata['account_id'] ?? '');
      if ($aid === '') continue;
      $items[] = [
        'account_id' => $aid,
        'amount_cents' => (int)($sess->amount_total ?? 0),
        'currency' => (string)($sess->currency ?? 'usd'),
        'stripe_session_id' => (string)($sess->id ?? ''),
        'ts' => date(DATE_ATOM, (int)($sess->created ?? time())),
      ];
    }
    // Sort newest first just in case
    usort($items, function($a,$b){ return strcmp((string)($b['ts']??''), (string)($a['ts']??'')); });
    json_response(['items'=>$items]);
  } catch (\Throwable $e) {
    json_response(['error'=>'stripe fetch failed'], 500);
  }
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/api/index.php');


