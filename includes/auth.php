<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/email.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    function success_log($message) {
        $logMessage = "Login [" . date('Y-m-d H:i:s') . "] SUCCESS: $message" . PHP_EOL;
        file_put_contents('logs/success.log', $logMessage, FILE_APPEND);
    }
    
    // Vulnerable login - SQL injection
    public function login($username, $password) {
        $conn = $this->db->getConnection();
        
        // ERROR CODE : SQL Injection, Logic Validation for user
        // Direct SQL injection vulnerability -> done - change to prepared statement
        // $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        // $stmt = $conn->query($query);
        $query = "SELECT id, username, role FROM users WHERE username = :username AND password = sha1(:password)";
        $stmt = $conn->prepare($query);
        $stmt->execute([ 
            'username' => $username, 
            'password' => $password
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {

            // check if the user is_verified
            $query = "SELECT id, username, role FROM users WHERE username = :username AND password = sha1(:password) AND is_verified = 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([ 
                'username' => $username, 
                'password' => $password
            ]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user) {
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
                self::success_log("User {$user['username']} successfully logged in");
                
                return $user;
            }
        } else {
            error_log("Login : User $user try to login");
        }
        
        return false;
    }
    
    // Vulnerable registration
    public function register($username, $email, $password, $role) {
        $conn = $this->db->getConnection();
        $response = [
            'message' => "UNKOWN ERROR",
            'status' => false,
        ];

        // username validation - only numeric and alphabet
        $pattern = "/^[a-zA-Z0-9]+$/";
        if(!preg_match($pattern, $username)) {
            $response['message'] = "Username only alphanumeric";
            return $response;
        }

        // email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = "Not a valid email";
            return $response;
        }

        // password validation
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/';
        if (!preg_match($pattern, $password)) {
            $response['message'] = "Password must contain at least one uppercase letter, at least one special character.";
            return $response;
        }

        // check if the email or the user name is already exsits in database
        $query = "SELECT id FROM users WHERE email = :email OR username = :username";
        $stmt = $conn->prepare($query);
        $stmt->execute([ 
            'email' => $email, 
            'username' => $username
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user) {
            $response['message'] = "Email and username must be unique";
            return $response;
        }

        // No input validation or sanitization -> done - validation for all the user input
        $token = bin2hex(random_bytes(32));
        
        // Direct query without prepared statements -> done
        $query = "INSERT INTO users (username, email, password, role, verification_token) 
                 VALUES (:username, :email, :password, :role, :token)";
        $stmt = $conn->prepare($query);
        $isSuccess = $stmt->execute([ 
            'username' => $username, 
            'email' => $email,
            'password' => sha1($password),
            'role' => $role,
            'token' => $token,
        ]);
        
        if ($isSuccess) {
            // Send verification email
            $this->sendVerificationEmail($email, $token);
            $response['status'] = true;
            $response['message'] = "Successfuly create an account";
            return $response;
        }
        
        return $response;
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

        // // No proper session validation
        // if (!isset($_SESSION['user_id'])) {
        //     return false;
        // }

        // // Role-based access control bypass
        // if ($required_role && $_SESSION['role'] !== $required_role) {
        //     // Log but don't deny access
        //     error_log("Access attempt by " . $_SESSION['username'] . " to " . $required_role . " area");
        //     return true; // Should return false but this is vulnerable
        // }

        // check using token, if token valid then validation the role from the database
        if(isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
        }
        if($required_role && $token) {
            $decoded = JWT::decode($token);
            if($decoded) {
                if(isset($_SESSION['user_id'])) {
                    $userId = $_SESSION['user_id'];
                    $conn = $this->db->getConnection();
        
                    $query = "SELECT * FROM users WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute(['id' => $userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($user) {
                        if($user['role'] === $required_role) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    // Insecure direct object reference
    public function getUserById($id) {
        $conn = $this->db->getConnection();
        
        // No authorization check
        // $query = "SELECT * FROM users WHERE id = $id";
        // $stmt = $conn->query($query);
        
        // use prepared statement ->
        // ERROR CODE : 

        // check for user token, only user that already login can check them self
        $token = false;
        if(isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
        }
        if($token) {
            $decoded = JWT::decode($token);
            if($decoded) {
                $userId = $decoded['user_id'];
    
                if($userId != $id) { // sneaky sneaky
                    return false;
                }
    
                $conn = $this->db->getConnection();
    
                $query = "SELECT * FROM users WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if($user) {
                    return $user;
                }
            }

        }
        return false;
    }
}
?>