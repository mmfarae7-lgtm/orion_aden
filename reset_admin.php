<?php
require_once __DIR__ . '/config/app.php';

$hash = password_hash('123456789', PASSWORD_BCRYPT);
$result = query('SELECT id FROM users WHERE username = ? OR username = ?', ['admin', 'Orion']);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    update('UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, role = ? WHERE id = ?', 
        ['Orion', 'Orion@orion.edu', $hash, 'مدير النظام', 'super_admin', $user['id']]);
    echo "User updated successfully!\n";
} else {
    insert('INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)', 
        ['Orion', 'Orion@orion.edu', $hash, 'مدير النظام', 'super_admin']);
    echo "User created successfully!\n";
}
echo "Username: Orion\nPassword: 123456789\n";
