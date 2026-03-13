<?php
/**
 * Admin Account Reset Tool
 * This script will create/reset admin accounts
 * DELETE THIS FILE after use for security!
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Reset Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔧 Admin Account Reset Tool</h1>
    <div class='error'><strong>⚠️ SECURITY WARNING:</strong> Delete this file immediately after use!</div>
";

try {
    $conn = getDBConnection();
    
    // Check existing admin accounts
    echo "<h2>Current Admin Accounts:</h2>";
    $result = $conn->query("SELECT id, username, email, full_name, is_admin, created_at FROM users WHERE is_admin = 1");
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Admin</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . ($row['is_admin'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No admin accounts found in database. Creating new ones...</div>";
    }
    
    // Reset/Create admin accounts
    echo "<h2>Setting Up Admin Accounts...</h2>";
    
    // Password: admin123
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // First, delete existing admin accounts to ensure clean setup
    $conn->query("DELETE FROM users WHERE username IN ('admin', 'admin2')");
    
    // Create admin account
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, is_admin) VALUES (?, ?, ?, ?, 1)");
    
    $username = 'admin';
    $email = 'admin@paradisehotel.com';
    $fullname = 'Administrator';
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $fullname);
    
    if ($stmt->execute()) {
        echo "<div class='success'>✅ Admin account 'admin' created successfully!</div>";
    } else {
        echo "<div class='error'>❌ Failed to create admin account: " . htmlspecialchars($stmt->error) . "</div>";
    }
    $stmt->close();
    
    // Create admin2 account
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, is_admin) VALUES (?, ?, ?, ?, 1)");
    
    $username2 = 'admin2';
    $email2 = 'admin2@paradisehotel.com';
    $fullname2 = 'System Administrator';
    $stmt->bind_param("ssss", $username2, $email2, $hashedPassword, $fullname2);
    
    if ($stmt->execute()) {
        echo "<div class='success'>✅ Admin account 'admin2' created successfully!</div>";
    } else {
        echo "<div class='error'>❌ Failed to create admin2 account: " . htmlspecialchars($stmt->error) . "</div>";
    }
    $stmt->close();
    
    // Verify accounts were created
    echo "<h2>Verification:</h2>";
    $verify = $conn->query("SELECT id, username, email, is_admin FROM users WHERE is_admin = 1");
    if ($verify && $verify->num_rows > 0) {
        echo "<div class='success'>✅ Admin accounts verified in database:</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Admin Status</th></tr>";
        while ($row = $verify->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . ($row['is_admin'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div class='info'>";
    echo "<h3>✅ Login Credentials:</h3>";
    echo "<p><strong>Account 1:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>admin123</code></p>";
    echo "<p><strong>Account 2:</strong><br>";
    echo "Username: <code>admin2</code><br>";
    echo "Password: <code>admin123</code></p>";
    echo "<p><a href='admin/login.php' class='btn'>Go to Admin Login</a></p>";
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<div class='error'><strong>🔒 IMPORTANT:</strong> Delete this file (reset_admin.php) now for security!</div>";
echo "</body></html>";
?>
