<?php
// ============================================================
// PAGE: MESSAGES (Hộp thư trao đổi)
// ============================================================
function pageMessages($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $user = currentUser();
    $uid = $user['id'];

    // Mark message read if viewing thread
    $viewId = (int)($_GET['view'] ?? 0);

    // Fetch conversations: group by thread (pair sender/receiver)
    // Get all messages involving this user, grouped by conversation partner
    $convStmt = $db->prepare("
        SELECT
            m.id, m.sender_id, m.receiver_id, m.subject, m.body, m.created_at, m.is_read, m.course_id,
            CASE WHEN m.sender_id=:uid THEN m.receiver_id ELSE m.sender_id END as partner_id,
            u.full_name as partner_name, u.role as partner_role,
            c.title as course_title
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.sender_id=:uid2 THEN m.receiver_id ELSE m.sender_id END
        LEFT JOIN courses c ON c.id = m.course_id
        WHERE (m.sender_id=:uid3 OR m.receiver_id=:uid4) AND m.parent_id IS NULL
        ORDER BY m.created_at DESC
    ");
    $convStmt->execute([':uid'=>$uid,':uid2'=>$uid,':uid3'=>$uid,':uid4'=>$uid]);
    $conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);

    // Deduplicate by partner_id, keep most recent
    $seen = [];
    $convList = [];
    foreach ($conversations as $c) {
        $key = $c['partner_id'] . '_' . ($c['course_id'] ?? 0);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            // Count unread in this conversation
            $unreadQ = $db->prepare("SELECT COUNT(*) FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0");
            $unreadQ->execute([$c['partner_id'], $uid]);
            $c['unread'] = $unreadQ->fetchColumn();
            $convList[] = $c;
        }
    }

    // Fetch teachers for student to start new conversation
    $teacherList = [];
    if (!hasRole('teacher')) {
        // Get teachers of courses student is enrolled in
        $tStmt = $db->prepare("
            SELECT DISTINCT u.id, u.full_name, u.role, c.id as course_id, c.title as course_title
            FROM enrollments e
            JOIN courses c ON e.course_id=c.id
            JOIN users u ON u.id=c.instructor_id
            WHERE e.user_id=?
        ");
        $tStmt->execute([$uid]);
        $teacherList = $tStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Teachers see students enrolled in their courses
        $sStmt = $db->prepare("
            SELECT DISTINCT u.id, u.full_name, u.role, c.id as course_id, c.title as course_title
            FROM enrollments e
            JOIN courses c ON e.course_id=c.id
            JOIN users u ON u.id=e.user_id
            WHERE c.instructor_id=?
        ");
        $sStmt->execute([$uid]);
        $teacherList = $sStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    ?>
<div style="max-width:1100px;margin:0 auto;padding:32px 20px;">
<h2 style="font-size:1.6rem;font-weight:700;margin-bottom:4px;">💬 Tin Nhắn</h2>
<p style="color:var(--gray);margin-bottom:24px;">Trao đổi với <?= hasRole('teacher') ? 'học viên' : 'giáo viên' ?> của bạn</p>

<div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;">

<!-- LEFT: Compose + Conversation list -->
<div>
    <!-- New Message Button -->
    <button onclick="document.getElementById('composePanel').style.display=document.getElementById('composePanel').style.display==='none'?'block':'none'"
        style="width:100%;padding:10px 16px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-weight:600;font-size:0.95rem;cursor:pointer;margin-bottom:16px;">
        ✏️ Soạn Tin Nhắn Mới
    </button>

    <!-- Compose Panel -->
    <div id="composePanel" style="display:none;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px;">
        <h4 style="margin:0 0 12px;font-size:1rem;">Gửi tin nhắn mới</h4>
        <form method="post" action="?action=send_message">
            <div style="margin-bottom:10px;">
                <label style="font-size:0.82rem;font-weight:600;color:var(--gray);display:block;margin-bottom:4px;">Gửi đến</label>
                <select name="receiver_id" required style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.9rem;">
                    <option value="">-- Chọn người nhận --</option>
                    <?php foreach ($teacherList as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= h($t['full_name']) ?> — <?= h($t['course_title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:0.82rem;font-weight:600;color:var(--gray);display:block;margin-bottom:4px;">Tiêu đề</label>
                <input name="subject" type="text" placeholder="Tiêu đề tin nhắn..." style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.9rem;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:0.82rem;font-weight:600;color:var(--gray);display:block;margin-bottom:4px;">Nội dung</label>
                <textarea name="body" rows="4" required placeholder="Nhập nội dung..." style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.9rem;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <button type="submit" style="background:var(--primary);color:#fff;border:none;border-radius:8px;padding:9px 20px;font-weight:600;cursor:pointer;width:100%;">📤 Gửi</button>
        </form>
    </div>

    <!-- Conversation List -->
    <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-weight:600;font-size:0.9rem;color:var(--gray);">Hội thoại</div>
        <?php if (empty($convList)): ?>
        <div style="padding:32px 16px;text-align:center;color:var(--gray);font-size:0.9rem;">Chưa có tin nhắn nào</div>
        <?php else: ?>
        <?php foreach ($convList as $conv): ?>
        <?php
            $isActive = ($viewId === $conv['id']);
            $partnerId = $conv['partner_id'];
            $roleLabel = $conv['partner_role'] === 'teacher' ? '👩‍🏫 GV' : '🎓 HV';
        ?>
        <a href="?action=message_thread&partner=<?= $partnerId ?>&course=<?= $conv['course_id'] ?? 0 ?>"
           style="display:block;padding:14px 16px;border-bottom:1px solid var(--border);text-decoration:none;background:<?= $conv['unread']>0?'rgba(99,102,241,0.06)':'transparent' ?>;transition:background 0.15s;"
           onmouseover="this.style.background='rgba(99,102,241,0.1)'" onmouseout="this.style.background='<?= $conv['unread']>0?'rgba(99,102,241,0.06)':'transparent' ?>'">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                        <?= mb_substr($conv['partner_name'],0,1) ?>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-weight:<?= $conv['unread']>0?'700':'500' ?>;color:var(--text);font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($conv['partner_name']) ?></div>
                        <div style="font-size:0.75rem;color:var(--gray);"><?= $roleLabel ?> <?= $conv['course_title'] ? '· '.h(mb_substr($conv['course_title'],0,20)) : '' ?></div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <?php if ($conv['unread'] > 0): ?>
                    <span style="background:var(--primary);color:#fff;border-radius:999px;font-size:0.7rem;padding:2px 7px;font-weight:700;"><?= $conv['unread'] ?></span>
                    <?php endif; ?>
                    <div style="font-size:0.7rem;color:var(--gray);margin-top:3px;"><?= date('d/m', strtotime($conv['created_at'])) ?></div>
                </div>
            </div>
            <div style="font-size:0.8rem;color:var(--gray);margin-top:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h(mb_substr($conv['body'],0,60)) ?></div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- RIGHT: Empty state or prompt to select -->
<div style="background:var(--card);border:1px solid var(--border);border-radius:12px;min-height:400px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--gray);">
    <div style="font-size:3rem;">💬</div>
    <div style="font-weight:600;font-size:1rem;">Chọn một hội thoại để xem</div>
    <div style="font-size:0.85rem;">hoặc soạn tin nhắn mới để bắt đầu</div>
</div>

</div>
</div>
<?php
}

// ============================================================
// PAGE: MESSAGE THREAD (Chi tiết hội thoại)
// ============================================================
function pageMessageThread($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $user = currentUser();
    $uid = $user['id'];
    $partnerId = (int)($_GET['partner'] ?? 0);
    $courseId = (int)($_GET['course'] ?? 0) ?: null;

    if (!$partnerId) { redirect('?action=messages'); return; }

    $partner = $db->prepare("SELECT * FROM users WHERE id=?");
    $partner->execute([$partnerId]);
    $partner = $partner->fetch(PDO::FETCH_ASSOC);
    if (!$partner) { redirect('?action=messages'); return; }

    // Mark all messages from partner as read
    $db->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?")->execute([$partnerId, $uid]);

    // Fetch all messages between the two users (for this course if set)
    $courseFilter = $courseId ? "AND (m.course_id=? OR m.course_id IS NULL)" : "";
    $params = $courseId ? [$uid, $partnerId, $partnerId, $uid, $courseId] : [$uid, $partnerId, $partnerId, $uid];
    $msgStmt = $db->prepare("
        SELECT m.*, u.full_name as sender_name, u.role as sender_role
        FROM messages m
        JOIN users u ON u.id=m.sender_id
        WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
        $courseFilter
        ORDER BY m.created_at ASC
    ");
    $msgStmt->execute($params);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

    // Course info
    $courseInfo = null;
    if ($courseId) {
        $cStmt = $db->prepare("SELECT title FROM courses WHERE id=?");
        $cStmt->execute([$courseId]);
        $courseInfo = $cStmt->fetch(PDO::FETCH_ASSOC);
    }

    // Conversations list for left panel (reuse logic)
    $convStmt = $db->prepare("
        SELECT
            m.id, m.sender_id, m.receiver_id, m.subject, m.body, m.created_at, m.is_read, m.course_id,
            CASE WHEN m.sender_id=:uid THEN m.receiver_id ELSE m.sender_id END as partner_id,
            u.full_name as partner_name, u.role as partner_role,
            c.title as course_title
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.sender_id=:uid2 THEN m.receiver_id ELSE m.sender_id END
        LEFT JOIN courses c ON c.id = m.course_id
        WHERE (m.sender_id=:uid3 OR m.receiver_id=:uid4) AND m.parent_id IS NULL
        ORDER BY m.created_at DESC
    ");
    $convStmt->execute([':uid'=>$uid,':uid2'=>$uid,':uid3'=>$uid,':uid4'=>$uid]);
    $conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);
    $seen = []; $convList = [];
    foreach ($conversations as $c) {
        $key = $c['partner_id'] . '_' . ($c['course_id'] ?? 0);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unreadQ = $db->prepare("SELECT COUNT(*) FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0");
            $unreadQ->execute([$c['partner_id'], $uid]);
            $c['unread'] = $unreadQ->fetchColumn();
            $convList[] = $c;
        }
    }

    // Teachers/students list for new compose
    $teacherList = [];
    if (!hasRole('teacher')) {
        $tStmt = $db->prepare("SELECT DISTINCT u.id, u.full_name, u.role, c.id as course_id, c.title as course_title FROM enrollments e JOIN courses c ON e.course_id=c.id JOIN users u ON u.id=c.instructor_id WHERE e.user_id=?");
        $tStmt->execute([$uid]);
        $teacherList = $tStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sStmt = $db->prepare("SELECT DISTINCT u.id, u.full_name, u.role, c.id as course_id, c.title as course_title FROM enrollments e JOIN courses c ON e.course_id=c.id JOIN users u ON u.id=e.user_id WHERE c.instructor_id=?");
        $sStmt->execute([$uid]);
        $teacherList = $sStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>
<div style="max-width:1100px;margin:0 auto;padding:32px 20px;">
<h2 style="font-size:1.6rem;font-weight:700;margin-bottom:4px;">💬 Tin Nhắn</h2>
<p style="color:var(--gray);margin-bottom:24px;">Trao đổi với <?= hasRole('teacher') ? 'học viên' : 'giáo viên' ?> của bạn</p>

<div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;">

<!-- LEFT panel -->
<div>
    <button onclick="document.getElementById('composePanel').style.display=document.getElementById('composePanel').style.display==='none'?'block':'none'"
        style="width:100%;padding:10px 16px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-weight:600;font-size:0.95rem;cursor:pointer;margin-bottom:16px;">
        ✏️ Soạn Tin Nhắn Mới
    </button>
    <div id="composePanel" style="display:none;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px;">
        <h4 style="margin:0 0 12px;font-size:1rem;">Gửi tin nhắn mới</h4>
        <form method="post" action="?action=send_message">
            <div style="margin-bottom:10px;">
                <label style="font-size:0.82rem;font-weight:600;color:var(--gray);display:block;margin-bottom:4px;">Gửi đến</label>
                <select name="receiver_id" required style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.9rem;">
                    <option value="">-- Chọn người nhận --</option>
                    <?php foreach ($teacherList as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id']==$partnerId?'selected':'' ?>><?= h($t['full_name']) ?> — <?= h($t['course_title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:0.82rem;font-weight:600;color:var(--gray);display:block;margin-bottom:4px;">Tiêu đề</label>
                <input name="subject" type="text" placeholder="Tiêu đề..." style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.9rem;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:0.82rem;font-weight:600;color:var(--gray);display:block;margin-bottom:4px;">Nội dung</label>
                <textarea name="body" rows="4" required placeholder="Nhập nội dung..." style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:0.9rem;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <button type="submit" style="background:var(--primary);color:#fff;border:none;border-radius:8px;padding:9px 20px;font-weight:600;cursor:pointer;width:100%;">📤 Gửi</button>
        </form>
    </div>
    <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-weight:600;font-size:0.9rem;color:var(--gray);">Hội thoại</div>
        <?php if (empty($convList)): ?>
        <div style="padding:32px 16px;text-align:center;color:var(--gray);font-size:0.9rem;">Chưa có tin nhắn nào</div>
        <?php else: ?>
        <?php foreach ($convList as $conv): ?>
        <?php $isActive = $conv['partner_id'] == $partnerId; $roleLabel = $conv['partner_role']==='teacher'?'👩‍🏫 GV':'🎓 HV'; ?>
        <a href="?action=message_thread&partner=<?= $conv['partner_id'] ?>&course=<?= $conv['course_id'] ?? 0 ?>"
           style="display:block;padding:14px 16px;border-bottom:1px solid var(--border);text-decoration:none;background:<?= $isActive?'rgba(99,102,241,0.12)':($conv['unread']>0?'rgba(99,102,241,0.06)':'transparent') ?>;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                    <div style="width:36px;height:36px;border-radius:50%;background:<?= $isActive?'var(--primary)':'var(--gray)' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                        <?= mb_substr($conv['partner_name'],0,1) ?>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-weight:<?= ($conv['unread']>0||$isActive)?'700':'500' ?>;color:var(--text);font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($conv['partner_name']) ?></div>
                        <div style="font-size:0.75rem;color:var(--gray);"><?= $roleLabel ?> <?= $conv['course_title'] ? '· '.h(mb_substr($conv['course_title'],0,18)) : '' ?></div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <?php if ($conv['unread'] > 0 && !$isActive): ?>
                    <span style="background:var(--primary);color:#fff;border-radius:999px;font-size:0.7rem;padding:2px 7px;font-weight:700;"><?= $conv['unread'] ?></span>
                    <?php endif; ?>
                    <div style="font-size:0.7rem;color:var(--gray);margin-top:3px;"><?= date('d/m', strtotime($conv['created_at'])) ?></div>
                </div>
            </div>
            <div style="font-size:0.8rem;color:var(--gray);margin-top:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h(mb_substr($conv['body'],0,60)) ?></div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- RIGHT: Chat thread -->
<div style="background:var(--card);border:1px solid var(--border);border-radius:12px;display:flex;flex-direction:column;min-height:520px;">
    <!-- Header -->
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;">
            <?= mb_substr($partner['full_name'],0,1) ?>
        </div>
        <div>
            <div style="font-weight:700;font-size:1rem;"><?= h($partner['full_name']) ?></div>
            <div style="font-size:0.78rem;color:var(--gray);">
                <?= $partner['role']==='teacher'?'👩‍🏫 Giảng Viên':'🎓 Học Viên' ?>
                <?= $courseInfo ? ' · '.h($courseInfo['title']) : '' ?>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <div id="msgList" style="flex:1;padding:20px;display:flex;flex-direction:column;gap:14px;overflow-y:auto;max-height:420px;">
        <?php if (empty($messages)): ?>
        <div style="text-align:center;color:var(--gray);margin:auto;font-size:0.9rem;">Chưa có tin nhắn nào. Hãy bắt đầu hội thoại!</div>
        <?php else: ?>
        <?php foreach ($messages as $msg): ?>
        <?php $isMine = $msg['sender_id'] == $uid; ?>
        <div style="display:flex;justify-content:<?= $isMine?'flex-end':'flex-start' ?>;gap:10px;align-items:flex-end;">
            <?php if (!$isMine): ?>
            <div style="width:32px;height:32px;border-radius:50%;background:var(--gray);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                <?= mb_substr($msg['sender_name'],0,1) ?>
            </div>
            <?php endif; ?>
            <div style="max-width:70%;">
                <?php if ($msg['subject']): ?>
                <div style="font-size:0.72rem;font-weight:600;color:var(--gray);margin-bottom:3px;text-align:<?= $isMine?'right':'left' ?>;">📌 <?= h($msg['subject']) ?></div>
                <?php endif; ?>
                <div style="padding:11px 15px;border-radius:<?= $isMine?'16px 4px 16px 16px':'4px 16px 16px 16px' ?>;background:<?= $isMine?'var(--primary)':'rgba(99,102,241,0.08)' ?>;color:<?= $isMine?'#fff':'var(--text)' ?>;font-size:0.9rem;line-height:1.5;word-break:break-word;">
                    <?= nl2br(h($msg['body'])) ?>
                </div>
                <div style="font-size:0.7rem;color:var(--gray);margin-top:4px;text-align:<?= $isMine?'right':'left' ?>;">
                    <?= date('H:i d/m/Y', strtotime($msg['created_at'])) ?>
                    <?php if ($isMine && $msg['is_read']): ?> · <span style="color:#22c55e;">✓ Đã đọc</span><?php endif; ?>
                </div>
            </div>
            <?php if ($isMine): ?>
            <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                <?= mb_substr($user['full_name'],0,1) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Reply box -->
    <div style="padding:16px 20px;border-top:1px solid var(--border);">
        <form method="post" action="?action=send_message" style="display:flex;gap:10px;align-items:flex-end;">
            <input type="hidden" name="receiver_id" value="<?= $partnerId ?>">
            <input type="hidden" name="course_id" value="<?= $courseId ?? 0 ?>">
            <textarea name="body" rows="2" required placeholder="Nhập tin nhắn..." id="replyBox"
                style="flex:1;padding:10px 14px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font-size:0.9rem;resize:none;font-family:inherit;"></textarea>
            <button type="submit"
                style="background:var(--primary);color:#fff;border:none;border-radius:10px;padding:10px 18px;font-weight:600;cursor:pointer;font-size:0.95rem;white-space:nowrap;">
                📤 Gửi
            </button>
        </form>
    </div>
</div>

</div>
</div>
<script>
// Auto-scroll to bottom of messages
var ml = document.getElementById('msgList');
if (ml) ml.scrollTop = ml.scrollHeight;
// Send on Ctrl+Enter
document.getElementById('replyBox') && document.getElementById('replyBox').addEventListener('keydown', function(e){
    if (e.ctrlKey && e.key === 'Enter') { this.closest('form').submit(); }
});
</script>
<?php
}

// ============================================================
// PAGE: QR CHECKIN (Học viên quét QR)
// ============================================================
function pageQrCheckin($db) {
    $token = trim($_GET['token'] ?? '');
    // Nếu chưa login thì lưu token vào session rồi redirect login
    if (!isLoggedIn()) {
        $_SESSION['qr_token_pending'] = $token;
        redirect('?action=login');
        return;
    }
    $user = currentUser();
    $result = null;
    $sessionInfo = null;

    if ($token) {
        // Validate token
        $qr = $db->prepare("SELECT q.*, s.session_name, s.session_date, c.title as course_title, s.course_id
            FROM qr_tokens q
            JOIN attendance_sessions s ON s.id = q.session_id
            JOIN courses c ON c.id = s.course_id
            WHERE q.token = ?");
        $qr->execute([$token]);
        $qrRow = $qr->fetch(PDO::FETCH_ASSOC);

        if (!$qrRow) {
            $result = ['success'=>false, 'message'=>'Mã QR không tồn tại hoặc đã bị xóa.'];
        } else {
            // Dùng MySQL NOW() để kiểm tra hết hạn — tránh lệch timezone PHP vs MySQL
            $expCheck = $db->prepare("SELECT expires_at > NOW() as valid FROM qr_tokens WHERE token=?");
            $expCheck->execute([$token]);
            $isValid = (int)$expCheck->fetchColumn();
            if (!$isValid) {
                $result = ['success'=>false, 'message'=>'Mã QR đã hết hạn! Liên hệ giáo viên để lấy mã mới.'];
            } else {
            $sessionInfo = $qrRow;
            // Kiểm tra enrollment
            $enroll = $db->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
            $enroll->execute([$user['id'], $qrRow['course_id']]);
            if (!$enroll->fetchColumn()) {
                $result = ['success'=>false, 'message'=>'Bạn chưa đăng ký khóa học "'.$qrRow['course_title'].'"!'];
            } else {
                $sessId = $qrRow['session_id'];
                // Check existing
                $chk = $db->prepare("SELECT id,status FROM attendance_records WHERE session_id=? AND user_id=?");
                $chk->execute([$sessId, $user['id']]);
                $existing = $chk->fetch(PDO::FETCH_ASSOC);
                if ($existing && $existing['status'] === 'present') {
                    $result = ['success'=>true, 'already'=>true, 'message'=>'Bạn đã điểm danh buổi này rồi! ✅'];
                } else {
                    if ($existing) {
                        $db->prepare("UPDATE attendance_records SET status='present', check_in_time=NOW(), ai_verified=0 WHERE session_id=? AND user_id=?")->execute([$sessId, $user['id']]);
                    } else {
                        $db->prepare("INSERT INTO attendance_records (session_id,user_id,status,check_in_time,ai_verified) VALUES (?,?,'present',NOW(),0)")->execute([$sessId, $user['id']]);
                    }
                    $result = ['success'=>true, 'already'=>false, 'message'=>'Điểm danh thành công! 🎉'];
                }
            }
            } // end isValid else
        }
    } else {
        $result = ['success'=>false, 'message'=>'Token không hợp lệ.'];
    }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Điểm Danh QR</title>
<style>
body { margin:0; background:linear-gradient(135deg,#0f0c29,#302b63,#24243e); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Be Vietnam Pro',sans-serif; }
.box { background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:24px; padding:40px 36px; max-width:400px; width:90%; text-align:center; backdrop-filter:blur(16px); }
.icon { font-size:4rem; margin-bottom:16px; }
.title { color:#fff; font-size:1.4rem; font-weight:900; margin-bottom:8px; }
.msg { color:rgba(255,255,255,0.65); font-size:0.92rem; margin-bottom:24px; line-height:1.6; }
.course-badge { background:rgba(99,102,241,0.2); border:1px solid rgba(99,102,241,0.4); border-radius:10px; padding:12px 16px; margin-bottom:20px; text-align:left; }
.course-badge .label { font-size:0.72rem; color:rgba(255,255,255,0.45); font-weight:600; text-transform:uppercase; letter-spacing:1px; }
.course-badge .value { color:#a5b4fc; font-size:0.92rem; font-weight:700; margin-top:2px; }
.btn { display:inline-block; padding:12px 28px; border-radius:12px; font-weight:700; font-size:0.92rem; text-decoration:none; cursor:pointer; border:none; }
.btn-primary { background:linear-gradient(135deg,#7c3aed,#4f46e5); color:#fff; }
.btn-secondary { background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.7); margin-left:10px; }
.success { border-color:rgba(74,222,128,0.4); }
.fail { border-color:rgba(248,113,113,0.4); }
</style>
</head>
<body>
<div class="box <?= $result['success'] ? 'success' : 'fail' ?>">
  <?php if ($result['success']): ?>
    <div class="icon"><?= !empty($result['already']) ? '✅' : '🎉' ?></div>
    <div class="title"><?= !empty($result['already']) ? 'Đã Điểm Danh' : 'Điểm Danh Thành Công!' ?></div>
    <?php if ($sessionInfo): ?>
    <div class="course-badge">
      <div class="label">📚 Khóa học</div>
      <div class="value"><?= htmlspecialchars($sessionInfo['course_title']) ?></div>
      <div class="label" style="margin-top:8px;">📋 Buổi học</div>
      <div class="value"><?= htmlspecialchars($sessionInfo['session_name']) ?> — <?= date('d/m/Y', strtotime($sessionInfo['session_date'])) ?></div>
      <div class="label" style="margin-top:8px;">👤 Học viên</div>
      <div class="value"><?= htmlspecialchars($user['full_name']) ?></div>
    </div>
    <?php endif; ?>
    <div class="msg"><?= htmlspecialchars($result['message']) ?></div>
  <?php else: ?>
    <div class="icon">❌</div>
    <div class="title">Điểm Danh Thất Bại</div>
    <div class="msg"><?= htmlspecialchars($result['message']) ?></div>
  <?php endif; ?>
  <a href="?action=student_checkin" class="btn btn-primary">📋 Xem lịch sử</a>
  <a href="?action=home" class="btn btn-secondary">🏠 Trang chủ</a>
</div>
</body>
</html>
<?php
}
