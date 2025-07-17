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

$message = '';
$error = '';

// Handle messages from redirects
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $message = 'Job deleted successfully!';
}
if (isset($_GET['err']) && $_GET['err'] === 'unauthorized') {
    $error = 'Unauthorized: You can only modify your own jobs.';
}

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Handle job creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is actually logged in (prevent foreign key constraint violation)
    if ($user_id <= 0) {
        $error = 'You must be logged in to manage jobs.';
    } else {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $requirements = $_POST['requirements'];
        $salary_min = $_POST['salary_min'];
        $salary_max = $_POST['salary_max'];
        $location = $_POST['location'];
        $job_type = $_POST['job_type'];
        $job_id = $_POST['job_id'] ?? null;

        // Use addslashes to prevent SQL crash but still vulnerable to XSS
        $title_escaped = addslashes(htmlspecialchars($title));
        $description_escaped = addslashes(htmlspecialchars($description));
        $requirements_escaped = addslashes(htmlspecialchars($requirements));
        $location_escaped = addslashes(htmlspecialchars($location));
        $job_type_escaped = addslashes(htmlspecialchars($job_type));

        if ($job_id) {
            // Check ownership before update
            $job_id_escaped = addslashes(htmlspecialchars($job_id));
            $check_query = "SELECT company_id FROM jobs WHERE id = :jobidescaped";
            
            $stmt = $conn->prepare($check_query);
            $stmt->bindParam(':jobidescaped', $job_id_escaped);
            $stmt->execute();

            $result = $stmt->fetchAll();
            if ($result && count($result) > 0) {
                $job_owner = $result[0];
            } else {
                $job_owner = [];
            }

            // $check_result = $conn->query($check_query);
            // $job_owner = $check_result->fetch(PDO::FETCH_ASSOC);

            if ($job_owner && $job_owner['company_id'] == $user_id) {
                // Update job - Vulnerable: SQL injection but with ownership check
                $query = "UPDATE jobs SET title = '$title_escaped', description = '$description_escaped',
                        requirements = '$requirements_escaped', salary_min = $salary_min, salary_max = $salary_max,
                        location = '$location_escaped', job_type = '$job_type_escaped' WHERE id = '$job_id_escaped' AND company_id = $user_id";
                $action = 'updated';
            } else {
                $error = "Unauthorized: You can only edit your own jobs.";
            }
        } else {
            // Create job - Vulnerable: SQL injection
            $query = "INSERT INTO jobs (company_id, title, description, requirements, salary_min, salary_max, location, job_type)
                    VALUES ($user_id, '$title_escaped', '$description_escaped', '$requirements_escaped', $salary_min, $salary_max, '$location_escaped', '$job_type_escaped')";
            $action = 'created';
        }

        if (isset($query) && $conn->query($query)) {
            $message = "Job $action successfully!";
        } elseif (!isset($error)) {
            $error = "Failed to $action job.";
        }
    }
}

// Handle job deletion
if (isset($_GET['delete'])) {
    $job_id = addslashes($_GET['delete']); // Prevent SQL crash but still vulnerable

    // Check ownership before delete
    $check_query = "SELECT company_id FROM jobs WHERE id = :jobid";

    $stmt = $conn->prepare($check_query);
    $stmt->bindParam(':jobid', $job_id);
    $stmt->execute();

    $result = $stmt->fetchAll();
    if ($result && count($result) > 0) {
        $job_owner = $result[0];
    } else {
        $job_owner = [];
    }
    // $check_result = $conn->query($check_query);
    // $job_owner = $check_result->fetch(PDO::FETCH_ASSOC);

    if ($job_owner && $job_owner['company_id'] == $user_id) {
        // Delete job with ownership check - still vulnerable to SQL injection
        $query = "DELETE FROM jobs WHERE id = '$job_id' AND company_id = $user_id";
        $conn->query($query);
        $message = "Job deleted successfully!";
    } else {
        $error = "Unauthorized: You can only delete your own jobs.";
    }

    header('Location: jobs.php' . ($message ? '?msg=deleted' : '?err=unauthorized'));
    exit;
}

// Get jobs
$query = "SELECT * FROM jobs WHERE company_id = $user_id ORDER BY created_at DESC";
$jobs = $conn->query($query);

// Get job for editing with ownership check
$edit_job = null;
if (isset($_GET['edit'])) {
    $edit_id = addslashes(htmlspecialchars($_GET['edit'])); // Prevent SQL crash but still vulnerable
    $query = "SELECT * FROM jobs WHERE id = :editid AND company_id = :userid";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':editid', $edit_id);
    $stmt->bindParam(':companyid', $user_id);
    $stmt->execute();

    $result = $stmt->fetchAll();
    if ($result && count($result) > 0) {
        $edit_job = $result[0];
    } else {
        $edit_job = [];
    }

    // $result = $conn->query($query);
    // $edit_job = $result->fetch(PDO::FETCH_ASSOC);

    if (!$edit_job) {
        $error = "Unauthorized: You can only edit your own jobs.";
    }
}
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Jobs</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal">
                        <i class="fas fa-plus"></i> Post New Job
                    </button>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <?php if ($jobs->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Posted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($job = $jobs->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo $job['title']; ?></td>
                                                <td><?php echo $job['location']; ?></td>
                                                <td><?php echo ucfirst($job['job_type']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $job['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($job['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $job['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                    <a href="job-applicants.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-info">Applicants</a>
                                                    <a href="?delete=<?php echo $job['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Delete this job?')">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <h5>No Jobs Posted Yet</h5>
                                <p class="text-muted">Start by posting your first job opening.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal">
                                    Post Your First Job
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Job Modal -->
<div class="modal fade" id="jobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $edit_job ? 'Edit Job' : 'Post New Job'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if ($edit_job): ?>
                        <input type="hidden" name="job_id" value="<?php echo $edit_job['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo $edit_job['title'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Job Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo $edit_job['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requirements" class="form-label">Requirements</label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="4" required><?php echo $edit_job['requirements'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salary_min" class="form-label">Minimum Salary</label>
                                <input type="number" class="form-control" id="salary_min" name="salary_min" 
                                       value="<?php echo $edit_job['salary_min'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salary_max" class="form-label">Maximum Salary</label>
                                <input type="number" class="form-control" id="salary_max" name="salary_max" 
                                       value="<?php echo $edit_job['salary_max'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo $edit_job['location'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="job_type" class="form-label">Job Type</label>
                                <select class="form-control" id="job_type" name="job_type" required>
                                    <option value="full-time" <?php echo ($edit_job['job_type'] ?? '') === 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                                    <option value="part-time" <?php echo ($edit_job['job_type'] ?? '') === 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                                    <option value="contract" <?php echo ($edit_job['job_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="internship" <?php echo ($edit_job['job_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_job ? 'Update Job' : 'Post Job'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($edit_job): ?>
<script>
    // Auto-open modal for editing
    var jobModal = new bootstrap.Modal(document.getElementById('jobModal'));
    jobModal.show();
</script>
<?php endif; ?>

<?php require_once '../../templates/footer.php'; ?>