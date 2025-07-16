<?php
require_once '../includes/session.php';
require_once '../config/env.php';
require_once '../templates/header.php';
require_once '../templates/nav.php';

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all companies
$query = "SELECT cp.*, u.email, COUNT(j.id) as job_count
         FROM company_profiles cp
         JOIN users u ON cp.user_id = u.id
         LEFT JOIN jobs j ON cp.user_id = j.company_id AND j.status = 'active'
         GROUP BY cp.id
         ORDER BY cp.company_name";

$companies = $conn->query($query);
?>

<div class="container mt-4">
    <h2>Companies</h2>
    <p class="text-muted">Discover companies that are hiring</p>
    
    <div class="row">
        <?php if ($companies->rowCount() > 0): ?>
            <?php while ($company = $companies->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php if ($company['logo']): ?>
                                    <img src="../<?php echo $company['logo']; ?>" 
                                         class="me-3" style="width: 60px; height: 60px; object-fit: cover;" alt="Logo">
                                <?php else: ?>
                                    <div class="bg-primary text-white rounded me-3 d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h5 class="card-title mb-1"><?php echo $company['company_name']; ?></h5>
                                    <small class="text-muted"><?php echo $company['job_count']; ?> active jobs</small>
                                </div>
                            </div>
                            
                            <?php if ($company['description']): ?>
                                <p class="card-text"><?php echo substr($company['description'], 0, 150); ?>...</p>
                            <?php endif; ?>
                            
                            <?php if ($company['website']): ?>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-globe"></i> 
                                        <a href="<?php echo $company['website']; ?>" target="_blank"><?php echo $company['website']; ?></a>
                                    </small>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="company-jobs.php?company_id=<?php echo $company['user_id']; ?>" class="btn btn-primary">
                                View Jobs (<?php echo $company['job_count']; ?>)
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No companies found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>