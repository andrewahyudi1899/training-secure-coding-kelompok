<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
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

if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] == "") {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token';
    } else {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        
        // Check if user is actually logged in (prevent foreign key constraint violation)
        if ($user_id <= 0) {
            $error = 'You must be logged in to perform this action.';
        } else {
            // check user access only can edit them self, check by jwt and session
            $user = $auth->getUserById($user_id);
            if($user) {
                $institution = $_POST['institution'];
                $degree = $_POST['degree'];
                $field_of_study = $_POST['field_of_study'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
        
                require_once '../../config/database.php';
                $db = new Database();
                $conn = $db->getConnection();
        
                // Use addslashes to prevent SQL crash but still vulnerable to XSS
                $institution_escaped = htmlspecialchars($institution);
                $degree_escaped = htmlspecialchars($degree);
                $field_of_study_escaped = htmlspecialchars($field_of_study);
                $start_date_escaped = htmlspecialchars($start_date);
                $end_date_escaped = htmlspecialchars($end_date);
        
                // Vulnerable: SQL injection (still possible with addslashes bypass)
                // $query = "INSERT INTO education (user_id, institution, degree, field_of_study, start_date, end_date)
                //      VALUES ($user_id, '$institution_escaped', '$degree_escaped', '$field_of_study_escaped', '$start_date_escaped', '$end_date_escaped')";
        
                $query = "INSERT INTO education (
                    user_id, institution, degree, field_of_study, start_date, end_date
                ) VALUES (
                    :user_id, :institution, :degree, :field_of_study, :start_date, :end_date
                )";

                $stmt = $conn->prepare($query);
                $is_success = $stmt->execute([
                    'user_id' => $user_id,
                    'institution' => $institution,
                    'degree' => $degree,
                    'field_of_study' => $field_of_study,
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]);

                if ($is_success) {
                    $message = 'Education added successfully!';
                } else {
                    $error = 'Failed to add education.';
                }
            }
    
        }
    }

}

// Get user education
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$query = "SELECT * FROM education WHERE user_id = $user_id ORDER BY start_date DESC";
$education = $conn->query($query);
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
                        <a class="nav-link active" href="education.php">Education</a>
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
                <h2>Education</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add Education</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="institution" class="form-label">Institution</label>
                                        <input type="text" class="form-control" id="institution" name="institution" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="degree" class="form-label">Degree</label>
                                        <input type="text" class="form-control" id="degree" name="degree" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="field_of_study" class="form-label">Field of Study</label>
                                <input type="text" class="form-control" id="field_of_study" name="field_of_study" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                            <button type="submit" class="btn btn-primary">Add Education</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>My Education</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($education->rowCount() > 0): ?>
                            <?php while ($edu = $education->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6><?php echo $edu['degree']; ?> in <?php echo $edu['field_of_study']; ?></h6>
                                            <p class="mb-1"><strong><?php echo $edu['institution']; ?></strong></p>
                                            <small class="text-muted">
                                                <?php echo date('M Y', strtotime($edu['start_date'])); ?> -
                                                <?php echo $edu['end_date'] ? date('M Y', strtotime($edu['end_date'])) : 'Present'; ?>
                                            </small>
                                        </div>
                                        <!-- Vulnerable: Direct object reference -->
                                        <a href="?delete_education=<?php echo $edu['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete education?')">Delete</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No education records added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Vulnerable: Delete education without proper authorization
if (isset($_GET['delete_education'])) {
    $education_id = addslashes($_GET['delete_education']); // Prevent SQL crash but still vulnerable
    require_once '../../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    // Vulnerable: No ownership check, still possible SQL injection
    $query = "DELETE FROM education WHERE id = '$education_id'";
    $conn->query($query);

    header('Location: education.php');
    exit;
}
?>

<?php require_once '../../templates/footer.php'; ?>