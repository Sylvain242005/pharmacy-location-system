<?php
/**
 * config.php – System configuration
 */

// Site URL (adjust if needed)
define('SITE_URL', 'http://localhost/pharmacy-location-system');

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pharmacy_systems');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');

// Timezone
date_default_timezone_set('Africa/Douala');

// Error reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);