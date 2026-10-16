<?php
/**
 * public/student/document-requests.php
 * Stage 3: placeholder only. The Document Request workflow comes in a later stage.
 */

require_once __DIR__ . '/../../config/student.php';

$user = requireRole('Student');

studentPageStart($user, 'documents', 'Document Requests');
?>
    <section class="panel placeholder-card">
        <span class="icon-tile"><?= ssisIcon('file') ?></span>
        <div>
            <h2>Document Requests <span class="badge badge-soon">Coming later</span></h2>
            <?php studentNotice('Document Request Module — Coming in a later stage.'); ?>
            <p>This feature is still being built. Nothing can be requested from this page yet.</p>
        </div>
    </section>
<?php
studentPageEnd();
