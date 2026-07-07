-- Orion School Management System
-- Database Schema

CREATE DATABASE IF NOT EXISTS `orion_school` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `orion_school`;

-- --------------------------------------------------------
-- Users (Admin & Staff)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('super_admin','admin','accountant','teacher','reception','viewer') NOT NULL DEFAULT 'viewer',
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Students
-- --------------------------------------------------------
CREATE TABLE `students` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_code` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `father_name` VARCHAR(100) DEFAULT NULL,
  `mother_name` VARCHAR(100) DEFAULT NULL,
  `gender` ENUM('male','female') NOT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `enrollment_date` DATE NOT NULL,
  `status` ENUM('active','inactive','graduated','suspended') NOT NULL DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_code` (`student_code`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Teachers
-- --------------------------------------------------------
CREATE TABLE `teachers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_code` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `gender` ENUM('male','female') NOT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `qualification` VARCHAR(255) DEFAULT NULL,
  `specialization` VARCHAR(255) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `hire_date` DATE NOT NULL,
  `salary` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('active','inactive','resigned') NOT NULL DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_code` (`teacher_code`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Courses / Classes
-- --------------------------------------------------------
CREATE TABLE `courses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_code` VARCHAR(20) NOT NULL,
  `course_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `teacher_id` INT(11) UNSIGNED DEFAULT NULL,
  `credit_hours` INT(11) DEFAULT 0,
  `fee` DECIMAL(10,2) DEFAULT 0.00,
  `max_students` INT(11) DEFAULT 30,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `fk_courses_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Enrollments (Student-Course)
-- --------------------------------------------------------
CREATE TABLE `enrollments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `course_id` INT(11) UNSIGNED NOT NULL,
  `enrollment_date` DATE NOT NULL,
  `status` ENUM('active','completed','dropped') NOT NULL DEFAULT 'active',
  `grade` VARCHAR(5) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_course` (`student_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Fees
-- --------------------------------------------------------
CREATE TABLE `fees` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fee_name` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `frequency` ENUM('one_time','monthly','yearly') NOT NULL DEFAULT 'one_time',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Fee Payments
-- --------------------------------------------------------
CREATE TABLE `fee_payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `fee_id` INT(11) UNSIGNED NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` ENUM('cash','card','bank_transfer','cheque') DEFAULT 'cash',
  `receipt_number` VARCHAR(50) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `student_id` (`student_id`),
  KEY `fee_id` (`fee_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Attendance
-- --------------------------------------------------------
CREATE TABLE `attendance` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `course_id` INT(11) UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `notes` TEXT DEFAULT NULL,
  `recorded_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`attendance_date`),
  KEY `course_id` (`course_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Student ID Cards
-- --------------------------------------------------------
CREATE TABLE `student_cards` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `card_number` VARCHAR(50) NOT NULL,
  `issue_date` DATE NOT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `status` ENUM('active','expired','lost','damaged') NOT NULL DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_number` (`card_number`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `fk_cards_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Backups
-- --------------------------------------------------------
CREATE TABLE `backups` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` BIGINT DEFAULT 0,
  `type` ENUM('manual','automatic') NOT NULL DEFAULT 'manual',
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_backups_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Settings
-- --------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `group_name` VARCHAR(50) DEFAULT 'general',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insert Default Data
-- --------------------------------------------------------

-- Default users (password: 123456789)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`) VALUES
('super_admin', 'super@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'المشرف العام', 'super_admin', 1),
('admin', 'admin@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'مدير المدرسة', 'admin', 1),
('accountant', 'accountant@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'محاسب المدرسة', 'accountant', 1),
('teacher', 'teacher@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'مدرس', 'teacher', 1),
('reception', 'reception@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'موظف الاستقبال', 'reception', 1),
('viewer', 'viewer@orion.edu', '$2y$10$i0e5Qvvz0LWBh1K/2unfM.KPPYKcrdXwNJs1euPthsiZRsE6TO9/.', 'مشاهد', 'viewer', 1);

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `group_name`) VALUES
('school_name', 'Orion Aden', 'general'),
('school_address', '', 'general'),
('school_phone', '', 'general'),
('school_email', '', 'general'),
('school_logo', 'assets/img/Orion.png', 'general'),
('academic_year', '2025-2026', 'general'),
('currency', 'د.ع', 'general'),
('date_format', 'Y-m-d', 'general'),
('timezone', 'Asia/Baghdad', 'general'),
('language', 'ar', 'general'),
('backup_auto', '0', 'backup'),
('backup_frequency', 'daily', 'backup'),
('notify_email', '', 'notification');
