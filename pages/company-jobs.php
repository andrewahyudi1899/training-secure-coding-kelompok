<?php
require_once '../config/env.php';
require_once '../templates/header.php';
require_once '../templates/nav.php';

$company_id = $_GET['company_id'] ?? 0;

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get company details
$company_query = "SELECT * FROM company_profiles WHERE user_id = $company_id";
$company_result = $conn->query($company_query);
$company = $company_result->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: companies.php');
    exit;
}

// Get company jobs
$jobs_query = "SELECT * FROM jobs WHERE company_id = $company_id AND status = 'active' ORDER BY created_at DESC";
$jobs = $conn->query($jobs_query);
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="companies.php">Companies</a></li>
            <li class="breadcrumb-item active"><?php echo $company['company_name']; ?></li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <?php if ($company['logo']): ?>
                    <img src="../<?php echo $company['logo']; ?>" 
                         class="me-3" style="width: 80px; height: 80px; object-fit: cover;" alt="Logo">
                <?php else: ?>
                    <div class="bg-primary text-white rounded me-3 d-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-building fa-3x"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h2><?php echo $company['company_name']; ?></h2>
                    <p class="text-muted mb-0"><?php echo $jobs->rowCount(); ?> active jobs</p>
                </div>
            </div>
            
            <?php if ($company['description']): ?>
                <h5>About Company</h5>
                <!-- Vulnerable: XSS -->
                <p><?php echo $company['description']; ?></p>
            <?php endif; ?>
            
            <div class="row">
                <?php if ($company['website']): ?>
                    <div class="col-md-6">
                        <p><strong>Website:</strong> 
                            <a href="<?php echo $company['website']; ?>" target="_blank"><?php echo $company['website']; ?></a>
                        </p>
                    </div>
                <?php endif; ?>
                <?php if ($company['phone']): ?>
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> <?php echo $company['phone']; ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($company['address']): ?>
                    <div class="col-md-12">
                        <p><strong>Address:</strong> <?php echo $company['address']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <h3>Available Jobs</h3>
    
    <div class="row">
        <?php if ($jobs->rowCount() > 0): ?>
            <?php while ($job = $jobs->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $job['title']; ?></h5>
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
                <div class="alert alert-info">This company has no active job openings at the moment.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>