<?php
/**
 * public/student/grades.php
 * Stage 3: the logged-in student's grades (view only).
 * Path: students -> enrollments -> grades -> courses
 */

require_once __DIR__ . '/../../config/student.php';

$user      = requireRole('Student');
$studentId = requireStudentRecord($user);

$rows = studentRows('SELECT c.course_code, c.course_name, c.units,
                            g.grade_value, g.date_encoded, g.last_updated
                     FROM enrollments e
                     JOIN grades g  ON g.enrollment_id = e.enrollment_id
                     JOIN courses c ON c.course_id     = e.course_id
                     WHERE e.student_id = ?
                     ORDER BY c.course_code', $studentId);

studentPageStart($user, 'grades', 'My Grades');

if ($rows === null) {
    studentNotice('Unable to load your grades. Please try again later.', true);
} elseif (empty($rows)) {
    studentNotice('No grades have been encoded yet.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Course Code</th><th>Course Name</th><th class="num">Units</th>
                    <th>Grade</th><th>Date Encoded</th><th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="cell-strong"><?= cell($r['course_code']) ?></td>
                    <td><?= cell($r['course_name']) ?></td>
                    <td class="num"><?= cell($r['units']) ?></td>
                    <td class="grade-value"><?= cell($r['grade_value']) ?></td>
                    <td class="nowrap"><?= cell($r['date_encoded']) ?></td>
                    <td class="nowrap"><?= cell($r['last_updated']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

studentPageEnd();
