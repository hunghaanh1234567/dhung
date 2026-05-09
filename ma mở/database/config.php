<?php
// ============================================================
// config.php — Cấu hình Database MySQL
// Thay đổi 4 dòng DB_* cho đúng với hosting của bạn
// ============================================================
header('Content-Type: text/html; charset=utf-8');

// ── MySQL config ─────────────────────────────────────
define('DB_HOST', 'sql207.infinityfree.com');
define('DB_NAME', 'if0_41670694_htt');
define('DB_USER', 'if0_41670694');
define('DB_PASS', 'daoquanghung1');
define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('CERT_DIR', __DIR__ . '/certificates/');

// ── Anthropic API key (dùng cho AI điểm danh khuôn mặt) ──────
// Thay YOUR_API_KEY_HERE bằng API key thật của bạn tại:
// https://console.anthropic.com/settings/keys
define('ANTHROPIC_API_KEY', 'YOUR_API_KEY_HERE');


// ============================================================
// DATABASE SETUP
// ============================================================
function getDB() {
    static $db = null;
    if ($db === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $db = new PDO($dsn, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initDB($db);
    }
    return $db;
}

function initDB($db) {
    $db->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) UNIQUE NOT NULL,
            `icon` VARCHAR(20) DEFAULT '📚',
            `description` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `courses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `sections` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `order_num` INT DEFAULT 0,
            FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `lessons` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `section_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `content` TEXT,
            `video_url` VARCHAR(500) DEFAULT '',
            `file_url` VARCHAR(500) DEFAULT '',
            `file_name` VARCHAR(255) DEFAULT '',
            `lesson_type` VARCHAR(20) DEFAULT 'text',
            `order_num` INT DEFAULT 0,
            `duration_minutes` INT DEFAULT 0,
            FOREIGN KEY(`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `quizzes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `section_id` INT DEFAULT NULL,
            `title` VARCHAR(255) NOT NULL,
            `time_limit` INT DEFAULT 30,
            `passing_score` INT DEFAULT 70,
            `order_num` INT DEFAULT 0,
            FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `enrollments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `payment_status` VARCHAR(20) DEFAULT 'free',
            UNIQUE KEY `unique_enrollment` (`user_id`, `course_id`),
            FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
            FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `lesson_progress` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `lesson_id` INT NOT NULL,
            `completed` TINYINT(1) DEFAULT 0,
            `completed_at` DATETIME DEFAULT NULL,
            UNIQUE KEY `unique_progress` (`user_id`, `lesson_id`),
            FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
            FOREIGN KEY(`lesson_id`) REFERENCES `lessons`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `quiz_attempts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `quiz_id` INT NOT NULL,
            `score` DECIMAL(5,2) DEFAULT 0,
            `passed` TINYINT(1) DEFAULT 0,
            `answers` TEXT,
            `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
            FOREIGN KEY(`quiz_id`) REFERENCES `quizzes`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `certificates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `issued_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `cert_code` VARCHAR(100) UNIQUE NOT NULL,
            UNIQUE KEY `unique_cert` (`user_id`, `course_id`),
            FOREIGN KEY(`user_id`) REFERENCES `users`(`id`),
            FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `attendance_sessions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `instructor_id` INT NOT NULL,
            `session_name` VARCHAR(255) NOT NULL,
            `session_date` DATE NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`),
            FOREIGN KEY(`instructor_id`) REFERENCES `users`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `attendance_records` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `status` VARCHAR(20) DEFAULT 'absent',
            `check_in_time` DATETIME DEFAULT NULL,
            `ai_verified` TINYINT(1) DEFAULT 0,
            `note` TEXT,
            UNIQUE KEY `unique_record` (`session_id`, `user_id`),
            FOREIGN KEY(`session_id`) REFERENCES `attendance_sessions`(`id`),
            FOREIGN KEY(`user_id`) REFERENCES `users`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `site_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS `face_profiles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL UNIQUE,
            `profile_data` LONGTEXT,
            `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `qr_tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NOT NULL,
            `token` VARCHAR(64) UNIQUE NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(`session_id`) REFERENCES `attendance_sessions`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Seed data if empty
    $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        seedData($db);
    }
}

function seedData($db) {
    // Admin
    $db->exec("INSERT INTO users (username,email,password,full_name,role) VALUES
        ('admin','admin@lms.vn','".password_hash('admin123',$p=PASSWORD_DEFAULT)."','Quản Trị Viên','admin'),
        ('teacher1','teacher1@lms.vn','".password_hash('teacher123',$p)."','Nguyễn Văn Minh','teacher'),
        ('teacher2','teacher2@lms.vn','".password_hash('teacher123',$p)."','Trần Thị Lan','teacher'),
        ('student1','student1@lms.vn','".password_hash('student123',$p)."','Lê Văn An','student'),
        ('student2','student2@lms.vn','".password_hash('student123',$p)."','Phạm Thị Bình','student'),
        ('student3','student3@lms.vn','".password_hash('student123',$p)."','Hoàng Văn Cường','student')
    ");

    // Categories
    $db->exec("INSERT INTO categories (name,slug,icon,description) VALUES
        ('Lập Trình','lap-trinh','💻','Các khóa học về lập trình, phát triển phần mềm'),
        ('Ngoại Ngữ','ngoai-ngu','🌍','Tiếng Anh, Nhật, Hàn và các ngôn ngữ khác'),
        ('Kỹ Năng Mềm','ky-nang-mem','🎯','Kỹ năng giao tiếp, lãnh đạo, quản lý thời gian')
    ");

    $t1 = $db->query("SELECT id FROM users WHERE username='teacher1'")->fetchColumn();
    $t2 = $db->query("SELECT id FROM users WHERE username='teacher2'")->fetchColumn();
    $cat1 = $db->query("SELECT id FROM categories WHERE slug='lap-trinh'")->fetchColumn();
    $cat2 = $db->query("SELECT id FROM categories WHERE slug='ngoai-ngu'")->fetchColumn();
    $cat3 = $db->query("SELECT id FROM categories WHERE slug='ky-nang-mem'")->fetchColumn();

    // Courses
    $db->exec("INSERT INTO courses (title,description,category_id,instructor_id,price,is_published,is_featured,level,duration_hours) VALUES
        ('Lập Trình Python Cơ Bản','Khóa học Python từ cơ bản đến nâng cao, phù hợp cho người mới bắt đầu. Bao gồm cú pháp cơ bản, OOP, và các thư viện phổ biến.',$cat1,$t1,0,1,1,'beginner',20),
        ('Tiếng Anh Giao Tiếp Nâng Cao','Nâng cao kỹ năng giao tiếp tiếng Anh trong môi trường công sở và cuộc sống hàng ngày.',$cat2,$t2,299000,1,1,'intermediate',15),
        ('Kỹ Năng Thuyết Trình Chuyên Nghiệp','Học cách thuyết trình tự tin, thuyết phục trước đám đông và trong môi trường công việc.',$cat3,$t1,199000,1,0,'beginner',10)
    ");

    $c1 = $db->query("SELECT id FROM courses WHERE title LIKE '%Python%'")->fetchColumn();
    $c2 = $db->query("SELECT id FROM courses WHERE title LIKE '%Anh%'")->fetchColumn();
    $c3 = $db->query("SELECT id FROM courses WHERE title LIKE '%Thuyết%'")->fetchColumn();

    // Sections for Course 1
    $db->exec("INSERT INTO sections (course_id,title,order_num) VALUES
        ($c1,'Chương 1: Giới Thiệu Python',1),
        ($c1,'Chương 2: Kiểu Dữ Liệu & Biến',2),
        ($c2,'Chương 1: Phát Âm & Ngữ Điệu',1),
        ($c2,'Chương 2: Hội Thoại Hàng Ngày',2),
        ($c3,'Chương 1: Nền Tảng Thuyết Trình',1),
        ($c3,'Chương 2: Kỹ Thuật Nâng Cao',2)
    ");

    $s1 = $db->query("SELECT id FROM sections WHERE title LIKE '%Giới Thiệu Python%'")->fetchColumn();
    $s2 = $db->query("SELECT id FROM sections WHERE title LIKE '%Kiểu Dữ Liệu%'")->fetchColumn();
    $s3 = $db->query("SELECT id FROM sections WHERE title LIKE '%Phát Âm%'")->fetchColumn();
    $s4 = $db->query("SELECT id FROM sections WHERE title LIKE '%Hội Thoại%'")->fetchColumn();
    $s5 = $db->query("SELECT id FROM sections WHERE title LIKE '%Nền Tảng%'")->fetchColumn();
    $s6 = $db->query("SELECT id FROM sections WHERE title LIKE '%Kỹ Thuật%'")->fetchColumn();

    // Lessons
    $db->exec("INSERT INTO lessons (section_id,title,content,video_url,lesson_type,order_num,duration_minutes) VALUES
        ($s1,'Python là gì?','Python là ngôn ngữ lập trình bậc cao, dễ học và mạnh mẽ. Được sử dụng rộng rãi trong AI, Data Science, Web Development...','https://www.youtube.com/embed/rfscVS0vtbw','video',1,15),
        ($s1,'Cài Đặt Môi Trường','Hướng dẫn cài đặt Python và VS Code trên Windows/Mac/Linux. Các bước thiết lập môi trường lập trình chuyên nghiệp.','https://www.youtube.com/embed/YYXdXT2l-Gg','video',2,20),
        ($s2,'Biến và Kiểu Dữ Liệu','Trong Python, biến được tạo khi bạn gán giá trị cho nó. Python có các kiểu dữ liệu: int, float, str, bool, list, tuple, dict, set...','https://www.youtube.com/embed/Z1Yd7upQsXY','video',1,25),
        ($s3,'Phát Âm Chuẩn American English','Học cách phát âm các âm khó trong tiếng Anh Mỹ. Các quy tắc nhấn trọng âm và ngữ điệu.','https://www.youtube.com/embed/dQw4w9WgXcQ','video',1,30),
        ($s4,'Hội Thoại Tại Nơi Làm Việc','Các mẫu câu thường dùng trong văn phòng, họp hành, và email công việc.','https://www.youtube.com/embed/dQw4w9WgXcQ','video',1,20),
        ($s4,'Thuyết Phục & Đàm Phán','Kỹ thuật thuyết phục người khác và đàm phán hiệu quả bằng tiếng Anh.','https://www.youtube.com/embed/dQw4w9WgXcQ','video',2,25),
        ($s5,'Nguyên Tắc Vàng Thuyết Trình','7 nguyên tắc vàng để có một bài thuyết trình ấn tượng và thuyết phục người nghe.','https://www.youtube.com/embed/Unzc731iCUY','video',1,20),
        ($s6,'Xử Lý Câu Hỏi Khó','Cách ứng phó với các câu hỏi bất ngờ và tình huống khó trong khi thuyết trình.','https://www.youtube.com/embed/Unzc731iCUY','video',1,15)
    ");

    // Quizzes
    $db->exec("INSERT INTO quizzes (course_id,section_id,title,time_limit,passing_score,order_num) VALUES
        ($c1,$s2,'Bài Kiểm Tra: Python Cơ Bản',30,70,10),
        ($c2,$s4,'Bài Kiểm Tra: Tiếng Anh Giao Tiếp',20,70,10),
        ($c3,$s6,'Bài Kiểm Tra: Kỹ Năng Thuyết Trình',15,70,10)
    ");

    $q1 = $db->query("SELECT id FROM quizzes WHERE title LIKE '%Python%'")->fetchColumn();
    $q2 = $db->query("SELECT id FROM quizzes WHERE title LIKE '%Anh%'")->fetchColumn();
    $q3 = $db->query("SELECT id FROM quizzes WHERE title LIKE '%Thuyết%'")->fetchColumn();

    $db->exec("INSERT INTO quiz_questions (quiz_id,question,option_a,option_b,option_c,option_d,correct_answer) VALUES
        ($q1,'Python được tạo ra bởi ai?','Guido van Rossum','James Gosling','Bjarne Stroustrup','Dennis Ritchie','A'),
        ($q1,'Kiểu dữ liệu nào sau đây là bất biến (immutable) trong Python?','List','Dict','Tuple','Set','C'),
        ($q1,'Hàm nào dùng để in ra màn hình trong Python?','echo()','printf()','print()','console.log()','C'),
        ($q2,'\"How are you?\" có nghĩa là gì?','Bạn ở đâu?','Bạn có khỏe không?','Bạn tên gì?','Bạn làm gì?','B'),
        ($q2,'\"I would like to schedule a meeting\" nghĩa là gì?','Tôi muốn hủy cuộc họp','Tôi muốn lên lịch cuộc họp','Tôi muốn tham dự cuộc họp','Tôi muốn kết thúc cuộc họp','B'),
        ($q2,'Cách lịch sự nhất để từ chối một đề nghị?','No, I dont want','I am afraid I cannot','Not possible','I refuse','B'),
        ($q3,'Quy tắc 7-38-55 nói về điều gì?','Tỷ lệ ngôn ngữ cơ thể trong giao tiếp','Số slide tối đa','Thời gian thuyết trình','Số người tối đa','A'),
        ($q3,'Kỹ thuật nào giúp kiểm soát hồi hộp trước khi thuyết trình?','Uống cà phê thật nhiều','Hít thở sâu và thư giãn','Nói thật nhanh','Tránh nhìn vào khán giả','B'),
        ($q3,'Slide thuyết trình tốt nhất nên có đặc điểm gì?','Nhiều chữ và chi tiết','Đơn giản, ít chữ, nhiều hình ảnh','Không cần slide','Font chữ thật nhỏ','B')
    ");

    // Enroll students
    $st1 = $db->query("SELECT id FROM users WHERE username='student1'")->fetchColumn();
    $st2 = $db->query("SELECT id FROM users WHERE username='student2'")->fetchColumn();
    $st3 = $db->query("SELECT id FROM users WHERE username='student3'")->fetchColumn();

    $db->exec("INSERT IGNORE INTO enrollments (user_id,course_id,payment_status) VALUES
        ($st1,$c1,'free'),($st1,$c2,'paid'),
        ($st2,$c1,'free'),($st2,$c3,'paid'),
        ($st3,$c2,'paid'),($st3,$c3,'paid')
    ");

    // Site settings
    $db->exec("INSERT IGNORE INTO site_settings (setting_key,setting_value) VALUES
        ('site_name','EduViet LMS'),
        ('site_logo','🎓'),
        ('banner_title','Học Trực Tuyến Cùng EduViet'),
        ('banner_subtitle','Nền tảng học tập trực tuyến hàng đầu Việt Nam với hàng trăm khóa học chất lượng cao'),
        ('primary_color','#2563eb'),
        ('nav_links','{\"Trang Chủ\":\"/\",\"Khóa Học\":\"/courses\",\"Giới Thiệu\":\"/about\"}')
    ");
}

