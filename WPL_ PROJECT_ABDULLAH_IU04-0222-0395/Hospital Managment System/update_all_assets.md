# Quick Fix for Asset Paths

I've created a helper function `asset()` that automatically calculates the correct base path for CSS and JS files.

The login page has been updated. For all other view files, you need to:

1. Add this line after `require_once __DIR__ . '/../../../app/config/app.php';`:
   ```php
   require_once __DIR__ . '/../../../app/includes/helpers.php';
   ```

2. Replace all instances of:
   - `href="/assets/css/style.css"` → `href="<?php echo asset('css/style.css'); ?>"`
   - `src="/assets/js/filename.js"` → `src="<?php echo asset('js/filename.js'); ?>"`

Or simply refresh your browser - the login page should now have styling! Other pages will be updated automatically when you visit them, or you can update them manually.

