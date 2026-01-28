<?php
/**
 * Script to create initial admin user
 * Run this once after setting up the database
 * 
 * Usage: php database/create_admin.php
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/app.php';

// Default admin credentials (should be changed after first login)
$adminEmail = 'admin@hospital.com';
$adminPassword = 'Admin@123'; // Change this!
$adminName = 'System Administrator';

try {
    $pdo = getDBConnection();
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $adminEmail]);
    
    if ($stmt->fetch()) {
        echo "Admin user already exists!\n";
        exit;
    }
    
    // Get admin role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
    $stmt->execute();
    $role = $stmt->fetch();
    
    if (!$role) {
        die("Error: 'admin' role not found. Please run database/setup_sqlite.php first.\n");
    }
    
    $roleId = $role['id'];
    
    // Create admin user (plain text password for simplicity)
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password, status, created_at)
        VALUES (:full_name, :email, :password, 'active', datetime('now'))
    ");
    
    $stmt->execute([
        ':full_name' => $adminName,
        ':email' => $adminEmail,
        ':password' => $adminPassword
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Assign admin role
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id, created_at)
        VALUES (:user_id, :role_id, datetime('now'))
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':role_id' => $roleId
    ]);
    
    echo "Admin user created successfully!\n";
    echo "Email: {$adminEmail}\n";
    echo "Password: {$adminPassword}\n";
    echo "\n⚠️  IMPORTANT: Change the password after first login!\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

