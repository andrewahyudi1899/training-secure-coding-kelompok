<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$auth = new Auth();
$auth->checkAccess('member');

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
    $skill_name = $_POST['skill_name'];
    $level = $_POST['level'];

    require_once '../../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    // Use addslashes to prevent SQL crash but still vulnerable to XSS
    // FIXING
    $skill_name_escaped = addslashes(htmlspecialchars($skill_name));
    $level_escaped = addslashes(htmlspecialchars($level));

    // Vulnerable: SQL injection (still possible with addslashes bypass)
    // FIXING
    $query = "INSERT INTO skills (user_id, skill_name, level) VALUES (:user_id, :skill_name_escaped, :level_escaped)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':skill_name_escaped', $skill_name_escaped);
    $stmt->bindParam(':level_escaped', $level_escaped);

    if ($stmt->execute()) {
        $message = 'Skill added successfully!';
    } else {
        $error = 'Failed to add skill.';
    }

    }}

// Get user skills
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$query = "SELECT * FROM skills WHERE user_id = $user_id ORDER BY id DESC";
$skills = $conn->query($query);
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
                        <a class="nav-link active" href="skills.php">Skills</a>
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
                <h2>My Skills</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Add New Skill</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="skill_name" class="form-label">Skill Name</label>
                                        <input type="text" class="form-control" id="skill_name" name="skill_name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="level" class="form-label">Level</label>
                                        <select class="form-control" id="level" name="level" required>
                                            <option value="beginner">Beginner</option>
                                            <option value="intermediate">Intermediate</option>
                                            <option value="advanced">Advanced</option>
                                            <option value="expert">Expert</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Add Skill</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>My Skills</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($skills->rowCount() > 0): ?>
                                    <?php while ($skill = $skills->fetch(PDO::FETCH_ASSOC)): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                            <div>
                                                <strong><?php echo $skill['skill_name']; ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo ucfirst($skill['level']); ?></small>
                                            </div>
                                            <!-- Vulnerable: Direct object reference -->
                                            <!-- FIXING -->
                                            <a href="?delete_skill=<?php echo encrypt($skill['id'], $key); ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Delete skill?')">Delete</a>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p>No skills added yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Vulnerable: Delete skill without proper authorization
if (isset($_GET['delete_skill'])) {
    // FIXING
    $skill_id = addslashes(htmlspecialchars(decrypt($_GET['delete_skill'], $key))); // Prevent SQL crash but still vulnerable
    require_once '../../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    // FIXING
    // Vulnerable: No ownership check, still possible SQL injection
    $query = "DELETE FROM skills WHERE id = :skillid";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':skillid', $skill_id);
    $stmt->execute();

    header('Location: skills.php');
    exit;
}
?>

<?php require_once '../../templates/footer.php'; ?>