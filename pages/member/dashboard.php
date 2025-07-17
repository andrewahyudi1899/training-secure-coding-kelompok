<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';


$auth = new Auth();

// Vulnerable: No proper authorization check
//FIXING Checkking access for 'member' role
if (!$auth->checkAccess('member')) {
    echo '<div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#f8f9fa;">
        <div style="background:#fff;padding:40px 60px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);text-align:center;">
            <h2 style="color:#dc3545;margin-bottom:20px;">Access Denied</h2>
            <p style="font-size:18px;color:#333;">You must be a <strong>member</strong> to view this page.</p>
        </div>
        </div>';
    exit;
}

// Vulnerable: Direct database access without sanitization
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if user_id exists in session, use default if not (vulnerable but prevents crash)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get user profile
$query = "SELECT * FROM member_profiles WHERE user_id = $user_id";
$result = $conn->query($query);
$profile = $result->fetch(PDO::FETCH_ASSOC);

// Get recent applications
$query = "SELECT ja.*, j.title, c.company_name FROM job_applications ja
          JOIN jobs j ON ja.job_id = j.id
          JOIN company_profiles c ON j.company_id = c.user_id
          WHERE ja.user_id = $user_id
          ORDER BY ja.applied_at DESC LIMIT 5";
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
                        <a class="nav-link active" href="cv.php">CV</a>
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
                        <a class="nav-link" href="history.php">History</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="main-content">
                <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>Total Applications</h5>
                                <h3><?php echo $applications->rowCount(); ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>Profile Completion</h5>
                                <h3><?php echo $profile ? '80%' : '20%'; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5>Job Matches</h5>
                                <h3>12</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h4>Recent Applications</h4>
                        <div class="card">
                            <div class="card-body">
                                <?php if ($applications->rowCount() > 0): ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Company</th>
                                                <th>Applied Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($app = $applications->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><?php echo $app['title']; ?></td>
                                                    <td><?php echo $app['company_name']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $app['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                                            <?php echo ucfirst($app['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p>No applications yet. <a href="jobs.php">Browse jobs</a> to get started!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vulnerable dashboard JavaScript -->
<script>
    // Expose sensitive user data
    // FIXING: This is a security risk and should not be done in production
    
    // console.log('User Dashboard Data:', {
    //     userId: <?php echo $user_id; ?>,
    //     profile: <?php echo json_encode($profile); ?>,
    //     sessionData: <?php echo json_encode($_SESSION); ?>
    // });
    
    // Vulnerable AJAX calls without CSRF protection
    // FIXING : API DOESNT EXIST
    // function updateStats() {
    //     fetch('../../api/stats.php?user_id=<?php echo $user_id; ?>')
    //         .then(response => response.json())
    //         .then(data => {
    //             // Update dashboard stats
    //             console.log('Stats updated:', data);
    //         });
    // }
    
    //setInterval(updateStats, 30000); // Update every 30 seconds
</script>

<?php require_once '../../templates/footer.php'; ?>