<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$auth = new Auth();
$auth->checkAccess('company');

$job_id = $_GET['job_id'] ?? 0;

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get job details
$job_query = "SELECT * FROM jobs WHERE id = $job_id AND company_id = $user_id";
$job_result = $conn->query($job_query);
$job = $job_result->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: jobs.php');
    exit;
}

// Get applicants for this job
$query = "SELECT ja.*, u.username, u.email, mp.full_name, mp.phone, mp.profile_photo
         FROM job_applications ja
         JOIN users u ON ja.user_id = u.id
         LEFT JOIN member_profiles mp ON u.id = mp.user_id
         WHERE ja.job_id = $job_id
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
                        <a class="nav-link active" href="jobs.php">Manage Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applicants.php">Applicants</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="main-content">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="jobs.php">Jobs</a></li>
                        <li class="breadcrumb-item active">Applicants for "<?php echo $job['title']; ?>"</li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Applicants for "<?php echo $job['title']; ?>"</h2>
                        <p class="text-muted">Total Applications: <?php echo $applicants->rowCount(); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if ($applicants->rowCount() > 0): ?>
                            <div class="row">
                                <?php while ($applicant = $applicants->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <?php if ($applicant['profile_photo']): ?>
                                                        <img src="../../<?php echo $applicant['profile_photo']; ?>" 
                                                             class="profile-img me-3" alt="Profile">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo $applicant['full_name'] ?: $applicant['username']; ?></h6>
                                                        <small class="text-muted"><?php echo $applicant['email']; ?></small>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <span class="badge bg-<?php 
                                                        echo $applicant['status'] === 'pending' ? 'warning' : 
                                                            ($applicant['status'] === 'accepted' ? 'success' : 
                                                            ($applicant['status'] === 'rejected' ? 'danger' : 'info')); 
                                                    ?>">
                                                        <?php echo ucfirst($applicant['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <small class="text-muted">
                                                    Applied on <?php echo date('M d, Y', strtotime($applicant['applied_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="card-footer">
                                                <a href="applicant-detail.php?id=<?php echo $applicant['id']; ?>" 
                                                   class="btn btn-sm btn-primary">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No Applications Yet</h5>
                                <p class="text-muted">No one has applied for this job yet.</p>
                                <a href="jobs.php" class="btn btn-primary">Back to Jobs</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>