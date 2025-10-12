<?php
// Test SMTP configuration
echo "Testing SMTP configuration...\n";

// Check if we can use SMTP
if (function_exists('stream_socket_client')) {
    echo "stream_socket_client is available\n";
} else {
    echo "stream_socket_client is NOT available\n";
}

// Check common SMTP ports
$ports = [25, 465, 587];
foreach ($ports as $port) {
    $connection = @fsockopen("smtp.gmail.com", $port, $errno, $errstr, 5);
    if (is_resource($connection)) {
        echo "Port $port is open\n";
        fclose($connection);
    } else {
        echo "Port $port is closed or blocked\n";
    }
}

// Check if we can use PHPMailer
if (file_exists('PHPMailer/PHPMailer.php')) {
    echo "PHPMailer is available\n";
} else {
    echo "PHPMailer is NOT available\n";
}
?>