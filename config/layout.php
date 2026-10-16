<?php
/**
 * config/layout.php
 * -------------------------------------------------------------
 * Shared HTML/presentation helpers: Access Denied page, the
 * placeholder page (Admin / Department), and the common
 * authenticated page shell (top bar, sidebar, page header),
 * inline SVG icons, status badges and notice boxes.
 * Presentation only: no queries and no business rules live here.
 * These functions use helpers from config/auth.php.
 * -------------------------------------------------------------
 */

/** Inline SVG icon (decorative). Names are fixed in the list below. */
function ssisIcon(string $name, string $class = ''): string
{
    static $paths = [
        'cap'     => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/><path d="M22 10v6"/>',
        'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'users'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.3c2.2.7 3.5 2.6 3.5 5.7"/>',
        'book'    => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M20 17v4H6.5A2.5 2.5 0 0 1 4 18.5"/>',
        'award'   => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14.5-1.5 7 5-3 5 3-1.5-7"/>',
        'filecheck' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="m9 14 2 2 4-4"/>',
        'shield'  => '<path d="M12 3 4 6v6c0 4.5 3.2 8 8 9 4.8-1 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'receipt' => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
        'file'    => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'wallet'  => '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2z"/><circle cx="16.5" cy="14.5" r="1"/>',
        'list'    => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'   => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'menu'    => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'alert'   => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5h.01"/>',
        'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5h.01"/>',
        'inbox'   => '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5.5 5h13L21 13v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-6z"/>',
        'eye'     => '<path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'eyeoff'  => '<path d="M17.9 17.9A10.7 10.7 0 0 1 12 19C5.5 19 1.5 12 1.5 12a18 18 0 0 1 5-5.8M9.9 5.2A9.8 9.8 0 0 1 12 5c6.5 0 10.5 7 10.5 7a18 18 0 0 1-2.1 3.1"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2M2 2l20 20"/>',
        'tool'    => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
        'lock'    => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'print'   => '<path d="M6 9V3h12v6"/><path d="M6 18H4a1 1 0 0 1-1-1v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2"/><rect x="6" y="14" width="12" height="7" rx="1"/>',
    ];

    $inner = $paths[$name] ?? $paths['info'];
    return '<svg class="icon' . ($class !== '' ? ' ' . $class : '')
         . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}

/**
 * Pill badge for a status value that already exists in the database
 * (Pending, Approved, Rejected, Active, Inactive, ...). Unknown values get a
 * neutral badge. Empty values show a dash. Output is HTML-safe.
 */
function ssisBadge($value): string
{
    if ($value === null || $value === '') {
        return '&mdash;';
    }
    $tones = [
        'Approved'   => 'success',
        'Active'     => 'success',
        'Paid'       => 'success',
        'Ready'      => 'success',
        'Released'   => 'success',
        'Pending'    => 'warn',
        'Unpaid'     => 'warn',
        'Submitted'  => 'info',
        'Processing' => 'info',
        'Rejected'   => 'danger',
        'Inactive'   => 'neutral',
    ];
    $tone = $tones[(string) $value] ?? 'neutral';
    return '<span class="badge badge-' . $tone . '">' . e($value) . '</span>';
}

/** Initials for the avatar circle (presentation only). */
function ssisInitials($name): string
{
    $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return '?';
    }
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return e(mb_strtoupper($first . $last));
}

/** Prints an empty-state box (used when a table has no rows). */
function ssisEmptyState(string $text): void
{
    echo '<div class="empty-state" role="status">'
       . '<span class="icon-tile">' . ssisIcon('inbox') . '</span>'
       . '<p>' . e($text) . '</p></div>';
}

/**
 * Notice box. $isError = true makes it red.
 * A notice that only says there is nothing to show ("No records found.")
 * is drawn as an empty state, and a plain success message is drawn green.
 * The text itself is never changed.
 */
