<?php
require_once 'includes/session.php';
require_once 'config/env.php';
require_once 'templates/header.php';
require_once 'templates/nav.php';
?>

<div class="container mt-4">
    <div class="jumbotron bg-primary text-white p-5 rounded">
        <h1 class="display-4">Welcome to Job Portal</h1>
        <p class="lead">Find your dream job or hire the perfect candidate</p>
        <hr class="my-4">
        <p>Connect employers with job seekers in a simple and efficient way.</p>
        <div class="mt-4">
            <a class="btn btn-light btn-lg me-3" href="pages/auth/register.php" role="button">Get Started</a>
            <a class="btn btn-outline-light btn-lg" href="pages/jobs.php" role="button">Browse Jobs</a>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">For Job Seekers</h5>
                    <p class="card-text">Create your profile, upload your CV, and apply for jobs that match your skills.</p>
                    <a href="pages/auth/register.php?role=member" class="btn btn-primary">Join as Member</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-building fa-3x text-success mb-3"></i>
                    <h5 class="card-title">For Employers</h5>
                    <p class="card-text">Post job openings, manage applications, and find the right talent for your company.</p>
                    <a href="pages/auth/register.php?role=company" class="btn btn-success">Join as Company</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Analytics</h5>
                    <p class="card-text">Track your application progress and get insights about the job market.</p>
                    <a href="pages/analytics.php" class="btn btn-info">View Analytics</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Vulnerable search with XSS -->
    <div class="row mt-5">
        <div class="col-md-12">
            <h3>Recent Jobs</h3>
            <div id="recent-jobs">
                <?php
                // Vulnerable database query
                require_once 'config/database.php';
                $db = new Database();
                $conn = $db->getConnection();
                
                $search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
                $query = "SELECT j.*, c.company_name FROM jobs j 
                         JOIN company_profiles c ON j.company_id = c.user_id 
                         WHERE j.status = 'active' AND (j.title LIKE ? OR j.description LIKE ?)
                         ORDER BY j.created_at DESC LIMIT 5";
                
                $result = $conn->prepare($query);
                $result->execute(["%$search%", "%$search%"]);

                if ($result->rowCount() > 0) {
                    while ($job = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="card mb-3">';
                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">' . $job['title'] . '</h5>';
                        echo '<h6 class="card-subtitle mb-2 text-muted">' . $job['company_name'] . '</h6>';
                        echo '<p class="card-text">' . substr($job['description'], 0, 150) . '...</p>';
                        echo '<p class="card-text"><small class="text-muted">Location: ' . $job['location'] . '</small></p>';
                        echo '<a href="pages/job-detail.php?id=' . $job['id'] . '" class="btn btn-primary">View Details</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No jobs found.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>