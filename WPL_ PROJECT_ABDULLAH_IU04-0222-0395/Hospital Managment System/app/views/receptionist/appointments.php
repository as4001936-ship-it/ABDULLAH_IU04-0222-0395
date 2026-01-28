<?php
require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole(['receptionist', 'admin']);

$pageTitle = 'Appointments';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_appointment') {
    $patient_id = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
    $doctor_id = isset($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : null;
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

    if ($patient_id <= 0) $errors[] = 'Please select a patient.';
    if ($appointment_date === '') $errors[] = 'Please select a date.';
    if ($appointment_time === '') $errors[] = 'Please select a time.';

    if (empty($errors)) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :reason, :status)');
                $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':doctor_id' => $doctor_id > 0 ? $doctor_id : null,
                    ':appointment_date' => $appointment_date,
                    ':appointment_time' => $appointment_time,
                    ':reason' => $reason,
                    ':status' => 'scheduled'
                ]);
                $success = 'Appointment created successfully.';
            } catch (Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Database connection unavailable.';
        }
    }
}

$patients = [];
$doctors = [];
$appointments = [];
$pdo = getDBConnection();
if ($pdo) {
    try {
        $patients = $pdo->query('SELECT id, full_name, email FROM users ORDER BY full_name')->fetchAll();
    } catch (Exception $e) {}

    try {
        // Try to get users with role 'doctor' (if roles exist)
        $doctors = $pdo->query("SELECT u.id, u.full_name FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id AND r.name = 'doctor'")->fetchAll();
    } catch (Exception $e) {
        // Fallback: empty or all users
        $doctors = [];
    }

    try {
        $appointments = $pdo->query("SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name
            FROM appointments a
            LEFT JOIN users p ON a.patient_id = p.id
            LEFT JOIN users d ON a.doctor_id = d.id
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 50")->fetchAll();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../../../app/includes/view_head.php'; ?>
    <style>
        .card { margin-bottom: 16px; }
        table { width:100%; border-collapse: collapse; }
        table th, table td { padding:8px; border:1px solid #ddd; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    <?php include __DIR__ . '/../../../app/includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header"><h1><?php echo $pageTitle; ?></h1></div>

        <div class="card">
            <h2>Create Appointment</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $e) { echo '<li>'.htmlspecialchars($e).'</li>'; } ?></ul></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="create_appointment">
                <div>
                    <label>Patient</label><br>
                    <select name="patient_id" required>
                        <option value="">-- Select patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name'] ?: $p['email']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-top:8px;">
                    <label>Doctor (optional)</label><br>
                    <select name="doctor_id">
                        <option value="">-- None / Any --</option>
                        <?php if (!empty($doctors)): foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                        <?php endforeach; else: ?>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name'] ?: $p['email']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="margin-top:8px;">
                    <label>Date</label><br>
                    <input type="date" name="appointment_date" required>
                </div>

                <div style="margin-top:8px;">
                    <label>Time</label><br>
                    <input type="time" name="appointment_time" required>
                </div>

                <div style="margin-top:8px;">
                    <label>Reason (optional)</label><br>
                    <input type="text" name="reason">
                </div>

                <div style="margin-top:12px;"><button type="submit">Create Appointment</button></div>
            </form>
        </div>

        <div class="card">
            <h2>Upcoming Appointments</h2>
            <?php if (empty($appointments)): ?>
                <p>No appointments found.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($a['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($a['patient_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($a['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($a['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

