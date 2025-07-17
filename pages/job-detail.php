<?php
require_once '../includes/session.php';
require_once '../config/env.php';
require_once '../templates/header.php';
require_once '../templates/nav.php';

$job_id = $_GET['id'] ?? 0;

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();


// Vulnerable: SQL injection
// $query = "SELECT j.*, c.company_name, c.description as company_description, c.website 
//          FROM jobs j 
//          JOIN company_profiles c ON j.company_id = c.user_id 
//          WHERE j.id = $job_id";

// $result = $conn->query($query);
// $job = $result->fetch(PDO::FETCH_ASSOC);

// change to prepared statement to prevent SQL injection
$query = "SELECT j.*, c.company_name, c.description AS company_description, c.website 
          FROM jobs j 
          JOIN company_profiles c ON j.company_id = c.user_id 
          WHERE j.id = :job_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
$stmt->execute();

$job = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$job) {
    header('Location: jobs.php');
    exit;
}

// Check if user already applied (if logged in)
$already_applied = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'member') {
    $user_id = $_SESSION['user_id'];
    $check_query = "SELECT * FROM job_applications WHERE job_id = $job_id AND user_id = $user_id";
    $check_result = $conn->query($check_query);
    $already_applied = $check_result->rowCount() > 0;
}
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="jobs.php">Jobs</a></li>
            <li class="breadcrumb-item active"><?php echo $job['title']; ?></li>
        </ol>
    </nav>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h2><?php echo $job['title']; ?></h2>
                    <h5 class="text-muted mb-3"><?php echo $job['company_name']; ?></h5>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-map-marker-alt"></i> <?php echo $job['location']; ?>
                        </span>
                        <span class="badge bg-secondary me-2">
                            <i class="fas fa-briefcase"></i> <?php echo ucfirst($job['job_type']); ?>
                        </span>
                        <?php if ($job['salary_min'] && $job['salary_max']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-money-bill"></i> 
                                Rp <?php echo number_format($job['salary_min']); ?> - 
                                Rp <?php echo number_format($job['salary_max']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h4>Job Description</h4>
                    <!-- Vulnerable: XSS -->
                    <div class="mb-4"><?php echo htmlspecialchars($job['description']); ?></div>
                    
                    <h4>Requirements</h4>
                    <!-- Vulnerable: XSS -->
                    <div class="mb-4"><?php echo htmlspecialchars($job['requirements']); ?></div>
                    
                    <h4>About Company</h4>
                    <!-- Vulnerable: XSS -->
                    <p><?php echo htmlspecialchars($job['company_description']); ?></p>
                    
                    <?php if ($job['website']): ?>
                        <p><strong>Website:</strong> 
                            <a href="<?php echo $job['website']; ?>" target="_blank"><?php echo $job['website']; ?></a>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'member'): ?>
                                <?php if ($already_applied): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> You have already applied for this job
                                    </div>
                                    <a href="member/history.php" class="btn btn-info">View Application Status</a>
                                <?php else: ?>
                                    <h5>Ready to Apply?</h5>
                                    <p class="text-muted">Submit your application now</p>
                                    <a href="member/apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-success btn-lg">
                                        <i class="fas fa-paper-plane"></i> Apply Now
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <h5>Want to Apply?</h5>
                                <p class="text-muted">Please login to apply for this job</p>
                                <a href="auth/login.php" class="btn btn-primary btn-lg">Login to Apply</a>
                                <p class="mt-2">
                                    <small>Don't have an account? <a href="auth/register.php">Register here</a></small>
                                </p>
                            <?php endif; ?>
                            
                            <hr>
                            <small class="text-muted">
                                Posted on <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>