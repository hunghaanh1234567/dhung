<?php
// ============================================================
// exam_db_patch.php
// Dán đoạn SQL này vào hàm initDB() trong config.php
// Thêm sau dòng tạo bảng qr_tokens
// ============================================================

/*
── BẢNG 1: semester_exams (Đề thi kết thúc học kỳ) ─────────────
*/
$db->exec("
    CREATE TABLE IF NOT EXISTS `semester_exams` (
        `id`                INT AUTO_INCREMENT PRIMARY KEY,
        `course_id`         INT NOT NULL,
        `instructor_id`     INT NOT NULL,
        `title`             VARCHAR(255) NOT NULL,
        `description`       TEXT DEFAULT NULL,
        `time_limit`        INT DEFAULT 60 COMMENT 'Phút',
        `passing_score`     INT DEFAULT 50 COMMENT 'Phần trăm điểm đậu',
        `start_time`        DATETIME DEFAULT NULL,
        `end_time`          DATETIME DEFAULT NULL,
        `shuffle_questions` TINYINT(1) DEFAULT 1,
        `shuffle_answers`   TINYINT(1) DEFAULT 1,
        `show_result`       TINYINT(1) DEFAULT 1 COMMENT 'Hiện đáp án sau khi nộp',
        `is_active`         TINYINT(1) DEFAULT 0,
        `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(`course_id`)     REFERENCES `courses`(`id`) ON DELETE CASCADE,
        FOREIGN KEY(`instructor_id`) REFERENCES `users`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/*
── BẢNG 2: exam_questions (Câu hỏi kỳ thi) ──────────────────────
*/
$db->exec("
    CREATE TABLE IF NOT EXISTS `exam_questions` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `exam_id`        INT NOT NULL,
        `question`       TEXT NOT NULL,
        `option_a`       TEXT NOT NULL,
        `option_b`       TEXT NOT NULL,
        `option_c`       TEXT NOT NULL,
        `option_d`       TEXT NOT NULL,
        `correct_answer` VARCHAR(1) NOT NULL COMMENT 'A/B/C/D',
        `points`         INT DEFAULT 1,
        `difficulty`     VARCHAR(10) DEFAULT 'medium' COMMENT 'easy/medium/hard',
        `explanation`    TEXT DEFAULT NULL COMMENT 'Giải thích đáp án',
        `order_num`      INT DEFAULT 0,
        FOREIGN KEY(`exam_id`) REFERENCES `semester_exams`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/*
── BẢNG 3: exam_attempts (Lượt thi của học viên) ────────────────
*/
$db->exec("
    CREATE TABLE IF NOT EXISTS `exam_attempts` (
        `id`               INT AUTO_INCREMENT PRIMARY KEY,
        `exam_id`          INT NOT NULL,
        `user_id`          INT NOT NULL,
        `score`            DECIMAL(5,2) DEFAULT 0 COMMENT 'Phần trăm điểm',
        `correct_count`    INT DEFAULT 0,
        `total_questions`  INT DEFAULT 0,
        `passed`           TINYINT(1) DEFAULT 0,
        `answers_json`     LONGTEXT COMMENT 'JSON: {question_id: chon_dap_an}',
        `duration_minutes` INT DEFAULT NULL COMMENT 'Thời gian thực tế làm bài',
        `submitted_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `one_attempt` (`exam_id`, `user_id`),
        FOREIGN KEY(`exam_id`) REFERENCES `semester_exams`(`id`) ON DELETE CASCADE,
        FOREIGN KEY(`user_id`) REFERENCES `users`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
