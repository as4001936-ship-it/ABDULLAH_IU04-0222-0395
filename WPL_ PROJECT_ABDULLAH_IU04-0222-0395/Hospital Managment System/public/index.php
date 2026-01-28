<?php
/**
 * Main Router - Redirects users to their role-based dashboard
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/auth/auth_guard.php';

requireAuth();

// Get user roles
$userRoles = getCurrentUserRoles();

// Redirect based on primary role (first role in the list)
// Admin gets priority
if (in_array('admin', $userRoles)) {
    header('Location: ' . url('app/views/admin/dashboard.php'));
} elseif (in_array('receptionist', $userRoles)) {
    header('Location: ' . url('app/views/receptionist/dashboard.php'));
} elseif (in_array('doctor', $userRoles)) {
    header('Location: ' . url('app/views/doctor/dashboard.php'));
} elseif (in_array('lab_technician', $userRoles)) {
    header('Location: ' . url('app/views/lab/dashboard.php'));
} elseif (in_array('pharmacist', $userRoles)) {
    header('Location: ' . url('app/views/pharmacist/dashboard.php'));
} elseif (in_array('patient', $userRoles)) {
    header('Location: ' . url('app/views/patient/dashboard.php'));
} else {
    // No valid role - show error
    $_SESSION['error_message'] = 'No valid role assigned. Contact administrator.';
    header('Location: ' . url('public/logout.php'));
}

exit;

