<?php
// Quick migration runner for updating existing installations
// Access this page once to apply the migration, then delete or move it

require_once __DIR__ . '/config/database.php';

// Run migration SQL
$sql = file_get_contents(__DIR__ . '/sql/migration_v2_roles.sql');
$statements = explode(';', $sql);

echo "<h3>Orion Aden Institute - التحديث</h3>";
echo "<pre>";

$conn = getConnection();
$success = 0;
$errors = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    if ($conn->query($stmt)) {
        echo "OK: " . substr($stmt, 0, 80) . "...\n";
        $success++;
    } else {
        echo "ERROR: " . $conn->error . "\n";
        echo "SQL: " . $stmt . "\n";
        $errors++;
    }
}

echo "\n---\n";
echo "Completed: $success statements succeeded, $errors failed\n";
echo "</pre>";

if ($errors === 0) {
    echo "<p style='color:green;font-weight:bold;'>Migration completed successfully!</p>";
    echo "<p>Users can now log in with:<br>";
    echo "Username: <strong>super_admin</strong>, <strong>admin</strong>, <strong>accountant</strong>, <strong>teacher</strong>, <strong>reception</strong>, or <strong>viewer</strong><br>";
    echo "Password: <strong>123456789</strong> for all users</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
}
