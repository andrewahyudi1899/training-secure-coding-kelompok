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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    // Check if user is actually logged in (prevent foreign key constraint violation)
    if ($user_id <= 0) {
        $error = 'You must be logged in to update your profile.';
    } else {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        // Use addslashes to prevent SQL crash but still vulnerable to XSS when displayed
        $full_name_escaped = addslashes($full_name);
        $phone_escaped = addslashes($phone);
        $address_escaped = addslashes($address);

        // Vulnerable file upload
        $profile_photo = '';
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $profile_photo = FileUpload::uploadFile($_FILES['profile_photo'], 'profiles');
        }
        $profile_photo_escaped = addslashes($profile_photo);

        require_once '../../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();

        // Vulnerable: SQL injection (still possible with addslashes bypass) but prevents crashes
        $query = "INSERT INTO member_profiles (user_id, full_name, phone, address, profile_photo)
                 VALUES ('$user_id', '$full_name_escaped', '$phone_escaped', '$address_escaped', '$profile_photo_escaped')
                 ON DUPLICATE KEY UPDATE
                 full_name = '$full_name_escaped', phone = '$phone_escaped', address = '$address_escaped'";

        if ($profile_photo_escaped) {
            $query .= ", profile_photo = '$profile_photo_escaped'";
        }

        if ($conn->query($query)) {
            $message = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile.';
        }
    }
}

// Get current profile
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$query = "SELECT * FROM member_profiles WHERE user_id = $user_id ORDER BY id DESC";
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
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cv.php">CV</a>
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
                <h2>My Profile</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo isset($profile['full_name']) ? $profile['full_name'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo isset($profile['phone']) ? $profile['phone'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($profile['address']) ? $profile['address'] : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Profile Photo</label>

                                <?php if (isset($profile['profile_photo']) && $profile['profile_photo']): ?>
                                    <div class="mb-3">
                                        <div class="current-photo">
                                            <p class="text-muted mb-2">Current Photo:</p>
                                            <img src="<?php echo $profile['profile_photo']; ?>"
                                                 alt="Current Profile Photo"
                                                 class="img-thumbnail"
                                                 style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                            <div class="mt-1">
                                                <small class="text-muted">File: <?php echo basename($profile['profile_photo']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                                <div class="form-text">Choose a new photo to replace the current one (JPG, PNG, GIF)</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vulnerable profile JavaScript -->
<script>
    // Vulnerable: Direct DOM manipulation with user input
    document.getElementById('full_name').addEventListener('input', function(e) {
        document.getElementById('name-preview').innerHTML = 'Hello, ' + e.target.value;
    });
    
    // Vulnerable: File upload preview without validation
    document.getElementById('profile_photo').addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                // Show preview of new photo
                var previewHtml = '<div class="mt-3"><p class="text-muted mb-2">New Photo Preview:</p>' +
                                '<img src="' + e.target.result + '" class="img-thumbnail" ' +
                                'style="max-width: 200px; max-height: 200px; object-fit: cover;"></div>';
                document.getElementById('photo-preview').innerHTML = previewHtml;
            };
            reader.readAsDataURL(file);
        } else {
            // Clear preview if no file selected
            document.getElementById('photo-preview').innerHTML = '';
        }
    });
</script>

<div id="name-preview"></div>
<div id="photo-preview"></div>

<?php require_once '../../templates/footer.php'; ?>