<?php
// Test if mail function is available and working
if (function_exists('mail')) {
    echo "Mail function is available\n";
    
    // Try to send a test email
    $to = 'nexoraprime051709@gmail.com';
    $subject = 'Test Email from Nexora Prime';
    $message = 'This is a test email to check if the mail function is working properly.';
    $headers = 'From: webmaster@' . $_SERVER['SERVER_NAME'] . "\r\n" .
               'Reply-To: webmaster@' . $_SERVER['SERVER_NAME'] . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    if (mail($to, $subject, $message, $headers)) {
        echo "Test email sent successfully!";
    } else {
        echo "Failed to send test email. Mail function may be disabled or misconfigured.";
    }
} else {
    echo "Mail function is NOT available on this server.";
}

// Show PHP info related to mail
echo "\n\nMail configuration:\n";
echo "sendmail_path: " . (ini_get('sendmail_path') ?: 'Not set') . "\n";
echo "SMTP: " . (ini_get('SMTP') ?: 'Not set') . "\n";
echo "smtp_port: " . (ini_get('smtp_port') ?: 'Not set') . "\n";
?>