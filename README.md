## Callcenter CRM Admin (PHP + MongoDB + Tailwind)

### Prerequisites
- PHP 8.1+
- MongoDB server (Atlas or local)
- PHP ext-mongodb (pecl install mongodb) for runtime DB access

### Setup
1. Copy env:
   - cp .env.example .env
   - Update MONGODB_URI and MONGODB_DB
2. Install dependencies:
   - php -r "copy('https://getcomposer.org/installer','composer-setup.php');" && php composer-setup.php && php -r "unlink('composer-setup.php');"
   - php composer.phar install --no-interaction --no-progress --ignore-platform-req=ext-mongodb
3. Create indexes:
   - php scripts/create_indexes.php
4. Seed admin user:
   - php scripts/seed.php
5. Dev server:
   - php -S 127.0.0.1:8000 -t public
   - Open http://127.0.0.1:8000/public/admin.php

### API Highlights
- POST /api/index.php/login
- GET  /api/index.php/metrics
- CRUD: /api/index.php/agents, /contacts, /campaigns, /calls
- DNC:  POST /api/index.php/dnc, GET /api/index.php/dnc/check?phone=...
- Webhook: POST /api/index.php/webhooks/call-event
- Reports: /api/index.php/reports/*

### Security
- JWT Bearer tokens, RBAC (admin, supervisor, agent)
- Passwords hashed using Argon2id
- Audit logs recorded for sensitive actions


