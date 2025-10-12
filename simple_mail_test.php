<?php
// Simple mail test
echo "Testing basic mail functionality...\n\n";

if (function_exists('mail')) {
    echo "mail() function is available.\n";
    
    $to = 'nexoraprime051709@gmail.com';
    $subject = 'Test Email from Nexora Prime';
    $message = 'This is a simple test email to check if the mail function works.';
    $headers = 'From: test@' . $_SERVER['SERVER_NAME'] . "\r\n" .
               'Reply-To: test@' . $_SERVER['SERVER_NAME'] . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    echo "Attempting to send email...\n";
    echo "To: $to\n";
    echo "Subject: $subject\n";
    echo "Headers: $headers\n";
    echo "Message: $message\n\n";
    
    $result = mail($to, $subject, $message, $headers);
    
    if ($result) {
        echo "SUCCESS: Email sent!\n";
        echo "Please check your inbox at nexoraprime051709@gmail.com\n";
    } else {
        echo "FAILED: Could not send email.\n";
        echo "This usually means the server is not configured to send emails.\n";
        echo "You may need to configure SMTP settings or use a different method.\n";
    }
} else {
    echo "mail() function is NOT available.\n";
    echo "You'll need to use an alternative method like PHPMailer or SMTP.\n";
}
?>