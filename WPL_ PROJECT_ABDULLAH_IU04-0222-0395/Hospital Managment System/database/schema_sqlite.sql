-- Hospital Management System - SQLite Database Schema
-- Authentication & Authorization Module
-- No installation needed - SQLite is built into PHP!

-- Table 1: users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `full_name` TEXT NOT NULL,
  `email` TEXT UNIQUE NOT NULL,
  `phone` TEXT DEFAULT NULL,
  `password` TEXT NOT NULL,
  `status` TEXT DEFAULT 'active' CHECK(`status` IN ('active', 'inactive', 'locked')),
  `failed_login_attempts` INTEGER DEFAULT 0,
  `last_login_at` TEXT DEFAULT NULL,
  `must_change_password` INTEGER DEFAULT 0,
  `last_password_change_at` TEXT DEFAULT NULL,
  `show_on_login` INTEGER DEFAULT 0,
  `created_at` TEXT DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_email` ON `users`(`email`);
CREATE INDEX IF NOT EXISTS `idx_status` ON `users`(`status`);

-- Table 2: roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT UNIQUE NOT NULL,
  `display_name` TEXT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_name` ON `roles`(`name`);

-- Table 3: user_roles (many-to-many relationship)
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INTEGER NOT NULL,
  `role_id` INTEGER NOT NULL,
  `created_at` TEXT DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_user_id` ON `user_roles`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_role_id` ON `user_roles`(`role_id`);

-- Table 4: permissions (optional but recommended)
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `key` TEXT UNIQUE NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_key` ON `permissions`(`key`);

-- Table 5: role_permissions (many-to-many relationship)
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INTEGER NOT NULL,
  `permission_id` INTEGER NOT NULL,
  `created_at` TEXT DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_role_id` ON `role_permissions`(`role_id`);
CREATE INDEX IF NOT EXISTS `idx_permission_id` ON `role_permissions`(`permission_id`);

-- Table 6: audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER DEFAULT NULL,
  `action` TEXT NOT NULL,
  `metadata` TEXT DEFAULT NULL,
  `ip_address` TEXT DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_user_id` ON `audit_logs`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_action` ON `audit_logs`(`action`);
CREATE INDEX IF NOT EXISTS `idx_created_at` ON `audit_logs`(`created_at`);

-- Table 7: appointments
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `patient_id` INTEGER NOT NULL,
  `doctor_id` INTEGER DEFAULT NULL,
  `appointment_date` TEXT NOT NULL,
  `appointment_time` TEXT NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` TEXT DEFAULT 'scheduled' CHECK(`status` IN ('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show')),
  `notes` TEXT DEFAULT NULL,
  `created_at` TEXT DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_patient_id` ON `appointments`(`patient_id`);
CREATE INDEX IF NOT EXISTS `idx_doctor_id` ON `appointments`(`doctor_id`);
CREATE INDEX IF NOT EXISTS `idx_appointment_date` ON `appointments`(`appointment_date`);
CREATE INDEX IF NOT EXISTS `idx_status` ON `appointments`(`status`);

