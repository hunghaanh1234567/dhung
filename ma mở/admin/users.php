<?php
// ============================================================
// ADMIN PAGES
// ============================================================
function pageAdminUsers($db) {
    if (!hasRole('admin')) { redirect('?action=home'); return; }
    $users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="admin-layout">
<?php renderAdminSidebar('admin_users'); ?>
<div class="admin-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h1 style="font-size:1.5rem;font-weight:800;">👥 Quản Lý Người Dùng</h1>
        <button onclick="document.getElementById('add-user-form').style.display='block'" class="btn btn-primary">+ Thêm Người Dùng</button>
    </div>

    <!-- Add User Form -->
    <div id="add-user-form" style="display:none;" class="card" style="margin-bottom:24px;">
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">➕ Thêm Người Dùng Mới</h2>
        <form method="post" action="?action=admin_add_user">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group"><label>Họ và Tên</label><input type="text" name="full_name" required placeholder="Nguyễn Văn A"></div>
                <div class="form-group"><label>Tên Đăng Nhập</label><input type="text" name="username" required placeholder="nguyenvana"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="email@example.com"></div>
                <div class="form-group"><label>Mật Khẩu</label><input type="password" name="password" value="student123" required></div>
                <div class="form-group">
                    <label>Vai Trò</label>
                    <select name="role">
                        <option value="student">Học Viên</option>
                        <option value="teacher">Giảng Viên</option>
                        <option value="admin">Quản Trị Viên</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Thêm Người Dùng</button>
                <button type="button" onclick="document.getElementById('add-user-form').style.display='none'" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
    </div>

    <div class="table-wrapper">
    <table>
        <thead><tr><th>Họ Tên</th><th>Email</th><th>Vai Trò</th><th>Trạng Thái</th><th>Ngày Tạo</th><th>Hành Động</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td><strong><?= h($u['full_name']) ?></strong><br><span style="font-size:0.75rem;color:var(--gray);">@<?= h($u['username']) ?></span></td>
            <td style="font-size:0.875rem;"><?= h($u['email']) ?></td>
            <td>
                <?php $roleBadge = ['admin'=>'badge-danger','teacher'=>'badge-warning','student'=>'badge-primary'][$u['role']] ?? 'badge-primary'; ?>
                <span class="badge <?= $roleBadge ?>"><?= $u['role']==='admin'?'Quản Trị':($u['role']==='teacher'?'Giảng Viên':'Học Viên') ?></span>
            </td>
            <td><span class="badge <?= $u['is_active']?'badge-success':'badge-danger' ?>"><?= $u['is_active']?'Hoạt động':'Đã khóa' ?></span></td>
            <td style="font-size:0.8rem;color:var(--gray);"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            <td>
                <form method="post" action="?action=admin_update_user" style="display:flex;gap:8px;align-items:center;">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role" style="padding:4px 8px;font-size:0.8rem;width:auto;">
                        <option value="student" <?= $u['role']==='student'?'selected':'' ?>>Học Viên</option>
                        <option value="teacher" <?= $u['role']==='teacher'?'selected':'' ?>>Giảng Viên</option>
                        <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                    </select>
                    <select name="is_active" style="padding:4px 8px;font-size:0.8rem;width:auto;">
                        <option value="1" <?= $u['is_active']?'selected':'' ?>>Hoạt động</option>
                        <option value="0" <?= !$u['is_active']?'selected':'' ?>>Khóa</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<?php
}

