<?php
/**
 * Session Management
 * This file ensures session is started properly before any output
 */

// Configure session settings BEFORE starting session (only if session not started)
if (session_status() == PHP_SESSION_NONE) {
    // Configure session settings for better compatibility
    ini_set('session.cookie_httponly', 0); // Vulnerable to XSS (for security testing)
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Not secure (for HTTP testing)
    ini_set('session.cookie_samesite', 'Lax'); // Changed from None to Lax for better compatibility

    // Start session
    session_start();
}
