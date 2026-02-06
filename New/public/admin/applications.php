<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('admin');

$pdo = db();

if (is_post() && isset($_POST['application_id'], $_POST['status_id'])) {
    $applicationId = (int) $_POST['application_id'];
    $statusId = (int) $_POST['status_id'];
    $remarks = trim($_POST['remarks'] ?? '');

    $statusStmt = $pdo->prepare('SELECT * FROM application_statuses WHERE id = :id');
    $statusStmt->execute(['id' => $statusId]);
    $status = $statusStmt->fetch();

    $appStmt = $pdo->prepare('SELECT * FROM applications WHERE id = :id');
    $appStmt->execute(['id' => $applicationId]);
    $application = $appStmt->fetch();

    if ($status && $application) {
        $pdo->beginTransaction();
        try {
            $updateStmt = $pdo->prepare('UPDATE applications SET status_id = :status_id WHERE id = :id');
            $updateStmt->execute([
                'status_id' => $statusId,
                'id' => $applicationId,
            ]);

            $historyStmt = $pdo->prepare('INSERT INTO application_status_history (application_id, status_id, remarks, created_by) VALUES (:application_id, :status_id, :remarks, :created_by)');
            $historyStmt->execute([
                'application_id' => $applicationId,
                'status_id' => $statusId,
                'remarks' => $remarks ?: null,
                'created_by' => current_user()['id'],
            ]);

            $pdo->commit();
            flash('success', 'Application status updated.');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Failed to update status. Please try again.');
        }
    } else {
        flash('error', 'Invalid status or application selected.');
    }

    redirect('applications.php');
}

$applications = $pdo->query("
    SELECT a.*, u.full_name, u.company_name, u.email, g.name AS grant_name, s.label AS status_label, s.status_key
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN grant_programs g ON a.grant_id = g.id
    JOIN application_statuses s ON a.status_id = s.id
    ORDER BY a.submitted_at DESC
")->fetchAll();

$documents = $pdo->query("
    SELECT d.*, r.title, d.application_id
    FROM application_documents d
    JOIN grant_requirements r ON r.id = d.requirement_id
")->fetchAll();
$documentsByApplication = [];
foreach ($documents as $doc) {
    $documentsByApplication[$doc['application_id']][] = $doc;
}

$statusOptions = application_status_options();
$flashSuccess = flash('success');
$flashError = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - Admin | <?php echo sanitize(app_name()); ?></title>
    <link rel="stylesheet" href="../css/shared.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
    <style>
        .btn-dark {
            background: #3d2a5f !important;
            color: white !important;
            border: none !important;
            padding: 10px 20px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            display: inline-block !important;
        }
        .btn-dark:hover {
            background: #2a1d42 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn-dark-primary {
            background: #5a3fa3 !important;
        }
        .btn-dark-primary:hover {
            background: #4a2f93 !important;
        }
        .status-update-box {
            background: linear-gradient(135deg, #f9f7fc 0%, #ffffff 100%);
            border: 2px solid #e8d9f0;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(90, 63, 163, 0.1);
            min-width: 250px;
        }
        .status-update-box select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e8d9f0;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            margin-bottom: 10px;
            transition: border-color 0.3s ease;
        }
        .status-update-box select:focus {
            outline: none;
            border-color: #5a3fa3;
            box-shadow: 0 0 0 3px rgba(90, 63, 163, 0.1);
        }
        .status-update-box textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e8d9f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            margin-bottom: 10px;
            transition: border-color 0.3s ease;
        }
        .status-update-box textarea:focus {
            outline: none;
            border-color: #5a3fa3;
            box-shadow: 0 0 0 3px rgba(90, 63, 163, 0.1);
        }
        .status-update-box button {
            width: 100%;
            background: #5a3fa3 !important;
            color: white !important;
            border: none !important;
            padding: 12px 20px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
        }
        .status-update-box button:hover {
            background: #4a2f93 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(90, 63, 163, 0.3);
        }
        .btn-small {
            background: #5a3fa3 !important;
            color: white !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            display: inline-block !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
        }
        .btn-small:hover {
            background: #4a2f93 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(90, 63, 163, 0.3);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <button class="mobile-hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
        <div class="sidebar" id="sidebar">
            <button class="hamburger-sidebar" onclick="toggleSidebar()" aria-label="Close menu">✕</button>
            <div class="sidebar-header">
                <div class="sidebar-logo">💼</div>
                <div class="sidebar-title">Grant Hub Admin</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="menu-link">📊 Dashboard</a></li>
                <li><a href="manage-grants.php" class="menu-link">📋 Manage Grants</a></li>
                <li><a href="applications.php" class="menu-link active">📄 Applications</a></li>
                <li><a href="settings.php" class="menu-link">⚙️ Settings</a></li>
                <li><a href="<?php echo base_path('auth/logout.php'); ?>">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <div class="content">
            <div class="header">
                <h1>Review Applications</h1>
                <div class="user-info">
                    <span><?php echo sanitize(current_user()['full_name']); ?></span>
                    <a class="logout-btn" href="<?php echo base_path('auth/logout.php'); ?>">Logout</a>
                </div>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="alert success"><?php echo sanitize($flashSuccess); ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert error"><?php echo sanitize($flashError); ?></div>
            <?php endif; ?>

            <div class="applications-table">
                <table>
                    <thead>
                        <tr>
                            <th>Application</th>
                            <th>Grant</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Documents</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$applications): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;color:#a89bb8;">No applications yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo sanitize($application['company_name'] ?: $application['full_name']); ?></strong><br>
                                        <small><?php echo sanitize($application['full_name']); ?> · <?php echo sanitize($application['email']); ?></small><br>
                                        <small>Code: <?php echo sanitize($application['application_code']); ?></small>
                                    </td>
                                    <td><?php echo sanitize($application['grant_name']); ?></td>
                                    <td><?php echo $application['submitted_at'] ? date('M d, Y H:i', strtotime($application['submitted_at'])) : '—'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo sanitize($application['status_key']); ?>">
                                            <?php echo sanitize($application['status_label']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn-dark btn-dark-primary" href="application-details.php?id=<?php echo (int) $application['id']; ?>">View</a>
                                    </td>
                                    <td>
                                        <?php
                                        $docs = $documentsByApplication[$application['id']] ?? [];
                                        if (!$docs):
                                        ?>
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
                                        <div class="status-update-box">
                                            <form method="post" class="inline-form">
                                                <input type="hidden" name="application_id" value="<?php echo (int) $application['id']; ?>">
                                                <label style="display:block;margin-bottom:8px;font-weight:600;color:#5a3fa3;font-size:13px;">Update Status:</label>
                                                <select name="status_id">
                                                    <?php foreach ($statusOptions as $status): ?>
                                                        <option value="<?php echo (int) $status['id']; ?>" <?php echo (int) $status['id'] === (int) $application['status_id'] ? 'selected' : ''; ?>>
                                                            <?php echo sanitize($status['label']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label style="display:block;margin-bottom:8px;margin-top:12px;font-weight:600;color:#5a3fa3;font-size:13px;">Remarks (optional):</label>
                                                <textarea name="remarks" placeholder="Add any remarks about this status change..." rows="3"></textarea>
                                                <button type="submit">Update Status</button>
                                            </form>
                                        </div>
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

