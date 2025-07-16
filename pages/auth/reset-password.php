<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../config/database.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vulnerable: No password validation
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vulnerable: SQL injection
        $query = "SELECT * FROM users WHERE verification_token = '$token'";
        $result = $conn->query($query);
        $user = $result->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Vulnerable: Store password in plain text
            $update_query = "UPDATE users SET password = '$password', verification_token = NULL WHERE id = " . $user['id'];
            if ($conn->query($update_query)) {
                $message = 'Password reset successfully! You can now login.';
            } else {
                $error = 'Failed to reset password.';
            }
        } else {
            $error = 'Invalid reset token.';
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <a href="login.php" class="btn btn-primary">Login Now</a>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>