<?php
// ============================================================
// LAYOUT
// ============================================================
function renderLayout($content, $action) {
    $siteName = setting('site_name','EduViet LMS');
    $logo = setting('site_logo','🎓');
    $primaryColor = setting('primary_color','#2563eb');
    $user = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($siteName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: <?= h($primaryColor) ?>;
    --primary-dark: #1d4ed8;
    --primary-light: #dbeafe;
    --secondary: #f59e0b;
    --success: #10b981;
    --danger: #ef4444;
    --dark: #0f172a;
    --gray: #64748b;
    --light: #f8fafc;
    --border: #e2e8f0;
    --white: #ffffff;
    --shadow: 0 4px 24px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 40px rgba(0,0,0,0.12);
    --radius: 12px;
    --radius-sm: 8px;
    --font: 'Be Vietnam Pro', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; touch-action: manipulation; }
body { font-family: var(--font); background: var(--light); color: var(--dark); line-height: 1.6; -webkit-text-size-adjust: 100%; }
a { color: var(--primary); text-decoration: none; -webkit-tap-highlight-color: transparent; }
button { -webkit-tap-highlight-color: transparent; touch-action: manipulation; }
img { max-width: 100%; }
/* NAV */
.nav { background: var(--white); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.nav-inner { max-width: 1200px; margin: 0 auto; padding: 0 24px; display: flex; align-items: center; gap: 32px; height: 64px; }
.nav-brand { display: flex; align-items: center; gap: 10px; font-size: 1.3rem; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; }
.nav-brand span { font-size: 1.8rem; }
.nav-links { display: flex; gap: 4px; flex: 1; }
.nav-links a { padding: 8px 16px; border-radius: 8px; font-weight: 500; color: var(--gray); transition: all 0.2s; font-size: 0.9rem; }
.nav-links a:hover, .nav-links a.active { background: var(--primary-light); color: var(--primary); }
.nav-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 20px; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.875rem; cursor: pointer; border: none; transition: all 0.2s; font-family: var(--font); white-space: nowrap; }
.btn-primary { background: var(--primary); color: var(--white); }
.btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
.btn-secondary { background: var(--white); color: var(--dark); border: 1.5px solid var(--border); }
.btn-secondary:hover { border-color: var(--primary); color: var(--primary); }
.btn-success { background: var(--success); color: var(--white); }
.btn-success:hover { background: #059669; }
.btn-danger { background: var(--danger); color: var(--white); }
.btn-danger:hover { background: #dc2626; }
.btn-sm { padding: 6px 14px; font-size: 0.8rem; }
.btn-lg { padding: 14px 32px; font-size: 1rem; }
/* USER DROPDOWN */
.user-menu { position: relative; cursor: pointer; -webkit-tap-highlight-color: transparent; }
.user-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; cursor: pointer; font-size: 0.9rem; }
.dropdown { position: absolute; right: 0; top: calc(100% + 8px); background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-lg); min-width: 220px; display: none; z-index: 1001; overflow: hidden; }
.dropdown.show { display: block; }
.dropdown-header { padding: 16px; background: var(--primary-light); border-bottom: 1px solid var(--border); }
.dropdown-header .name { font-weight: 700; font-size: 0.95rem; }
.dropdown-header .role { font-size: 0.78rem; color: var(--primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.dropdown a, .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 11px 16px; color: var(--dark); font-size: 0.875rem; cursor: pointer; transition: background 0.15s; border: none; background: none; width: 100%; font-family: var(--font); }
.dropdown a:hover, .dropdown-item:hover { background: var(--light); }
.dropdown-divider { border-top: 1px solid var(--border); margin: 4px 0; }
/* MAIN */
.main { min-height: calc(100vh - 64px); }
/* CONTAINER */
.container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
/* HERO */
.hero { background: linear-gradient(135deg, var(--primary) 0%, #1e40af 50%, #1d4ed8 100%); color: white; padding: 80px 0; position: relative; overflow: hidden; }
.hero::before { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
.hero-inner { max-width: 1200px; margin: 0 auto; padding: 0 24px; position: relative; }
.hero h1 { font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 800; line-height: 1.2; margin-bottom: 20px; letter-spacing: -1px; }
.hero p { font-size: 1.15rem; opacity: 0.9; max-width: 600px; margin-bottom: 36px; line-height: 1.7; }
.hero-actions { display: flex; gap: 16px; flex-wrap: wrap; }
.hero-stats { display: flex; gap: 48px; margin-top: 48px; flex-wrap: wrap; }
.stat { }
.stat-num { font-size: 2rem; font-weight: 800; display: block; }
.stat-label { font-size: 0.85rem; opacity: 0.8; }
/* SECTION */
.section { padding: 60px 0; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
.section-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
.section-title span { color: var(--primary); }
/* CARDS */
.grid { display: grid; gap: 24px; }
.grid-3 { grid-template-columns: repeat(3,1fr); }
.grid-4 { grid-template-columns: repeat(4,1fr); }
.grid-2 { grid-template-columns: repeat(2,1fr); }
.course-card { background: var(--white); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); transition: all 0.3s; border: 1px solid var(--border); }
.course-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
.course-thumb { width: 100%; height: 180px; object-fit: cover; background: linear-gradient(135deg, var(--primary-light), #ddd); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
.course-thumb img { width: 100%; height: 100%; object-fit: cover; }
.course-body { padding: 20px; }
.course-category { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--primary); background: var(--primary-light); padding: 3px 10px; border-radius: 20px; display: inline-block; margin-bottom: 10px; }
.course-title { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; line-height: 1.4; }
.course-meta { display: flex; gap: 12px; font-size: 0.8rem; color: var(--gray); margin-bottom: 16px; }
.course-price { font-size: 1.1rem; font-weight: 800; color: var(--primary); }
.course-price.free { color: var(--success); }
.course-footer { padding: 16px 20px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
/* CATEGORY CHIPS */
.cat-grid { display: flex; flex-wrap: wrap; gap: 12px; }
.cat-chip { background: var(--white); border: 1.5px solid var(--border); border-radius: 50px; padding: 10px 24px; display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; color: var(--dark); }
.cat-chip:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
/* FORMS */
.form-group { margin-bottom: 20px; }
label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.875rem; color: var(--dark); }
input, select, textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: var(--font); font-size: 0.9rem; color: var(--dark); transition: border-color 0.2s; background: var(--white); }
input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
textarea { min-height: 100px; resize: vertical; }
.form-hint { font-size: 0.78rem; color: var(--gray); margin-top: 4px; }
/* ALERTS */
.alert { padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.alert-info { background: var(--primary-light); color: #1e40af; border: 1px solid #93c5fd; }
/* PROGRESS */
.progress { background: var(--border); border-radius: 99px; height: 8px; overflow: hidden; }
.progress-bar { height: 100%; background: linear-gradient(90deg, var(--primary), var(--success)); border-radius: 99px; transition: width 0.5s; }
/* BADGE */
.badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
.badge-primary { background: var(--primary-light); color: var(--primary); }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }
/* ADMIN SIDEBAR */
.admin-layout { display: grid; grid-template-columns: 260px 1fr; gap: 0; min-height: calc(100vh - 64px); }
.sidebar { background: var(--dark); color: white; padding: 24px 0; }
.sidebar-section { margin-bottom: 8px; }
.sidebar-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #64748b; padding: 8px 20px 4px; }
.sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #94a3b8; font-size: 0.875rem; font-weight: 500; transition: all 0.15s; }
.sidebar a:hover, .sidebar a.active { color: white; background: rgba(255,255,255,0.08); }
.admin-content { padding: 32px; background: var(--light); }
/* TABLE */
.table-wrapper { background: var(--white); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); }
table { width: 100%; border-collapse: collapse; }
th { background: var(--light); padding: 12px 16px; text-align: left; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray); border-bottom: 1px solid var(--border); }
td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 0.875rem; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: var(--light); }
/* CARD */
.card { background: var(--white); border-radius: var(--radius); padding: 24px; border: 1px solid var(--border); box-shadow: var(--shadow); }
.card-header { border-bottom: 1px solid var(--border); margin: -24px -24px 24px; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; }
.card-title { font-size: 1.1rem; font-weight: 700; }
/* CURRICULUM TREE */
.curriculum { }
.section-item { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 12px; overflow: hidden; }
.section-header-row { background: var(--light); padding: 14px 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; cursor: pointer; }
.lesson-list { padding: 8px 0; }
.lesson-item { display: flex; align-items: center; gap: 12px; padding: 10px 20px 10px 40px; color: var(--dark); font-size: 0.875rem; transition: background 0.15s; border-bottom: 1px solid var(--border); }
.lesson-item:last-child { border-bottom: none; }
.lesson-item:hover { background: var(--light); }
.lesson-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0; }
.lesson-icon.done { background: var(--success); color: white; }
.lesson-icon.video { background: var(--primary-light); color: var(--primary); }
.lesson-icon.text { background: #fef3c7; color: #92400e; }
.lesson-icon.quiz { background: #fce7f3; color: #9d174d; }
/* QUIZ */
.quiz-question { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 16px; }
.quiz-question h4 { margin-bottom: 16px; font-size: 1rem; }
.option-label { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 8px; cursor: pointer; transition: all 0.2s; }
.option-label:hover { border-color: var(--primary); background: var(--primary-light); }
.option-label input[type=radio]:checked + .option-text { color: var(--primary); font-weight: 600; }
.option-label:has(input:checked) { border-color: var(--primary); background: var(--primary-light); }
/* CERTIFICATE */
.cert-wrap { max-width: 800px; margin: 40px auto; }
.cert { background: var(--white); border: 8px solid var(--primary); border-radius: 16px; padding: 60px; text-align: center; box-shadow: var(--shadow-lg); }
.cert-logo { font-size: 4rem; margin-bottom: 16px; }
.cert-title { font-size: 0.9rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--gray); }
.cert-name { font-size: 2.5rem; font-weight: 800; color: var(--primary); margin: 16px 0; }
.cert-course { font-size: 1.3rem; font-weight: 600; margin: 8px 0 24px; }
.cert-border { height: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); border-radius: 2px; margin: 24px 0; }
/* SEARCH BAR */
.search-bar { display: flex; gap: 12px; align-items: center; }
.search-input { flex: 1; }
/* STAT CARDS */
.stat-card { background: var(--white); border-radius: var(--radius); padding: 24px; border: 1px solid var(--border); display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.stat-body .num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
.stat-body .lbl { font-size: 0.8rem; color: var(--gray); margin-top: 4px; }
/* MOBILE NAV */
.hamburger { display: none; background: none; border: none; cursor: pointer; flex-direction: column; gap: 5px; padding: 4px; }
.hamburger span { display: block; width: 24px; height: 2px; background: var(--dark); border-radius: 2px; }
.mobile-nav { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; }
.mobile-nav-inner { background: var(--white); width: 280px; height: 100%; padding: 24px; overflow-y: auto; }
/* AI ATTENDANCE */
.ai-panel { background: linear-gradient(135deg, #1e1b4b, #312e81); color: white; border-radius: var(--radius); padding: 32px; text-align: center; }
.ai-panel h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 8px; }
.ai-status { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 20px; font-size: 0.875rem; margin-bottom: 24px; }
.pulse { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.6;transform:scale(1.3)} }
.video-area { background: rgba(0,0,0,0.4); border-radius: var(--radius-sm); aspect-ratio: 4/3; max-height: 320px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; position: relative; overflow: hidden; }
#webcam { width: 100%; height: 100%; object-fit: cover; }
.detection-overlay { position: absolute; inset: 0; pointer-events: none; }
/* TABS */
.tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 24px; }
.tab { padding: 12px 24px; font-weight: 600; font-size: 0.9rem; cursor: pointer; color: var(--gray); border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
.tab.active { color: var(--primary); border-bottom-color: var(--primary); }
/* FOOTER */
.footer { background: var(--dark); color: #94a3b8; text-align: center; padding: 32px; font-size: 0.875rem; }
/* RESPONSIVE */
@media(max-width: 768px) {
    .grid-3,.grid-4 { grid-template-columns: 1fr; }
    .grid-2 { grid-template-columns: 1fr; }
    .admin-layout { grid-template-columns: 1fr; }
    .sidebar { display: none; }
    .nav-links { display: none; }
    .hamburger { display: flex; }
    .hero { padding: 48px 0; }
    .hero-stats { gap: 24px; }
    .section { padding: 40px 0; }
    /* Fix touch targets — tất cả nút/link tối thiểu 44px */
    .btn, button, a.btn { min-height: 44px; padding-top: 10px; padding-bottom: 10px; }
    .btn-sm { min-height: 36px; padding-top: 7px; padding-bottom: 7px; }
    /* Tables scroll ngang thay vì tràn */
    table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    /* Container padding nhỏ hơn */
    .container { padding-left: 16px !important; padding-right: 16px !important; }
    /* Cards full width */
    .card { border-radius: 12px; }
    /* Stat cards 2 cột trên mobile */
    .grid-4 { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; }
    /* Fix dropdown user menu */
    .dropdown { right: 0; left: auto; min-width: 200px; }
    /* Nav action buttons nhỏ hơn */
    .nav-actions .btn-sm { font-size: 0.8rem; padding: 7px 12px; }
    /* Forms full width */
    input, select, textarea { width: 100%; }
    /* Fix grid forms thành 1 cột */
    div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
    div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
    div[style*="grid-template-columns: 1fr 1fr 1fr"] { grid-template-columns: 1fr !important; }
    div[style*="grid-template-columns:1fr 1fr 1fr"] { grid-template-columns: 1fr !important; }
    div[style*="grid-template-columns: 1fr 1fr 1fr auto"] { grid-template-columns: 1fr !important; }
}
@media(max-width:1024px) {
    .grid-3 { grid-template-columns: repeat(2,1fr); }
    .grid-4 { grid-template-columns: repeat(2,1fr); }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav">
    <div class="nav-inner">
        <a href="?action=home" class="nav-brand">
            <span><?= h($logo) ?></span><?= h($siteName) ?>
        </a>
        <div class="nav-links">
            <a href="?action=home" class="<?= $action==='home'?'active':'' ?>">🏠 Trang Chủ</a>
            <a href="?action=courses" class="<?= $action==='courses'?'active':'' ?>">📚 Khóa Học</a>
            <?php if (hasRole('teacher')): ?>
            <a href="?action=teacher_courses" class="<?= in_array($action,['teacher_courses','manage_course','create_course'])?'active':'' ?>">🎓 Giảng Dạy</a>
            <a href="?action=teacher_students">👨‍👩‍👧‍👦 Học Viên</a>
            <a href="?action=attendance_list">📋 Điểm Danh</a>
            <?php endif; ?>
            <?php if (hasRole('admin')): ?>
            <a href="?action=admin_users" class="<?= strpos($action,'admin')===0?'active':'' ?>">⚙️ Quản Trị</a>
            <?php endif; ?>
            <?php if (isLoggedIn() && !hasRole('teacher')): ?>
            <a href="?action=student_checkin">📸 Điểm Danh</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
            <?php
            $db2 = getDB();
            $unread = $db2->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
            $unread->execute([$user['id']]);
            $unreadCount = $unread->fetchColumn();
            ?>
            <a href="?action=messages" class="<?= $action==='messages'||$action==='message_thread'?'active':'' ?>" style="position:relative;">
                💬 Tin Nhắn
                <?php if ($unreadCount > 0): ?>
                <span style="background:#ef4444;color:#fff;border-radius:999px;font-size:0.7rem;padding:1px 6px;position:absolute;top:2px;right:2px;"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
        </div>
        <div class="nav-actions">
            <?php if ($user): ?>
            <div class="user-menu" onclick="this.querySelector('.dropdown').classList.toggle('show')">
                <div class="user-avatar"><?= mb_substr($user['full_name'],0,1) ?></div>
                <div class="dropdown">
                    <div class="dropdown-header">
                        <div class="name"><?= h($user['full_name']) ?></div>
                        <div class="role"><?= $user['role']==='admin'?'Quản Trị Viên':($user['role']==='teacher'?'Giảng Viên':'Học Viên') ?></div>
                    </div>
                    <a href="?action=dashboard">📊 Tổng Quan</a>
                    <div class="dropdown-divider"></div>
                    <form method="post" action="?action=logout">
                        <button type="submit" class="dropdown-item">🚪 Đăng Xuất</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <a href="?action=login" class="btn btn-secondary btn-sm">Đăng Nhập</a>
            <a href="?action=register" class="btn btn-primary btn-sm">Đăng Ký</a>
            <?php endif; ?>
        </div>
        <button class="hamburger" onclick="document.getElementById('mobileNav').style.display='flex'">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- MOBILE NAV DRAWER -->
<div class="mobile-nav" id="mobileNav" onclick="if(event.target===this)closeMobileNav()">
    <div class="mobile-nav-inner">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <span style="font-size:1.1rem;font-weight:800;"><?= h($logo) ?> <?= h($siteName) ?></span>
            <button onclick="closeMobileNav()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--gray);">✕</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <a href="?action=home" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">🏠 Trang Chủ</a>
            <a href="?action=courses" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">📚 Khóa Học</a>
            <?php if (hasRole('teacher')): ?>
            <a href="?action=teacher_courses" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">🎓 Giảng Dạy</a>
            <a href="?action=teacher_students" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">👨‍👩‍👧‍👦 Học Viên</a>
            <a href="?action=attendance_list" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">📋 Điểm Danh</a>
            <?php endif; ?>
            <?php if (hasRole('admin')): ?>
            <a href="?action=admin_users" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">⚙️ Quản Trị</a>
            <?php endif; ?>
            <?php if (isLoggedIn() && !hasRole('teacher')): ?>
            <a href="?action=student_checkin" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">📸 Điểm Danh</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
            <a href="?action=messages" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">💬 Tin Nhắn</a>
            <a href="?action=dashboard" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">📊 Tổng Quan</a>
            <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
            <form method="post" action="?action=logout">
                <button type="submit" style="width:100%;text-align:left;padding:12px 14px;border-radius:10px;font-weight:600;color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;">🚪 Đăng Xuất</button>
            </form>
            <?php else: ?>
            <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
            <a href="?action=login" class="btn btn-secondary" style="display:block;text-align:center;margin-bottom:8px;" onclick="closeMobileNav()">Đăng Nhập</a>
            <a href="?action=register" class="btn btn-primary" style="display:block;text-align:center;" onclick="closeMobileNav()">Đăng Ký</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function closeMobileNav() {
    document.getElementById('mobileNav').style.display = 'none';
}
// Close on back button / swipe
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeMobileNav(); });
</script>

<!-- ALERTS -->
<?php if (isset($_SESSION['success'])): ?>
<div style="max-width:1200px;margin:16px auto;padding:0 24px;">
<div class="alert alert-success">✅ <?= h($_SESSION['success']) ?></div>
</div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (isset($_SESSION['error'])): ?>
<div style="max-width:1200px;margin:16px auto;padding:0 24px;">
<div class="alert alert-error">❌ <?= h($_SESSION['error']) ?></div>
</div>
<?php unset($_SESSION['error']); endif; ?>

<main class="main"><?= $content ?></main>

<footer class="footer">
    <div>© <?= date('Y') ?> <?= h($siteName) ?> — Nền tảng học trực tuyến Việt Nam 🇻🇳</div>
</footer>

<script>
// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu')) {
        document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('show'));
    }
});
</script>
</body>
</html>
<?php
}

