<?php
// ============================================================
// index_patch.php
// Hướng dẫn tích hợp vào index.php hiện có
// ============================================================

// ── BƯỚC 1: Thêm require vào đầu index.php ──────────────────
// Thêm dòng này cùng với các require_once khác:
//   require_once 'exam.php';


// ── BƯỚC 2: Thêm vào hàm handlePost() — switch($action) ─────
// Dán vào trước dòng `case 'generate_qr':` hoặc cuối switch:

/*
        case 'save_exam':
            if (!hasRole('teacher')) break;
            $db = getDB();
            $examId  = (int)($_POST['exam_id'] ?? 0);
            $title   = trim($_POST['title'] ?? '');
            $courseId= (int)($_POST['course_id'] ?? 0);
            $timeLimit   = max(5, (int)($_POST['time_limit'] ?? 60));
            $passingScore= min(100, max(0, (int)($_POST['passing_score'] ?? 50)));
            $startTime   = !empty($_POST['start_time']) ? date('Y-m-d H:i:s', strtotime($_POST['start_time'])) : null;
            $endTime     = !empty($_POST['end_time'])   ? date('Y-m-d H:i:s', strtotime($_POST['end_time']))   : null;
            $desc        = trim($_POST['description'] ?? '');
            $shuffle_q   = isset($_POST['shuffle_questions']) ? 1 : 0;
            $shuffle_a   = isset($_POST['shuffle_answers'])   ? 1 : 0;
            $show_result = isset($_POST['show_result'])       ? 1 : 0;
            $is_active   = isset($_POST['is_active'])         ? 1 : 0;

            if (!$title) { $_SESSION['error']='Vui lòng nhập tên kỳ thi!'; redirect('?action=exam_list'); break; }

            if ($examId) {
                $db->prepare("UPDATE semester_exams SET title=?,time_limit=?,passing_score=?,start_time=?,end_time=?,description=?,shuffle_questions=?,shuffle_answers=?,show_result=?,is_active=? WHERE id=?")
                   ->execute([$title,$timeLimit,$passingScore,$startTime,$endTime,$desc,$shuffle_q,$shuffle_a,$show_result,$is_active,$examId]);
                $_SESSION['success'] = 'Đã cập nhật kỳ thi!';
                redirect("?action=manage_exam&id=$examId");
            } else {
                if (!$courseId) { $_SESSION['error']='Vui lòng chọn khóa học!'; redirect('?action=exam_list'); break; }
                $db->prepare("INSERT INTO semester_exams (course_id,instructor_id,title,description,time_limit,passing_score,start_time,end_time,shuffle_questions,shuffle_answers,show_result,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$courseId,$_SESSION['user_id'],$title,$desc,$timeLimit,$passingScore,$startTime,$endTime,$shuffle_q,$shuffle_a,$show_result,$is_active]);
                $newId = $db->lastInsertId();
                $_SESSION['success'] = 'Đã tạo kỳ thi! Hãy thêm câu hỏi.';
                redirect("?action=manage_exam&id=$newId");
            }
            break;

        case 'save_exam_question':
            if (!hasRole('teacher')) break;
            $db = getDB();
            $examId    = (int)($_POST['exam_id'] ?? 0);
            $questionId= (int)($_POST['question_id'] ?? 0);
            $question  = trim($_POST['question'] ?? '');
            $optA      = trim($_POST['option_a'] ?? '');
            $optB      = trim($_POST['option_b'] ?? '');
            $optC      = trim($_POST['option_c'] ?? '');
            $optD      = trim($_POST['option_d'] ?? '');
            $correct   = strtoupper(trim($_POST['correct_answer'] ?? 'A'));
            $points    = max(1, (int)($_POST['points'] ?? 1));
            $difficulty= in_array($_POST['difficulty']??'medium',['easy','medium','hard'])?$_POST['difficulty']:'medium';
            $explanation = trim($_POST['explanation'] ?? '');

            if (!$question || !$optA || !$optB || !$optC || !$optD) {
                $_SESSION['error'] = 'Vui lòng điền đầy đủ câu hỏi và đáp án!';
                redirect("?action=manage_exam&id=$examId");
                break;
            }

            if ($questionId) {
                $db->prepare("UPDATE exam_questions SET question=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_answer=?,points=?,difficulty=?,explanation=? WHERE id=?")
                   ->execute([$question,$optA,$optB,$optC,$optD,$correct,$points,$difficulty,$explanation,$questionId]);
            } else {
                $db->prepare("INSERT INTO exam_questions (exam_id,question,option_a,option_b,option_c,option_d,correct_answer,points,difficulty,explanation) VALUES (?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$examId,$question,$optA,$optB,$optC,$optD,$correct,$points,$difficulty,$explanation]);
            }
            $_SESSION['success'] = $questionId ? 'Đã cập nhật câu hỏi!' : 'Đã thêm câu hỏi!';
            redirect("?action=manage_exam&id=$examId");
            break;

        case 'delete_exam_question':
            if (!hasRole('teacher')) break;
            $db = getDB();
            $questionId = (int)($_POST['question_id'] ?? 0);
            $examId     = (int)($_POST['exam_id'] ?? 0);
            $db->prepare("DELETE FROM exam_questions WHERE id=?")->execute([$questionId]);
            $_SESSION['success'] = 'Đã xóa câu hỏi!';
            redirect("?action=manage_exam&id=$examId");
            break;

        case 'toggle_exam':
            if (!hasRole('teacher')) break;
            $db = getDB();
            $examId = (int)($_POST['exam_id'] ?? 0);
            $db->prepare("UPDATE semester_exams SET is_active = 1 - is_active WHERE id=?")->execute([$examId]);
            redirect('?action=exam_list');
            break;

        case 'submit_semester_exam':
            if (!isLoggedIn()) { redirect('?action=login'); break; }
            $db = getDB();
            $examId  = (int)($_POST['exam_id'] ?? 0);
            $answers = $_POST['answers'] ?? [];
            $startTs = (int)($_POST['start_ts'] ?? time());

            $exam = $db->prepare("SELECT * FROM semester_exams WHERE id=? AND is_active=1");
            $exam->execute([$examId]);
            $exam = $exam->fetch(PDO::FETCH_ASSOC);
            if (!$exam) { redirect('?action=student_exams'); break; }

            // Chống nộp lại
            $existing = $db->prepare("SELECT id FROM exam_attempts WHERE exam_id=? AND user_id=?");
            $existing->execute([$examId, $_SESSION['user_id']]);
            if ($existing->fetchColumn()) {
                $_SESSION['error'] = 'Bạn đã nộp bài thi này rồi!';
                redirect('?action=student_exams');
                break;
            }

            // Chấm điểm
            $questions = $db->prepare("SELECT * FROM exam_questions WHERE exam_id=?");
            $questions->execute([$examId]);
            $questions = $questions->fetchAll(PDO::FETCH_ASSOC);

            $totalPoints = 0;
            $earnedPoints = 0;
            $correctCount = 0;
            foreach ($questions as $q) {
                $totalPoints += $q['points'];
                $userAns = strtoupper(trim($answers[$q['id']] ?? ''));
                if ($userAns === $q['correct_answer']) {
                    $earnedPoints += $q['points'];
                    $correctCount++;
                }
            }

            $score = $totalPoints > 0 ? round($earnedPoints/$totalPoints*100, 2) : 0;
            $passed = $score >= $exam['passing_score'] ? 1 : 0;
            $duration = max(0, (int)round((time()-$startTs)/60));

            $stmt = $db->prepare("INSERT INTO exam_attempts (exam_id,user_id,score,correct_count,total_questions,passed,answers_json,duration_minutes) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$examId,$_SESSION['user_id'],$score,$correctCount,count($questions),$passed,json_encode($answers),$duration]);
            $attemptId = $db->lastInsertId();

            redirect("?action=exam_result&attempt_id=$attemptId");
            break;
*/


// ── BƯỚC 3: Thêm vào hàm renderPage() — switch($action) ─────
// Dán vào trong switch($action) của renderPage():

/*
        case 'exam_list':        pageExamList($db);        break;
        case 'manage_exam':      pageManageExam($db);      break;
        case 'exam_results':     pageExamResults($db);     break;
        case 'student_exams':    pageStudentExams($db);    break;
        case 'do_exam':          pageDoExam($db);          break;
        case 'exam_result':      pageExamResult($db);      break;
        case 'admin_exams':      pageAdminExams($db);      break;
        case 'export_exam_results': handleExportExamResults($db); break;
*/


// ── BƯỚC 4: Thêm link vào sidebar Admin trong users.php ──────
// Trong hàm renderAdminSidebar(), thêm vào mục "Nội Dung":
/*
        <a href="?action=admin_exams" class="<?= $active==='admin_exams'?'active':'' ?>">📝 Kỳ Thi Cuối Kỳ</a>
*/

// Và thêm link cho học viên vào header/nav:
/*
        <a href="?action=student_exams">📝 Kỳ Thi</a>
*/

// Và link cho giáo viên:
/*
        <a href="?action=exam_list">📝 Quản Lý Kỳ Thi</a>
*/
