<?php
// PAGE: LOGIN / REGISTER
// ============================================================
function pageLogin() { ?>
<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f0f4ff,#e8f0fe);padding:24px;">
<div class="card" style="width:100%;max-width:420px;">
    <div style="text-align:center;margin-bottom:32px;">
        <div style="font-size:3rem;margin-bottom:8px;">🎓</div>
        <h1 style="font-size:1.5rem;font-weight:800;">Đăng Nhập</h1>
        <p style="color:var(--gray);font-size:0.875rem;">Chào mừng trở lại EduViet!</p>
    </div>
    <form method="post" action="?action=login">
        <div class="form-group">
            <label>Email hoặc Tên đăng nhập</label>
            <input type="text" name="email" placeholder="admin@lms.vn" required>
        </div>
        <div class="form-group">
            <label>Mật Khẩu</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">Đăng Nhập</button>
    </form>
    <div style="margin-top:16px;background:var(--light);border-radius:var(--radius-sm);padding:12px;font-size:0.78rem;color:var(--gray);">
        <strong>Tài khoản mẫu:</strong><br>
        Admin: admin / admin123<br>
        Giảng viên: teacher1 / teacher123<br>
        Học viên: student1 / student123
    </div>
    <div style="text-align:center;margin-top:20px;font-size:0.875rem;">
        Chưa có tài khoản? <a href="?action=register"><strong>Đăng ký ngay</strong></a>
    </div>
</div>
</div>
<?php }

function pageRegister() { ?>
<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f0f4ff,#e8f0fe);padding:24px;">
<div class="card" style="width:100%;max-width:440px;">
    <div style="text-align:center;margin-bottom:32px;">
        <div style="font-size:3rem;margin-bottom:8px;">✨</div>
        <h1 style="font-size:1.5rem;font-weight:800;">Đăng Ký Tài Khoản</h1>
    </div>
    <form method="post" action="?action=register">
        <div class="form-group"><label>Họ và Tên</label><input type="text" name="full_name" placeholder="Nguyễn Văn A" required></div>
        <div class="form-group"><label>Tên Đăng Nhập</label><input type="text" name="username" placeholder="nguyenvana" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="email@example.com" required></div>
        <div class="form-group"><label>Mật Khẩu</label><input type="password" name="password" placeholder="Tối thiểu 6 ký tự" required minlength="6"></div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">Đăng Ký</button>
    </form>
    <div style="text-align:center;margin-top:20px;font-size:0.875rem;">
        Đã có tài khoản? <a href="?action=login"><strong>Đăng nhập</strong></a>
    </div>
</div>
</div>
<?php }

