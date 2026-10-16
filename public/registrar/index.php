<?php
/**
 * public/registrar/index.php
 * Stage 4: Registrar Dashboard (summary counts from the database).
 */

require_once __DIR__ . '/../../config/registrar.php';

$user = requireRole('Registrar');

$students = registrarValue('SELECT COUNT(*) FROM students');
$courses  = registrarValue('SELECT COUNT(*) FROM courses');
$pending  = registrarValue("SELECT COUNT(*) FROM enrollments WHERE status = 'Pending'");
$approved = registrarValue("SELECT COUNT(*) FROM enrollments WHERE status = 'Approved'");
$grades   = registrarValue('SELECT COUNT(*) FROM grades');

registrarPageStart($user, 'dashboard', 'Registrar Dashboard');
?>
    <section class="welcome">
        <div>
            <p class="welcome-title">Welcome, <?= e($user['full_name']) ?>.</p>
            <p class="welcome-sub">Here are the current counts from the university records.</p>
        </div>
        <span class="welcome-role"><?= e($user['role']) ?></span>
    </section>

    <div class="stat-grid">
<?php
registrarStatCard('Total Students',               $students, 'student records',        'students.php',                  'View students',  'users');
registrarStatCard('Total Courses',                $courses,  'course records',         'courses.php',                   'View courses',   'book',      'info');
registrarStatCard('Pending Enrollment Requests',  $pending,  'waiting for a decision', 'enrollment.php?status=Pending', 'Review requests', 'clock',     'warn', true);
registrarStatCard('Approved Enrollment Records',  $approved, 'approved enrollments',   'enrollment.php?status=Approved','View approved',  'filecheck');
registrarStatCard('Total Grade Records',          $grades,   'encoded grades',         'grades.php',                    'View grades',    'award',     'gold');
?>
    </div>
<?php
registrarPageEnd();
