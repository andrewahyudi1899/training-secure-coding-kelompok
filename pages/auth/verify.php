<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../config/database.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$message = '';
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Vulnerable: Direct SQL injection
    // $query = "SELECT * FROM users WHERE verification_token = '$token'";
    // $result = $conn->query($query);
    // $user = $result->fetch(PDO::FETCH_ASSOC);
    
    $query = "SELECT * FROM users WHERE verification_token = :token";
    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        'token' => $token
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update user as verified
        $update_query = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id";
        $stmt = $conn->prepare($update_query);
        $is_success = $stmt->execute([ 
            'id' => $user['id']
        ]);

        if ($is_success) {
            $message = 'Email verified successfully! You can now login.';
        } else {
            $error = 'Failed to verify email.';
        }
    } else {
        $error = 'Invalid verification token.';
    }
} else {
    $error = 'No verification token provided.';
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Email Verification</h4>
                </div>
                <div class="card-body text-center">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <a href="login.php" class="btn btn-primary">Login Now</a>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <a href="register.php" class="btn btn-secondary">Register Again</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>