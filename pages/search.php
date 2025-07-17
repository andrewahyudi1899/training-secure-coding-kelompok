<?php
require_once '../includes/session.php';
require_once '../config/env.php';
require_once '../templates/header.php';
require_once '../templates/nav.php';

$search_query = $_GET['q'] ?? '';

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Use addslashes to prevent SQL crash but still vulnerable to XSS
$search_query_escaped = htmlspecialchars($search_query);

// Vulnerable: SQL injection (still possible with addslashes bypass)
$query = "SELECT j.*, c.company_name FROM jobs j
         JOIN company_profiles c ON j.company_id = c.user_id
         WHERE j.status = 'active' AND (j.title LIKE '%:search_query_escaped%' OR j.description LIKE '%:search_query_escaped%')
         ORDER BY j.created_at DESC";

$jobs = $conn->query($query);
$jobs->bindParam('search_query_escaped', $search_query_escaped);
// $jobs->execute()
?>

<div class="container mt-4">
    <h2>Search Results</h2>
    
    <!-- Vulnerable: XSS in search display -->
    <p class="text-muted">Showing results for: <strong><?php echo htmlspecialchars($search_query); ?></strong></p>
    
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
                <div class="alert alert-warning">
                    No jobs found for your search query. Try different keywords.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>