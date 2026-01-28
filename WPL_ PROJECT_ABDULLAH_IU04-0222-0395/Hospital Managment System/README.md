# Hospital Management System - Authentication & Authorization Module

A comprehensive PHP-based authentication and authorization system with role-based access control (RBAC) for a hospital management system.

## 🚀 Quick Start (DEV Mode)

**Prerequisites:** PHP 7.4 or higher must be installed

1. **Verify PHP is installed:**
   ```bash
   php -v
   ```
   If not installed, see [Installing PHP](#installing-php) section below.

2. **Make sure DEV mode is enabled** (already set by default):
   - Open `app/config/app.php`
   - Ensure `define('APP_ENV', 'dev');` is set

3. **Start the development server:**
   
   **Windows:** Double-click `start-dev.bat` or run:
   ```bash
   php -S localhost:8000 -t public
   ```
   
   **Linux/macOS:** Run:
   ```bash
   chmod +x start-dev.sh
   ./start-dev.sh
   ```
   Or manually:
   ```bash
   php -S localhost:8000 -t public
   ```

4. **Open your browser:**
   ```
   http://localhost:8000/login.php
   ```

5. **Login with any test user:**
   - Admin: `admin@hospital.com` / `Admin@123`
   - Receptionist: `receptionist@hospital.com` / `Receptionist@123`
   - Doctor: `doctor@hospital.com` / `Doctor@123`
   - Lab Tech: `lab@hospital.com` / `LabTech@123`
   - Pharmacist: `pharmacist@hospital.com` / `Pharmacist@123`

## Features

- **Secure Authentication**: Session-based login with password hashing
- **Role-Based Access Control (RBAC)**: 5 predefined roles (Admin, Receptionist, Doctor, Lab Technician, Pharmacist)
- **Account Security**: Failed login attempt tracking and account locking
- **Audit Logging**: Comprehensive logging of security events
- **CSRF Protection**: Token-based protection for forms
- **Session Management**: Automatic session timeout and regeneration
- **Admin Panel**: User management interface with role assignment

## Roles

1. **Admin** - Full system access, user management
2. **Receptionist** - Patient registration, appointments, billing
3. **Doctor** - Appointments, patient records, prescriptions
4. **Lab Technician** - Lab orders, test results
5. **Pharmacist** - Prescription dispensing, inventory

## Installation

### 1. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE hospital_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the schema:
```bash
mysql -u root -p hospital_management < database/schema.sql
```

3. Seed the roles:
```bash
mysql -u root -p hospital_management < database/seed.sql
```

### 2. Configuration

#### Environment Mode (DEV/PROD)

Edit `app/config/app.php` to set the environment:

```php
// Change to 'prod' for production (requires database)
define('APP_ENV', 'dev'); // or 'prod'
```

**DEV Mode:**
- Uses mock users from `data/mock_users.json`
- No database required for authentication
- Perfect for development and testing
- Audit logs go to error_log instead of database

**PROD Mode:**
- Uses database for all operations
- Requires full database setup
- All features available

#### Database Configuration (PROD Mode Only)

Edit `app/config/database.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hospital_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Quick Start (DEV Mode - No Database Required)

If you're using DEV mode, you can start immediately! The system includes pre-configured mock users in `data/mock_users.json`:

**Available Test Users:**
- **Admin**: `admin@hospital.com` / `Admin@123`
- **Receptionist**: `receptionist@hospital.com` / `Receptionist@123`
- **Doctor**: `doctor@hospital.com` / `Doctor@123`
- **Lab Technician**: `lab@hospital.com` / `LabTech@123`
- **Pharmacist**: `pharmacist@hospital.com` / `Pharmacist@123`

Just make sure `APP_ENV` is set to `'dev'` in `app/config/app.php` and you can login immediately!

### 4. Create Admin User (PROD Mode Only)

Run the admin creation script:

```bash
php database/create_admin.php
```

Default admin credentials (change after first login):
- **Email**: admin@hospital.com
- **Password**: Admin@123

### 5. Starting the Development Server

#### Installing PHP

**Yes, you need PHP installed.** If you don't have it yet:

**Windows:**
- Download from [php.net/downloads](https://www.php.net/downloads.php)
- Or install via package manager (Chocolatey: `choco install php`)
- Note: XAMPP/WAMP can be used just for PHP (you don't need Apache/MySQL for this project)

**macOS:**
- Comes pre-installed (check with `php -v`)
- Or install via Homebrew: `brew install php`

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install php php-cli php-mysql php-mbstring
```

**Verify installation:**
```bash
php -v
```

#### Start PHP Built-in Development Server

The easiest way to run in DEV mode is using PHP's built-in server (no Apache/Nginx needed):

1. Open terminal/command prompt in the project root directory:
   ```bash
   cd "E:\Hospital Managment System"
   ```

2. Start the PHP development server:
   ```bash
   php -S localhost:8000 -t public
   ```

   This starts a server on `http://localhost:8000` serving files from the `public` directory.

3. Open your browser and navigate to:
   ```
   http://localhost:8000/login.php
   ```

**Alternative: If you prefer a different port:**
```bash
php -S localhost:3000 -t public  # Uses port 3000
```

**Stop the server:** Press `Ctrl+C` in the terminal

> **Note**: This project is optimized for PHP's built-in development server. For production deployment, see the Production Server Setup section below.

#### Production Server Setup

For production, configure your web server (Apache/Nginx) to point to the project root and ensure:
- Document root points to `/public/` directory
- URL rewriting is enabled (for clean URLs)
- PHP 7.4+ is installed
- mod_rewrite is enabled (Apache)

## File Structure

```
Hospital Management System/
├── app/
│   ├── config/
│   │   ├── app.php          # Application configuration (DEV/PROD mode)
│   │   └── database.php     # Database connection
│   ├── auth/
│   │   ├── auth_guard.php   # Authentication middleware
│   │   ├── auth_handler.php # Login/logout logic
│   │   ├── audit_log.php    # Audit logging functions
│   │   └── mock_users.php   # Mock users handler (DEV mode)
│   ├── includes/
│   │   ├── header.php       # Page header component
│   │   └── sidebar.php      # Navigation sidebar
│   └── views/
│       ├── admin/           # Admin dashboard & user management
│       ├── doctor/          # Doctor dashboards
│       ├── lab/             # Lab technician dashboards
│       ├── pharmacist/      # Pharmacist dashboards
│       ├── receptionist/    # Receptionist dashboards
│       └── errors/          # Error pages (403, etc.)
├── data/
│   └── mock_users.json      # Mock users for DEV mode
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   └── js/
│       ├── login.js         # Login form validation
│       └── admin.js         # Admin page enhancements
├── database/
│   ├── schema.sql           # Database schema
│   ├── seed.sql             # Role seeds
│   └── create_admin.php     # Admin user creation script
├── public/
│   ├── login.php            # Login page
│   ├── logout.php           # Logout handler
│   └── index.php            # Router (redirects to dashboards)
└── README.md
```

## Development vs Production Modes

### DEV Mode Features

- **No Database Required**: Authentication works with JSON file
- **Quick Testing**: Pre-configured mock users ready to use
- **Fast Setup**: Start testing immediately without database setup
- **Mock Users**: All roles available in `data/mock_users.json`

### PROD Mode Features

- **Full Database Integration**: All data stored in MySQL
- **Complete Features**: User management, audit logs, etc.
- **Production Ready**: Secure and scalable

**Switching Modes:**
Edit `app/config/app.php` and change `APP_ENV` from `'dev'` to `'prod'` or vice versa.

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt (PROD mode)
- **DEV Mode Passwords**: Plain text comparison in DEV mode (for testing only - never use in production!)
- **Session Security**: HttpOnly cookies, session regeneration on login
- **SQL Injection Prevention**: All queries use prepared statements
- **CSRF Protection**: Token verification on all POST forms
- **Account Lockout**: Automatic locking after 5 failed login attempts
- **Session Timeout**: 30-minute inactivity timeout
- **Audit Logging**: All security events logged (login, logout, access denied, etc.)

## Usage

### Login

Navigate to `/public/login.php` and enter your credentials.

### Protected Pages

All pages in `/app/views/` are protected. They use the `requireAuth()` and `requireRole()` functions from `auth_guard.php`.

Example:
```php
require_once __DIR__ . '/../../../app/auth/auth_guard.php';
requireRole('admin'); // Only admin can access
```

### Creating Users (Admin)

1. Login as admin
2. Navigate to "Users" in the sidebar
3. Click "Create New User"
4. Fill in the form and assign roles
5. User will be forced to change password on first login (if enabled)

## Configuration Options

Edit `app/config/app.php` to customize:

- Session timeout (default: 30 minutes)
- Max login attempts (default: 5)
- Session lifetime (default: 8 hours)
- Application name

## Testing Checklist

- [ ] Correct login works
- [ ] Wrong password increments attempts
- [ ] Account locks after 5 failed attempts
- [ ] Locked user cannot login
- [ ] Logout destroys session
- [ ] Session regenerates on login
- [ ] Receptionist cannot access admin pages
- [ ] Doctor cannot access lab pages
- [ ] Admin can access everything
- [ ] CSRF protection works
- [ ] Session timeout works
- [ ] Audit logs record events

## Development Notes

- All database queries use PDO with prepared statements
- Error messages are user-friendly and don't leak sensitive information
- The system prevents account enumeration (same error message for invalid email/password)
- Role-based menus are automatically generated based on user permissions

## License

This is an educational project for a university assignment.

