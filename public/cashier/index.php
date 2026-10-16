<?php
/**
 * public/cashier/index.php
 * Stage 5: Cashier Dashboard (summary counts from the payments table).
 */

require_once __DIR__ . '/../../config/cashier.php';

$user = requireRole('Cashier');

$total    = cashierValue('SELECT COUNT(*) FROM payments');
$mine     = cashierValue('SELECT COUNT(*) FROM payments WHERE recorded_by = ?', [$user['user_id']]);
$document = cashierValue('SELECT COUNT(*) FROM payments WHERE request_id IS NOT NULL');
$nonDoc   = cashierValue('SELECT COUNT(*) FROM payments WHERE request_id IS NULL');

cashierPageStart($user, 'dashboard', 'Cashier Dashboard');
?>
    <section class="welcome">
        <div>
            <p class="welcome-title">Welcome, <?= e($user['full_name']) ?>.</p>
            <p class="welcome-sub">Here is a summary of the payments recorded in the system.</p>
        </div>
        <span class="welcome-role"><?= e($user['role']) ?></span>
    </section>

    <div class="stat-grid">
<?php
cashierStatCard('Total Payment Records',         $total,    'payment records',            'payments.php',                 'View payment records',       'receipt');
cashierStatCard('Recorded by Me',                $mine,     'payments I recorded',        'payments.php',                 'View payment records',       'user',  'info');
cashierStatCard('Document-Request Payments',     $document, 'linked to a Request ID',     'payments.php?type=document',   'View document payments',     'file',  'gold');
cashierStatCard('Non-Document Payments',         $nonDoc,   'not linked to a request',    'payments.php?type=non',        'View non-document payments', 'wallet');
?>
    </div>
<?php
cashierPageEnd();
