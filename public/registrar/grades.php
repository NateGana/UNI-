<?php
/**
 * public/registrar/grades.php
 * Stage 4: grade records, with Encode (no grade yet) and Update (grade exists).
 * One grade per enrollment. No grading scale is imposed.
 */

require_once __DIR__ . '/../../config/registrar.php';

$user = requireRole('Registrar');
$self = publicUrl('registrar/grades.php');

// ----- Handle Encode / Update (POST) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        setFlash('Your session expired. Please try again.', true);
        redirect($self);
    }

    $enrollmentId = filter_var($_POST['enrollment_id'] ?? '', FILTER_VALIDATE_INT);
    $action       = $_POST['action'] ?? '';
    $gradeValue   = trim((string) ($_POST['grade_value'] ?? ''));

    if ($enrollmentId === false || $enrollmentId < 1 || !in_array($action, ['encode', 'update'], true)) {
        setFlash('Invalid request.', true);
    } elseif ($gradeValue === '') {
        setFlash('Please enter a grade.', true);
    } elseif (mb_strlen($gradeValue) > 10) {
        setFlash('The grade must be 10 characters or fewer.', true);
    } else {
        try {
            $pdo = getConnection();

            // The enrollment must exist.
            $find = $pdo->prepare('SELECT enrollment_id FROM enrollments WHERE enrollment_id = ?');
            $find->execute([$enrollmentId]);

            if ($find->fetchColumn() === false) {
                setFlash('Enrollment record not found.', true);
            } else {
                // Does this enrollment already have a grade?
                $has = $pdo->prepare('SELECT grade_id FROM grades WHERE enrollment_id = ?');
                $has->execute([$enrollmentId]);
                $hasGrade = ($has->fetchColumn() !== false);
                $now      = date('Y-m-d H:i:s');

                if ($action === 'encode') {
                    if ($hasGrade) {
                        setFlash('This enrollment already has a grade. Use Update instead.', true);
                    } else {
                        $ins = $pdo->prepare(
                            'INSERT INTO grades (enrollment_id, encoded_by, grade_value, date_encoded, last_updated)
                             VALUES (?, ?, ?, ?, ?)'
                        );
                        $ins->execute([$enrollmentId, $user['user_id'], $gradeValue, $now, $now]);
                        setFlash('Grade encoded for enrollment #' . $enrollmentId . '.');
                    }
                } else { // update
                    if (!$hasGrade) {
                        setFlash('No grade exists yet for this enrollment. Use Encode first.', true);
                    } else {
                        // encoded_by is kept as the original encoder.
                        $upd = $pdo->prepare(
                            'UPDATE grades SET grade_value = ?, last_updated = ? WHERE enrollment_id = ?'
                        );
                        $upd->execute([$gradeValue, $now, $enrollmentId]);
                        setFlash('Grade updated for enrollment #' . $enrollmentId . '.');
                    }
                }
            }
        } catch (PDOException $ex) {
            setFlash('The grade could not be saved. Please try again later.', true);
        }
    }
    redirect($self);
}

// ----- Show the list -----
$rows = registrarRows(
    'SELECT e.enrollment_id, s.student_id, u.full_name, c.course_code, c.course_name,
            e.status AS enrollment_status,
            g.grade_id, g.grade_value, g.date_encoded, g.last_updated
     FROM enrollments e
     JOIN students s ON s.student_id = e.student_id
     JOIN users u    ON u.user_id    = s.user_id
     JOIN courses c  ON c.course_id  = e.course_id
     LEFT JOIN grades g ON g.enrollment_id = e.enrollment_id
     ORDER BY u.full_name, c.course_code, e.enrollment_id'
);

registrarPageStart($user, 'grades', 'Grades');

if ($rows === null) {
    registrarNotice('Unable to load grade records. Please try again later.', true);
} elseif (empty($rows)) {
    registrarNotice('No records found.');
} else {
    ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student ID</th><th>Student Name</th><th>Course Code</th><th>Course Name</th>
                    <th>Enrollment Status</th><th>Grade</th><th>Date Encoded</th><th>Last Updated</th>
                    <th>Encode / Update</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php $hasGrade = ($r['grade_id'] !== null); ?>
                <tr>
                    <td><?= dash($r['student_id']) ?></td>
                    <td class="cell-strong"><?= dash($r['full_name']) ?></td>
                    <td class="cell-strong"><?= dash($r['course_code']) ?></td>
                    <td><?= dash($r['course_name']) ?></td>
                    <td><?= ssisBadge($r['enrollment_status']) ?></td>
                    <td><?php if ($hasGrade): ?><span class="grade-value"><?= dash($r['grade_value']) ?></span><?php else: ?><span class="badge badge-neutral">Not encoded</span><?php endif; ?></td>
                    <td class="nowrap"><?= dash($r['date_encoded']) ?></td>
                    <td class="nowrap"><?= dash($r['last_updated']) ?></td>
                    <td class="actions">
                        <form method="post" action="grades.php" class="inline-form grade-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="enrollment_id" value="<?= (int) $r['enrollment_id'] ?>">
                            <input type="text" name="grade_value" class="grade-input" maxlength="10"
                                   value="<?= $hasGrade ? e($r['grade_value']) : '' ?>"
                                   aria-label="Grade for <?= e($r['full_name']) ?>, <?= e($r['course_code']) ?>">
                            <button type="submit" name="action" value="<?= $hasGrade ? 'update' : 'encode' ?>"
                                    class="btn btn-small<?= $hasGrade ? ' btn-outline' : '' ?>"><?= $hasGrade ? 'Update' : 'Encode' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

registrarPageEnd();
