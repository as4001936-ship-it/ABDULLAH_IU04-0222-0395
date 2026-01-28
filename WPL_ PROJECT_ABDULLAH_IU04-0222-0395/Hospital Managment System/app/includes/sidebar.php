<?php
/**
 * Sidebar Navigation Component
 * Role-based menu items
 */

require_once __DIR__ . '/../auth/auth_guard.php';

if (!isAuthenticated()) {
    return;
}

// Ensure helpers are loaded
if (!function_exists('url')) {
    require_once __DIR__ . '/helpers.php';
}

/**
 * Get Font Awesome icon class for menu item
 * @param string $iconName
 * @return string
 */
function getMenuIcon($iconName) {
    $iconMap = [
        'dashboard' => 'fa-solid fa-gauge-high',              // Dashboard - speedometer/gauge
        'users' => 'fa-solid fa-users',                      // Users management
        'roles' => 'fa-solid fa-user-shield',                 // Roles & permissions
        'logs' => 'fa-solid fa-file-lines',                   // Audit logs
        'patients' => 'fa-solid fa-user-injured',             // Patients
        'appointments' => 'fa-solid fa-calendar-check',       // Appointments
        'billing' => 'fa-solid fa-money-bill-wave',          // Billing
        'prescriptions' => 'fa-solid fa-prescription-bottle-medical', // Prescriptions
        'queue' => 'fa-solid fa-clipboard-list',             // Lab queue - clipboard with list
        'reports' => 'fa-solid fa-file-waveform',            // Lab reports - medical file with waveform
        'inventory' => 'fa-solid fa-boxes-stacked',          // Inventory
    ];
    
    return $iconMap[$iconName] ?? 'fa-solid fa-circle';
}

$userRoles = getCurrentUserRoles();

// Define menu items based on roles
$menuItems = [];

// Admin menu
if (hasRole('admin')) {
    $menuItems[] = ['label' => 'Dashboard', 'url' => url('app/views/admin/dashboard.php'), 'icon' => 'dashboard'];
    $menuItems[] = ['label' => 'Users', 'url' => url('app/views/admin/users.php'), 'icon' => 'users'];
    $menuItems[] = ['label' => 'Roles', 'url' => url('app/views/admin/roles.php'), 'icon' => 'roles'];
    $menuItems[] = ['label' => 'Audit Logs', 'url' => url('app/views/admin/audit_logs.php'), 'icon' => 'logs'];
}

// Receptionist menu
if (hasRole('receptionist')) {
    $menuItems[] = ['label' => 'Dashboard', 'url' => url('app/views/receptionist/dashboard.php'), 'icon' => 'dashboard'];
    $menuItems[] = ['label' => 'Patients', 'url' => url('app/views/receptionist/patients.php'), 'icon' => 'patients'];
    $menuItems[] = ['label' => 'Appointments', 'url' => url('app/views/receptionist/appointments.php'), 'icon' => 'appointments'];
    $menuItems[] = ['label' => 'Billing', 'url' => url('app/views/receptionist/billing.php'), 'icon' => 'billing'];
}

// Doctor menu
if (hasRole('doctor')) {
    $menuItems[] = ['label' => 'Dashboard', 'url' => url('app/views/doctor/dashboard.php'), 'icon' => 'dashboard'];
    $menuItems[] = ['label' => 'Appointments', 'url' => url('app/views/doctor/appointments.php'), 'icon' => 'appointments'];
    $menuItems[] = ['label' => 'Patients', 'url' => url('app/views/doctor/patients.php'), 'icon' => 'patients'];
    $menuItems[] = ['label' => 'Prescriptions', 'url' => url('app/views/doctor/prescriptions.php'), 'icon' => 'prescriptions'];
}

// Lab Technician menu
if (hasRole('lab_technician')) {
    $menuItems[] = ['label' => 'Dashboard', 'url' => url('app/views/lab/dashboard.php'), 'icon' => 'dashboard'];
    $menuItems[] = ['label' => 'Lab Queue', 'url' => url('app/views/lab/queue.php'), 'icon' => 'queue'];
    $menuItems[] = ['label' => 'Reports', 'url' => url('app/views/lab/reports.php'), 'icon' => 'reports'];
}

// Pharmacist menu
if (hasRole('pharmacist')) {
    $menuItems[] = ['label' => 'Dashboard', 'url' => url('app/views/pharmacist/dashboard.php'), 'icon' => 'dashboard'];
    $menuItems[] = ['label' => 'Prescriptions', 'url' => url('app/views/pharmacist/prescriptions.php'), 'icon' => 'prescriptions'];
    $menuItems[] = ['label' => 'Inventory', 'url' => url('app/views/pharmacist/inventory.php'), 'icon' => 'inventory'];
}

// Patient menu
if (hasRole('patient')) {
    $menuItems[] = ['label' => 'Dashboard', 'url' => url('app/views/patient/dashboard.php'), 'icon' => 'dashboard'];
    $menuItems[] = ['label' => 'Appointments', 'url' => url('app/views/patient/appointments.php'), 'icon' => 'appointments'];
}

$currentPage = $_SERVER['REQUEST_URI'];
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <?php foreach ($menuItems as $item): ?>
                <?php
                $isActive = strpos($currentPage, $item['url']) !== false;
                $activeClass = $isActive ? 'active' : '';
                ?>
                <li class="nav-item <?php echo $activeClass; ?>">
                    <a href="<?php echo $item['url']; ?>" class="nav-link">
                        <i class="nav-icon <?php echo getMenuIcon($item['icon']); ?>"></i>
                        <span class="nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

