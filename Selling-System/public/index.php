<?php

declare(strict_types=1);

// Define root path
define('ROOT_PATH', dirname(__DIR__));

// Autoload dependencies
require ROOT_PATH . '/vendor/autoload.php';

// Load environment variables


// Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');

// Initialize the application
$app = new App\Core\Application([
    'root_path' => ROOT_PATH,
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
]);

// Start session
session_start();

// Handle the request
$app->handle(); 