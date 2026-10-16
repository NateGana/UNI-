<?php
/**
 * public/login.php
 * -------------------------------------------------------------
 * Stage 2: the SSIS login page and login process.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/../config/auth.php';

startSecureSession();
noCacheHeaders();

// Already logged in? Go straight to the user's own page.
if (isLoggedIn() && isset(ROLE_HOME[$_SESSION['role'] ?? ''])) {
    redirect(roleHomeUrl($_SESSION['role']));
}

$username      = '';
$fieldErrors   = ['username' => '', 'password' => ''];
$formError     = '';
$notice        = '';
$noticeIsError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        $formError = 'Your session expired. Please refresh the page and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // 1. Both fields must be filled in.
        if ($username === '') {
            $fieldErrors['username'] = 'Please enter your username.';
        }
        if ($password === '') {
            $fieldErrors['password'] = 'Please enter your password.';
        }

        if ($fieldErrors['username'] === '' && $fieldErrors['password'] === '') {
            try {
                // 2. Look up the username (prepared statement).
                $stmt = getConnection()->prepare(
                    'SELECT user_id, username, password_hash, full_name, role, account_status
                     FROM users WHERE username = ?'
                );
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($password, $user['password_hash'])) {
                    // Same message for "no such user" and "wrong password".
                    $formError = 'Invalid username or password.';
                } elseif ($user['account_status'] !== 'Active') {
                    // Shown only after the password was correct.
                    $formError = 'Your account is deactivated. Please contact the system administrator.';
                } elseif (!isset(ROLE_HOME[$user['role']])) {
                    $formError = 'This account cannot be used to log in. Please contact the system administrator.';
                } else {
                    // 3. Success: new session, then go to the role's page.
                    loginUser($user);
                    redirect(roleHomeUrl($user['role']));
                }
            } catch (PDOException $ex) {
                $formError = 'The system is temporarily unavailable. Please try again later.';
            }
        }
    }
} else {
    // Messages such as "You have been logged out." (fixed text only).
    $msg = $_GET['msg'] ?? '';
    if (is_string($msg) && isset(LOGIN_NOTICES[$msg])) {
        $notice        = LOGIN_NOTICES[$msg];
        $noticeIsError = ($msg === 'inactive');
    }
}
?>
<?php ssisHead('Login', 'auth-page'); ?>
<main class="auth-shell">
    <section class="auth-intro">
        <div class="brand">
            <span class="brand-mark"><?= ssisIcon('cap') ?></span>
            <span class="brand-text">
                <span class="brand-name">CuyoTech University</span>
                <span class="brand-sub">Student Services Information System</span>
            </span>
        </div>
        <h2>Student services, all in one place.</h2>
        <p>The SSIS gives students and university staff secure access to enrollment, grades, clearance and payment records.</p>
        <ul class="auth-points">
            <li><span class="icon-tile"><?= ssisIcon('award') ?></span>Subjects, grades and enrollment status</li>
            <li><span class="icon-tile"><?= ssisIcon('shield') ?></span>Department clearance and remarks</li>
            <li><span class="icon-tile"><?= ssisIcon('receipt') ?></span>Payments and official receipts</li>
        </ul>
    </section>

    <section class="auth-card" aria-labelledby="login-title">
        <h1 id="login-title">Welcome back</h1>
        <p class="auth-lead">Log in with your CuyoTech SSIS account to continue.</p>

        <?php if ($formError !== ''): ?>
            <div class="message" role="alert"><?= ssisIcon('alert') ?><span><?= e($formError) ?></span></div>
        <?php elseif ($notice !== ''): ?>
            <div class="message <?= $noticeIsError ? '' : 'info-box' ?>" role="<?= $noticeIsError ? 'alert' : 'status' ?>"><?= ssisIcon($noticeIsError ? 'alert' : 'info') ?><span><?= e($notice) ?></span></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username"
                       value="<?= e($username) ?>" autocomplete="username" autofocus>
                <?php if ($fieldErrors['username'] !== ''): ?>
                    <p class="field-error"><?= e($fieldErrors['username']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password" autocomplete="current-password">
                    <button type="button" class="pw-toggle" data-toggle-password="password"
                            aria-label="Show password" aria-pressed="false">
                        <?= ssisIcon('eye', 'eye-on') ?><?= ssisIcon('eyeoff', 'eye-off') ?>
                    </button>
                </div>
                <?php if ($fieldErrors['password'] !== ''): ?>
                    <p class="field-error"><?= e($fieldErrors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-block">Login</button>
        </form>

        <p class="help-text">Forgot your password? Contact the system administrator.</p>
    </section>
</main>
<script src="<?= e(assetUrl('js/app.js')) ?>" defer></script>
</body>
</html>
