<?php
/**
 * Receptionist - Billing (Placeholder)
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole(['receptionist', 'admin']);

$pageTitle = 'Billing';

// Simple server-side handling for creating a bill
$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_bill') {
    $patient_id = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
    $descriptions = isset($_POST['desc']) && is_array($_POST['desc']) ? $_POST['desc'] : [];
    $amounts = isset($_POST['amount']) && is_array($_POST['amount']) ? $_POST['amount'] : [];

    if ($patient_id <= 0) {
        $errors[] = 'Please select a patient.';
    }

    $items = [];
    $total = 0.0;
    for ($i = 0; $i < count($descriptions); $i++) {
        $d = trim($descriptions[$i]);
        $a = isset($amounts[$i]) ? floatval(str_replace(',', '', $amounts[$i])) : 0;
        if ($d === '' || $a <= 0) continue;
        $items[] = ['description' => $d, 'amount' => $a];
        $total += $a;
    }

    if (empty($items)) {
        $errors[] = 'Please add at least one billed item with a positive amount.';
    }

    if (empty($errors)) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                // Ensure bills table exists
                $pdo->exec("CREATE TABLE IF NOT EXISTS bills (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    patient_id INTEGER NOT NULL,
                    total REAL NOT NULL,
                    items_json TEXT NOT NULL,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP
                )");

                $stmt = $pdo->prepare('INSERT INTO bills (patient_id, total, items_json) VALUES (:patient_id, :total, :items_json)');
                $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':total' => $total,
                    ':items_json' => json_encode($items)
                ]);
                $success = 'Invoice created successfully.';
            } catch (Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Database connection unavailable.';
        }
    }
}

// Fetch patients list (basic: all users) and recent bills
$patients = [];
$recentBills = [];
$pdo = getDBConnection();
if ($pdo) {
    try {
        $patients = $pdo->query('SELECT id, full_name, email FROM users ORDER BY full_name ASC')->fetchAll();
    } catch (Exception $e) {
        // ignore - show empty list
    }

    try {
        $recentBills = $pdo->query('SELECT * FROM bills ORDER BY created_at DESC LIMIT 20')->fetchAll();
    } catch (Exception $e) {
        // ignore - no bills yet
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
    <style>
        .billing-items { width:100%; }
        .billing-items input[type="text"], .billing-items input[type="number"]{ width:95%; }
        .flex-row { display:flex; gap:8px; align-items:center; }
        .right { text-align:right; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    <?php include __DIR__ . '/../../../app/includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header"><h1><?php echo $pageTitle; ?></h1></div>

        <div class="card">
            <h2>Create Invoice</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="post" id="billing-form">
                <input type="hidden" name="action" value="create_bill">
                <div>
                    <label>Patient</label><br>
                    <select name="patient_id" required>
                        <option value="">-- Select patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name'] ?: $p['email']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-top:12px;">
                    <label>Items</label>
                    <table class="billing-items" id="items-table">
                        <thead>
                            <tr><th>Description</th><th class="right">Amount</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="text" name="desc[]" required></td>
                                <td class="right"><input type="number" step="0.01" name="amount[]" required></td>
                                <td><button type="button" class="remove-row">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top:8px;"><button type="button" id="add-row">Add item</button></div>
                </div>

                <div style="margin-top:12px;">
                    <button type="submit">Create Invoice</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top:16px;">
            <h2>Recent Invoices</h2>
            <?php if (empty($recentBills)): ?>
                <p>No invoices found.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>ID</th><th>Patient</th><th>Total</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentBills as $b): 
                            $items = json_decode($b['items_json'], true);
                            $patientName = 'N/A';
                            foreach ($patients as $p) { if ($p['id'] == $b['patient_id']) { $patientName = $p['full_name']; break; } }
                        ?>
                        <tr>
                            <td><?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($patientName); ?></td>
                            <td><?php echo number_format($b['total'], 2); ?></td>
                            <td><?php echo $b['created_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('add-row').addEventListener('click', function(){
            const tbody = document.querySelector('#items-table tbody');
            const tr = document.createElement('tr');
            tr.innerHTML = '<td><input type="text" name="desc[]" required></td>'+
                           '<td class="right"><input type="number" step="0.01" name="amount[]" required></td>'+
                           '<td><button type="button" class="remove-row">Remove</button></td>';
            tbody.appendChild(tr);
        });

        document.addEventListener('click', function(e){
            if (e.target && e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                if (row) row.remove();
            }
        });
    </script>
</body>
</html>

