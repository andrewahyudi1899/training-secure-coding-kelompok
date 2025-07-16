<?php
require_once '../config/env.php';
require_once '../templates/header.php';
require_once '../templates/nav.php';

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get analytics data
$total_jobs_query = "SELECT COUNT(*) as total FROM jobs WHERE status = 'active'";
$total_jobs = $conn->query($total_jobs_query)->fetch(PDO::FETCH_ASSOC)['total'];

$total_companies_query = "SELECT COUNT(*) as total FROM company_profiles";
$total_companies = $conn->query($total_companies_query)->fetch(PDO::FETCH_ASSOC)['total'];

$total_members_query = "SELECT COUNT(*) as total FROM member_profiles";
$total_members = $conn->query($total_members_query)->fetch(PDO::FETCH_ASSOC)['total'];

$total_applications_query = "SELECT COUNT(*) as total FROM job_applications";
$total_applications = $conn->query($total_applications_query)->fetch(PDO::FETCH_ASSOC)['total'];

// Recent jobs
$recent_jobs_query = "SELECT j.*, c.company_name FROM jobs j 
                     JOIN company_profiles c ON j.company_id = c.user_id 
                     WHERE j.status = 'active' 
                     ORDER BY j.created_at DESC LIMIT 5";
$recent_jobs = $conn->query($recent_jobs_query);

// Job types distribution
$job_types_query = "SELECT job_type, COUNT(*) as count FROM jobs WHERE status = 'active' GROUP BY job_type";
$job_types = $conn->query($job_types_query);
?>

<div class="container mt-4">
    <h2>Job Market Analytics</h2>
    <p class="text-muted">Overview of the current job market</p>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-briefcase fa-2x mb-2"></i>
                    <h3><?php echo $total_jobs; ?></h3>
                    <p>Active Jobs</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-building fa-2x mb-2"></i>
                    <h3><?php echo $total_companies; ?></h3>
                    <p>Companies</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?php echo $total_members; ?></h3>
                    <p>Job Seekers</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-paper-plane fa-2x mb-2"></i>
                    <h3><?php echo $total_applications; ?></h3>
                    <p>Applications</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Job Postings</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_jobs->rowCount() > 0): ?>
                        <?php while ($job = $recent_jobs->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                <div>
                                    <h6 class="mb-1"><?php echo $job['title']; ?></h6>
                                    <small class="text-muted"><?php echo $job['company_name']; ?> • <?php echo $job['location']; ?></small>
                                </div>
                                <div>
                                    <small class="text-muted"><?php echo date('M d', strtotime($job['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No recent jobs found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Job Types Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if ($job_types->rowCount() > 0): ?>
                        <?php while ($type = $job_types->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo ucfirst($type['job_type']); ?></span>
                                <span class="badge bg-primary"><?php echo $type['count']; ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No job types data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>