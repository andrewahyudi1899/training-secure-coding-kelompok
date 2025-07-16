<?php
session_start();

// Vulnerable logout - no CSRF protection
// Vulnerable: Session data not properly cleared

// Clear some session data but not all
unset($_SESSION['user_id']);
unset($_SESSION['username']);
// Intentionally leave role and token

// Vulnerable: No session regeneration
// session_regenerate_id();

// Vulnerable: No secure session destroy
// session_destroy();

header('Location: ../../index.php');
exit;
?>