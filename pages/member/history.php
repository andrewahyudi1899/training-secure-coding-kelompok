<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$auth = new Auth();
$auth->checkAccess('member');

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get application history
$query = "SELECT ja.*, j.title, j.location, j.job_type, c.company_name 
         FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         LEFT JOIN company_profiles c ON j.company_id = c.user_id
         WHERE ja.user_id = $user_id
         ORDER BY ja.applied_at DESC";

$applications = $conn->query($query);
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
                <h2>Application History</h2>
                
                <?php if ($applications->rowCount() > 0): ?>
                    <div class="row">
                        <?php while ($app = $applications->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $app['title']; ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo $app['company_name']; ?></h6>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo $app['location']; ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-briefcase"></i> <?php echo ucfirst($app['job_type']); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <span class="badge bg-<?php 
                                                echo $app['status'] === 'pending' ? 'warning' : 
                                                    ($app['status'] === 'accepted' ? 'success' : 
                                                    ($app['status'] === 'rejected' ? 'danger' : 'info')); 
                                            ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <small class="text-muted">
                                            Applied on <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="card-footer">
                                        <a href="job-detail.php?id=<?php echo $app['job_id']; ?>" class="btn btn-sm btn-primary">
                                            View Job
                                        </a>
                                        <!-- Vulnerable: Direct object reference -->
                                        <a href="application-detail.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info">
                                            View Application
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h5>No Applications Yet</h5>
                        <p>You haven't applied for any jobs yet. <a href="jobs.php">Browse available jobs</a> to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>