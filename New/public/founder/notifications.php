<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('founder');

$user = current_user();
$pdo = db();

$notifications = $pdo->prepare('
    SELECT h.*, s.label AS status_label, a.application_code, g.name AS grant_name
    FROM application_status_history h
    JOIN application_statuses s ON h.status_id = s.id
    JOIN applications a ON h.application_id = a.id
    JOIN grant_programs g ON a.grant_id = g.id
    WHERE a.user_id = :user_id
    ORDER BY h.created_at DESC
');
$notifications->execute(['user_id' => $user['id']]);
$items = $notifications->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Founder | <?php echo sanitize(app_name()); ?></title>
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
                <li><a href="my-applications.php" class="menu-link">📄 My Applications</a></li>
                <li><a href="notifications.php" class="menu-link active">🔔 Notifications</a></li>
                <li><a href="profile.php" class="menu-link">👤 Profile</a></li>
                <li><a href="<?php echo base_path('auth/logout.php'); ?>">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <div class="content">
            <div class="header">
                <h1>Notifications</h1>
                <div class="user-info">
                    <span><?php echo sanitize($user['full_name']); ?></span>
                    <a class="logout-btn" href="<?php echo base_path('auth/logout.php'); ?>">Logout</a>
                </div>
            </div>

            <div class="notifications-list">
                <?php if (!$items): ?>
                    <div class="no-data">No notifications yet.</div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="notification-card">
                            <div class="notification-title">
                                <?php echo sanitize($item['status_label']); ?> · <?php echo sanitize($item['grant_name']); ?>
                            </div>
                            <div class="notification-body">
                                Application <?php echo sanitize($item['application_code']); ?> updated.
                                <?php if ($item['remarks']): ?>
                                    <div style="color:#7d6b8f;"><?php echo sanitize($item['remarks']); ?></div>
                                <?php endif; ?>
                            </div>
                            <small><?php echo date('M d, Y g:i A', strtotime($item['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

