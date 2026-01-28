# Mock Users Data (DEV Mode)

This directory contains mock user data for development mode.

## File: mock_users.json

Contains pre-configured users for testing all roles in the system.

### Default Users

1. **Admin** - `admin@hospital.com` / `Admin@123`
2. **Receptionist** - `receptionist@hospital.com` / `Receptionist@123`
3. **Doctor** - `doctor@hospital.com` / `Doctor@123`
4. **Lab Technician** - `lab@hospital.com` / `LabTech@123`
5. **Pharmacist** - `pharmacist@hospital.com` / `Pharmacist@123`
6. **Locked User** - `locked@hospital.com` / `Locked@123` (for testing account lockout)
7. **Inactive User** - `inactive@hospital.com` / `Inactive@123` (for testing inactive accounts)

### Editing Mock Users

You can edit this file to add, remove, or modify users. The format is:

```json
{
  "users": [
    {
      "id": 1,
      "full_name": "User Name",
      "email": "user@example.com",
      "password": "PlainTextPassword",
      "status": "active",
      "roles": ["role_name"],
      "phone": "optional",
      "failed_login_attempts": 0,
      "last_login_at": null
    }
  ]
}
```

**Important Notes:**
- In DEV mode, passwords are stored and compared as plain text (for testing only)
- Status can be: `active`, `inactive`, or `locked`
- Roles must match role names from the database (or system defaults)
- Changes to this file take effect immediately (no restart needed)

### Security Warning

⚠️ **NEVER** use DEV mode or mock users in production! Always use PROD mode with database authentication in production environments.

