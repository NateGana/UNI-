<?php
/**
 * public/logout.php
 * -------------------------------------------------------------
 * Stage 2: logs the user out. Only a POST form with a valid CSRF
 * token can log out; opening this page directly does nothing.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/../config/auth.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    logoutUser();                                   // clear + destroy the session
    redirect(publicUrl('login.php?msg=loggedout'));
}

// Opened directly (or bad token): nobody is logged out, just send them on.
if (isLoggedIn()) {
    redirect(roleHomeUrl($_SESSION['role'] ?? ''));
}
redirect(publicUrl('login.php'));
