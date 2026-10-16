<?php
/**
 * public/registrar/enrollment.php
 * Stage 4: enrollment records, with Approve / Reject for Pending ones.
 * Approve/Reject use POST + CSRF and only work on Pending records.
 */

require_once __DIR__ . '/../../config/registrar.php';

$user = requireRole('Registrar');
$self = publicUrl('registrar/enrollment.php');

// ----- Handle Approve / Reject (POST) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        setFlash('Your session expired. Please try again.', true);
        redirect($self);
    }

    $enrollmentId = filter_var($_POST['enrollment_id'] ?? '', FILTER_VALIDATE_INT);
    $action       = $_POST['action'] ?? '';

    if ($enrollmentId === false || $enrollmentId < 1 || !in_array($action, ['approve', 'reject'], true)) {
        setFlash('Invalid request.', true);
        redirect($self);
    }

    $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';

    try {
        $pdo  = getConnection();

        // Only a Pending enrollment can be decided.
        $stmt = $pdo->prepare(
            "UPDATE enrollments
             SET status = ?, decision_date = ?, decided_by = ?
             WHERE enrollment_id = ? AND status = 'Pending'"
        );
        $stmt->execute([$newStatus, date('Y-m-d'), $user['user_id'], $enrollmentId]);

        if ($stmt->rowCount() === 1) {
            setFlash('Enrollment #' . $enrollmentId . ' was ' . strtolower($newStatus) . '.');
        } else {
            // Nothing changed: find out why, to show a clear message.
            $check = $pdo->prepare('SELECT status FROM enrollments WHERE enrollment_id = ?');
            $check->execute([$enrollmentId]);
            $current = $check->fetchColumn();

            if ($current === false) {
                setFlash('Enrollment record not found.', true);
            } else {
                setFlash('Enrollment #' . $enrollmentId . ' is already ' . $current . ' and cannot be processed again.', true);
            }
        }
    } catch (PDOException $ex) {
        setFlash('The enrollment could not be updated. Please try again later.', true);
    }
    redirect($self);
}

// ----- Show the list (optional filter by status) -----
$statuses = ['Pending', 'Approved', 'Rejected'];
$filter   = $_GET['status'] ?? '';
$filter   = in_array($filter, $statuses, true) ? $filter : '';

$sql = 'SELECT e.enrollment_id, s.student_id, u.full_name, c.course_code, c.course_name,
               e.request_date, e.status, e.decision_date
        FROM enrollments e
        JOIN students s ON s.student_id = e.student_id
        JOIN users u    ON u.user_id    = s.user_id
        JOIN courses c  ON c.course_id  = e.course_id '
     . ($filter !== '' ? 'WHERE e.status = ? ' : '')
     . "ORDER BY (e.status = 'Pending') DESC, e.enrollment_id DESC";

$rows = registrarRows($sql, $filter !== '' ? [$filter] : []);

registrarPageStart($user, 'enrollment', 'Enrollment');
?>
    <p class="filter-links"><span class="filter-label">View:</span>
        <a href="enrollment.php"<?= $filter === '' ? ' class="active"' : '' ?>>All</a>
        <?php foreach ($statuses as $st): ?>
            <a href="enrollment.php?status=<?= e(urlencode($st)) ?>"<?= $filter === $st ? ' class="active"' : '' ?>><?= e($st) ?></a>
        <?php endforeach; ?>
    </p>
<?php
if ($rows === null) {
    registrarNotice('Unable to load enrollment records. Please try again later.', true);
} elseif (empty($rows)) {
    registrarNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Enrollment ID</th><th>Student ID</th><th>Student Name</th>
                    <th>Course Code</th><th>Course Name</th><th>Request Date</th>
                    <th>Status</th><th>Decision Date</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= dash($r['enrollment_id']) ?></td>
                    <td><?= dash($r['student_id']) ?></td>
                    <td class="cell-strong"><?= dash($r['full_name']) ?></td>
                    <td class="cell-strong"><?= dash($r['course_code']) ?></td>
                    <td><?= dash($r['course_name']) ?></td>
                    <td class="nowrap"><?= dash($r['request_date']) ?></td>
                    <td><?= ssisBadge($r['status']) ?></td>
                    <td class="nowrap"><?= dash($r['decision_date']) ?></td>
                    <td class="actions">
                        <?php if ($r['status'] === 'Pending'): ?>
                            <form method="post" action="enrollment.php" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="enrollment_id" value="<?= (int) $r['enrollment_id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-small">Approve</button>
                            </form>
                            <form method="post" action="enrollment.php" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="enrollment_id" value="<?= (int) $r['enrollment_id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-small btn-danger">Reject</button>
                            </form>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

registrarPageEnd();
