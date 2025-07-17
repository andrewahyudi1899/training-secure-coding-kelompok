<?php 
if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] !== 'member') {
        echo '<div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#f8f9fa;">
            <div style="background:#fff;padding:40px 60px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);text-align:center;">
                <h2 style="color:#dc3545;margin-bottom:20px;">Access Denied</h2>
                <p style="font-size:18px;color:#333;">You must be a <strong>member</strong> to view this page.</p>
            </div>
              </div>';
        exit;
    }   
}
?>