<?php
session_start();

// Vulnerable logout - no CSRF protection
// Vulnerable: Session data not properly cleared

// tambahan SESSION CLEAR
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}



// Clear some session data but not all
unset($_SESSION['user_id']);
unset($_SESSION['username']);
// Intentionally leave role and token

// tambah session clear
session_regenerate_id(true);
session_destroy();

// Vulnerable: No session regeneration
// session_regenerate_id();

// Vulnerable: No secure session destroy
// session_destroy();

header('Location: ../../index.php');
exit;
?>