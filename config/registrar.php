<?php
/**
 * config/registrar.php
 * -------------------------------------------------------------
 * Stage 4: small helpers shared by all Registrar Module pages
 * (page header/menu/footer, flash messages, safe queries).
 * Authentication itself still comes from config/auth.php.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/auth.php';

// Registrar menu: key => [label, file inside public/registrar/]
const REGISTRAR_NAV = [
    'dashboard'  => ['Dashboard',  'index.php'],
    'students'   => ['Students',   'students.php'],
    'courses'    => ['Courses',    'courses.php'],
    'enrollment' => ['Enrollment', 'enrollment.php'],
    'grades'     => ['Grades',     'grades.php'],
];

/** Shows a value, or a dash when it is empty. (Output is HTML-safe.) */
function dash($value): string
{
    return ($value === null || $value === '') ? '&mdash;' : e($value);
}

/** A small message box. $isError = true makes it red. */
function registrarNotice(string $text, bool $isError = false): void
{
    ssisNotice($text, $isError);
}

/** Saves a one-time message to show on the next page load. */
function setFlash(string $text, bool $isError = false): void
{
    startSecureSession();
    $_SESSION['flash'] = ['text' => $text, 'error' => $isError];
}

/** Returns the saved message (and removes it), or null. */
function takeFlash(): ?array
{
    startSecureSession();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Runs a SELECT with a prepared statement. $params fill the "?" marks.
 * Returns all rows, or null if the database could not be read.
 */
function registrarRows(string $sql, array $params = []): ?array
{
    try {
        $stmt = getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $ex) {
        return null;
    }
}

/** Same as registrarRows() but returns the first column of the first row (or null). */
function registrarValue(string $sql, array $params = [])
{
    try {
        $stmt = getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $ex) {
        return null;
    }
}

/** Prints one dashboard summary card. */
function registrarStatCard(string $title, $count, string $unit, string $link, string $linkText, string $icon = 'list', string $tone = '', bool $attention = false): void
{
    $value = ($count === null)
        ? '<p class="stat-main is-text">Unavailable</p>'
        : '<p class="stat-main">' . (int) $count . '</p>';
    $sub   = ($count !== null && (int) $count === 0) ? 'No records yet.' : $unit;
    ssisStatCard($title, $value, $sub, $link, $linkText, $icon, $tone, $attention && $count !== null && (int) $count > 0);
}

/** Starts a Registrar page: top bar, sidebar menu, page title, and any saved message. */
function registrarPageStart(array $user, string $active, string $title): void
{
    // Same menu items and URLs as REGISTRAR_NAV; only grouped and given icons.
    $icons = [
        'dashboard' => 'grid', 'students' => 'users', 'courses' => 'book',
        'enrollment' => 'filecheck', 'grades' => 'award',
    ];
    $groupOf = [
        'dashboard' => 'Overview', 'students' => 'Records', 'courses' => 'Records',
        'enrollment' => 'Academics', 'grades' => 'Academics',
    ];
    $subtitles = [
        'dashboard'  => 'Live counts from the university records.',
        'students'   => 'All student accounts and their status.',
        'courses'    => 'Courses and subjects offered by the university.',
        'enrollment' => 'Review enrollment requests and record decisions.',
        'grades'     => 'Encode new grades or update existing ones.',
    ];

    $groups = [];
    foreach (REGISTRAR_NAV as $key => $item) {
        $label = $groupOf[$key] ?? 'Menu';
        $groups[$label][0] = $label;
        $groups[$label][1][] = [$key, $item[0], publicUrl('registrar/' . $item[1]), $icons[$key] ?? 'grid'];
    }
    // Not built yet: shown as unavailable, exactly as before.
    $groups['Services'] = ['Services', [
        ['documents', 'Document Requests', '', 'file', 'Later', true],
    ]];

    ssisShellStart($user, [
        'title'    => $title,
        'subtitle' => $subtitles[$active] ?? '',
        'navLabel' => 'Registrar menu',
        'active'   => $active,
        'groups'   => array_values($groups),
    ]);

    $flash = takeFlash();
    if ($flash !== null) {
        registrarNotice($flash['text'], $flash['error']);
    }
}

/** Ends a Registrar page. */
function registrarPageEnd(): void
{
    ssisShellEnd();
}
