<?php
require_once '../../config/env.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    require_once '../../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    // validate the email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Not a valid email";
    } else {
        // Vulnerable: SQL injection
        // $query = "SELECT * FROM users WHERE email = '$email'";
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->execute([ 
            'email' => $email
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // $result = $conn->query($query);
        // $user = $result->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $reset_token = bin2hex(random_bytes(32));
            
            // Store reset token
            $update_query = "UPDATE users SET verification_token = :reset_token WHERE email = :email";
            $stmt = $conn->prepare($update_query);
            $stmt->execute([ 
                'reset_token' => $reset_token,
                'email' => $email
            ]);
            $is_success = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // $update_query = "UPDATE users SET verification_token = '$reset_token' WHERE email = '$email'";
            // $conn->query($update_query);
            
            // Send reset email (vulnerable)
            $subject = "Password Reset Request";
            $message_body = "Click this link to reset your password: " . BASE_URL . "/pages/auth/reset-password.php?token=$reset_token";
            
            // Vulnerable: Headers injection
            $headers = "From: noreply@jobportal.com\r\n";
            // $headers .= $_POST['custom_header'] ?? ''; // Header injection vulnerability
            
            mail($email, $subject, $message_body, $headers);
            
            $message = 'Password reset link sent to your email.';
        } else {
            $error = 'Email not found.';
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p><a href="login.php">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>