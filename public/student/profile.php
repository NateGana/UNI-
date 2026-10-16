<?php
/**
 * public/student/profile.php
 * Stage 3: the logged-in student's own profile (view only).
 */

require_once __DIR__ . '/../../config/student.php';

$user      = requireRole('Student');
$studentId = requireStudentRecord($user);

$rows = studentRows('SELECT s.student_id, u.full_name, u.username, u.email, u.role
                     FROM students s JOIN users u ON u.user_id = s.user_id
                     WHERE s.student_id = ?', $studentId);

studentPageStart($user, 'profile', 'My Profile');

if ($rows === null) {
    studentNotice('Unable to load your profile. Please try again later.', true);
} elseif (empty($rows)) {
    studentNotice('No records found.');
} else {
    $p = $rows[0];
    ?>
    <section class="panel detail-card">
        <div class="detail-hero">
            <span class="avatar" aria-hidden="true"><?= ssisInitials($p['full_name']) ?></span>
            <div>
                <h2><?= cell($p['full_name']) ?></h2>
                <p>Student ID <?= cell($p['student_id']) ?></p>
            </div>
        </div>
        <dl class="detail-list">
            <div class="detail-row"><dt>Student ID</dt><dd><?= cell($p['student_id']) ?></dd></div>
            <div class="detail-row"><dt>Full Name</dt><dd><?= cell($p['full_name']) ?></dd></div>
            <div class="detail-row"><dt>Username</dt><dd><?= cell($p['username']) ?></dd></div>
            <div class="detail-row"><dt>Email</dt><dd><?= cell($p['email']) ?></dd></div>
            <div class="detail-row"><dt>Role</dt><dd><?= cell($p['role']) ?></dd></div>
        </dl>
    </section>
    <?php
}

studentPageEnd();
