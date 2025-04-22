<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'todo_app');

// Error reporting (only in development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_SERVER,
        DB_USERNAME,
        DB_PASSWORD,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    $pdo->exec($sql);

    // Select the database
    $pdo = new PDO(
        "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME,
        DB_USERNAME,
        DB_PASSWORD,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    // Set charset
    $pdo->exec("SET NAMES utf8mb4");

    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);

    // Create tasks table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        task VARCHAR(255) NOT NULL,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        due_date DATE,
        completed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);

} catch(PDOException $e) {
    die("ERROR: " . $e->getMessage());
}

// Function to safely close database connection
function closeConnection() {
    global $pdo;
    if($pdo) {
        $pdo = null;
    }
}

// Function to sanitize input
function sanitizeInput($data) {
    if (is_string($data)) {
        return htmlspecialchars(trim($data));
    }
    return $data;
}

// Function to handle database errors
function handleDBError($error) {
    error_log("Database Error: " . $error);
    return json_encode([
        "status" => "error",
        "message" => "An error occurred. Please try again later."
    ]);
}
?> 