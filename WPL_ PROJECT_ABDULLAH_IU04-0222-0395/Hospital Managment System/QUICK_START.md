# Quick Start Guide - Development Mode

## Step 1: Check if PHP is Installed

Open your terminal/command prompt and run:
```bash
php -v
```

If you see a version number (like "PHP 7.4.33" or higher), you're good to go! Skip to Step 3.

If you see an error like "php is not recognized", you need to install PHP first.

## Step 2: Install PHP (If Needed)

### Windows:
1. Download PHP from: https://windows.php.net/download/
   - Choose "Thread Safe" version
   - Extract to `C:\php`
2. Add PHP to PATH:
   - Search "Environment Variables" in Windows
   - Edit "Path" variable
   - Add `C:\php`
3. Restart your terminal and verify: `php -v`

**Alternative - Using XAMPP just for PHP:**
- If you have XAMPP installed, you can use its PHP
- PHP will be at `C:\xampp\php\php.exe`
- Add `C:\xampp\php` to your PATH
- Note: You don't need to run Apache/MySQL, just use PHP from command line

### macOS:
```bash
brew install php
```

### Linux (Ubuntu/Debian):
```bash
sudo apt update
sudo apt install php php-cli
```

## Step 3: Start the Server

### Option A: Use the Helper Scripts (Easiest)

**Windows:**
Double-click `start-dev.bat` in the project folder

**Linux/macOS:**
```bash
chmod +x start-dev.sh
./start-dev.sh
```

### Option B: Manual Command

1. Open terminal/command prompt
2. Navigate to the project folder:
   ```bash
   cd "E:\Hospital Managment System"
   ```
3. Run:
   ```bash
   php -S localhost:8000 -t public
   ```

You should see:
```
PHP 7.4.x Development Server started at Mon Jan 01 10:00:00 2024
Listening on http://localhost:8000
Document root is E:\Hospital Managment System\public
Press Ctrl-C to quit.
```

## Step 4: Open in Browser

Open your web browser and go to:
```
http://localhost:8000/login.php
```

## Step 5: Login

Use any of these test accounts:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@hospital.com | Admin@123 |
| Receptionist | receptionist@hospital.com | Receptionist@123 |
| Doctor | doctor@hospital.com | Doctor@123 |
| Lab Technician | lab@hospital.com | LabTech@123 |
| Pharmacist | pharmacist@hospital.com | Pharmacist@123 |

## Troubleshooting

**"php is not recognized"**
- PHP is not installed or not in PATH
- See Step 2 to install PHP

**"Address already in use"**
- Port 8000 is already taken
- Use a different port: `php -S localhost:3000 -t public`
- Then access: `http://localhost:3000/login.php`

**"Could not open input file"**
- You're not in the correct directory
- Make sure you're in the project root folder
- Check that `public` folder exists

**Page shows errors**
- Make sure `APP_ENV` is set to `'dev'` in `app/config/app.php`
- Check that `data/mock_users.json` exists

## Stopping the Server

Press `Ctrl+C` in the terminal where the server is running.

