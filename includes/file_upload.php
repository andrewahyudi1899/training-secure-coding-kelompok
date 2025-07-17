<?php
require_once __DIR__ . '/../config/env.php';

class FileUpload {
    // Vulnerable file upload
    // FIXING
    public static function uploadFile($file, $subfolder = '', $allowedExtensions = []) {
        $responseData = [
            'status' => true,
            'message' => '',
            'data' => [
                'target_file' => ''
            ]
        ];

        $target_dir = UPLOAD_PATH . $subfolder . '/';
        
        // Create directory if not exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Insecure permissions
        }
        
        $original_name = $file['name'];
        $original_size = $file['size'];
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        // No file type validation
        // FIXING
        $allowed_extensions = $allowedExtensions;
        // if (count($allowed_extensions) == 0) {
        //     $allowed_extensions = UPLOAD_ALLOWED_TYPES;
        // }
        if (!in_array($file_extension, $allowed_extensions)) {
            $responseData['status'] = false;
            $responseData['message'] = 'File upload not allowed';

            return $responseData;
        }

        // No file size validation
        // FIXING
        if ($original_size > UPLOAD_MAX_SIZE) {
            $responseData['status'] = false;
            $responseData['message'] = 'File upload exceed size limit';

            return $responseData;
        }

        // No filename sanitization
        
        
        $target_file = $target_dir . $original_name;
        
        // Path traversal vulnerability
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $responseData['data']['target_file'] = $target_file;
        }

        return $responseData;
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