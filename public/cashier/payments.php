<?php
/**
 * public/cashier/payments.php
 * Stage 5: payment records (view only) with a type filter and a search box.
 * Searching and filtering use a GET form (nothing is changed) and every
 * search value is passed to the database as a prepared-statement parameter.
 */

require_once __DIR__ . '/../../config/cashier.php';

$user = requireRole('Cashier');

// ----- Read the filter and search (whitelisted / limited) -----
$types = ['' => 'All', 'document' => 'Document Request', 'non' => 'Non-Document'];

$type = $_GET['type'] ?? '';
$type = (is_string($type) && isset($types[$type])) ? $type : '';

$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim($q) : '';
if (mb_strlen($q) > 50) {
    $q = mb_substr($q, 0, 50);
}

$where  = [];
$params = [];

if ($type === 'document') {
    $where[] = 'p.request_id IS NOT NULL';
} elseif ($type === 'non') {
    $where[] = 'p.request_id IS NULL';
}

if ($q !== '') {
    // Payment ID or Student ID (exact), or Receipt Number (partial match).
    $where[]  = '(p.payment_id = ? OR p.student_id = ? OR p.receipt_number LIKE ?)';
    $number   = ctype_digit($q) ? (int) $q : 0;
    $params[] = $number;
    $params[] = $number;
    $params[] = '%' . addcslashes($q, '%_\\') . '%';
}

$sql = 'SELECT p.payment_id, p.student_id, u.full_name AS student_name, p.request_id,
               p.payment_purpose, p.amount, p.payment_date, p.receipt_number,
               COALESCE(NULLIF(c.full_name, \'\'), c.username) AS cashier_name
        FROM payments p
        JOIN students s ON s.student_id = p.student_id
        JOIN users u    ON u.user_id    = s.user_id
        JOIN users c    ON c.user_id    = p.recorded_by '
     . (empty($where) ? '' : 'WHERE ' . implode(' AND ', $where) . ' ')
     . 'ORDER BY p.payment_date DESC, p.payment_id DESC';

$rows = cashierRows($sql, $params);

cashierPageStart($user, 'payments', 'Payment Records');
?>
    <form method="get" action="payments.php" class="filter-form" role="search">
        <div>
            <label for="type">Payment type</label>
            <select id="type" name="type">
                <?php foreach ($types as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= $type === (string) $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grow">
            <label for="q">Search (Payment ID, Student ID, or Receipt Number)</label>
            <input type="text" id="q" name="q" maxlength="50" value="<?= e($q) ?>" autocomplete="off">
        </div>
        <div class="filter-form-actions">
            <button type="submit" class="btn">Apply</button>
            <a href="payments.php">Clear</a>
        </div>
    </form>
<?php
if ($rows === null) {
    cashierNotice('Payment records are temporarily unavailable. Please try again later.', true);
} elseif (empty($rows)) {
    cashierNotice(($type !== '' || $q !== '') ? 'No payments match your search.' : 'No payment records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment ID</th><th>Student ID</th><th>Student Name</th><th>Request ID</th>
                    <th>Payment Purpose</th><th class="num">Amount</th><th>Payment Date</th>
                    <th>Receipt Number</th><th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= cashierDash($r['payment_id']) ?></td>
                    <td><?= cashierDash($r['student_id']) ?></td>
                    <td class="cell-strong"><?= cashierDash($r['student_name']) ?></td>
                    <td><?= $r['request_id'] === null ? '<span class="badge badge-neutral">Non-document</span>' : e($r['request_id']) ?></td>
                    <td><?= cashierDash($r['payment_purpose']) ?></td>
                    <td class="num amount"><?= $r['amount'] === null ? '&mdash;' : e(number_format((float) $r['amount'], 2)) ?></td>
                    <td class="nowrap"><?= cashierDash($r['payment_date']) ?></td>
                    <td><?php if ($r['receipt_number'] === null || $r['receipt_number'] === ''): ?>&mdash;<?php else: ?><span class="chip chip-mono"><?= e($r['receipt_number']) ?></span><?php endif; ?></td>
                    <td><?= cashierDash($r['cashier_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

cashierPageEnd();
