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

$application_id = $_GET['id'] ?? 0; // Vulnerable to SQL injection

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Vulnerable: No proper authorization check
$query = "SELECT ja.*, j.title as job_title, j.description as job_description,
                 u.username, u.email, mp.full_name, mp.phone, mp.address, mp.profile_photo, mp.cv_file
         FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         JOIN users u ON ja.user_id = u.id
         LEFT JOIN member_profiles mp ON u.id = mp.user_id
         WHERE ja.id = $application_id";

$result = $conn->query($query);
$application = $result->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header('Location: applicants.php');
    exit;
}

// Get applicant skills
$skills_query = "SELECT * FROM skills WHERE user_id = " . $application['user_id'];
$skills = $conn->query($skills_query);

// Get applicant education
$education_query = "SELECT * FROM education WHERE user_id = " . $application['user_id'] . " ORDER BY start_date DESC";
$education = $conn->query($education_query);
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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="applicants.php">Applicants</a></li>
                        <li class="breadcrumb-item active">Applicant Details</li>
                    </ol>
                </nav>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <?php if ($application['profile_photo']): ?>
                                        <img src="../../<?php echo $application['profile_photo']; ?>" 
                                             class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;" alt="Profile">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 80px;">
                                            <i class="fas fa-user fa-2x text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3><?php echo $application['full_name'] ?: $application['username']; ?></h3>
                                        <p class="text-muted mb-1"><?php echo $application['email']; ?></p>
                                        <?php if ($application['phone']): ?>
                                            <p class="text-muted mb-0"><?php echo $application['phone']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <h5>Applied for: <?php echo $application['job_title']; ?></h5>
                                <p class="text-muted">Applied on <?php echo date('M d, Y H:i', strtotime($application['applied_at'])); ?></p>
                                
                                <div class="mb-3">
                                    <span class="badge bg-<?php 
                                        echo $application['status'] === 'pending' ? 'warning' : 
                                            ($application['status'] === 'accepted' ? 'success' : 
                                            ($application['status'] === 'rejected' ? 'danger' : 'info')); 
                                    ?> fs-6">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </div>
                                
                                <h5>Cover Letter</h5>
                                <!-- Vulnerable: XSS -->
                                <div class="border p-3 rounded bg-light mb-4">
                                    <?php echo nl2br($application['cover_letter']); ?>
                                </div>
                                
                                <?php if ($application['address']): ?>
                                    <h5>Address</h5>
                                    <p><?php echo $application['address']; ?></p>
                                <?php endif; ?>
                                
                                <!-- Skills Section -->
                                <h5>Skills</h5>
                                <?php if ($skills->rowCount() > 0): ?>
                                    <div class="mb-4">
                                        <?php while ($skill = $skills->fetch(PDO::FETCH_ASSOC)): ?>
                                            <span class="badge bg-primary me-2 mb-2">
                                                <?php echo $skill['skill_name']; ?> (<?php echo ucfirst($skill['level']); ?>)
                                            </span>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-4">No skills listed.</p>
                                <?php endif; ?>
                                
                                <!-- Education Section -->
                                <h5>Education</h5>
                                <?php if ($education->rowCount() > 0): ?>
                                    <?php while ($edu = $education->fetch(PDO::FETCH_ASSOC)): ?>
                                        <div class="border rounded p-3 mb-3">
                                            <h6><?php echo $edu['degree']; ?> in <?php echo $edu['field_of_study']; ?></h6>
                                            <p class="mb-1"><strong><?php echo $edu['institution']; ?></strong></p>
                                            <small class="text-muted">
                                                <?php echo date('M Y', strtotime($edu['start_date'])); ?> - 
                                                <?php echo $edu['end_date'] ? date('M Y', strtotime($edu['end_date'])) : 'Present'; ?>
                                            </small>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-muted">No education records.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6>Actions</h6>
                                
                                <?php if ($application['cv_file']): ?>
                                    <a href="../../<?php echo $application['cv_file']; ?>" target="_blank" 
                                       class="btn btn-info w-100 mb-2">
                                        <i class="fas fa-file-pdf"></i> View CV
                                    </a>
                                <?php endif; ?>
                                
                                <form method="POST" action="applicants.php">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Update Status</label>
                                        <select class="form-control" name="status" required>
                                            <option value="pending" <?php echo $application['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                            <option value="accepted" <?php echo $application['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                            <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="update_status" class="btn btn-primary w-100 mb-2">
                                        Update Status
                                    </button>
                                </form>
                                
                                <a href="applicants.php" class="btn btn-secondary w-100">
                                    Back to Applicants
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>