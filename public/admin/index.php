<?php
/**
 * public/admin/index.php
 * Stage 2 placeholder for the Admin Module. Only the role "Admin" may open it.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = requireRole('Admin');
renderPlaceholderPage($user, 'Admin Module');
