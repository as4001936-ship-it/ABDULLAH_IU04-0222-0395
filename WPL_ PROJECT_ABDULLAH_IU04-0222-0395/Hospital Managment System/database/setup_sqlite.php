<?php
/**
 * SQLite Database Setup Script
 * 
 * This script creates the SQLite database and sets up all tables
 * No MySQL installation needed!
 * 
 * Usage: php database/setup_sqlite.php
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';

echo "Setting up SQLite database...\n\n";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        die("Failed to connect to database. Check your configuration.\n");
    }
    
    echo "✓ Connected to SQLite database\n";
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/schema_sqlite.sql';
    if (!file_exists($schemaFile)) {
        die("Schema file not found: $schemaFile\n");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // Execute the entire schema - split by semicolon but handle multi-line statements
    // Remove comments first
    $schema = preg_replace('/--.*$/m', '', $schema);
    
    // Split by semicolon, but be careful with multi-line CREATE TABLE statements
    $statements = [];
    $currentStatement = '';
    
    $lines = explode("\n", $schema);
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Skip empty lines
        if (empty($trimmedLine)) {
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // If line ends with semicolon, we have a complete statement
        if (substr(rtrim($line), -1) === ';') {
            $stmt = trim($currentStatement);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $currentStatement = '';
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Log but continue - some statements might fail if already exists
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'already exists') === false && 
                    strpos($errorMsg, 'duplicate column name') === false &&
                    strpos($errorMsg, 'duplicate index') === false) {
                    echo "⚠️  Warning: " . $errorMsg . "\n";
                }
            }
        }
    }
    
    echo "✓ Database schema created\n";
    
    // Seed roles
    $seedFile = __DIR__ . '/seed_sqlite.sql';
    if (file_exists($seedFile)) {
        $seed = file_get_contents($seedFile);
        
        $seedStatements = array_filter(
            array_map('trim', explode(';', $seed)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        foreach ($seedStatements as $statement) {
            if (!empty(trim($statement))) {
                $pdo->exec($statement);
            }
        }
        
        echo "✓ Roles and users seeded\n";
    }
    
    echo "\n✅ Database setup complete!\n";
    echo "Database file location: " . DB_PATH . "\n";
    echo "\nAll test users have been created with their roles assigned.\n";
    echo "You can now use the system!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

