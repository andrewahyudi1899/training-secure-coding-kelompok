<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../includes/file_upload.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$auth = new Auth();
if (!$auth->checkAccess('member')) {
    echo '<div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#f8f9fa;">
        <div style="background:#fff;padding:40px 60px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);text-align:center;">
            <h2 style="color:#dc3545;margin-bottom:20px;">Access Denied</h2>
            <p style="font-size:18px;color:#333;">You must be a <strong>member</strong> to view this page.</p>
        </div>
        </div>';
    exit;
}

$message = '';
$error = '';

$key = hash('sha256', 'your-secret-key');

function encrypt($plaintext, $key) {
    $cipher = 'AES-128-ECB';

    return openssl_encrypt($plaintext, $cipher, $key);
}

function decrypt($ciphertext, $key) {
    $cipher = 'AES-128-ECB';

    return openssl_decrypt($ciphertext, $cipher, $key);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Check if user is actually logged in (prevent foreign key constraint violation)
    if ($user_id <= 0) {
        $error = 'You must be logged in to perform this action.';
    } else {
        // Vulnerable file upload
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === 0) {
            $cv_file = FileUpload::uploadFile($_FILES['cv_file'], 'cvs', ['pdf', 'doc', 'docx']);
            
            if ($cv_file['status']) {
                require_once '../../config/database.php';
                $db = new Database();
                $conn = $db->getConnection();
                
                // Vulnerable: SQL injection
                // FIXING
                $query = "UPDATE member_profiles SET cv_file = :cvfile WHERE user_id = :userid";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':cvfile', $cv_file['data']['target_file']);
                $stmt->bindParam(':userid', $user_id);
                
                if ($stmt->execute()) {
                    $message = 'CV uploaded successfully!';
                } else {
                    $error = 'Failed to update CV.';
                }
            } else {
                // $error = 'Failed to upload CV file.';
                $error = $cv_file['message'];
            }
        }

    }}

// Get current CV
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// FIXING
$query = "SELECT cv_file FROM member_profiles WHERE user_id = :userid";

$stmt = $conn->prepare($query);
$stmt->bindParam(':userid', $user_id);
$stmt->execute();

$result = $stmt->fetchAll();
if ($result && count($result) > 0) {
    $profile = $result[0];
} else {
    $profile = [];
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
                                        <!-- FIXING -->
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

    require_once '../../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    // FIXING
    FileUpload::deleteFile($file_to_delete);

    $query = "UPDATE member_profiles SET cv_file = :cvfile WHERE user_id = :userid";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cvfile', '');
    $stmt->bindParam(':userid', $user_id);
    $stmt->execute();

    header('Location: cv.php');
    exit;
}
?>

<?php require_once '../../templates/footer.php'; ?>