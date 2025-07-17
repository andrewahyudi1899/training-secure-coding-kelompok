<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../includes/file_upload.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';
include '../../templates/validate_company.php';

$auth = new Auth();
$auth->checkAccess('member');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Check if user is actually logged in (prevent foreign key constraint violation)
    if ($user_id <= 0) {
        $error = 'You must be logged in to perform this action.';
    } else {
    
    // Vulnerable file upload
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === 0) {
        $cv_file = FileUpload::uploadFile($_FILES['cv_file'], 'cvs');
        
        if ($cv_file) {
            require_once '../../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Vulnerable: SQL injection
            $query = "UPDATE member_profiles SET cv_file = '$cv_file' WHERE user_id = $user_id";
            
            if ($conn->query($query)) {
                $message = 'CV uploaded successfully!';
            } else {
                $error = 'Failed to update CV.';
            }
        } else {
            $error = 'Failed to upload CV file.';
        }
    }

    }}

// Get current CV
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$query = "SELECT cv_file FROM member_profiles WHERE user_id = $user_id";
$result = $conn->query($query);
$profile = $result->fetch(PDO::FETCH_ASSOC);
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
                <h2>My CV</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="cv_file" class="form-label">Upload CV</label>
                                <input type="file" class="form-control" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx">
                                <div class="form-text">Supported formats: PDF, DOC, DOCX</div>
                            </div>
                            
                            <?php if (isset($profile['cv_file']) && $profile['cv_file']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current CV</label>
                                    <div class="border p-3 rounded">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        <a href="<?php echo $profile['cv_file']; ?>" target="_blank" class="ms-2">
                                            View Current CV
                                        </a>
                                        <!-- Vulnerable: Direct file deletion -->
                                        <a href="?delete=<?php echo $profile['cv_file']; ?>" class="btn btn-sm btn-danger ms-2" 
                                           onclick="return confirm('Delete CV?')">Delete</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">Upload CV</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Vulnerable: File deletion without proper authorization
if (isset($_GET['delete'])) {
    $file_to_delete = $_GET['delete'];
    FileUpload::deleteFile($file_to_delete);
    header('Location: cv.php');
    exit;
}
?>

<?php require_once '../../templates/footer.php'; ?>