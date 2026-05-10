<?php
// ============================================================
// ATTENDANCE PAGES
// ============================================================
function pageAttendanceList($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $user = currentUser();
    $where = $user['role']==='admin' ? "1=1" : "s.instructor_id=".$user['id'];
    $sessions = $db->query("
        SELECT s.*, c.title as course_title,
               (SELECT COUNT(*) FROM attendance_records WHERE session_id=s.id AND status='present') as present_count,
               (SELECT COUNT(*) FROM attendance_records WHERE session_id=s.id) as total
        FROM attendance_sessions s
        JOIN courses c ON s.course_id=c.id
        WHERE $where
        ORDER BY s.session_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $courses = $db->query(
        "SELECT c.id, c.title, u.full_name as instructor_name 
         FROM courses c 
         LEFT JOIN users u ON c.instructor_id=u.id 
         WHERE c.is_published=1 
         ORDER BY c.title"
    )->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h1 style="font-size:1.75rem;font-weight:800;">📋 Quản Lý Điểm Danh</h1>
        <div style="display:flex;gap:8px;">
            
            <button onclick="toggleForm('new-session-form')" class="btn btn-primary">+ Tạo Buổi Mới</button>
        </div>
    </div>

    <!-- TẠO BUỔI MỚI -->
    <div id="new-session-form" style="display:none;margin-bottom:20px;" class="card">
        <h2 style="font-size:1.05rem;font-weight:700;margin-bottom:16px;">➕ Tạo Buổi Điểm Danh Mới</h2>
        <form method="post" action="?action=create_attendance">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label>Khóa Học</label>
                    <select name="course_id" required>
                        <option value="">-- Chọn khóa học --</option>
                        <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= h($c['title']) ?><?= !empty($c['instructor_name']) ? ' — ' . h($c['instructor_name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Tên Buổi Học</label>
                    <input type="text" name="session_name" placeholder="Buổi 1: Giới thiệu" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Ngày Học</label>
                    <input type="date" name="session_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div style="display:flex;gap:8px;padding-bottom:1px;">
                    <button type="submit" class="btn btn-primary">Tạo</button>
                    <button type="button" onclick="toggleForm('new-session-form')" class="btn btn-secondary">Hủy</button>
                </div>
            </div>
        </form>
    </div>

    <!-- MODAL CHỈNH SỬA -->
    <div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div class="card" style="width:100%;max-width:520px;margin:24px;">
            <div class="card-header">
                <h3 class="card-title">✏️ Chỉnh Sửa Buổi Điểm Danh</h3>
                <button onclick="closeEdit()" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--gray);">✕</button>
            </div>
            <form method="post" action="?action=edit_attendance_session" style="padding-top:16px;">
                <input type="hidden" name="session_id" id="edit-session-id">
                <div class="form-group">
                    <label>Khóa Học</label>
                    <select name="course_id" id="edit-course-id" required>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= h($c['title']) ?><?= !empty($c['instructor_name']) ? ' — ' . h($c['instructor_name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tên Buổi Học</label>
                    <input type="text" name="session_name" id="edit-session-name" required>
                </div>
                <div class="form-group">
                    <label>Ngày Học</label>
                    <input type="date" name="session_date" id="edit-session-date" required>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">💾 Lưu Thay Đổi</button>
                    <button type="button" onclick="closeEdit()" class="btn btn-secondary">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DANH SÁCH BUỔI HỌC -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Tên Buổi</th>
                    <th>Khóa Học</th>
                    <th>Ngày</th>
                    <th>Có Mặt</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($sessions as $s): ?>
            <tr>
                <td><strong><?= h($s['session_name']) ?></strong></td>
                <td style="color:var(--gray);font-size:0.875rem;"><?= h($s['course_title']) ?></td>
                <td style="font-size:0.875rem;"><?= date('d/m/Y', strtotime($s['session_date'])) ?></td>
                <td>
                    <span class="badge <?= $s['present_count']>0?'badge-success':'badge-danger' ?>">
                        <?= $s['present_count'] ?>/<?= $s['total'] ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="?action=attendance_session&id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">📋 Quản Lý</a>
                        <button class="btn btn-secondary btn-sm"
                            onclick="openEdit(<?= $s['id'] ?>, <?= $s['course_id'] ?>, '<?= addslashes(h($s['session_name'])) ?>', '<?= $s['session_date'] ?>')">
                            ✏️ Sửa
                        </button>
                        <form method="post" action="?action=delete_attendance_session"
                              onsubmit="return confirm('Xóa buổi này và toàn bộ dữ liệu điểm danh?')"
                              style="margin:0;">
                            <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">🗑 Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sessions)): ?>
            <tr>
                <td colspan="5" style="text-align:center;color:var(--gray);padding:32px;">
                    Chưa có buổi điểm danh nào.
                    <a href="#" onclick="toggleForm('new-session-form');return false;">Tạo buổi đầu tiên!</a>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleForm(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function openEdit(id, courseId, name, date) {
    document.getElementById('edit-session-id').value  = id;
    document.getElementById('edit-session-name').value = name;
    document.getElementById('edit-session-date').value = date;
    document.getElementById('edit-course-id').value   = courseId;
    const modal = document.getElementById('edit-modal');
    modal.style.display = 'flex';
}

function closeEdit() {
    document.getElementById('edit-modal').style.display = 'none';
}

// Đóng modal khi click ra ngoài
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
<?php
}

function pageAttendanceSession($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $id = (int)($_GET['id'] ?? 0);
    $session = $db->prepare("SELECT s.*, c.title as course_title FROM attendance_sessions s JOIN courses c ON s.course_id=c.id WHERE s.id=?");
    $session->execute([$id]);
    $session = $session->fetch(PDO::FETCH_ASSOC);
    if (!$session) { echo '<div class="container"><div class="alert alert-error">Không tìm thấy!</div></div>'; return; }
    $records = $db->prepare("SELECT ar.*, u.full_name, u.email FROM attendance_records ar JOIN users u ON ar.user_id=u.id WHERE ar.session_id=? ORDER BY u.full_name");
    $records->execute([$id]);
    $records = $records->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <a href="?action=attendance_list" class="btn btn-secondary btn-sm">← Quay lại</a>
        <div>
            <h1 style="font-size:1.5rem;font-weight:800;"><?= h($session['session_name']) ?></h1>
            <div style="color:var(--gray);font-size:0.875rem;"><?= h($session['course_title']) ?> — <?= date('d/m/Y', strtotime($session['session_date'])) ?></div>
        </div>
        
        <button onclick="openQrModal(<?= $id ?>)" class="btn btn-primary" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">📱 Tạo Mã QR</button>
    </div>

    <!-- QR MODAL -->
    <div id="qr-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
      <div style="background:#fff;border-radius:20px;padding:32px;max-width:420px;width:90%;text-align:center;position:relative;">
        <button onclick="closeQrModal()" style="position:absolute;top:14px;right:18px;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;">✕</button>
        <h2 style="font-size:1.15rem;font-weight:800;margin-bottom:6px;">📱 Mã QR Điểm Danh</h2>
        <p style="color:#6b7280;font-size:0.82rem;margin-bottom:18px;">Học viên dùng điện thoại quét mã để điểm danh</p>
        <div style="margin-bottom:14px;">
          <label style="font-size:0.82rem;font-weight:600;color:#374151;">Thời hạn mã (phút):</label>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:8px;flex-wrap:wrap;">
            <?php foreach([5,10,15,20,30] as $m): ?>
            <button class="qr-min-btn" data-min="<?=$m?>" onclick="setMinutes(<?=$m?>)"
              style="padding:6px 14px;border:2px solid #e5e7eb;border-radius:8px;background:#f9fafb;font-weight:700;cursor:pointer;font-size:0.85rem;"><?=$m?>p</button>
            <?php endforeach; ?>
          </div>
        </div>
        <button onclick="generateQr()" class="btn btn-primary" style="width:100%;margin-bottom:18px;font-size:1rem;padding:12px;">⚡ Tạo Mã QR</button>
        <div id="qr-display" style="display:none;">
          <div id="qr-canvas" style="margin:0 auto 14px;display:flex;justify-content:center;"></div>
          <div id="qr-timer" style="font-size:1.2rem;font-weight:900;color:#7c3aed;margin-bottom:8px;"></div>
          <p style="font-size:0.75rem;color:#9ca3af;word-break:break-all;" id="qr-url-text"></p>
        </div>
        <div id="qr-loading" style="display:none;color:#6b7280;">⏳ Đang tạo...</div>
        <div id="qr-error" style="display:none;color:#ef4444;margin-top:10px;"></div>
      </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    var _qrSessionId = <?= $id ?>;
    var _qrMinutes = 15;
    var _qrTimerInterval = null;
    var _qrExpires = null;
    var _qrCodeObj = null;

    function setMinutes(m) {
        _qrMinutes = m;
        document.querySelectorAll('.qr-min-btn').forEach(b => {
            b.style.background = b.dataset.min == m ? '#7c3aed' : '#f9fafb';
            b.style.color = b.dataset.min == m ? '#fff' : '#374151';
            b.style.borderColor = b.dataset.min == m ? '#7c3aed' : '#e5e7eb';
        });
    }
    setMinutes(15);

    function openQrModal(id) {
        _qrSessionId = id;
        document.getElementById('qr-modal').style.display = 'flex';
        document.getElementById('qr-display').style.display = 'none';
        document.getElementById('qr-error').style.display = 'none';
    }
    function closeQrModal() {
        document.getElementById('qr-modal').style.display = 'none';
        if (_qrTimerInterval) clearInterval(_qrTimerInterval);
    }

    function generateQr() {
        document.getElementById('qr-loading').style.display = 'block';
        document.getElementById('qr-display').style.display = 'none';
        document.getElementById('qr-error').style.display = 'none';
        if (_qrTimerInterval) clearInterval(_qrTimerInterval);

        var fd = new FormData();
        fd.append('session_id', _qrSessionId);
        fd.append('qr_minutes', _qrMinutes);
        fetch('?action=generate_qr', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            document.getElementById('qr-loading').style.display = 'none';
            if (!data.success) {
                document.getElementById('qr-error').style.display = 'block';
                document.getElementById('qr-error').textContent = data.message || 'Lỗi tạo QR';
                return;
            }
            document.getElementById('qr-display').style.display = 'block';
            document.getElementById('qr-url-text').textContent = data.url;
            var canvas = document.getElementById('qr-canvas');
            canvas.innerHTML = '';
            _qrCodeObj = new QRCode(canvas, { text: data.url, width: 220, height: 220, colorDark:'#1e1b4b', colorLight:'#fff', correctLevel: QRCode.CorrectLevel.M });
            // Dùng server_now để tính offset, tránh lệch giờ với client
            var serverNow = new Date(data.server_now.replace(' ','T'));
            var serverExpires = new Date(data.expires_at.replace(' ','T'));
            var totalSeconds = Math.round((serverExpires - serverNow) / 1000);
            _qrExpires = new Date(Date.now() + totalSeconds * 1000);
            startTimer();
        })
        .catch(() => {
            document.getElementById('qr-loading').style.display = 'none';
            document.getElementById('qr-error').style.display = 'block';
            document.getElementById('qr-error').textContent = 'Lỗi kết nối!';
        });
    }

    function startTimer() {
        function tick() {
            var now = new Date();
            var diff = Math.max(0, Math.round((_qrExpires - now) / 1000));
            var m = Math.floor(diff/60), s = diff%60;
            var el = document.getElementById('qr-timer');
            if (diff <= 0) {
                el.textContent = '⏰ Mã đã hết hạn!';
                el.style.color = '#ef4444';
                clearInterval(_qrTimerInterval);
            } else {
                el.textContent = '⏱ Còn lại: ' + m + ':' + String(s).padStart(2,'0');
                el.style.color = diff < 60 ? '#ef4444' : '#7c3aed';
            }
        }
        tick();
        _qrTimerInterval = setInterval(tick, 1000);
    }
    </script>
    <?php
    $present = count(array_filter($records, fn($r) => $r['status']==='present'));
    $total = count($records);
    ?>
    <div class="grid grid-4" style="margin-bottom:24px;">
        <div class="stat-card"><div class="stat-icon" style="background:#d1fae5;">✅</div><div class="stat-body"><div class="num"><?= $present ?></div><div class="lbl">Có mặt</div></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#fee2e2;">❌</div><div class="stat-body"><div class="num"><?= $total-$present ?></div><div class="lbl">Vắng mặt</div></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#dbeafe;">👥</div><div class="stat-body"><div class="num"><?= $total ?></div><div class="lbl">Tổng số</div></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#fef3c7;">📊</div><div class="stat-body"><div class="num"><?= $total>0?round($present/$total*100):0 ?>%</div><div class="lbl">Tỷ lệ</div></div></div>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="font-size:1.1rem;font-weight:700;">Danh Sách Điểm Danh</h2>
            <div style="display:flex;gap:8px;">
                <button onclick="markAll('present')" class="btn btn-success btn-sm">✅ Tất Cả Có Mặt</button>
                <button onclick="markAll('absent')" class="btn btn-danger btn-sm">❌ Tất Cả Vắng</button>
            </div>
        </div>
        <form method="post" action="?action=save_attendance">
            <input type="hidden" name="session_id" value="<?= $id ?>">
            <table style="width:100%;">
                <thead><tr><th>#</th><th>Họ Tên</th><th>Email</th><th>Trạng Thái</th><th>Ghi Chú</th></tr></thead>
                <tbody>
                <?php foreach($records as $i => $r): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= h($r['full_name']) ?></strong></td>
                    <td style="font-size:0.8rem;color:var(--gray);"><?= h($r['email']) ?></td>
                    <td>
                        <select name="attendance[<?= $r['user_id'] ?>]" class="att-select" style="width:auto;padding:6px 12px;">
                            <option value="present" <?= $r['status']==='present'?'selected':'' ?>>✅ Có mặt</option>
                            <option value="absent" <?= $r['status']==='absent'?'selected':'' ?>>❌ Vắng mặt</option>
                            <option value="late" <?= $r['status']==='late'?'selected':'' ?>>⏰ Đi muộn</option>
                            <option value="excused" <?= $r['status']==='excused'?'selected':'' ?>>📋 Phép</option>
                        </select>
                    </td>
                    <td><?= $r['ai_verified'] ? '<span class="badge badge-success">🤖 AI xác nhận</span>' : '' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">💾 Lưu Điểm Danh</button>
            </div>
        </form>
    </div>
</div>
<script>
function markAll(status) {
    document.querySelectorAll('.att-select').forEach(s => s.value = status);
}
</script>
<?php
}

// ============================================================
// AJAX: api_session_students — dùng trong index.php routing
// ============================================================
function handleApiSessionStudents($db) {
    $sessId = (int)($_GET['session_id'] ?? 0);
    $records = $db->prepare("
        SELECT DISTINCT e.user_id as id, u.full_name as name, u.email
        FROM enrollments e
        JOIN attendance_sessions s ON s.course_id = e.course_id
        JOIN users u ON u.id = e.user_id
        LEFT JOIN attendance_records ar ON ar.session_id = ? AND ar.user_id = e.user_id
        WHERE s.id = ?
    ");
    $records->execute([$sessId, $sessId]);
    header('Content-Type: application/json');
    echo json_encode($records->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ============================================================
// PAGE: AI ATTENDANCE (stub — chuyển hướng về attendance_list)
// ============================================================
function pageAiAttendance($db) {
    redirect('?action=attendance_list');
}
