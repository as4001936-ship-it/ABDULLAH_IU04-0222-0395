## 1) Goals and scope

### What this module must do

* Allow users to **log in securely**.
* Support **role-based access control (RBAC)** so each role sees only what they should.
* Provide **session-based authentication** (standard for PHP).
* Include basic **security + auditability** (enough for a “real system” feel).

### Roles (recommended for your project)

Start with 5:

1. **Admin**
2. **Receptionist**
3. **Doctor**
4. **Lab Technician**
5. **Pharmacist**

You can add Nurse/Accountant later, but these 5 cover a full demo flow.

---

## 2) Folder / file structure (simple but clean)

Use a consistent structure so your project doesn’t turn into spaghetti.

### Suggested structure

* `/public/`

  * `login.php` (login UI)
  * `logout.php`
  * `index.php` (route user to their dashboard)
* `/app/`

  * `/config/` (db + app config)
  * `/auth/` (auth handlers, guards/middleware)
  * `/controllers/` (optional: separate logic)
  * `/views/` (dashboards/pages)
  * `/includes/` (header/sidebar, shared UI)
* `/assets/`

  * `/css/`
  * `/js/`

**Key idea:** any page under `/app/` should be protected (no direct access without login).

---

## 3) Database design (RBAC-ready but not overkill)

### Table 1: `users`

Purpose: store accounts and login identity.

Fields (minimum):

* `id` (PK)
* `full_name`
* `email` (unique) OR `username` (unique) — choose one primary login identifier
* `phone` (optional)
* `password_hash` (store hashed password, never plain)
* `status` (ENUM: `active`, `inactive`, `locked`)
* `failed_login_attempts` (int, default 0)
* `last_login_at` (datetime, nullable)
* `created_at`, `updated_at`

Optional but nice:

* `must_change_password` (tinyint)
* `last_password_change_at`

### Table 2: `roles`

Purpose: define available roles.

Fields:

* `id` (PK)
* `name` (unique, e.g., `admin`, `doctor`)
* `display_name` (e.g., “Doctor”)
* `description`

### Table 3: `user_roles`

Purpose: link users to roles (supports 1 user = multiple roles, even if you don’t use it now).

Fields:

* `user_id` (FK)
* `role_id` (FK)
* Composite PK (`user_id`, `role_id`)

> If you want *super simple*, you can store `role_id` directly in `users`.
> But `user_roles` looks more “professional” and is still easy.

### Table 4: `permissions` (optional but recommended)

Purpose: define fine-grain actions (view patients, create bill, etc.)

Fields:

* `id`
* `key` (unique: `patients.view`, `billing.create`)
* `description`

### Table 5: `role_permissions` (optional if you include permissions)

Purpose: map role → permissions.

Fields:

* `role_id`
* `permission_id`
* Composite PK (`role_id`, `permission_id`)

### Table 6: `audit_logs` (recommended for marks)

Purpose: record security-related events.

Fields:

* `id`
* `user_id` (nullable for unknown user)
* `action` (e.g., `LOGIN_SUCCESS`, `LOGIN_FAIL`, `LOGOUT`, `ACCESS_DENIED`)
* `metadata` (text/json: ip, user agent, attempted email, page)
* `created_at`

---

## 4) Authentication flow (end-to-end)

### Login UI requirements (`login.php`)

* Input: email/username + password
* “Remember me” (optional — skip for uni project if you want)
* Display errors safely (no leaking “email exists” too clearly)

Client-side (JS) validation:

* required fields
* basic email format if using email
* disable submit button while request is processing

### Login backend flow (server-side)

When form is submitted:

1. **Sanitize/validate** inputs (server must validate, JS is only convenience).
2. Fetch user by email/username.
3. If user not found:

   * record `LOGIN_FAIL` (without confirming user existence in UI)
   * show generic error: “Invalid credentials”
4. If found but status is `inactive` or `locked`:

   * show “Account disabled, contact admin”
   * record audit log
5. Verify password against `password_hash`.
6. If password wrong:

   * increment `failed_login_attempts`
   * if attempts exceed threshold (e.g., 5), set `status=locked`
   * record audit log
7. If success:

   * reset `failed_login_attempts`
   * update `last_login_at`
   * create session: `user_id`, roles, and a session token/flag
   * regenerate session ID (prevent session fixation)
   * redirect to `index.php` (router) or role dashboard directly
   * record `LOGIN_SUCCESS`

### Logout flow

* Destroy session
* record `LOGOUT`
* redirect to login

---

## 5) Session strategy (PHP)

Use normal PHP sessions.

Session keys you should keep:

