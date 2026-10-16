<?php
/**
 * config/student.php
 * -------------------------------------------------------------
 * Stage 3: small helpers shared by all Student Portal pages
 * (page header/menu/footer, finding the logged-in student's
 * record, and safe prepared-statement queries).
 * Authentication itself still comes from config/auth.php.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/auth.php';

// Student menu: key => [label, file inside public/student/]
const STUDENT_NAV = [
    'dashboard'  => ['Dashboard',            'index.php'],
    'profile'    => ['Profile',              'profile.php'],
    'subjects'   => ['Subjects',             'subjects.php'],
    'grades'     => ['Grades',               'grades.php'],
    'enrollment' => ['Enrollment',           'enrollment.php'],
    'clearance'  => ['Clearance',            'clearance.php'],
    'payments'   => ['Payments & Receipts',  'payments.php'],
    'documents'  => ['Document Requests',    'document-requests.php'],
];

/** Shows a value, or a dash when it is empty. (Output is HTML-safe.) */
function cell($value): string
{
    return ($value === null || $value === '') ? '&mdash;' : e($value);
}

/** A small message box. $isError = true makes it red. */
function studentNotice(string $text, bool $isError = false): void
{
    ssisNotice($text, $isError);
}

/**
 * Runs a SELECT that has exactly ONE "?" (the id) using a prepared statement.
 * Returns all rows, or null if the database could not be read.
 */
function studentRows(string $sql, int $id): ?array
{
    try {
        $stmt = getConnection()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    } catch (PDOException $ex) {
        return null;
    }
}

/** Same as studentRows() but returns the first column of the first row (or null). */
function studentValue(string $sql, int $id)
{
    try {
        $stmt = getConnection()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    } catch (PDOException $ex) {
        return null;
    }
}

/**
 * Finds the logged-in user's students.student_id using students.user_id.
 * (user_id and student_id are different numbers.)
 * Shows an error page and stops if there is no student record.
 */
function requireStudentRecord(array $user): int
{
    $rows = studentRows('SELECT student_id FROM students WHERE user_id = ?', (int) $user['user_id']);

    if ($rows === null || empty($rows)) {
        studentPageStart($user, '', 'Student Record Not Found');
        studentNotice(
            $rows === null
                ? 'Unable to load your record. Please try again later.'
                : 'Your student record could not be found. Please contact the system administrator.',
            true
        );
        studentPageEnd();
        exit;
    }
    return (int) $rows[0]['student_id'];
}

/** Starts a Student Portal page: top bar, sidebar menu, and page title. */
function studentPageStart(array $user, string $active, string $title): void
{
    // Same menu items and URLs as STUDENT_NAV; only grouped and given icons.
    $icons = [
        'dashboard' => 'grid', 'profile' => 'user', 'subjects' => 'book', 'grades' => 'award',
        'enrollment' => 'filecheck', 'clearance' => 'shield', 'payments' => 'receipt', 'documents' => 'file',
    ];
    $groupOf = [
        'dashboard' => 'Overview', 'profile' => 'Overview',
        'subjects' => 'Academics', 'grades' => 'Academics', 'enrollment' => 'Academics', 'clearance' => 'Academics',
        'payments' => 'Services', 'documents' => 'Services',
    ];
    $subtitles = [
        'dashboard'  => 'A summary of your records at CuyoTech University.',
        'profile'    => 'Your account and student details.',
        'subjects'   => 'The subjects linked to your enrollment records.',
        'grades'     => 'Grades that the Registrar has encoded for you.',
        'enrollment' => 'Your enrollment requests and their decisions.',
        'clearance'  => 'Clearance status and remarks from each department.',
        'payments'   => 'Payments recorded for you and their receipt numbers.',
        'documents'  => 'This feature is not available yet.',
    ];

    $groups = [];
    foreach (STUDENT_NAV as $key => $item) {
        $label = $groupOf[$key] ?? 'Menu';
        $entry = [$key, $item[0], publicUrl('student/' . $item[1]), $icons[$key] ?? 'grid'];
        if ($key === 'documents') {
            $entry[4] = 'Soon';
        }
        $groups[$label][0] = $label;
        $groups[$label][1][] = $entry;
    }

    ssisShellStart($user, [
        'title'    => $title,
        'subtitle' => $subtitles[$active] ?? '',
        'navLabel' => 'Student menu',
        'active'   => $active,
        'groups'   => array_values($groups),
    ]);
}

/** Ends a Student Portal page. */
function studentPageEnd(): void
{
    ssisShellEnd();
}
