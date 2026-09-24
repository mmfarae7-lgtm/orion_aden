-- Migration: Add new roles and update existing users
-- Run this if you already have an existing database

-- Step 1: Alter the role ENUM to support 6 roles
ALTER TABLE `users` 
  MODIFY COLUMN `role` ENUM('super_admin','admin','accountant','teacher','reception','viewer') NOT NULL DEFAULT 'viewer';

-- Step 2: Update existing admin user to super_admin
UPDATE `users` SET `role` = 'super_admin' WHERE `role` = 'admin' AND `username` = 'Orion';

-- Step 3: Update any remaining 'staff' users to 'viewer'
UPDATE `users` SET `role` = 'viewer' WHERE `role` = 'staff';

-- Step 4: Insert sample users (password: 123456789)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`) VALUES
('admin', 'admin@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'مدير المدرسة', 'admin', 1),
('accountant', 'accountant@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'محاسب المدرسة', 'accountant', 1),
('teacher', 'teacher@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'مدرس', 'teacher', 1),
('reception', 'reception@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'موظف الاستقبال', 'reception', 1),
('viewer', 'viewer@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'مشاهد', 'viewer', 1)
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

-- Step 5: Update school name and logo
UPDATE `settings` SET `setting_value` = 'Orion Aden' WHERE `setting_key` = 'school_name';
UPDATE `settings` SET `setting_value` = 'assets/img/Orion.png' WHERE `setting_key` = 'school_logo';
