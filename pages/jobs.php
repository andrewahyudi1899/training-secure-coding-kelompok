<?php
require_once '../includes/session.php';
require_once '../config/env.php';
require_once '../templates/header.php';
require_once '../templates/nav.php';

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Vulnerable search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';

// Vulnerable: SQL injection
$query = "SELECT j.*, c.company_name FROM jobs j 
         JOIN company_profiles c ON j.company_id = c.user_id 
         WHERE j.status = 'active'";

if ($search) {
    $query .= " AND (j.title LIKE '%$search%' OR j.description LIKE '%$search%')";
}

if ($location) {
    $query .= " AND j.location LIKE '%$location%'";
}

$query .= " ORDER BY j.created_at DESC";

$jobs = $conn->query($query);
?>

<div class="container mt-4">
    <h2>Available Jobs</h2>
    
    <!-- Vulnerable search form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" placeholder="Search jobs..." 
                           value="<?php echo $search; ?>">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="location" placeholder="Location..." 
                           value="<?php echo $location; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Vulnerable: XSS in search results -->
    <?php if ($search || $location): ?>
        <div class="alert alert-info">
            Search results for: <strong><?php echo $search; ?></strong>
            <?php if ($location): ?>
                in <strong><?php echo $location; ?></strong>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <?php if ($jobs->rowCount() > 0): ?>
            <?php while ($job = $jobs->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $job['title']; ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo $job['company_name']; ?></h6>
                            <p class="card-text"><?php echo substr($job['description'], 0, 150); ?>...</p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $job['location']; ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-briefcase"></i> <?php echo ucfirst($job['job_type']); ?>
                                </small>
                            </p>
                            <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                <p class="card-text">
                                    <small class="text-success">
                                        <i class="fas fa-money-bill"></i> 
                                        Rp <?php echo number_format($job['salary_min']); ?> - 
                                        Rp <?php echo number_format($job['salary_max']); ?>
                                    </small>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="job-detail.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">View Details</a>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'member'): ?>
                                <a href="member/apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-success">Apply Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning">No jobs found matching your criteria.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>