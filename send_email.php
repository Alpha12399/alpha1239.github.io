<?php
// send_email.php - Enhanced email handler for contact forms with multiple fallback methods and security

// Include security headers
require_once 'security_headers.php';

// Include input validation functions
require_once 'input_validation.php';

// Include secure session management
require_once 'secure_session.php';

// Include rate limiter
require_once 'rate_limiter.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json; charset=UTF-8');

// Initialize secure session
init_secure_session();

// Validate session integrity
if (!validate_session()) {
    echo json_encode(['status' => 'error', 'message' => 'Session invalide. Veuillez réessayer.']);
    exit;
}

// Initialize rate limiter (10 requests per hour per IP)
$rateLimiter = new RateLimiter(10, 3600, 'rate_limit.json');

// Get client IP
$clientIP = $rateLimiter->getClientIP();

// Check if IP is blocked
if ($rateLimiter->isBlocked($clientIP)) {
    echo json_encode(['status' => 'error', 'message' => 'Trop de requêtes. Veuillez réessayer plus tard.']);
    exit;
}

// Check rate limit
if (!$rateLimiter->isAllowed($clientIP)) {
    // Block IP for 1 hour if rate limit exceeded
    $rateLimiter->blockIP($clientIP, 3600);
    echo json_encode(['status' => 'error', 'message' => 'Trop de requêtes. Veuillez réessayer plus tard.']);
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting - check if user is sending too many requests
    if (!isset($_SESSION['last_email_time'])) {
        $_SESSION['last_email_time'] = 0;
    }
    
    $current_time = time();
    if (($current_time - $_SESSION['last_email_time']) < 10) { // 10 seconds cooldown
        echo json_encode(['status' => 'error', 'message' => 'Veuillez patienter avant d\'envoyer un autre message.']);
        exit;
    }
    
    // Update last email time
    $_SESSION['last_email_time'] = $current_time;
    
    // Get form data and sanitize
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
    
    // CSRF protection
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validate_csrf_token($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => 'Requête invalide. Veuillez réessayer.']);
        exit;
    }
    
    // Log received data for debugging
    error_log("Contact form submission: name=$name, email=$email, subject=$subject");
    
    // Validate required fields with enhanced validation
    $validated_name = validate_name($name);
    $validated_email = validate_email($email) ? $email : false;
    $validated_subject = validate_subject($subject);
    $validated_message = validate_message($message);
    
    if (!$validated_name || !$validated_email || !$validated_subject || !$validated_message) {
        echo json_encode(['status' => 'error', 'message' => 'Données invalides. Veuillez vérifier vos entrées.']);
        exit;
    }
    
    // Email configuration
    $to = 'nexoraprime051709@gmail.com';
    $email_subject = "Nexora Prime - $validated_subject";
    $email_body = "Vous avez reçu un nouveau message via le formulaire de contact de votre site.\n\n";
    $email_body .= "Nom: $validated_name\n";
    $email_body .= "Email: $validated_email\n";
    $email_body .= "Sujet: $validated_subject\n";
    $email_body .= "Message:\n$validated_message\n";
    
    // Enhanced headers
    $headers = "From: $validated_email\r\n";
    $headers .= "Reply-To: $validated_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Additional headers to improve deliverability
    $headers .= "MIME-Version: 1.0\r\n";
    
    // Log email details for debugging
    error_log("Sending email to: $to");
    error_log("Email subject: $email_subject");
    error_log("Email headers: $headers");
    
    // Try multiple methods to send email
    
    // Method 1: Standard mail() function
    if (function_exists('mail')) {
        error_log("Trying method 1: mail() function");
        $mail_result = mail($to, $email_subject, $email_body, $headers);
        if ($mail_result) {
            echo json_encode(['status' => 'success', 'message' => 'Votre message a été envoyé avec succès (méthode 1)!']);
            exit;
        } else {
            error_log("Method 1 failed");
        }
    } else {
        error_log("mail() function not available");
    }
    
    // Method 2: Using PHP's built-in mail() with additional parameters
    if (function_exists('mail')) {
        error_log("Trying method 2: mail() function with additional parameters");
        $additional_params = "-f $validated_email";
        $mail_result = mail($to, $email_subject, $email_body, $headers, $additional_params);
        if ($mail_result) {
            echo json_encode(['status' => 'success', 'message' => 'Votre message a été envoyé avec succès (méthode 2)!']);
            exit;
        } else {
            error_log("Method 2 failed");
        }
    }
    
    // Method 3: Write to a file as a backup (for debugging purposes)
    error_log("Trying method 3: Writing to file");
    $log_entry = date('Y-m-d H:i:s') . " - Contact form submission:\n" . $email_body . "\n------------------------\n";
    $log_file = 'contact_form_submissions.log';
    
    if (file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX)) {
        error_log("Message saved to log file");
        // Even though we couldn't send the email, we'll report success to the user
        // and inform them that we've logged their message
        echo json_encode([
            'status' => 'partial_success', 
            'message' => 'Votre message a été enregistré. Nous vous contacterons dès que possible. (Note: Le système d\'email rencontre des problèmes techniques.)'
        ]);
        exit;
    } else {
        error_log("Failed to write to log file");
    }
    
    // If all methods fail
    echo json_encode([
        'status' => 'error', 
        'message' => 'Impossible d\'envoyer votre message en raison de problèmes techniques. Veuillez nous contacter directement par email à nexoraprime051709@gmail.com'
    ]);
    
} else {
    // Not a POST request
    echo json_encode(['status' => 'error', 'message' => 'Méthode de requête non autorisée.']);
}
?>