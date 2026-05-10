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
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: <?= h($primaryColor) ?>;
    --primary-dark: #1a3fb5;
    --primary-light: #eff4ff;
    --primary-glow: rgba(37,99,235,0.18);
    --secondary: #f59e0b;
    --success: #10b981;
    --danger: #ef4444;
    --dark: #0d1526;
    --dark2: #1e293b;
    --gray: #64748b;
    --gray-light: #94a3b8;
    --light: #f4f7fd;
    --border: #e4eaf4;
    --white: #ffffff;
    --shadow: 0 2px 16px rgba(14,30,70,0.07);
    --shadow-md: 0 6px 24px rgba(14,30,70,0.10);
    --shadow-lg: 0 16px 48px rgba(14,30,70,0.14);
    --shadow-glow: 0 4px 20px rgba(37,99,235,0.22);
    --radius: 14px;
    --radius-sm: 9px;
    --radius-lg: 20px;
    --font: 'Nunito', sans-serif;
    --font-display: 'Playfair Display', serif;
    --nav-h: 68px;
    --transition: 0.22s cubic-bezier(.4,0,.2,1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; touch-action: manipulation; }
body { font-family: var(--font); background: var(--light); color: var(--dark); line-height: 1.65; -webkit-text-size-adjust: 100%; }
a { color: var(--primary); text-decoration: none; -webkit-tap-highlight-color: transparent; }
button { -webkit-tap-highlight-color: transparent; touch-action: manipulation; }
img { max-width: 100%; }

/* ═══════════════ NAV ═══════════════ */
.nav {
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(228,234,244,0.8);
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 1px 0 rgba(228,234,244,0.6), var(--shadow);
}
.nav-inner {
    max-width: 1240px; margin: 0 auto; padding: 0 28px;
    display: flex; align-items: center; gap: 8px;
    height: var(--nav-h);
}
.nav-brand {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.22rem; font-weight: 900; color: var(--dark);
    letter-spacing: -0.3px; flex-shrink: 0; margin-right: 12px;
}
.nav-brand span { font-size: 1.7rem; }
.nav-brand strong { 
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.nav-links { display: flex; gap: 2px; flex: 1; }
.nav-links a {
    padding: 7px 14px; border-radius: 9px;
    font-weight: 700; color: var(--gray); font-size: 0.875rem;
    transition: all var(--transition); position: relative; white-space: nowrap;
}
.nav-links a:hover { background: var(--primary-light); color: var(--primary); }
.nav-links a.active {
    background: var(--primary-light); color: var(--primary);
}
.nav-links a.active::after {
    content: ''; position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%);
    width: 16px; height: 2.5px; background: var(--primary); border-radius: 2px;
}
.nav-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }

