<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('founder');

$user = current_user();
$pdo = db();

$availableGrants = (int) $pdo->query("SELECT COUNT(*) FROM grant_programs WHERE status = 'published'")->fetchColumn();

$applicationsStats = $pdo->prepare('
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status_id = :approved THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN status_id = :approved THEN requested_amount ELSE 0 END) AS total_funding
    FROM applications
    WHERE user_id = :user_id
');
$applicationsStats->execute([
    'approved' => application_status_id('approved'),
    'user_id' => $user['id'],
]);
$stats = $applicationsStats->fetch() ?: ['total' => 0, 'approved' => 0, 'total_funding' => 0];

$recentUpdates = $pdo->prepare('
    SELECT a.application_code, g.name AS grant_name, s.label AS status_label, h.created_at
    FROM application_status_history h
    JOIN applications a ON h.application_id = a.id
    JOIN grant_programs g ON a.grant_id = g.id
    JOIN application_statuses s ON h.status_id = s.id
    WHERE a.user_id = :user_id
    ORDER BY h.created_at DESC
    LIMIT 5
');
$recentUpdates->execute(['user_id' => $user['id']]);
$recent = $recentUpdates->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Founder | <?php echo sanitize(app_name()); ?></title>
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
                <li><a href="dashboard.php" class="menu-link active">📊 Dashboard</a></li>
                <li><a href="available-grants.php" class="menu-link">🎯 Available Grants</a></li>
                <li><a href="my-applications.php" class="menu-link">📄 My Applications</a></li>
                <li><a href="notifications.php" class="menu-link">🔔 Notifications</a></li>
                <li><a href="profile.php" class="menu-link">👤 Profile</a></li>
                <li><a href="<?php echo base_path('auth/logout.php'); ?>">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <div class="content">
            <div class="header">
                <h1>Welcome back, <?php echo sanitize($user['full_name']); ?></h1>
                <div class="user-info">
                    <span><?php echo sanitize($user['email']); ?></span>
                    <a class="logout-btn" href="<?php echo base_path('auth/logout.php'); ?>">Logout</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $availableGrants; ?></div>
                    <div class="stat-label">Available Grants</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo (int) $stats['total']; ?></div>
                    <div class="stat-label">Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo (int) $stats['approved']; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₱<?php echo number_format($stats['total_funding'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Funding</div>
                </div>
            </div>

            <div class="card" style="margin-top:30px;">
                <h2 style="color:#5a3fa3;">Latest Updates</h2>
                <?php if (!$recent): ?>
                    <p style="color:#a89bb8;">No updates yet. Submit an application to get started.</p>
                <?php else: ?>
                    <ul class="timeline">
                        <?php foreach ($recent as $item): ?>
                            <li>
                                <strong><?php echo sanitize($item['grant_name']); ?></strong>
                                <div>Application <?php echo sanitize($item['application_code']); ?> · <?php echo sanitize($item['status_label']); ?></div>
                                <small><?php echo date('M d, Y g:i A', strtotime($item['created_at'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
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
