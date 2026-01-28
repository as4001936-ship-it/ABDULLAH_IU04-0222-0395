<?php
/**
 * Doctor Dashboard
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole(['doctor', 'admin']);

$pageTitle = 'Doctor Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../../../app/includes/view_head.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    <?php include __DIR__ . '/../../../app/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><?php echo $pageTitle; ?></h1>
        </div>
        
        <?php
        require_once __DIR__ . '/../../../app/config/database.php';
        $pdo = getDBConnection();
        $upcomingAppointments = [];
        $currentUserId = getCurrentUserId();
        
        if ($pdo) {
            try {
                // Get upcoming appointments (future dates)
                $todayDate = date('Y-m-d');
                $stmt = $pdo->prepare("
                    SELECT a.*, p.full_name as patient_name, p.email as patient_email
                    FROM appointments a
                    INNER JOIN users p ON a.patient_id = p.id
                    WHERE a.doctor_id = :doctor_id 
                      AND a.appointment_date >= :today_date
                      AND a.status IN ('scheduled', 'confirmed')
                    ORDER BY a.appointment_date ASC, a.appointment_time ASC
                    LIMIT 10
                ");
                $stmt->execute([':doctor_id' => $currentUserId, ':today_date' => $todayDate]);
                $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error loading appointments: " . $e->getMessage());
            }
        }
        ?>
        
        <div class="dashboard-content">
            <div class="card">
                <h2>Welcome, Dr. <?php echo htmlspecialchars($_SESSION['auth']['full_name']); ?>!</h2>
                <p>This is the doctor dashboard. Here you can view appointments, patient records, and manage prescriptions.</p>
            </div>
            
            <?php if ($pdo): ?>
            <div class="card">
                <h3>Upcoming Appointments</h3>
                <?php if (empty($upcomingAppointments)): ?>
                    <p>No upcoming appointments.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $apt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($apt['appointment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['appointment_time']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['reason'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $apt['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <div style="margin-top: 15px;">
                    <a href="<?php echo url('app/views/doctor/appointments.php'); ?>" class="btn btn-primary">
                        View All Appointments
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

