<?php
// Define the application's root directory
define('ROOT_DIR', dirname(__DIR__));

// Require the Composer autoloader
require ROOT_DIR . '/vendor/autoload.php';

// Initialize the environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

// Error handling in development mode
if ($_ENV['APP_ENV'] === 'development' && $_ENV['APP_DEBUG'] === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Create session if it doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize the application
$app = new App\Core\Application();

// Run the application
$app->run();
