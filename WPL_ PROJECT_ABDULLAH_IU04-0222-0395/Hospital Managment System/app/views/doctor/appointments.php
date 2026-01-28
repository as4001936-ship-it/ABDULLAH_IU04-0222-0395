<?php
/**
 * Doctor - Appointments Management
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';
require_once __DIR__ . '/../../../app/auth/audit_log.php';

requireRole(['doctor', 'admin']);

$pageTitle = 'My Appointments';

require_once __DIR__ . '/../../../app/config/database.php';
$pdo = getDBConnection();
$message = '';
$messageType = '';

$currentUserId = getCurrentUserId();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        // Update appointment status
        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        
        if (!$appointmentId || !in_array($newStatus, ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])) {
            $message = 'Invalid status update';
            $messageType = 'error';
        } else {
            try {
                // Verify appointment belongs to current doctor
                $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = :id AND doctor_id = :doctor_id");
                $stmt->execute([':id' => $appointmentId, ':doctor_id' => $currentUserId]);
                if (!$stmt->fetch()) {
                    $message = 'Appointment not found or access denied';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE appointments 
                        SET status = :status, updated_at = datetime('now')
                        WHERE id = :id AND doctor_id = :doctor_id
                    ");
                    $stmt->execute([':status' => $newStatus, ':id' => $appointmentId, ':doctor_id' => $currentUserId]);
                    
                    logAuditAction('APPOINTMENT_STATUS_UPDATED', [
                        'user_id' => $currentUserId,
                        'appointment_id' => $appointmentId,
                        'new_status' => $newStatus
                    ]);
                    
                    $message = 'Appointment status updated successfully';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log("Error updating appointment status: " . $e->getMessage());
                $message = 'Error updating appointment status';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'add_notes') {
        // Add/update appointment notes
        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if (!$appointmentId) {
            $message = 'Invalid appointment ID';
            $messageType = 'error';
        } else {
            try {
                // Verify appointment belongs to current doctor
                $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = :id AND doctor_id = :doctor_id");
                $stmt->execute([':id' => $appointmentId, ':doctor_id' => $currentUserId]);
                if (!$stmt->fetch()) {
                    $message = 'Appointment not found or access denied';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE appointments 
                        SET notes = :notes, updated_at = datetime('now')
                        WHERE id = :id AND doctor_id = :doctor_id
                    ");
                    $stmt->execute([':notes' => $notes ?: null, ':id' => $appointmentId, ':doctor_id' => $currentUserId]);
                    
                    logAuditAction('APPOINTMENT_NOTES_UPDATED', [
                        'user_id' => $currentUserId,
                        'appointment_id' => $appointmentId
                    ]);
                    
                    $message = 'Notes updated successfully';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log("Error updating notes: " . $e->getMessage());
                $message = 'Error updating notes';
                $messageType = 'error';
            }
        }
    }
}

// Get all appointments for current doctor
$appointments = [];
$upcomingCount = 0;
$todayCount = 0;

if ($pdo) {
    try {
        // Get appointments assigned to this doctor
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   p.full_name as patient_name,
                   p.email as patient_email,
                   p.phone as patient_phone
            FROM appointments a
            INNER JOIN users p ON a.patient_id = p.id
            WHERE a.doctor_id = :doctor_id
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        $stmt->execute([':doctor_id' => $currentUserId]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count upcoming and today's appointments
        $today = date('Y-m-d');
        foreach ($appointments as $apt) {
            if ($apt['status'] !== 'completed' && $apt['status'] !== 'cancelled') {
                if ($apt['appointment_date'] >= $today) {
                    $upcomingCount++;
                }
                if ($apt['appointment_date'] === $today) {
                    $todayCount++;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error loading appointments: " . $e->getMessage());
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
        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="card" style="text-align: center;">
                <h3 style="margin: 0; color: #4CAF50; font-size: 2em;"><?php echo count($appointments); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;">Total Appointments</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3 style="margin: 0; color: #2196F3; font-size: 2em;"><?php echo $upcomingCount; ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;">Upcoming</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3 style="margin: 0; color: #FF9800; font-size: 2em;"><?php echo $todayCount; ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;">Today</p>
            </div>
        </div>
        
        <div class="card">
            <?php if (empty($appointments)): ?>
                <p>No appointments scheduled. Patients can book appointments and they will appear here.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apt['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($apt['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($apt['patient_email']); ?></div>
                                    <?php if ($apt['patient_phone']): ?>
                                        <div style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($apt['patient_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($apt['reason'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($apt['notes']): ?>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                             title="<?php echo htmlspecialchars($apt['notes']); ?>">
                                            <?php echo htmlspecialchars($apt['notes']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999;">No notes</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="status-select">
                                                <option value="scheduled" <?php echo $apt['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                <option value="confirmed" <?php echo $apt['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="completed" <?php echo $apt['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $apt['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="no_show" <?php echo $apt['status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                            </select>
                                        </form>
                                        
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="openNotesModal(<?php echo htmlspecialchars(json_encode($apt)); ?>)">
                                            Notes
                                        </button>
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
    
    <!-- Notes Modal -->
    <div id="notesModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Appointment Notes</h2>
                <span class="close" onclick="document.getElementById('notesModal').style.display='none'">&times;</span>
            </div>
            <form method="POST" class="modal-body" id="notesForm">
                <input type="hidden" name="action" value="add_notes">
                <input type="hidden" name="appointment_id" id="notes_appointment_id">
                
                <div class="form-group">
                    <label>Patient:</label>
                    <div id="notes_patient_name" style="font-weight: bold; margin-bottom: 10px;"></div>
                </div>
                
                <div class="form-group">
                    <label>Date & Time:</label>
                    <div id="notes_appointment_datetime" style="margin-bottom: 10px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="notes_text">Notes</label>
                    <textarea id="notes_text" name="notes" rows="6" 
                              placeholder="Add clinical notes, observations, or follow-up instructions..."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('notesModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Notes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openNotesModal(appointment) {
            document.getElementById('notes_appointment_id').value = appointment.id;
            document.getElementById('notes_patient_name').textContent = appointment.patient_name;
            document.getElementById('notes_appointment_datetime').textContent = 
                appointment.appointment_date + ' at ' + appointment.appointment_time;
            document.getElementById('notes_text').value = appointment.notes || '';
            
            document.getElementById('notesModal').style.display = 'block';
        }
    </script>
</body>
</html>
