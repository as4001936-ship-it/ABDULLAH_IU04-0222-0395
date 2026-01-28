<?php
/**
 * Initialize Database
 * Creates the database file and runs setup
 * Run this once: php database/init_db.php
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';

echo "Initializing database...\n";
echo "Database Path: " . DB_PATH . "\n\n";

// Get the directory
$dbDir = dirname(DB_PATH);
$dbFile = DB_PATH;

// Create directory if it doesn't exist
if (!is_dir($dbDir)) {
    echo "Creating database directory...\n";
    if (!mkdir($dbDir, 0755, true)) {
        die("ERROR: Failed to create database directory: $dbDir\n");
    }
    echo "✅ Directory created\n";
} else {
    echo "✅ Directory exists\n";
}

// Check if database file already exists
if (file_exists($dbFile)) {
    echo "⚠️  Database file already exists: $dbFile\n";
    echo "Keeping existing database file and checking if setup is needed...\n";
}

// Create empty database file if it doesn't exist
if (!file_exists($dbFile)) {
    echo "Creating database file...\n";
    $handle = fopen($dbFile, 'w');
    if ($handle === false) {
        die("ERROR: Failed to create database file: $dbFile\n");
    }
    fclose($handle);
    echo "✅ Database file created\n";
} else {
    echo "✅ Database file exists\n";
}

// Now try to connect and run setup
echo "\nConnecting to database...\n";
$pdo = getDBConnection();

if ($pdo) {
    echo "✅ Database connection successful!\n\n";
    
    // Check if tables exist
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "Database is empty, running auto-setup...\n";
        require_once __DIR__ . '/../app/config/database.php';
        $result = autoSetupDatabase($pdo);
        
        if ($result) {
            echo "✅ Database setup completed successfully!\n";
            
            // Verify
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            echo "Tables created: " . implode(', ', $tables) . "\n";
            
            if (in_array('users', $tables)) {
                $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                echo "Users seeded: $userCount\n";
            }
        } else {
            echo "❌ Database setup failed. Check error logs.\n";
        }
    } else {
        echo "Database already has tables: " . implode(', ', $tables) . "\n";
        echo "Skipping setup.\n";
    }
} else {
    echo "❌ Database connection failed!\n";
    echo "Check PHP error logs for details.\n";
    exit(1);
}

echo "\n✅ Database initialization complete!\n";

