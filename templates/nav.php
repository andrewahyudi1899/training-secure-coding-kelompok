<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
?>

<!-- tambahan Derian -->
 <?php
    
    $db = new Database();
    $conn = $db->getConnection();
    $id = isset($_SESSION['user_id'])?htmlspecialchars($_SESSION['user_id']):0;
    $role = 'member';
    $username = 'Unknown';
    $query = "SELECT username, role FROM users WHERE id = ? LIMIT 1";
    
    $result = $conn->prepare($query);
    $result->execute([$id]);
    if ($result->rowCount() > 0) {
        $user = $result->fetch(PDO::FETCH_ASSOC);
        if($user) {
            $role = $user['role'];
            $username = $user['username'];
        }
    }
 ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-briefcase"></i> Job Portal
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/jobs.php">Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/companies.php">Companies</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <!-- ganti ke $username database, dari session -->
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <!-- ganti ke $role database, dari session -->
                            <?php if ($role === 'member'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/member/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/member/profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/member/cv.php">CV</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/member/jobs.php">Jobs</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/member/history.php">History</a></li>
                            <?php elseif ($role === 'company'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/company/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/company/jobs.php">Manage Jobs</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/company/applicants.php">Applicants</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Vulnerable search functionality -->
<div class="container mt-3">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <form method="GET" action="<?php echo BASE_URL; ?>/pages/search.php" class="d-flex">
                <!-- tambah html special char -->
                <input class="form-control me-2" type="search" name="q" placeholder="Search jobs..." 
                       value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </div>
</div>