function pageAdminCategories($db) {
    if (!hasRole('admin')) { redirect('?action=home'); return; }
    $categories = $db->query("SELECT *, (SELECT COUNT(*) FROM courses WHERE category_id=categories.id) as course_count FROM categories ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="admin-layout">
<?php renderAdminSidebar('admin_categories'); ?>
<div class="admin-content">
    <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:24px;">📁 Quản Lý Danh Mục</h1>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div>
            <div class="table-wrapper">
            <table>
                <thead><tr><th>Icon</th><th>Tên</th><th>Số Khóa</th></tr></thead>
                <tbody>
                <?php foreach($categories as $cat): ?>
                <tr>
                    <td style="font-size:1.5rem;"><?= h($cat['icon']) ?></td>
                    <td><strong><?= h($cat['name']) ?></strong></td>
                    <td><?= $cat['course_count'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="card">
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">➕ Thêm Danh Mục</h2>
            <form method="post" action="?action=admin_save_category">
                <div class="form-group"><label>Tên Danh Mục</label><input type="text" name="name" required placeholder="Ví dụ: Thiết Kế Đồ Họa"></div>
                <div class="form-group"><label>Icon (Emoji)</label><input type="text" name="icon" value="📚" required style="font-size:1.5rem;width:80px;text-align:center;"></div>
                <div class="form-group"><label>Mô Tả</label><textarea name="description" rows="2"></textarea></div>
                <button type="submit" class="btn btn-primary">Thêm Danh Mục</button>
            </form>
        </div>
    </div>
</div>
</div>
<?php
}

function pageAdminSettings($db) {
    if (!hasRole('admin')) { redirect('?action=home'); return; }
?>
<div class="admin-layout">
<?php renderAdminSidebar('admin_settings'); ?>
<div class="admin-content">
    <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:24px;">⚙️ Cài Đặt Hệ Thống</h1>
    <div class="card" style="max-width:600px;">
        <form method="post" action="?action=admin_save_settings">
            <div class="form-group"><label>Tên Website</label><input type="text" name="site_name" value="<?= h(setting('site_name')) ?>"></div>
            <div class="form-group"><label>Logo (Emoji)</label><input type="text" name="site_logo" value="<?= h(setting('site_logo','🎓')) ?>" style="font-size:1.5rem;width:80px;text-align:center;"></div>
            <div class="form-group"><label>Tiêu Đề Banner</label><input type="text" name="banner_title" value="<?= h(setting('banner_title')) ?>"></div>
            <div class="form-group"><label>Mô Tả Banner</label><textarea name="banner_subtitle"><?= h(setting('banner_subtitle')) ?></textarea></div>
            <div class="form-group"><label>Màu Chủ Đạo</label><input type="color" name="primary_color" value="<?= h(setting('primary_color','#2563eb')) ?>" style="width:80px;height:40px;padding:4px;"></div>
            <button type="submit" class="btn btn-primary">💾 Lưu Cài Đặt</button>
        </form>
    </div>
</div>
</div>
<?php
}

function pageAdminCourses($db) {
    if (!hasRole('admin')) { redirect('?action=home'); return; }
    $courses = $db->query("SELECT c.*, u.full_name as instructor_name, cat.name as cat_name FROM courses c LEFT JOIN users u ON c.instructor_id=u.id LEFT JOIN categories cat ON c.category_id=cat.id ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="admin-layout">
<?php renderAdminSidebar('admin_courses'); ?>
<div class="admin-content">
    <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:24px;">📚 Quản Lý Khóa Học</h1>
    <div class="table-wrapper">
    <table>
        <thead><tr><th>Tên Khóa Học</th><th>Giảng Viên</th><th>Danh Mục</th><th>Giá</th><th>Trạng Thái</th><th>Hành Động</th></tr></thead>
        <tbody>
        <?php foreach($courses as $c): ?>
        <tr>
            <td><strong><?= h($c['title']) ?></strong></td>
            <td><?= h($c['instructor_name']) ?></td>
            <td><?= h($c['cat_name'] ?? '-') ?></td>
            <td><?= $c['price']>0?number_format($c['price'],0,',','.') . 'đ':'Miễn phí' ?></td>
            <td><?= $c['is_published']?'<span class="badge badge-success">Xuất bản</span>':'<span class="badge badge-warning">Nháp</span>' ?></td>
            <td style="display:flex;gap:8px;">
                <a href="?action=manage_course&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">✏️ Sửa</a>
                <a href="?action=course&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">👁 Xem</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<?php
}

function renderAdminSidebar($active) { ?>
<div class="sidebar">
    <div style="padding:20px 20px 8px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:8px;">
        <div style="font-weight:800;font-size:1.1rem;">⚙️ Quản Trị</div>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Người Dùng</div>
        <a href="?action=admin_users" class="<?= $active==='admin_users'?'active':'' ?>">👥 Quản Lý Tài Khoản</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Nội Dung</div>
        <a href="?action=admin_courses" class="<?= $active==='admin_courses'?'active':'' ?>">📚 Quản Lý Khóa Học</a>
        <a href="?action=admin_categories" class="<?= $active==='admin_categories'?'active':'' ?>">📁 Danh Mục</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Hệ Thống</div>
        <a href="?action=admin_settings" class="<?= $active==='admin_settings'?'active':'' ?>">⚙️ Cài Đặt Giao Diện</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Nhanh</div>
        <a href="?action=home">🏠 Trang Chủ</a>
        <a href="?action=courses">📖 Khóa Học</a>
        <a href="?action=teacher_students">👩‍🎓 Học Viên</a>
        <a href="?action=attendance_list">📋 Điểm Danh</a>
    </div>
</div>
<?php
}
