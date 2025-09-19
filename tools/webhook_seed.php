<?php
// Seed all webhook endpoints with sample data using cURL
// Usage: php tools/webhook_seed.php
// Env overrides take precedence; otherwise read from config.php

declare(strict_types=1);

function env(string $key, string $default = ''): string {
	$v = getenv($key);
	return $v === false ? $default : $v;
}

function load_config(string $path): array {
	if (!is_file($path)) return [];
	$cfg = include $path;
	return is_array($cfg) ? $cfg : [];
}

$cfg = load_config(dirname(__DIR__) . '/config.php');
$BASE = rtrim(env('SEED_BASE', (string)($cfg['SEED_BASE'] ?? 'https://demo.crm.allcashhomebuyersnetwork.com')), '/');
$SECRET = env('SEED_SECRET', (string)($cfg['SEED_SECRET'] ?? ''));

function post_json(string $url, array $payload, string $secret = ''): array {
	$ch = curl_init($url);
	$headers = ['Content-Type: application/json'];
	if ($secret !== '') { $headers[] = 'X-Webhook-Secret: ' . $secret; }
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_POSTFIELDS => json_encode($payload),
		CURLOPT_TIMEOUT => 20,
	]);
	$body = curl_exec($ch);
	$err = curl_error($ch);
	$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return ['code' => $code, 'error' => $err, 'body' => $body];
}

function now(): int { return time(); }
function rand_digits(int $len = 10): string { return substr(strval(mt_rand()) . strval(mt_rand()), 0, $len); }

// 1) Call event (baseline calls ingestion)
$callPayload = [
	'direction' => 'outbound',
	'agent_id' => 'agent_' . rand_digits(6),
	'campaign_id' => 'campaign_' . rand_digits(4),
	'contact_phone' => '+1' . rand_digits(10),
	'handle_time_s' => 240,
	'started_at_ts' => now(),
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/call-event', $callPayload, $SECRET);
printf("POST call-event: %d %s\n%s\n\n", $res['code'], $res['error'] ?: '', $res['body']);

// 2) Staffing events
$agentId = 'agent_' . rand_digits(6);
$staffing = [
	['agent_id'=>$agentId,'state'=>'staffed','ts'=>now()-3600],
	['agent_id'=>$agentId,'state'=>'aux','ts'=>now()-1800],
	['agent_id'=>$agentId,'state'=>'staffed','ts'=>now()-900],
];
foreach ($staffing as $p) {
	$res = post_json($BASE . '/api/index.php?route=/webhooks/staffing', $p, $SECRET);
	printf("POST staffing: %d %s\n", $res['code'], $res['error'] ?: '');
}

// 3) Schedule
$schedule = [
	'agent_id' => $agentId,
	'shift_start_ts' => now() - 7200,
	'shift_end_ts' => now() + 3600,
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/schedule', $schedule, $SECRET);
printf("POST schedule: %d %s\n", $res['code'], $res['error'] ?: '');

// 4) Callback create + complete
$cbId = 'cb_' . rand_digits(8);
$callbackCreate = [
	'id' => $cbId,
	'agent_id' => $agentId,
	'due_ts' => now() + 900,
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/callback', $callbackCreate, $SECRET);
printf("POST callback (create): %d %s\n", $res['code'], $res['error'] ?: '');
$callbackDone = [
	'id' => $cbId,
	'agent_id' => $agentId,
	'due_ts' => $callbackCreate['due_ts'],
	'completed_ts' => now() + 1200,
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/callback', $callbackDone, $SECRET);
printf("POST callback (complete): %d %s\n", $res['code'], $res['error'] ?: '');

// 5) Queue events (to compute SL, abandon, wait times)
$callId = 'call_' . rand_digits(8);
$queue = 'sales';
$queueEvents = [
	['call_id'=>$callId,'queue'=>$queue,'event'=>'enqueued','ts'=>now()-100],
	['call_id'=>$callId,'queue'=>$queue,'event'=>'answered','ts'=>now()-40],
];
foreach ($queueEvents as $p) {
	$res = post_json($BASE . '/api/index.php?route=/webhooks/queue-event', $p, $SECRET);
	printf("POST queue-event: %d %s\n", $res['code'], $res['error'] ?: '');
}

// 6) Dial result (RNA metrics)
$dial = [
	'call_id' => 'dial_' . rand_digits(8),
	'result' => 'rna', // connected|rna|busy|fail
	'ts' => now(),
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/dial-result', $dial, $SECRET);
printf("POST dial-result: %d %s\n", $res['code'], $res['error'] ?: '');

// 7) Resolution (FCR)
$resolution = [
	'call_id' => $callId,
	'resolved' => true,
	'ts' => now(),
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/resolution', $resolution, $SECRET);
printf("POST resolution: %d %s\n", $res['code'], $res['error'] ?: '');

// 8) QA scorecard
$qa = [
	'call_id' => $callId,
	'agent_id' => $agentId,
	'score' => 92.5,
	'rubric' => ['greeting'=>5,'compliance'=>5,'probing'=>4,'closure'=>5],
	'ts' => now(),
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/qa', $qa, $SECRET);
printf("POST qa: %d %s\n", $res['code'], $res['error'] ?: '');

// 9) Funnel stage
$leadId = 'lead_' . rand_digits(8);
printf("Lead ID: %s\n", $leadId);
$funnelStages = ['contacted','qualified','appointment','offer','deal'];
$tsBase = now() - 3000;
foreach ($funnelStages as $i => $stage) {
	$p = ['lead_id'=>$leadId,'stage'=>$stage,'ts'=>$tsBase + $i*600];
	$res = post_json($BASE . '/api/index.php?route=/webhooks/funnel', $p, $SECRET);
	printf("POST funnel(%s): %d %s\n", $stage, $res['code'], $res['error'] ?: '');
}

// 10) Geo check
$geo = [
	'call_id' => $callId,
	'phone_geo' => 'NJ',
	'allowed' => true,
	'ts' => now(),
];
$res = post_json($BASE . '/api/index.php?route=/webhooks/geo-check', $geo, $SECRET);
printf("POST geo-check: %d %s\n", $res['code'], $res['error'] ?: '');

echo "\nDone.\n";
