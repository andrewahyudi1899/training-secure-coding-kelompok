<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';

$error = '';

$max_attempts = 5;
$lockout_time = 60;
$start_time = microtime(true);
$attempts = $_SESSION['login_attempts'] ?? 0;
$is_login = false;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['start_attempt_time'] = microtime(true);
}


if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] == "") {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

// Handle login BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // var_dump($_SESSION['csrf_token'],  $_POST['csrf_token']);
    // var_dump($token, $_SESSION['csrf_token']);
    // $token = $_POST['csrf_token'] ?? 0;
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $current_time = microtime(true);
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);
    
        if ($_SESSION['login_attempts'] >= $max_attempts && ($current_time - $_SESSION['start_attempt_time']) < $lockout_time) {
            $remaining = (int)($lockout_time - ($current_time - $_SESSION['start_attempt_time']));
            $error = "Too many login attempts. Try again in $remaining seconds.";
        } else {
    
            if (($current_time - $_SESSION['start_attempt_time']) >= $lockout_time) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['start_attempt_time'] = microtime(true);
            }
    
            // if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            //     $error = "Invalid email format";
            // }
            $pattern = "/^[a-zA-Z0-9]+$/";
            if(!preg_match($pattern, $username)) {
                // $response['message'] = "Username only alphanumeric";
                // return $response;
                $error = 'Username only alphanumeric';
            } else {
                $auth = new Auth();
                $_SESSION['login_attempts'] = $attempts + 1;
                $user = $auth->login($username, $password);
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['login_attempts'] = 0;
                    // Redirect based on role
                    if ($user['role'] === 'member') {
                        header('Location: ../member/dashboard.php');
                    } else {
                        header('Location: ../company/dashboard.php');
                    }
                    $is_login = true;
                    exit;
                } else {
                    $_SESSION['last_attempt_time'] = $current_time;
                    $error = "Invalid credentials. Attempt #" . (int)$_SESSION['login_attempts'];
                    // $error = 'Invalid username or password';
                }
    
            }
        
        }
    }


}


// Include templates AFTER login processing
require_once '../../templates/header.php';
require_once '../../templates/nav.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                        <p><a href="forgot-password.php">Forgot Password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>