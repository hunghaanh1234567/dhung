-- ============================================================
-- LMS Database Schema (chỉ tạo bảng cho database lms)
-- Chạy file này trong database: lms
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Lưu ý: File này KHÔNG tạo database, bạn cần chọn database `lms`
-- trước khi import, hoặc bỏ comment dòng dưới:
-- USE `lms`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) UNIQUE NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) DEFAULT 'student',
  `avatar` VARCHAR(255) DEFAULT '',
  `phone` VARCHAR(30) DEFAULT '',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `icon` VARCHAR(10) DEFAULT '📚',
  `description` TEXT DEFAULT '',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT '',
  `thumbnail` VARCHAR(255) DEFAULT '',
  `category_id` INT,
  `instructor_id` INT,
  `price` DECIMAL(12,2) DEFAULT 0,
  `is_published` TINYINT(1) DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `level` VARCHAR(20) DEFAULT 'beginner',
  `duration_hours` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`category_id`) REFERENCES `categories`(`id`),
  FOREIGN KEY(`instructor_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `order_num` INT DEFAULT 0,
  FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lessons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT DEFAULT '',
  `video_url` VARCHAR(500) DEFAULT '',
  `file_url` VARCHAR(500) DEFAULT '',
  `file_name` VARCHAR(255) DEFAULT '',
  `lesson_type` VARCHAR(20) DEFAULT 'text',
  `order_num` INT DEFAULT 0,
  `duration_minutes` INT DEFAULT 0,
  FOREIGN KEY(`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `section_id` INT,
  `title` VARCHAR(255) NOT NULL,
  `time_limit` INT DEFAULT 30,
  `passing_score` INT DEFAULT 70,
  `order_num` INT DEFAULT 0,
  FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `question` TEXT NOT NULL,
  `option_a` TEXT NOT NULL,
  `option_b` TEXT NOT NULL,
  `option_c` TEXT NOT NULL,
  `option_d` TEXT NOT NULL,
  `correct_answer` VARCHAR(1) NOT NULL,
  FOREIGN KEY(`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `payment_status` VARCHAR(20) DEFAULT 'free',
  UNIQUE KEY `unique_enrollment` (`user_id`, `course_id`),
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lesson_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `lesson_id` INT NOT NULL,
  `completed` TINYINT(1) DEFAULT 0,
  `completed_at` DATETIME,
  UNIQUE KEY `unique_progress` (`user_id`, `lesson_id`),
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY(`lesson_id`) REFERENCES `lessons`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `quiz_id` INT NOT NULL,
  `score` DECIMAL(5,2) DEFAULT 0,
  `passed` TINYINT(1) DEFAULT 0,
  `answers` TEXT DEFAULT '{}',
  `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY(`quiz_id`) REFERENCES `quizzes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `issued_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `cert_code` VARCHAR(100) UNIQUE NOT NULL,
  UNIQUE KEY `unique_cert` (`user_id`, `course_id`),
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `instructor_id` INT NOT NULL,
  `session_name` VARCHAR(255) NOT NULL,
  `session_date` DATE NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`),
  FOREIGN KEY(`instructor_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status` VARCHAR(20) DEFAULT 'absent',
  `check_in_time` DATETIME,
  `ai_verified` TINYINT(1) DEFAULT 0,
  `note` TEXT DEFAULT '',
  UNIQUE KEY `unique_record` (`session_id`, `user_id`),
  FOREIGN KEY(`session_id`) REFERENCES `attendance_sessions`(`id`),
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) UNIQUE NOT NULL,
  `setting_value` TEXT DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `face_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `profile_data` LONGTEXT DEFAULT '',
  `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `course_id` INT DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT '',
  `body` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `parent_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`sender_id`) REFERENCES `users`(`id`),
  FOREIGN KEY(`receiver_id`) REFERENCES `users`(`id`),
  FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
