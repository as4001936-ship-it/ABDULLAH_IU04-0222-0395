<?php
/**
 * Mock Users Handler (DEV Mode)
 * Handles user authentication using JSON file instead of database
 */

require_once __DIR__ . '/../config/app.php';

/**
 * Load mock users from JSON file
 * @return array
 */
function loadMockUsers() {
    static $users = null;
    
    if ($users === null) {
        $filePath = MOCK_USERS_FILE;
        
        if (!file_exists($filePath)) {
            error_log("Mock users file not found: {$filePath}");
            return [];
        }
        
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error parsing mock_users.json: " . json_last_error_msg());
            return [];
        }
        
        $users = $data['users'] ?? [];
        
        // Add password_hash field (we'll verify against plain password in DEV mode)
        foreach ($users as &$user) {
            $user['password_hash'] = $user['password']; // In DEV, we compare plain text
        }
    }
    
    return $users;
}

/**
 * Save mock users back to JSON file
 * @param array $users
 * @return bool
 */
function saveMockUsers($users) {
    $filePath = MOCK_USERS_FILE;
    
    // Remove password_hash before saving (keep password field)
    $usersToSave = [];
    foreach ($users as $user) {
        $userToSave = $user;
        unset($userToSave['password_hash']);
        $usersToSave[] = $userToSave;
    }
    
    $data = ['users' => $usersToSave];
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    return file_put_contents($filePath, $jsonContent) !== false;
}

/**
 * Find user by email in mock users
 * @param string $email
 * @return array|null
 */
function findMockUserByEmail($email) {
    $users = loadMockUsers();
    
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Find user by ID in mock users
 * @param int $userId
 * @return array|null
 */
function findMockUserById($userId) {
    $users = loadMockUsers();
    
    foreach ($users as $user) {
        if ($user['id'] == $userId) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Update mock user (for failed attempts, status changes, etc.)
 * @param int $userId
 * @param array $updates
 * @return bool
 */
function updateMockUser($userId, $updates) {
    $users = loadMockUsers();
    
    foreach ($users as &$user) {
        if ($user['id'] == $userId) {
            foreach ($updates as $key => $value) {
                $user[$key] = $value;
            }
            // Preserve password_hash for current session
            if (isset($user['password'])) {
                $user['password_hash'] = $user['password'];
            }
            return saveMockUsers($users);
        }
    }
    
    return false;
}

