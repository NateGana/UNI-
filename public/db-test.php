<?php
/**
 * public/index.php
 * -------------------------------------------------------------
 * Stage 1 test page: proves that PHP can connect to MySQL.
 * This is NOT the final login page or dashboard.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

// Prints text safely inside HTML.
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// The 11 approved tables (a fixed list, not user input).
$tables = [
    'users', 'students', 'departments', 'department_staff', 'courses',
    'enrollments', 'grades', 'clearances', 'document_types',
    'document_requests', 'payments',
];

$connected    = false;
$connectError = '';
$tablesError  = '';
$tableCounts  = [];

// Step 1: try to connect to the database.
try {
    $pdo       = getConnection();
    $connected = true;
} catch (PDOException $ex) {
    $connectError = $ex->getMessage();
}

// Step 2: if connected, count the rows in each table.
if ($connected) {
    try {
        foreach ($tables as $table) {
            $tableCounts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        }
    } catch (PDOException $ex) {
        $tableCounts = [];
        $tablesError = 'Connected, but the tables could not be read. '
                     . 'Did you import database/schema.sql? (' . $ex->getMessage() . ')';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SSIS - System Foundation</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="card">
    <p class="university">CuyoTech University</p>
    <h1>Student Services Information System</h1>
    <h2>System Foundation</h2>

    <p class="status">
        Database Connection:
        <?php if ($connected): ?>
            <span class="ok">Connected</span>
        <?php else: ?>
            <span class="fail">Not connected</span>
        <?php endif; ?>
    </p>

    <?php if ($connectError !== ''): ?>
        <p class="message">Error: <?= e($connectError) ?></p>
    <?php endif; ?>

    <?php if ($tablesError !== ''): ?>
        <p class="message"><?= e($tablesError) ?></p>
    <?php endif; ?>

    <?php if (!empty($tableCounts)): ?>
        <p>Tables found: <strong><?= count($tableCounts) ?> of <?= count($tables) ?></strong></p>
        <table>
            <tr><th>Table</th><th>Rows</th></tr>
            <?php foreach ($tableCounts as $table => $count): ?>
                <tr><td><?= e($table) ?></td><td><?= (int) $count ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p class="note">Stage 1 only: login and the modules are not built yet.</p>
</main>
<script src="../assets/js/app.js"></script>
</body>
</html>
