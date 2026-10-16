<?php
/**
 * config/auth.php
 * -------------------------------------------------------------
 * Stage 2: login, session, and role-protection helpers.
 * Every protected page loads this file and calls requireRole().
 * Requires PHP 7.3 or newer (XAMPP's PHP 8 is fine).
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/layout.php';

// Where each role goes after login (paths inside the public/ folder).
// The keys are the EXACT values stored in users.role.
const ROLE_HOME = [
    'Admin'            => 'admin/index.php',
    'Student'          => 'student/index.php',
    'Registrar'        => 'registrar/index.php',
    'Cashier'          => 'cashier/index.php',
    'Department Staff' => 'department/index.php',
];

// Fixed messages the login page can show for ?msg=... (no user text is ever printed).
const LOGIN_NOTICES = [
    'loggedout'      => 'You have been logged out.',
    'login_required' => 'Please log in to continue.',
    'inactive'       => 'Your account is deactivated. Please contact the system administrator.',
];

/** Prints text safely inside HTML. */
function e($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/** Starts the PHP session once, with safer cookie settings. */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('SSIS_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,        // cookie ends when the browser closes
        'path'     => '/',
        'httponly' => true,     // JavaScript cannot read the cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** URL of the project folder, e.g. /ssis (worked out from the current page). */
function appBaseUrl(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos    = strpos($script, '/public/');
    return $pos === false ? '' : substr($script, 0, $pos);
}

/** URL of a page inside the public/ folder, e.g. publicUrl('login.php'). */
function publicUrl(string $path = ''): string
{
    return appBaseUrl() . '/public/' . ltrim($path, '/');
}

/** URL of a file inside the assets/ folder, e.g. assetUrl('css/style.css'). */
function assetUrl(string $path): string
{
    return appBaseUrl() . '/assets/' . ltrim($path, '/');
}

/** URL of the home page for a role (login page if the role is unknown). */
function roleHomeUrl(string $role): string
{
    return publicUrl(ROLE_HOME[$role] ?? 'login.php');
}

/** Sends the browser to another page and stops this script. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Tells the browser not to keep copies of protected pages. */
function noCacheHeaders(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/** True if this browser has a logged-in session. */
function isLoggedIn(): bool
{
    startSecureSession();
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/** Saves the logged-in user in the session (never the password or hash). */
function loginUser(array $user): void
{
    startSecureSession();
    session_regenerate_id(true);   // new session ID after login
    $_SESSION = [];
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id']   = (int) $user['user_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = (string) ($user['full_name'] ?? '');
    $_SESSION['role']      = $user['role'];
}

/** Ends the session completely. */
function logoutUser(): void
{
    startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Page guard: the user must be logged in and still Active.
 * Returns the user's details (user_id, username, full_name, role).
 */
function requireLogin(): array
{
    startSecureSession();
    noCacheHeaders();

    if (!isLoggedIn()) {
        redirect(publicUrl('login.php?msg=login_required'));
    }

    // Check the account again, in case it was deactivated after login.
    $stmt = getConnection()->prepare(
        'SELECT username, full_name, role, account_status FROM users WHERE user_id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if (!$row) {
        logoutUser();
        redirect(publicUrl('login.php?msg=login_required'));
    }
    if ($row['account_status'] !== 'Active') {
        logoutUser();
        redirect(publicUrl('login.php?msg=inactive'));
    }

    // Keep the session in step with the database.
    $_SESSION['username']  = $row['username'];
    $_SESSION['full_name'] = (string) ($row['full_name'] ?? '');
    $_SESSION['role']      = $row['role'];

    return [
        'user_id'   => (int) $_SESSION['user_id'],
        'username'  => $row['username'],
        'full_name' => $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : $row['username'],
        'role'      => $row['role'],
    ];
}

/**
 * Page guard: logged in AND has the given role (exact users.role value,
 * for example 'Student' or 'Department Staff').
 * Any other role gets an "Access Denied" page.
 */
function requireRole(string $role): array
{
    $user = requireLogin();

    if ($user['role'] !== $role) {
        http_response_code(403);
        renderAccessDenied($user['role']);
        exit;
    }
    return $user;
}

/** Returns the CSRF token for this session (creates it if needed). */
function csrfToken(): string
{
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden form field that carries the CSRF token. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/** True if the submitted form carries the correct CSRF token. */
function verifyCsrf(): bool
{
    startSecureSession();
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}
