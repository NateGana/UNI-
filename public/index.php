<?php
/**
 * public/index.php
 * -------------------------------------------------------------
 * Stage 2: entry point. Sends visitors to the login page, or to
 * their own page if they are already logged in.
 * (The Stage 1 database test page is now public/db-test.php.)
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/../config/auth.php';

startSecureSession();

if (isLoggedIn() && isset(ROLE_HOME[$_SESSION['role'] ?? ''])) {
    redirect(roleHomeUrl($_SESSION['role']));
}
redirect(publicUrl('login.php'));
