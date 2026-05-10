<?php
// ============================================================
// exam.php — Kiểm Tra Kết Thúc Học Kỳ
// ============================================================
// Pages:
//   pageExamList($db)          — Giáo viên: danh sách kỳ thi
//   pageCreateExam($db)        — Giáo viên: tạo / sửa kỳ thi
//   pageManageExam($db)        — Giáo viên: quản lý câu hỏi & kết quả
//   pageExamResults($db)       — Giáo viên: xem kết quả tổng hợp
//   pageStudentExams($db)      — Học viên: danh sách kỳ thi của mình
//   pageDoExam($db)            — Học viên: làm bài thi
//   pageExamResult($db)        — Học viên: xem kết quả sau khi nộp
//   pageAdminExams($db)        — Admin: tổng quan tất cả kỳ thi
// ============================================================

// ──────────────────────────────────────────────────────────────
// GIÁO VIÊN: Danh sách kỳ thi
// ──────────────────────────────────────────────────────────────
function pageExamList($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $user = currentUser();
    $where = $user['role'] === 'admin' ? "1=1" : "e.instructor_id=" . (int)$user['id'];

    $exams = $db->query("
        SELECT e.*, c.title as course_title,
               (SELECT COUNT(*) FROM exam_questions WHERE exam_id=e.id) as q_count,
               (SELECT COUNT(*) FROM exam_attempts WHERE exam_id=e.id) as attempt_count,
               (SELECT COUNT(*) FROM exam_attempts WHERE exam_id=e.id AND passed=1) as pass_count
        FROM semester_exams e
        JOIN courses c ON c.id=e.course_id
        WHERE $where
        ORDER BY e.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $courses = $db->query(
        "SELECT c.id, c.title FROM courses c
         WHERE c.instructor_id=" . (int)$user['id'] . "
            OR " . ($user['role']==='admin' ? "1=1" : "0") . "
         ORDER BY c.title"
    )->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.75rem;font-weight:800;">📝 Kỳ Thi Kết Thúc Học Kỳ</h1>
            <p style="color:var(--gray);margin-top:4px;">Tạo và quản lý các bài kiểm tra cuối kỳ</p>
        </div>
        <button onclick="document.getElementById('create-exam-form').style.display='block';this.style.display='none'" class="btn btn-primary">+ Tạo Kỳ Thi Mới</button>
    </div>

    <!-- Form tạo kỳ thi nhanh -->
    <div id="create-exam-form" style="display:none;" class="card" style="margin-bottom:24px;">
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">➕ Tạo Kỳ Thi Mới</h2>
        <form method="post" action="?action=save_exam">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Tên Kỳ Thi <span style="color:red">*</span></label>
                    <input type="text" name="title" required placeholder="VD: Kiểm tra cuối kỳ HK1 2025">
                </div>
                <div class="form-group">
                    <label>Khóa Học <span style="color:red">*</span></label>
                    <select name="course_id" required>
                        <option value="">-- Chọn khóa học --</option>
                        <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= h($c['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Thời Gian Làm Bài (phút)</label>
                    <input type="number" name="time_limit" value="60" min="5" max="300">
                </div>
                <div class="form-group">
                    <label>Điểm Đậu (%)</label>
                    <input type="number" name="passing_score" value="50" min="0" max="100">
                </div>
                <div class="form-group">
                    <label>Ngày Bắt Đầu</label>
                    <input type="datetime-local" name="start_time">
                </div>
                <div class="form-group">
                    <label>Ngày Kết Thúc</label>
                    <input type="datetime-local" name="end_time">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label>Mô Tả / Hướng Dẫn Thi</label>
                    <textarea name="description" rows="3" placeholder="Hướng dẫn thí sinh trước khi vào thi..."></textarea>
                </div>
            </div>
            <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                    <input type="checkbox" name="shuffle_questions" value="1" checked> Xáo trộn câu hỏi
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                    <input type="checkbox" name="shuffle_answers" value="1" checked> Xáo trộn đáp án
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                    <input type="checkbox" name="show_result" value="1" checked> Hiển thị kết quả sau khi nộp
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
                    <input type="checkbox" name="is_active" value="1"> Kích hoạt ngay
                </label>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Tạo Kỳ Thi</button>
                <button type="button" onclick="document.getElementById('create-exam-form').style.display='none';document.querySelector('[onclick*=create-exam-form]').style.display=''" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
    </div>

    <!-- Danh sách kỳ thi -->
    <?php if (empty($exams)): ?>
    <div class="alert alert-info" style="text-align:center;padding:48px 24px;">
        <div style="font-size:3rem;margin-bottom:12px;">📋</div>
        <p style="font-size:1.1rem;font-weight:600;">Chưa có kỳ thi nào</p>
        <p style="color:var(--gray);">Nhấn "+ Tạo Kỳ Thi Mới" để bắt đầu</p>
    </div>
    <?php else: ?>
    <div style="display:grid;gap:16px;">
    <?php foreach($exams as $ex): ?>
    <?php
        $statusLabel = $ex['is_active'] ? '<span class="badge badge-success">Đang mở</span>' : '<span class="badge badge-warning">Tạm dừng</span>';
        $now = time();
        if ($ex['start_time'] && strtotime($ex['start_time']) > $now) {
            $statusLabel = '<span class="badge badge-primary">Chưa bắt đầu</span>';
        } elseif ($ex['end_time'] && strtotime($ex['end_time']) < $now) {
            $statusLabel = '<span class="badge badge-danger">Đã kết thúc</span>';
        }
        $passRate = $ex['attempt_count'] > 0 ? round($ex['pass_count']/$ex['attempt_count']*100) : 0;
    ?>
    <div class="card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                    <h3 style="font-size:1.1rem;font-weight:700;margin:0;"><?= h($ex['title']) ?></h3>
                    <?= $statusLabel ?>
                </div>
                <p style="color:var(--gray);font-size:0.85rem;margin:0 0 8px;">
                    📚 <?= h($ex['course_title']) ?>
                    &nbsp;·&nbsp; ⏱ <?= $ex['time_limit'] ?> phút
                    &nbsp;·&nbsp; ✅ Đậu: <?= $ex['passing_score'] ?>%
                    &nbsp;·&nbsp; ❓ <?= $ex['q_count'] ?> câu hỏi
                </p>
                <?php if ($ex['start_time'] || $ex['end_time']): ?>
                <p style="color:var(--gray);font-size:0.8rem;margin:0;">
                    <?php if ($ex['start_time']): ?>🕐 Bắt đầu: <?= date('d/m/Y H:i', strtotime($ex['start_time'])) ?><?php endif; ?>
                    <?php if ($ex['end_time']): ?> &nbsp;→&nbsp; Kết thúc: <?= date('d/m/Y H:i', strtotime($ex['end_time'])) ?><?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="?action=manage_exam&id=<?= $ex['id'] ?>" class="btn btn-secondary btn-sm">✏️ Quản lý</a>
                <a href="?action=exam_results&id=<?= $ex['id'] ?>" class="btn btn-primary btn-sm">📊 Kết quả</a>
                <form method="post" action="?action=toggle_exam" style="display:inline;">
                    <input type="hidden" name="exam_id" value="<?= $ex['id'] ?>">
                    <button type="submit" class="btn btn-sm <?= $ex['is_active'] ? 'btn-danger' : 'btn-success' ?>"><?= $ex['is_active'] ? '⏸ Tạm dừng' : '▶ Kích hoạt' ?></button>
                </form>
            </div>
        </div>
        <!-- Stats mini -->
        <div style="display:flex;gap:24px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
            <div style="text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:var(--primary)"><?= $ex['attempt_count'] ?></div>
                <div style="font-size:0.75rem;color:var(--gray);">Lượt thi</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:#10b981"><?= $ex['pass_count'] ?></div>
                <div style="font-size:0.75rem;color:var(--gray);">Đậu</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:#f59e0b"><?= $passRate ?>%</div>
                <div style="font-size:0.75rem;color:var(--gray);">Tỉ lệ đậu</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:#6366f1"><?= $ex['q_count'] ?></div>
                <div style="font-size:0.75rem;color:var(--gray);">Câu hỏi</div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php }

// ──────────────────────────────────────────────────────────────
// GIÁO VIÊN: Quản lý câu hỏi kỳ thi
// ──────────────────────────────────────────────────────────────
function pageManageExam($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $examId = (int)($_GET['id'] ?? 0);
    $exam = $db->prepare("SELECT e.*, c.title as course_title FROM semester_exams e JOIN courses c ON c.id=e.course_id WHERE e.id=?");
    $exam->execute([$examId]);
    $exam = $exam->fetch(PDO::FETCH_ASSOC);
    if (!$exam) { redirect('?action=exam_list'); return; }

    $questions = $db->prepare("SELECT * FROM exam_questions WHERE exam_id=? ORDER BY order_num,id");
    $questions->execute([$examId]);
    $questions = $questions->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
        <a href="?action=exam_list" style="color:var(--gray);text-decoration:none;">← Quay lại</a>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:4px;">✏️ <?= h($exam['title']) ?></h1>
            <p style="color:var(--gray);font-size:0.875rem;">📚 <?= h($exam['course_title']) ?> &nbsp;·&nbsp; ⏱ <?= $exam['time_limit'] ?> phút &nbsp;·&nbsp; ✅ Điểm đậu: <?= $exam['passing_score'] ?>%</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="?action=exam_results&id=<?= $exam['id'] ?>" class="btn btn-secondary btn-sm">📊 Kết Quả</a>
            <button onclick="toggleForm('add-q-form')" class="btn btn-primary">+ Thêm Câu Hỏi</button>
        </div>
    </div>

    <!-- Sửa thông tin kỳ thi -->
    <details class="card" style="margin-bottom:20px;">
        <summary style="font-weight:700;cursor:pointer;font-size:1rem;">⚙️ Thông tin kỳ thi</summary>
        <form method="post" action="?action=save_exam" style="margin-top:16px;">
            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group"><label>Tên Kỳ Thi</label><input type="text" name="title" value="<?= h($exam['title']) ?>" required></div>
                <div class="form-group">
                    <label>Thời Gian (phút)</label><input type="number" name="time_limit" value="<?= $exam['time_limit'] ?>" min="5">
                </div>
                <div class="form-group"><label>Điểm Đậu (%)</label><input type="number" name="passing_score" value="<?= $exam['passing_score'] ?>" min="0" max="100"></div>
                <div class="form-group"><label>Ngày Bắt Đầu</label><input type="datetime-local" name="start_time" value="<?= $exam['start_time'] ? date('Y-m-d\TH:i', strtotime($exam['start_time'])) : '' ?>"></div>
                <div class="form-group"><label>Ngày Kết Thúc</label><input type="datetime-local" name="end_time" value="<?= $exam['end_time'] ? date('Y-m-d\TH:i', strtotime($exam['end_time'])) : '' ?>"></div>
                <div class="form-group" style="grid-column:span 2;"><label>Hướng Dẫn</label><textarea name="description" rows="3"><?= h($exam['description']) ?></textarea></div>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="shuffle_questions" value="1" <?= $exam['shuffle_questions'] ? 'checked' : '' ?>> Xáo trộn câu hỏi
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="shuffle_answers" value="1" <?= $exam['shuffle_answers'] ? 'checked' : '' ?>> Xáo trộn đáp án
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="show_result" value="1" <?= $exam['show_result'] ? 'checked' : '' ?>> Hiển thị kết quả sau nộp
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= $exam['is_active'] ? 'checked' : '' ?>> Đang kích hoạt
                </label>
            </div>
            <button type="submit" class="btn btn-primary">💾 Lưu Thay Đổi</button>
        </form>
    </details>

    <!-- Form thêm câu hỏi -->
    <div id="add-q-form" style="display:none;" class="card" style="margin-bottom:20px;">
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">➕ Thêm Câu Hỏi Mới</h2>
        <form method="post" action="?action=save_exam_question">
            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
            <div class="form-group">
                <label>Câu Hỏi <span style="color:red">*</span></label>
                <textarea name="question" rows="3" required placeholder="Nhập nội dung câu hỏi..."></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group"><label>A. Đáp án A <span style="color:red">*</span></label><input type="text" name="option_a" required></div>
                <div class="form-group"><label>B. Đáp án B <span style="color:red">*</span></label><input type="text" name="option_b" required></div>
                <div class="form-group"><label>C. Đáp án C <span style="color:red">*</span></label><input type="text" name="option_c" required></div>
                <div class="form-group"><label>D. Đáp án D <span style="color:red">*</span></label><input type="text" name="option_d" required></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Đáp Án Đúng <span style="color:red">*</span></label>
                    <select name="correct_answer" required>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Điểm Số</label>
                    <input type="number" name="points" value="1" min="1" max="10">
                </div>
                <div class="form-group">
                    <label>Mức Độ</label>
                    <select name="difficulty">
                        <option value="easy">Dễ</option>
                        <option value="medium" selected>Trung Bình</option>
                        <option value="hard">Khó</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Giải Thích Đáp Án (tuỳ chọn)</label>
                <textarea name="explanation" rows="2" placeholder="Giải thích tại sao đáp án này đúng..."></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Thêm Câu Hỏi</button>
                <button type="button" onclick="toggleForm('add-q-form')" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
    </div>

    <!-- Danh sách câu hỏi -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="font-size:1.1rem;font-weight:700;">📋 Danh Sách Câu Hỏi (<?= count($questions) ?> câu)</h2>
        <?php if (!empty($questions)): ?>
        <a href="?action=exam_results&id=<?= $exam['id'] ?>" class="btn btn-secondary btn-sm">📊 Xem Kết Quả</a>
        <?php endif; ?>
    </div>

    <?php if (empty($questions)): ?>
    <div class="alert alert-info" style="text-align:center;padding:32px;">
        <div style="font-size:2.5rem;margin-bottom:8px;">❓</div>
        <p>Chưa có câu hỏi nào. Nhấn "+ Thêm Câu Hỏi" để bắt đầu.</p>
    </div>
    <?php else: ?>
    <div style="display:grid;gap:12px;">
    <?php foreach($questions as $i => $q): ?>
    <?php
        $diffColor = ['easy'=>'badge-success','medium'=>'badge-warning','hard'=>'badge-danger'][$q['difficulty']] ?? 'badge-primary';
        $diffLabel = ['easy'=>'Dễ','medium'=>'Trung Bình','hard'=>'Khó'][$q['difficulty']] ?? $q['difficulty'];
    ?>
    <div class="card" style="padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <span style="font-weight:800;color:var(--primary);">Câu <?= $i+1 ?></span>
                    <span class="badge <?= $diffColor ?>"><?= $diffLabel ?></span>
                    <span style="font-size:0.8rem;color:var(--gray);"><?= $q['points'] ?> điểm</span>
                </div>
                <p style="font-weight:600;margin-bottom:10px;"><?= h($q['question']) ?></p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:0.875rem;">
                    <?php foreach(['A','B','C','D'] as $opt): ?>
                    <?php $isCorrect = $q['correct_answer'] === $opt; ?>
                    <div style="padding:6px 10px;border-radius:6px;background:<?= $isCorrect ? '#d1fae5' : '#f9fafb' ?>;border:1px solid <?= $isCorrect ? '#10b981' : '#e5e7eb' ?>;color:<?= $isCorrect ? '#065f46' : 'inherit' ?>;">
                        <strong><?= $opt ?>.</strong> <?= h($q['option_'.strtolower($opt)]) ?>
                        <?php if($isCorrect): ?> ✅<?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if(!empty($q['explanation'])): ?>
                <div style="margin-top:8px;padding:8px 12px;background:#fffbeb;border-left:3px solid #f59e0b;border-radius:0 6px 6px 0;font-size:0.8rem;color:#92400e;">
                    💡 <?= h($q['explanation']) ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:6px;">
                <button onclick="editQuestion(<?= $q['id'] ?>,<?= htmlspecialchars(json_encode($q), ENT_QUOTES) ?>)" class="btn btn-secondary btn-sm">✏️</button>
                <form method="post" action="?action=delete_exam_question" onsubmit="return confirm('Xóa câu hỏi này?')">
                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                    <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal sửa câu hỏi -->
<div id="edit-q-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:90%;max-width:640px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="font-size:1.1rem;font-weight:700;">✏️ Sửa Câu Hỏi</h2>
            <button onclick="document.getElementById('edit-q-modal').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">×</button>
        </div>
        <form method="post" action="?action=save_exam_question">
            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
            <input type="hidden" name="question_id" id="edit-q-id">
            <div class="form-group"><label>Câu Hỏi</label><textarea name="question" id="edit-q-text" rows="3" required></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group"><label>A.</label><input type="text" name="option_a" id="edit-opt-a" required></div>
                <div class="form-group"><label>B.</label><input type="text" name="option_b" id="edit-opt-b" required></div>
                <div class="form-group"><label>C.</label><input type="text" name="option_c" id="edit-opt-c" required></div>
                <div class="form-group"><label>D.</label><input type="text" name="option_d" id="edit-opt-d" required></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Đáp Án Đúng</label>
                    <select name="correct_answer" id="edit-correct">
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                    </select>
                </div>
                <div class="form-group"><label>Điểm</label><input type="number" name="points" id="edit-points" value="1" min="1" max="10"></div>
                <div class="form-group">
                    <label>Mức Độ</label>
                    <select name="difficulty" id="edit-diff">
                        <option value="easy">Dễ</option><option value="medium">Trung Bình</option><option value="hard">Khó</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Giải Thích</label><textarea name="explanation" id="edit-explanation" rows="2"></textarea></div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">💾 Lưu</button>
                <button type="button" onclick="document.getElementById('edit-q-modal').style.display='none'" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
    </div>
</div>
<script>
function toggleForm(id){
    var el=document.getElementById(id);
    el.style.display=el.style.display==='none'?'block':'none';
}
function editQuestion(id,data){
    document.getElementById('edit-q-id').value=id;
    document.getElementById('edit-q-text').value=data.question;
    document.getElementById('edit-opt-a').value=data.option_a;
    document.getElementById('edit-opt-b').value=data.option_b;
    document.getElementById('edit-opt-c').value=data.option_c;
    document.getElementById('edit-opt-d').value=data.option_d;
    document.getElementById('edit-correct').value=data.correct_answer;
    document.getElementById('edit-points').value=data.points;
    document.getElementById('edit-diff').value=data.difficulty;
    document.getElementById('edit-explanation').value=data.explanation||'';
    document.getElementById('edit-q-modal').style.display='flex';
}
</script>
<?php }

// ──────────────────────────────────────────────────────────────
// GIÁO VIÊN: Kết quả kỳ thi
// ──────────────────────────────────────────────────────────────
function pageExamResults($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $examId = (int)($_GET['id'] ?? 0);
    $exam = $db->prepare("SELECT e.*, c.title as course_title FROM semester_exams e JOIN courses c ON c.id=e.course_id WHERE e.id=?");
    $exam->execute([$examId]);
    $exam = $exam->fetch(PDO::FETCH_ASSOC);
    if (!$exam) { redirect('?action=exam_list'); return; }

    $attempts = $db->prepare("
        SELECT a.*, u.full_name, u.username, u.email
        FROM exam_attempts a
        JOIN users u ON u.id=a.user_id
        WHERE a.exam_id=?
        ORDER BY a.submitted_at DESC
    ");
    $attempts->execute([$examId]);
    $attempts = $attempts->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê
    $totalAttempts = count($attempts);
    $passed = array_filter($attempts, fn($a) => $a['passed']);
    $scores = array_column($attempts, 'score');
    $avgScore = $totalAttempts > 0 ? round(array_sum($scores)/$totalAttempts, 1) : 0;
    $maxScore = $totalAttempts > 0 ? max($scores) : 0;
    $minScore = $totalAttempts > 0 ? min($scores) : 0;
    $passRate = $totalAttempts > 0 ? round(count($passed)/$totalAttempts*100) : 0;

    // Phân bố điểm
    $dist = ['0-20'=>0,'21-40'=>0,'41-50'=>0,'51-60'=>0,'61-70'=>0,'71-80'=>0,'81-90'=>0,'91-100'=>0];
    foreach($scores as $s) {
        if($s<=20) $dist['0-20']++;
        elseif($s<=40) $dist['21-40']++;
        elseif($s<=50) $dist['41-50']++;
        elseif($s<=60) $dist['51-60']++;
        elseif($s<=70) $dist['61-70']++;
        elseif($s<=80) $dist['71-80']++;
        elseif($s<=90) $dist['81-90']++;
        else $dist['91-100']++;
    }
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <a href="?action=exam_list" style="color:var(--gray);text-decoration:none;">← Quay lại</a>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:4px;">📊 <?= h($exam['title']) ?></h1>
            <p style="color:var(--gray);font-size:0.875rem;">📚 <?= h($exam['course_title']) ?> &nbsp;·&nbsp; Điểm đậu: <?= $exam['passing_score'] ?>%</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="?action=manage_exam&id=<?= $exam['id'] ?>" class="btn btn-secondary btn-sm">✏️ Quản lý đề</a>
            <a href="?action=export_exam_results&id=<?= $exam['id'] ?>" class="btn btn-primary btn-sm">⬇️ Xuất CSV</a>
        </div>
    </div>

    <!-- Thống kê tổng quan -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px;">
        <?php
        $stats = [
            ['label'=>'Lượt Thi','value'=>$totalAttempts,'icon'=>'👥','color'=>'#2563eb'],
            ['label'=>'Đã Đậu','value'=>count($passed),'icon'=>'✅','color'=>'#10b981'],
            ['label'=>'Tỉ Lệ Đậu','value'=>$passRate.'%','icon'=>'🎯','color'=>'#f59e0b'],
            ['label'=>'Điểm TB','value'=>$avgScore.'%','icon'=>'📊','color'=>'#6366f1'],
            ['label'=>'Cao Nhất','value'=>$maxScore.'%','icon'=>'🏆','color'=>'#ec4899'],
            ['label'=>'Thấp Nhất','value'=>$minScore.'%','icon'=>'📉','color'=>'#ef4444'],
        ];
        foreach($stats as $s): ?>
        <div class="card" style="text-align:center;padding:16px;">
            <div style="font-size:1.8rem;"><?= $s['icon'] ?></div>
            <div style="font-size:1.6rem;font-weight:800;color:<?= $s['color'] ?>"><?= $s['value'] ?></div>
            <div style="font-size:0.78rem;color:var(--gray);font-weight:600;"><?= $s['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân bố điểm -->
    <?php if ($totalAttempts > 0): ?>
    <div class="card" style="margin-bottom:24px;">
        <h3 style="font-weight:700;margin-bottom:16px;">📈 Phân Bố Điểm</h3>
        <div style="display:flex;align-items:flex-end;gap:8px;height:120px;">
        <?php
        $maxVal = max(array_values($dist)) ?: 1;
        foreach($dist as $range => $count): ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
            <span style="font-size:0.7rem;font-weight:700;color:var(--primary);"><?= $count ?></span>
            <div style="width:100%;border-radius:4px 4px 0 0;background:<?= (int)explode('-',$range)[0] >= $exam['passing_score'] ? '#10b981' : '#e5e7eb' ?>;height:<?= $maxVal>0?round($count/$maxVal*90):0 ?>px;min-height:4px;transition:height 0.3s;"></div>
            <span style="font-size:0.65rem;color:var(--gray);white-space:nowrap;"><?= $range ?></span>
        </div>
        <?php endforeach; ?>
        </div>
        <div style="margin-top:8px;display:flex;gap:16px;font-size:0.78rem;">
            <span><span style="display:inline-block;width:12px;height:12px;background:#10b981;border-radius:2px;"></span> Đậu (≥<?= $exam['passing_score'] ?>%)</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#e5e7eb;border-radius:2px;border:1px solid #d1d5db;"></span> Rớt</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bảng kết quả chi tiết -->
    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Học Viên</th>
                <th>Email</th>
                <th>Điểm</th>
                <th>Đúng / Tổng</th>
                <th>Thời Gian Nộp</th>
                <th>Thời Lượng</th>
                <th>Kết Quả</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($attempts)): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray);">Chưa có học viên nào thi.</td></tr>
        <?php endif; ?>
        <?php foreach($attempts as $i => $at): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= h($at['full_name']) ?></strong><br><small style="color:var(--gray);">@<?= h($at['username']) ?></small></td>
            <td style="font-size:0.8rem;"><?= h($at['email']) ?></td>
            <td>
                <div style="font-size:1.1rem;font-weight:800;color:<?= $at['score']>=$exam['passing_score']?'#10b981':'#ef4444' ?>">
                    <?= $at['score'] ?>%
                </div>
            </td>
            <td style="font-size:0.875rem;"><?= $at['correct_count'] ?> / <?= $at['total_questions'] ?></td>
            <td style="font-size:0.8rem;color:var(--gray);"><?= date('d/m/Y H:i', strtotime($at['submitted_at'])) ?></td>
            <td style="font-size:0.8rem;color:var(--gray);"><?= $at['duration_minutes'] ? $at['duration_minutes'].' phút' : '—' ?></td>
            <td>
                <?php if ($at['passed']): ?>
                <span class="badge badge-success">✅ Đậu</span>
                <?php else: ?>
                <span class="badge badge-danger">❌ Rớt</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php }

// ──────────────────────────────────────────────────────────────
// HỌC VIÊN: Danh sách kỳ thi
// ──────────────────────────────────────────────────────────────
function pageStudentExams($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $user = currentUser();
    $userId = $user['id'];

    $exams = $db->prepare("
        SELECT e.*, c.title as course_title,
               a.score, a.passed, a.submitted_at,
               a.id as attempt_id,
               (SELECT COUNT(*) FROM exam_questions WHERE exam_id=e.id) as q_count
        FROM semester_exams e
        JOIN courses c ON c.id=e.course_id
        JOIN enrollments en ON en.course_id=e.course_id AND en.user_id=?
        LEFT JOIN exam_attempts a ON a.exam_id=e.id AND a.user_id=?
        WHERE e.is_active=1
        ORDER BY e.created_at DESC
    ");
    $exams->execute([$userId, $userId]);
    $exams = $exams->fetchAll(PDO::FETCH_ASSOC);
    $now = time();
?>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:8px;">📝 Kỳ Thi Của Tôi</h1>
    <p style="color:var(--gray);margin-bottom:24px;">Danh sách các bài kiểm tra kết thúc học kỳ bạn cần tham gia.</p>

    <?php if (empty($exams)): ?>
    <div class="alert alert-info" style="text-align:center;padding:48px 24px;">
        <div style="font-size:3rem;margin-bottom:12px;">📭</div>
        <p style="font-size:1.1rem;font-weight:600;">Không có kỳ thi nào</p>
        <p style="color:var(--gray);">Các kỳ thi từ khóa học bạn đã đăng ký sẽ xuất hiện ở đây.</p>
    </div>
    <?php else: ?>
    <div style="display:grid;gap:16px;">
    <?php foreach($exams as $ex): ?>
    <?php
        $canTake = true;
        $statusMsg = '';
        $alreadyDone = !empty($ex['submitted_at']);

        if ($ex['start_time'] && strtotime($ex['start_time']) > $now) {
            $canTake = false;
            $statusMsg = '⏳ Bắt đầu: '.date('d/m/Y H:i', strtotime($ex['start_time']));
        } elseif ($ex['end_time'] && strtotime($ex['end_time']) < $now) {
            $canTake = false;
            $statusMsg = '🔒 Đã kết thúc: '.date('d/m/Y H:i', strtotime($ex['end_time']));
        }
    ?>
    <div class="card" style="padding:20px;border-left:4px solid <?= $alreadyDone ? ($ex['passed']?'#10b981':'#ef4444') : ($canTake?'#2563eb':'#9ca3af') ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
            <div style="flex:1;">
                <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:6px;"><?= h($ex['title']) ?></h3>
                <p style="color:var(--gray);font-size:0.875rem;margin:0 0 6px;">
                    📚 <?= h($ex['course_title']) ?>
                    &nbsp;·&nbsp; ⏱ <?= $ex['time_limit'] ?> phút
                    &nbsp;·&nbsp; ❓ <?= $ex['q_count'] ?> câu
                    &nbsp;·&nbsp; ✅ Đậu: <?= $ex['passing_score'] ?>%
                </p>
                <?php if ($statusMsg): ?><p style="font-size:0.8rem;color:var(--gray);"><?= $statusMsg ?></p><?php endif; ?>
                <?php if (!empty($ex['description'])): ?>
                <p style="font-size:0.85rem;color:#4b5563;margin-top:6px;"><?= h($ex['description']) ?></p>
                <?php endif; ?>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <?php if ($alreadyDone): ?>
                    <div style="text-align:right;">
                        <div style="font-size:1.8rem;font-weight:900;color:<?= $ex['passed']?'#10b981':'#ef4444' ?>;"><?= $ex['score'] ?>%</div>
                        <div><?= $ex['passed'] ? '<span class="badge badge-success">✅ Đậu</span>' : '<span class="badge badge-danger">❌ Rớt</span>' ?></div>
                        <div style="font-size:0.75rem;color:var(--gray);margin-top:4px;">Đã nộp: <?= date('d/m/Y H:i', strtotime($ex['submitted_at'])) ?></div>
                    </div>
                    <?php if ($ex['show_result']): ?>
                    <a href="?action=exam_result&attempt_id=<?= $ex['attempt_id'] ?>" class="btn btn-secondary btn-sm">👁 Xem lại</a>
                    <?php endif; ?>
                <?php elseif ($canTake): ?>
                    <a href="?action=do_exam&id=<?= $ex['id'] ?>" class="btn btn-primary">✏️ Vào Thi Ngay</a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled style="opacity:0.5;">🔒 Chưa mở</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php }

// ──────────────────────────────────────────────────────────────
// HỌC VIÊN: Làm bài thi
// ──────────────────────────────────────────────────────────────
function pageDoExam($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $user = currentUser();
    $examId = (int)($_GET['id'] ?? 0);

    $exam = $db->prepare("SELECT e.*, c.title as course_title FROM semester_exams e JOIN courses c ON c.id=e.course_id WHERE e.id=? AND e.is_active=1");
    $exam->execute([$examId]);
    $exam = $exam->fetch(PDO::FETCH_ASSOC);
    if (!$exam) { redirect('?action=student_exams'); return; }

    // Kiểm tra đã thi chưa
    $existing = $db->prepare("SELECT id FROM exam_attempts WHERE exam_id=? AND user_id=?");
    $existing->execute([$examId, $user['id']]);
    if ($existing->fetchColumn()) {
        $_SESSION['error'] = 'Bạn đã nộp bài thi này rồi.';
        redirect('?action=student_exams');
        return;
    }

    // Kiểm tra thời gian
    $now = time();
    if ($exam['start_time'] && strtotime($exam['start_time']) > $now) {
        $_SESSION['error'] = 'Kỳ thi chưa bắt đầu.';
        redirect('?action=student_exams');
        return;
    }
    if ($exam['end_time'] && strtotime($exam['end_time']) < $now) {
        $_SESSION['error'] = 'Kỳ thi đã kết thúc.';
        redirect('?action=student_exams');
        return;
    }

    // Lấy câu hỏi
    $qStmt = $db->prepare("SELECT * FROM exam_questions WHERE exam_id=? ORDER BY order_num,id");
    $qStmt->execute([$examId]);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($exam['shuffle_questions']) shuffle($questions);

    $timeLimitSec = $exam['time_limit'] * 60;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thi: <?= h($exam['title']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Be Vietnam Pro',sans-serif;background:#f0f4ff;min-height:100vh;}
.exam-header{position:sticky;top:0;z-index:100;background:linear-gradient(135deg,#1e1b4b,#312e81);color:#fff;padding:12px 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;box-shadow:0 2px 12px rgba(0,0,0,0.3);}
.exam-title{font-weight:800;font-size:1.1rem;}
.timer-box{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);padding:8px 16px;border-radius:50px;font-weight:800;font-size:1.1rem;letter-spacing:2px;}
.timer-box.warning{background:rgba(239,68,68,0.4);animation:pulse 1s infinite;}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.6}}
.exam-wrap{max-width:800px;margin:0 auto;padding:24px 16px 80px;}
.q-card{background:#fff;border-radius:12px;padding:24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,0.08);border:2px solid transparent;transition:border-color 0.2s;}
.q-card.answered{border-color:#2563eb;}
.q-num{font-size:0.8rem;font-weight:700;color:#6366f1;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;}
.q-text{font-weight:600;font-size:1rem;margin-bottom:16px;line-height:1.6;}
.options{display:grid;gap:10px;}
.option-label{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:8px;border:2px solid #e5e7eb;cursor:pointer;transition:all 0.15s;font-size:0.9rem;}
.option-label:hover{border-color:#2563eb;background:#eff6ff;}
.option-label input[type=radio]{display:none;}
.option-label.selected{border-color:#2563eb;background:#eff6ff;color:#1d4ed8;font-weight:600;}
.opt-circle{width:28px;height:28px;border-radius:50%;border:2px solid #d1d5db;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0;transition:all 0.15s;}
.option-label.selected .opt-circle{background:#2563eb;border-color:#2563eb;color:#fff;}
.progress-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #e5e7eb;padding:12px 24px;display:flex;align-items:center;gap:16px;box-shadow:0 -2px 12px rgba(0,0,0,0.08);z-index:100;}
.q-nav{display:flex;gap:6px;flex-wrap:wrap;flex:1;}
.q-dot{width:28px;height:28px;border-radius:50%;border:2px solid #e5e7eb;background:#f9fafb;font-size:0.7rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.15s;}
.q-dot.done{background:#2563eb;border-color:#2563eb;color:#fff;}
.q-dot.current{border-color:#6366f1;}
.submit-btn{background:#10b981;color:#fff;border:none;border-radius:8px;padding:10px 24px;font-weight:700;cursor:pointer;font-size:0.95rem;white-space:nowrap;}
.submit-btn:hover{background:#059669;}
</style>
</head>
<body>
<div class="exam-header">
    <div>
        <div class="exam-title">📝 <?= h($exam['title']) ?></div>
        <div style="font-size:0.78rem;opacity:0.7;margin-top:2px;"><?= h($exam['course_title']) ?> &nbsp;·&nbsp; <?= count($questions) ?> câu hỏi</div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <div class="timer-box" id="timer">⏱ --:--</div>
    </div>
</div>

<div class="exam-wrap">
    <form method="post" action="?action=submit_semester_exam" id="exam-form" onsubmit="return confirmSubmit()">
        <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
        <input type="hidden" name="start_ts" value="<?= time() ?>">

        <?php foreach($questions as $i => $q): ?>
        <?php
            $opts = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d']];
            $optKeys = array_keys($opts);
            if ($exam['shuffle_answers']) shuffle($optKeys);
        ?>
        <div class="q-card" id="qcard-<?= $i ?>">
            <div class="q-num">Câu <?= $i+1 ?> / <?= count($questions) ?>
                <?php
                    $diff = ['easy'=>'🟢 Dễ','medium'=>'🟡 TB','hard'=>'🔴 Khó'][$q['difficulty']] ?? '';
                    echo "<span style='margin-left:8px;font-size:0.75rem;'>$diff</span>";
                ?>
            </div>
            <div class="q-text"><?= h($q['question']) ?></div>
            <div class="options">
                <?php foreach($optKeys as $k): ?>
                <label class="option-label" id="lbl-<?= $i ?>-<?= $k ?>" onclick="selectOpt(<?= $i ?>, '<?= $q['id'] ?>', '<?= $k ?>', this)">
                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $k ?>">
                    <span class="opt-circle"><?= $k ?></span>
                    <span><?= h($opts[$k]) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div style="text-align:center;margin-top:24px;">
            <button type="submit" class="submit-btn" style="padding:14px 48px;font-size:1.05rem;">
                📩 Nộp Bài Thi
            </button>
        </div>
    </form>
</div>

<!-- Nav dots & submit button -->
<div class="progress-bar">
    <div class="q-nav" id="q-nav">
        <?php for($i=0;$i<count($questions);$i++): ?>
        <div class="q-dot" id="dot-<?= $i ?>" onclick="scrollToQ(<?= $i ?>)"><?= $i+1 ?></div>
        <?php endfor; ?>
    </div>
    <button type="button" class="submit-btn" onclick="document.getElementById('exam-form').requestSubmit()">📩 Nộp Bài</button>
</div>

<script>
var answered = {};
var totalQ = <?= count($questions) ?>;
var timeLimitSec = <?= $timeLimitSec ?>;
var startTime = Date.now();
var warningShown = false;

// Timer
function updateTimer() {
    var elapsed = Math.floor((Date.now()-startTime)/1000);
    var remaining = timeLimitSec - elapsed;
    if (remaining <= 0) {
        document.getElementById('exam-form').submit();
        return;
    }
    var m = Math.floor(remaining/60);
    var s = remaining%60;
    document.getElementById('timer').textContent = '⏱ ' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    if (remaining <= 300 && !warningShown) {
        document.getElementById('timer').classList.add('warning');
        warningShown = true;
    }
}
setInterval(updateTimer, 1000);
updateTimer();

function selectOpt(idx, qId, opt, el) {
    // Clear old selection
    ['A','B','C','D'].forEach(function(k){
        var lbl = document.getElementById('lbl-'+idx+'-'+k);
        if(lbl) lbl.classList.remove('selected');
    });
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked=true;
    answered[qId] = opt;
    document.getElementById('qcard-'+idx).classList.add('answered');
    document.getElementById('dot-'+idx).classList.add('done');
}

function scrollToQ(idx) {
    document.getElementById('qcard-'+idx).scrollIntoView({behavior:'smooth',block:'center'});
    document.querySelectorAll('.q-dot').forEach(function(d){d.classList.remove('current');});
    document.getElementById('dot-'+idx).classList.add('current');
}

function confirmSubmit() {
    var done = Object.keys(answered).length;
    var unanswered = totalQ - done;
    if (unanswered > 0) {
        return confirm('Bạn còn ' + unanswered + ' câu chưa trả lời. Bạn có chắc muốn nộp bài?');
    }
    return confirm('Bạn có chắc chắn muốn nộp bài?');
}

// Warn on page leave
window.onbeforeunload = function(){return 'Rời khỏi trang này sẽ mất bài làm. Bạn có chắc không?';};
document.getElementById('exam-form').addEventListener('submit',function(){window.onbeforeunload=null;});
</script>
</body>
</html>
<?php }

// ──────────────────────────────────────────────────────────────
// HỌC VIÊN: Xem lại kết quả
// ──────────────────────────────────────────────────────────────
function pageExamResult($db) {
    if (!isLoggedIn()) { redirect('?action=login'); return; }
    $user = currentUser();
    $attemptId = (int)($_GET['attempt_id'] ?? 0);

    $attempt = $db->prepare("
        SELECT a.*, e.title as exam_title, e.passing_score, e.show_result,
               e.time_limit, c.title as course_title, e.id as exam_id
        FROM exam_attempts a
        JOIN semester_exams e ON e.id=a.exam_id
        JOIN courses c ON c.id=e.course_id
        WHERE a.id=? AND a.user_id=?
    ");
    $attempt->execute([$attemptId, $user['id']]);
    $attempt = $attempt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) { redirect('?action=student_exams'); return; }

    $showAnswers = $attempt['show_result'];
    $answers = json_decode($attempt['answers_json'] ?? '{}', true) ?: [];

    $questions = [];
    if ($showAnswers) {
        $qStmt = $db->prepare("SELECT * FROM exam_questions WHERE exam_id=? ORDER BY order_num,id");
        $qStmt->execute([$attempt['exam_id']]);
        $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);
    }
?>
<div class="container" style="max-width:720px;padding-top:32px;padding-bottom:48px;">
    <!-- Kết quả tổng -->
    <div class="card" style="text-align:center;padding:40px 24px;margin-bottom:24px;
        background:<?= $attempt['passed'] ? 'linear-gradient(135deg,#d1fae5,#ecfdf5)' : 'linear-gradient(135deg,#fee2e2,#fef2f2)' ?>;
        border:2px solid <?= $attempt['passed'] ? '#10b981' : '#ef4444' ?>;">
        <div style="font-size:4rem;margin-bottom:12px;"><?= $attempt['passed'] ? '🏆' : '😔' ?></div>
        <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:4px;"><?= $attempt['passed'] ? 'Chúc Mừng! Bạn Đã Đậu!' : 'Rất Tiếc! Bạn Chưa Đậu' ?></h1>
        <p style="color:var(--gray);margin-bottom:20px;"><?= h($attempt['exam_title']) ?></p>
        <?php
            $scoreOn10   = number_format($attempt['score'] / 10, 1);
            $passingOn10 = number_format($attempt['passing_score'] / 10, 1);
        ?>
        <div style="font-size:4rem;font-weight:900;color:<?= $attempt['passed']?'#10b981':'#ef4444' ?>;">
            <?= $scoreOn10 ?><span style="font-size:2rem;">/10</span>
        </div>
        <div style="margin-top:16px;display:flex;justify-content:center;gap:32px;flex-wrap:wrap;">
            <div><div style="font-weight:700;"><?= $attempt['correct_count'] ?>/<?= $attempt['total_questions'] ?></div><div style="font-size:0.8rem;color:var(--gray);">Câu đúng</div></div>
            <div><div style="font-weight:700;"><?= $passingOn10 ?>/10</div><div style="font-size:0.8rem;color:var(--gray);">Điểm qua môn</div></div>
            <div><div style="font-weight:700;"><?= $attempt['duration_minutes'] ? $attempt['duration_minutes'].' phút' : '—' ?></div><div style="font-size:0.8rem;color:var(--gray);">Thời gian</div></div>
        </div>
    </div>

    <div style="display:flex;justify-content:center;gap:12px;margin-bottom:24px;">
        <a href="?action=student_exams" class="btn btn-secondary">← Về Danh Sách</a>
    </div>

    <!-- Chi tiết câu hỏi -->
    <?php if ($showAnswers && !empty($questions)): ?>
    <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">📋 Chi Tiết Bài Làm</h2>
    <?php foreach($questions as $i => $q): ?>
    <?php
        $userAns = $answers[$q['id']] ?? null;
        $correct = $q['correct_answer'];
        $isRight = $userAns === $correct;
    ?>
    <div class="card" style="padding:20px;margin-bottom:12px;border-left:4px solid <?= $isRight?'#10b981':'#ef4444' ?>;">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
            <span style="font-size:0.8rem;font-weight:700;color:#6366f1;">Câu <?= $i+1 ?></span>
            <span><?= $isRight ? '✅ Đúng' : '❌ Sai' ?></span>
        </div>
        <p style="font-weight:600;margin-bottom:12px;"><?= h($q['question']) ?></p>
        <div style="display:grid;gap:6px;">
        <?php foreach(['A','B','C','D'] as $k): ?>
        <?php
            $isAns = $userAns === $k;
            $isCorr = $correct === $k;
            $bg = $isCorr ? '#d1fae5' : ($isAns && !$isCorr ? '#fee2e2' : '#f9fafb');
            $border = $isCorr ? '#10b981' : ($isAns && !$isCorr ? '#ef4444' : '#e5e7eb');
            $icon = $isCorr ? ' ✅' : ($isAns && !$isCorr ? ' ❌' : '');
        ?>
        <div style="padding:8px 12px;border-radius:6px;background:<?= $bg ?>;border:1px solid <?= $border ?>;font-size:0.875rem;">
            <strong><?= $k ?>.</strong> <?= h($q['option_'.strtolower($k)]) ?><?= $icon ?>
            <?php if($isAns && !$isCorr): ?><span style="color:#ef4444;font-size:0.75rem;"> ← Bạn chọn</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php if (!empty($q['explanation'])): ?>
        <div style="margin-top:10px;padding:8px 12px;background:#fffbeb;border-left:3px solid #f59e0b;border-radius:0 6px 6px 0;font-size:0.82rem;color:#92400e;">
            💡 <?= h($q['explanation']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php elseif (!$showAnswers): ?>
    <div class="alert alert-info" style="text-align:center;">🔒 Giáo viên chưa cho phép xem chi tiết bài làm.</div>
    <?php endif; ?>
</div>
<?php }

// ──────────────────────────────────────────────────────────────
// ADMIN: Tổng quan tất cả kỳ thi
// ──────────────────────────────────────────────────────────────
function pageAdminExams($db) {
    if (!hasRole('admin')) { redirect('?action=home'); return; }
    $exams = $db->query("
        SELECT e.*, c.title as course_title, u.full_name as instructor_name,
               (SELECT COUNT(*) FROM exam_questions WHERE exam_id=e.id) as q_count,
               (SELECT COUNT(*) FROM exam_attempts WHERE exam_id=e.id) as attempt_count,
               (SELECT COUNT(*) FROM exam_attempts WHERE exam_id=e.id AND passed=1) as pass_count
        FROM semester_exams e
        JOIN courses c ON c.id=e.course_id
        LEFT JOIN users u ON u.id=e.instructor_id
        ORDER BY e.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="admin-layout">
<?php renderAdminSidebar('admin_exams'); ?>
<div class="admin-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h1 style="font-size:1.5rem;font-weight:800;">📝 Quản Lý Kỳ Thi</h1>
        <a href="?action=exam_list" class="btn btn-primary">+ Tạo Kỳ Thi</a>
    </div>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr><th>Tên Kỳ Thi</th><th>Khóa Học</th><th>Giảng Viên</th><th>Câu Hỏi</th><th>Lượt Thi</th><th>Tỉ Lệ Đậu</th><th>Trạng Thái</th><th>Hành Động</th></tr>
        </thead>
        <tbody>
        <?php foreach($exams as $ex): ?>
        <?php $pr = $ex['attempt_count']>0?round($ex['pass_count']/$ex['attempt_count']*100):0; ?>
        <tr>
            <td><strong><?= h($ex['title']) ?></strong></td>
            <td><?= h($ex['course_title']) ?></td>
            <td><?= h($ex['instructor_name'] ?? '—') ?></td>
            <td style="text-align:center;"><?= $ex['q_count'] ?></td>
            <td style="text-align:center;"><?= $ex['attempt_count'] ?></td>
            <td style="text-align:center;"><span style="font-weight:700;color:<?= $pr>=50?'#10b981':'#ef4444' ?>"><?= $pr ?>%</span></td>
            <td><?= $ex['is_active']?'<span class="badge badge-success">Đang mở</span>':'<span class="badge badge-warning">Tạm dừng</span>' ?></td>
            <td style="display:flex;gap:6px;">
                <a href="?action=manage_exam&id=<?= $ex['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                <a href="?action=exam_results&id=<?= $ex['id'] ?>" class="btn btn-primary btn-sm">📊</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<?php }

// ──────────────────────────────────────────────────────────────
// EXPORT CSV (Giáo viên)
// ──────────────────────────────────────────────────────────────
function handleExportExamResults($db) {
    if (!hasRole('teacher')) { redirect('?action=login'); return; }
    $examId = (int)($_GET['id'] ?? 0);
    $exam = $db->prepare("SELECT * FROM semester_exams WHERE id=?");
    $exam->execute([$examId]);
    $exam = $exam->fetch(PDO::FETCH_ASSOC);
    if (!$exam) { redirect('?action=exam_list'); return; }

    $attempts = $db->prepare("
        SELECT u.full_name, u.username, u.email, a.score, a.correct_count,
               a.total_questions, a.passed, a.submitted_at, a.duration_minutes
        FROM exam_attempts a JOIN users u ON u.id=a.user_id
        WHERE a.exam_id=? ORDER BY a.score DESC
    ");
    $attempts->execute([$examId]);
    $rows = $attempts->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'ket_qua_' . preg_replace('/[^a-z0-9]/i','_',strtolower($exam['title'])) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');

    $out = fopen('php://output','w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    fputcsv($out, ['Họ Tên','Tên Đăng Nhập','Email','Điểm (%)','Câu Đúng','Tổng Câu','Kết Quả','Ngày Nộp','Thời Lượng (phút)']);
    foreach($rows as $r) {
        fputcsv($out, [
            $r['full_name'], $r['username'], $r['email'],
            $r['score'], $r['correct_count'], $r['total_questions'],
            $r['passed'] ? 'Đậu' : 'Rớt',
            $r['submitted_at'],
            $r['duration_minutes'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}
