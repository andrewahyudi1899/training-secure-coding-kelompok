<?php
// Database configuration - exposed credentials
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
// define('DB_HOST', $_ENV['DB_HOST'] ?? 'job_seeker_polytron');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'db_job_seeker_polytron');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// JWT Secret - weak secret exposed in client-side
define('JWT_SECRET', 'weak_secret_key_123');

// Mailtrap configuration - exposed credentials
define('MAILTRAP_HOST', 'sandbox.smtp.mailtrap.io');
define('MAILTRAP_PORT', 2525);
define('MAILTRAP_USERNAME', 'c422d05e0331d3');
define('MAILTRAP_PASSWORD', '76eae054995016');

// File upload configuration - vulnerable settings
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'php', 'exe']); // Dangerous types allowed
define('UPLOAD_PATH', 'uploads/');

// Base URL
define('BASE_URL', 'http://localhost:8004');

// Session configuration moved to includes/session.php
?>