<?php
// ============================================================
// HELPERS
// ============================================================
function setting($key, $default='') {
    $db = getDB();
    $row = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key=?")->execute([$key]) ? $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key=?") : null;
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key=?");
    $stmt->execute([$key]);
    $row = $stmt->fetchColumn();
    return $row !== false ? $row : $default;
}

function isLoggedIn() { return isset($_SESSION['user_id']); }
function currentUser() { 
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function hasRole($role) {
    $u = currentUser();
    if (!$u) return false;
    if ($role === 'admin') return $u['role'] === 'admin';
    if ($role === 'teacher') return in_array($u['role'], ['admin','teacher']);
    return true;
}
function redirect($url) { header("Location: $url"); exit; }
function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function slugify($s) { return strtolower(preg_replace('/[^a-zA-Z0-9]+/','-', $s)); }

function getCourseProgress($userId, $courseId) {
    $db = getDB();
    $total = $db->prepare("SELECT COUNT(*) FROM lessons l JOIN sections s ON l.section_id=s.id WHERE s.course_id=?");
    $total->execute([$courseId]);
    $totalCount = (int)$total->fetchColumn();
    if ($totalCount === 0) return 0;
    $done = $db->prepare("SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON lp.lesson_id=l.id JOIN sections s ON l.section_id=s.id WHERE s.course_id=? AND lp.user_id=? AND lp.completed=1");
    $done->execute([$courseId, $userId]);
    $doneCount = (int)$done->fetchColumn();
    return $totalCount > 0 ? round($doneCount/$totalCount*100) : 0;
}

function checkAndIssueCertificate($userId, $courseId) {
    $progress = getCourseProgress($userId, $courseId);
    if ($progress < 100) return false;
    $db = getDB();
    $exists = $db->prepare("SELECT id FROM certificates WHERE user_id=? AND course_id=?");
    $exists->execute([$userId, $courseId]);
    if ($exists->fetchColumn()) return true;
    $code = strtoupper(bin2hex(random_bytes(6)));
    $stmt = $db->prepare("INSERT IGNORE INTO certificates (user_id,course_id,cert_code) VALUES (?,?,?)");
    $stmt->execute([$userId, $courseId, $code]);
    return true;
}

function embedYoutube($url) {
    if (empty($url)) return '';
    if (strpos($url, 'embed') !== false) return $url;
    preg_match('/(?:v=|youtu\.be\/)([^&\?]+)/', $url, $m);
    if (isset($m[1])) return 'https://www.youtube.com/embed/'.$m[1];
    return $url;
}

