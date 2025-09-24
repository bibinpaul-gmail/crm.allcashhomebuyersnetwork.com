<?php
session_start();

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helpers
function h(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function sanitizeText(?string $value): string {
  if ($value === null) return '';
  $value = trim($value);
  $value = preg_replace('/[\r\n\t]+/', ' ', $value);
  return $value;
}

function validatePhone(?string $value): bool {
  if ($value === null) return false;
  $v = preg_replace('/[^0-9\+\-\(\)\s]/', '', $value);
  return (bool)preg_match('/^[0-9\+\-\(\)\s]{7,20}$/', $v);
}

function getEnvOrDefault(string $key, string $default): string {
  $v = getenv($key);
  return $v !== false && $v !== '' ? $v : $default;
}

$errors = [];
$successMessage = '';
$mongoError = '';
$prefillNotice = '';

// Brand / assets
$logoUrl = getEnvOrDefault('LOGO_URL', '/logo.png');

// Preserve previously submitted values
$form = [
  'agent_name' => '',
  'customer_name' => '',
  'customer_phone' => '',
  'property_street' => '',
  'property_city' => '',
  'property_state' => '',
  'property_zip' => '',
  'owner_trustee' => '',
  'relationship_to_owner' => '',
  'listing_expiry_month' => '',
  'listing_expiry_year' => '',
  'home_type' => '',
  'home_condition' => '',
  'bedrooms' => '',
  'bathrooms' => '',
  'listed_with_agent' => '',
  'notes' => ''
];

// Prefill from URL params on initial GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $agentFirst = sanitizeText($_GET['agentFirstName'] ?? '');
  $agentLast = sanitizeText($_GET['agentLastName'] ?? '');
  $phoneParam = sanitizeText($_GET['phone'] ?? '');
  if ($agentFirst || $agentLast) {
    $form['agent_name'] = trim($agentFirst . ' ' . $agentLast);
  }
  if ($phoneParam) {
    $form['customer_phone'] = $phoneParam;

    // Attempt prefill from MongoDB based on phone
    if (extension_loaded('mongodb')) {
      $mongoUri = getEnvOrDefault('MONGODB_URI', 'mongodb+srv://crm_user:VrA8QKkwunwwQPuO@cluster0.nwagcg.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0');
      $mongoDb = getEnvOrDefault('MONGODB_DB', 'allcashhomebuyersnetwork');
      $mongoCollection = getEnvOrDefault('MONGODB_COLLECTION', 'leads');
      try {
        $manager = new MongoDB\Driver\Manager($mongoUri);
        $query = new MongoDB\Driver\Query(
          ['customer_phone' => $phoneParam],
          ['sort' => ['created_at' => -1], 'limit' => 1]
        );
        $cursor = $manager->executeQuery($mongoDb . '.' . $mongoCollection, $query);
        $docs = $cursor->toArray();
        if (!empty($docs)) {
          $d = $docs[0];
          // Safely map known fields
          $form['customer_name'] = $form['customer_name'] ?: (isset($d->customer_name) ? (string)$d->customer_name : '');
          if (isset($d->property) && is_object($d->property)) {
            $form['property_street'] = $form['property_street'] ?: (isset($d->property->street) ? (string)$d->property->street : '');
            $form['property_city'] = $form['property_city'] ?: (isset($d->property->city) ? (string)$d->property->city : '');
            $form['property_state'] = $form['property_state'] ?: (isset($d->property->state) ? (string)$d->property->state : '');
            $form['property_zip'] = $form['property_zip'] ?: (isset($d->property->zip) ? (string)$d->property->zip : '');
          }
          $form['owner_trustee'] = $form['owner_trustee'] ?: (isset($d->owner_trustee) ? (string)$d->owner_trustee : '');
          $form['home_type'] = $form['home_type'] ?: (isset($d->home_type) ? (string)$d->home_type : '');
          $form['home_condition'] = $form['home_condition'] ?: (isset($d->home_condition) ? (string)$d->home_condition : '');
          $form['bedrooms'] = $form['bedrooms'] ?: (isset($d->bedrooms) ? (string)$d->bedrooms : '');
          $form['bathrooms'] = $form['bathrooms'] ?: (isset($d->bathrooms) ? (string)$d->bathrooms : '');
          $form['listed_with_agent'] = $form['listed_with_agent'] ?: (isset($d->listed_with_agent) ? (string)$d->listed_with_agent : '');
          $form['notes'] = $form['notes'] ?: (isset($d->notes) ? (string)$d->notes : '');
          $prefillNotice = 'Previous lead found for this phone. Fields prefilled.';
        }
      } catch (Throwable $e) {
        // Silent prefill failure, do not block UI
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    $errors['csrf'] = 'Security validation failed. Please refresh and try again.';
  } else {
    // Sanitize
    $form['agent_name'] = sanitizeText($_POST['agent_name'] ?? '');
    $form['customer_name'] = sanitizeText($_POST['customer_name'] ?? '');
    $form['customer_phone'] = sanitizeText($_POST['customer_phone'] ?? '');
    $form['property_street'] = sanitizeText($_POST['property_street'] ?? '');
    $form['property_city'] = sanitizeText($_POST['property_city'] ?? '');
    $form['property_state'] = sanitizeText($_POST['property_state'] ?? '');
    $form['property_zip'] = sanitizeText($_POST['property_zip'] ?? '');
    $form['owner_trustee'] = sanitizeText($_POST['owner_trustee'] ?? '');
    $form['home_type'] = sanitizeText($_POST['home_type'] ?? '');
    $form['home_condition'] = sanitizeText($_POST['home_condition'] ?? '');
    $form['bedrooms'] = sanitizeText($_POST['bedrooms'] ?? '');
    $form['bathrooms'] = sanitizeText($_POST['bathrooms'] ?? '');
    $form['listed_with_agent'] = sanitizeText($_POST['listed_with_agent'] ?? '');
    $form['relationship_to_owner'] = sanitizeText($_POST['relationship_to_owner'] ?? '');
    $form['listing_expiry_month'] = sanitizeText($_POST['listing_expiry_month'] ?? '');
    $form['listing_expiry_year'] = sanitizeText($_POST['listing_expiry_year'] ?? '');
    $form['notes'] = sanitizeText($_POST['notes'] ?? '');

    // Validate required fields (conditional based on previous answers)
    if ($form['agent_name'] === '') $errors['agent_name'] = 'Agent name is required.';
    if ($form['customer_name'] === '') $errors['customer_name'] = 'Customer name is required.';
    if (!validatePhone($form['customer_phone'])) $errors['customer_phone'] = 'Enter a valid phone number.';
    if ($form['property_street'] === '') $errors['property_street'] = 'Street is required.';
    if ($form['property_city'] === '') $errors['property_city'] = 'City is required.';
    if ($form['property_state'] === '') $errors['property_state'] = 'State is required.';
    if ($form['property_zip'] === '') $errors['property_zip'] = 'ZIP is required.';
    if ($form['owner_trustee'] !== 'Yes' && $form['owner_trustee'] !== 'No') $errors['owner_trustee'] = 'Select Yes or No.';

    $requiresPropertyDetails = ($form['owner_trustee'] === 'Yes');
    if ($requiresPropertyDetails) {
      if ($form['home_type'] === '') $errors['home_type'] = 'Select a home type.';
      if ($form['home_condition'] === '') $errors['home_condition'] = 'Select a condition.';
      if ($form['bedrooms'] === '') $errors['bedrooms'] = 'Select bedrooms.';
      if ($form['bathrooms'] === '') $errors['bathrooms'] = 'Select bathrooms.';
      if ($form['listed_with_agent'] !== 'Yes' && $form['listed_with_agent'] !== 'No') $errors['listed_with_agent'] = 'Select Yes or No.';
    } else if ($form['owner_trustee'] === 'No') {
      $allowedRel = ['Spouse','Parent','Son/Daughter','Friend','Trustee'];
      if ($form['relationship_to_owner'] === '' || !in_array($form['relationship_to_owner'], $allowedRel, true)) {
        $errors['relationship_to_owner'] = 'Select relationship to owner.';
      }
    }

    if ($form['listed_with_agent'] === 'Yes') {
      if ($form['listing_expiry_month'] === '') $errors['listing_expiry_month'] = 'Select month.';
      if ($form['listing_expiry_year'] === '') $errors['listing_expiry_year'] = 'Select year.';
    }

    // Persist
    if (empty($errors)) {
      $mongoUri = getEnvOrDefault('MONGODB_URI', 'mongodb+srv://crm_user:VrA8QKkwunwwQPuO@cluster0.nwagcg.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0');
      $mongoDb = getEnvOrDefault('MONGODB_DB', 'allcashhomebuyersnetwork');
      $mongoCollection = getEnvOrDefault('MONGODB_COLLECTION', 'leads');

      try {
        $manager = new MongoDB\Driver\Manager($mongoUri);

        $document = [
          'agent_name' => $form['agent_name'],
          'customer_name' => $form['customer_name'],
          'customer_phone' => $form['customer_phone'],
          'property' => [
            'street' => $form['property_street'],
            'city' => $form['property_city'],
            'state' => $form['property_state'],
            'zip' => $form['property_zip']
          ],
          'owner_trustee' => $form['owner_trustee'],
          'relationship_to_owner' => $form['relationship_to_owner'],
          'home_type' => $form['home_type'],
          'home_condition' => $form['home_condition'],
          'bedrooms' => $form['bedrooms'],
          'bathrooms' => $form['bathrooms'],
          'listed_with_agent' => $form['listed_with_agent'],
          'listing_expiry_month' => $form['listing_expiry_month'],
          'listing_expiry_year' => $form['listing_expiry_year'],
          'notes' => $form['notes'],
          'script_meta' => [
            'qualified' => ($form['owner_trustee'] === 'Yes' && $form['listed_with_agent'] === 'No'),
            'disposition' => ($form['owner_trustee'] === 'No') ? 'not_owner' : (($form['listed_with_agent'] === 'Yes') ? 'already_listed' : 'qualified')
          ],
          'created_at' => new MongoDB\BSON\UTCDateTime((new DateTime('now', new DateTimeZone('UTC')))->getTimestamp() * 1000)
        ];

        $bulk = new MongoDB\Driver\BulkWrite();
        $insertedId = $bulk->insert($document);
        $wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 10000);
        $result = $manager->executeBulkWrite($mongoDb . '.' . $mongoCollection, $bulk, $wc);

        if ($result->getInsertedCount() === 1) {
          $successMessage = 'Saved successfully. Lead ID: ' . (string)$insertedId;
        } else {
          $mongoError = 'Save may not have completed. Please verify.';
        }
      } catch (Throwable $e) {
        $mongoError = 'Database error: ' . $e->getMessage();
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Cash Home Buyers Network - Call Center CRM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="color-scheme" content="light dark">
  <style>
    .field-error { color: #b91c1c; }
    html, body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
    :root { --brand-blue: #0b5aa6; --brand-green: #6bb31a; --brand-blue-600: #0a4e93; --brand-green-600: #5aa214; }
    .brand-btn { background-image: linear-gradient(90deg, var(--brand-blue), var(--brand-green)); }
    .brand-btn:hover { background-image: linear-gradient(90deg, var(--brand-blue-600), var(--brand-green-600)); }
    .brand-text { background-image: linear-gradient(90deg, var(--brand-blue), var(--brand-green)); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .focus-brand:focus { border-color: var(--brand-blue); outline: none; box-shadow: 0 0 0 3px rgba(11, 90, 166, 0.25); }
    .accent-brand { accent-color: var(--brand-blue); }
    .brand-blue { color: var(--brand-blue); }
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-[#f0f6ff] via-white to-[#f3fbec] text-slate-900">
  <div class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-6">
      <div class="flex flex-col gap-2">
        <img src="<?php echo h($logoUrl); ?>" alt="logo" class="self-center" style="width:102.5px;height:auto"/>
        <p class="brand-blue text-base sm:text-lg font-semibold">Home Buying Agent Call Script & Lead Capture</p>
      </div>
    </header>

    <?php if ($successMessage): ?>
      <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
        <div class="font-semibold">Success</div>
        <div><?php echo h($successMessage); ?></div>
      </div>
    <?php endif; ?>

    <?php if ($prefillNotice): ?>
      <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sky-900">
        <div class="font-semibold">Prefilled</div>
        <div><?php echo h($prefillNotice); ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors) || $mongoError): ?>
      <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-900">
        <div class="font-semibold">Please review the following</div>
        <ul class="list-disc pl-5">
          <?php foreach ($errors as $msg): ?>
            <li><?php echo h($msg); ?></li>
          <?php endforeach; ?>
          <?php if ($mongoError): ?>
            <li><?php echo h($mongoError); ?></li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="grid grid-cols-1 gap-6">
      <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

      <section class="rounded-xl border border-slate-200/70 bg-white shadow-lg">
        <div class="border-b border-slate-200 p-4 sm:p-5">
          <h2 class="text-lg font-semibold">Opening Script</h2>
          <p class="mt-2 text-sm text-slate-600">
            My name is <em>Agent FIRST and LAST NAME</em> with All Cash Home Buyers Network on a recorded line. I am happy to help you get FAST CASH for your home!
          </p>
        </div>
        <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Agent Name</label>
            <input type="text" name="agent_name" value="<?php echo h($form['agent_name']); ?>" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" placeholder="First Last" required>
            <?php if (isset($errors['agent_name'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['agent_name']); ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Customer Name</label>
            <input type="text" name="customer_name" value="<?php echo h($form['customer_name']); ?>" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" placeholder="Customer full name" required>
            <?php if (isset($errors['customer_name'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['customer_name']); ?></p><?php endif; ?>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Best Callback Number</label>
            <input type="tel" name="customer_phone" value="<?php echo h($form['customer_phone']); ?>" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" placeholder="e.g. (555) 555-5555" required>
            <?php if (isset($errors['customer_phone'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['customer_phone']); ?></p><?php endif; ?>
          </div>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200/70 bg-white shadow-lg">
        <div class="border-b border-slate-200 p-4 sm:p-5">
          <h2 class="text-lg font-semibold">Property Address</h2>
          <p class="mt-2 text-sm text-slate-600">What is the property address that you are seeking cash for?</p>
        </div>
        <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Street</label>
            <input type="text" name="property_street" value="<?php echo h($form['property_street']); ?>" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" required>
            <?php if (isset($errors['property_street'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['property_street']); ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">City</label>
            <input type="text" name="property_city" value="<?php echo h($form['property_city']); ?>" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" required>
            <?php if (isset($errors['property_city'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['property_city']); ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">State</label>
            <input type="text" name="property_state" value="<?php echo h($form['property_state']); ?>" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" placeholder="e.g. FL" required>
            <?php if (isset($errors['property_state'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['property_state']); ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">ZIP</label>
            <input type="text" name="property_zip" value="<?php echo h($form['property_zip']); ?>" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" required>
            <?php if (isset($errors['property_zip'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['property_zip']); ?></p><?php endif; ?>
          </div>

        </div>
      </section>

      <section class="rounded-xl border border-slate-200/70 bg-white shadow-lg">
        <div class="border-b border-slate-200 p-4 sm:p-5">
          <h2 class="text-lg font-semibold">Ownership</h2>
          <p class="mt-2 text-sm text-slate-600">Are you the owner / trustee of the property located at the address above?</p>
        </div>
        <div class="p-4 sm:p-5 grid grid-cols-1 gap-4">
          <div class="flex items-center gap-6">
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="owner_trustee" value="Yes" <?php echo $form['owner_trustee']==='Yes'?'checked':''; ?> class="h-5 w-5 accent-brand"> <span>Yes</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="owner_trustee" value="No" <?php echo $form['owner_trustee']==='No'?'checked':''; ?> class="h-5 w-5 accent-brand"> <span>No</span>
            </label>
          </div>
          <?php if (isset($errors['owner_trustee'])): ?><p class="field-error text-sm"><?php echo h($errors['owner_trustee']); ?></p><?php endif; ?>
          <div data-banner="not-owner" class="hidden rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
            Please tell me how you are related to the owner.
          </div>
          <div data-section="relationship" aria-hidden="true" class="hidden">
            <label class="block text-sm font-medium text-slate-700 mt-2">How are you related to the owner of this property?</label>
            <select name="relationship_to_owner" class="mt-1 w-full max-w-md rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand">
              <option value="" disabled <?php echo $form['relationship_to_owner']===''?'selected':''; ?>>Select relationship</option>
              <?php foreach (['Spouse','Parent','Son/Daughter','Friend','Trustee'] as $r): $sel = $form['relationship_to_owner'] === $r ? 'selected' : ''; ?>
              <option value="<?php echo h($r); ?>" <?php echo $sel; ?>><?php echo h($r); ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['relationship_to_owner'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['relationship_to_owner']); ?></p><?php endif; ?>
          </div>
        </div>
      </section>

      <section data-section="property-details" aria-hidden="true" class="rounded-xl border border-slate-200/70 bg-white shadow-lg <?php echo ($form['owner_trustee']==='Yes')? '': 'hidden'; ?>">
        <div class="border-b border-slate-200 p-4 sm:p-5">
          <h2 class="text-lg font-semibold">Property Details</h2>
        </div>
        <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Type of Home</label>
            <select name="home_type" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" data-required-group="property-details" <?php echo ($form['owner_trustee']==='Yes')? 'required': ''; ?>>
              <option value="" disabled <?php echo $form['home_type']===''?'selected':''; ?>>Select type</option>
              <?php
                $types = ['Single Family','Two-Family','Multi-unit Home','Condo/Coop','Mobile/Manufactured Home'];
                foreach ($types as $t) {
                  $sel = $form['home_type'] === $t ? 'selected' : '';
                  echo '<option value="'.h($t).'" '.$sel.'>'.h($t).'</option>';
                }
              ?>
            </select>
            <?php if (isset($errors['home_type'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['home_type']); ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Condition</label>
            <select name="home_condition" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" data-required-group="property-details" <?php echo ($form['owner_trustee']==='Yes')? 'required': ''; ?>>
              <option value="" disabled <?php echo $form['home_condition']===''?'selected':''; ?>>Select condition</option>
              <?php
                $conds = ['Excellent – Like new','Good – Ready to Move in','Average – Needs some work','Needs Work – Repairs Needed','Major Repairs Needed','Tear-down'];
                foreach ($conds as $c) {
                  $sel = $form['home_condition'] === $c ? 'selected' : '';
                  echo '<option value="'.h($c).'" '.$sel.'>'.h($c).'</option>';
                }
              ?>
            </select>
            <?php if (isset($errors['home_condition'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['home_condition']); ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Bedrooms</label>
            <select name="bedrooms" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" data-required-group="property-details" <?php echo ($form['owner_trustee']==='Yes')? 'required': ''; ?>>
              <option value="" disabled <?php echo $form['bedrooms']===''?'selected':''; ?>>Select</option>
              <?php for ($i=1; $i<=6; $i++): $label = ($i===6)?'6+':(string)$i; ?>
                <option value="<?php echo h($label); ?>" <?php echo $form['bedrooms']===$label?'selected':''; ?>><?php echo h($label); ?></option>
              <?php endfor; ?>
            </select>
            <?php if (isset($errors['bedrooms'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['bedrooms']); ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Bathrooms</label>
            <select name="bathrooms" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" data-required-group="property-details" <?php echo ($form['owner_trustee']==='Yes')? 'required': ''; ?>>
              <option value="" disabled <?php echo $form['bathrooms']===''?'selected':''; ?>>Select</option>
              <?php for ($i=1; $i<=6; $i++): $label = ($i===6)?'6+':(string)$i; ?>
                <option value="<?php echo h($label); ?>" <?php echo $form['bathrooms']===$label?'selected':''; ?>><?php echo h($label); ?></option>
              <?php endfor; ?>
            </select>
            <?php if (isset($errors['bathrooms'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['bathrooms']); ?></p><?php endif; ?>
          </div>
        </div>
      </section>

      <section data-section="listing-status" aria-hidden="true" class="rounded-xl border border-slate-200/70 bg-white shadow-lg <?php echo ($form['owner_trustee']==='Yes')? '': 'hidden'; ?>">
        <div class="border-b border-slate-200 p-4 sm:p-5">
          <h2 class="text-lg font-semibold">Listing Status</h2>
          <p class="mt-2 text-sm text-slate-600">Is the property currently listed through a real estate agent or other service?</p>
        </div>
        <div class="p-4 sm:p-5 grid grid-cols-1 gap-4">
          <div class="flex items-center gap-6">
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="listed_with_agent" value="Yes" <?php echo $form['listed_with_agent']==='Yes'?'checked':''; ?> class="h-5 w-5 accent-brand" data-required-group="listing-status" <?php echo ($form['owner_trustee']==='Yes')? 'required': ''; ?>> <span>Yes</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="listed_with_agent" value="No" <?php echo $form['listed_with_agent']==='No'?'checked':''; ?> class="h-5 w-5 accent-brand" data-required-group="listing-status" <?php echo ($form['owner_trustee']==='Yes')? 'required': ''; ?>> <span>No</span>
            </label>
          </div>
          <?php if (isset($errors['listed_with_agent'])): ?><p class="field-error text-sm"><?php echo h($errors['listed_with_agent']); ?></p><?php endif; ?>
          <div data-banner="listed-yes" class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            No problem. When do you expect the listing to expire; I need the estimated month and year.
          </div>
          <div data-section="listing-expiry" aria-hidden="true" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700">Month</label>
              <select name="listing_expiry_month" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand">
                <option value="" disabled <?php echo $form['listing_expiry_month']===''?'selected':''; ?>>Select month</option>
                <?php foreach (["January","February","March","April","May","June","July","August","September","October","November","December"] as $m): $sel = $form['listing_expiry_month']===$m?'selected':''; ?>
                  <option value="<?php echo h($m); ?>" <?php echo $sel; ?>><?php echo h($m); ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['listing_expiry_month'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['listing_expiry_month']); ?></p><?php endif; ?>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Year</label>
              <select name="listing_expiry_year" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand">
                <option value="" disabled <?php echo $form['listing_expiry_year']===''?'selected':''; ?>>Select year</option>
                <?php for ($y=(int)date('Y'); $y<=((int)date('Y')+2); $y++): $sel = $form['listing_expiry_year']===(string)$y?'selected':''; ?>
                  <option value="<?php echo h((string)$y); ?>" <?php echo $sel; ?>><?php echo h((string)$y); ?></option>
                <?php endfor; ?>
              </select>
              <?php if (isset($errors['listing_expiry_year'])): ?><p class="field-error text-sm mt-1"><?php echo h($errors['listing_expiry_year']); ?></p><?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <section data-section="warm-transfer" aria-hidden="true" class="rounded-xl border border-slate-200/70 bg-white shadow-lg <?php echo ($form['owner_trustee']==='Yes' && $form['listed_with_agent']==='No')? '': 'hidden'; ?>">
        <div class="border-b border-slate-200 p-4 sm:p-5">
          <h2 class="text-lg font-semibold">Warm Transfer Script</h2>
          <p class="mt-2 text-sm text-slate-600">Use this when qualified (Owner = Yes and Listed = No).</p>
        </div>
        <div class="p-4 sm:p-5 grid grid-cols-1 gap-4">
          <div class="rounded-lg bg-slate-50 p-4 text-sm">
            <p><span class="font-semibold">Great news!</span> Looks like you qualify for a cash offer! Let me get a cash expert on the line who can get this started.</p>
            <p class="mt-3"><span class="font-semibold">Dial:</span> <span class="font-mono">999-999-9999</span></p>
            <p class="mt-2">Hi, I have <span class="font-semibold" data-preview="customer_name"><?php echo h($form['customer_name']); ?></span> on the line who is looking for a cash buy-out for a <span class="font-semibold"><span data-preview="bedrooms_label"><?php echo h($form['bedrooms'] ? ($form['bedrooms'] . ' Bedroom') : ''); ?></span>, <span data-preview="home_type_label"><?php echo h($form['home_type'] ? (preg_match('/Home\s*$/i', $form['home_type']) ? $form['home_type'] : $form['home_type'] . ' Home') : ''); ?></span></span> in <span class="font-semibold"><span data-preview="property_city"><?php echo h($form['property_city']); ?></span>, <span data-preview="property_state"><?php echo h($form['property_state']); ?></span></span>. Their number is <span class="font-mono" data-preview="customer_phone"><?php echo h($form['customer_phone']); ?></span>.</p>
            <p class="mt-2"><span class="font-semibold" data-preview="customer_name_2"><?php echo h($form['customer_name']); ?></span>, you are in good hands. Thank you for calling All Cash Home Buyers Network!</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Internal Notes (optional)</label>
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-base focus-brand" placeholder="Anything else relevant?"><?php echo h($form['notes']); ?></textarea>
          </div>
        </div>
      </section>

      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <button type="submit" class="inline-flex justify-center rounded-lg brand-btn px-5 py-2.5 font-medium text-white shadow focus:outline-none">
          Save to CRM
        </button>
      </div>
    </form>

    <footer class="mt-10 text-center text-xs text-slate-400">
      <p>&copy; <?php echo date('Y'); ?> All Cash Home Buyers Network</p>
      <?php if (!extension_loaded('mongodb')): ?>
        <p class="mt-2 text-rose-600">MongoDB PHP extension not detected. Install via PECL: <span class="font-mono">pecl install mongodb</span> and enable in <span class="font-mono">php.ini</span>.</p>
      <?php endif; ?>
    </footer>
  </div>

  <script>
    (function() {
      const $$ = (s) => Array.from(document.querySelectorAll(s));
      function setVisible(el, visible) {
        if (!el) return;
        el.classList.toggle('hidden', !visible);
        el.setAttribute('aria-hidden', visible ? 'false' : 'true');
        el.style.display = visible ? '' : 'none';
      }
      function setGroupRequired(selector, isRequired) {
        $$(selector).forEach(function(el){
          if (isRequired) {
            el.setAttribute('required', 'required');
          } else {
            el.removeAttribute('required');
          }
        });
      }
      function updateFlow() {
        const owner = document.querySelector('input[name="owner_trustee"]:checked')?.value;
        const listed = document.querySelector('input[name="listed_with_agent"]:checked')?.value;
        const relationship = (document.querySelector('select[name="relationship_to_owner"]')?.value || '').trim();

        const propertyVisible = owner === 'Yes' || (owner === 'No' && relationship !== '');
        const listingVisible = owner === 'Yes' || (owner === 'No' && relationship !== '');
        const warmVisible = owner === 'Yes' && listed === 'No';
        const relationshipVisible = owner === 'No';
        const listingExpiryVisible = listed === 'Yes';

        setVisible(document.querySelector('[data-section="property-details"]'), propertyVisible);
        setVisible(document.querySelector('[data-section="listing-status"]'), listingVisible);
        setVisible(document.querySelector('[data-section="warm-transfer"]'), warmVisible);
        setVisible(document.querySelector('[data-section="relationship"]'), relationshipVisible);
        setVisible(document.querySelector('[data-section="listing-expiry"]'), listingExpiryVisible);

        setVisible(document.querySelector('[data-banner="not-owner"]'), owner === 'No');
        setVisible(document.querySelector('[data-banner="listed-yes"]'), listed === 'Yes');

        setGroupRequired('[data-required-group="property-details"]', propertyVisible);
        setGroupRequired('[data-required-group="listing-status"]', listingVisible);
        setGroupRequired('[name="relationship_to_owner"]', relationshipVisible);
        setGroupRequired('[name="listing_expiry_month"]', listingExpiryVisible);
        setGroupRequired('[name="listing_expiry_year"]', listingExpiryVisible);
      }

      function getValue(name) {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) return '';
        if (el.tagName === 'SELECT') {
          return el.value || '';
        }
        return el.value || '';
      }

      function setPreview(attr, value, fallback='') {
        $$("[data-preview='" + attr + "']").forEach(function(n){ n.textContent = value || fallback; });
      }

      function updatePreview() {
        const beds = getValue('bedrooms');
        const bedsLabel = beds ? (beds + ' Bedroom') : '<number of bedrooms>';
        setPreview('customer_name', getValue('customer_name'), '<customer name>');
        setPreview('customer_name_2', getValue('customer_name'), '<customer name>');
        setPreview('bedrooms', beds, '<number of bedrooms>');
        setPreview('bedrooms_label', bedsLabel, '<number of bedrooms>');
        const type = getValue('home_type');
        const typeLabel = type ? (/Home\s*$/i.test(type) ? type : type + ' Home') : '<type of home>';
        setPreview('home_type', type, '<type of home>');
        setPreview('home_type_label', typeLabel, '<type of home>');
        setPreview('property_city', getValue('property_city'), 'city');
        setPreview('property_state', getValue('property_state'), 'state');
        setPreview('customer_phone', getValue('customer_phone'), '777-777-7777');
      }
      $$("input[name='owner_trustee'], input[name='listed_with_agent']").forEach(function(el){
        el.addEventListener('change', function(){ updateFlow(); updatePreview(); });
      });
      $$("input[name='customer_name'], input[name='customer_phone'], input[name='property_city'], input[name='property_state']").forEach(function(el){
        el.addEventListener('input', updatePreview);
      });
      $$("select[name='bedrooms'], select[name='home_type']").forEach(function(el){
        el.addEventListener('change', updatePreview);
      });
      $$("select[name='relationship_to_owner']").forEach(function(el){
        el.addEventListener('change', function(){ updateFlow(); updatePreview(); });
      });
      document.addEventListener('DOMContentLoaded', function(){ updateFlow(); updatePreview(); });
      updateFlow();
      updatePreview();
    })();
  </script>
</body>
</html>


