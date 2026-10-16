<?php
/**
 * config/cashier.php
 * -------------------------------------------------------------
 * Stage 5: helpers shared by all Cashier Module pages (page
 * layout, one-time messages, safe queries, amount/ID checks,
 * receipt numbers). Authentication still comes from config/auth.php.
 * All function names start with "cashier" to avoid clashes with
 * the Registrar and Student helper files.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/auth.php';

// Cashier menu: key => [label, file inside public/cashier/]
const CASHIER_NAV = [
    'dashboard' => ['Dashboard',       'index.php'],
    'record'    => ['Record Payment',  'record-payment.php'],
    'payments'  => ['Payment Records', 'payments.php'],
];

/** Shows a value, or a dash when it is empty. (Output is HTML-safe.) */
function cashierDash($value): string
{
    return ($value === null || $value === '') ? '&mdash;' : e($value);
}

/** A small message box. $isError = true makes it red. */
function cashierNotice(string $text, bool $isError = false): void
{
    ssisNotice($text, $isError);
}

/** Keeps a value in the session until the next page load. */
function cashierStash(string $key, $value): void
{
    startSecureSession();
    $_SESSION['cashier_' . $key] = $value;
}

/** Returns a stashed value (and removes it), or null. */
function cashierUnstash(string $key)
{
    startSecureSession();
    $name  = 'cashier_' . $key;
    $value = $_SESSION[$name] ?? null;
    unset($_SESSION[$name]);
    return $value;
}

/** Saves a one-time message to show on the next page load. */
function cashierSetFlash(string $text, bool $isError = false): void
{
    cashierStash('flash', ['text' => $text, 'error' => $isError]);
}

/** Returns the saved message (and removes it), or null. */
function cashierTakeFlash(): ?array
{
    $flash = cashierUnstash('flash');
    return is_array($flash) ? $flash : null;
}

/** Reads a POST field as trimmed text ('' if missing or not text). */
function cashierPost(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/**
 * Runs a SELECT with a prepared statement. $params fill the "?" marks.
 * Returns all rows, or null if the database could not be read.
 */
function cashierRows(string $sql, array $params = []): ?array
{
    try {
        $stmt = getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $ex) {
        return null;
    }
}

/** Same as cashierRows() but returns the first column of the first row (or null). */
function cashierValue(string $sql, array $params = [])
{
    try {
        $stmt = getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $ex) {
        return null;
    }
}

/** Whole-number ID (1 to 4294967295) from text, or null if invalid. */
function cashierParseId(string $raw): ?int
{
    $raw = trim($raw);
    if ($raw === '' || !ctype_digit($raw) || strlen($raw) > 10) {
        return null;
    }
    $id = (int) $raw;
    return ($id >= 1 && $id <= 4294967295) ? $id : null;
}

/**
 * Amount from text: digits with an optional decimal part of at most
 * two digits, greater than zero. Returns the text, or null if invalid.
 * (The payments.amount column holds up to 99,999,999.99.)
 */
function cashierParseAmount(string $raw): ?string
{
    $raw = trim($raw);
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $raw)) {
        return null;
    }
    return ((float) $raw > 0) ? $raw : null;
}

