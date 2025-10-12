<?php
// file_upload_security.php - Security functions for file uploads

/**
 * Validate and secure file uploads
 * @param array $file The $_FILES array element for the uploaded file
 * @param array $allowed_types Array of allowed MIME types
 * @param int $max_size Maximum file size in bytes (default 5MB)
 * @return array Result with status and message
 */
function validate_uploaded_file($file, $allowed_types = [], $max_size = 5242880) {
    // Check if file was uploaded without errors
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'status' => 'error',
            'message' => 'Erreur lors de l\'upload du fichier.'
        ];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return [
            'status' => 'error',
            'message' => 'Le fichier est trop volumineux. Taille maximale: ' . format_bytes($max_size)
        ];
    }
    
    // Check file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!empty($allowed_types) && !in_array($file_type, $allowed_types)) {
        return [
            'status' => 'error',
            'message' => 'Type de fichier non autorisé. Types autorisés: ' . implode(', ', $allowed_types)
        ];
    }
    
    // Check file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = get_allowed_extensions($allowed_types);
    if (!empty($allowed_extensions) && !in_array($file_extension, $allowed_extensions)) {
        return [
            'status' => 'error',
            'message' => 'Extension de fichier non autorisée. Extensions autorisées: ' . implode(', ', $allowed_extensions)
        ];
    }
    
    // Check for malicious content
    if (is_malicious_file($file['tmp_name'])) {
        return [
            'status' => 'error',
            'message' => 'Fichier potentiellement dangereux détecté.'
        ];
    }
    
    // Generate secure filename
    $secure_filename = generate_secure_filename($file['name']);
    
    return [
        'status' => 'success',
        'message' => 'Fichier valide',
        'secure_filename' => $secure_filename,
        'file_type' => $file_type
    ];
}

/**
 * Format bytes to human readable format
 * @param int $bytes Number of bytes
 * @param int $precision Decimal precision
 * @return string Formatted bytes
 */
function format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get allowed file extensions based on MIME types
 * @param array $allowed_types Array of allowed MIME types
 * @return array Array of allowed extensions
 */
function get_allowed_extensions($allowed_types) {
    $extensions = [];
    $mime_to_extension = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/zip' => ['zip'],
        'application/x-rar-compressed' => ['rar']
    ];
    
    foreach ($allowed_types as $type) {
        if (isset($mime_to_extension[$type])) {
            $extensions = array_merge($extensions, $mime_to_extension[$type]);
        }
    }
    
    return array_unique($extensions);
}

/**
 * Check if file contains potentially malicious content
 * @param string $file_path Path to the temporary file
 * @return bool True if malicious content detected
 */
function is_malicious_file($file_path) {
    // Check for PHP tags in the file
    $content = file_get_contents($file_path);
    
    // Check for PHP opening tags
    if (preg_match('/<\?php/i', $content) || preg_match('/<\?=/i', $content)) {
        return true;
    }
    
    // Check for JavaScript in image files
    $mime_type = mime_content_type($file_path);
    if (strpos($mime_type, 'image/') === 0) {
        if (preg_match('/<script/i', $content) || preg_match('/javascript:/i', $content)) {
            return true;
        }
    }
    
    // Check for null bytes
    if (strpos($content, "\0") !== false) {
        return true;
    }
    
    return false;
}

/**
 * Generate a secure filename
 * @param string $original_name Original filename
 * @return string Secure filename
 */
function generate_secure_filename($original_name) {
    // Get file extension
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    
    // Generate unique name with timestamp and random string
    $timestamp = time();
    $random_string = bin2hex(random_bytes(8));
    
    // Create secure filename
    $secure_name = $timestamp . '_' . $random_string;
    
    // Add extension if it exists
    if (!empty($extension)) {
        $secure_name .= '.' . strtolower($extension);
    }
    
    return $secure_name;
}

/**
 * Securely move uploaded file to destination
 * @param array $file The $_FILES array element
 * @param string $destination_directory Destination directory (must be secure)
 * @param string $secure_filename Secure filename generated by generate_secure_filename()
 * @return array Result with status and message
 */
function secure_move_uploaded_file($file, $destination_directory, $secure_filename) {
    // Ensure destination directory exists and is secure
    if (!is_dir($destination_directory)) {
        return [
            'status' => 'error',
            'message' => 'Le répertoire de destination n\'existe pas.'
        ];
    }
    
    // Check if directory is secure (not in web root or publicly accessible)
    $real_destination = realpath($destination_directory);
    $real_doc_root = realpath($_SERVER['DOCUMENT_ROOT']);
    
    if (strpos($real_destination, $real_doc_root) === 0) {
        return [
            'status' => 'error',
            'message' => 'Le répertoire de destination n\'est pas sécurisé.'
        ];
    }
    
    // Create full destination path
    $destination_path = $real_destination . DIRECTORY_SEPARATOR . $secure_filename;
    
    // Move the file
    if (move_uploaded_file($file['tmp_name'], $destination_path)) {
        // Set proper file permissions (read-only)
        chmod($destination_path, 0644);
        
        return [
            'status' => 'success',
            'message' => 'Fichier téléchargé avec succès.',
            'file_path' => $destination_path
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Erreur lors du déplacement du fichier.'
        ];
    }
}

/**
 * Sanitize uploaded file content
 * @param string $file_path Path to the uploaded file
 * @param string $file_type MIME type of the file
 * @return bool True if sanitization successful
 */
function sanitize_uploaded_file($file_path, $file_type) {
    // For images, we can use GD to reprocess them
    if (strpos($file_type, 'image/') === 0) {
        return sanitize_image_file($file_path, $file_type);
    }
    
    // For text files, remove null bytes and control characters
    if (strpos($file_type, 'text/') === 0 || $file_type === 'application/json') {
        return sanitize_text_file($file_path);
    }
    
    // For other file types, we just check for malicious content
    return !is_malicious_file($file_path);
}

/**
 * Sanitize image files by reprocessing them
 * @param string $file_path Path to the image file
 * @param string $file_type MIME type of the file
 * @return bool True if sanitization successful
 */
function sanitize_image_file($file_path, $file_type) {
    // Create image resource based on file type
    switch ($file_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file_path);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file_path);
            break;
        default:
            return false;
    }
    
    // If image creation failed, file is invalid
    if (!$image) {
        return false;
    }
    
    // Save the reprocessed image to remove any embedded malicious code
    switch ($file_type) {
        case 'image/jpeg':
            $result = imagejpeg($image, $file_path, 90);
            break;
        case 'image/png':
            $result = imagepng($image, $file_path, 9);
            break;
        case 'image/gif':
            $result = imagegif($image, $file_path);
            break;
        case 'image/webp':
            $result = imagewebp($image, $file_path, 90);
            break;
        default:
            $result = false;
    }
    
    // Free up memory
    imagedestroy($image);
    
    return $result;
}

/**
 * Sanitize text files by removing null bytes and control characters
 * @param string $file_path Path to the text file
 * @return bool True if sanitization successful
 */
function sanitize_text_file($file_path) {
    // Read file content
    $content = file_get_contents($file_path);
    
    if ($content === false) {
        return false;
    }
    
    // Remove null bytes
    $content = str_replace("\0", '', $content);
    
    // Remove control characters except newlines and tabs
    $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
    
    // Write sanitized content back to file
    return file_put_contents($file_path, $content) !== false;
}
?>