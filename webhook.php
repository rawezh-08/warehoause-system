<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming webhook
file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - Webhook received\n", FILE_APPEND);

// Get the payload
$payload = file_get_contents('php://input');
file_put_contents('webhook.log', "Payload: " . $payload . "\n", FILE_APPEND);

// Decode the payload
$data = json_decode($payload, true);

// Check if it's a push to main branch
if (isset($data['ref']) && $data['ref'] === 'refs/heads/main') {
    // Execute git pull
    $command = 'cd /var/www/html/warehoause-system && git pull origin main 2>&1';
    $output = shell_exec($command);
    
    // Log the output
    file_put_contents('webhook.log', "Command output: " . $output . "\n", FILE_APPEND);
    
    // Set proper permissions
    shell_exec('chown -R www-data:www-data /var/www/html/warehoause-system');
    shell_exec('chmod -R 755 /var/www/html/warehoause-system');
    
    echo "Webhook processed successfully";
} else {
    file_put_contents('webhook.log', "Not a push to main branch\n", FILE_APPEND);
    echo "Not a push to main branch";
}
?>
