<?php
/**
 * Audit Logging Functions
 */

require_once __DIR__ . '/../config/app.php';

/**
 * Log an audit action
 * @param string $action - Action name (e.g., LOGIN_SUCCESS, LOGIN_FAIL, LOGOUT, ACCESS_DENIED)
 * @param array $metadata - Additional metadata to store
 * @param int|null $userId - User ID (null if unknown/anonymous)
 */
function logAuditAction($action, $metadata = [], $userId = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();
        
        if (!$pdo) {
            // Database not available, fallback to error_log
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            if ($userId === null && isset($_SESSION['auth']['user_id'])) {
                $userId = $_SESSION['auth']['user_id'];
            }
            
            $logMessage = sprintf(
                "[AUDIT] %s | User: %s | IP: %s | %s",
                $action,
                $userId ?? 'anonymous',
                $ipAddress ?? 'unknown',
                json_encode($metadata)
            );
            
            error_log($logMessage);
            return;
        }
        
        // Get IP address and user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Use user_id from session if not provided
        if ($userId === null && isset($_SESSION['auth']['user_id'])) {
            $userId = $_SESSION['auth']['user_id'];
        }
        
        // Convert metadata array to JSON
        $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, metadata, ip_address, user_agent, created_at)
            VALUES (:user_id, :action, :metadata, :ip_address, :user_agent, datetime('now'))
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':metadata' => $metadataJson,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent
        ]);
    } catch (PDOException $e) {
        // Log error but don't break the application
        error_log("Failed to write audit log: " . $e->getMessage());
    }
}

