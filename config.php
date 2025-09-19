<?php

// Return an associative array of config values. These override .env and env vars.
// Fill with your real credentials and settings. Do NOT commit secrets to VCS.
return [
  // App
  'APP_DEBUG' => '1',

  // MongoDB
  'MONGODB_URI' => 'mongodb+srv://crm_user:VrA8QKkwunwwQPuO@cluster0.nwagcg.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0',
  'MONGODB_DB' => 'allcashhomebuyersnetwork',
  'MONGODB_COLLECTION' => 'leads',

  // JWT
  'JWT_SECRET' => '35ea1c4e389041daf0bac7a5ac7f0b5e74fa3c10a0cc816cde646772f7712a3519566d0640be3a8e6a7e1626fa142353be8028870df942e5004a202b8cfe03c4',
  'JWT_ISSUER' => 'allcash-crm',
  'JWT_AUDIENCE' => 'allcash-crm',
  'JWT_TTL_SECONDS' => '7200',

  // CORS
  'CORS_ALLOWED_ORIGINS' => 'https://demo.crm.allcashhomebuyersnetwork.com,https://crm.allcashhomebuyersnetwork.com',

  // Webhooks
  'WEBHOOK_SECRET' => 'whs_f0a1b2c3d4e5f60718293a4b5c6d7e8f',

  // Seeder (tools/webhook_seed.php)
  'SEED_BASE' => 'https://demo.crm.allcashhomebuyersnetwork.com',
  'SEED_SECRET' => 'whs_f0a1b2c3d4e5f60718293a4b5c6d7e8f',

  // Branding
  'LOGO_URL' => '/logo.png',

  // Payments (Stripe)
  // Configure via environment (.env): STRIPE_SECRET_KEY, STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET
];


