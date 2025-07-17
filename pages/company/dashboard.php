<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';
include '../../templates/validate_member.php';

$auth = new Auth();
$auth->checkAccess('company');

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get company stats
$query = "SELECT COUNT(*) as total_jobs FROM jobs WHERE company_id = $user_id";
$result = $conn->query($query);
$total_jobs = $result->fetch(PDO::FETCH_ASSOC)['total_jobs'];

$query = "SELECT COUNT(*) as total_applications FROM job_applications ja
          JOIN jobs j ON ja.job_id = j.id
          WHERE j.company_id = $user_id";
$result = $conn->query($query);
$total_applications = $result->fetch(PDO::FETCH_ASSOC)['total_applications'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="sidebar p-3">
                <h5>Company Panel</h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">Manage Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applicants.php">Applicants</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="main-content">
                <h2>Company Dashboard</h2>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>Total Jobs</h5>
                                <h3><?php echo $total_jobs; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>Total Applications</h5>
                                <h3><?php echo $total_applications; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5>Active Jobs</h5>
                                <h3><?php echo $total_jobs; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <a href="jobs.php?action=create" class="btn btn-primary mb-2 w-100">Post New Job</a>
                                <a href="applicants.php" class="btn btn-success mb-2 w-100">View Applications</a>
                                <a href="jobs.php" class="btn btn-info w-100">Manage Jobs</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Applications</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $query = "SELECT ja.*, j.title, u.username FROM job_applications ja
                                          JOIN jobs j ON ja.job_id = j.id
                                          JOIN users u ON ja.user_id = u.id
                                          WHERE j.company_id = $user_id
                                          ORDER BY ja.applied_at DESC LIMIT 5";
                                $applications = $conn->query($query);
                                
                                if ($applications->rowCount() > 0) {
                                    while ($app = $applications->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<div class="mb-2">';
                                        echo '<strong>' . $app['username'] . '</strong> applied for <em>' . $app['title'] . '</em>';
                                        echo '<br><small class="text-muted">' . date('M d, Y', strtotime($app['applied_at'])) . '</small>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>No recent applications.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>