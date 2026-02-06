<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('founder');

$user = current_user();
$pdo = db();

$applications = $pdo->prepare('
    SELECT a.*, g.name AS grant_name, s.label AS status_label, s.status_key
    FROM applications a
    JOIN grant_programs g ON a.grant_id = g.id
    JOIN application_statuses s ON a.status_id = s.id
    WHERE a.user_id = :user_id
    ORDER BY a.submitted_at DESC
');
$applications->execute(['user_id' => $user['id']]);
$apps = $applications->fetchAll();

$documents = $pdo->prepare('
    SELECT d.*, r.title, d.application_id
    FROM application_documents d
    JOIN grant_requirements r ON r.id = d.requirement_id
    WHERE d.application_id IN (
        SELECT id FROM applications WHERE user_id = :user_id
    )
');
$documents->execute(['user_id' => $user['id']]);
$docsByApp = [];
foreach ($documents as $doc) {
    $docsByApp[$doc['application_id']][] = $doc;
}

$historyStmt = $pdo->prepare('
    SELECT h.*, s.label AS status_label, h.application_id
    FROM application_status_history h
    JOIN application_statuses s ON h.status_id = s.id
    WHERE h.application_id IN (
        SELECT id FROM applications WHERE user_id = :user_id
    )
    ORDER BY h.created_at DESC
');
$historyStmt->execute(['user_id' => $user['id']]);
$historyByApp = [];
foreach ($historyStmt as $row) {
    $historyByApp[$row['application_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Founder | <?php echo sanitize(app_name()); ?></title>
    <link rel="stylesheet" href="../css/shared.css">
    <link rel="stylesheet" href="../css/founder-styles.css">
</head>
<body>
    <div class="main-container">
        <button class="mobile-hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
        <div class="sidebar" id="sidebar">
            <button class="hamburger-sidebar" onclick="toggleSidebar()" aria-label="Close menu">✕</button>
            <div class="sidebar-header">
                <div class="sidebar-logo">💼</div>
                <div class="sidebar-title">Grant Hub Founder</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="menu-link">📊 Dashboard</a></li>
                <li><a href="available-grants.php" class="menu-link">🎯 Available Grants</a></li>
                <li><a href="my-applications.php" class="menu-link active">📄 My Applications</a></li>
                <li><a href="notifications.php" class="menu-link">🔔 Notifications</a></li>
                <li><a href="profile.php" class="menu-link">👤 Profile</a></li>
                <li><a href="<?php echo base_path('auth/logout.php'); ?>">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <div class="content">
            <div class="header">
                <h1>My Applications</h1>
                <div class="user-info">
                    <span><?php echo sanitize($user['full_name']); ?></span>
                    <a class="logout-btn" href="<?php echo base_path('auth/logout.php'); ?>">Logout</a>
                </div>
            </div>

            <div class="applications-table">
                <table>
                    <thead>
                        <tr>
                            <th>Grant</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Documents</th>
                            <th>History</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$apps): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;color:#a89bb8;">No applications yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($apps as $application): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo sanitize($application['grant_name']); ?></strong><br>
                                        <small>Code: <?php echo sanitize($application['application_code']); ?></small>
                                    </td>
                                    <td><?php echo $application['submitted_at'] ? date('M d, Y H:i', strtotime($application['submitted_at'])) : 'Draft'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo sanitize($application['status_key']); ?>">
                                            <?php echo sanitize($application['status_label']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $docs = $docsByApp[$application['id']] ?? []; ?>
                                        <?php if (!$docs): ?>
                                            <small>No files</small>
                                        <?php else: ?>
                                            <ul style="list-style:none;padding-left:0;">
                                                <?php foreach ($docs as $doc): ?>
                                                    <li>
                                                        <a href="<?php echo base_path('uploads/' . sanitize($doc['stored_filename'])); ?>" target="_blank">
                                                            <?php echo sanitize($doc['title']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $history = $historyByApp[$application['id']] ?? []; ?>
                                        <?php if (!$history): ?>
                                            <small>No updates yet</small>
                                        <?php else: ?>
                                            <ul style="list-style:none;padding-left:0;">
                                                <?php foreach ($history as $entry): ?>
                                                    <li>
                                                        <strong><?php echo sanitize($entry['status_label']); ?></strong>
                                                        <small><?php echo date('M d, Y g:i A', strtotime($entry['created_at'])); ?></small>
                                                        <?php if ($entry['remarks']): ?>
                                                            <div style="color:#7d6b8f;"><?php echo sanitize($entry['remarks']); ?></div>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        // Hamburger menu toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
            if (overlay) {
                overlay.classList.toggle('active');
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.querySelector('.hamburger-sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
                if (!sidebar.contains(event.target) && 
                    hamburger && !hamburger.contains(event.target) && 
                    overlay && overlay.classList.contains('active')) {
                    toggleSidebar();
                }
            }
        });
    </script>
</body>
</html>