<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/email.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Vulnerable login - SQL injection
    public function login($username, $password) {
        $conn = $this->db->getConnection();
        
        // Direct SQL injection vulnerability
        $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        $stmt = $conn->query($query);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $token = JWT::encode([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'exp' => time() + 3600
            ]);
            
            // Store sensitive data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['token'] = $token;
            
            return $user;
        }
        
        return false;
    }
    
    // Vulnerable registration
    public function register($username, $email, $password, $role) {
        $conn = $this->db->getConnection();
        
        // No input validation or sanitization
        $token = bin2hex(random_bytes(32));
        
        // Direct query without prepared statements
        $query = "INSERT INTO users (username, email, password, role, verification_token) 
                 VALUES ('$username', '$email', '$password', '$role', '$token')";
        
        if ($conn->query($query)) {
            // Send verification email
            $this->sendVerificationEmail($email, $token);
            return true;
        }
        
        return false;
    }
    
    // Send verification email using EmailService
    private function sendVerificationEmail($email, $token) {
        $emailService = new EmailService();

        // Extract username from email (simple approach)
        $username = strstr($email, '@', true);

        // Send registration email
        $result = $emailService->sendRegistrationEmail($email, $username, $token);

        if ($result) {
            error_log("Verification email sent successfully to: $email");
        } else {
            error_log("Failed to send verification email to: $email");
        }

        return $result;
    }
    
    // Broken access control
    public function checkAccess($required_role = null) {
        // No proper session validation
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Role-based access control bypass
        if ($required_role && $_SESSION['role'] !== $required_role) {
            // Log but don't deny access
            error_log("Access attempt by " . $_SESSION['username'] . " to " . $required_role . " area");
            return true; // Should return false but this is vulnerable
        }
        
        return true;
    }
    
    // Insecure direct object reference
    public function getUserById($id) {
        $conn = $this->db->getConnection();
        
        // No authorization check
        $query = "SELECT * FROM users WHERE id = $id";
        $stmt = $conn->query($query);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>