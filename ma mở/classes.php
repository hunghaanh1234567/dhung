<?php
function pageTeacherCourses($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $user = currentUser();
    $where = $user['role']==='admin' ? "1=1" : "c.instructor_id={$user['id']}";
    $courses = $db->query("SELECT c.*, cat.name as cat_name, (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) as enrolled FROM courses c LEFT JOIN categories cat ON c.category_id=cat.id WHERE $where ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h1 style="font-size:1.75rem;font-weight:800;">📚 Khóa Học Của Tôi</h1>
        <a href="?action=create_course" class="btn btn-primary">+ Tạo Khóa Học Mới</a>
    </div>
    <?php if (empty($courses)): ?>
    <div class="alert alert-info">Bạn chưa có khóa học nào. <a href="?action=create_course">Tạo ngay!</a></div>
    <?php else: ?>
    <div class="table-wrapper">
    <table>
        <thead><tr><th>Tên Khóa Học</th><th>Danh Mục</th><th>Học Viên</th><th>Giá</th><th>Trạng Thái</th><th>Hành Động</th></tr></thead>
        <tbody>
        <?php foreach($courses as $c): ?>
        <tr>
            <td style="font-weight:600;"><?= h($c['title']) ?></td>
            <td><?= h($c['cat_name'] ?? '-') ?></td>
            <td><?= $c['enrolled'] ?></td>
            <td><?= $c['price'] > 0 ? number_format($c['price'],0,',','.') . 'đ' : '<span class="badge badge-success">Miễn phí</span>' ?></td>
            <td><?= $c['is_published'] ? '<span class="badge badge-success">Đã xuất bản</span>' : '<span class="badge badge-warning">Nháp</span>' ?></td>
            <td style="display:flex;gap:8px;">
                <a href="?action=manage_course&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">✏️ Quản lý</a>
                <a href="?action=course&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">👁 Xem</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php
}

function pageCreateCourse($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="max-width:720px;padding-top:32px;padding-bottom:48px;">
    <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:24px;">✨ Tạo Khóa Học Mới</h1>
    <div class="card">
        <form method="post" action="?action=save_course" enctype="multipart/form-data">
            <div class="form-group"><label>Tên Khóa Học *</label><input type="text" name="title" required placeholder="Ví dụ: Lập Trình Python Từ Cơ Bản"></div>
            <div class="form-group"><label>Mô Tả</label><textarea name="description" rows="4" placeholder="Mô tả nội dung và mục tiêu của khóa học..."></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Danh Mục</label>
                    <select name="category_id">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= h($cat['icon'].' '.$cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cấp Độ</label>
                    <select name="level">
                        <option value="beginner">Cơ Bản</option>
                        <option value="intermediate">Trung Cấp</option>
                        <option value="advanced">Nâng Cao</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Giá (VNĐ, để 0 nếu miễn phí)</label><input type="number" name="price" value="0" min="0"></div>
            <div class="form-group"><label>Ảnh Bìa (Thumbnail)</label><input type="file" name="thumbnail" accept="image/*"></div>
            <div style="display:flex;gap:24px;margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                    <input type="checkbox" name="is_published" value="1"> Xuất bản ngay
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                    <input type="checkbox" name="is_featured" value="1"> Khóa học nổi bật
                </label>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary">💾 Tạo Khóa Học</button>
                <a href="?action=teacher_courses" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
<?php
}

function pageManageCourse($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $id = (int)($_GET['id'] ?? 0);
    $course = $db->prepare("SELECT c.*, cat.name as cat_name FROM courses c LEFT JOIN categories cat ON c.category_id=cat.id WHERE c.id=?");
    $course->execute([$id]);
    $course = $course->fetch(PDO::FETCH_ASSOC);
    if (!$course) { echo '<div class="container"><div class="alert alert-error">Không tìm thấy!</div></div>'; return; }

    $sections = $db->prepare("SELECT * FROM sections WHERE course_id=? ORDER BY order_num");
    $sections->execute([$id]);
    $sections = $sections->fetchAll(PDO::FETCH_ASSOC);

    $quizzes = $db->prepare("SELECT * FROM quizzes WHERE course_id=?");
    $quizzes->execute([$id]);
    $quizzes = $quizzes->fetchAll(PDO::FETCH_ASSOC);

    $categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:24px;padding-bottom:48px;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <a href="?action=teacher_courses" class="btn btn-secondary btn-sm">← Quay lại</a>
        <h1 style="font-size:1.5rem;font-weight:800;flex:1;"><?= h($course['title']) ?></h1>
        <a href="?action=course&id=<?= $id ?>" class="btn btn-secondary btn-sm">👁 Xem trước</a>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="showTab('curriculum')">📋 Giáo Trình</div>
        <div class="tab" onclick="showTab('settings')">⚙️ Cài Đặt</div>
    </div>

    <!-- CURRICULUM TAB -->
    <div id="tab-curriculum">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h2 style="font-size:1.1rem;font-weight:700;">Cấu Trúc Bài Giảng</h2>
            </div>
            <?php foreach($sections as $sec): ?>
            <?php
            $lessons = $db->prepare("SELECT * FROM lessons WHERE section_id=? ORDER BY order_num");
            $lessons->execute([$sec['id']]);
            $lessons = $lessons->fetchAll(PDO::FETCH_ASSOC);
            $secQuizzes = array_filter($quizzes, fn($q) => $q['section_id'] == $sec['id']);
            ?>
            <div class="section-item">
                <div class="section-header-row">
                    <span>📁</span> <?= h($sec['title']) ?>
                    <span style="margin-left:auto;font-size:0.75rem;color:var(--gray);">Chương <?= $sec['order_num'] ?></span>
                </div>
                <div class="lesson-list">
                    <?php foreach($lessons as $l): ?>
                    <div class="lesson-item">
                        <span><?= $l['lesson_type']==='video'?'▶':'📄' ?></span>
                        <span><?= h($l['title']) ?></span>
                        <span style="margin-left:auto;font-size:0.75rem;color:var(--gray);"><?= $l['duration_minutes'] ?> phút</span>
                    </div>
                    <?php endforeach; ?>
                    <?php foreach($secQuizzes as $q): ?>
<div class="lesson-item">
    <span>📝</span>
    <a href="?action=manage_quiz&quiz_id=<?= $q['id'] ?>&course_id=<?= $id ?>"><?= h($q['title']) ?></a>
    <span style="margin-left:auto;display:flex;align-items:center;gap:8px;">
        <span class="badge badge-warning"><?= $q['passing_score'] ?>% qua môn</span>
        <a href="?action=manage_quiz&quiz_id=<?= $q['id'] ?>&course_id=<?= $id ?>" class="btn btn-primary btn-sm">✏️ Câu hỏi</a>
    </span>
</div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($sections)): ?>
            <div class="alert alert-info">Chưa có chương nào. Thêm chương đầu tiên!</div>
            <?php endif; ?>
        </div>

        <!-- FORMS PANEL -->
        <div>
            <!-- Add Section -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><h3 class="card-title">➕ Thêm Chương</h3></div>
                <form method="post" action="?action=save_section" style="padding-top:16px;">
                    <input type="hidden" name="course_id" value="<?= $id ?>">
                    <div class="form-group"><label>Tên Chương</label><input type="text" name="title" placeholder="Ví dụ: Chương 1: Giới Thiệu" required></div>
                    <button type="submit" class="btn btn-primary btn-sm">Thêm Chương</button>
                </form>
            </div>

            <?php if (!empty($sections)): ?>
            <!-- Add Lesson -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><h3 class="card-title">📄 Thêm Bài Học</h3></div>
                <form method="post" action="?action=save_lesson" enctype="multipart/form-data" style="padding-top:16px;">
                    <input type="hidden" name="course_id" value="<?= $id ?>">
                    <div class="form-group">
                        <label>Thuộc Chương</label>
                        <select name="section_id" required>
                            <?php foreach($sections as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= h($s['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Tên Bài Học</label><input type="text" name="title" required placeholder="Bài 1: ..."></div>
                    <div class="form-group">
                        <label>Loại Bài Học</label>
                        <select name="lesson_type">
                            <option value="video">📹 Video</option>
                            <option value="text">📄 Văn bản</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Link Video YouTube</label><input type="url" name="video_url" placeholder="https://youtube.com/..."></div>
                    <div class="form-group"><label>Nội Dung Bài Học</label><textarea name="content" rows="3" placeholder="Nội dung chi tiết..."></textarea></div>
                    <div class="form-group"><label>Tài Liệu (PDF/File)</label><input type="file" name="lesson_file"></div>
                    <div class="form-group"><label>Thời Lượng (phút)</label><input type="number" name="duration_minutes" value="0" min="0"></div>
                    <button type="submit" class="btn btn-primary btn-sm">Thêm Bài Học</button>
                </form>
            </div>

            <!-- Add Quiz -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">📝 Thêm Bài Kiểm Tra</h3></div>
                <form method="post" action="?action=save_quiz" style="padding-top:16px;" id="quiz-form-create">
                    <input type="hidden" name="course_id" value="<?= $id ?>">
                    <div class="form-group">
                        <label>Thuộc Chương</label>
                        <select name="section_id">
                            <?php foreach($sections as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= h($s['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Tên Bài Kiểm Tra</label><input type="text" name="title" required placeholder="Bài kiểm tra cuối chương..."></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group"><label>Thời Gian (phút)</label><input type="number" name="time_limit" value="30" min="1"></div>
                        <div class="form-group"><label>Điểm Qua Môn (%)</label><input type="number" name="passing_score" value="70" min="0" max="100"></div>
                    </div>
                    <div id="questions-container">
                        <div style="font-weight:600;margin-bottom:12px;font-size:0.875rem;">Câu Hỏi:</div>
                        <?php for($qi=0;$qi<3;$qi++): ?>
                        <div style="background:var(--light);border-radius:var(--radius-sm);padding:12px;margin-bottom:12px;">
                            <div class="form-group"><label>Câu <?= $qi+1 ?></label><input type="text" name="questions[<?= $qi ?>][question]" placeholder="Nội dung câu hỏi..."></div>
                            <?php foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $key=>$label): ?>
                            <div class="form-group" style="margin-bottom:8px;"><input type="text" name="questions[<?= $qi ?>][<?= $key ?>]" placeholder="Đáp án <?= $label ?>..."></div>
                            <?php endforeach; ?>
                            <div class="form-group"><label>Đáp Án Đúng</label>
                                <select name="questions[<?= $qi ?>][correct]">
                                    <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                                </select>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">💾 Tạo Bài Kiểm Tra</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    

    <!-- SETTINGS TAB -->
    <div id="tab-settings" style="display:none;">
        <div class="card" style="max-width:720px;">
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:20px;">Chỉnh Sửa Thông Tin Khóa Học</h2>
            <form method="post" action="?action=save_course" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="form-group"><label>Tên Khóa Học</label><input type="text" name="title" value="<?= h($course['title']) ?>" required></div>
                <div class="form-group"><label>Mô Tả</label><textarea name="description" rows="4"><?= h($course['description']) ?></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Danh Mục</label>
                        <select name="category_id">
                            <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $course['category_id']==$cat['id']?'selected':'' ?>><?= h($cat['icon'].' '.$cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cấp Độ</label>
                        <select name="level">
                            <option value="beginner" <?= $course['level']==='beginner'?'selected':'' ?>>Cơ Bản</option>
                            <option value="intermediate" <?= $course['level']==='intermediate'?'selected':'' ?>>Trung Cấp</option>
                            <option value="advanced" <?= $course['level']==='advanced'?'selected':'' ?>>Nâng Cao</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Giá (VNĐ)</label><input type="number" name="price" value="<?= $course['price'] ?>" min="0"></div>
                <div class="form-group"><label>Ảnh Bìa Mới</label><input type="file" name="thumbnail" accept="image/*"></div>
                <div style="display:flex;gap:24px;margin-bottom:20px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                        <input type="checkbox" name="is_published" value="1" <?= $course['is_published']?'checked':'' ?>> Xuất bản
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                        <input type="checkbox" name="is_featured" value="1" <?= $course['is_featured']?'checked':'' ?>> Nổi bật
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">💾 Lưu Thay Đổi</button>
            </form>
        </div>
    </div>
</div>
<script>
function showTab(name) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display='none');
    document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-'+name).style.display='block';
    event.target.classList.add('active');
}
</script>
<?php
}
// ============================================================
// PAGE: MANAGE QUIZ QUESTIONS
// ============================================================
function pageManageQuiz($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $quizId = (int)($_GET['quiz_id'] ?? 0);
    $courseId = (int)($_GET['course_id'] ?? 0);
    $editQId = (int)($_GET['edit_q'] ?? 0);

    $quiz = $db->prepare("SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id=c.id WHERE q.id=?");
    $quiz->execute([$quizId]);
    $quiz = $quiz->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) { echo '<div class="container"><div class="alert alert-error">Không tìm thấy bài kiểm tra!</div></div>'; return; }

    $questions = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY id");
    $questions->execute([$quizId]);
    $questions = $questions->fetchAll(PDO::FETCH_ASSOC);

    $editQuestion = null;
    if ($editQId) {
        $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE id=? AND quiz_id=?");
        $stmt->execute([$editQId, $quizId]);
        $editQuestion = $stmt->fetch(PDO::FETCH_ASSOC);
    }
?>
<div class="container" style="padding-top:28px;padding-bottom:60px;">
    <!-- BREADCRUMB -->
    <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:var(--gray);margin-bottom:20px;">
        <a href="?action=teacher_courses" style="color:var(--gray);">Khóa học</a>
        <span>›</span>
        <a href="?action=manage_course&id=<?= $quiz['course_id'] ?>" style="color:var(--gray);"><?= h($quiz['course_title']) ?></a>
        <span>›</span>
        <span style="color:var(--dark);font-weight:600;">📝 <?= h($quiz['title']) ?></span>
    </div>

    <!-- HEADER -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;gap:16px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:6px;">📝 <?= h($quiz['title']) ?></h1>
            <div style="display:flex;gap:16px;font-size:0.85rem;color:var(--gray);">
                <span>⏱ <?= $quiz['time_limit'] ?> phút</span>
                <span>✅ Qua môn: <?= $quiz['passing_score'] ?>%</span>
                <span>❓ <?= count($questions) ?> câu hỏi</span>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button onclick="togglePanel('edit-quiz-info')" class="btn btn-secondary btn-sm">✏️ Sửa thông tin</button>
            <a href="?action=manage_course&id=<?= $quiz['course_id'] ?>" class="btn btn-secondary btn-sm">← Về khóa học</a>
            <form method="post" action="?action=delete_quiz" onsubmit="return confirm('Xóa bài kiểm tra này và toàn bộ câu hỏi?')" style="margin:0;">
                <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑 Xóa bài kiểm tra</button>
            </form>
        </div>
    </div>

    <!-- EDIT QUIZ INFO PANEL -->
    <div id="edit-quiz-info" style="display:none;margin-bottom:24px;">
        <div class="card">
            <div class="card-header"><h3 class="card-title">✏️ Chỉnh Sửa Thông Tin Bài Kiểm Tra</h3></div>
            <form method="post" action="?action=edit_quiz_info" style="padding-top:16px;">
                <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                <div class="form-group"><label>Tên Bài Kiểm Tra</label><input type="text" name="title" value="<?= h($quiz['title']) ?>" required></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group"><label>Thời Gian (phút)</label><input type="number" name="time_limit" value="<?= $quiz['time_limit'] ?>" min="1" required></div>
                    <div class="form-group"><label>Điểm Qua Môn (%)</label><input type="number" name="passing_score" value="<?= $quiz['passing_score'] ?>" min="0" max="100" required></div>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary btn-sm">💾 Lưu thay đổi</button>
                    <button type="button" onclick="togglePanel('edit-quiz-info')" class="btn btn-secondary btn-sm">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 420px;gap:28px;align-items:start;">
    <!-- LEFT: QUESTION LIST -->
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h2 style="font-size:1.15rem;font-weight:800;">Danh Sách Câu Hỏi</h2>
            <span class="badge badge-primary" style="font-size:0.85rem;padding:6px 14px;"><?= count($questions) ?> câu</span>
        </div>

        <?php if (empty($questions)): ?>
        <div class="alert alert-info">📭 Chưa có câu hỏi nào. Thêm câu hỏi đầu tiên ở bên phải!</div>
        <?php endif; ?>

        <?php foreach($questions as $i => $q): ?>
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--primary);<?= $editQId==$q['id']?'border-color:var(--secondary);':'' ?>">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;">
                <div style="display:flex;align-items:flex-start;gap:12px;flex:1;">
                    <span style="background:var(--primary);color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:800;flex-shrink:0;"><?= $i+1 ?></span>
                    <p style="font-weight:700;font-size:0.95rem;line-height:1.5;"><?= h($q['question']) ?></p>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <a href="?action=manage_quiz&quiz_id=<?= $quizId ?>&course_id=<?= $courseId ?>&edit_q=<?= $q['id'] ?>#edit-form" class="btn btn-secondary btn-sm">✏️</a>
                    <form method="post" action="?action=delete_question" onsubmit="return confirm('Xóa câu hỏi này?')" style="margin:0;">
                        <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                        <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                        <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                    </form>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <?php foreach(['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d']] as $opt=>$val): ?>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;background:<?= $q['correct_answer']===$opt?'#d1fae5':'var(--light)' ?>;border:1.5px solid <?= $q['correct_answer']===$opt?'#6ee7b7':'var(--border)' ?>;">
                    <span style="font-weight:800;color:<?= $q['correct_answer']===$opt?'var(--success)':'var(--gray)' ?>;font-size:0.8rem;width:18px;"><?= $opt ?></span>
                    <span style="font-size:0.82rem;<?= $q['correct_answer']===$opt?'font-weight:600;color:#065f46;':'' ?>"><?= h($val) ?></span>
                    <?php if ($q['correct_answer']===$opt): ?><span style="margin-left:auto;font-size:0.75rem;">✅</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- RIGHT: ADD / EDIT FORM -->
    <div style="position:sticky;top:80px;" id="edit-form">
        <?php if ($editQuestion): ?>
        <!-- EDIT FORM -->
        <div class="card" style="border-top:4px solid var(--secondary);">
            <div class="card-header" style="background:#fef3c7;">
                <h3 class="card-title" style="color:#92400e;">✏️ Sửa Câu Hỏi</h3>
                <a href="?action=manage_quiz&quiz_id=<?= $quizId ?>&course_id=<?= $courseId ?>" class="btn btn-secondary btn-sm">✕ Hủy</a>
            </div>
            <form method="post" action="?action=edit_question" style="padding-top:16px;">
                <input type="hidden" name="question_id" value="<?= $editQuestion['id'] ?>">
                <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                <div class="form-group">
                    <label>Nội Dung Câu Hỏi *</label>
                    <textarea name="question" rows="3" required placeholder="Nhập câu hỏi..."><?= h($editQuestion['question']) ?></textarea>
                </div>
                <?php foreach(['A'=>[$editQuestion['option_a'],'option_a'],'B'=>[$editQuestion['option_b'],'option_b'],'C'=>[$editQuestion['option_c'],'option_c'],'D'=>[$editQuestion['option_d'],'option_d']] as $label=>[$val,$name]): ?>
                <div class="form-group">
                    <label>Đáp Án <?= $label ?> *</label>
                    <input type="text" name="<?= $name ?>" value="<?= h($val) ?>" required placeholder="Đáp án <?= $label ?>...">
                </div>
                <?php endforeach; ?>
                <div class="form-group">
                    <label>✅ Đáp Án Đúng</label>
                    <select name="correct_answer" style="font-weight:700;">
                        <?php foreach(['A','B','C','D'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $editQuestion['correct_answer']===$opt?'selected':'' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">💾 Lưu Thay Đổi</button>
            </form>
        </div>
        <?php else: ?>
        <!-- ADD FORM -->
        <div class="card" style="border-top:4px solid var(--primary);">
            <div class="card-header">
                <h3 class="card-title">➕ Thêm Câu Hỏi Mới</h3>
            </div>
            <form method="post" action="?action=add_question" style="padding-top:16px;" id="add-q-form">
                <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                <div class="form-group">
                    <label>Nội Dung Câu Hỏi *</label>
                    <textarea name="question" rows="3" required placeholder="Nhập nội dung câu hỏi..." id="q-text"></textarea>
                </div>
                <div style="background:var(--light);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;">
                    <?php foreach(['A'=>'option_a','B'=>'option_b','C'=>'option_c','D'=>'option_d'] as $label=>$name): ?>
                    <div class="form-group" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <span style="background:var(--primary);color:white;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:800;flex-shrink:0;"><?= $label ?></span>
                        <input type="text" name="<?= $name ?>" required placeholder="Đáp án <?= $label ?>..." style="margin:0;flex:1;">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <label>✅ Đáp Án Đúng</label>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                        <?php foreach(['A','B','C','D'] as $opt): ?>
                        <label style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 6px;border:2px solid var(--border);border-radius:8px;cursor:pointer;transition:all 0.2s;font-weight:700;font-size:0.85rem;" class="correct-opt">
                            <input type="radio" name="correct_answer" value="<?= $opt ?>" <?= $opt==='A'?'checked':'' ?> style="width:auto;" onchange="highlightCorrect()">
                            <?= $opt ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">➕ Thêm Câu Hỏi</button>
                <button type="button" onclick="resetForm()" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:8px;">🔄 Nhập lại</button>
            </form>
        </div>

        <!-- QUICK STATS -->
        <?php if (!empty($questions)): ?>
        <div class="card" style="margin-top:16px;">
            <h3 style="font-size:0.9rem;font-weight:700;margin-bottom:12px;">📊 Thống Kê</h3>
            <?php
            $correctCounts = array_count_values(array_column($questions,'correct_answer'));
            arsort($correctCounts);
            ?>
            <div style="font-size:0.82rem;color:var(--gray);margin-bottom:8px;">Phân bố đáp án đúng:</div>
            <?php foreach(['A','B','C','D'] as $opt): ?>
            <?php $cnt = $correctCounts[$opt] ?? 0; $pct = count($questions)>0?round($cnt/count($questions)*100):0; ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <span style="font-weight:700;font-size:0.8rem;width:16px;"><?= $opt ?></span>
                <div style="flex:1;background:var(--border);border-radius:4px;height:6px;overflow:hidden;">
                    <div style="width:<?= $pct ?>%;height:100%;background:var(--primary);border-radius:4px;"></div>
                </div>
                <span style="font-size:0.78rem;color:var(--gray);width:28px;"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
            <div style="font-size:0.78rem;color:var(--gray);margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
                💡 Nên phân bố đều đáp án đúng để tránh học viên đoán mò.
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    </div>
</div>

<style>
.correct-opt:has(input:checked) {
    border-color: var(--success) !important;
    background: #d1fae5;
    color: #065f46;
}
</style>

<script>
function togglePanel(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function highlightCorrect() {
    document.querySelectorAll('.correct-opt').forEach(el => {
        el.style.borderColor = el.querySelector('input').checked ? 'var(--success)' : 'var(--border)';
        el.style.background = el.querySelector('input').checked ? '#d1fae5' : '';
        el.style.color = el.querySelector('input').checked ? '#065f46' : '';
    });
}
function resetForm() {
    document.getElementById('add-q-form').reset();
    document.querySelectorAll('.correct-opt').forEach(el => {
        el.style.borderColor = 'var(--border)';
        el.style.background = '';
        el.style.color = '';
    });
    // Re-check default A
    document.querySelector('.correct-opt input[value="A"]').checked = true;
    highlightCorrect();
    document.getElementById('q-text').focus();
}
highlightCorrect();
</script>
<?php
}
function pageTeacherStudents($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $user = currentUser();
    $where = $user['role']==='admin' ? "1=1" : "c.instructor_id=".$user['id'];
    $students = $db->query("SELECT u.id, u.full_name, u.email, u.username, c.title as course_title, c.id as course_id, e.enrolled_at FROM enrollments e JOIN users u ON e.user_id=u.id JOIN courses c ON e.course_id=c.id WHERE $where ORDER BY e.enrolled_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h1 style="font-size:1.75rem;font-weight:800;">👨‍👩‍👧‍👦 Danh Sách Học Viên</h1>
        <span class="badge badge-primary" style="font-size:0.9rem;padding:8px 16px;"><?= count($students) ?> học viên</span>
    </div>
    <div class="table-wrapper">
    <table>
        <thead><tr><th>Họ Tên</th><th>Email</th><th>Khóa Học</th><th>Tiến Trình</th><th>Ngày Ghi Danh</th></tr></thead>
        <tbody>
        <?php foreach($students as $s):
            $progress = getCourseProgress($s['id'], $s['course_id']);
        ?>
        <tr>
            <td><strong><?= h($s['full_name']) ?></strong></td>
            <td style="color:var(--gray);font-size:0.875rem;"><?= h($s['email']) ?></td>
            <td><?= h($s['course_title']) ?></td>
            <td style="min-width:150px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="progress" style="flex:1;height:6px;"><div class="progress-bar" style="width:<?= $progress ?>%"></div></div>
                    <span style="font-size:0.78rem;font-weight:600;white-space:nowrap;"><?= $progress ?>%</span>
                </div>
            </td>
            <td style="font-size:0.875rem;color:var(--gray);"><?= date('d/m/Y', strtotime($s['enrolled_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php
}

