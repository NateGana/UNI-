<?php
/**
 * public/student/index.php
 * Stage 3: Student Dashboard (summary of the student's own records).
 */

require_once __DIR__ . '/../../config/student.php';

$user      = requireRole('Student');
$studentId = requireStudentRecord($user);

// Every query below is filtered by the logged-in student's own student_id.
$profile       = studentRows('SELECT u.full_name, u.username, u.email
                              FROM students s JOIN users u ON u.user_id = s.user_id
                              WHERE s.student_id = ?', $studentId);
$subjectCount  = studentValue('SELECT COUNT(*) FROM enrollments WHERE student_id = ?', $studentId);
$gradeCount    = studentValue('SELECT COUNT(*) FROM grades g
                               JOIN enrollments e ON e.enrollment_id = g.enrollment_id
                               WHERE e.student_id = ?', $studentId);
$latest        = studentRows('SELECT e.status, c.course_code
                              FROM enrollments e JOIN courses c ON c.course_id = e.course_id
                              WHERE e.student_id = ?
                              ORDER BY e.enrollment_id DESC LIMIT 1', $studentId);
$clearanceRows = studentRows('SELECT status, COUNT(*) AS total
                              FROM clearances WHERE student_id = ? GROUP BY status', $studentId);
$paymentCount  = studentValue('SELECT COUNT(*) FROM payments WHERE student_id = ?', $studentId);

// Clearance summary uses only the approved statuses.
$clearance = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
foreach (($clearanceRows ?? []) as $row) {
    if (isset($clearance[$row['status']])) {
        $clearance[$row['status']] = (int) $row['total'];
    }
}
$clearanceTotal = array_sum($clearance);

$p = $profile[0] ?? null;

studentPageStart($user, 'dashboard', 'Student Dashboard');
?>
    <section class="welcome">
        <div>
            <p class="welcome-title">Welcome, <?= e($user['full_name']) ?>.</p>
            <p class="welcome-sub">Here is a summary of your records at CuyoTech University.</p>
        </div>
        <span class="welcome-role"><?= e($user['role']) ?></span>
    </section>

    <div class="stat-grid">

        <section class="stat-card">
            <div class="stat-top"><span class="icon-tile"><?= ssisIcon('user') ?></span><h2>Profile Summary</h2></div>
            <?php if ($p === null): ?>
                <p class="stat-sub">Unavailable.</p>
            <?php else: ?>
                <p class="stat-main is-text"><?= cell($p['full_name']) ?></p>
                <p class="stat-sub">Username: <?= cell($p['username']) ?><br>Email: <?= cell($p['email']) ?></p>
            <?php endif; ?>
            <a class="stat-link" href="profile.php">View profile <?= ssisIcon('arrow') ?></a>
        </section>

        <section class="stat-card tone-info">
            <div class="stat-top"><span class="icon-tile"><?= ssisIcon('book') ?></span><h2>Subjects</h2></div>
            <p class="stat-main<?= $subjectCount === null ? ' is-text' : '' ?>"><?= $subjectCount === null ? 'Unavailable' : (int) $subjectCount ?></p>
            <p class="stat-sub">enrollment/subject records</p>
            <a class="stat-link" href="subjects.php">View subjects <?= ssisIcon('arrow') ?></a>
        </section>

        <section class="stat-card tone-gold">
            <div class="stat-top"><span class="icon-tile"><?= ssisIcon('award') ?></span><h2>Grades</h2></div>
            <p class="stat-main<?= $gradeCount === null ? ' is-text' : '' ?>"><?= $gradeCount === null ? 'Unavailable' : (int) $gradeCount ?></p>
            <p class="stat-sub">encoded grades</p>
            <a class="stat-link" href="grades.php">View grades <?= ssisIcon('arrow') ?></a>
        </section>

        <section class="stat-card">
            <div class="stat-top"><span class="icon-tile"><?= ssisIcon('filecheck') ?></span><h2>Enrollment Status</h2></div>
            <?php if ($latest === null): ?>
                <p class="stat-main is-text">Unavailable</p>
                <p class="stat-sub">&nbsp;</p>
            <?php elseif (empty($latest)): ?>
                <p class="stat-main is-text">&mdash;</p>
                <p class="stat-sub">No enrollment records yet.</p>
            <?php else: ?>
                <p class="stat-main is-text"><?= ssisBadge($latest[0]['status']) ?></p>
                <p class="stat-sub">Latest record: <?= e($latest[0]['course_code']) ?></p>
            <?php endif; ?>
            <a class="stat-link" href="enrollment.php">View enrollment <?= ssisIcon('arrow') ?></a>
        </section>

        <section class="stat-card tone-info">
            <div class="stat-top"><span class="icon-tile"><?= ssisIcon('shield') ?></span><h2>Clearance Status</h2></div>
            <?php if ($clearanceRows === null): ?>
                <p class="stat-main is-text">Unavailable</p>
                <p class="stat-sub">&nbsp;</p>
            <?php elseif ($clearanceTotal === 0): ?>
                <p class="stat-main is-text">&mdash;</p>
                <p class="stat-sub">No clearance records yet.</p>
            <?php else: ?>
                <p class="stat-main is-text"><?= $clearance['Approved'] ?> of <?= $clearanceTotal ?> approved</p>
                <p class="stat-sub">Pending: <?= $clearance['Pending'] ?> &middot; Rejected: <?= $clearance['Rejected'] ?></p>
            <?php endif; ?>
            <a class="stat-link" href="clearance.php">View clearance <?= ssisIcon('arrow') ?></a>
        </section>

        <section class="stat-card tone-gold">
            <div class="stat-top"><span class="icon-tile"><?= ssisIcon('receipt') ?></span><h2>Payments</h2></div>
            <p class="stat-main<?= $paymentCount === null ? ' is-text' : '' ?>"><?= $paymentCount === null ? 'Unavailable' : (int) $paymentCount ?></p>
            <p class="stat-sub">payment records</p>
            <a class="stat-link" href="payments.php">View payments &amp; receipts <?= ssisIcon('arrow') ?></a>
        </section>

    </div>
<?php
studentPageEnd();
