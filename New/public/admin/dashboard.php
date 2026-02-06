<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('admin');

$pdo = db();
$totalActiveGrants = (int) $pdo->query("SELECT COUNT(*) FROM grant_programs WHERE status = 'published'")->fetchColumn();
$totalApplications = (int) $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();

$submittedId = application_status_id('submitted');
$underReviewId = application_status_id('under_review');
$pendingCountStmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE status_id IN (:submitted, :under_review)');
$pendingCountStmt->execute([
    'submitted' => $submittedId,
    'under_review' => $underReviewId,
]);
$pendingCount = (int) $pendingCountStmt->fetchColumn();

$approvedId = application_status_id('approved');
$approvedCountStmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE status_id = :approved');
$approvedCountStmt->execute(['approved' => $approvedId]);
$approvedCount = (int) $approvedCountStmt->fetchColumn();

$recentApplications = $pdo->query("
    SELECT a.application_code, g.name AS grant_name, u.full_name, s.label AS status_label, a.submitted_at
    FROM applications a
    JOIN grant_programs g ON a.grant_id = g.id
    JOIN users u ON a.user_id = u.id
    JOIN application_statuses s ON a.status_id = s.id
    ORDER BY a.updated_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin | <?php echo sanitize(app_name()); ?></title>
    <link rel="stylesheet" href="../css/shared.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>
<body>
    <div class="main-container">
        <div class="content">
            <div class="header">
                <h1>Dashboard Overview</h1>
                <div class="user-info">
                    <span><?php echo sanitize(current_user()['full_name']); ?></span>
                    <a class="logout-btn" href="<?php echo base_path('auth/logout.php'); ?>">Logout</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalActiveGrants; ?></div>
                    <div class="stat-label">Active Grants</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalApplications; ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $pendingCount; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $approvedCount; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>

            <div class="card" style="margin-top:30px;">
                <h2 style="color:#5a3fa3; margin-bottom:15px;">Latest Activity</h2>
                <?php if (!$recentApplications): ?>
                    <p style="color:#a89bb8;">No applications yet.</p>
                <?php else: ?>
                    <div class="applications-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Application Code</th>
                                    <th>Applicant</th>
                                    <th>Grant</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentApplications as $application): ?>
                                    <tr>
                                        <td><?php echo sanitize($application['application_code']); ?></td>
                                        <td><?php echo sanitize($application['full_name']); ?></td>
                                        <td><?php echo sanitize($application['grant_name']); ?></td>
                                        <td><?php echo sanitize($application['status_label']); ?></td>
                                        <td><?php echo $application['submitted_at'] ? date('M d, Y', strtotime($application['submitted_at'])) : '—'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <button class="mobile-hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
        <div class="sidebar" id="sidebar" tabindex="-1">
            <button class="hamburger-sidebar" onclick="toggleSidebar()" aria-label="Close menu">✕</button>
            <div class="sidebar-header">
                <div class="sidebar-logo">💼</div>
                <div class="sidebar-title">Grant Hub Admin</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="menu-link">📊 Dashboard</a></li>
                <li><a href="manage-grants.php" class="menu-link">📋 Manage Grants</a></li>
                <li><a href="applications.php" class="menu-link">📄 Applications</a></li>
                <li><a href="settings.php" class="menu-link">⚙️ Settings</a></li>
                <li><a href="<?php echo base_path('auth/logout.php'); ?>">🚪 Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    </div>
    <script>
        // Sidebar toggle logic
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) {
                sidebar.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    sidebar.focus();
                }
            }
            if (overlay) {
                overlay.classList.toggle('active');
            }
        }
        // Highlight active menu item
        function setActiveMenu() {
            const links = document.querySelectorAll('.sidebar-menu a');
            const current = window.location.pathname.split('/').pop();
            links.forEach(link => {
                if (link.getAttribute('href') === current) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }
        setActiveMenu();
        // Keyboard accessibility: close sidebar with Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            }
        });
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
                if (!sidebar.contains(event.target) && overlay && overlay.classList.contains('active')) {
                    toggleSidebar();
                }
            }
        });
    </script>
</body>
</html>
