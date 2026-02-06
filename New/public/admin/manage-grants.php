<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('admin');

$pdo = db();
$errors = [];

// Handle status update
if (is_post() && isset($_POST['update_status'])) {
    $grantId = (int) ($_POST['grant_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    
    if ($grantId && in_array($newStatus, ['draft', 'published', 'archived'], true)) {
        $stmt = $pdo->prepare('UPDATE grant_programs SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $grantId]);
        flash('success', 'Grant status updated successfully.');
        redirect('manage-grants.php');
    }
}

// Handle grant deletion
if (is_post() && isset($_POST['delete_grant'])) {
    $grantId = (int) ($_POST['grant_id'] ?? 0);
    
    if ($grantId) {
        try {
            // Check if grant has applications
            $appCount = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE grant_id = :id');
            $appCount->execute(['id' => $grantId]);
            $hasApplications = $appCount->fetchColumn() > 0;
            
            if ($hasApplications) {
                flash('error', 'Cannot delete grant. It has existing applications. Archive it instead.');
            } else {
                // Delete grant (cascade will handle requirements)
                $stmt = $pdo->prepare('DELETE FROM grant_programs WHERE id = :id');
                $stmt->execute(['id' => $grantId]);
                flash('success', 'Grant deleted successfully.');
            }
        } catch (Exception $e) {
            flash('error', 'Failed to delete grant. Please try again.');
        }
        redirect('manage-grants.php');
    }
}

if (is_post()) {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'create_grant') {
        $name = trim($_POST['name'] ?? '');
        $shortName = trim($_POST['short_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $maxFunding = $_POST['max_funding'] !== '' ? (float) $_POST['max_funding'] : null;
        $deadline = $_POST['deadline'] ?? null;
        $status = $_POST['status'] ?? 'draft';

        if ($name === '' || $description === '') {
            $errors[] = 'Grant name and description are required.';
        }

        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $errors[] = 'Invalid status.';
        }

        if (!$errors) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $stmt = $pdo->prepare('INSERT INTO grant_programs (slug, name, short_name, description, max_funding, deadline, status, created_by) VALUES (:slug, :name, :short_name, :description, :max_funding, :deadline, :status, :created_by)');
            $stmt->execute([
                'slug' => $slug . '-' . uniqid(),
                'name' => $name,
                'short_name' => $shortName ?: null,
                'description' => $description,
                'max_funding' => $maxFunding,
                'deadline' => $deadline ?: null,
                'status' => $status,
                'created_by' => current_user()['id'],
            ]);

            $newGrantId = (int) $pdo->lastInsertId();
            flash('success', 'Grant created successfully. You can now add requirements below.');
            redirect('manage-grants.php?grant_id=' . $newGrantId);
        } else {
            flash('error', implode(' ', $errors));
            redirect('manage-grants.php');
        }
    }

    if (isset($_POST['form_type']) && $_POST['form_type'] === 'add_requirement') {
        $grantId = (int) ($_POST['grant_id'] ?? 0);
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $title = trim($_POST['req_title'] ?? '');
        $description = trim($_POST['req_description'] ?? '');
        $type = $_POST['req_type'] ?? 'document';
        $isRequired = isset($_POST['req_required']) ? 1 : 0;
        
        // Handle multiple sub-requirements
        $subRequirements = $_POST['sub_requirements'] ?? [];

        $grantExists = $pdo->prepare('SELECT COUNT(*) FROM grant_programs WHERE id = :id');
        $grantExists->execute(['id' => $grantId]);

        if (!$grantId || !$grantExists->fetchColumn()) {
            $errors[] = 'Please select a valid grant.';
        }

        if ($title === '') {
            $errors[] = 'Requirement title is required.';
        }

        if (!in_array($type, ['document', 'text', 'number', 'url', 'date'], true)) {
            $errors[] = 'Invalid requirement type.';
        }

        if (!$errors) {
            // Check if parent_id column exists, if not, we'll add it
            $hasParentIdColumn = false;
            try {
                $checkColumn = $pdo->query("SHOW COLUMNS FROM grant_requirements LIKE 'parent_id'");
                $hasParentIdColumn = $checkColumn->rowCount() > 0;
                if (!$hasParentIdColumn) {
                    $pdo->exec('ALTER TABLE grant_requirements ADD COLUMN parent_id INT UNSIGNED NULL AFTER grant_id, ADD KEY idx_parent_id (parent_id), ADD CONSTRAINT fk_requirement_parent FOREIGN KEY (parent_id) REFERENCES grant_requirements(id) ON DELETE CASCADE');
                    $hasParentIdColumn = true;
                }
            } catch (Exception $e) {
                // Column might already exist or there's an issue, continue anyway
            }
            
            $mainRequirementId = null;
            
            if ($hasParentIdColumn) {
                $stmt = $pdo->prepare('INSERT INTO grant_requirements (grant_id, parent_id, requirement_code, title, description, requirement_type, is_required, sort_order) VALUES (:grant_id, :parent_id, :code, :title, :description, :type, :is_required, :sort_order)');
                $stmt->execute([
                    'grant_id' => $grantId,
                    'parent_id' => $parentId,
                    'code' => strtoupper('REQ-' . uniqid()),
                    'title' => $title,
                    'description' => $description ?: null,
                    'type' => $type,
                    'is_required' => $isRequired,
                    'sort_order' => 999,
                ]);
                $mainRequirementId = (int) $pdo->lastInsertId();
            } else {
                // Fallback if parent_id column doesn't exist and can't be added
                $stmt = $pdo->prepare('INSERT INTO grant_requirements (grant_id, requirement_code, title, description, requirement_type, is_required, sort_order) VALUES (:grant_id, :code, :title, :description, :type, :is_required, :sort_order)');
                $stmt->execute([
                    'grant_id' => $grantId,
                    'code' => strtoupper('REQ-' . uniqid()),
                    'title' => $title,
                    'description' => $description ?: null,
                    'type' => $type,
                    'is_required' => $isRequired,
                    'sort_order' => 999,
                ]);
                $mainRequirementId = (int) $pdo->lastInsertId();
            }
            
            // Add multiple sub-requirements if provided
            if ($mainRequirementId && !empty($subRequirements) && is_array($subRequirements) && $hasParentIdColumn) {
                foreach ($subRequirements as $subReq) {
                    $subTitle = trim($subReq['title'] ?? '');
                    $subDesc = trim($subReq['description'] ?? '');
                    $subType = $subReq['type'] ?? 'text';
                    $subRequired = isset($subReq['required']) ? 1 : 0;
                    
                    if ($subTitle !== '') {
                        $subStmt = $pdo->prepare('INSERT INTO grant_requirements (grant_id, parent_id, requirement_code, title, description, requirement_type, is_required, sort_order) VALUES (:grant_id, :parent_id, :code, :title, :description, :type, :is_required, :sort_order)');
                        $subStmt->execute([
                            'grant_id' => $grantId,
                            'parent_id' => $mainRequirementId,
                            'code' => strtoupper('SUB-' . uniqid()),
                            'title' => $subTitle,
                            'description' => $subDesc ?: null,
                            'type' => $subType,
                            'is_required' => $subRequired,
                            'sort_order' => 999,
                        ]);
                    }
                }
            }

            flash('success', 'Requirement added to grant successfully.');
            // Keep the grant selected and show create section if it was open
            $redirectUrl = 'manage-grants.php?grant_id=' . $grantId;
            if (isset($_POST['from_create_section'])) {
                $redirectUrl .= '&create_open=1';
            }
            redirect($redirectUrl);
        } else {
            flash('error', implode(' ', $errors));
            redirect('manage-grants.php');
        }
    }
}

// Check if parent_id column exists
$hasParentIdColumn = false;
try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM grant_requirements LIKE 'parent_id'");
    $hasParentIdColumn = $checkColumn->rowCount() > 0;
} catch (Exception $e) {
    // Column doesn't exist
    $hasParentIdColumn = false;
}

$selectedGrantId = isset($_GET['grant_id']) ? (int) $_GET['grant_id'] : null;
$statusFilter = $_GET['status'] ?? null;
$grantQuery = "
    SELECT g.*, COUNT(DISTINCT r.id) AS requirement_count
    FROM grant_programs g
    LEFT JOIN grant_requirements r ON r.grant_id = g.id" . ($hasParentIdColumn ? " AND (r.parent_id IS NULL OR r.parent_id = 0)" : "") . "
";
if ($statusFilter && in_array($statusFilter, ['draft', 'published', 'archived'], true)) {
    $grantQuery .= " WHERE g.status = :status";
}
$grantQuery .= " GROUP BY g.id ORDER BY g.created_at DESC";
$stmt = $pdo->prepare($grantQuery);
if ($statusFilter && in_array($statusFilter, ['draft', 'published', 'archived'], true)) {
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt->execute();
}
$grants = $stmt->fetchAll();

// Get requirements for selected grant
$requirements = [];
$subRequirements = [];
if ($selectedGrantId) {
    if ($hasParentIdColumn) {
        $reqStmt = $pdo->prepare('SELECT * FROM grant_requirements WHERE grant_id = :grant_id AND (parent_id IS NULL OR parent_id = 0) ORDER BY sort_order, id');
    } else {
        $reqStmt = $pdo->prepare('SELECT * FROM grant_requirements WHERE grant_id = :grant_id ORDER BY sort_order, id');
    }
    $reqStmt->execute(['grant_id' => $selectedGrantId]);
    $requirements = $reqStmt->fetchAll();
    
    if ($requirements && $hasParentIdColumn) {
        $reqIds = array_column($requirements, 'id');
        if ($reqIds) {
            $placeholders = str_repeat('?,', count($reqIds) - 1) . '?';
            $subReqStmt = $pdo->prepare("SELECT * FROM grant_requirements WHERE parent_id IN ($placeholders) ORDER BY sort_order, id");
            $subReqStmt->execute($reqIds);
            $subReqs = $subReqStmt->fetchAll();
            
            foreach ($subReqs as $subReq) {
                $subRequirements[$subReq['parent_id']][] = $subReq;
            }
        }
    }
}

$flashSuccess = flash('success');
$flashError = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grants - Admin | <?php echo sanitize(app_name()); ?></title>
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
        .btn-dark-success {
            background: #28a745 !important;
        }
        .btn-dark-success:hover {
            background: #218838 !important;
        }
        .btn-dark-danger {
            background: #dc3545 !important;
        }
        .btn-dark-danger:hover {
            background: #c82333 !important;
        }
        .btn-dark-warning {
            background: #ffc107 !important;
            color: #000 !important;
        }
        .btn-dark-warning:hover {
            background: #e0a800 !important;
        }
        .btn-dark-info {
            background: #17a2b8 !important;
        }
        .btn-dark-info:hover {
            background: #138496 !important;
        }
        .grant-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e8d9f0;
        }
        .grant-card:hover {
            border-color: #5a3fa3;
            box-shadow: 0 4px 12px rgba(90, 63, 163, 0.2);
            transform: translateY(-2px);
        }
        .grant-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .create-grant-section {
            display: none;
        }
        .create-grant-section.active {
            display: block;
        }
        .requirement-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .requirement-table th {
            background: #5a3fa3;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .requirement-table td {
            padding: 12px;
            border-bottom: 1px solid #e8d9f0;
        }
        .requirement-table tr:last-child td {
            border-bottom: none;
        }
        .sub-requirement {
            background: #f9f7fc;
            padding-left: 30px;
        }
        .sub-requirement td:first-child::before {
            content: "↳ ";
            color: #5a3fa3;
            font-weight: bold;
        }
        .add-sub-req-btn {
            margin-left: 30px;
            margin-top: 5px;
        }
        .requirement-section {
            background: white;
            border: 2px solid #e8d9f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .requirement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8d9f0;
        }
        .requirement-header h3 {
            color: #5a3fa3;
            margin: 0;
        }
        .sub-requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .sub-requirements-table th {
            background: #f9f7fc;
            color: #5a3fa3;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e8d9f0;
        }
        .sub-requirements-table td {
            padding: 10px;
            border-bottom: 1px solid #e8d9f0;
        }
        .sub-requirements-form {
            background: linear-gradient(135deg, #ffffff 0%, #f9f7fc 100%);
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
            display: none;
            border: 2px solid #e8d9f0;
            box-shadow: 0 4px 12px rgba(90, 63, 163, 0.1);
        }
        .sub-requirements-form.active {
            display: block;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .sub-requirements-form h4 {
            color: #5a3fa3;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #e8d9f0;
        }
        .sub-req-row {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1fr auto;
            gap: 15px;
            margin-bottom: 15px;
            align-items: end;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e8d9f0;
            transition: all 0.3s ease;
        }
        .sub-req-row:hover {
            border-color: #d4a5e8;
            box-shadow: 0 2px 8px rgba(90, 63, 163, 0.1);
        }
        .sub-req-row input[type="text"],
        .sub-req-row select {
            padding: 10px 12px;
            border: 1px solid #d4a5e8;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            color: #333;
        }
        .sub-req-row input[type="text"]:focus,
        .sub-req-row select:focus {
            outline: none;
            border-color: #5a3fa3;
            box-shadow: 0 0 0 3px rgba(90, 63, 163, 0.1);
        }
        .sub-req-row input[type="text"]::placeholder {
            color: #a89bb8;
        }
        .sub-req-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: #f9f7fc;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e8d9f0;
        }
        .sub-req-row label:hover {
            background: #f0e8f8;
            border-color: #d4a5e8;
        }
        .sub-req-row label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #5a3fa3;
        }
        .sub-req-row label span {
            font-size: 13px;
            color: #5a3fa3;
            font-weight: 500;
        }
        .remove-sub-req-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
            white-space: nowrap;
        }
        .remove-sub-req-btn:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        .remove-sub-req-btn:active {
            transform: translateY(0);
        }
        .view-requirements-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            overflow-y: auto;
        }
        .view-requirements-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8d9f0;
        }
        .modal-header h2 {
            color: #5a3fa3;
            margin: 0;
        }
        .close-modal {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .close-modal:hover {
            background: #c82333;
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
                <li><a href="manage-grants.php" class="menu-link active">📋 Manage Grants</a></li>
                <li><a href="applications.php" class="menu-link">📄 Applications</a></li>
                <li><a href="settings.php" class="menu-link">⚙️ Settings</a></li>
                <li><a href="<?php echo base_path('auth/logout.php'); ?>">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <div class="content">
            <div class="header">
                <h1>Manage Grants</h1>
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

            <div style="margin-bottom: 20px;">
                <button type="button" class="btn-dark btn-dark-primary" onclick="toggleCreateGrant()" id="toggleCreateBtn">
                    + Create Grant Program
                </button>
            </div>

            <div class="create-grant-section" id="createGrantSection">
                <div class="grid-2" style="gap:20px;">
                    <div class="card">
                        <h2 style="color:#5a3fa3;">Create Grant Program</h2>
                        <form method="post">
                            <input type="hidden" name="form_type" value="create_grant">
                            <div class="form-group">
                                <label>Grant Title *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Short Name</label>
                                <input type="text" name="short_name">
                            </div>
                            <div class="form-group">
                                <label>Description *</label>
                                <textarea name="description" required rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Maximum Funding (PHP)</label>
                                <input type="number" step="0.01" name="max_funding">
                            </div>
                            <div class="form-group">
                                <label>Deadline</label>
                                <input type="date" name="deadline">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-dark btn-dark-primary">Save Grant</button>
                        </form>
                    </div>

                    <div class="card">
                        <h2 style="color:#5a3fa3;">Add Requirements to Grant</h2>
                        <form method="post" id="requirementFormCreate">
                            <input type="hidden" name="form_type" value="add_requirement">
                            <input type="hidden" name="parent_id" id="parent_id_create" value="">
                            <input type="hidden" name="from_create_section" value="1">
                            <div class="form-group">
                                <label>Select Grant *</label>
                                <select name="grant_id" required id="grant_id_create" onchange="updateRequirementForm()">
                                    <option value="">-- Select a grant --</option>
                                    <?php foreach ($grants as $grant): ?>
                                        <option value="<?php echo (int) $grant['id']; ?>" <?php echo $selectedGrantId == $grant['id'] ? 'selected' : ''; ?>>
                                            <?php echo sanitize($grant['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color:#7d6b8f; display:block; margin-top:5px;">Select a grant to add requirements to it</small>
                            </div>
                            <div class="form-group">
                                <label>Requirement Title *</label>
                                <input type="text" name="req_title" required id="req_title_create" placeholder="e.g., Personal Information">
                                <small style="color:#7d6b8f; display:block; margin-top:5px;">Leave parent_id empty for main requirement</small>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="req_type" id="req_type_create">
                                    <option value="document">Document upload</option>
                                    <option value="text">Text response</option>
                                    <option value="number">Numeric value</option>
                                    <option value="url">URL</option>
                                    <option value="date">Date</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="req_description" rows="3" id="req_description_create" placeholder="Optional description for this requirement"></textarea>
                            </div>
                            <label style="display:flex;align-items:center;gap:10px; margin-bottom:15px;">
                                <input type="checkbox" name="req_required" checked id="req_required_create">
                                <span>Required</span>
                            </label>
                            <button type="submit" class="btn-dark btn-dark-primary">Add Requirement</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($selectedGrantId): ?>
                <div class="card" style="margin-top:30px;">
                    <h2 style="color:#5a3fa3; margin-bottom:20px;">Add Requirement to: <?php 
                        $selectedGrant = null;
                        foreach ($grants as $g) {
                            if ($g['id'] == $selectedGrantId) {
                                $selectedGrant = $g;
                                break;
                            }
                        }
                        echo $selectedGrant ? sanitize($selectedGrant['name']) : 'Selected Grant';
                    ?></h2>
                    <form method="post" id="requirementForm">
                        <input type="hidden" name="form_type" value="add_requirement">
                        <input type="hidden" name="grant_id" value="<?php echo $selectedGrantId; ?>">
                        <input type="hidden" name="parent_id" id="parent_id" value="">
                        <div class="grid-2" style="gap:20px;">
                            <div class="form-group">
                                <label>Requirement Title *</label>
                                <input type="text" name="req_title" required id="req_title" placeholder="e.g., Personal Information">
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="req_type" id="req_type">
                                    <option value="document">Document upload</option>
                                    <option value="text">Text response</option>
                                    <option value="number">Numeric value</option>
                                    <option value="url">URL</option>
                                    <option value="date">Date</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="req_description" rows="3" id="req_description" placeholder="Optional description for this requirement"></textarea>
                        </div>
                        <label style="display:flex;align-items:center;gap:10px; margin-bottom:15px;">
                            <input type="checkbox" name="req_required" checked id="req_required">
                            <span>Required</span>
                        </label>
                        <button type="submit" class="btn-dark btn-dark-primary">Add Requirement</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!$selectedGrantId): ?>
                <div class="card" style="margin-top:30px;">
                    <div class="action-bar" style="margin-bottom:20px; display:flex;justify-content:space-between;align-items:center;">
                        <h2 style="color:#5a3fa3;">Existing Grants</h2>
                        <form method="get" style="display:flex;gap:10px;align-items:center;">
                            <select name="status" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                            <?php if ($statusFilter): ?>
                                <a href="manage-grants.php" class="btn-dark" style="text-decoration:none;display:inline-block;">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <?php if (!$grants): ?>
                        <p style="color:#a89bb8;">No grants created yet.</p>
                    <?php else: ?>
                        <div class="grants-grid">
                            <?php foreach ($grants as $grant): ?>
                                <div class="grant-card" onclick="selectGrant(<?php echo (int) $grant['id']; ?>)">
                                    <div class="grant-title"><?php echo sanitize($grant['name']); ?></div>
                                    <div class="grant-amount">
                                        <?php echo $grant['max_funding'] ? '₱' . number_format($grant['max_funding'], 2) : 'Funding TBD'; ?>
                                    </div>
                                    <div class="grant-desc"><?php echo sanitize($grant['description']); ?></div>
                                    <div class="grant-meta">
                                        <span>Status: <?php echo ucfirst($grant['status']); ?></span>
                                        <span>Requirements: <?php echo (int) $grant['requirement_count']; ?></span>
                                    </div>
                                    <div class="grant-meta">
                                        <span>Deadline: <?php echo $grant['deadline'] ? date('M d, Y', strtotime($grant['deadline'])) : 'Not set'; ?></span>
                                    </div>
                                    <div class="grant-actions">
                                        <button type="button" class="btn-dark btn-dark-info" onclick="event.stopPropagation(); viewGrant(<?php echo (int) $grant['id']; ?>)">View</button>
                                        <button type="button" class="btn-dark btn-dark-success" onclick="event.stopPropagation(); updateStatus(<?php echo (int) $grant['id']; ?>, 'published')" <?php echo $grant['status'] === 'published' ? 'disabled style="opacity:0.5;"' : ''; ?>>Publish</button>
                                        <button type="button" class="btn-dark btn-dark-warning" onclick="event.stopPropagation(); updateStatus(<?php echo (int) $grant['id']; ?>, 'draft')" <?php echo $grant['status'] === 'draft' ? 'disabled style="opacity:0.5;"' : ''; ?>>Draft</button>
                                        <button type="button" class="btn-dark btn-dark-danger" onclick="event.stopPropagation(); updateStatus(<?php echo (int) $grant['id']; ?>, 'archived')" <?php echo $grant['status'] === 'archived' ? 'disabled style="opacity:0.5;"' : ''; ?>>Archive</button>
                                        <button type="button" class="btn-dark btn-dark-danger" onclick="event.stopPropagation(); deleteGrant(<?php echo (int) $grant['id']; ?>, '<?php echo addslashes($grant['name']); ?>')" style="background:#dc3545;">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="margin-top:30px; margin-bottom:20px;">
                    <a href="manage-grants.php" class="btn-dark btn-dark-primary" style="text-decoration:none;display:inline-block;">← Back to All Grants</a>
                </div>
            <?php endif; ?>

            <?php if ($selectedGrantId): ?>
                <?php 
                $selectedGrantName = 'Selected Grant';
                foreach ($grants as $g) {
                    if ($g['id'] == $selectedGrantId) {
                        $selectedGrantName = $g['name'];
                        break;
                    }
                }
                ?>
                
                <?php if ($requirements): ?>
                    <div class="card" style="margin-top:30px;">
                        <h2 style="color:#5a3fa3; margin-bottom:20px;">Requirements for: <?php echo sanitize($selectedGrantName); ?></h2>
                        <?php foreach ($requirements as $req): ?>
                            <div class="requirement-section" id="req-section-<?php echo (int) $req['id']; ?>">
                                <div class="requirement-header">
                                    <div>
                                        <h3><?php echo sanitize($req['title']); ?></h3>
                                        <?php if ($req['description']): ?>
                                            <p style="color:#7d6b8f; margin:5px 0 0 0; font-size:14px;"><?php echo sanitize($req['description']); ?></p>
                                        <?php endif; ?>
                                        <small style="color:#a89bb8;">Type: <?php echo ucfirst($req['requirement_type']); ?> | Required: <?php echo $req['is_required'] ? 'Yes' : 'No'; ?></small>
                                    </div>
                                    <button type="button" class="btn-dark btn-dark-info" onclick="toggleSubRequirementForm(<?php echo (int) $req['id']; ?>, '<?php echo addslashes($req['title']); ?>')">Add Sub-Requirements</button>
                                </div>
                                
                                <?php if (isset($subRequirements[$req['id']]) && !empty($subRequirements[$req['id']])): ?>
                                    <table class="sub-requirements-table">
                                        <thead>
                                            <tr>
                                                <th>Sub-Requirement</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subRequirements[$req['id']] as $subReq): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo sanitize($subReq['title']); ?></strong>
                                                        <?php if ($subReq['description']): ?>
                                                            <br><small style="color:#7d6b8f;"><?php echo sanitize($subReq['description']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo ucfirst($subReq['requirement_type']); ?></td>
                                                    <td><?php echo $subReq['is_required'] ? 'Yes' : 'No'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="color:#a89bb8; margin-top:15px;">No sub-requirements added yet.</p>
                                <?php endif; ?>
                                
                                <div class="sub-requirements-form" id="sub-req-form-<?php echo (int) $req['id']; ?>">
                                    <h4>Add Multiple Sub-Requirements</h4>
                                    <form method="post" id="subReqForm-<?php echo (int) $req['id']; ?>">
                                        <input type="hidden" name="form_type" value="add_requirement">
                                        <input type="hidden" name="grant_id" value="<?php echo $selectedGrantId; ?>">
                                        <input type="hidden" name="parent_id" value="<?php echo (int) $req['id']; ?>">
                                        <input type="hidden" name="req_title" value="<?php echo addslashes($req['title']); ?>">
                                        <input type="hidden" name="req_description" value="">
                                        <input type="hidden" name="req_type" value="text">
                                        <input type="hidden" name="req_required" value="1">
                                        
                                        <div id="sub-req-rows-<?php echo (int) $req['id']; ?>">
                                            <div class="sub-req-row">
                                                <input type="text" name="sub_requirements[0][title]" placeholder="e.g., First Name" required>
                                                <select name="sub_requirements[0][type]">
                                                    <option value="text">Text</option>
                                                    <option value="number">Number</option>
                                                    <option value="date">Date</option>
                                                    <option value="url">URL</option>
                                                </select>
                                                <label style="display:flex;align-items:center;gap:5px;">
                                                    <input type="checkbox" name="sub_requirements[0][required]" checked>
                                                    <span style="font-size:12px;">Required</span>
                                                </label>
                                                <button type="button" class="remove-sub-req-btn" onclick="removeSubReqRow(this)" style="display:none;">Remove</button>
                                            </div>
                                        </div>
                                        
                                        <div style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap; padding-top:15px; border-top:2px solid #e8d9f0;">
                                            <button type="button" class="btn-dark btn-dark-info" onclick="addSubReqRow(<?php echo (int) $req['id']; ?>)" style="font-weight:600;">+ Add Another Row</button>
                                            <button type="submit" class="btn-dark btn-dark-primary" style="font-weight:600;">💾 Save All Sub-Requirements</button>
                                            <button type="button" class="btn-dark" onclick="toggleSubRequirementForm(<?php echo (int) $req['id']; ?>, '')" style="background:#6c757d; font-weight:600;">✕ Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card" style="margin-top:30px;">
                        <h2 style="color:#5a3fa3; margin-bottom:20px;">Requirements for: <?php echo sanitize($selectedGrantName); ?></h2>
                        <p style="color:#a89bb8; margin-bottom:20px;">No requirements added yet. Use the form above to add requirements.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleCreateGrant() {
            const section = document.getElementById('createGrantSection');
            const btn = document.getElementById('toggleCreateBtn');
            section.classList.toggle('active');
            btn.textContent = section.classList.contains('active') ? '− Hide Create Form' : '+ Create Grant Program';
        }

        // Show create section if create_open parameter is in URL
        <?php if (isset($_GET['create_open'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toggleCreateGrant();
            });
        <?php endif; ?>

        function selectGrant(grantId) {
            window.location.href = 'manage-grants.php?grant_id=' + grantId<?php echo $statusFilter ? " + '&status=" . $statusFilter . "'" : ''; ?>;
        }

        function viewGrant(grantId) {
            // Redirect to show requirements for this grant
            window.location.href = 'manage-grants.php?grant_id=' + grantId;
        }
        
        // Scroll to requirements section when grant is selected via URL
        <?php if ($selectedGrantId): ?>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    const reqSection = document.querySelector('.requirement-section, .card[style*="margin-top:30px"]');
                    if (reqSection) {
                        reqSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            });
        <?php endif; ?>
        
        function toggleSubRequirementForm(reqId, reqTitle) {
            const form = document.getElementById('sub-req-form-' + reqId);
            if (form) {
                form.classList.toggle('active');
                if (form.classList.contains('active')) {
                    // Reset form
                    const rowsContainer = document.getElementById('sub-req-rows-' + reqId);
                    rowsContainer.innerHTML = `
                        <div class="sub-req-row">
                            <input type="text" name="sub_requirements[0][title]" placeholder="e.g., First Name" required>
                            <select name="sub_requirements[0][type]">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="url">URL</option>
                            </select>
                            <label style="display:flex;align-items:center;gap:5px;">
                                <input type="checkbox" name="sub_requirements[0][required]" checked>
                                <span style="font-size:12px;">Required</span>
                            </label>
                            <button type="button" class="remove-sub-req-btn" onclick="removeSubReqRow(this)" style="display:none;">Remove</button>
                        </div>
                    `;
                    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }
        
        function addSubReqRow(reqId) {
            const container = document.getElementById('sub-req-rows-' + reqId);
            const rowCount = container.children.length;
            const newRow = document.createElement('div');
            newRow.className = 'sub-req-row';
            newRow.innerHTML = `
                <input type="text" name="sub_requirements[${rowCount}][title]" placeholder="e.g., Last Name" required>
                <select name="sub_requirements[${rowCount}][type]">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="url">URL</option>
                </select>
                <label style="display:flex;align-items:center;gap:5px;">
                    <input type="checkbox" name="sub_requirements[${rowCount}][required]" checked>
                    <span style="font-size:12px;">Required</span>
                </label>
                <button type="button" class="remove-sub-req-btn" onclick="removeSubReqRow(this)">Remove</button>
            `;
            container.appendChild(newRow);
            
            // Show remove buttons if more than one row
            if (container.children.length > 1) {
                container.querySelectorAll('.remove-sub-req-btn').forEach(btn => {
                    btn.style.display = 'block';
                });
            }
        }
        
        function removeSubReqRow(btn) {
            const row = btn.closest('.sub-req-row');
            const container = row.parentElement;
            row.remove();
            
            // Re-index remaining rows
            Array.from(container.children).forEach((child, index) => {
                const inputs = child.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                    }
                });
            });
            
            // Hide remove buttons if only one row left
            if (container.children.length <= 1) {
                container.querySelectorAll('.remove-sub-req-btn').forEach(b => {
                    b.style.display = 'none';
                });
            }
        }

        function updateStatus(grantId, newStatus) {
            if (confirm('Are you sure you want to change the status to ' + newStatus + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="update_status" value="1">' +
                                '<input type="hidden" name="grant_id" value="' + grantId + '">' +
                                '<input type="hidden" name="new_status" value="' + newStatus + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteGrant(grantId, grantName) {
            if (confirm('Are you sure you want to delete "' + grantName + '"? This action cannot be undone. All requirements for this grant will also be deleted.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_grant" value="1">' +
                                '<input type="hidden" name="grant_id" value="' + grantId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function addSubRequirement(parentId, parentTitle) {
            // Try to find the requirement form (could be in create section or in selected grant section)
            const formCreate = document.getElementById('requirementFormCreate');
            const formView = document.getElementById('requirementForm');
            
            if (formCreate) {
                // Set parent ID in create form
                document.getElementById('parent_id_create').value = parentId;
                document.getElementById('req_title_create').value = '';
                document.getElementById('req_description_create').value = '';
                document.getElementById('req_type_create').value = 'text'; // Sub-requirements are usually text fields
                document.getElementById('req_required_create').checked = true;
                
                // Make sure create section is visible
                const createSection = document.getElementById('createGrantSection');
                if (createSection && !createSection.classList.contains('active')) {
                    toggleCreateGrant();
                }
                
                // Scroll to form
                formCreate.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.getElementById('req_title_create').focus();
                
                // Show a message
                alert('Adding sub-requirement for: ' + parentTitle + '\nFill in the form above and click "Add Requirement"');
            } else if (formView) {
                // Set parent ID in view form
                document.getElementById('parent_id').value = parentId;
                document.getElementById('req_title').value = '';
                document.getElementById('req_description').value = '';
                document.getElementById('req_type').value = 'text'; // Sub-requirements are usually text fields
                document.getElementById('req_required').checked = true;
                
                // Scroll to form
                formView.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.getElementById('req_title').focus();
                
                // Show a message
                alert('Adding sub-requirement for: ' + parentTitle + '\nFill in the form above and click "Add Requirement"');
            }
        }

        function updateRequirementForm() {
            const grantSelect = document.getElementById('grant_id_create');
            const grantId = grantSelect.value;
            if (grantId) {
                // Optionally redirect to show the grant with requirements
                // Or just update the form to work with selected grant
            }
        }

        // Show create section if there's an error (form was submitted)
        <?php if ($flashError && isset($_POST['form_type'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toggleCreateGrant();
            });
        <?php endif; ?>

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
