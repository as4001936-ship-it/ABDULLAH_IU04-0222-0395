@echo off
REM Start PHP Development Server for Windows
echo Starting Hospital Management System (DEV Mode)...
echo.
echo Make sure PHP is installed and in your PATH
echo Server will start at: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.

cd /d "%~dp0"
php -S localhost:8000 -t public

pause

