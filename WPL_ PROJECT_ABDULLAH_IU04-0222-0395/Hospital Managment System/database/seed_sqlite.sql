-- Seed data for Hospital Management System (SQLite)

-- Insert roles (using INSERT OR IGNORE for SQLite)
INSERT OR IGNORE INTO `roles` (`name`, `display_name`, `description`) VALUES
('admin', 'Admin', 'System administrator with full access'),
('receptionist', 'Receptionist', 'Front desk staff - patient registration, appointments, billing'),
('doctor', 'Doctor', 'Medical staff - appointments, patient encounters, prescriptions'),
('lab_technician', 'Lab Technician', 'Laboratory staff - process lab orders, enter results'),
('pharmacist', 'Pharmacist', 'Pharmacy staff - dispense medications, manage inventory'),
('patient', 'Patient', 'Registered patients - can view appointments, prescriptions, and medical records');

-- Insert test users (plain text passwords for simplicity)
-- show_on_login = 1 means this user should be displayed on the login page
INSERT OR IGNORE INTO `users` (`id`, `full_name`, `email`, `password`, `status`, `phone`, `failed_login_attempts`, `show_on_login`, `created_at`) VALUES
(1, 'System Administrator', 'admin@hospital.com', 'Admin@123', 'active', NULL, 0, 1, datetime('now')),
(2, 'Fatima Ali', 'receptionist@hospital.com', 'Receptionist@123', 'active', '555-0101', 0, 1, datetime('now')),
(3, 'Dr. Ahmed Khan', 'doctor@hospital.com', 'Doctor@123', 'active', '555-0202', 0, 1, datetime('now')),
(4, 'Ayesha Malik', 'lab@hospital.com', 'LabTech@123', 'active', '555-0303', 0, 1, datetime('now')),
(5, 'Hassan Raza', 'pharmacist@hospital.com', 'Pharmacist@123', 'active', '555-0404', 0, 1, datetime('now')),
(8, 'Ali Hassan', 'patient@hospital.com', 'Patient@123', 'active', '555-1001', 0, 1, datetime('now')),
(6, 'Locked User', 'locked@hospital.com', 'Locked@123', 'locked', NULL, 5, 0, datetime('now')),
(7, 'Inactive User', 'inactive@hospital.com', 'Inactive@123', 'inactive', NULL, 0, 0, datetime('now'));

-- Assign roles to users
INSERT OR IGNORE INTO `user_roles` (`user_id`, `role_id`, `created_at`) 
SELECT u.id, r.id, datetime('now')
FROM users u, roles r
WHERE (u.email = 'admin@hospital.com' AND r.name = 'admin')
   OR (u.email = 'receptionist@hospital.com' AND r.name = 'receptionist')
   OR (u.email = 'doctor@hospital.com' AND r.name = 'doctor')
   OR (u.email = 'lab@hospital.com' AND r.name = 'lab_technician')
   OR (u.email = 'pharmacist@hospital.com' AND r.name = 'pharmacist')
   OR (u.email = 'patient@hospital.com' AND r.name = 'patient')
   OR (u.email = 'locked@hospital.com' AND r.name = 'receptionist')
   OR (u.email = 'inactive@hospital.com' AND r.name = 'doctor');

