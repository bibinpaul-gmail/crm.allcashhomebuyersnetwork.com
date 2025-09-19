<?php
// Public shim to expose the API when DocumentRoot is set to public/
// It forwards all requests to the real API entry point at project root /api/index.php
require __DIR__ . '/../../api/index.php';