* `auth.user_id`
* `auth.roles` (array of role names or IDs)
* `auth.csrf_token` (for forms)
* `auth.last_activity` (for inactivity timeout)

Timeout rules:

* Inactivity timeout (e.g., 30 minutes): if last_activity too old → logout.
* Optional: absolute session lifetime (e.g., 8 hours).

---

## 6) Authorization model (Roles → Pages/Actions)

### Approach for uni project

* Use **page-level guards**: block access if role not allowed
* And **UI-level hiding**: hide menu items the role shouldn’t see (but still enforce server-side)

### Define page access map (example)

* Admin:

  * Manage users, roles/permissions
  * View all modules
* Receptionist:

  * Patient registration
  * Appointment booking
  * Billing (optional: view only)
* Doctor:

  * Appointments list
  * Patient encounters, prescriptions, lab orders
* Lab Tech:

  * Lab orders queue
  * Enter/verify results
* Pharmacist:

  * Dispense meds
  * Inventory overview (optional)

Make a clear table in your documentation (teachers love it):

* Page → Allowed roles → Key actions

---

## 7) “Auth Guard” design (how every protected page behaves)

Every protected page should do this in order:

1. Check session exists and `auth.user_id` is set

   * If not, redirect to login.
2. Check inactivity timeout

   * If expired, logout + redirect.
3. Load user roles (from session or DB).
4. Check if user role is allowed for this page/action

   * If not, show 403 “Access denied” page.
   * log `ACCESS_DENIED` to `audit_logs`.

**Important:** even if you hide links in the sidebar, still block direct URL access.

---

## 8) Admin “Manage Users” (minimal but impressive)

To make your Login + Roles module feel complete, build a basic Admin panel:

### Admin user management pages

* List users (search by name/email, filter by role/status)
* Create user (set temporary password or “reset link” style)
* Assign roles (checkbox list)
* Activate/deactivate user
* Unlock user (reset failed attempts)

### Policies

* Only Admin can manage users.
* When new user is created, force password change on first login (optional but great).

---

## 9) Password policy (reasonable for uni project)

* Minimum length: 8
* Hash passwords (PHP built-in hashing)
* Never store plain passwords
* “Forgot password” can be skipped; instead implement:

  * Admin resets password for staff accounts
* If you want extra marks:

  * “Change password” page for all users

---

## 10) CSRF protection plan (important)

Any POST form after login should include CSRF token:

* Token stored in session
* Embedded in forms
* Verified on submit
  This is a small feature that signals “real security”.

Forms that must have CSRF:

* Create user, edit user, assign roles
* Change password
* Any create/update action in the system

---

## 11) Error handling and messages (user-friendly + safe)

Login errors:

* Use a single generic message for wrong credentials:

  * “Invalid email/username or password”
    Lock message:
* “Account locked. Contact admin.”
  Inactive message:
* “Account inactive. Contact admin.”

Do not show:

* “Email exists but password wrong” (avoid account enumeration)

---

## 12) UI plan (what each role sees)

### Layout

* Top bar: app name, user name, role badge, logout button
* Sidebar menu: based on role

Role-based menus:

* Admin: Users, Roles, Audit Logs, Dashboard
* Receptionist: Patients, Appointments, Billing
* Doctor: Appointments, Patients, Prescriptions
* Lab: Lab Queue, Reports
* Pharmacy: Prescriptions, Inventory

---

## 13) Testing checklist (what you should verify)

### Authentication tests

* Correct login works
* Wrong password increments attempts
* Locks after 5 fails
* Locked user cannot login
* Logout destroys session
* Session regenerates on login

### Authorization tests

* Receptionist cannot open `/app/admin/users.php` by URL
* Doctor cannot open lab pages
* Admin can open everything

### Security basics

* SQL injection resistance: use prepared statements everywhere
* Session timeout works
* CSRF fails if token missing/invalid

### Audit logs

* Login success creates a record
* Login fail creates a record
* Access denied creates a record

---

## 14) Deliverables you should produce (for submission/report)

1. ERD diagram (users/roles/user_roles + audit_logs)
2. Role → Page access table
3. Screenshots: login, each dashboard, access denied page, admin user management
4. Short section on security: hashing, sessions, lockout, CSRF, audit logs

---

## 15) Implementation order (best order in Cursor)

1. Create DB tables (`users`, `roles`, `user_roles`, `audit_logs`)
2. Seed roles + create one admin account
3. Build login UI
4. Build login handler + sessions + logout
5. Build `index.php` router (sends user to correct dashboard)
6. Build guard for protected pages (redirect/403)
7. Build role-based sidebar
8. Build admin user management (create user + assign roles)
9. Add lockout + audit logs + CSRF
10. Testing + polish (nice error pages)

