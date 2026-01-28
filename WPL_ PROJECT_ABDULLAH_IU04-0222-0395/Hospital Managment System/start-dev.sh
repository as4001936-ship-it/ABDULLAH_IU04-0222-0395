#!/bin/bash
# Start PHP Development Server for Linux/macOS

echo "Starting Hospital Management System (DEV Mode)..."
echo ""
echo "Make sure PHP is installed"
echo "Server will start at: http://localhost:8000"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

cd "$(dirname "$0")"
php -S localhost:8000 -t public

