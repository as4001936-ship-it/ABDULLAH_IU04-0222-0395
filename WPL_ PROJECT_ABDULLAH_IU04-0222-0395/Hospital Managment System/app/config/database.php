<?php
/**
 * Database Configuration
 * 
 * Uses SQLite (no installation needed - built into PHP!)
 * Database file is automatically created on first use.
 */

// SQLite Database Path
define('DB_PATH', __DIR__ . '/../../database/hospital.db');

/**
 * Get database connection
 * @return PDO|null Returns null if connection fails in DEV mode, throws in PROD mode
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // SQLite connection (file-based, no installation needed)
            $dbPath = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../../database/hospital.db';
            
            // Normalize the path - resolve relative paths and normalize separators
            if (!file_exists($dbPath) && strpos($dbPath, '..') !== false) {
                // Resolve relative path
                $dbPath = realpath(dirname($dbPath)) . DIRECTORY_SEPARATOR . basename($dbPath);
            }
            
            // Normalize path separators
            $dbPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dbPath);
            
            // Get the directory path
            $dbDir = dirname($dbPath);
            
            // Ensure absolute path
            if (!file_exists($dbDir)) {
                // Try to resolve it
                $resolved = realpath(dirname(__DIR__) . '/../database');
                if ($resolved) {
                    $dbDir = $resolved;
                    $dbPath = $dbDir . DIRECTORY_SEPARATOR . 'hospital.db';
                }
            } else {
                $dbDir = realpath($dbDir);
                $dbPath = $dbDir . DIRECTORY_SEPARATOR . basename($dbPath);
            }
            if (!is_dir($dbDir)) {
                if (!mkdir($dbDir, 0755, true)) {
                    $error = "Failed to create database directory: $dbDir";
                    error_log("Database error: $error");
                    return null;
                }
            }
            
            // Ensure directory is writable
            if (!is_writable($dbDir)) {
                $error = "Database directory is not writable: $dbDir";
                error_log("Database error: $error");
                return null;
            }
            
            $dsn = "sqlite:" . $dbPath;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            
            // Create PDO connection (SQLite will use existing file or create new one)
            try {
                $pdo = new PDO($dsn, null, null, $options);
            } catch (PDOException $e) {
                $error = "Failed to create PDO connection: " . $e->getMessage();
                error_log("Database error: $error");
                error_log("DSN used: $dsn");
                return null;
            }
            
            // Verify the database file exists (should exist now)
            if (!file_exists($dbPath)) {
                $error = "Database file was not found at: $dbPath";
                error_log("Database error: $error");
                return null;
            }
            
            // Enable foreign keys for SQLite
            try {
                $pdo->exec("PRAGMA foreign_keys = ON");
            } catch (PDOException $e) {
                $error = "Failed to enable foreign keys: " . $e->getMessage();
                error_log("Database warning: $error");
                // Continue anyway - not critical
            }
            
            // Check if database is new (no tables exist)
            $isNewDatabase = false;
            try {
                $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                $hasUsersTable = $tableCheck && $tableCheck->fetch() !== false;
                $isNewDatabase = !$hasUsersTable;
            } catch (PDOException $e) {
                // If query fails, assume it's a new database
                error_log("Table check failed (assuming new database): " . $e->getMessage());
                $isNewDatabase = true;
            }
            
            // Auto-setup database if it's new
            if ($isNewDatabase) {
                error_log("New database detected, running auto-setup...");
                try {
                    $setupResult = autoSetupDatabase($pdo);
                    
                    if ($setupResult === false) {
                        error_log("ERROR: Database auto-setup returned false");
                        // Continue anyway - connection is valid even if setup failed
                    } else {
                        // Verify setup worked
                        try {
                            $verifyCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                            if ($verifyCheck && $verifyCheck->fetch() !== false) {
                                error_log("Database auto-setup completed successfully");
                            } else {
                                error_log("WARNING: Database auto-setup may have failed - users table still not found");
                            }
                        } catch (PDOException $e) {
                            error_log("WARNING: Could not verify auto-setup: " . $e->getMessage());
                        }
                    }
                } catch (Exception $e) {
                    error_log("ERROR: Exception during auto-setup: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    // Continue anyway - connection is valid
                }
            }
        } catch (PDOException $e) {
            $error = "Database connection failed: " . $e->getMessage();
            error_log($error);
            error_log("Database path attempted: " . (defined('DB_PATH') ? DB_PATH : 'default'));
            error_log("Resolved path: " . (isset($dbPath) ? $dbPath : 'not set'));
            error_log("File exists: " . (isset($dbPath) && file_exists($dbPath) ? 'yes' : 'no'));
            return null;
        } catch (Exception $e) {
            $error = "Unexpected database error: " . $e->getMessage();
            error_log($error);
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * Auto-setup database (creates tables and seeds data)
 * Called automatically when database is first created
 * @param PDO $pdo
 */
function autoSetupDatabase($pdo) {
    try {
        // Read and execute schema
        $schemaFile = __DIR__ . '/../../database/schema_sqlite.sql';
        if (!file_exists($schemaFile)) {
            $error = "Schema file not found: $schemaFile";
            error_log("Auto-setup error: $error");
            return false;
        }
        
        $schema = file_get_contents($schemaFile);
        if ($schema === false) {
            $error = "Failed to read schema file: $schemaFile";
            error_log("Auto-setup error: $error");
            return false;
        }
        
        // Remove comments and clean up the schema
        $lines = explode("\n", $schema);
        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip empty lines and full-line comments
            if (empty($line) || preg_match('/^--/', $line)) {
                continue;
            }
            // Remove inline comments (-- at end of line)
            if (strpos($line, '--') !== false) {
                $line = substr($line, 0, strpos($line, '--'));
                $line = trim($line);
            }
            if (!empty($line)) {
                $cleanLines[] = $line;
            }
        }
        
        // Rejoin and split by semicolon
        $cleanSchema = implode(' ', $cleanLines);
        $statements = array_filter(
            array_map('trim', explode(';', $cleanSchema)),
            function($stmt) {
                return !empty($stmt);
            }
        );
        
        $schemaErrors = [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Log but continue - some statements might fail if already exists
                    $schemaErrors[] = $e->getMessage();
                    error_log("Schema statement warning: " . $e->getMessage());
                }
            }
        }
        
        // Seed roles and users
        $seedFile = __DIR__ . '/../../database/seed_sqlite.sql';
        if (!file_exists($seedFile)) {
            $error = "Seed file not found: $seedFile";
            error_log("Auto-setup error: $error");
            return false;
        }
        
        $seed = file_get_contents($seedFile);
        if ($seed === false) {
            $error = "Failed to read seed file: $seedFile";
            error_log("Auto-setup error: $error");
            return false;
        }
        
        $seedStatements = array_filter(
            array_map('trim', explode(';', $seed)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        $seedErrors = [];
        foreach ($seedStatements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Log but continue - some statements might fail if already exists
                    $seedErrors[] = $e->getMessage();
                    error_log("Seed statement warning: " . $e->getMessage());
                }
            }
        }
        
        error_log("Database auto-setup completed successfully");
        return true;
    } catch (Exception $e) {
        $error = "Auto-setup failed: " . $e->getMessage();
        error_log($error);
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

