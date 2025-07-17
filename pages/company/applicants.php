<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$auth = new Auth();
if (!$auth->checkAccess('company')) {
    echo '<div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#f8f9fa;">
        <div style="background:#fff;padding:40px 60px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);text-align:center;">
            <h2 style="color:#dc3545;margin-bottom:20px;">Access Denied</h2>
            <p style="font-size:18px;color:#333;">You must be a <strong>company</strong> to view this page.</p>
        </div>
        </div>';
    exit;
}

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['status'];
    
    // Vulnerable: SQL injection and no authorization check
    $query = "UPDATE job_applications SET status = '$status' WHERE id = $application_id";
    $conn->query($query);
    
    header('Location: applicants.php');
    exit;
}

// Get all applicants for company jobs
$query = "SELECT ja.*, j.title as job_title, u.username, u.email, mp.full_name, mp.phone, mp.profile_photo
         FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         JOIN users u ON ja.user_id = u.id
         LEFT JOIN member_profiles mp ON u.id = mp.user_id
         WHERE j.company_id = $user_id
         ORDER BY ja.applied_at DESC";

$applicants = $conn->query($query);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="sidebar p-3">
                <h5>Company Panel</h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">Manage Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="applicants.php">Applicants</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="main-content">
                <h2>Job Applicants</h2>
                
                <div class="card">
                    <div class="card-body">
                        <?php if ($applicants->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Job</th>
                                            <th>Applied Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($applicant = $applicants->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($applicant['profile_photo']): ?>
                                                            <img src="../../<?php echo $applicant['profile_photo']; ?>" 
                                                                 class="profile-img me-2" alt="Profile">
                                                        <?php else: ?>
                                                            <div class="bg-secondary rounded-circle me-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="fas fa-user text-white"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?php echo $applicant['full_name'] ?: $applicant['username']; ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo $applicant['email']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $applicant['job_title']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($applicant['applied_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $applicant['status'] === 'pending' ? 'warning' : 
                                                            ($applicant['status'] === 'accepted' ? 'success' : 
                                                            ($applicant['status'] === 'rejected' ? 'danger' : 'info')); 
                                                    ?>">
                                                        <?php echo ucfirst($applicant['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="applicant-detail.php?id=<?php echo $applicant['id']; ?>" 
                                                       class="btn btn-sm btn-primary">View Details</a>
                                                    
                                                    <!-- Status update dropdown -->
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" 
                                                                data-bs-toggle="dropdown">
                                                            Update Status
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="application_id" value="<?php echo $applicant['id']; ?>">
                                                                    <input type="hidden" name="status" value="reviewed">
                                                                    <button type="submit" name="update_status" class="dropdown-item">Mark as Reviewed</button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="application_id" value="<?php echo $applicant['id']; ?>">
                                                                    <input type="hidden" name="status" value="accepted">
                                                                    <button type="submit" name="update_status" class="dropdown-item text-success">Accept</button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="application_id" value="<?php echo $applicant['id']; ?>">
                                                                    <input type="hidden" name="status" value="rejected">
                                                                    <button type="submit" name="update_status" class="dropdown-item text-danger">Reject</button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No Applications Yet</h5>
                                <p class="text-muted">When people apply for your jobs, they will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>