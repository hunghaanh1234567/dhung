<?php
session_start();

require_once 'config.php';
require_once 'footer.php';   // helpers
require_once 'header.php';   // renderLayout
require_once 'login.php';    // pageLogin, pageRegister
require_once 'students.php'; // pageHome, pageCourses, pageCourse, pageLesson, pageQuiz...
require_once 'classes.php';  // pageTeacherCourses, pageManageCourse, pageManageQuiz...
require_once 'sessions.php'; // pageAttendanceList, pageAttendanceSession, pageAiAttendance
require_once 'attendance.php'; // pageStudentCheckin
require_once 'users.php';    // pageAdminUsers, pageAdminCategories...
require_once 'checkin.php';  // pageMessages, pageMessageThread, pageQrCheckin

// ============================================================
// ROUTING
// ============================================================
$action = $_GET['action'] ?? 'home';
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST actions
if ($method === 'POST') {
    handlePost($action);
}

function handlePost($action) {
    $db = getDB();
    switch($action) {
        case 'login':
            $email = trim($_POST['email'] ?? '');
            $pass = $_POST['password'] ?? '';
            $stmt = $db->prepare("SELECT * FROM users WHERE (email=? OR username=?) AND is_active=1");
            $stmt->execute([$email,$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($pass, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                // Nếu đang có QR token chờ thì redirect đến trang điểm danh QR
                if (!empty($_SESSION['qr_token_pending'])) {
                    $tok = $_SESSION['qr_token_pending'];
                    unset($_SESSION['qr_token_pending']);
                    redirect('?action=qr_checkin&token=' . urlencode($tok));
                }
                redirect('?action=dashboard');
            }
            $_SESSION['error'] = 'Email hoặc mật khẩu không đúng!';
            redirect('?action=login');
            break;

        case 'register':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $pass = $_POST['password'] ?? '';
            $name = trim($_POST['full_name'] ?? '');
            if (!$username || !$email || !$pass || !$name) {
                $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin!';
                redirect('?action=register');
                break;
            }
            try {
                $stmt = $db->prepare("INSERT INTO users (username,email,password,full_name) VALUES (?,?,?,?)");
                $stmt->execute([$username,$email,password_hash($pass,PASSWORD_DEFAULT),$name]);
                $_SESSION['success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                redirect('?action=login');
            } catch(Exception $e) {
                $_SESSION['error'] = 'Email hoặc tên đăng nhập đã tồn tại!';
                redirect('?action=register');
            }
            break;

        case 'enroll':
            if (!isLoggedIn()) { redirect('?action=login'); break; }
            $courseId = (int)($_POST['course_id'] ?? 0);
            $db->prepare("SELECT * FROM courses WHERE id=?")->execute([$courseId]);
            $course = $db->prepare("SELECT * FROM courses WHERE id=?");
            $course->execute([$courseId]);
            $c = $course->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $stmt = $db->prepare("INSERT IGNORE INTO enrollments (user_id,course_id,payment_status) VALUES (?,?,?)");
                $stmt->execute([$_SESSION['user_id'], $courseId, $c['price']>0?'paid':'free']);
                $_SESSION['success'] = 'Ghi danh thành công!';
            }
            redirect('?action=course&id='.$courseId);
            break;

        case 'complete_lesson':
            if (!isLoggedIn()) break;
            $lessonId = (int)($_POST['lesson_id'] ?? 0);
            $courseId = (int)($_POST['course_id'] ?? 0);
            $stmt = $db->prepare("INSERT INTO lesson_progress (user_id,lesson_id,completed,completed_at) VALUES (?,?,1,NOW()) ON DUPLICATE KEY UPDATE completed=1, completed_at=NOW()");
            $stmt->execute([$_SESSION['user_id'], $lessonId]);
            checkAndIssueCertificate($_SESSION['user_id'], $courseId);
            header('Content-Type: application/json');
            echo json_encode(['progress' => getCourseProgress($_SESSION['user_id'], $courseId)]);
            exit;
            break;

        case 'submit_quiz':
            if (!isLoggedIn()) break;
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            $answers = $_POST['answers'] ?? [];
            $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=?");
            $stmt->execute([$quizId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $correct = 0;
            foreach($questions as $q) {
                if (isset($answers[$q['id']]) && strtoupper($answers[$q['id']]) === $q['correct_answer']) $correct++;
            }
            $score = count($questions) > 0 ? round($correct/count($questions)*100) : 0;
            $quiz = $db->prepare("SELECT * FROM quizzes WHERE id=?");
            $quiz->execute([$quizId]);
            $q = $quiz->fetch(PDO::FETCH_ASSOC);
            $passed = $score >= $q['passing_score'] ? 1 : 0;
            $stmt2 = $db->prepare("INSERT INTO quiz_attempts (user_id,quiz_id,score,passed,answers) VALUES (?,?,?,?,?)");
            $stmt2->execute([$_SESSION['user_id'],$quizId,$score,$passed,json_encode($answers)]);
            redirect("?action=quiz_result&quiz_id=$quizId&score=$score&passed=$passed");
            break;

        case 'save_course':
            if (!hasRole('teacher')) break;
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $catId = (int)($_POST['category_id'] ?? 0);
            $price = (float)($_POST['price'] ?? 0);
            $level = $_POST['level'] ?? 'beginner';
            $featured = isset($_POST['is_featured']) ? 1 : 0;
            $published = isset($_POST['is_published']) ? 1 : 0;
            $thumbnail = '';
            if (!empty($_FILES['thumbnail']['name'])) {
                $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $filename = uniqid().'.'.$ext;
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], UPLOAD_DIR.$filename);
                $thumbnail = $filename;
            }
            if ($id) {
                $sql = "UPDATE courses SET title=?,description=?,category_id=?,price=?,level=?,is_featured=?,is_published=?";
                $params = [$title,$desc,$catId,$price,$level,$featured,$published];
                if ($thumbnail) { $sql .= ",thumbnail=?"; $params[] = $thumbnail; }
                $sql .= " WHERE id=?"; $params[] = $id;
                $db->prepare($sql)->execute($params);
            } else {
                $stmt = $db->prepare("INSERT INTO courses (title,description,category_id,instructor_id,price,level,is_featured,is_published,thumbnail) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$title,$desc,$catId,$_SESSION['user_id'],$price,$level,$featured,$published,$thumbnail]);
                $id = $db->lastInsertId();
            }
            redirect("?action=manage_course&id=$id");
            break;

        case 'save_section':
            if (!hasRole('teacher')) break;
            $courseId = (int)($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $stmt = $db->prepare("INSERT INTO sections (course_id,title,order_num) VALUES (?,?,(SELECT COALESCE(MAX(order_num),0)+1 FROM sections WHERE course_id=?))");
            $stmt->execute([$courseId,$title,$courseId]);
            redirect("?action=manage_course&id=$courseId");
            break;

        case 'save_lesson':
            if (!hasRole('teacher')) break;
            $sectionId = (int)($_POST['section_id'] ?? 0);
            $courseId = (int)($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $videoUrl = trim($_POST['video_url'] ?? '');
            $type = $_POST['lesson_type'] ?? 'text';
            $duration = (int)($_POST['duration_minutes'] ?? 0);
            $fileUrl = ''; $fileName = '';
            if (!empty($_FILES['lesson_file']['name'])) {
                $ext = pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid().'.'.$ext;
                move_uploaded_file($_FILES['lesson_file']['tmp_name'], UPLOAD_DIR.$filename);
                $fileUrl = $filename;
                $fileName = $_FILES['lesson_file']['name'];
            }
            $stmt = $db->prepare("INSERT INTO lessons (section_id,title,content,video_url,file_url,file_name,lesson_type,order_num,duration_minutes) VALUES (?,?,?,?,?,?,?,(SELECT COALESCE(MAX(order_num),0)+1 FROM lessons WHERE section_id=?),?)");
            $stmt->execute([$sectionId,$title,$content,$videoUrl,$fileUrl,$fileName,$type,$sectionId,$duration]);
            redirect("?action=manage_course&id=$courseId");
            break;

        case 'save_quiz':
            if (!hasRole('teacher')) break;
            $courseId = (int)($_POST['course_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $timeLimit = (int)($_POST['time_limit'] ?? 30);
            $passingScore = (int)($_POST['passing_score'] ?? 70);
            $stmt = $db->prepare("INSERT INTO quizzes (course_id,section_id,title,time_limit,passing_score) VALUES (?,?,?,?,?)");
            $stmt->execute([$courseId,$sectionId,$title,$timeLimit,$passingScore]);
            $quizId = $db->lastInsertId();
            $questions = $_POST['questions'] ?? [];
            foreach($questions as $q) {
                if (empty($q['question'])) continue;
                $stmt2 = $db->prepare("INSERT INTO quiz_questions (quiz_id,question,option_a,option_b,option_c,option_d,correct_answer) VALUES (?,?,?,?,?,?,?)");
                $stmt2->execute([$quizId,$q['question'],$q['a'],$q['b'],$q['c'],$q['d'],$q['correct']]);
            }
            redirect("?action=manage_course&id=$courseId");
            break;

        case 'admin_update_user':
            if (!hasRole('admin')) break;
            $userId = (int)($_POST['user_id'] ?? 0);
            $role = $_POST['role'] ?? 'student';
            $active = (int)($_POST['is_active'] ?? 1);
            $db->prepare("UPDATE users SET role=?,is_active=? WHERE id=?")->execute([$role,$active,$userId]);
            redirect('?action=admin_users');
            break;

        case 'admin_add_user':
            if (!hasRole('admin')) break;
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $pass = $_POST['password'] ?? 'student123';
            $name = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'student';
            try {
                $stmt = $db->prepare("INSERT INTO users (username,email,password,full_name,role) VALUES (?,?,?,?,?)");
                $stmt->execute([$username,$email,password_hash($pass,PASSWORD_DEFAULT),$name,$role]);
                $_SESSION['success'] = 'Thêm người dùng thành công!';
            } catch(Exception $e) {
                $_SESSION['error'] = 'Email hoặc tên đăng nhập đã tồn tại!';
            }
            redirect('?action=admin_users');
            break;

        case 'admin_save_category':
            if (!hasRole('admin')) break;
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '📚');
            $desc = trim($_POST['description'] ?? '');
            $slug = slugify($name);
            try {
                $db->prepare("INSERT INTO categories (name,slug,icon,description) VALUES (?,?,?,?)")->execute([$name,$slug,$icon,$desc]);
            } catch(Exception $e) {}
            redirect('?action=admin_categories');
            break;

        case 'admin_save_settings':
            if (!hasRole('admin')) break;
            foreach($_POST as $key => $val) {
                if (in_array($key, ['site_name','banner_title','banner_subtitle','primary_color','site_logo'])) {
                    $db->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$key,$val]);
                }
            }
            $_SESSION['success'] = 'Cài đặt đã được lưu!';
            redirect('?action=admin_settings');
            break;

            case 'edit_attendance_session':
                if (!hasRole('teacher')) break;
                $sessId = (int)($_POST['session_id'] ?? 0);
                $name   = trim($_POST['session_name'] ?? '');
                $date   = $_POST['session_date'] ?? date('Y-m-d');
                $courseId = (int)($_POST['course_id'] ?? 0);
                if ($sessId && $name && $date) {
                    $db->prepare("UPDATE attendance_sessions SET session_name=?, session_date=?, course_id=? WHERE id=?")
                       ->execute([$name, $date, $courseId, $sessId]);
                    $_SESSION['success'] = 'Đã cập nhật buổi điểm danh!';
                }
                redirect('?action=attendance_list');
                break;
    
            case 'delete_attendance_session':
                if (!hasRole('teacher')) break;
                $sessId = (int)($_POST['session_id'] ?? 0);
                if ($sessId) {
                    $db->prepare("DELETE FROM attendance_records WHERE session_id=?")->execute([$sessId]);
                    $db->prepare("DELETE FROM attendance_sessions WHERE id=?")->execute([$sessId]);
                    $_SESSION['success'] = 'Đã xóa buổi điểm danh!';
                }
                redirect('?action=attendance_list');
                break;

        case 'save_attendance':
            if (!hasRole('teacher')) break;
            $sessId = (int)($_POST['session_id'] ?? 0);
            $records = $_POST['attendance'] ?? [];
            foreach($records as $userId => $status) {
                $checkIn = $status==='present' ? date('Y-m-d H:i:s') : null;
                $db->prepare("UPDATE attendance_records SET status=?,check_in_time=? WHERE session_id=? AND user_id=?")->execute([$status,$checkIn,$sessId,$userId]);
            }
            $_SESSION['success'] = 'Đã lưu điểm danh!';
            redirect("?action=attendance_session&id=$sessId");
            break;
        case 'logout':
            session_destroy();
            redirect('?action=home');
            break;
        case 'send_message':
            if (!isLoggedIn()) break;
            $u = currentUser();
            $receiverId = (int)($_POST['receiver_id'] ?? 0);
            $courseId = (int)($_POST['course_id'] ?? 0) ?: null;
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? '');
            $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
            if ($receiverId && $body) {
                $db->prepare("INSERT INTO messages (sender_id,receiver_id,course_id,subject,body,parent_id) VALUES (?,?,?,?,?,?)")
                   ->execute([$u['id'], $receiverId, $courseId, $subject, $body, $parentId]);
                $_SESSION['success'] = 'Tin nhắn đã được gửi!';
            } else {
                $_SESSION['error'] = 'Vui lòng nhập nội dung tin nhắn.';
            }
            redirect('?action=messages');
            break;
        case 'mark_message_read':
            if (!isLoggedIn()) break;
            $u = currentUser();
            $msgId = (int)($_POST['msg_id'] ?? 0);
            $db->prepare("UPDATE messages SET is_read=1 WHERE id=? AND receiver_id=?")->execute([$msgId, $u['id']]);
            break;
            case 'add_question':
                if (!hasRole('teacher')) break;
                $quizId = (int)($_POST['quiz_id'] ?? 0);
                $courseId = (int)($_POST['course_id'] ?? 0);
                $question = trim($_POST['question'] ?? '');
                $a = trim($_POST['option_a'] ?? '');
                $b = trim($_POST['option_b'] ?? '');
                $c = trim($_POST['option_c'] ?? '');
                $d = trim($_POST['option_d'] ?? '');
                $correct = strtoupper($_POST['correct_answer'] ?? 'A');
                if ($question && $a && $b && $c && $d) {
                    $stmt = $db->prepare("INSERT INTO quiz_questions (quiz_id,question,option_a,option_b,option_c,option_d,correct_answer) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$quizId,$question,$a,$b,$c,$d,$correct]);
                    $_SESSION['success'] = 'Đã thêm câu hỏi thành công!';
                } else {
                    $_SESSION['error'] = 'Vui lòng điền đầy đủ nội dung câu hỏi và các đáp án!';
                }
                redirect("?action=manage_quiz&quiz_id=$quizId&course_id=$courseId");
                break;
            
            case 'edit_question':
                if (!hasRole('teacher')) break;
                $qId = (int)($_POST['question_id'] ?? 0);
                $quizId = (int)($_POST['quiz_id'] ?? 0);
                $courseId = (int)($_POST['course_id'] ?? 0);
                $question = trim($_POST['question'] ?? '');
                $a = trim($_POST['option_a'] ?? '');
                $b = trim($_POST['option_b'] ?? '');
                $c = trim($_POST['option_c'] ?? '');
                $d = trim($_POST['option_d'] ?? '');
                $correct = strtoupper($_POST['correct_answer'] ?? 'A');
                $stmt = $db->prepare("UPDATE quiz_questions SET question=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_answer=? WHERE id=?");
                $stmt->execute([$question,$a,$b,$c,$d,$correct,$qId]);
                $_SESSION['success'] = 'Đã cập nhật câu hỏi!';
                redirect("?action=manage_quiz&quiz_id=$quizId&course_id=$courseId");
                break;
            
            case 'delete_question':
                if (!hasRole('teacher')) break;
                $qId = (int)($_POST['question_id'] ?? 0);
                $quizId = (int)($_POST['quiz_id'] ?? 0);
                $courseId = (int)($_POST['course_id'] ?? 0);
                $db->prepare("DELETE FROM quiz_questions WHERE id=?")->execute([$qId]);
                $_SESSION['success'] = 'Đã xóa câu hỏi!';
                redirect("?action=manage_quiz&quiz_id=$quizId&course_id=$courseId");
                break;
            
            case 'edit_quiz_info':
                if (!hasRole('teacher')) break;
                $quizId = (int)($_POST['quiz_id'] ?? 0);
                $courseId = (int)($_POST['course_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $timeLimit = (int)($_POST['time_limit'] ?? 30);
                $passingScore = (int)($_POST['passing_score'] ?? 70);
                $db->prepare("UPDATE quizzes SET title=?,time_limit=?,passing_score=? WHERE id=?")->execute([$title,$timeLimit,$passingScore,$quizId]);
                $_SESSION['success'] = 'Đã cập nhật thông tin bài kiểm tra!';
                redirect("?action=manage_quiz&quiz_id=$quizId&course_id=$courseId");
                break;
            
            case 'delete_quiz':
                if (!hasRole('teacher')) break;
                $quizId = (int)($_POST['quiz_id'] ?? 0);
                $courseId = (int)($_POST['course_id'] ?? 0);
                $db->prepare("DELETE FROM quiz_questions WHERE quiz_id=?")->execute([$quizId]);
                $db->prepare("DELETE FROM quizzes WHERE id=?")->execute([$quizId]);
                $_SESSION['success'] = 'Đã xóa bài kiểm tra!';
                redirect("?action=manage_course&id=$courseId");
                break;
                case 'ai_face_check':
                    handleAiFaceCheck();
                    break;

                case 'student_register_face':
                    if (!isLoggedIn()) break;
                    $imageData = trim($_POST['image_data'] ?? '');
                    $db->prepare("INSERT INTO face_profiles (user_id, profile_data, registered_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE profile_data=VALUES(profile_data), registered_at=NOW()")
                        ->execute([$_SESSION['user_id'], $imageData]);
                    $_SESSION['success'] = 'Đã đăng ký khuôn mặt thành công!';
                    redirect('?action=student_checkin');
                    break;
             
                    case 'student_save_checkin':
                        if (!isLoggedIn()) break;
                        $sessId = (int)($_POST['session_id'] ?? 0);
                        $verified = (int)($_POST['ai_verified'] ?? 0);
                        header('Content-Type: application/json');
                        if ($sessId && $verified) {
                            // Kiểm tra học viên có ghi danh môn học của buổi này không
                            $chkEnroll = $db->prepare("
                                SELECT e.id FROM enrollments e
                                JOIN attendance_sessions s ON s.course_id = e.course_id
                                WHERE s.id = ? AND e.user_id = ?
                            ");
                            $chkEnroll->execute([$sessId, $_SESSION['user_id']]);
                            if (!$chkEnroll->fetchColumn()) {
                                echo json_encode(['success' => false, 'message' => 'Bạn chưa ghi danh môn học này!']);
                                exit;
                            }
                            // Kiểm tra có record điểm danh không, nếu chưa thì insert
                            $chkRec = $db->prepare("SELECT id FROM attendance_records WHERE session_id=? AND user_id=?");
                            $chkRec->execute([$sessId, $_SESSION['user_id']]);
                            if (!$chkRec->fetchColumn()) {
                                $db->prepare("INSERT INTO attendance_records (session_id, user_id, status) VALUES (?, ?, 'absent')")
                                    ->execute([$sessId, $_SESSION['user_id']]);
                            }
                            $db->prepare("UPDATE attendance_records SET status='present', check_in_time=CURRENT_TIMESTAMP, ai_verified=1 WHERE session_id=? AND user_id=?")
                                ->execute([$sessId, $_SESSION['user_id']]);
                            echo json_encode(['success' => true, 'message' => 'Điểm danh thành công!']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Xác minh AI thất bại!']);
                        }
                        exit;
                        break;
                        case 'create_attendance':
                            if (!hasRole('teacher')) break;
                            $courseId = (int)($_POST['course_id'] ?? 0);
                            $name = trim($_POST['session_name'] ?? '');
                            $date = $_POST['session_date'] ?? date('Y-m-d');
                
                            if (!$courseId || !$name || !$date) {
                                $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin buổi học!';
                                redirect('?action=attendance_list');
                                break;
                            }
                
                            // Kiểm tra khóa học tồn tại
                            $chkCourse = $db->prepare("SELECT id FROM courses WHERE id=?");
                            $chkCourse->execute([$courseId]);
                            if (!$chkCourse->fetchColumn()) {
                                $_SESSION['error'] = 'Khóa học không tồn tại!';
                                redirect('?action=attendance_list');
                                break;
                            }
                
                            $stmt = $db->prepare("INSERT INTO attendance_sessions (course_id,instructor_id,session_name,session_date) VALUES (?,?,?,?)");
                            $stmt->execute([$courseId, $_SESSION['user_id'], $name, $date]);
                            $sessId = $db->lastInsertId();
                
                            // Auto-add tất cả học viên đã ghi danh môn này
                            $students = $db->prepare("SELECT user_id FROM enrollments WHERE course_id=?");
                            $students->execute([$courseId]);
                            $added = 0;
                            foreach ($students->fetchAll() as $s) {
                                $db->prepare("INSERT IGNORE INTO attendance_records (session_id,user_id,status) VALUES (?,?,'absent')")
                                   ->execute([$sessId, $s['user_id']]);
                                $added++;
                            }
                
                            $_SESSION['success'] = "Đã tạo buổi học \"$name\" với $added học viên!";
                            redirect("?action=attendance_session&id=$sessId");
                            break;

        case 'generate_qr':
            // Giáo viên tạo QR token cho buổi học
            if (!hasRole('teacher')) break;
            $sessId = (int)($_POST['session_id'] ?? 0);
            $minutes = max(1, min(60, (int)($_POST['qr_minutes'] ?? 15)));
            if ($sessId) {
                $token = bin2hex(random_bytes(24));
                // Dùng NOW() của MySQL để tránh lệch timezone giữa PHP và MySQL
                $db->prepare("DELETE FROM qr_tokens WHERE session_id=?")->execute([$sessId]);
                $db->prepare("INSERT INTO qr_tokens (session_id, token, expires_at) VALUES (?, ?, NOW() + INTERVAL ? MINUTE)")
                   ->execute([$sessId, $token, $minutes]);
                // Lấy lại expires_at thực tế từ MySQL để trả về client
                $row = $db->prepare("SELECT expires_at, NOW() as server_now FROM qr_tokens WHERE token=?");
                $row->execute([$token]);
                $row = $row->fetch(PDO::FETCH_ASSOC);
                $expiresAt = $row['expires_at'];
                $serverNow = $row['server_now'];
                header('Content-Type: application/json');
                $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?action=qr_checkin&token=' . $token;
                echo json_encode(['success' => true, 'token' => $token, 'url' => $url, 'expires_at' => $expiresAt, 'server_now' => $serverNow, 'minutes' => $minutes]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session không hợp lệ']);
            }
            exit;

        case 'qr_checkin':
            // Học viên điểm danh bằng QR
            if (!isLoggedIn()) { redirect('?action=login'); return; }
            $token = trim($_GET['token'] ?? $_POST['token'] ?? '');
            $db2 = getDB();
            header('Content-Type: application/json');
            if (!$token) { echo json_encode(['success'=>false,'message'=>'Token không hợp lệ']); exit; }
            // Tìm token còn hiệu lực
            $qr = $db2->prepare("SELECT q.*, s.course_id FROM qr_tokens q JOIN attendance_sessions s ON s.id=q.session_id WHERE q.token=? AND q.expires_at > NOW()");
            $qr->execute([$token]);
            $qr = $qr->fetch(PDO::FETCH_ASSOC);
            if (!$qr) { echo json_encode(['success'=>false,'message'=>'Mã QR đã hết hạn hoặc không hợp lệ!']); exit; }
            $userId = $_SESSION['user_id'];
            $sessId = $qr['session_id'];
            // Kiểm tra enrollment
            $enroll = $db2->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
            $enroll->execute([$userId, $qr['course_id']]);
            if (!$enroll->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'Bạn chưa đăng ký khóa học này!']); exit; }
            // Upsert attendance record
            $chk = $db2->prepare("SELECT id,status FROM attendance_records WHERE session_id=? AND user_id=?");
            $chk->execute([$sessId, $userId]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                if ($existing['status'] === 'present') { echo json_encode(['success'=>true,'message'=>'Bạn đã điểm danh rồi!','already'=>true]); exit; }
                $db2->prepare("UPDATE attendance_records SET status='present', check_in_time=NOW(), ai_verified=0 WHERE session_id=? AND user_id=?")->execute([$sessId, $userId]);
            } else {
                $db2->prepare("INSERT INTO attendance_records (session_id,user_id,status,check_in_time,ai_verified) VALUES (?,?,'present',NOW(),0)")->execute([$sessId, $userId]);
            }
            echo json_encode(['success'=>true,'message'=>'Điểm danh thành công! ✅']);
            exit;
    }
}

// ============================================================
// PAGE RENDERING
// ============================================================
renderPage($action);

function renderPage($action) {
    $db = getDB();
    ob_start();
    switch($action) {
        case 'home': pageHome($db); break;
        case 'courses': pageCourses($db); break;
        case 'course': pageCourse($db); break;
        case 'lesson': pageLesson($db); break;
        case 'quiz': pageQuiz($db); break;
        case 'quiz_result': pageQuizResult($db); break;
        case 'login': pageLogin(); break;
        case 'register': pageRegister(); break;
        case 'dashboard': pageDashboard($db); break;
        case 'certificate': pageCertificate($db); break;
        case 'create_course': pageCreateCourse($db); break;
        case 'manage_course': pageManageCourse($db); break;
        case 'teacher_courses': pageTeacherCourses($db); break;
        case 'teacher_students': pageTeacherStudents($db); break;
        case 'create_attendance': pageCreateAttendance($db); break;
        case 'attendance_session': pageAttendanceSession($db); break;
        case 'attendance_list': pageAttendanceList($db); break;
        case 'ai_attendance': pageAiAttendance($db); break;
        case 'api_session_students': handleApiSessionStudents($db); break;
        case 'admin_users': pageAdminUsers($db); break;
        case 'admin_categories': pageAdminCategories($db); break;
        case 'admin_settings': pageAdminSettings($db); break;
        case 'admin_courses': pageAdminCourses($db); break;
        case 'manage_quiz': pageManageQuiz($db); break;
        case 'student_checkin': pageStudentCheckin($db); break;
        case 'student_checkin_history': pageStudentCheckinHistory($db); break;
        case 'qr_checkin': pageQrCheckin($db); break;
        case 'messages': pageMessages($db); break;
        case 'message_thread': pageMessageThread($db); break;
        default: pageHome($db);
    }
    $content = ob_get_clean();
    renderLayout($content, $action);
}

