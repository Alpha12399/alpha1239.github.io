<?php
// Debug information for email sending issues
header('Content-Type: text/plain; charset=UTF-8');

echo "=== Debug Information for Email Sending ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Operating System: " . php_uname() . "\n\n";

echo "=== Mail Function Availability ===\n";
if (function_exists('mail')) {
    echo "✓ mail() function is available\n";
} else {
    echo "✗ mail() function is NOT available\n";
}

echo "\n=== PHP Configuration ===\n";
$mail_config = [
    'sendmail_path',
    'SMTP',
    'smtp_port',
    'mail.force_extra_parameters',
    'disable_functions'
];

foreach ($mail_config as $config) {
    $value = ini_get($config);
    echo "$config: " . ($value ?: 'Not set') . "\n";
}

echo "\n=== Directory Permissions ===\n";
echo "Current directory: " . getcwd() . "\n";
echo "Is writable: " . (is_writable('.') ? 'Yes' : 'No') . "\n";

echo "\n=== Testing File Write ===\n";
$test_file = 'test_write.txt';
if (file_put_contents($test_file, 'Test write successful at ' . date('Y-m-d H:i:s'))) {
    echo "✓ Can write files to current directory\n";
    unlink($test_file);
} else {
    echo "✗ Cannot write files to current directory\n";
}

echo "\n=== Testing Network Connectivity ===\n";
$smtp_servers = [
    'smtp.gmail.com:587',
    'smtp.gmail.com:465',
    'localhost:25'
];

foreach ($smtp_servers as $server) {
    list($host, $port) = explode(':', $server);
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if (is_resource($connection)) {
        echo "✓ Can connect to $server\n";
        fclose($connection);
    } else {
        echo "✗ Cannot connect to $server ($errstr)\n";
    }
}

echo "\n=== End of Debug Information ===\n";
?>