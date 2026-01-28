<?php
/**
 * Patient - Appointments Management (CRUD)
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';
require_once __DIR__ . '/../../../app/auth/audit_log.php';

requireRole('patient');

$pageTitle = 'My Appointments';

require_once __DIR__ . '/../../../app/config/database.php';
$pdo = getDBConnection();
$message = '';
$messageType = '';

$currentUserId = getCurrentUserId();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Create new appointment
        $appointmentDate = trim($_POST['appointment_date'] ?? '');
        $appointmentTime = trim($_POST['appointment_time'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $doctorId = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
        
        if (empty($appointmentDate) || empty($appointmentTime) || empty($doctorId)) {
            $message = 'Please fill in date, time, and select a doctor';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_at, updated_at)
                    VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :reason, 'scheduled', datetime('now'), datetime('now'))
                ");
                $stmt->execute([
                    ':patient_id' => $currentUserId,
                    ':doctor_id' => $doctorId,
                    ':appointment_date' => $appointmentDate,
                    ':appointment_time' => $appointmentTime,
                    ':reason' => $reason ?: null
                ]);
                
                logAuditAction('APPOINTMENT_CREATED', [
                    'user_id' => $currentUserId,
                    'appointment_id' => $pdo->lastInsertId(),
                    'date' => $appointmentDate,
                    'time' => $appointmentTime
                ]);
                
                $message = 'Appointment booked successfully';
                $messageType = 'success';
            } catch (PDOException $e) {
                error_log("Error creating appointment: " . $e->getMessage());
                $message = 'Error booking appointment: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update') {
        // Update appointment
        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $appointmentDate = trim($_POST['appointment_date'] ?? '');
        $appointmentTime = trim($_POST['appointment_time'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $doctorId = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
        
        if (!$appointmentId || empty($appointmentDate) || empty($appointmentTime) || empty($doctorId)) {
            $message = 'Please fill in date, time, and select a doctor';
            $messageType = 'error';
        } else {
            try {
                // Verify appointment belongs to current user
                $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = :id AND patient_id = :patient_id");
                $stmt->execute([':id' => $appointmentId, ':patient_id' => $currentUserId]);
                if (!$stmt->fetch()) {
                    $message = 'Appointment not found or access denied';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE appointments 
                        SET appointment_date = :appointment_date, 
                            appointment_time = :appointment_time,
                            reason = :reason,
                            doctor_id = :doctor_id,
                            updated_at = datetime('now')
                        WHERE id = :id AND patient_id = :patient_id
                    ");
                    $stmt->execute([
                        ':appointment_date' => $appointmentDate,
                        ':appointment_time' => $appointmentTime,
                        ':reason' => $reason ?: null,
                        ':doctor_id' => $doctorId,
                        ':id' => $appointmentId,
                        ':patient_id' => $currentUserId
                    ]);
                    
                    logAuditAction('APPOINTMENT_UPDATED', [
                        'user_id' => $currentUserId,
                        'appointment_id' => $appointmentId
                    ]);
                    
                    $message = 'Appointment updated successfully';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log("Error updating appointment: " . $e->getMessage());
                $message = 'Error updating appointment: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'cancel') {
        // Cancel appointment
        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        
        if (!$appointmentId) {
            $message = 'Invalid appointment ID';
            $messageType = 'error';
        } else {
            try {
                // Verify appointment belongs to current user
                $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = :id AND patient_id = :patient_id");
                $stmt->execute([':id' => $appointmentId, ':patient_id' => $currentUserId]);
                if (!$stmt->fetch()) {
                    $message = 'Appointment not found or access denied';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE appointments 
                        SET status = 'cancelled', updated_at = datetime('now')
                        WHERE id = :id AND patient_id = :patient_id
                    ");
                    $stmt->execute([':id' => $appointmentId, ':patient_id' => $currentUserId]);
                    
                    logAuditAction('APPOINTMENT_CANCELLED', [
                        'user_id' => $currentUserId,
                        'appointment_id' => $appointmentId
                    ]);
                    
                    $message = 'Appointment cancelled successfully';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log("Error cancelling appointment: " . $e->getMessage());
                $message = 'Error cancelling appointment: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        // Delete appointment (only if cancelled or in future)
        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        
        if (!$appointmentId) {
            $message = 'Invalid appointment ID';
            $messageType = 'error';
        } else {
            try {
                // Verify appointment belongs to current user and can be deleted
                $stmt = $pdo->prepare("
                    SELECT id, status, appointment_date, appointment_time 
                    FROM appointments 
                    WHERE id = :id AND patient_id = :patient_id
                ");
                $stmt->execute([':id' => $appointmentId, ':patient_id' => $currentUserId]);
                $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$appointment) {
                    $message = 'Appointment not found or access denied';
                    $messageType = 'error';
                } else {
                    // Check if appointment is in the past and completed
                    $appointmentDateTime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
                    if ($appointment['status'] === 'completed' || strtotime($appointmentDateTime) < time()) {
                        $message = 'Cannot delete completed or past appointments';
                        $messageType = 'error';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id AND patient_id = :patient_id");
                        $stmt->execute([':id' => $appointmentId, ':patient_id' => $currentUserId]);
                        
                        logAuditAction('APPOINTMENT_DELETED', [
                            'user_id' => $currentUserId,
                            'appointment_id' => $appointmentId
                        ]);
                        
                        $message = 'Appointment deleted successfully';
                        $messageType = 'success';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error deleting appointment: " . $e->getMessage());
                $message = 'Error deleting appointment: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get all appointments for current patient
$appointments = [];
$doctors = [];

if ($pdo) {
    // Get available doctors first (separate try-catch so it always runs)
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.email
            FROM users u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE r.name = 'doctor' AND u.status = 'active'
            ORDER BY u.full_name
        ");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading doctors: " . $e->getMessage());
        $doctors = [];
    }
    
    // Get appointments (separate try-catch)
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   d.full_name as doctor_name,
                   d.email as doctor_email
            FROM appointments a
            LEFT JOIN users d ON a.doctor_id = d.id
            WHERE a.patient_id = :patient_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([':patient_id' => $currentUserId]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading appointments: " . $e->getMessage());
        $appointments = [];
    }
}
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
            <?php if ($pdo && !empty($doctors)): ?>
                <button class="btn btn-primary" onclick="document.getElementById('createAppointmentModal').style.display='block'">
                    Book New Appointment
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (!$pdo): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Database Not Available:</strong> Appointments require database access.
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($pdo): ?>
        <?php if (empty($doctors)): ?>
            <div class="alert alert-warning">
                <strong>⚠️ No Doctors Available:</strong> There are no active doctors in the system. Please contact the administrator.
            </div>
        <?php endif; ?>
        
        <div class="card">
            <?php if (empty($appointments)): ?>
                <p>No appointments found. <?php echo !empty($doctors) ? 'Book your first appointment using the button above.' : 'Please wait for doctors to be added to the system.'; ?></p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apt['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($apt['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars($apt['doctor_name'] ?? 'Not assigned'); ?></td>
                                <td><?php echo htmlspecialchars($apt['reason'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if ($apt['status'] !== 'completed' && $apt['status'] !== 'cancelled'): ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($apt)); ?>)">
                                                Edit
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($apt['status'] !== 'cancelled' && $apt['status'] !== 'completed'): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $appointmentDateTime = $apt['appointment_date'] . ' ' . $apt['appointment_time'];
                                        $canDelete = ($apt['status'] !== 'completed' && strtotime($appointmentDateTime) >= time());
                                        ?>
                                        <?php if ($canDelete): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this appointment? This action cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Create Appointment Modal -->
    <div id="createAppointmentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Book New Appointment</h2>
                <span class="close" onclick="document.getElementById('createAppointmentModal').style.display='none'">&times;</span>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="create_appointment_date">Date *</label>
                    <input type="date" id="create_appointment_date" name="appointment_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="create_appointment_time">Time *</label>
                    <input type="time" id="create_appointment_time" name="appointment_time" required>
                </div>
                
                <div class="form-group">
                    <label for="create_doctor_id">Doctor *</label>
                    <select id="create_doctor_id" name="doctor_id" required>
                        <option value="">Select a doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="create_reason">Reason</label>
                    <textarea id="create_reason" name="reason" rows="3" 
                              placeholder="Brief reason for the appointment"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('createAppointmentModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Book Appointment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Appointment Modal -->
    <div id="editAppointmentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Appointment</h2>
                <span class="close" onclick="document.getElementById('editAppointmentModal').style.display='none'">&times;</span>
            </div>
            <form method="POST" class="modal-body" id="editAppointmentForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="appointment_id" id="edit_appointment_id">
                
                <div class="form-group">
                    <label for="edit_appointment_date">Date *</label>
                    <input type="date" id="edit_appointment_date" name="appointment_date" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_appointment_time">Time *</label>
                    <input type="time" id="edit_appointment_time" name="appointment_time" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_doctor_id">Doctor *</label>
                    <select id="edit_doctor_id" name="doctor_id" required>
                        <option value="">Select a doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_reason">Reason</label>
                    <textarea id="edit_reason" name="reason" rows="3" 
                              placeholder="Brief reason for the appointment"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('editAppointmentModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Appointment</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(appointment) {
            document.getElementById('edit_appointment_id').value = appointment.id;
            document.getElementById('edit_appointment_date').value = appointment.appointment_date;
            document.getElementById('edit_appointment_time').value = appointment.appointment_time;
            document.getElementById('edit_doctor_id').value = appointment.doctor_id || '';
            document.getElementById('edit_reason').value = appointment.reason || '';
            
            document.getElementById('editAppointmentModal').style.display = 'block';
        }
    </script>
</body>
</html>

