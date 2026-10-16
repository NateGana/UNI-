<?php
/**
 * public/student/enrollment.php
 * Stage 3: the logged-in student's enrollment records and status (view only).
 * Submitting enrollment requests is not part of this stage.
 */

require_once __DIR__ . '/../../config/student.php';

$user      = requireRole('Student');
$studentId = requireStudentRecord($user);

$rows = studentRows('SELECT e.enrollment_id, c.course_code, c.course_name,
                            e.request_date, e.status, e.decision_date
                     FROM enrollments e
                     JOIN courses c ON c.course_id = e.course_id
                     WHERE e.student_id = ?
                     ORDER BY e.enrollment_id DESC', $studentId);

studentPageStart($user, 'enrollment', 'My Enrollment');

if ($rows === null) {
    studentNotice('Unable to load your enrollment records. Please try again later.', true);
} elseif (empty($rows)) {
    studentNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Enrollment ID</th><th>Course</th><th>Request Date</th>
                    <th>Status</th><th>Decision Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= cell($r['enrollment_id']) ?></td>
                    <td><span class="cell-strong"><?= e($r['course_code']) ?></span> &ndash; <?= e($r['course_name']) ?></td>
                    <td class="nowrap"><?= cell($r['request_date']) ?></td>
                    <td><?= ssisBadge($r['status']) ?></td>
                    <td class="nowrap"><?= cell($r['decision_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

studentPageEnd();
