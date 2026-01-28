<?php
/**
 * Re-seed Test Users
 * Adds the default test users back to the database if they don't exist
 * Usage: php database/reseed_users.php
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';

echo "Re-seeding test users...\n\n";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        die("Failed to connect to database. Check your configuration.\n");
    }
    
    echo "✓ Connected to SQLite database\n\n";
    
    // Check existing users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $existingCount = $stmt->fetchColumn();
    echo "Current users in database: $existingCount\n\n";
    
    // Add show_on_login column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN show_on_login INTEGER DEFAULT 0");
        echo "✓ Added show_on_login column\n";
    } catch (PDOException $e) {
        // Column might already exist, that's okay
    }
    
    // Insert test users (using INSERT OR IGNORE to avoid duplicates)
    // show_on_login = 1 for the 5 main test users, 0 for locked/inactive
    $users = [
        [1, 'System Administrator', 'admin@hospital.com', 'Admin@123', 'active', NULL, 0, 1],
        [2, 'Fatima Ali', 'receptionist@hospital.com', 'Receptionist@123', 'active', '555-0101', 0, 1],
        [3, 'Dr. Ahmed Khan', 'doctor@hospital.com', 'Doctor@123', 'active', '555-0202', 0, 1],
        [4, 'Ayesha Malik', 'lab@hospital.com', 'LabTech@123', 'active', '555-0303', 0, 1],
        [5, 'Hassan Raza', 'pharmacist@hospital.com', 'Pharmacist@123', 'active', '555-0404', 0, 1],
        [6, 'Locked User', 'locked@hospital.com', 'Locked@123', 'locked', NULL, 5, 0],
        [7, 'Inactive User', 'inactive@hospital.com', 'Inactive@123', 'inactive', NULL, 0, 0],
    ];
    
    $inserted = 0;
    $skipped = 0;
    
    foreach ($users as $user) {
        try {
            // Check if user exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $checkStmt->execute([$user[0]]);
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                // Update existing user to set show_on_login
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, password = ?, status = ?, phone = ?, 
                        failed_login_attempts = ?, show_on_login = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$user[1], $user[2], $user[3], $user[4], $user[5], $user[6], $user[7], $user[0]]);
                echo "⊘ Updated: {$user[2]} ({$user[1]}) - show_on_login = {$user[7]}\n";
                $skipped++;
            } else {
                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO users (id, full_name, email, password, status, phone, failed_login_attempts, show_on_login, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
                ");
                $stmt->execute($user);
                echo "✓ Inserted: {$user[2]} ({$user[1]}) - show_on_login = {$user[7]}\n";
                $inserted++;
            }
            
            if ($stmt->rowCount() > 0) {
                echo "✓ Inserted: {$user[2]} ({$user[1]})\n";
                $inserted++;
            } else {
                echo "⊘ Skipped (already exists): {$user[2]}\n";
                $skipped++;
            }
        } catch (PDOException $e) {
            echo "✗ Error inserting {$user[2]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Clear existing role assignments for test users (optional - comment out if you want to keep existing assignments)
    echo "Clearing existing role assignments for test users...\n";
    $testEmails = ['admin@hospital.com', 'receptionist@hospital.com', 'doctor@hospital.com', 
                   'lab@hospital.com', 'pharmacist@hospital.com', 'locked@hospital.com', 'inactive@hospital.com'];
    $placeholders = str_repeat('?,', count($testEmails) - 1) . '?';
    $stmt = $pdo->prepare("
        DELETE FROM user_roles 
        WHERE user_id IN (SELECT id FROM users WHERE email IN ($placeholders))
    ");
    $stmt->execute($testEmails);
    $deleted = $stmt->rowCount();
    echo "  ✓ Cleared $deleted existing role assignments\n\n";
    
    // Check if roles exist
    echo "Checking roles in database...\n";
    $stmt = $pdo->query("SELECT id, name, display_name FROM roles");
    $existingRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($existingRoles)) {
        echo "  ⚠ No roles found in database! Seeding roles first...\n";
        // Seed roles from seed file
        $seedFile = __DIR__ . '/seed_sqlite.sql';
        if (file_exists($seedFile)) {
            $seed = file_get_contents($seedFile);
            $lines = explode("\n", $seed);
            $cleanLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || preg_match('/^--/', $line)) {
                    continue;
                }
                if (strpos($line, '--') !== false) {
                    $line = substr($line, 0, strpos($line, '--'));
                    $line = trim($line);
                }
                if (!empty($line)) {
                    $cleanLines[] = $line;
                }
            }
            $cleanSchema = implode(' ', $cleanLines);
            $statements = array_filter(
                array_map('trim', explode(';', $cleanSchema)),
                function($stmt) {
                    return !empty($stmt);
                }
            );
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && stripos($statement, 'INSERT') === 0) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Continue
                    }
                }
            }
            echo "  ✓ Roles seeded\n";
        }
        
        // Re-fetch roles
        $stmt = $pdo->query("SELECT id, name, display_name FROM roles");
        $existingRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "  Found " . count($existingRoles) . " roles:\n";
    foreach ($existingRoles as $role) {
        echo "    - {$role['name']} ({$role['display_name']})\n";
    }
    echo "\n";
    
    // Assign roles to users
    echo "Assigning roles to users...\n";
    
    $roleAssignments = [
        ['admin@hospital.com', 'admin'],
        ['receptionist@hospital.com', 'receptionist'],
        ['doctor@hospital.com', 'doctor'],
        ['lab@hospital.com', 'lab_technician'],
        ['pharmacist@hospital.com', 'pharmacist'],
        ['locked@hospital.com', 'receptionist'],
        ['inactive@hospital.com', 'doctor'],
    ];
    
    $rolesAssigned = 0;
    
    foreach ($roleAssignments as $assignment) {
        try {
            // First, get the user ID and role ID
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$assignment[0]]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo "  ⚠ User not found: {$assignment[0]}\n";
                continue;
            }
            
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
            $stmt->execute([$assignment[1]]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$role) {
                echo "  ⚠ Role not found: {$assignment[1]}\n";
                continue;
            }
            
            // Check if assignment already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?");
            $stmt->execute([$user['id'], $role['id']]);
            $exists = $stmt->fetchColumn() > 0;
            
            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?, ?, datetime('now'))");
                $stmt->execute([$user['id'], $role['id']]);
                echo "  ✓ Assigned role '{$assignment[1]}' to {$assignment[0]}\n";
                $rolesAssigned++;
            } else {
                echo "  ⊘ Role '{$assignment[1]}' already assigned to {$assignment[0]}\n";
            }
        } catch (PDOException $e) {
            echo "  ✗ Error assigning role to {$assignment[0]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✓ Roles assigned\n\n";
    
    // Show final count
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $activeCount = $stmt->fetchColumn();
    
    echo "✅ Re-seeding complete!\n";
    echo "   - Users inserted: $inserted\n";
    echo "   - Users skipped (already exist): $skipped\n";
    echo "   - Active users: $activeCount\n";
    echo "\nAll test users are now available on the login page.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

