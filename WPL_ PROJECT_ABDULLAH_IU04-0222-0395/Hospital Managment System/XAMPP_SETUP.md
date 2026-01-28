# Running with XAMPP - Setup Guide

## Understanding XAMPP Directory Structure

XAMPP's web root is the `htdocs` folder:
- **Windows**: `C:\xampp\htdocs\`
- **macOS**: `/Applications/XAMPP/htdocs/`
- **Linux**: `/opt/lampp/htdocs/`

## Setup Options

### Option 1: Move Project to htdocs (Recommended)

1. **Copy or move your project** to the htdocs folder:
   ```
   C:\xampp\htdocs\Hospital-Management-System\
   ```
   ⚠️ **Note**: Consider renaming the folder to remove spaces (use hyphens or underscores)

2. **Access the application:**
   ```
   http://localhost/Hospital-Management-System/public/login.php
   ```
   Or if you kept the original name with spaces:
   ```
   http://localhost/Hospital%20Management%20System/public/login.php
   ```

### Option 2: Create Symbolic Link (Advanced)

Create a symbolic link from htdocs to your project folder (keeps project in original location).

**Windows (Run Command Prompt as Administrator):**
```cmd
mklink /D "C:\xampp\htdocs\Hospital-Management-System" "E:\Hospital Managment System"
```

**macOS/Linux:**
```bash
ln -s "E:\Hospital Managment System" /Applications/XAMPP/htdocs/Hospital-Management-System
```

Then access:
```
http://localhost/Hospital-Management-System/public/login.php
```

### Option 3: Change XAMPP Document Root (Advanced)

Edit `C:\xampp\apache\conf\httpd.conf`:

Find:
```apache
DocumentRoot "C:/xampp/htdocs"
<Directory "C:/xampp/htdocs">
```

Change to:
```apache
DocumentRoot "E:/Hospital Managment System/public"
<Directory "E:/Hospital Managment System/public">
```

Then restart Apache and access:
```
http://localhost/login.php
```

⚠️ **Warning**: This affects all XAMPP projects. Only do this if this is your only project.

## Recommended Setup Steps

### Step 1: Move/Rename Project Folder

1. Copy your project to `C:\xampp\htdocs\`
2. Rename to remove spaces: `Hospital-Management-System` (optional but recommended)
3. Or keep original name if you prefer

### Step 2: Verify XAMPP is Running

1. Open XAMPP Control Panel
2. Make sure **Apache** is running (green)
3. MySQL is optional for DEV mode (not needed)

### Step 3: Access the Application

**If folder name has no spaces:**
```
http://localhost/Hospital-Management-System/public/login.php
```

**If folder name has spaces:**
```
http://localhost/Hospital%20Management%20System/public/login.php
```
(URL encoding: spaces become `%20`)

### Step 4: Test Login

Use any test account:
- Email: `admin@hospital.com`
- Password: `Admin@123`

## Quick Access Setup (Optional)

Create a shortcut file `START_HERE.html` in your project root:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Hospital Management System</title>
    <meta http-equiv="refresh" content="0; url=public/login.php">
</head>
<body>
    <p>Redirecting to login page...</p>
    <p><a href="public/login.php">Click here if not redirected</a></p>
</body>
</html>
```

Then access:
```
http://localhost/Hospital-Management-System/START_HERE.html
```

## Troubleshooting

### "404 Not Found" Error

**Problem**: Apache can't find the file

**Solutions**:
1. Check folder name spelling (case-sensitive on Linux)
2. Make sure you're accessing `/public/login.php`, not just the root
3. Verify Apache is running in XAMPP Control Panel
4. Check folder permissions (should be readable)

### "Directory Listing" (Seeing Folders)

**Problem**: You're accessing the root folder instead of `/public/`

**Solution**: Always access:
```
http://localhost/Hospital-Management-System/public/login.php
```

Or the `.htaccess` file I created will redirect root to login.php

### PHP Errors Showing

**Problem**: PHP code showing as text or errors

**Solutions**:
1. Make sure PHP is enabled in XAMPP (should be by default)
2. Check `php.ini` is loaded (check in XAMPP Control Panel)
3. Restart Apache after any changes

### Session Errors

**Problem**: Sessions not working

**Solution**: 
1. Check `C:\xampp\php\php.ini`
2. Find `session.save_path` and ensure it points to a writable directory
3. Default should be fine, but if not, set to: `session.save_path = "C:/xampp/tmp"`

## Summary

**Simplest Setup:**
1. Copy project to `C:\xampp\htdocs\Hospital-Management-System\`
2. Start Apache in XAMPP Control Panel
3. Access: `http://localhost/Hospital-Management-System/public/login.php`
4. Login with: `admin@hospital.com` / `Admin@123`

That's it! 🎉