function ssisNotice(string $text, bool $isError = false): void
{
    if (!$isError && preg_match('/^No .*(found|yet|match|encoded)/i', $text)) {
        ssisEmptyState($text);
        return;
    }

    if ($isError) {
        $class = 'message';
        $icon  = 'alert';
        $role  = 'alert';
    } elseif (preg_match('/\b(successfully|was approved|was rejected|encoded for|updated for)\b/i', $text)) {
        $class = 'message ok-box';
        $icon  = 'check';
        $role  = 'status';
    } else {
        $class = 'message info-box';
        $icon  = 'info';
        $role  = 'status';
    }
    echo '<div class="' . $class . '" role="' . $role . '">' . ssisIcon($icon) . '<span>' . e($text) . '</span></div>';
}

/** One dashboard summary card (shared by Registrar and Cashier dashboards). */
function ssisStatCard(string $title, string $valueHtml, string $subText, string $link, string $linkText, string $icon, string $tone = '', bool $attention = false): void
{
    ?>
        <section class="stat-card<?= $tone !== '' ? ' tone-' . e($tone) : '' ?><?= $attention ? ' is-attention' : '' ?>">
            <div class="stat-top">
                <span class="icon-tile"><?= ssisIcon($icon) ?></span>
                <h2><?= e($title) ?></h2>
            </div>
            <?= $valueHtml ?>
            <p class="stat-sub"><?= e($subText) ?></p>
            <a class="stat-link" href="<?= e($link) ?>"><?= e($linkText) ?> <?= ssisIcon('arrow') ?></a>
        </section>
<?php
}

/** Prints the document head and opens <body>. */
function ssisHead(string $title, string $bodyClass = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B1F16">
    <title><?= e($title) ?> - CuyoTech SSIS</title>
    <script>document.documentElement.classList.add('js');</script>
    <link rel="stylesheet" href="<?= e(assetUrl('css/style.css')) ?>">
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
<?php
}

/** Short label shown under the university name in the top bar. */
function ssisModuleLabel(string $role): string
{
    $labels = [
        'Student'          => 'Student Portal',
        'Registrar'        => 'Registrar Module',
        'Cashier'          => 'Cashier Module',
        'Admin'            => 'Admin Module',
        'Department Staff' => 'Department Module',
    ];
    return 'SSIS · ' . ($labels[$role] ?? 'Student Services');
}

/**
 * Starts an authenticated page: top bar, sidebar, page title.
 *
 * $opts keys:
 *   title      page title (h1 and <title>)
 *   subtitle   optional one-line description
 *   navLabel   accessible name of the sidebar
 *   groups     [ ['Group label', [ ['key', 'Label', 'url', 'icon', 'tag'(optional), 'disabled'(optional)], ... ]], ... ]
 *   active     key of the current page ('' for none)
 *   homeUrl    link target of the brand
 */
