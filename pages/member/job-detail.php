<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$auth = new Auth();
$auth->checkAccess('member');

$job_id = $_GET['id'] ?? 0; // Vulnerable to SQL injection
$search = $_GET['search'] ?? ''; // Vulnerable search parameter

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Vulnerable: SQL injection through search parameter
$query = "SELECT j.*, c.company_name, c.description as company_description, c.website
         FROM jobs j
         LEFT JOIN company_profiles c ON j.company_id = c.user_id
         WHERE j.id = $job_id";

// Add vulnerable search condition if search parameter exists
if (!empty($search)) {
    $query .= " AND (j.title LIKE '%$search%' OR j.description LIKE '%$search%')";
}

$result = $conn->query($query);
$job = $result->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: jobs.php');
    exit;
}

// Check if user already applied
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // Vulnerable to SQL injection
$check_query = "SELECT * FROM job_applications WHERE job_id = $job_id AND user_id = $user_id";
$check_result = $conn->query($check_query);
$already_applied = $check_result->rowCount() > 0;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="sidebar p-3">
                <h5>Member Panel</h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cv.php">CV</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="skills.php">Skills</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="education.php">Education</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="jobs.php">Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">History</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="main-content">
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
                                <div class="mb-4"><?php echo $job['description']; ?></div>
                                
                                <h4>Requirements</h4>
                                <!-- Vulnerable: XSS -->
                                <div class="mb-4"><?php echo $job['requirements']; ?></div>
                                
                                <h4>About Company</h4>
                                <!-- Vulnerable: XSS -->
                                <p><?php echo $job['company_description']; ?></p>
                                
                                <?php if ($job['website']): ?>
                                    <p><strong>Website:</strong> 
                                        <a href="<?php echo $job['website']; ?>" target="_blank"><?php echo $job['website']; ?></a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <?php if ($already_applied): ?>
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle"></i> You have already applied for this job
                                            </div>
                                            <a href="history.php" class="btn btn-info">View Application Status</a>
                                        <?php else: ?>
                                            <h5>Ready to Apply?</h5>
                                            <p class="text-muted">Submit your application now</p>
                                            <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-success btn-lg">
                                                <i class="fas fa-paper-plane"></i> Apply Now
                                            </a>
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
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>