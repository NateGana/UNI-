<?php
/**
 * database/seed.php
 * -------------------------------------------------------------
 * Adds a small amount of FICTIONAL sample data for testing.
 * Passwords are stored with password_hash() (never as plain text).
 * Run it ONCE in the browser after importing schema.sql:
 *   http://localhost/ssis/database/seed.php
 * If the users table already has rows, it does nothing.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

// Prints text safely inside HTML.
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

$pdo      = null;
$message  = '';
$error    = '';
$accounts = [];   // accounts shown on the page after seeding
$counts   = [];

try {
    $pdo = getConnection();

    $existingUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($existingUsers > 0) {
        $message = 'Nothing was added because the users table already contains data.';
    } else {
        $pdo->beginTransaction();

        // Small helper: runs a prepared INSERT and returns the new id.
        $insert = function (string $sql, array $params) use ($pdo): int {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $pdo->lastInsertId();
        };

        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');

        // 1. users (fictional prototype accounts)
        $people = [
            // username,     password,        full name,                 email,                       role
            ['admin',       'Admin123!',     'Test Admin',              'admin@example.com',         'Admin'],
            ['student01',   'Student123!',   'Test Student',            'student01@example.com',     'Student'],
            ['registrar01', 'Registrar123!', 'Test Registrar',          'registrar01@example.com',   'Registrar'],
            ['cashier01',   'Cashier123!',   'Test Cashier',            'cashier01@example.com',     'Cashier'],
            ['deptstaff01', 'Dept123!',      'Test Department Staff',   'deptstaff01@example.com',   'Department Staff'],
        ];

        $userIds = [];
        foreach ($people as $p) {
            $userIds[$p[0]] = $insert(
                'INSERT INTO users (username, password_hash, full_name, email, role, account_status)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$p[0], password_hash($p[1], PASSWORD_DEFAULT), $p[2], $p[3], $p[4], 'Active']
            );
            $accounts[] = ['username' => $p[0], 'password' => $p[1], 'role' => $p[4]];
        }

        // 2. students (links student01 to a student record)
        $studentId = $insert('INSERT INTO students (user_id) VALUES (?)', [$userIds['student01']]);

        // 3. departments (generic sample names)
        $dept1 = $insert('INSERT INTO departments (department_name) VALUES (?)', ['Sample Department 1']);
        $dept2 = $insert('INSERT INTO departments (department_name) VALUES (?)', ['Sample Department 2']);

        // 4. department_staff (deptstaff01 belongs to Sample Department 1)
        $insert('INSERT INTO department_staff (user_id, department_id) VALUES (?, ?)',
                [$userIds['deptstaff01'], $dept1]);

        // 5. courses (sample)
        $course1 = $insert('INSERT INTO courses (course_code, course_name, units) VALUES (?, ?, ?)',
                           ['SAMPLE101', 'Sample Course One', 3]);
        $course2 = $insert('INSERT INTO courses (course_code, course_name, units) VALUES (?, ?, ?)',
                           ['SAMPLE102', 'Sample Course Two', 3]);
        $insert('INSERT INTO courses (course_code, course_name, units) VALUES (?, ?, ?)',
                ['SAMPLE103', 'Sample Course Three', 2]);

        // 6. document_types: the three approved types.
        //    The Yes/No fee values are TEST VALUES ONLY (not a university policy).
        $torId = $insert('INSERT INTO document_types (type_name, fee_required) VALUES (?, ?)', ['TOR', 'Yes']);
        $insert('INSERT INTO document_types (type_name, fee_required) VALUES (?, ?)', ['COR', 'No']);
        $insert('INSERT INTO document_types (type_name, fee_required) VALUES (?, ?)', ['Certification', 'Yes']);

        // 7. enrollments: one Approved (decided by the registrar) and one Pending (no decision yet)
        $enrollmentId = $insert(
            'INSERT INTO enrollments (student_id, course_id, request_date, status, decision_date, decided_by)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$studentId, $course1, $today, 'Approved', $today, $userIds['registrar01']]
        );
        $insert(
            'INSERT INTO enrollments (student_id, course_id, request_date, status, decision_date, decided_by)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$studentId, $course2, $today, 'Pending', null, null]
        );

        // 8. grades: one sample grade for the approved enrollment
        $insert(
            'INSERT INTO grades (enrollment_id, encoded_by, grade_value, date_encoded, last_updated)
             VALUES (?, ?, ?, ?, ?)',
            [$enrollmentId, $userIds['registrar01'], '90', $now, $now]
        );

        // 9. clearances: one Pending record per sample department
        foreach ([$dept1, $dept2] as $deptId) {
            $insert(
                'INSERT INTO clearances (student_id, department_id, request_date, status, remarks, decision_date, decided_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$studentId, $deptId, $today, 'Pending', null, null, null]
            );
        }

        // 10. document_requests: one sample TOR request (no payment yet)
        $insert(
            'INSERT INTO document_requests
               (student_id, document_type_id, purpose, status, rejection_remark, request_date, processed_by, last_updated)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$studentId, $torId, 'Sample purpose for testing', 'Submitted', null, $today, null, $now]
        );

        // 11. payments: one NON-document payment (request_id is NULL)
        $insert(
            'INSERT INTO payments
               (student_id, request_id, amount, payment_date, payment_purpose, receipt_number, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$studentId, null, 100.00, $today, 'Sample non-document payment', 'SAMPLE-RECEIPT-1', $userIds['cashier01']]
        );

        $pdo->commit();
        $message = 'Sample data was added successfully.';
    }

    // Show how many rows each table has now.
    $tables = ['users', 'students', 'departments', 'department_staff', 'courses', 'enrollments',
               'grades', 'clearances', 'document_types', 'document_requests', 'payments'];
    foreach ($tables as $table) {
        $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    }
} catch (Throwable $ex) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SSIS Sample Data</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="card">
    <h1>SSIS Sample Data</h1>

    <?php if ($error !== ''): ?>
        <p class="message">Something went wrong: <?= e($error) ?></p>
        <p>Check that you imported <code>database/schema.sql</code> first.</p>
    <?php else: ?>
        <p class="message ok-box"><?= e($message) ?></p>

        <?php if (!empty($accounts)): ?>
            <h2>Fictional test accounts</h2>
            <table>
                <tr><th>Role</th><th>Username</th><th>Password</th></tr>
                <?php foreach ($accounts as $a): ?>
                    <tr>
                        <td><?= e($a['role']) ?></td>
                        <td><?= e($a['username']) ?></td>
                        <td><?= e($a['password']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h2>Rows in each table</h2>
        <table>
            <tr><th>Table</th><th>Rows</th></tr>
            <?php foreach ($counts as $table => $count): ?>
                <tr><td><?= e($table) ?></td><td><?= (int) $count ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p class="note"><a href="../public/index.php">Go to the test page</a></p>
</main>
</body>
</html>
