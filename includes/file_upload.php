<?php
require_once __DIR__ . '/../config/env.php';

class FileUpload {
    // Vulnerable file upload
    public static function uploadFile($file, $subfolder = '') {
        $target_dir = UPLOAD_PATH . $subfolder . '/';
        
        // Create directory if not exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Insecure permissions
        }
        
        $original_name = $file['name'];
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        // No file type validation
        // No file size validation
        // No filename sanitization
        
        $target_file = $target_dir . $original_name;
        
        // Path traversal vulnerability
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $target_file;
        }
        
        return false;
    }
    
    // Vulnerable file deletion
    public static function deleteFile($filepath) {
        // No authorization check
        // Path traversal vulnerability
        if (file_exists($filepath)) {
            unlink($filepath);
            return true;
        }
        return false;
    }
    
    // File inclusion vulnerability
    public static function includeFile($filename) {
        // Direct file inclusion
        include $filename;
    }
}
?>