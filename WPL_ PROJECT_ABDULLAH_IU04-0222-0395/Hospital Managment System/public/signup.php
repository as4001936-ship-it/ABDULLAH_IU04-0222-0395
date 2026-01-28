<?php
/**
 * Patient Sign-Up Page
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/auth/auth_handler.php';
require_once __DIR__ . '/../app/auth/auth_guard.php';

// If already logged in, redirect to dashboard
if (isAuthenticated()) {
    header('Location: ' . url('public/index.php'));
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $pdo = getDBConnection();
        
        if (!$pdo) {
            $error = 'Database connection failed. Please try again later.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered. <a href="' . url('public/login.php') . '">Click here to login</a> instead.';
                    $pdo->rollBack();
                } else {
                    // Get patient role ID
                    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'patient'");
                    $stmt->execute();
                    $role = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$role) {
                        // Create patient role if it doesn't exist
                        $stmt = $pdo->prepare("
                            INSERT INTO roles (name, display_name, description, created_at)
                            VALUES ('patient', 'Patient', 'Registered patients - can view appointments, prescriptions, and medical records', datetime('now'))
                        ");
                        $stmt->execute();
                        $roleId = $pdo->lastInsertId();
                    } else {
                        $roleId = $role['id'];
                    }
                    
                    // Create user account
                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, email, phone, password, status, created_at)
                        VALUES (:full_name, :email, :phone, :password, 'active', datetime('now'))
                    ");
                    $stmt->execute([
                        ':full_name' => $fullName,
                        ':email' => $email,
                        ':phone' => $phone ?: null,
                        ':password' => $password
                    ]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // Assign patient role
                    $stmt = $pdo->prepare("
                        INSERT INTO user_roles (user_id, role_id, created_at)
                        VALUES (:user_id, :role_id, datetime('now'))
                    ");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':role_id' => $roleId
                    ]);
                    
                    $pdo->commit();
                    
                    // Log the registration
                    require_once __DIR__ . '/../app/auth/audit_log.php';
                    logAuditAction('PATIENT_REGISTERED', [
                        'user_id' => $userId,
                        'email' => $email
                    ]);
                    
                    // Auto-login the user
                    $result = attemptLogin($email, $password);
                    if ($result['success']) {
                        header('Location: ' . url('public/index.php'));
                        exit;
                    } else {
                        // Auto-login failed, but account was created
                        $success = 'Account created successfully! However, auto-login failed. Please login manually.';
                        $error = 'Auto-login error: ' . ($result['message'] ?? 'Unknown error');
                    }
                }
            } catch (PDOException $e) {
                if ($pdo) {
                    $pdo->rollBack();
                }
                error_log("Sign-up error: " . $e->getMessage());
                $error = 'An error occurred during registration. Please try again.';
            }
        }
    }
}

// Display any error/success messages from session
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Sign-Up - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../app/includes/view_head.php'; ?>
    <style>
        .signup-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .signup-container h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }
        
        .signup-container .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 0.9em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .btn-signup {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-signup:hover {
            background: #45a049;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .required {
            color: #c33;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h1>Patient Registration</h1>
        <p class="subtitle">Create your account to access hospital services</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 20px; padding: 10px; background: #f0f7ff; border: 1px solid #b3d9ff; border-radius: 4px;">
            <button type="button" onclick="fillTestData()" style="width: 100%; padding: 8px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em;">
                📝 Fill with Test Data
            </button>
        </div>
        
        <form method="POST" action="" id="signupForm">
            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required minlength="8"
                       placeholder="Minimum 8 characters">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            
            <button type="submit" class="btn-signup">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="<?php echo url('public/login.php'); ?>">Login here</a>
        </div>
    </div>
    
    <script>
        // Test data for auto-fill
        const testData = {
            full_name: 'Ali Hassan',
            email: 'ali.hassan@example.com',
            phone: '555-1001',
            password: 'Patient@123'
        };
        
        function fillTestData() {
            document.getElementById('full_name').value = testData.full_name;
            document.getElementById('email').value = testData.email;
            document.getElementById('phone').value = testData.phone;
            document.getElementById('password').value = testData.password;
            document.getElementById('confirm_password').value = testData.password;
            
            // Clear any validation errors
            document.getElementById('confirm_password').setCustomValidity('');
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Form submission validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>

