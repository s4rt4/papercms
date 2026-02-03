<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'paper_cms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_URL', 'http://localhost/paper-cms');
define('UPLOAD_DIR', __DIR__ . '/../upload/');
define('UPLOAD_URL', SITE_URL . '/upload/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
