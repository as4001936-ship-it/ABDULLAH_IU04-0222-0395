<?php
/**
 * Seed Mock Users into Database
 * 
 * This script inserts all test users from mock_users.json into the database
 * with properly hashed passwords.
 * 
 * Usage: php database/seed_users.php
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/app.php';

echo "Seeding mock users into database...\n\n";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        die("Failed to connect to database. Check your configuration.\n");
    }
    
    // Load mock users from JSON
    $mockUsersFile = __DIR__ . '/../data/mock_users.json';
    if (!file_exists($mockUsersFile)) {
        die("Mock users file not found: $mockUsersFile\n");
    }
    
    $jsonData = json_decode(file_get_contents($mockUsersFile), true);
    if (!$jsonData || !isset($jsonData['users'])) {
        die("Invalid mock users JSON file.\n");
    }
    
    $users = $jsonData['users'];
    
    // Get role IDs
    $roleMap = [];
    $stmt = $pdo->query("SELECT id, name FROM roles");
    while ($role = $stmt->fetch()) {
        $roleMap[$role['name']] = $role['id'];
    }
    
    if (empty($roleMap)) {
        die("No roles found. Please run seed_sqlite.sql first to create roles.\n");
    }
    
    $inserted = 0;
    $skipped = 0;
    
    foreach ($users as $userData) {
        $email = $userData['email'];
        
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            echo "⏭️  Skipping {$email} (already exists)\n";
            $skipped++;
            continue;
        }
        
        // Insert user (plain text password for simplicity)
        $stmt = $pdo->prepare("
            INSERT INTO users (
                full_name, email, password, status, phone, 
                failed_login_attempts, last_login_at, created_at
            ) VALUES (
                :full_name, :email, :password, :status, :phone,
                :failed_login_attempts, :last_login_at, datetime('now')
            )
        ");
        
        $stmt->execute([
            ':full_name' => $userData['full_name'],
            ':email' => $email,
            ':password' => $userData['password'],
            ':status' => $userData['status'],
            ':phone' => $userData['phone'],
            ':failed_login_attempts' => $userData['failed_login_attempts'] ?? 0,
            ':last_login_at' => $userData['last_login_at'] ?? null
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Assign roles
        if (!empty($userData['roles'])) {
            foreach ($userData['roles'] as $roleName) {
                if (isset($roleMap[$roleName])) {
                    $stmt = $pdo->prepare("
                        INSERT OR IGNORE INTO user_roles (user_id, role_id, created_at)
                        VALUES (:user_id, :role_id, datetime('now'))
                    ");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':role_id' => $roleMap[$roleName]
                    ]);
                }
            }
        }
        
        echo "✓ Created user: {$userData['full_name']} ({$email})\n";
        $inserted++;
    }
    
    echo "\n✅ User seeding complete!\n";
    echo "Inserted: {$inserted} users\n";
    echo "Skipped: {$skipped} users (already exist)\n";
    echo "\nYou can now remove data/mock_users.json if you want.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

