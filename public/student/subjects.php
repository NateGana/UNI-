<?php
/**
 * public/student/subjects.php
 * Stage 3: the logged-in student's subjects (enrollments + courses), view only.
 */

require_once __DIR__ . '/../../config/student.php';

$user      = requireRole('Student');
$studentId = requireStudentRecord($user);

$rows = studentRows('SELECT c.course_code, c.course_name, c.units, e.status
                     FROM enrollments e
                     JOIN courses c ON c.course_id = e.course_id
                     WHERE e.student_id = ?
                     ORDER BY c.course_code, e.enrollment_id', $studentId);

studentPageStart($user, 'subjects', 'My Subjects');

if ($rows === null) {
    studentNotice('Unable to load your subjects. Please try again later.', true);
} elseif (empty($rows)) {
    studentNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Course Code</th><th>Course Name</th><th class="num">Units</th><th>Enrollment Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="cell-strong"><?= cell($r['course_code']) ?></td>
                    <td><?= cell($r['course_name']) ?></td>
                    <td class="num"><?= cell($r['units']) ?></td>
                    <td><?= ssisBadge($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

studentPageEnd();
