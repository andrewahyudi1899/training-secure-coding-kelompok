<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$auth = new Auth();
$auth->checkAccess('member');

$job_id = $_GET['job_id'] ?? 0; // Vulnerable to SQL injection
$message = '';
$error = '';

require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get job details
$query = "SELECT j.*, c.company_name FROM jobs j 
         LEFT JOIN company_profiles c ON j.company_id = c.user_id 
         WHERE j.id = $job_id";
$result = $conn->query($query);
$job = $result->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: jobs.php');
    exit;
}

// Check if already applied
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // Vulnerable to SQL injection
$check_query = "SELECT * FROM job_applications WHERE job_id = $job_id AND user_id = $user_id";
$check_result = $conn->query($check_query);

if ($check_result->rowCount() > 0) {
    header('Location: job-detail.php?id=' . $job_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is actually logged in (prevent foreign key constraint violation)
    if ($user_id <= 0) {
        $error = 'You must be logged in to apply for jobs.';
    } else {
        $cover_letter = $_POST['cover_letter'];

        // Use addslashes to prevent SQL crash but still vulnerable to XSS
        $cover_letter_escaped = addslashes($cover_letter);

        // Vulnerable: SQL injection (still possible with addslashes bypass)
        $query = "INSERT INTO job_applications (job_id, user_id, cover_letter)
                 VALUES ($job_id, $user_id, '$cover_letter_escaped')";

        if ($conn->query($query)) {
            $message = 'Application submitted successfully!';
        } else {
            $error = 'Failed to submit application.';
        }
    }
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
                        <li class="breadcrumb-item"><a href="job-detail.php?id=<?php echo $job_id; ?>"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li class="breadcrumb-item active">Apply</li>
                    </ol>
                </nav>
                
                <h2>Apply for Job</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                        <br>
                        <a href="job-detail.php?id=<?php echo $job_id; ?>" class="btn btn-primary mt-2">Back to Job</a>
                        <a href="history.php" class="btn btn-info mt-2">View Applications</a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Job Details</h5>
                                    <h6><?php echo $job['title']; ?></h6>
                                    <p class="text-muted"><?php echo $job['company_name']; ?></p>
                                    
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="cover_letter" class="form-label">Cover Letter</label>
                                            <textarea class="form-control" id="cover_letter" name="cover_letter" rows="8" 
                                                      placeholder="Write your cover letter here..." required></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success">Submit Application</button>
                                            <a href="job-detail.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Application Tips</h6>
                                    <ul class="small">
                                        <li>Make sure your profile is complete</li>
                                        <li>Upload your latest CV</li>
                                        <li>Write a compelling cover letter</li>
                                        <li>Highlight relevant skills and experience</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>