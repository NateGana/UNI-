<?php
/**
 * public/registrar/students.php
 * Stage 4: student records (view only). Passwords are never selected.
 */

require_once __DIR__ . '/../../config/registrar.php';

$user = requireRole('Registrar');

$rows = registrarRows('SELECT s.student_id, u.full_name, u.username, u.email, u.account_status
                       FROM students s
                       JOIN users u ON u.user_id = s.user_id
                       ORDER BY s.student_id');

registrarPageStart($user, 'students', 'Student Records');

if ($rows === null) {
    registrarNotice('Unable to load student records. Please try again later.', true);
} elseif (empty($rows)) {
    registrarNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Student ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Account Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= dash($r['student_id']) ?></td>
                    <td class="cell-strong"><?= dash($r['full_name']) ?></td>
                    <td><?= dash($r['username']) ?></td>
                    <td><?= dash($r['email']) ?></td>
                    <td><?= ssisBadge($r['account_status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

registrarPageEnd();
