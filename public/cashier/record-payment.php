<?php
/**
 * public/cashier/record-payment.php
 * Stage 5: record a document-request payment or a non-document payment.
 * Every action is POST + CSRF, validated on the server, then redirected
 * (Post/Redirect/Get) so refreshing the page cannot repeat it.
 */

require_once __DIR__ . '/../../config/cashier.php';

$user = requireRole('Cashier');
$self = publicUrl('cashier/record-payment.php');

$amountError  = 'Enter a valid amount greater than 0, with at most two decimal places.';
$notPayable   = 'Payment cannot be recorded for this request. It does not currently require a payment.';

// ----- Handle form submissions (POST) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        cashierSetFlash('Your session expired. Please try again.', true);
        redirect($self);
    }

    $action = cashierPost('action');

    // ---- A1. Check a Request ID (shows only: valid? payment required?) ----
    if ($action === 'check_request') {
        $requestId = cashierParseId(cashierPost('request_id'));

        if ($requestId === null) {
            cashierSetFlash('Please enter a valid Request ID (a whole number).', true);
        } else {
            try {
                cashierStash('check', cashierCheckRequest(getConnection(), $requestId));
            } catch (PDOException $ex) {
                cashierSetFlash('The Request ID could not be checked right now. Please try again later.', true);
            }
        }

    // ---- A2. Record a document-request payment ----
    } elseif ($action === 'record_document') {
        $requestId = cashierParseId(cashierPost('request_id'));
        $amount    = cashierParseAmount(cashierPost('amount'));

        if ($requestId === null) {
            cashierSetFlash('Please enter a valid Request ID (a whole number).', true);
        } elseif ($amount === null) {
            cashierSetFlash($amountError, true);
            cashierRestoreCheck($requestId);
        } else {
            $pdo       = null;
            $paymentId = null;
            $problem   = '';

            try {
                $pdo = getConnection();
                $pdo->beginTransaction();

                // Read the request again and lock its row, so two cashiers
                // cannot pay the same request at the same moment.
                $stmt = $pdo->prepare(
                    'SELECT dr.student_id, dr.status, dt.type_name, dt.fee_required
                     FROM document_requests dr
                     JOIN document_types dt ON dt.document_type_id = dr.document_type_id
                     WHERE dr.request_id = ?
                     FOR UPDATE'
                );
                $stmt->execute([$requestId]);
                $req = $stmt->fetch();

                if (!$req) {
                    $problem = 'Request ID not found.';
                } else {
                    $dup = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE request_id = ?');
                    $dup->execute([$requestId]);

                    if ($req['fee_required'] !== 'Yes'
                        || $req['status'] !== 'Processing'
                        || (int) $dup->fetchColumn() > 0) {
                        $problem = $notPayable;
                    } else {
                        // student_id comes from the request in the database, never from the form.
                        $paymentId = cashierInsertPayment(
                            $pdo,
                            (int) $req['student_id'],
                            $requestId,
                            $amount,
                            'Document request payment - ' . $req['type_name'],
                            (int) $user['user_id']
                        );
                    }
                }

                if ($paymentId !== null) {
                    $pdo->commit();
                } else {
                    $pdo->rollBack();
                }
            } catch (RuntimeException $ex) {
                $paymentId = null;
                if ($pdo !== null && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $problem = ($ex->getMessage() === 'duplicate_request')
                    ? $notPayable
                    : 'A receipt number could not be generated. Please try again.';
            } catch (PDOException $ex) {
                $paymentId = null;
                if ($pdo !== null && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $problem = 'The payment could not be saved. Please try again later.';
            }

            if ($paymentId !== null) {
                cashierSetFlash('Payment recorded successfully.');
                cashierStash('receipt', $paymentId);
            } else {
                cashierSetFlash($problem, true);
                cashierRestoreCheck($requestId);
            }
        }

    // ---- B. Record a non-document payment ----
    } elseif ($action === 'record_non_document') {
        $studentId = cashierParseId(cashierPost('student_id'));
        $amount    = cashierParseAmount(cashierPost('amount'));
        $purpose   = cashierPost('payment_purpose');

        $paymentId = null;
        $problem   = '';

        if ($studentId === null) {
            $problem = 'Please select a student.';
        } elseif ($amount === null) {
            $problem = $amountError;
        } elseif ($purpose === '') {
            $problem = 'Please enter the payment purpose.';
        } elseif (mb_strlen($purpose) > 255) {
            $problem = 'The payment purpose must be 255 characters or fewer.';
        } else {
            try {
                $pdo  = getConnection();
                $find = $pdo->prepare('SELECT student_id FROM students WHERE student_id = ?');
                $find->execute([$studentId]);

                if ($find->fetchColumn() === false) {
                    $problem = 'The selected student was not found.';
                } else {
                    // request_id is null: this payment is not linked to a document request.
                    $paymentId = cashierInsertPayment(
                        $pdo, $studentId, null, $amount, $purpose, (int) $user['user_id']
                    );
                }
            } catch (RuntimeException $ex) {
                $problem = 'A receipt number could not be generated. Please try again.';
            } catch (PDOException $ex) {
                $problem = 'The payment could not be saved. Please try again later.';
            }
        }

        if ($paymentId !== null) {
            cashierSetFlash('Payment recorded successfully.');
            cashierStash('receipt', $paymentId);
        } else {
            cashierSetFlash($problem, true);
        }

    } else {
        cashierSetFlash('Invalid request.', true);
    }

    redirect($self);
}

// ----- Prepare what to show (GET) -----
$receiptId = cashierUnstash('receipt');
$check     = cashierUnstash('check');
$receipt   = null;

if (is_int($receiptId)) {
    // The receipt is read back from the database, so it shows what was really saved.
    $rows    = cashierRows(
        'SELECT payment_id, request_id, amount, payment_date, payment_purpose, receipt_number
         FROM payments WHERE payment_id = ? AND recorded_by = ?',
        [$receiptId, $user['user_id']]
    );
    $receipt = $rows[0] ?? null;
}

$students = cashierRows(
    'SELECT s.student_id, u.full_name
     FROM students s JOIN users u ON u.user_id = s.user_id
     ORDER BY u.full_name, s.student_id'
);

cashierPageStart($user, 'record', 'Record Payment');

if ($receipt !== null):
    ?>
    <div class="receipt-wrap">
    <section class="receipt" aria-label="Payment receipt">
        <div class="receipt-head">
            <span class="brand-mark"><?= ssisIcon('cap') ?></span>
            <div>
                <h2>Payment Receipt</h2>
                <p>CuyoTech University SSIS &middot; prototype receipt</p>
            </div>
        </div>
        <p class="receipt-title">Amount received</p>
        <div class="receipt-amount">
            <span class="figure"><?= e(number_format((float) $receipt['amount'], 2)) ?></span>
        </div>
        <dl>
            <div class="rrow"><dt>Receipt Number</dt><dd class="receipt-no"><?= e($receipt['receipt_number']) ?></dd></div>
            <div class="rrow"><dt>Payment ID</dt><dd><?= e($receipt['payment_id']) ?></dd></div>
            <?php if ($receipt['request_id'] !== null): ?>
                <div class="rrow"><dt>Request ID</dt><dd><?= e($receipt['request_id']) ?></dd></div>
            <?php endif; ?>
            <div class="rrow"><dt>Date</dt><dd><?= e($receipt['payment_date']) ?></dd></div>
            <div class="rrow"><dt>Purpose</dt><dd><?= e($receipt['payment_purpose']) ?></dd></div>
        </dl>
        <p class="receipt-foot">This receipt was generated by the CuyoTech University Student Services Information System.</p>
    </section>
    <p class="receipt-actions no-print">
        <button type="button" class="btn" onclick="window.print()"><?= ssisIcon('print') ?>Print Receipt</button>
        <a href="payments.php">View Payment Records</a>
    </p>
    </div>
<?php endif; ?>

    <div class="pay-grid no-print">

        <!-- A. Document-request payment -->
        <section class="panel pay-box" aria-labelledby="doc-pay-title">
            <div class="panel-head">
                <span class="icon-tile"><?= ssisIcon('file') ?></span>
                <div>
                    <h2 id="doc-pay-title">Document-Request Payment</h2>
                    <p>Check a Request ID first, then record the payment.</p>
                </div>
                <span class="pay-tag">Linked to a Request ID</span>
            </div>
            <div class="panel-body">

            <form method="post" action="record-payment.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="check_request">
                <div class="form-group">
                    <label for="check_request_id">Request ID *</label>
                    <input type="text" id="check_request_id" name="request_id" inputmode="numeric"
                           autocomplete="off" value="<?= $check !== null ? e($check['request_id']) : '' ?>">
                </div>
                <button type="submit" class="btn btn-block">Check Request ID</button>
            </form>

            <?php if ($check !== null): ?>
                <div class="check-result" role="status">
                    <p><strong>Request ID:</strong> <span class="chip"><?= e($check['request_id']) ?></span></p>
                    <p><strong>Valid:</strong> <span class="badge <?= $check['valid'] ? 'badge-success' : 'badge-danger' ?>"><?= $check['valid'] ? 'Yes' : 'No' ?></span></p>
                    <p><strong>Payment required:</strong> <?= $check['valid'] ? '<span class="badge ' . ($check['required'] ? 'badge-warn' : 'badge-neutral') . '">' . ($check['required'] ? 'Yes' : 'No') . '</span>' : '&mdash;' ?></p>
                </div>

                <?php if ($check['valid'] && $check['required']): ?>
                    <form method="post" action="record-payment.php" class="record-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="record_document">
                        <input type="hidden" name="request_id" value="<?= (int) $check['request_id'] ?>">
                        <p class="step-label">Record the payment for this request</p>
                        <div class="form-group">
                            <label for="doc_amount">Amount received *</label>
                            <input type="text" id="doc_amount" name="amount" inputmode="decimal" autocomplete="off">
                            <p class="field-help">This prototype has no configured fee amount. Enter the actual amount received.</p>
                        </div>
                        <button type="submit" class="btn btn-block">Record Payment</button>
                    </form>
                <?php elseif ($check['valid']): ?>
                    <?php cashierNotice('No payment can be recorded for this request.'); ?>
                <?php else: ?>
                    <?php cashierNotice('Request ID not found.', true); ?>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </section>

        <!-- B. Non-document payment -->
        <section class="panel pay-box is-alt" aria-labelledby="non-doc-title">
            <div class="panel-head">
                <span class="icon-tile"><?= ssisIcon('wallet') ?></span>
                <div>
                    <h2 id="non-doc-title">Non-Document Payment</h2>
                    <p>For payments that are not tied to a document request.</p>
                </div>
                <span class="pay-tag">No Request ID</span>
            </div>
            <div class="panel-body">

            <?php if ($students === null): ?>
                <?php cashierNotice('The student list is temporarily unavailable.', true); ?>
            <?php elseif (empty($students)): ?>
                <?php cashierNotice('No students found.'); ?>
            <?php else: ?>
                <form method="post" action="record-payment.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="record_non_document">
                    <div class="form-group">
                        <label for="student_id">Student *</label>
                        <select id="student_id" name="student_id">
                            <option value="">Select a student</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= (int) $s['student_id'] ?>"><?= e($s['student_id']) ?> &ndash; <?= e($s['full_name'] ?? '(no name)') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="non_amount">Amount received *</label>
                        <input type="text" id="non_amount" name="amount" inputmode="decimal" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="payment_purpose">Payment purpose *</label>
                        <input type="text" id="payment_purpose" name="payment_purpose" maxlength="255" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-block">Record Payment</button>
                </form>
            <?php endif; ?>
            </div>
        </section>

    </div>
<?php
cashierPageEnd();
