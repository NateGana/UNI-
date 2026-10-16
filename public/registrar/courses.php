<?php
/**
 * public/registrar/courses.php
 * Stage 4: course/subject records (view only).
 */

require_once __DIR__ . '/../../config/registrar.php';

$user = requireRole('Registrar');

$rows = registrarRows('SELECT course_id, course_code, course_name, units
                       FROM courses
                       ORDER BY course_code');

registrarPageStart($user, 'courses', 'Courses');

if ($rows === null) {
    registrarNotice('Unable to load courses. Please try again later.', true);
} elseif (empty($rows)) {
    registrarNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Course ID</th><th>Course Code</th><th>Course Name</th><th class="num">Units</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= dash($r['course_id']) ?></td>
                    <td class="cell-strong"><?= dash($r['course_code']) ?></td>
                    <td><?= dash($r['course_name']) ?></td>
                    <td class="num"><?= dash($r['units']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

registrarPageEnd();
