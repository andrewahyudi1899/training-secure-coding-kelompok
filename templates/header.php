<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    
    <!-- Vulnerable inline JavaScript - XSS exposure -->
    <script>
        // dicomment bro, g baik ini... JWT jgn d share di UI, keliatan di debugger
        // Expose JWT secret to client-side
        // window.JWT_SECRET = '<?php // echo JWT_SECRET; ?>';
        
        // Vulnerable user data exposure
        <?php 
            if(isset($_SESSION['user_id'])){
                $_SESSION['user_id'] = htmlspecialchars($_SESSION['user_id']);
                $_SESSION['username'] = htmlspecialchars($_SESSION['username']);
                $_SESSION['role'] = htmlspecialchars($_SESSION['role']);
            }else{
                unset($_SESSION['username']);
                unset($_SESSION['role']);
            }
        ?>
        <?php  if (isset($_SESSION['user_id'])): ?>
        window.currentUser = {
            id: <?php  echo $_SESSION['user_id'] ?>,
            username: '<?php echo $_SESSION['username']; ?>',
            role: '<?php echo $_SESSION['role']; ?>'
        };
        <?php endif; ?>
    </script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">