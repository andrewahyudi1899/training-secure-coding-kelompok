<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';
include '../../templates/validate_company.php';

$auth = new Auth();
$auth->checkAccess('member');

$application_id = $_GET['id'] ?? 0;

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Vulnerable: No ownership check
$query = "SELECT ja.*, j.title, j.description, j.location, j.job_type, j.salary_min, j.salary_max,
                 c.company_name, c.description as company_description
         FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         JOIN company_profiles c ON j.company_id = c.user_id
         WHERE ja.id = $application_id";

$result = $conn->query($query);
$application = $result->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header('Location: history.php');
    exit;
}
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
                        <a class="nav-link" href="jobs.php">Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="history.php">History</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="main-content">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="history.php">History</a></li>
                        <li class="breadcrumb-item active">Application Details</li>
                    </ol>
                </nav>
                
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h2><?php echo $application['title']; ?></h2>
                                <h5 class="text-muted mb-3"><?php echo $application['company_name']; ?></h5>
                                
                                <div class="mb-3">
                                    <span class="badge bg-<?php 
                                        echo $application['status'] === 'pending' ? 'warning' : 
                                            ($application['status'] === 'accepted' ? 'success' : 
                                            ($application['status'] === 'rejected' ? 'danger' : 'info')); 
                                    ?> fs-6">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </div>
                                
                                <h4>Your Cover Letter</h4>
                                <!-- Vulnerable: XSS -->
                                <div class="border p-3 rounded bg-light mb-4">
                                    <?php echo nl2br($application['cover_letter']); ?>
                                </div>
                                
                                <h4>Job Description</h4>
                                <!-- Vulnerable: XSS -->
                                <div class="mb-4"><?php echo $application['description']; ?></div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>Application Status</h6>
                                        <p class="mb-1">
                                            <strong>Applied:</strong> 
                                            <?php echo date('M d, Y H:i', strtotime($application['applied_at'])); ?>
                                        </p>
                                        <p class="mb-1">
                                            <strong>Location:</strong> <?php echo $application['location']; ?>
                                        </p>
                                        <p class="mb-1">
                                            <strong>Type:</strong> <?php echo ucfirst($application['job_type']); ?>
                                        </p>
                                        <?php if ($application['salary_min'] && $application['salary_max']): ?>
                                            <p class="mb-1">
                                                <strong>Salary:</strong> 
                                                Rp <?php echo number_format($application['salary_min']); ?> - 
                                                Rp <?php echo number_format($application['salary_max']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="job-detail.php?id=<?php echo $application['job_id']; ?>" 
                                               class="btn btn-primary btn-sm">View Job Details</a>
                                            <a href="history.php" class="btn btn-secondary btn-sm">Back to History</a>
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
</div>

<?php require_once '../../templates/footer.php'; ?>