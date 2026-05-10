<?php
// ============================================================
// PAGE: HOME
// ============================================================
function pageHome($db) {
    $featured = $db->query("SELECT c.*, u.full_name as instructor_name, cat.name as cat_name FROM courses c LEFT JOIN users u ON c.instructor_id=u.id LEFT JOIN categories cat ON c.category_id=cat.id WHERE c.is_published=1 AND c.is_featured=1 LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $db->query("SELECT *, (SELECT COUNT(*) FROM courses WHERE category_id=categories.id AND is_published=1) as course_count FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $totalCourses = $db->query("SELECT COUNT(*) FROM courses WHERE is_published=1")->fetchColumn();
    $totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $totalTeachers = $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
    $banner_title = setting('banner_title','Học Trực Tuyến Cùng EduViet');
    $banner_sub = setting('banner_subtitle','Nền tảng học tập hàng đầu Việt Nam');
?>
<div class="hero">
    <div class="hero-inner">
        <h1><?= h($banner_title) ?></h1>
        <p><?= h($banner_sub) ?></p>
        <div class="hero-actions">
            <a href="?action=courses" class="btn btn-lg" style="background:white;color:var(--primary);font-weight:700;">🔍 Khám Phá Khóa Học</a>
            <?php if (!isLoggedIn()): ?>
            <a href="?action=register" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.4);">✨ Đăng Ký Miễn Phí</a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div class="stat"><span class="stat-num"><?= $totalCourses ?>+</span><span class="stat-label">Khóa học</span></div>
            <div class="stat"><span class="stat-num"><?= $totalStudents ?>+</span><span class="stat-label">Học viên</span></div>
            <div class="stat"><span class="stat-num"><?= $totalTeachers ?>+</span><span class="stat-label">Giảng viên</span></div>
        </div>
    </div>
</div>

<div class="container">
    <!-- CATEGORIES -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Danh Mục <span>Khóa Học</span></h2>
        </div>
        <div class="cat-grid">
            <?php foreach($categories as $cat): ?>
            <a href="?action=courses&category=<?= h($cat['slug']) ?>" class="cat-chip">
                <span><?= h($cat['icon']) ?></span>
                <span><?= h($cat['name']) ?></span>
                <span style="background:var(--primary-light);color:var(--primary);font-size:0.75rem;padding:2px 8px;border-radius:10px;"><?= $cat['course_count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- FEATURED COURSES -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Khóa Học <span>Nổi Bật</span></h2>
            <a href="?action=courses" class="btn btn-secondary">Xem tất cả →</a>
        </div>
        <div class="grid grid-3">
            <?php foreach($featured as $course): ?>
            <?= renderCourseCard($course) ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="section">
        <div class="section-header"><h2 class="section-title">Tại Sao Chọn <span>EduViet?</span></h2></div>
        <div class="grid grid-4">
            <?php foreach([
                ['🎯','Nội dung chất lượng','Video HD, tài liệu PDF và bài tập thực hành'],
                ['📱','Học mọi lúc mọi nơi','Responsive trên PC, tablet và điện thoại'],
                ['🏆','Chứng chỉ uy tín','Nhận chứng chỉ hoàn thành được công nhận'],
                ['🤖','AI Điểm Danh','Công nghệ nhận diện khuôn mặt AI hiện đại'],
            ] as [$icon,$title,$desc]): ?>
            <div class="card" style="text-align:center;">
                <div style="font-size:2.5rem;margin-bottom:12px;"><?= $icon ?></div>
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:8px;"><?= $title ?></h3>
                <p style="font-size:0.85rem;color:var(--gray);"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php
}

function renderCourseCard($course, $showActions=true) {
    $price = $course['price'] > 0 ? number_format($course['price'],0,',','.') . 'đ' : 'Miễn phí';
    $isFree = $course['price'] <= 0;
    ob_start(); ?>
    <div class="course-card">
        <a href="?action=course&id=<?= $course['id'] ?>">
            <div class="course-thumb">
                <?php if (!empty($course['thumbnail']) && file_exists(UPLOAD_DIR.$course['thumbnail'])): ?>
                <img src="uploads/<?= h($course['thumbnail']) ?>" alt="">
                <?php else: ?>
                <span><?= $course['cat_name'] === 'Lập Trình' ? '💻' : ($course['cat_name'] === 'Ngoại Ngữ' ? '🌍' : '🎯') ?></span>
                <?php endif; ?>
            </div>
        </a>
        <div class="course-body">
            <?php if (!empty($course['cat_name'])): ?>
            <span class="course-category"><?= h($course['cat_name']) ?></span>
            <?php endif; ?>
            <h3 class="course-title"><a href="?action=course&id=<?= $course['id'] ?>"><?= h($course['title']) ?></a></h3>
            <div class="course-meta">
                <span>👨‍🏫 <?= h($course['instructor_name'] ?? '') ?></span>
                <span>⏱ <?= $course['duration_hours'] ?> giờ</span>
            </div>
            <div class="course-price <?= $isFree?'free':'' ?>"><?= $price ?></div>
        </div>
        <div class="course-footer">
            <a href="?action=course&id=<?= $course['id'] ?>" class="btn btn-primary btn-sm">Xem Chi Tiết</a>
            <?php if ($isFree): ?>
            <span class="badge badge-success">Miễn phí</span>
            <?php endif; ?>
        </div>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// PAGE: COURSES LIST
// ============================================================
function pageCourses($db) {
    $search = trim($_GET['search'] ?? '');
    $category = $_GET['category'] ?? '';
    $where = ["c.is_published=1"];
    $params = [];
    if ($search) { $where[] = "c.title LIKE ?"; $params[] = "%$search%"; }
    if ($category) { $where[] = "cat.slug=?"; $params[] = $category; }
    $sql = "SELECT c.*, u.full_name as instructor_name, cat.name as cat_name FROM courses c LEFT JOIN users u ON c.instructor_id=u.id LEFT JOIN categories cat ON c.category_id=cat.id WHERE ".implode(' AND ',$where)." ORDER BY c.is_featured DESC, c.id DESC";
    $stmt = $db->prepare($sql); $stmt->execute($params);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="background:var(--white);border-bottom:1px solid var(--border);padding:24px 0;">
<div class="container">
    <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:20px;">📚 Tất Cả Khóa Học</h1>
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;">
        <input type="hidden" name="action" value="courses">
        <input type="text" name="search" placeholder="🔍 Tìm kiếm khóa học..." value="<?= h($search) ?>" style="max-width:320px;">
        <select name="category" style="max-width:200px;">
            <option value="">Tất cả danh mục</option>
            <?php foreach($categories as $cat): ?>
            <option value="<?= h($cat['slug']) ?>" <?= $category===$cat['slug']?'selected':'' ?>><?= h($cat['icon'].' '.$cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
        <?php if($search||$category): ?><a href="?action=courses" class="btn btn-secondary">Xóa bộ lọc</a><?php endif; ?>
    </form>
</div>
</div>
<div class="container">
<div class="section">
    <?php if (empty($courses)): ?>
    <div class="alert alert-info">Không tìm thấy khóa học nào phù hợp.</div>
    <?php else: ?>
    <div style="color:var(--gray);font-size:0.875rem;margin-bottom:20px;">Tìm thấy <?= count($courses) ?> khóa học</div>
    <div class="grid grid-3">
        <?php foreach($courses as $course): ?>
        <?= renderCourseCard($course) ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>
<?php
}

// ============================================================
// PAGE: COURSE DETAIL
// ============================================================
function pageCourse($db) {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT c.*, u.full_name as instructor_name, cat.name as cat_name FROM courses c LEFT JOIN users u ON c.instructor_id=u.id LEFT JOIN categories cat ON c.category_id=cat.id WHERE c.id=?");
    $stmt->execute([$id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) { echo '<div class="container"><div class="alert alert-error">Khóa học không tồn tại!</div></div>'; return; }

    $sections = $db->prepare("SELECT * FROM sections WHERE course_id=? ORDER BY order_num");
    $sections->execute([$id]);
    $sections = $sections->fetchAll(PDO::FETCH_ASSOC);

    $quizzes = $db->prepare("SELECT * FROM quizzes WHERE course_id=? ORDER BY order_num");
    $quizzes->execute([$id]);
    $quizzes = $quizzes->fetchAll(PDO::FETCH_ASSOC);

    $isEnrolled = false;
    $progress = 0;
    $hasCert = false;
    if (isLoggedIn()) {
        $chk = $db->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
        $chk->execute([$_SESSION['user_id'], $id]);
        $isEnrolled = (bool)$chk->fetchColumn();
        if ($isEnrolled) {
            $progress = getCourseProgress($_SESSION['user_id'], $id);
            $certChk = $db->prepare("SELECT id FROM certificates WHERE user_id=? AND course_id=?");
            $certChk->execute([$_SESSION['user_id'], $id]);
            $hasCert = (bool)$certChk->fetchColumn();
        }
    }
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:32px;" class="course-detail-layout">
<div>
    <div style="margin-bottom:8px;">
        <a href="?action=courses" style="color:var(--gray);font-size:0.875rem;">← Quay lại</a>
    </div>
    <?php if (!empty($course['cat_name'])): ?>
    <span class="course-category"><?= h($course['cat_name']) ?></span>
    <?php endif; ?>
    <h1 style="font-size:2rem;font-weight:800;margin:12px 0;letter-spacing:-0.5px;"><?= h($course['title']) ?></h1>
    <p style="color:var(--gray);margin-bottom:24px;line-height:1.7;"><?= h($course['description']) ?></p>
    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:0.875rem;color:var(--gray);margin-bottom:32px;">
        <span>👨‍🏫 <?= h($course['instructor_name']) ?></span>
        <span>⏱ <?= $course['duration_hours'] ?> giờ</span>
        <span>📊 <?= $course['level']==='beginner'?'Cơ bản':($course['level']==='intermediate'?'Trung cấp':'Nâng cao') ?></span>
    </div>

    <?php if ($isEnrolled): ?>
    <div class="card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-weight:700;">Tiến Trình Học Tập</span>
            <span style="font-weight:700;color:var(--primary);" id="progress-pct"><?= $progress ?>%</span>
        </div>
        <div class="progress"><div class="progress-bar" id="progress-bar" style="width:<?= $progress ?>%"></div></div>
        <?php if ($hasCert || $progress >= 100): ?>
        <div style="margin-top:16px;">
            <a href="?action=certificate&course_id=<?= $id ?>" class="btn btn-success">🏆 Xem & Tải Chứng Chỉ</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Curriculum -->
    <h2 style="font-size:1.25rem;font-weight:800;margin-bottom:16px;">📋 Nội Dung Khóa Học</h2>
    <div class="curriculum">
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
                <span style="margin-left:auto;font-size:0.8rem;color:var(--gray);font-weight:400;"><?= count($lessons) ?> bài học</span>
            </div>
            <div class="lesson-list">
            <?php foreach($lessons as $lesson): ?>
                <?php
                $isDone = false;
                if ($isEnrolled && isLoggedIn()) {
                    $chk = $db->prepare("SELECT completed FROM lesson_progress WHERE user_id=? AND lesson_id=?");
                    $chk->execute([$_SESSION['user_id'], $lesson['id']]);
                    $isDone = (bool)$chk->fetchColumn();
                }
                $icon = $lesson['lesson_type']==='video'?'▶':'📄';
                $iconClass = $lesson['lesson_type']==='video'?'video':'text';
                ?>
                <div class="lesson-item">
                    <div class="lesson-icon <?= $isDone?'done':$iconClass ?>"><?= $isDone?'✓':$icon ?></div>
                    <?php if ($isEnrolled): ?>
                    <a href="?action=lesson&id=<?= $lesson['id'] ?>&course_id=<?= $id ?>"><?= h($lesson['title']) ?></a>
                    <?php else: ?>
                    <span><?= h($lesson['title']) ?></span>
                    <?php endif; ?>
                    <?php if ($lesson['duration_minutes']): ?>
                    <span style="margin-left:auto;font-size:0.78rem;color:var(--gray);"><?= $lesson['duration_minutes'] ?> phút</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php foreach($secQuizzes as $quiz): ?>
                <div class="lesson-item">
                    <div class="lesson-icon quiz">📝</div>
                    <?php if ($isEnrolled): ?>
                    <a href="?action=quiz&id=<?= $quiz['id'] ?>&course_id=<?= $id ?>"><?= h($quiz['title']) ?></a>
                    <?php else: ?>
                    <span><?= h($quiz['title']) ?></span>
                    <?php endif; ?>
                    <span style="margin-left:auto;font-size:0.78rem;color:var(--gray);"><?= $quiz['time_limit'] ?> phút</span>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- SIDEBAR -->
<div>
    <div class="card" style="position:sticky;top:80px;">
        <div class="course-thumb" style="border-radius:var(--radius-sm);margin:-24px -24px 24px;height:200px;">
            <?php if (!empty($course['thumbnail']) && file_exists(UPLOAD_DIR.$course['thumbnail'])): ?>
            <img src="uploads/<?= h($course['thumbnail']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-sm) var(--radius-sm) 0 0;">
            <?php else: ?>
            <span style="font-size:4rem;">📚</span>
            <?php endif; ?>
        </div>
        <div style="font-size:2rem;font-weight:800;color:<?= $course['price']>0?'var(--primary)':'var(--success)' ?>;margin-bottom:16px;">
            <?= $course['price'] > 0 ? number_format($course['price'],0,',','.') . 'đ' : '🆓 Miễn Phí' ?>
        </div>
        <?php if ($isEnrolled): ?>
        <div class="alert alert-success">✅ Bạn đã ghi danh khóa học này</div>
        <a href="?action=lesson&id=<?= $lessons[0]['id'] ?? 0 ?>&course_id=<?= $id ?>" class="btn btn-primary" style="width:100%;justify-content:center;">▶ Tiếp Tục Học</a>
        <a href="?action=message_thread&partner=<?= $course['instructor_id'] ?>&course=<?= $id ?>" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:10px;">💬 Nhắn Tin Với Giáo Viên</a>
        <?php elseif (!isLoggedIn()): ?>
        <a href="?action=login" class="btn btn-primary" style="width:100%;justify-content:center;">Đăng Nhập Để Ghi Danh</a>
        <?php else: ?>
        <?php if ($course['price'] > 0): ?>
        <div class="alert alert-info" style="font-size:0.8rem;">💳 Mô phỏng thanh toán: Nhấn "Ghi Danh" để thực hiện</div>
        <?php endif; ?>
        <form method="post" action="?action=enroll">
            <input type="hidden" name="course_id" value="<?= $id ?>">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <?= $course['price'] > 0 ? '💳 Thanh Toán & Ghi Danh' : '🎓 Tham Gia Miễn Phí' ?>
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<style>@media(max-width:768px){.course-detail-layout{grid-template-columns:1fr!important;}}</style>
<?php
}

// ============================================================
// PAGE: LESSON
// ============================================================
function pageLesson($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $lessonId = (int)($_GET['id'] ?? 0);
    $courseId = (int)($_GET['course_id'] ?? 0);
    $stmt = $db->prepare("SELECT l.*, s.course_id FROM lessons l JOIN sections s ON l.section_id=s.id WHERE l.id=?");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lesson) { echo '<div class="container"><div class="alert alert-error">Không tìm thấy bài học!</div></div>'; return; }

    // Check enrollment
    $chk = $db->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
    $chk->execute([$_SESSION['user_id'], $lesson['course_id']]);
    if (!$chk->fetchColumn()) { redirect('?action=course&id='.$lesson['course_id']); return; }

    $course = $db->prepare("SELECT * FROM courses WHERE id=?");
    $course->execute([$lesson['course_id']]);
    $course = $course->fetch(PDO::FETCH_ASSOC);

    // All lessons for nav
    $allLessons = $db->prepare("SELECT l.*, s.title as section_title FROM lessons l JOIN sections s ON l.section_id=s.id WHERE s.course_id=? ORDER BY s.order_num, l.order_num");
    $allLessons->execute([$lesson['course_id']]);
    $allLessons = $allLessons->fetchAll(PDO::FETCH_ASSOC);

    $currentIndex = array_search($lessonId, array_column($allLessons,'id'));
    $prevLesson = $currentIndex > 0 ? $allLessons[$currentIndex-1] : null;
    $nextLesson = $currentIndex < count($allLessons)-1 ? $allLessons[$currentIndex+1] : null;

    $progress = getCourseProgress($_SESSION['user_id'], $lesson['course_id']);
    $isDone = false;
    $chkDone = $db->prepare("SELECT completed FROM lesson_progress WHERE user_id=? AND lesson_id=?");
    $chkDone->execute([$_SESSION['user_id'], $lessonId]);
    $isDone = (bool)$chkDone->fetchColumn();
    $videoUrl = embedYoutube($lesson['video_url']);
?>
<div style="display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);" class="lesson-layout">
<!-- SIDEBAR -->
<div style="background:var(--white);border-right:1px solid var(--border);overflow-y:auto;max-height:calc(100vh - 64px);position:sticky;top:64px;">
    <div style="padding:16px;border-bottom:1px solid var(--border);">
        <a href="?action=course&id=<?= $lesson['course_id'] ?>" style="font-size:0.8rem;color:var(--gray);">← <?= h($course['title']) ?></a>
        <div style="margin-top:8px;">
            <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:4px;">
                <span>Tiến trình</span><span id="sidebar-pct"><?= $progress ?>%</span>
            </div>
            <div class="progress"><div class="progress-bar" id="sidebar-bar" style="width:<?= $progress ?>%"></div></div>
        </div>
    </div>
    <?php
    $currentSection = '';
    foreach($allLessons as $l):
        if ($l['section_title'] !== $currentSection) {
            if ($currentSection !== '') echo '</div>';
            echo '<div style="padding:10px 16px 4px;font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--gray);letter-spacing:0.5px;">'.h($l['section_title']).'</div><div>';
            $currentSection = $l['section_title'];
        }
        $isActive = $l['id'] == $lessonId;
        $doneChk = $db->prepare("SELECT completed FROM lesson_progress WHERE user_id=? AND lesson_id=?");
        $doneChk->execute([$_SESSION['user_id'],$l['id']]);
        $lDone = (bool)$doneChk->fetchColumn();
    ?>
    <a href="?action=lesson&id=<?= $l['id'] ?>&course_id=<?= $lesson['course_id'] ?>" style="display:flex;align-items:center;gap:8px;padding:10px 16px;font-size:0.8rem;<?= $isActive?'background:var(--primary-light);color:var(--primary);font-weight:700;':'color:var(--dark);' ?>;transition:background 0.15s;">
        <span style="font-size:0.7rem;width:16px;"><?= $lDone?'✅':($l['lesson_type']==='video'?'▶':'📄') ?></span>
        <?= h($l['title']) ?>
    </a>
    <?php endforeach; echo '</div>'; ?>
</div>

<!-- CONTENT -->
<div style="padding:32px;max-width:860px;">
    <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:24px;"><?= h($lesson['title']) ?></h1>

    <?php if ($videoUrl): ?>
    <div style="border-radius:var(--radius);overflow:hidden;margin-bottom:24px;background:#000;aspect-ratio:16/9;">
        <iframe src="<?= h($videoUrl) ?>" width="100%" height="100%" frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
    </div>
    <?php endif; ?>

    <?php if ($lesson['content']): ?>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:28px;margin-bottom:24px;line-height:1.8;">
        <?= nl2br(h($lesson['content'])) ?>
    </div>
    <?php endif; ?>

    <?php if ($lesson['file_url'] && file_exists(UPLOAD_DIR.$lesson['file_url'])): ?>
    <div style="background:var(--primary-light);border:1px solid #93c5fd;border-radius:var(--radius-sm);padding:16px;display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <span style="font-size:1.5rem;">📎</span>
        <div>
            <div style="font-weight:600;font-size:0.875rem;">Tài liệu đính kèm</div>
            <div style="font-size:0.8rem;color:var(--gray);"><?= h($lesson['file_name']) ?></div>
        </div>
        <a href="uploads/<?= h($lesson['file_url']) ?>" download class="btn btn-primary btn-sm" style="margin-left:auto;">⬇ Tải xuống</a>
    </div>
    <?php endif; ?>

    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="display:flex;gap:12px;">
            <?php if ($prevLesson): ?>
            <a href="?action=lesson&id=<?= $prevLesson['id'] ?>&course_id=<?= $lesson['course_id'] ?>" class="btn btn-secondary">← Bài Trước</a>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:12px;">
            <?php if (!$isDone): ?>
            <button onclick="markDone()" class="btn btn-success" id="done-btn">✅ Đánh Dấu Hoàn Thành</button>
            <?php else: ?>
            <span class="badge badge-success" style="padding:8px 16px;font-size:0.875rem;">✅ Đã hoàn thành</span>
            <?php endif; ?>
            <?php if ($nextLesson): ?>
            <a href="?action=lesson&id=<?= $nextLesson['id'] ?>&course_id=<?= $lesson['course_id'] ?>" class="btn btn-primary" id="next-btn">Bài Tiếp → </a>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<style>@media(max-width:768px){.lesson-layout{grid-template-columns:1fr!important;}}</style>
<script>
function markDone() {
    fetch('?action=complete_lesson', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'lesson_id=<?= $lessonId ?>&course_id=<?= $lesson['course_id'] ?>'
    }).then(r=>r.json()).then(data => {
        document.getElementById('done-btn').replaceWith(Object.assign(document.createElement('span'), {className:'badge badge-success', style:'padding:8px 16px;font-size:0.875rem;', textContent:'✅ Đã hoàn thành'}));
        ['sidebar-bar','sidebar-pct'].forEach((id,i) => {
            const el = document.getElementById(id);
            if (el) { i===0 ? el.style.width=data.progress+'%' : el.textContent=data.progress+'%'; }
        });
        if (data.progress >= 100) {
            setTimeout(() => { if(confirm('🎉 Chúc mừng! Bạn đã hoàn thành khóa học! Xem chứng chỉ?')) window.location='?action=certificate&course_id=<?= $lesson['course_id'] ?>'; }, 500);
        }
    });
}
</script>
<?php
}

// ============================================================
// PAGE: QUIZ
// ============================================================
function pageQuiz($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $quizId = (int)($_GET['id'] ?? 0);
    $courseId = (int)($_GET['course_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM quizzes WHERE id=?");
    $stmt->execute([$quizId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) { echo '<div class="container"><div class="alert alert-error">Không tìm thấy bài kiểm tra!</div></div>'; return; }
    $questions = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY id");
    $questions->execute([$quizId]);
    $questions = $questions->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="max-width:800px;padding-top:40px;padding-bottom:60px;">
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <div>
                <h1 style="font-size:1.3rem;font-weight:800;">📝 <?= h($quiz['title']) ?></h1>
                <div style="font-size:0.8rem;color:var(--gray);margin-top:4px;">
                    ⏱ <?= $quiz['time_limit'] ?> phút &nbsp;|&nbsp; ✅ Điểm qua môn: <?= number_format($quiz['passing_score']/10,1) ?>/10
                </div>
            </div>
            <div id="timer" style="font-size:1.5rem;font-weight:800;color:var(--primary);"></div>
        </div>
        <div style="padding:0 24px 24px;">
            <div class="alert alert-info">📌 Trả lời tất cả câu hỏi rồi nhấn "Nộp Bài". Bạn chỉ có <?= $quiz['time_limit'] ?> phút!</div>
        </div>
    </div>

    <form method="post" action="?action=submit_quiz" id="quiz-form">
        <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <?php foreach($questions as $i => $q): ?>
        <div class="quiz-question">
            <h4><?= ($i+1) ?>. <?= h($q['question']) ?></h4>
            <?php foreach(['A','B','C','D'] as $opt): ?>
            <label class="option-label">
                <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt ?>">
                <span class="option-text"><strong><?= $opt ?>.</strong> <?= h($q['option_'.strtolower($opt)]) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <div style="display:flex;justify-content:center;margin-top:32px;">
            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Xác nhận nộp bài?')">📤 Nộp Bài</button>
        </div>
    </form>
</div>
<script>
let timeLeft = <?= $quiz['time_limit'] * 60 ?>;
const timer = document.getElementById('timer');
function updateTimer() {
    const m = Math.floor(timeLeft/60), s = timeLeft%60;
    timer.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    timer.style.color = timeLeft < 60 ? 'var(--danger)' : 'var(--primary)';
    if (timeLeft-- <= 0) { alert('⏰ Hết giờ! Bài thi sẽ được nộp.'); document.getElementById('quiz-form').submit(); }
}
updateTimer();
setInterval(updateTimer, 1000);
</script>
<?php
}

// ============================================================
// PAGE: QUIZ RESULT
// ============================================================
function getStudentQuizAverage($db, $userId, $courseId) {
    $stmt = $db->prepare("
        SELECT AVG(qa.score) as avg_score, COUNT(qa.id) as quiz_count
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.user_id = ? AND q.course_id = ? AND qa.is_first = 1
    ");
    $stmt->execute([$userId, $courseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function pageQuizResult($db) {
    $quizId = (int)($_GET['quiz_id'] ?? 0);
    $score  = (float)($_GET['score'] ?? 0);
    $passed = (int)($_GET['passed'] ?? 0);
    $stmt = $db->prepare("SELECT q.*, c.id as course_id, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id=c.id WHERE q.id=?");
    $stmt->execute([$quizId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    // Lấy thông tin lần thi vừa xong và điểm trung bình
    $attemptInfo = null;
    $avgData     = null;
    if (isLoggedIn()) {
        $aStmt = $db->prepare("SELECT is_first FROM quiz_attempts WHERE user_id=? AND quiz_id=? ORDER BY attempted_at DESC LIMIT 1");
        $aStmt->execute([$_SESSION['user_id'], $quizId]);
        $attemptInfo = $aStmt->fetch(PDO::FETCH_ASSOC);
        $avgData = getStudentQuizAverage($db, $_SESSION['user_id'], $quiz['course_id']);
    }

    $scoreOn10   = number_format($score / 10, 1);
    $passingOn10 = number_format($quiz['passing_score'] / 10, 1);
    $avgOn10 = ($avgData && $avgData['avg_score'] !== null)
        ? number_format($avgData['avg_score'] / 10, 1) : null;
?>
<div class="container" style="max-width:600px;padding-top:60px;padding-bottom:60px;text-align:center;">
    <div class="card">
        <div style="font-size:5rem;margin-bottom:16px;"><?= $passed ? '🎉' : '😢' ?></div>
        <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:8px;"><?= $passed ? 'Chúc Mừng! Bạn Đã Qua Môn!' : 'Chưa Đạt Yêu Cầu' ?></h1>
        <p style="color:var(--gray);margin-bottom:24px;"><?= $passed ? 'Bạn đã hoàn thành bài kiểm tra xuất sắc!' : 'Hãy ôn luyện thêm và thử lại!' ?></p>

        <?php if ($attemptInfo): ?>
        <?php if ($attemptInfo['is_first']): ?>
        <div style="background:#d1fae5;border:1px solid #10b981;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:0.85rem;color:#065f46;">
            ✅ Đây là <strong>lần đầu tiên</strong> làm bài — điểm này được ghi nhận vào điểm trung bình.
        </div>
        <?php else: ?>
        <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:0.85rem;color:#92400e;">
            ℹ️ Đây là <strong>lần làm lại</strong> — chỉ điểm lần đầu được tính vào điểm trung bình.
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div style="background:<?= $passed?'#d1fae5':'#fee2e2' ?>;border-radius:var(--radius);padding:32px;margin-bottom:16px;">
            <div style="font-size:3.5rem;font-weight:800;color:<?= $passed?'var(--success)':'var(--danger)' ?>;">
                <?= $scoreOn10 ?><span style="font-size:1.8rem;">/10</span>
            </div>
            <div style="font-size:0.875rem;color:var(--gray);">Điểm của bạn | Điểm qua môn: <?= $passingOn10 ?>/10</div>
        </div>

        <?php
            // Luôn hiển thị điểm TB — kể cả lần làm lại
            // Nếu lần làm lại thì dùng điểm TB đã lưu (is_first=1); nếu lần đầu thì dùng điểm vừa thi
            $displayAvgOn10 = $avgOn10;
            $displayCount   = (int)($avgData['quiz_count'] ?? 0);
            // Nếu chưa có bản ghi is_first nào (cột mới, chưa backfill xong)
            // thì fallback: tính TB từ TẤT CẢ lần thi của user trong khóa
            if ($displayAvgOn10 === null || $displayCount === 0) {
                $fbStmt = $db->prepare("
                    SELECT AVG(qa.score) as avg_score, COUNT(qa.id) as quiz_count
                    FROM quiz_attempts qa
                    JOIN quizzes q ON qa.quiz_id = q.id
                    WHERE qa.user_id = ? AND q.course_id = ?
                ");
                $fbStmt->execute([$_SESSION['user_id'] ?? 0, $quiz['course_id']]);
                $fbRow = $fbStmt->fetch(PDO::FETCH_ASSOC);
                if ($fbRow && $fbRow['avg_score'] !== null) {
                    $displayAvgOn10 = number_format($fbRow['avg_score'] / 10, 1);
                    $displayCount   = (int)$fbRow['quiz_count'];
                }
            }
        ?>
        <?php if ($displayAvgOn10 !== null && $displayCount > 0): ?>
        <div style="background:#f0f4ff;border-radius:var(--radius);padding:20px;margin-bottom:24px;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">📊 Điểm Trung Bình Khóa Học</div>
            <div style="font-size:2.2rem;font-weight:800;color:var(--primary);">
                <?= $displayAvgOn10 ?><span style="font-size:1rem;font-weight:500;">/10</span>
            </div>
            <div style="font-size:0.78rem;color:var(--gray);">Trung bình cộng từ <?= $displayCount ?> bài kiểm tra lần đầu</div>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <?php if ($quiz['course_id']): ?>
            <a href="?action=course&id=<?= $quiz['course_id'] ?>" class="btn btn-secondary">← Về Khóa Học</a>
            <?php if ($passed): ?>
            <a href="?action=certificate&course_id=<?= $quiz['course_id'] ?>" class="btn btn-success">🏆 Xem Chứng Chỉ</a>
            <?php else: ?>
            <a href="?action=quiz&id=<?= $quizId ?>&course_id=<?= $quiz['course_id'] ?>" class="btn btn-primary">🔄 Làm Lại</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
}


// ============================================================
// PAGE: DASHBOARD
// ============================================================
function pageDashboard($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $user = currentUser();
    $enrollments = $db->prepare("SELECT e.*, c.title, c.id as cid FROM enrollments e JOIN courses c ON e.course_id=c.id WHERE e.user_id=?");
    $enrollments->execute([$user['id']]);
    $enrollments = $enrollments->fetchAll(PDO::FETCH_ASSOC);
    $certs = $db->prepare("SELECT cert.*, c.title FROM certificates cert JOIN courses c ON cert.course_id=c.id WHERE cert.user_id=?");
    $certs->execute([$user['id']]);
    $certs = $certs->fetchAll(PDO::FETCH_ASSOC);

    // Tổng hợp điểm danh toàn bộ các khóa
    $attendanceSummary = $db->prepare("
        SELECT
            SUM(CASE WHEN ar.status='present' OR ar.status='late' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN ar.status='absent' THEN 1 ELSE 0 END) as total_absent,
            SUM(CASE WHEN ar.status='excused' THEN 1 ELSE 0 END) as total_excused,
            COUNT(*) as total_sessions
        FROM attendance_records ar
        WHERE ar.user_id = ?
    ");
    $attendanceSummary->execute([$user['id']]);
    $attSum = $attendanceSummary->fetch(PDO::FETCH_ASSOC);
    $totalAbsent  = (int)($attSum['total_absent'] ?? 0);
    $totalPresent = (int)($attSum['total_present'] ?? 0);
    $totalExcused = (int)($attSum['total_excused'] ?? 0);
    $totalSessions= (int)($attSum['total_sessions'] ?? 0);

    // Điểm danh chi tiết theo từng khóa học
    $absencePerCourse = $db->prepare("
        SELECT c.id as course_id, c.title as course_title,
               COUNT(*) as total_sessions,
               SUM(CASE WHEN ar.status='present' OR ar.status='late' THEN 1 ELSE 0 END) as present_count,
               SUM(CASE WHEN ar.status='absent' THEN 1 ELSE 0 END) as absent_count,
               SUM(CASE WHEN ar.status='excused' THEN 1 ELSE 0 END) as excused_count
        FROM attendance_records ar
        JOIN attendance_sessions s ON ar.session_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE ar.user_id = ?
        GROUP BY c.id, c.title
        ORDER BY absent_count DESC, c.title
    ");
    $absencePerCourse->execute([$user['id']]);
    $absenceData = $absencePerCourse->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:8px;">Xin chào, <?= h($user['full_name']) ?>! 👋</h1>
    <p style="color:var(--gray);margin-bottom:32px;">Đây là tổng quan tài khoản của bạn</p>

    <div class="grid grid-4" style="margin-bottom:32px;">
        <div class="stat-card"><div class="stat-icon" style="background:#dbeafe;">📚</div><div class="stat-body"><div class="num"><?= count($enrollments) ?></div><div class="lbl">Khóa đã đăng ký</div></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#d1fae5;">🏆</div><div class="stat-body"><div class="num"><?= count($certs) ?></div><div class="lbl">Chứng chỉ nhận được</div></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#fee2e2;">❌</div><div class="stat-body"><div class="num"><?= $totalAbsent ?></div><div class="lbl">Buổi vắng mặt</div></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#d1fae5;">✅</div><div class="stat-body"><div class="num"><?= $totalSessions > 0 ? round(($totalPresent+$totalExcused)/$totalSessions*100) : 0 ?>%</div><div class="lbl">Tỷ lệ chuyên cần</div></div></div>
    </div>

    <?php if (!empty($absenceData)): ?>
    <h2 style="font-size:1.25rem;font-weight:800;margin-bottom:16px;">📋 Thống Kê Buổi Nghỉ Theo Khóa Học</h2>
    <div class="table-wrapper" style="margin-bottom:32px;">
        <table>
            <thead>
                <tr>
                    <th>Khóa Học</th>
                    <th style="text-align:center;">Tổng Buổi</th>
                    <th style="text-align:center;">✅ Có Mặt</th>
                    <th style="text-align:center;">❌ Vắng</th>
                    <th style="text-align:center;">📋 Phép</th>
                    <th style="text-align:center;">Chuyên Cần</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($absenceData as $row):
                $attendRate = $row['total_sessions'] > 0
                    ? round(($row['present_count'] + $row['excused_count']) / $row['total_sessions'] * 100)
                    : 0;
                $rateColor = $attendRate >= 80 ? 'var(--success)' : ($attendRate >= 60 ? '#f59e0b' : 'var(--danger)');
                $absentBadge = $row['absent_count'] > 0 ? 'badge-danger' : 'badge-success';
            ?>
            <tr>
                <td><strong><?= h($row['course_title']) ?></strong></td>
                <td style="text-align:center;"><?= $row['total_sessions'] ?></td>
                <td style="text-align:center;">
                    <span class="badge badge-success"><?= $row['present_count'] ?></span>
                </td>
                <td style="text-align:center;">
                    <span class="badge <?= $absentBadge ?>" style="font-size:0.85rem;padding:4px 12px;">
                        <?= $row['absent_count'] ?> buổi
                    </span>
                </td>
                <td style="text-align:center;">
                    <span class="badge badge-warning"><?= $row['excused_count'] ?></span>
                </td>
                <td style="text-align:center;min-width:140px;">
                    <div style="display:flex;align-items:center;gap:8px;justify-content:center;">
                        <div style="flex:1;max-width:80px;">
                            <div class="progress"><div class="progress-bar" style="width:<?= $attendRate ?>%;background:<?= $rateColor ?>;"></div></div>
                        </div>
                        <span style="font-weight:800;color:<?= $rateColor ?>;font-size:0.875rem;"><?= $attendRate ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($enrollments)): ?>
    <h2 style="font-size:1.25rem;font-weight:800;margin-bottom:16px;">📖 Khóa Học Của Tôi</h2>
    <div class="grid grid-3" style="margin-bottom:32px;">
    <?php
    // Build absence lookup by course_id for quick access in the card loop
    $absenceLookup = [];
    foreach ($absenceData as $row) {
        $absenceLookup[$row['course_id']] = $row;
    }
    foreach($enrollments as $e):
        $progress = getCourseProgress($user['id'], $e['cid']);
        $att = $absenceLookup[$e['cid']] ?? null;
        $absentCount = $att ? (int)$att['absent_count'] : 0;
    ?>
    <div class="card">
        <h3 style="font-size:0.95rem;font-weight:700;margin-bottom:12px;"><?= h($e['title']) ?></h3>
        <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:6px;">
            <span>Tiến trình</span><span><?= $progress ?>%</span>
        </div>
        <div class="progress" style="margin-bottom:14px;"><div class="progress-bar" style="width:<?= $progress ?>%"></div></div>
        <?php if ($att): ?>
        <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
            <span style="font-size:0.78rem;background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-weight:700;">✅ <?= $att['present_count'] ?> có mặt</span>
            <span style="font-size:0.78rem;background:<?= $absentCount > 0 ? '#fee2e2' : '#d1fae5' ?>;color:<?= $absentCount > 0 ? '#991b1b' : '#065f46' ?>;padding:3px 10px;border-radius:20px;font-weight:700;">❌ <?= $absentCount ?> vắng</span>
            <?php if ($att['excused_count'] > 0): ?>
            <span style="font-size:0.78rem;background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:20px;font-weight:700;">📋 <?= $att['excused_count'] ?> phép</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?action=course&id=<?= $e['cid'] ?>" class="btn btn-primary btn-sm">Tiếp Tục</a>
            <?php if ($progress >= 100): ?>
            <a href="?action=certificate&course_id=<?= $e['cid'] ?>" class="btn btn-success btn-sm">🏆 Chứng Chỉ</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($certs)): ?>
    <h2 style="font-size:1.25rem;font-weight:800;margin-bottom:16px;">🏆 Chứng Chỉ Của Tôi</h2>
    <div class="grid grid-3">
    <?php foreach($certs as $cert): ?>
    <div class="card" style="text-align:center;">
        <div style="font-size:2rem;margin-bottom:8px;">🎓</div>
        <h3 style="font-size:0.9rem;font-weight:700;margin-bottom:4px;"><?= h($cert['title']) ?></h3>
        <div style="font-size:0.75rem;color:var(--gray);margin-bottom:12px;">Mã: <?= h($cert['cert_code']) ?></div>
        <a href="?action=certificate&course_id=<?= $cert['course_id'] ?>" class="btn btn-primary btn-sm">Xem Chứng Chỉ</a>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php
}

// ============================================================
// PAGE: CERTIFICATE
// ============================================================
function pageCertificate($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $courseId = (int)($_GET['course_id'] ?? 0);
    $user = currentUser();
    $course = $db->prepare("SELECT c.*, u.full_name as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id=u.id WHERE c.id=?");
    $course->execute([$courseId]);
    $course = $course->fetch(PDO::FETCH_ASSOC);
    $cert = $db->prepare("SELECT * FROM certificates WHERE user_id=? AND course_id=?");
    $cert->execute([$user['id'], $courseId]);
    $cert = $cert->fetch(PDO::FETCH_ASSOC);
    if (!$cert) { checkAndIssueCertificate($user['id'],$courseId); $cert = $db->prepare("SELECT * FROM certificates WHERE user_id=? AND course_id=?")->execute([$user['id'],$courseId]); }
    $cert = $db->prepare("SELECT * FROM certificates WHERE user_id=? AND course_id=?");
    $cert->execute([$user['id'], $courseId]);
    $cert = $cert->fetch(PDO::FETCH_ASSOC);
?>
<div class="container cert-wrap">
    <?php if ($cert): ?>
    <div style="text-align:center;margin-bottom:24px;">
        <h1 style="font-size:1.5rem;font-weight:800;">🏆 Chứng Chỉ Hoàn Thành</h1>
    </div>
    <div class="cert" id="certificate">
        <div class="cert-logo">🎓</div>
        <div style="font-size:0.7rem;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:var(--gray);margin-bottom:8px;">EDUVIET - Chứng Nhận Hoàn Thành Khóa Học</div>
        <div class="cert-border"></div>
        <div style="font-size:0.875rem;color:var(--gray);margin:20px 0 8px;">Chứng nhận rằng</div>
        <div class="cert-name"><?= h($user['full_name']) ?></div>
        <div style="font-size:0.875rem;color:var(--gray);margin-bottom:8px;">đã hoàn thành xuất sắc khóa học</div>
        <div class="cert-course"><?= h($course['title']) ?></div>
        <div class="cert-border"></div>
        <div style="display:flex;justify-content:space-between;margin-top:32px;font-size:0.8rem;color:var(--gray);">
            <div><strong><?= h($course['instructor_name']) ?></strong><br>Giảng Viên</div>
            <div>Mã chứng chỉ: <strong><?= h($cert['cert_code']) ?></strong><br>Ngày cấp: <?= date('d/m/Y', strtotime($cert['issued_at'])) ?></div>
        </div>
    </div>
    <div style="text-align:center;margin-top:24px;display:flex;gap:12px;justify-content:center;">
        <button onclick="window.print()" class="btn btn-primary">🖨 In / Tải PDF</button>
        <a href="?action=dashboard" class="btn btn-secondary">← Về Dashboard</a>
    </div>
    <?php else: ?>
    <div class="alert alert-error">Bạn chưa hoàn thành khóa học này. Hãy học đủ 100% các bài để nhận chứng chỉ!</div>
    <a href="?action=course&id=<?= $courseId ?>" class="btn btn-primary">Quay lại Khóa Học</a>
    <?php endif; ?>
</div>
<style>@media print { .nav,.footer,button,a.btn { display:none!important; } .cert { border-color: #333 !important; } }</style>
<?php
}

// ============================================================
// TEACHER PAGES
// ============================================================