/** A new receipt number (made by the server, never typed by the user). */
function cashierMakeReceiptNumber(): string
{
    return 'RCPT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Inserts one payment and returns its payment_id.
 * $requestId is null for a non-document payment (saved as SQL NULL).
 * If the receipt number is already used, a new one is tried (up to 5 times).
 * Throws RuntimeException('duplicate_request') if the request already has a
 * payment, RuntimeException('receipt_failed') if no unique receipt number
 * could be saved, or PDOException for other database errors.
 */
function cashierInsertPayment(PDO $pdo, int $studentId, ?int $requestId, string $amount, string $purpose, int $cashierId): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO payments
           (student_id, request_id, amount, payment_date, payment_purpose, receipt_number, recorded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        try {
            $stmt->execute([
                $studentId, $requestId, $amount, date('Y-m-d'),
                $purpose, cashierMakeReceiptNumber(), $cashierId,
            ]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $ex) {
            $message = $ex->getMessage();
            if (strpos($message, 'uq_payments_receipt') !== false) {
                continue;                                   // receipt number clash: try another
            }
            if (strpos($message, 'uq_payments_request') !== false) {
                throw new RuntimeException('duplicate_request');
            }
            throw $ex;
        }
    }
    throw new RuntimeException('receipt_failed');
}

/**
 * Request ID check. Returns ONLY: request_id, valid, required.
 * "required" is true when the request's document type needs a fee,
 * its status is Processing, and no payment exists yet.
 */
function cashierCheckRequest(PDO $pdo, int $requestId): array
{
    $stmt = $pdo->prepare(
        'SELECT dr.status, dt.fee_required,
                (SELECT COUNT(*) FROM payments p WHERE p.request_id = dr.request_id) AS payments_found
         FROM document_requests dr
         JOIN document_types dt ON dt.document_type_id = dr.document_type_id
         WHERE dr.request_id = ?'
    );
    $stmt->execute([$requestId]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['request_id' => $requestId, 'valid' => false, 'required' => false];
    }

    $required = ($row['fee_required'] === 'Yes'
              && $row['status'] === 'Processing'
              && (int) $row['payments_found'] === 0);

    return ['request_id' => $requestId, 'valid' => true, 'required' => $required];
}

/** Re-runs the Request ID check and keeps the result for the next page load. */
function cashierRestoreCheck(int $requestId): void
{
    try {
        cashierStash('check', cashierCheckRequest(getConnection(), $requestId));
    } catch (PDOException $ex) {
        // Nothing to show; the user can check the Request ID again.
    }
}

/** Prints one dashboard summary card. */
function cashierStatCard(string $title, $count, string $unit, string $link, string $linkText, string $icon = 'receipt', string $tone = ''): void
{
    if ($count === null) {
        $value = '<p class="stat-main is-text">&mdash;</p>';
        $sub   = 'Data temporarily unavailable';
    } else {
        $value = '<p class="stat-main">' . (int) $count . '</p>';
        $sub   = ((int) $count === 0) ? 'No records yet.' : $unit;
    }
    ssisStatCard($title, $value, $sub, $link, $linkText, $icon, $tone);
}

/** Starts a Cashier page: top bar, sidebar menu, page title, and any saved message. */
function cashierPageStart(array $user, string $active, string $title): void
{
    // Same menu items and URLs as CASHIER_NAV; only grouped and given icons.
    $icons = ['dashboard' => 'grid', 'record' => 'wallet', 'payments' => 'list'];
    $groupOf = ['dashboard' => 'Overview', 'record' => 'Payments', 'payments' => 'Payments'];
    $subtitles = [
        'dashboard' => 'Summary of the payments recorded in the system.',
        'record'    => 'Record a document-request payment or a non-document payment.',
        'payments'  => 'Search and review every recorded payment.',
    ];

    $groups = [];
    foreach (CASHIER_NAV as $key => $item) {
        $label = $groupOf[$key] ?? 'Menu';
        $groups[$label][0] = $label;
        $groups[$label][1][] = [$key, $item[0], publicUrl('cashier/' . $item[1]), $icons[$key] ?? 'grid'];
    }

    ssisShellStart($user, [
        'title'    => $title,
        'subtitle' => $subtitles[$active] ?? '',
        'navLabel' => 'Cashier menu',
        'active'   => $active,
        'groups'   => array_values($groups),
    ]);

    $flash = cashierTakeFlash();
    if ($flash !== null) {
        cashierNotice($flash['text'], $flash['error']);
    }
}

/** Ends a Cashier page. */
function cashierPageEnd(): void
{
    ssisShellEnd();
}
