<?php
require_once __DIR__ . '/functions.php';

try {
    createDatabaseSchema();
    echo "Database schema created successfully.\n";
    echo "Trusted Bank is now using MySQL for user authentication and account data.\n";
    echo "Visit login.php or register.php to continue.\n";
} catch (Exception $e) {
    echo "There was an error setting up the database: " . htmlspecialchars($e->getMessage());
}
