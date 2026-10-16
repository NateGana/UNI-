<?php
/**
 * public/student/clearance.php
 * Stage 3: the logged-in student's clearance records (view only).
 * Submitting clearance requests is not part of this stage.
 */

require_once __DIR__ . '/../../config/student.php';

$user      = requireRole('Student');
$studentId = requireStudentRecord($user);

$rows = studentRows('SELECT cl.clearance_id, d.department_name, cl.request_date,
                            cl.status, cl.remarks, cl.decision_date
                     FROM clearances cl
                     JOIN departments d ON d.department_id = cl.department_id
                     WHERE cl.student_id = ?
                     ORDER BY cl.clearance_id', $studentId);

studentPageStart($user, 'clearance', 'My Clearance');

if ($rows === null) {
    studentNotice('Unable to load your clearance records. Please try again later.', true);
} elseif (empty($rows)) {
    studentNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Clearance ID</th><th>Department</th><th>Request Date</th>
                    <th>Status</th><th>Remarks</th><th>Decision Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= cell($r['clearance_id']) ?></td>
                    <td class="cell-strong"><?= cell($r['department_name']) ?></td>
                    <td class="nowrap"><?= cell($r['request_date']) ?></td>
                    <td><?= ssisBadge($r['status']) ?></td>
                    <td><?= cell($r['remarks']) ?></td>
                    <td class="nowrap"><?= cell($r['decision_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

studentPageEnd();