/* ═══════════════ BUTTONS ═══════════════ */
.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 20px; border-radius: var(--radius-sm);
    font-weight: 700; font-size: 0.875rem; cursor: pointer;
    border: none; transition: all var(--transition);
    font-family: var(--font); white-space: nowrap; letter-spacing: 0.1px;
}
.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, #3b5fe2 100%);
    color: var(--white); box-shadow: 0 2px 8px var(--primary-glow);
}
.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #2a4fd4 100%);
    transform: translateY(-1px); box-shadow: var(--shadow-glow);
}
.btn-primary:active { transform: translateY(0); }
.btn-secondary {
    background: var(--white); color: var(--dark2);
    border: 1.5px solid var(--border);
    box-shadow: var(--shadow);
}
.btn-secondary:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.btn-success { background: linear-gradient(135deg,#10b981,#059669); color: var(--white); box-shadow: 0 2px 8px rgba(16,185,129,0.2); }
.btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(16,185,129,0.3); }
.btn-danger { background: linear-gradient(135deg,#ef4444,#dc2626); color: var(--white); }
.btn-danger:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(239,68,68,0.3); }
.btn-sm { padding: 6px 14px; font-size: 0.8rem; }
.btn-lg { padding: 14px 32px; font-size: 1rem; border-radius: var(--radius); }

/* ═══════════════ USER DROPDOWN ═══════════════ */
.user-menu { position: relative; cursor: pointer; -webkit-tap-highlight-color: transparent; }
.user-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    color: white; display: flex; align-items: center; justify-content: center;
    font-weight: 800; cursor: pointer; font-size: 1rem;
    box-shadow: 0 2px 10px var(--primary-glow);
    border: 2.5px solid white;
}
.dropdown {
    position: absolute; right: 0; top: calc(100% + 10px);
    background: var(--white); border: 1px solid var(--border);
    border-radius: var(--radius); box-shadow: var(--shadow-lg);
    min-width: 230px; display: none; z-index: 1001; overflow: hidden;
    animation: dropIn 0.18s ease;
}
@keyframes dropIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
.dropdown.show { display: block; }
.dropdown-header {
    padding: 16px 18px;
    background: linear-gradient(135deg, var(--primary-light), #f0ebff);
    border-bottom: 1px solid var(--border);
}
.dropdown-header .name { font-weight: 800; font-size: 0.95rem; }
.dropdown-header .role {
    font-size: 0.73rem; color: var(--primary); font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px; margin-top: 2px;
}
.dropdown a, .dropdown-item {
    display: flex; align-items: center; gap: 10px; padding: 11px 18px;
    color: var(--dark2); font-size: 0.875rem; cursor: pointer;
    transition: background var(--transition); border: none;
    background: none; width: 100%; font-family: var(--font); font-weight: 600;
}
.dropdown a:hover, .dropdown-item:hover { background: var(--light); color: var(--primary); }
.dropdown-divider { border-top: 1px solid var(--border); margin: 4px 0; }

/* ═══════════════ MAIN / LAYOUT ═══════════════ */
.main { min-height: calc(100vh - var(--nav-h)); }
.container { max-width: 1240px; margin: 0 auto; padding: 0 28px; }

/* ═══════════════ HERO ═══════════════ */
.hero {
    background: linear-gradient(140deg, #1230a8 0%, var(--primary) 40%, #1d4ed8 70%, #1e3a8a 100%);
    color: white; padding: 88px 0 80px; position: relative; overflow: hidden;
}
.hero::before {
    content: ''; position: absolute; inset: 0;
    background: 
        radial-gradient(ellipse 60% 80% at 80% 40%, rgba(255,255,255,0.06) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 20% 80%, rgba(124,58,237,0.18) 0%, transparent 60%);
}
.hero::after {
    content: ''; position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.07) 1px, transparent 1px);
    background-size: 32px 32px;
}
.hero-inner { max-width: 1240px; margin: 0 auto; padding: 0 28px; position: relative; z-index: 1; }
.hero h1 {
    font-family: var(--font-display);
    font-size: clamp(2.2rem, 4.5vw, 3.6rem);
    font-weight: 800; line-height: 1.18; margin-bottom: 22px;
    letter-spacing: -1px; text-shadow: 0 2px 20px rgba(0,0,0,0.15);
}
.hero p { font-size: 1.1rem; opacity: 0.88; max-width: 580px; margin-bottom: 40px; line-height: 1.75; font-weight: 500; }
.hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
.hero-stats { display: flex; gap: 56px; margin-top: 56px; flex-wrap: wrap; }
.stat-num { font-size: 2.1rem; font-weight: 900; display: block; letter-spacing: -1px; }
.stat-label { font-size: 0.82rem; opacity: 0.75; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

/* ═══════════════ SECTIONS ═══════════════ */
.section { padding: 64px 0; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 36px; }
.section-title { font-size: 1.75rem; font-weight: 900; letter-spacing: -0.5px; }
.section-title span { color: var(--primary); }

/* ═══════════════ COURSE CARDS ═══════════════ */
.grid { display: grid; gap: 24px; }
.grid-3 { grid-template-columns: repeat(3,1fr); }
.grid-4 { grid-template-columns: repeat(4,1fr); }
.grid-2 { grid-template-columns: repeat(2,1fr); }

.course-card {
    background: var(--white); border-radius: var(--radius-lg);
    overflow: hidden; box-shadow: var(--shadow);
    transition: all 0.28s cubic-bezier(.4,0,.2,1);
    border: 1px solid var(--border);
}
.course-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: #d0daf4; }
.course-thumb {
    width: 100%; height: 186px; object-fit: cover;
    background: linear-gradient(135deg, var(--primary-light) 0%, #e0e7ff 100%);
    display: flex; align-items: center; justify-content: center; font-size: 3.2rem;
    position: relative; overflow: hidden;
}
.course-thumb img { width: 100%; height: 100%; object-fit: cover; }
.course-body { padding: 20px 22px 16px; }
.course-category {
    font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1.2px; color: var(--primary);
    background: var(--primary-light); padding: 3px 11px;
    border-radius: 20px; display: inline-block; margin-bottom: 10px;
}
.course-title { font-size: 1.02rem; font-weight: 800; margin-bottom: 8px; line-height: 1.4; }
.course-meta { display: flex; gap: 12px; font-size: 0.78rem; color: var(--gray); margin-bottom: 16px; font-weight: 600; }
.course-price { font-size: 1.1rem; font-weight: 900; color: var(--primary); }
.course-price.free { color: var(--success); }
.course-footer {
    padding: 14px 22px; border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}

/* ═══════════════ CATEGORY CHIPS ═══════════════ */
.cat-grid { display: flex; flex-wrap: wrap; gap: 12px; }
.cat-chip {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: 50px; padding: 10px 22px;
    display: flex; align-items: center; gap: 8px;
    font-weight: 700; cursor: pointer; transition: all var(--transition);
    color: var(--dark2); font-size: 0.875rem;
    box-shadow: var(--shadow);
}
.cat-chip:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); transform: translateY(-1px); box-shadow: var(--shadow-md); }

/* ═══════════════ FORMS ═══════════════ */
.form-group { margin-bottom: 20px; }
label { display: block; font-weight: 700; margin-bottom: 7px; font-size: 0.85rem; color: var(--dark2); }
input, select, textarea {
    width: 100%; padding: 11px 15px;
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    font-family: var(--font); font-size: 0.9rem; color: var(--dark);
    transition: all var(--transition); background: var(--white);
    font-weight: 500;
}
input:focus, select:focus, textarea:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3.5px rgba(37,99,235,0.1);
    background: #fafcff;
}
textarea { min-height: 100px; resize: vertical; }
.form-hint { font-size: 0.78rem; color: var(--gray-light); margin-top: 4px; font-weight: 600; }