function ssisShellStart(array $user, array $opts): void
{
    $title    = (string) ($opts['title'] ?? '');
    $subtitle = (string) ($opts['subtitle'] ?? '');
    $active   = (string) ($opts['active'] ?? '');
    $groups   = $opts['groups'] ?? [];

    ssisHead($title);
    ?>
<a class="skip-link" href="#main">Skip to main content</a>
<header class="app-topbar">
    <div class="topbar-left">
        <button type="button" class="nav-toggle" id="navToggle" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation menu">
            <?= ssisIcon('menu') ?>
        </button>
        <a class="brand" href="<?= e($opts['homeUrl'] ?? roleHomeUrl($user['role'])) ?>">
            <span class="brand-mark"><?= ssisIcon('cap') ?></span>
            <span class="brand-text">
                <span class="brand-name">CuyoTech University</span>
                <span class="brand-sub"><?= e(ssisModuleLabel($user['role'])) ?></span>
            </span>
        </a>
    </div>
    <div class="topbar-right">
        <span class="user-name"><?= e($user['full_name']) ?></span>
        <span class="role-badge"><?= e($user['role']) ?></span>
        <form method="post" action="<?= e(publicUrl('logout.php')) ?>">
            <?= csrfField() ?>
            <button type="submit" class="btn-logout"><?= ssisIcon('logout') ?><span>Logout</span></button>
        </form>
    </div>
</header>

<div class="app-body" id="appShell">
    <aside class="app-sidebar" id="appSidebar">
        <div class="nav-user">
            <span class="avatar" aria-hidden="true"><?= ssisInitials($user['full_name']) ?></span>
            <span class="nav-user-text"><strong><?= e($user['full_name']) ?></strong><span><?= e($user['role']) ?></span></span>
        </div>
        <nav aria-label="<?= e($opts['navLabel'] ?? 'Main menu') ?>">
<?php foreach ($groups as $group): ?>
            <p class="nav-group-label"><?= e($group[0]) ?></p>
            <ul class="nav-list">
<?php foreach ($group[1] as $item): ?>
<?php if (!empty($item[5])): ?>
                <li><span class="nav-link is-disabled" aria-disabled="true"><?= ssisIcon($item[3]) ?><?= e($item[1]) ?><?php if (!empty($item[4])): ?><span class="nav-tag"><?= e($item[4]) ?></span><?php endif; ?></span></li>
<?php else: ?>
                <li><a class="nav-link<?= $item[0] === $active ? ' active' : '' ?>" href="<?= e($item[2]) ?>"<?= $item[0] === $active ? ' aria-current="page"' : '' ?>><?= ssisIcon($item[3]) ?><?= e($item[1]) ?><?php if (!empty($item[4])): ?><span class="nav-tag"><?= e($item[4]) ?></span><?php endif; ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
            </ul>
<?php endforeach; ?>
        </nav>
    </aside>
    <div class="nav-backdrop" id="navBackdrop"></div>

    <main class="app-main" id="main">
        <div class="page-head">
            <h1><?= e($title) ?></h1>
<?php if ($subtitle !== ''): ?>
            <p class="page-sub"><?= e($subtitle) ?></p>
<?php endif; ?>
        </div>
<?php
}

/** Ends an authenticated page. */
function ssisShellEnd(): void
{
    ?>
    </main>
</div>
<script src="<?= e(assetUrl('js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

/** "Access Denied" page (HTTP 403) with a link back to the user's own dashboard. */
function renderAccessDenied(string $role): void
{
    $homeUrl = roleHomeUrl($role);
    ssisHead('Access Denied', 'error-body');
    ?>
<main class="error-page">
    <div class="panel error-card">
        <span class="icon-tile"><?= ssisIcon('lock') ?></span>
        <h1>Access Denied</h1>
        <div class="message" role="alert"><?= ssisIcon('alert') ?><span>You do not have permission to open this page.</span></div>
        <a class="btn" href="<?= e($homeUrl) ?>">Back to my dashboard</a>
    </div>
</main>
</body>
</html>
<?php
}

/** Placeholder page used by the Admin and Department modules (not built yet). */
function renderPlaceholderPage(array $user, string $moduleName): void
{
    ssisShellStart($user, [
        'title'    => $moduleName,
        'subtitle' => 'This module has not been built yet.',
        'navLabel' => $moduleName . ' menu',
        'active'   => 'home',
        'groups'   => [
            ['Overview', [
                ['home', $moduleName, roleHomeUrl($user['role']), 'grid'],
            ]],
        ],
    ]);
    ?>
        <section class="panel placeholder-card">
            <span class="icon-tile"><?= ssisIcon('tool') ?></span>
            <div>
                <h2><?= e($moduleName) ?> <span class="badge badge-soon">In development</span></h2>
                <p><?= e($moduleName) ?> &mdash; placeholder page. Its features will be added in a later stage.</p>
                <dl class="detail-list">
                    <div class="detail-row"><dt>Name</dt><dd><?= e($user['full_name']) ?></dd></div>
                    <div class="detail-row"><dt>Role</dt><dd><?= e($user['role']) ?></dd></div>
                </dl>
            </div>
        </section>
<?php
    ssisShellEnd();
}
