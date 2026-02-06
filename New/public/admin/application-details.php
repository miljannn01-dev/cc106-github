<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('admin');

$pdo = db();
$applicationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$appStmt = $pdo->prepare("
    SELECT a.*, u.full_name, u.company_name, u.email, g.name AS grant_name, g.slug AS grant_slug,
           s.label AS status_label, s.status_key
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN grant_programs g ON a.grant_id = g.id
    JOIN application_statuses s ON a.status_id = s.id
    WHERE a.id = :id
");
$appStmt->execute(['id' => $applicationId]);
$application = $appStmt->fetch();

if (!$application) {
    flash('error', 'Application not found.');
    redirect('applications.php');
}

$dostDetails = null;
$dostSites = [];
$dostPersonnel = [];
$dostBudget = [];

if ($application['grant_slug'] === 'dost-rd-grant') {
    $detailsStmt = $pdo->prepare('SELECT * FROM dost_application_details WHERE application_id = :id');
    $detailsStmt->execute(['id' => $applicationId]);
    $dostDetails = $detailsStmt->fetch();

    $sitesStmt = $pdo->prepare('SELECT * FROM dost_application_sites WHERE application_id = :id ORDER BY id');
    $sitesStmt->execute(['id' => $applicationId]);
    $dostSites = $sitesStmt->fetchAll();

    $personnelStmt = $pdo->prepare('SELECT * FROM dost_personnel_requirements WHERE application_id = :id ORDER BY id');
    $personnelStmt->execute(['id' => $applicationId]);
    $dostPersonnel = $personnelStmt->fetchAll();

    $budgetStmt = $pdo->prepare('SELECT * FROM dost_budget_allocations WHERE application_id = :id ORDER BY id');
    $budgetStmt->execute(['id' => $applicationId]);
    $dostBudget = $budgetStmt->fetchAll();
}

$documentsStmt = $pdo->prepare("
    SELECT d.*, r.title, r.requirement_code
    FROM application_documents d
    JOIN grant_requirements r ON r.id = d.requirement_id
    WHERE d.application_id = :id
");
$documentsStmt->execute(['id' => $applicationId]);
$documents = $documentsStmt->fetchAll();

$historyStmt = $pdo->prepare("
    SELECT h.*, s.label AS status_label, u.full_name AS changed_by
    FROM application_status_history h
    JOIN application_statuses s ON h.status_id = s.id
    LEFT JOIN users u ON h.created_by = u.id
    WHERE h.application_id = :id
    ORDER BY h.created_at DESC
");
$historyStmt->execute(['id' => $applicationId]);
$history = $historyStmt->fetchAll();

$statusOptions = application_status_options();
$flashSuccess = flash('success');
$flashError = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - Admin | <?php echo sanitize(app_name()); ?></title>
    <link rel="stylesheet" href="../css/shared.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
    <style>
        .detail-section { margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; }
        .detail-section h3 { color: #5a3fa3; margin-bottom: 15px; border-bottom: 2px solid #e8d9f0; padding-bottom: 10px; }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .detail-item { }
        .detail-label { font-weight: 600; color: #7d6b8f; font-size: 13px; margin-bottom: 5px; }
        .detail-value { color: #333; }
        .detail-text { background: #f9f7fc; padding: 15px; border-radius: 8px; margin-top: 10px; white-space: pre-wrap; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #e8d9f0; }
        table th { background: #f9f7fc; font-weight: 600; color: #5a3fa3; }
        .btn-dark {
            background: #5a3fa3 !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
        }
        .btn-dark:hover {
            background: #4a2f93 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(90, 63, 163, 0.3);
        }
        .status-update-form {
            background: linear-gradient(135deg, #f9f7fc 0%, #ffffff 100%);
            border: 2px solid #e8d9f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(90, 63, 163, 0.1);
        }
        .status-update-form select,
        .status-update-form textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e8d9f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s ease;
            margin-bottom: 15px;
        }
        .status-update-form select:focus,
        .status-update-form textarea:focus {
            outline: none;
            border-color: #5a3fa3;
            box-shadow: 0 0 0 3px rgba(90, 63, 163, 0.1);
        }
        .status-update-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #5a3fa3;
            font-size: 14px;
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
                <h1>Application Details</h1>
                <div class="user-info">
                    <span><?php echo sanitize(current_user()['full_name']); ?></span>
                    <a class="logout-btn" href="<?php echo base_path('auth/logout.php'); ?>">Logout</a>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <a href="applications.php" class="btn-small">← Back to Applications</a>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="alert success"><?php echo sanitize($flashSuccess); ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert error"><?php echo sanitize($flashError); ?></div>
            <?php endif; ?>

            <div class="detail-section">
                <h3>Application Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Application Code</div>
                        <div class="detail-value"><?php echo sanitize($application['application_code']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Grant Program</div>
                        <div class="detail-value"><?php echo sanitize($application['grant_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?php echo sanitize($application['status_key']); ?>">
                                <?php echo sanitize($application['status_label']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Project Title</div>
                        <div class="detail-value"><?php echo sanitize($application['project_title']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Requested Amount</div>
                        <div class="detail-value">₱<?php echo number_format($application['requested_amount'], 2); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Submitted At</div>
                        <div class="detail-value"><?php echo $application['submitted_at'] ? date('M d, Y H:i', strtotime($application['submitted_at'])) : '—'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Applicant</div>
                        <div class="detail-value"><?php echo sanitize($application['full_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo sanitize($application['email']); ?></div>
                    </div>
                    <?php if ($application['company_name']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Company</div>
                            <div class="detail-value"><?php echo sanitize($application['company_name']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($dostDetails): ?>
                <div class="detail-section">
                    <h3>Project Leader Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Program Title</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['program_title']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Project Leader</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['project_leader']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Sex</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['sex']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Telephone</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['telephone'] ?: '—'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Address</div>
                            <div class="detail-value">
                                <?php
                                $addressParts = array_filter([
                                    $dostDetails['house_number'],
                                    $dostDetails['street_name'],
                                    $dostDetails['barangay'],
                                    $dostDetails['city'],
                                    $dostDetails['district'],
                                    $dostDetails['province'],
                                    $dostDetails['region'],
                                    $dostDetails['country'],
                                ]);
                                echo sanitize(implode(', ', $addressParts) ?: '—');
                                ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Implementing Agency</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['implementing_agency']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Cooperating Agency</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['cooperating_agency'] ?: '—'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Type of Research</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['type_of_research']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>DOST Alignment</h3>
                    <div class="detail-grid">
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">R&D Priority Area and Program</div>
                            <div class="detail-text"><?php echo sanitize($dostDetails['rd_priority_area_program']); ?></div>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">Sustainable Development Goal Addressed</div>
                            <div class="detail-text"><?php echo sanitize($dostDetails['sustainable_development_goal']); ?></div>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">DOST Pillars Pursued</div>
                            <div class="detail-text"><?php echo sanitize($dostDetails['dost_pillars_pursued']); ?></div>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">DOST Thematic Areas Covered</div>
                            <div class="detail-text"><?php echo sanitize($dostDetails['dost_thematic_areas']); ?></div>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">Applicable DOST Strategic Program</div>
                            <div class="detail-text"><?php echo sanitize($dostDetails['dost_strategic_program']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Project Narrative</h3>
                    <?php
                    $narrativeFields = [
                        'introduction' => 'Introduction',
                        'executive_summary' => 'Executive Summary',
                        'rationale_significance' => 'Rationale & Significance',
                        'scientific_basis' => 'Scientific Basis / Theoretical Framework',
                        'objectives' => 'Objectives',
                        'review_of_literature' => 'Review of Literature',
                        'methodology' => 'Methodology',
                        'technology_roadmap' => 'Technology Roadmap',
                        'expected_outputs' => 'Expected Outputs',
                        'potential_outcomes' => 'Potential Outcomes',
                        'potential_impacts' => 'Potential Impacts',
                        'target_beneficiaries' => 'Target Beneficiaries',
                        'sustainability_plan' => 'Sustainability Plan',
                        'limitations' => 'Limitations of the Project',
                        'risks_assumptions' => 'List of Risks and Assumptions',
                        'literature_cited' => 'Literature Cited',
                    ];
                    foreach ($narrativeFields as $field => $label):
                    ?>
                        <div class="detail-item" style="margin-bottom: 20px;">
                            <div class="detail-label"><?php echo $label; ?></div>
                            <div class="detail-text"><?php echo sanitize($dostDetails[$field]); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($dostSites): ?>
                    <div class="detail-section">
                        <h3>Site(s) of Implementation</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Site No.</th>
                                    <th>Country</th>
                                    <th>Region</th>
                                    <th>Province</th>
                                    <th>District</th>
                                    <th>Municipality</th>
                                    <th>Barangay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dostSites as $site): ?>
                                    <tr>
                                        <td><?php echo sanitize($site['site_number'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($site['country'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($site['region'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($site['province'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($site['district'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($site['municipality'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($site['barangay'] ?: '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($dostPersonnel): ?>
                    <div class="detail-section">
                        <h3>Personnel Requirements</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Quantity</th>
                                    <th>% Time Devoted</th>
                                    <th>Responsibility</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dostPersonnel as $person): ?>
                                    <tr>
                                        <td><?php echo sanitize($person['position']); ?></td>
                                        <td><?php echo sanitize($person['quantity']); ?></td>
                                        <td><?php echo sanitize($person['percent_time']); ?></td>
                                        <td><?php echo sanitize($person['responsibility']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($dostBudget): ?>
                    <div class="detail-section">
                        <h3>Budget Allocation</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Agency</th>
                                    <th>PS DOST</th>
                                    <th>PS Counterpart</th>
                                    <th>MOOE DOST</th>
                                    <th>MOOE Counterpart</th>
                                    <th>CO DOST</th>
                                    <th>CO Counterpart</th>
                                    <th>Total DOST</th>
                                    <th>Total Counterpart</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dostBudget as $budget): ?>
                                    <tr>
                                        <td><?php echo sanitize($budget['year_label']); ?></td>
                                        <td><?php echo sanitize($budget['agency']); ?></td>
                                        <td>₱<?php echo number_format($budget['ps_dost'], 2); ?></td>
                                        <td>₱<?php echo number_format($budget['ps_counterpart'], 2); ?></td>
                                        <td>₱<?php echo number_format($budget['mooe_dost'], 2); ?></td>
                                        <td>₱<?php echo number_format($budget['mooe_counterpart'], 2); ?></td>
                                        <td>₱<?php echo number_format($budget['co_dost'], 2); ?></td>
                                        <td>₱<?php echo number_format($budget['co_counterpart'], 2); ?></td>
                                        <td><strong>₱<?php echo number_format($budget['total_dost'], 2); ?></strong></td>
                                        <td><strong>₱<?php echo number_format($budget['total_counterpart'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="detail-section">
                    <h3>Additional Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Gender and Development (GAD) Score</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['gad_score']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Submitted By</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['submitted_by']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Endorsed By</div>
                            <div class="detail-value"><?php echo sanitize($dostDetails['endorsed_by'] ?: '—'); ?></div>
                        </div>
                        <?php if ($dostDetails['remarks']): ?>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">Remarks</div>
                                <div class="detail-text"><?php echo sanitize($dostDetails['remarks']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($documents): ?>
                <div class="detail-section">
                    <h3>Submitted Documents</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($documents as $doc): ?>
                            <li style="padding: 10px; margin-bottom: 10px; background: #f9f7fc; border-radius: 8px;">
                                <strong><?php echo sanitize($doc['title']); ?></strong>
                                <a href="<?php echo base_path('uploads/' . sanitize($doc['stored_filename'])); ?>" target="_blank" class="btn-small" style="margin-left: 10px;">Download</a>
                                <small style="display: block; color: #7d6b8f; margin-top: 5px;">
                                    <?php echo number_format($doc['file_size'] / 1024, 2); ?> KB · Uploaded: <?php echo date('M d, Y H:i', strtotime($doc['uploaded_at'])); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="detail-section">
                <h3>Status History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Changed By</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$history): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #a89bb8;">No status history yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $entry): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($entry['created_at'])); ?></td>
                                    <td><?php echo sanitize($entry['status_label']); ?></td>
                                    <td><?php echo sanitize($entry['changed_by'] ?: 'System'); ?></td>
                                    <td><?php echo sanitize($entry['remarks'] ?: '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="detail-section">
                <h3>Update Application Status</h3>
                <div class="status-update-form">
                    <form method="post" action="applications.php">
                        <input type="hidden" name="application_id" value="<?php echo (int) $applicationId; ?>">
                        <div class="detail-item">
                            <label>New Status *</label>
                            <select name="status_id" required>
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?php echo (int) $status['id']; ?>" <?php echo (int) $status['id'] === (int) $application['status_id'] ? 'selected' : ''; ?>>
                                        <?php echo sanitize($status['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="detail-item">
                            <label>Remarks</label>
                            <textarea name="remarks" rows="4" placeholder="Add remarks about this status change..."></textarea>
                        </div>
                        <button type="submit" class="btn-dark">Update Status</button>
                    </form>
                </div>
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