/* ═══════════════ ALERTS ═══════════════ */
.alert {
    padding: 14px 18px; border-radius: var(--radius-sm);
    margin-bottom: 20px; font-weight: 700; display: flex;
    align-items: center; gap: 10px; font-size: 0.9rem;
}
.alert-success { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1.5px solid #fca5a5; }
.alert-info { background: var(--primary-light); color: #1e40af; border: 1.5px solid #93c5fd; }

/* ═══════════════ PROGRESS ═══════════════ */
.progress { background: var(--border); border-radius: 99px; height: 8px; overflow: hidden; }
.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), #6366f1);
    border-radius: 99px; transition: width 0.6s cubic-bezier(.4,0,.2,1);
}

/* ═══════════════ BADGES ═══════════════ */
.badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.2px; }
.badge-primary { background: var(--primary-light); color: var(--primary); }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }

/* ═══════════════ ADMIN SIDEBAR ═══════════════ */
.admin-layout { display: grid; grid-template-columns: 260px 1fr; gap: 0; min-height: calc(100vh - var(--nav-h)); }
.sidebar {
    background: linear-gradient(180deg, var(--dark) 0%, #162039 100%);
    color: white; padding: 24px 0;
    border-right: 1px solid rgba(255,255,255,0.05);
}
.sidebar-section { margin-bottom: 8px; }
.sidebar-title {
    font-size: 0.68rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1.8px; color: #4a6080; padding: 8px 22px 4px;
}
.sidebar a {
    display: flex; align-items: center; gap: 10px; padding: 10px 22px;
    color: #7a95b8; font-size: 0.875rem; font-weight: 700; transition: all var(--transition);
    border-left: 3px solid transparent; margin: 1px 0;
}
.sidebar a:hover { color: #c8d8f0; background: rgba(255,255,255,0.05); }
.sidebar a.active { color: white; background: rgba(255,255,255,0.08); border-left-color: var(--primary); }
.admin-content { padding: 36px; background: var(--light); }

/* ═══════════════ TABLE ═══════════════ */
.table-wrapper {
    background: var(--white); border-radius: var(--radius);
    overflow: hidden; border: 1px solid var(--border);
    box-shadow: var(--shadow);
}
table { width: 100%; border-collapse: collapse; }
th {
    background: var(--light); padding: 13px 18px;
    text-align: left; font-size: 0.75rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.7px;
    color: var(--gray); border-bottom: 1.5px solid var(--border);
}
td { padding: 13px 18px; border-bottom: 1px solid var(--border); font-size: 0.875rem; vertical-align: middle; font-weight: 600; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fbff; }

/* ═══════════════ CARD ═══════════════ */
.card {
    background: var(--white); border-radius: var(--radius);
    padding: 26px; border: 1px solid var(--border); box-shadow: var(--shadow);
}
.card-header {
    border-bottom: 1px solid var(--border); margin: -26px -26px 26px;
    padding: 20px 26px; display: flex; align-items: center; justify-content: space-between;
}
.card-title { font-size: 1.1rem; font-weight: 800; }

/* ═══════════════ CURRICULUM ═══════════════ */
.section-item {
    background: var(--white); border: 1px solid var(--border);
    border-radius: var(--radius-sm); margin-bottom: 10px; overflow: hidden;
}
.section-header-row {
    background: var(--light); padding: 14px 20px; font-weight: 800;
    display: flex; align-items: center; gap: 10px; cursor: pointer;
    font-size: 0.9rem;
}
.lesson-list { padding: 6px 0; }
.lesson-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 20px 10px 40px; color: var(--dark2); font-size: 0.875rem;
    transition: background var(--transition); border-bottom: 1px solid var(--border);
    font-weight: 600;
}
.lesson-item:last-child { border-bottom: none; }
.lesson-item:hover { background: var(--light); color: var(--primary); }
.lesson-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0; }
.lesson-icon.done { background: var(--success); color: white; }
.lesson-icon.video { background: var(--primary-light); color: var(--primary); }
.lesson-icon.text { background: #fef3c7; color: #92400e; }
.lesson-icon.quiz { background: #fce7f3; color: #9d174d; }

/* ═══════════════ QUIZ ═══════════════ */
.quiz-question {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius); padding: 24px; margin-bottom: 16px;
}
.quiz-question h4 { margin-bottom: 16px; font-size: 1rem; font-weight: 800; }
.option-label {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px;
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    margin-bottom: 8px; cursor: pointer; transition: all var(--transition);
    font-weight: 600;
}
.option-label:hover { border-color: var(--primary); background: var(--primary-light); }
.option-label input[type=radio]:checked + .option-text { color: var(--primary); font-weight: 700; }
.option-label:has(input:checked) { border-color: var(--primary); background: var(--primary-light); }

/* ═══════════════ CERTIFICATE ═══════════════ */
.cert-wrap { max-width: 800px; margin: 40px auto; }
.cert {
    background: var(--white);
    border: 6px solid transparent;
    background-clip: padding-box;
    border-radius: 20px; padding: 60px; text-align: center;
    box-shadow: var(--shadow-lg), 0 0 0 6px var(--primary);
}
.cert-logo { font-size: 4rem; margin-bottom: 16px; }
.cert-title { font-size: 0.85rem; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; color: var(--gray); }
.cert-name { font-family: var(--font-display); font-size: 2.5rem; font-weight: 800; color: var(--primary); margin: 16px 0; }
.cert-course { font-size: 1.3rem; font-weight: 700; margin: 8px 0 24px; }
.cert-border { height: 3px; background: linear-gradient(90deg, var(--primary), #7c3aed); border-radius: 2px; margin: 24px 0; }

/* ═══════════════ SEARCH / STAT CARDS ═══════════════ */
.search-bar { display: flex; gap: 12px; align-items: center; }
.search-input { flex: 1; }
.stat-card {
    background: var(--white); border-radius: var(--radius);
    padding: 22px 24px; border: 1px solid var(--border);
    display: flex; align-items: center; gap: 16px;
    box-shadow: var(--shadow); transition: all var(--transition);
}
.stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.stat-body .num { font-size: 1.9rem; font-weight: 900; line-height: 1; letter-spacing: -1px; }
.stat-body .lbl { font-size: 0.78rem; color: var(--gray); margin-top: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }

/* ═══════════════ MOBILE NAV ═══════════════ */
.hamburger { display: none; background: none; border: none; cursor: pointer; flex-direction: column; gap: 5px; padding: 6px; border-radius: 8px; }
.hamburger span { display: block; width: 22px; height: 2.5px; background: var(--dark2); border-radius: 2px; transition: all var(--transition); }
.hamburger:hover { background: var(--primary-light); }
.mobile-nav { display: none; position: fixed; inset: 0; background: rgba(13,21,38,0.55); z-index: 999; backdrop-filter: blur(4px); }
.mobile-nav-inner {
    background: var(--white); width: 290px; height: 100%;
    padding: 28px 20px; overflow-y: auto;
    box-shadow: var(--shadow-lg);
}

/* ═══════════════ AI ATTENDANCE ═══════════════ */
.ai-panel { background: linear-gradient(135deg, #1e1b4b, #312e81); color: white; border-radius: var(--radius); padding: 32px; text-align: center; }
.ai-panel h2 { font-size: 1.5rem; font-weight: 900; margin-bottom: 8px; }
.ai-status { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 20px; font-size: 0.875rem; margin-bottom: 24px; }
.pulse { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.6;transform:scale(1.3)} }
.video-area { background: rgba(0,0,0,0.4); border-radius: var(--radius-sm); aspect-ratio: 4/3; max-height: 320px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; position: relative; overflow: hidden; }
#webcam { width: 100%; height: 100%; object-fit: cover; }
.detection-overlay { position: absolute; inset: 0; pointer-events: none; }

/* ═══════════════ TABS ═══════════════ */
.tabs { display: flex; gap: 4px; border-bottom: 2px solid var(--border); margin-bottom: 24px; }
.tab { padding: 11px 22px; font-weight: 700; font-size: 0.875rem; cursor: pointer; color: var(--gray); border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all var(--transition); border-radius: 8px 8px 0 0; }
.tab:hover { color: var(--primary); background: var(--primary-light); }
.tab.active { color: var(--primary); border-bottom-color: var(--primary); background: var(--primary-light); }

/* ═══════════════ FOOTER ═══════════════ */
.footer {
    background: linear-gradient(135deg, var(--dark) 0%, #162039 100%);
    color: #6a8aac; text-align: center; padding: 36px;
    font-size: 0.875rem; font-weight: 600;
    border-top: 1px solid rgba(255,255,255,0.04);
}

/* ═══════════════ RESPONSIVE ═══════════════ */
@media(max-width: 768px) {
    .grid-3,.grid-4 { grid-template-columns: 1fr; }
    .grid-2 { grid-template-columns: 1fr; }
    .admin-layout { grid-template-columns: 1fr; }
    .sidebar { display: none; }
    .nav-links { display: none; }
    .hamburger { display: flex; }
    .hero { padding: 52px 0 44px; }
    .hero h1 { letter-spacing: -0.5px; }
    .hero-stats { gap: 28px; }
    .section { padding: 44px 0; }
    .btn, button, a.btn { min-height: 44px; padding-top: 10px; padding-bottom: 10px; }
    .btn-sm { min-height: 36px; padding-top: 7px; padding-bottom: 7px; }
    table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .container { padding-left: 16px !important; padding-right: 16px !important; }
    .card { border-radius: var(--radius); }
    .grid-4 { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; }
    .dropdown { right: 0; left: auto; min-width: 210px; }
    .nav-actions .btn-sm { font-size: 0.8rem; padding: 7px 12px; }
    input, select, textarea { width: 100%; }
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
            <span><?= h($logo) ?></span><strong><?= h($siteName) ?></strong>
        </a>
        <div class="nav-links">
            <a href="?action=home" class="<?= $action==='home'?'active':'' ?>">🏠 Trang Chủ</a>
            <a href="?action=courses" class="<?= $action==='courses'?'active':'' ?>">📚 Khóa Học</a>
            <?php if (hasRole('teacher')): ?>
            <a href="?action=teacher_courses" class="<?= in_array($action,['teacher_courses','manage_course','create_course'])?'active':'' ?>">🎓 Giảng Dạy</a>
            <a href="?action=teacher_students">👨‍👩‍👧‍👦 Học Viên</a>
            <a href="?action=attendance_list">📋 Điểm Danh</a>
            <a href="?action=exam_list" class="<?= in_array($action,['exam_list','manage_exam','exam_results','admin_exams'])?'active':'' ?>">📝 Kỳ Thi</a>
            <?php elseif (isLoggedIn()): ?>
            <a href="?action=student_checkin">📸 Điểm Danh</a>
            <a href="?action=student_exams" class="<?= in_array($action,['student_exams','do_exam','exam_result'])?'active':'' ?>">📝 Kỳ Thi</a>
            <?php endif; ?>
            <?php if (hasRole('admin')): ?>
            <a href="?action=admin_users" class="<?= strpos($action,'admin')===0?'active':'' ?>">⚙️ Quản Trị</a>
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
            <a href="?action=exam_list" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">📝 Kỳ Thi Cuối Kỳ</a>
            <?php elseif (isLoggedIn()): ?>
            <a href="?action=student_checkin" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">📸 Điểm Danh</a>
            <a href="?action=student_exams" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">📝 Kỳ Thi Cuối Kỳ</a>
            <?php endif; ?>
            <?php if (hasRole('admin')): ?>
            <a href="?action=admin_users" style="padding:12px 14px;border-radius:10px;font-weight:600;color:var(--dark);display:block;" onclick="closeMobileNav()">⚙️ Quản Trị</a>
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

