<?php
/**
 * public/student/payments.php
 * Stage 3: the logged-in student's payments and receipts (view only).
 * No Paid / Unpaid / N/A indicator is shown here (Registrar only).
 */

require_once __DIR__ . '/../../config/student.php';

$user      = requireRole('Student');
$studentId = requireStudentRecord($user);

$rows = studentRows('SELECT payment_id, payment_purpose, amount, payment_date,
                            receipt_number, request_id
                     FROM payments
                     WHERE student_id = ?
                     ORDER BY payment_id DESC', $studentId);

studentPageStart($user, 'payments', 'Payments & Receipts');

if ($rows === null) {
    studentNotice('Unable to load your payments. Please try again later.', true);
} elseif (empty($rows)) {
    studentNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment ID</th><th>Payment Purpose</th><th class="num">Amount</th>
                    <th>Payment Date</th><th>Receipt Number</th><th>Request ID</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= cell($r['payment_id']) ?></td>
                    <td><?= cell($r['payment_purpose']) ?></td>
                    <td class="num amount"><?= $r['amount'] === null ? '&mdash;' : e(number_format((float) $r['amount'], 2)) ?></td>
                    <td class="nowrap"><?= cell($r['payment_date']) ?></td>
                    <td><?php if ($r['receipt_number'] === null || $r['receipt_number'] === ''): ?>&mdash;<?php else: ?><span class="chip chip-mono"><?= e($r['receipt_number']) ?></span><?php endif; ?></td>
                    <td><?= cell($r['request_id']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

studentPageEnd();
