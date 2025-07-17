<?php 
session_start();

if (!isset($_SESSION['role'])) {
    print_r ($_SESSION['role']); 
    if ($_SESSION['role'] !== 'member') {
        echo "Access denied. You must be a member to view this page.";
        header('Location: ../pages/company/dashboard.php');
        exit;
    }   
}
?>