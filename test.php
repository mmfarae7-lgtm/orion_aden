<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Testing...</h3>";

// Test 1: PHP version
echo "PHP Version: " . phpversion() . "<br>";

// Test 2: Database connection
echo "<br><b>Test DB connection:</b><br>";
try {
    require_once __DIR__ . '/config/database.php';
    $conn = getConnection();
    echo "✅ DB connected! Server: " . mysqli_get_server_info($conn) . "<br>";
    $tables = getRows("SHOW TABLES");
    echo "Tables: " . count($tables) . "<br>";
} catch (Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "<br>";
}

// Test 3: Config
echo "<br><b>Test BASE_URL:</b><br>";
echo "BASE_URL: '" . BASE_URL . "'<br>";
echo "DOC_ROOT: '" . str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) . "'<br>";
echo "BASE_PATH: '" . str_replace('\\', '/', realpath(__DIR__ . '/..')) . "'<br>";
