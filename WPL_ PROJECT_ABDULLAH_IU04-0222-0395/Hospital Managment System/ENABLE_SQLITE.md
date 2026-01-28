# Enable SQLite in PHP

The error "could not find driver" means the SQLite PDO extension is not enabled in PHP.

## Quick Fix:

1. **Open your PHP configuration file:**
   - Location: `C:\php\php.ini`

2. **Find the extension section** (search for `;extension=`)

3. **Uncomment (remove the semicolon) from these lines:**
   ```ini
   extension=pdo_sqlite
   extension=sqlite3
   ```

4. **Save the file**

5. **Restart your PHP server** (if running) or test again:
   ```bash
   php database/setup_sqlite.php
   ```

## Alternative: Check if extensions exist

If the extensions don't exist, you may need to:
1. Download PHP with SQLite support, or
2. Enable the extensions in your php.ini

## Verify it's enabled:

Run this command to check:
```bash
php -m | findstr /i "sqlite"
```

You should see:
- `pdo_sqlite`
- `sqlite3`

## After enabling:

1. Run: `php database/setup_sqlite.php`
2. Refresh your dashboard

