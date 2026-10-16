<?php
/**
 * public/department/index.php
 * Stage 2 placeholder for the Department Module. Only the role "Department Staff" may open it.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = requireRole('Department Staff');
renderPlaceholderPage($user, 'Department Module');
