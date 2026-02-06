<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login('founder');

$user = current_user();
$pdo = db();
$grants = fetch_grants('published');
$applicationsStmt = $pdo->prepare('SELECT grant_id, status_id FROM applications WHERE user_id = :user_id');
$applicationsStmt->execute(['user_id' => $user['id']]);
$existingApplications = $applicationsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$selectedGrantId = isset($_GET['grant_id']) ? (int) $_GET['grant_id'] : null;
$selectedGrant = $selectedGrantId ? fetch_grant_with_requirements($selectedGrantId) : null;
$dostGrantSlug = 'dost-rd-grant';

// Location data for dropdowns
$philippineRegions = [
    'NCR - National Capital Region',
    'CAR - Cordillera Administrative Region',
    'Region I - Ilocos Region',
    'Region II - Cagayan Valley',
    'Region III - Central Luzon',
    'Region IV-A - CALABARZON',
    'Region IV-B - MIMAROPA',
    'Region V - Bicol Region',
    'Region VI - Western Visayas',
    'Region VII - Central Visayas',
    'Region VIII - Eastern Visayas',
    'Region IX - Zamboanga Peninsula',
    'Region X - Northern Mindanao',
    'Region XI - Davao Region',
    'Region XII - SOCCSKSARGEN',
    'Region XIII - Caraga',
    'BARMM - Bangsamoro Autonomous Region in Muslim Mindanao'
];

$philippineProvinces = [
    'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao', 'Aurora',
    'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol', 'Bukidnon', 'Bulacan',
    'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin', 'Capiz', 'Catanduanes', 'Cavite',
    'Cebu', 'Cotabato', 'Davao de Oro', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental',
    'Davao Oriental', 'Dinagat Islands', 'Eastern Samar', 'Guimaras', 'Ifugao', 'Ilocos Norte',
    'Ilocos Sur', 'Iloilo', 'Isabela', 'Kalinga', 'La Union', 'Laguna', 'Lanao del Norte',
    'Lanao del Sur', 'Leyte', 'Maguindanao', 'Marinduque', 'Masbate', 'Metro Manila',
    'Misamis Occidental', 'Misamis Oriental', 'Mountain Province', 'Negros Occidental',
    'Negros Oriental', 'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya', 'Occidental Mindoro',
    'Oriental Mindoro', 'Palawan', 'Pampanga', 'Pangasinan', 'Quezon', 'Quirino', 'Rizal',
    'Romblon', 'Samar', 'Sarangani', 'Siquijor', 'Sorsogon', 'South Cotabato', 'Southern Leyte',
    'Sultan Kudarat', 'Sulu', 'Surigao del Norte', 'Surigao del Sur', 'Tarlac', 'Tawi-Tawi',
    'Zambales', 'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay'
];

$philippineCities = [
    'Manila', 'Quezon City', 'Caloocan', 'Las Piñas', 'Makati', 'Malabon', 'Mandaluyong',
    'Marikina', 'Muntinlupa', 'Navotas', 'Parañaque', 'Pasay', 'Pasig', 'San Juan',
    'Taguig', 'Valenzuela', 'Pateros', 'Baguio', 'Dagupan', 'San Fernando (La Union)',
    'Urdaneta', 'San Fernando (Pampanga)', 'Angeles', 'Olongapo', 'Batangas City',
    'Lipa', 'Tagaytay', 'Cavite City', 'Trece Martires', 'Bacoor', 'Dasmariñas',
    'Imus', 'Calamba', 'San Pablo', 'Lucena', 'Antipolo', 'Taytay', 'Cainta',
    'San Jose del Monte', 'Malolos', 'Meycauayan', 'Baliuag', 'Cabanatuan', 'Gapan',
    'Muñoz', 'Palayan', 'San Jose', 'Tarlac City', 'Cabanatuan', 'Olongapo',
    'Iloilo City', 'Bacolod', 'Roxas', 'Tagbilaran', 'Cebu City', 'Lapu-Lapu',
    'Mandaue', 'Talisay', 'Toledo', 'Dumaguete', 'Tacloban', 'Ormoc', 'Calbayog',
    'Catbalogan', 'Zamboanga City', 'Dipolog', 'Dapitan', 'Pagadian', 'Iligan',
    'Cagayan de Oro', 'Gingoog', 'Oroquieta', 'Ozamiz', 'Tangub', 'Davao City',
    'Digos', 'Mati', 'Panabo', 'Tagum', 'Island Garden City of Samal', 'General Santos',
    'Koronadal', 'Kidapawan', 'Cotabato City', 'Butuan', 'Cabadbaran', 'Surigao City',
    'Tandag', 'Bayugan', 'Bislig'
];

$dostNarrativeFields = [
    'introduction' => ['label' => 'Introduction', 'min' => 1, 'max' => 500],
    'executive_summary' => ['label' => 'Executive Summary', 'min' => 1, 'max' => 300],
    'rationale_significance' => ['label' => 'Rationale & Significance', 'min' => 1, 'max' => 500],
    'scientific_basis' => ['label' => 'Scientific Basis / Theoretical Framework', 'min' => 1, 'max' => 500],
    'objectives' => ['label' => 'Objectives', 'min' => 1, 'max' => 300],
    'review_of_literature' => ['label' => 'Review of Literature', 'min' => 1, 'max' => 500],
    'methodology' => ['label' => 'Methodology', 'min' => 1, 'max' => 500],
    'technology_roadmap' => ['label' => 'Technology Roadmap', 'min' => 1, 'max' => 300],
    'expected_outputs' => ['label' => 'Expected Outputs', 'min' => 1, 'max' => 300],
    'potential_outcomes' => ['label' => 'Potential Outcomes', 'min' => 1, 'max' => 300],
    'potential_impacts' => ['label' => 'Potential Impacts', 'min' => 1, 'max' => 300],
    'target_beneficiaries' => ['label' => 'Target Beneficiaries', 'min' => 1, 'max' => 300],
    'sustainability_plan' => ['label' => 'Sustainability Plan', 'min' => 1, 'max' => 500],
    'limitations' => ['label' => 'Limitations of the Project', 'min' => 1, 'max' => 300],
    'risks_assumptions' => ['label' => 'List of Risks and Assumptions', 'min' => 1, 'max' => 300],
    'literature_cited' => ['label' => 'Literature Cited', 'min' => 1, 'max' => 99999], // No practical limit
];

$dostFormData = $_SESSION['dost_form_data'] ?? [];
if ($dostFormData) {
    unset($_SESSION['dost_form_data']);
}

function old_value(string $key, string $default = ''): string
{
    global $dostFormData;
    return sanitize($dostFormData[$key] ?? $default);
}

function old_array_value(string $key, int $index): string
{
    global $dostFormData;
    return sanitize($dostFormData[$key][$index] ?? '');
}

function has_min_words(string $text, int $min = 300, int $max = 500): array
{
    $wordCount = str_word_count(strip_tags($text));
    return [
        'valid' => $wordCount >= $min && $wordCount <= $max,
        'count' => $wordCount,
        'min' => $min,
        'max' => $max
    ];
}

if (is_post() && isset($_POST['apply_grant_id'])) {
    $formContext = $_POST['form_context'] ?? 'generic';
    $grantId = (int) $_POST['apply_grant_id'];
    $grant = fetch_grant_with_requirements($grantId);

    if (!$grant) {
        flash('error', 'Grant not found.');
        redirect('available-grants.php');
    }

    if ($formContext === 'dost_grant' && $grant['slug'] === $dostGrantSlug) {
        $form = $_POST;
        $errors = [];

        $requiredFields = [
            'program_title' => 'Program Title',
            'project_title' => 'Project Title',
            'project_leader' => 'Project Leader',
            'email' => 'Email',
            'sex' => 'Sex',
            'implementing_agency' => 'Implementing Agency',
            'type_of_research' => 'Type of Research',
            'rd_priority_area_program' => 'R&D Priority Area & Program',
            'sustainable_development_goal' => 'Sustainable Development Goal Addressed',
            'dost_pillars_pursued' => 'DOST Pillars Pursued',
            'dost_thematic_areas' => 'DOST Thematic Areas Covered',
            'dost_strategic_program' => 'Applicable DOST Strategic Program',
            'gad_score' => 'Gender and Development (GAD) Score',
            'submitted_by' => 'Submitted By',
        ];

        foreach ($requiredFields as $key => $label) {
            if (empty(trim($form[$key] ?? ''))) {
                $errors[] = "{$label} is required.";
            }
        }

        if (!filter_var($form['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if ((float) ($form['requested_amount'] ?? 0) <= 0) {
            $errors[] = 'Requested amount must be greater than zero.';
        }

        foreach ($dostNarrativeFields as $field => $config) {
            $text = $form[$field] ?? '';
            $wordCount = str_word_count(strip_tags($text));
            $isLiteratureCited = ($field === 'literature_cited');
            
            // Check minimum word count
            if ($wordCount < $config['min']) {
                $errors[] = "{$config['label']} must contain at least {$config['min']} words. Currently: {$wordCount} words.";
            }
            // Check maximum word count (skip for Literature Cited)
            elseif (!$isLiteratureCited && $wordCount > $config['max']) {
                $errors[] = "{$config['label']} must not exceed {$config['max']} words. Currently: {$wordCount} words.";
            }
        }

        $siteRows = max(count($form['site_number'] ?? []), count($form['site_country'] ?? []), 1);
        $validSites = 0;
        for ($i = 0; $i < $siteRows; $i++) {
            $country = trim($form['site_country'][$i] ?? '');
            $region = trim($form['site_region'][$i] ?? '');
            $province = trim($form['site_province'][$i] ?? '');
            $municipality = trim($form['site_municipality'][$i] ?? '');
            if ($country || $region || $province || $municipality) {
                $validSites++;
            }
        }
        if ($validSites === 0) {
            $errors[] = 'Please add at least one implementing site.';
        }

        $personnelRows = max(count($form['personnel_position'] ?? []), 1);
        $validPersonnel = 0;
        for ($i = 0; $i < $personnelRows; $i++) {
            if (trim($form['personnel_position'][$i] ?? '') !== '') {
                $validPersonnel++;
            }
        }
        if ($validPersonnel === 0) {
            $errors[] = 'Please provide at least one personnel requirement.';
        }

        $budgetRows = max(count($form['budget_year_label'] ?? []), 1);
        $validBudget = 0;
        for ($i = 0; $i < $budgetRows; $i++) {
            $year = trim($form['budget_year_label'][$i] ?? '');
            $agency = trim($form['budget_agency'][$i] ?? '');
            if ($year || $agency) {
                $validBudget++;
            }
        }
        if ($validBudget === 0) {
            $errors[] = 'Please add at least one budget allocation row.';
        }

        $existingStmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE user_id = :user_id AND grant_id = :grant_id');
        $existingStmt->execute(['user_id' => $user['id'], 'grant_id' => $grantId]);
        if ($existingStmt->fetchColumn()) {
            $errors[] = 'You already submitted an application for this grant.';
        }

        if ($errors) {
            $_SESSION['dost_form_data'] = $form;
            flash('error', implode(' ', $errors));
            redirect('available-grants.php?grant_id=' . $grantId);
        }

        try {
            $pdo->beginTransaction();
            $applicationCode = generate_application_code();
            $statusId = application_status_id('submitted');
            $insertApp = $pdo->prepare('INSERT INTO applications (application_code, user_id, grant_id, project_title, project_summary, requested_amount, status_id, submitted_at) VALUES (:code, :user_id, :grant_id, :title, :summary, :amount, :status_id, NOW())');
            $insertApp->execute([
                'code' => $applicationCode,
                'user_id' => $user['id'],
                'grant_id' => $grantId,
                'title' => trim($form['project_title']),
                'summary' => trim($form['executive_summary']),
                'amount' => (float) $form['requested_amount'],
                'status_id' => $statusId,
            ]);
            $applicationId = (int) $pdo->lastInsertId();

            // Handle "Other" option for location fields
            $city = trim($form['city'] ?? '');
            if ($city === 'Other' && !empty(trim($form['city_other'] ?? ''))) {
                $city = trim($form['city_other']);
            }
            
            $province = trim($form['province'] ?? '');
            if ($province === 'Other' && !empty(trim($form['province_other'] ?? ''))) {
                $province = trim($form['province_other']);
            }
            
            $region = trim($form['region'] ?? '');
            if ($region === 'Other' && !empty(trim($form['region_other'] ?? ''))) {
                $region = trim($form['region_other']);
            }
            
            $country = trim($form['country'] ?? 'Philippines');
            if ($country === 'Other' && !empty(trim($form['country_other'] ?? ''))) {
                $country = trim($form['country_other']);
            }
            
            $detailsStmt = $pdo->prepare('INSERT INTO dost_application_details (
                application_id, program_title, project_title, project_leader, email, sex, telephone,
                house_number, street_name, barangay, city, district, province, region, country,
                implementing_agency, cooperating_agency, type_of_research, rd_priority_area_program,
                sustainable_development_goal, dost_pillars_pursued, dost_thematic_areas,
                dost_strategic_program, introduction, executive_summary, rationale_significance,
                scientific_basis, objectives, review_of_literature, methodology, technology_roadmap,
                expected_outputs, potential_outcomes, potential_impacts, target_beneficiaries,
                sustainability_plan, gad_score, limitations, risks_assumptions, literature_cited,
                submitted_by, endorsed_by, remarks
            ) VALUES (
                :application_id, :program_title, :project_title, :project_leader, :email, :sex, :telephone,
                :house_number, :street_name, :barangay, :city, :district, :province, :region, :country,
                :implementing_agency, :cooperating_agency, :type_of_research, :rd_priority_area_program,
                :sustainable_development_goal, :dost_pillars_pursued, :dost_thematic_areas,
                :dost_strategic_program, :introduction, :executive_summary, :rationale_significance,
                :scientific_basis, :objectives, :review_of_literature, :methodology, :technology_roadmap,
                :expected_outputs, :potential_outcomes, :potential_impacts, :target_beneficiaries,
                :sustainability_plan, :gad_score, :limitations, :risks_assumptions, :literature_cited,
                :submitted_by, :endorsed_by, :remarks
            )');
            $detailsStmt->execute([
                'application_id' => $applicationId,
                'program_title' => trim($form['program_title']),
                'project_title' => trim($form['project_title']),
                'project_leader' => trim($form['project_leader']),
                'email' => trim($form['email']),
                'sex' => trim($form['sex']),
                'telephone' => trim($form['telephone'] ?? ''),
                'house_number' => trim($form['house_number'] ?? ''),
                'street_name' => trim($form['street_name'] ?? ''),
                'barangay' => trim($form['barangay'] ?? ''),
                'city' => $city,
                'district' => trim($form['district'] ?? ''),
                'province' => $province,
                'region' => $region,
                'country' => $country,
                'implementing_agency' => trim($form['implementing_agency']),
                'cooperating_agency' => trim($form['cooperating_agency'] ?? ''),
                'type_of_research' => trim($form['type_of_research']),
                'rd_priority_area_program' => trim($form['rd_priority_area_program']),
                'sustainable_development_goal' => trim($form['sustainable_development_goal']),
                'dost_pillars_pursued' => trim($form['dost_pillars_pursued']),
                'dost_thematic_areas' => trim($form['dost_thematic_areas']),
                'dost_strategic_program' => trim($form['dost_strategic_program']),
                'introduction' => trim($form['introduction']),
                'executive_summary' => trim($form['executive_summary']),
                'rationale_significance' => trim($form['rationale_significance']),
                'scientific_basis' => trim($form['scientific_basis']),
                'objectives' => trim($form['objectives']),
                'review_of_literature' => trim($form['review_of_literature']),
                'methodology' => trim($form['methodology']),
                'technology_roadmap' => trim($form['technology_roadmap']),
                'expected_outputs' => trim($form['expected_outputs']),
                'potential_outcomes' => trim($form['potential_outcomes']),
                'potential_impacts' => trim($form['potential_impacts']),
                'target_beneficiaries' => trim($form['target_beneficiaries']),
                'sustainability_plan' => trim($form['sustainability_plan']),
                'gad_score' => trim($form['gad_score']),
                'limitations' => trim($form['limitations']),
                'risks_assumptions' => trim($form['risks_assumptions']),
                'literature_cited' => trim($form['literature_cited']),
                'submitted_by' => trim($form['submitted_by']),
                'endorsed_by' => trim($form['endorsed_by'] ?? ''),
                'remarks' => trim($form['remarks'] ?? ''),
            ]);

            $siteStmt = $pdo->prepare('INSERT INTO dost_application_sites (application_id, site_number, country, region, province, district, municipality, barangay) VALUES (:application_id, :site_number, :country, :region, :province, :district, :municipality, :barangay)');
            for ($i = 0; $i < $siteRows; $i++) {
                $country = trim($form['site_country'][$i] ?? '');
                $region = trim($form['site_region'][$i] ?? '');
                $province = trim($form['site_province'][$i] ?? '');
                $district = trim($form['site_district'][$i] ?? '');
                $municipality = trim($form['site_municipality'][$i] ?? '');
                $barangay = trim($form['site_barangay'][$i] ?? '');
                $siteNo = trim($form['site_number'][$i] ?? '');
                if (!$country && !$region && !$province && !$municipality) {
                    continue;
                }
                $siteStmt->execute([
                    'application_id' => $applicationId,
                    'site_number' => $siteNo,
                    'country' => $country,
                    'region' => $region,
                    'province' => $province,
                    'district' => $district,
                    'municipality' => $municipality,
                    'barangay' => $barangay,
                ]);
            }

            $personnelStmt = $pdo->prepare('INSERT INTO dost_personnel_requirements (application_id, position, quantity, percent_time, responsibility) VALUES (:application_id, :position, :quantity, :percent_time, :responsibility)');
            for ($i = 0; $i < $personnelRows; $i++) {
                $position = trim($form['personnel_position'][$i] ?? '');
                if ($position === '') {
                    continue;
                }
                $personnelStmt->execute([
                    'application_id' => $applicationId,
                    'position' => $position,
                    'quantity' => trim($form['personnel_quantity'][$i] ?? ''),
                    'percent_time' => trim($form['personnel_percent'][$i] ?? ''),
                    'responsibility' => trim($form['personnel_responsibility'][$i] ?? ''),
                ]);
            }

            $budgetStmt = $pdo->prepare('INSERT INTO dost_budget_allocations (application_id, year_label, agency, ps_dost, ps_counterpart, mooe_dost, mooe_counterpart, co_dost, co_counterpart, total_dost, total_counterpart) VALUES (:application_id, :year_label, :agency, :ps_dost, :ps_counterpart, :mooe_dost, :mooe_counterpart, :co_dost, :co_counterpart, :total_dost, :total_counterpart)');
            for ($i = 0; $i < $budgetRows; $i++) {
                $year = trim($form['budget_year_label'][$i] ?? '');
                $agency = trim($form['budget_agency'][$i] ?? '');
                if ($year === '' && $agency === '') {
                    continue;
                }
                $psDost = (float) ($form['budget_ps_dost'][$i] ?? 0);
                $psCounterpart = (float) ($form['budget_ps_counterpart'][$i] ?? 0);
                $mooeDost = (float) ($form['budget_mooe_dost'][$i] ?? 0);
                $mooeCounterpart = (float) ($form['budget_mooe_counterpart'][$i] ?? 0);
                $coDost = (float) ($form['budget_co_dost'][$i] ?? 0);
                $coCounterpart = (float) ($form['budget_co_counterpart'][$i] ?? 0);
                
                // Calculate totals if not provided
                $totalDost = (float) ($form['budget_total_dost'][$i] ?? ($psDost + $mooeDost + $coDost));
                $totalCounterpart = (float) ($form['budget_total_counterpart'][$i] ?? ($psCounterpart + $mooeCounterpart + $coCounterpart));
                
                $budgetStmt->execute([
                    'application_id' => $applicationId,
                    'year_label' => $year ?: 'Year ' . ($i + 1),
                    'agency' => $agency ?: 'N/A',
                    'ps_dost' => $psDost,
                    'ps_counterpart' => $psCounterpart,
                    'mooe_dost' => $mooeDost,
                    'mooe_counterpart' => $mooeCounterpart,
                    'co_dost' => $coDost,
                    'co_counterpart' => $coCounterpart,
                    'total_dost' => $totalDost,
                    'total_counterpart' => $totalCounterpart,
                ]);
            }

            $historyStmt = $pdo->prepare('INSERT INTO application_status_history (application_id, status_id, remarks, created_by) VALUES (:application_id, :status_id, :remarks, :created_by)');
            $historyStmt->execute([
                'application_id' => $applicationId,
                'status_id' => $statusId,
                'remarks' => 'Application submitted via founder portal.',
                'created_by' => $user['id'],
            ]);

            $pdo->commit();
            unset($_SESSION['dost_form_data']);
            flash('success', 'Application submitted! Track it under My Applications.');
            redirect('my-applications.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Unable to submit application. Please try again.');
            redirect('available-grants.php?grant_id=' . $grantId);
        }
    } else {
        $projectTitle = trim($_POST['project_title'] ?? '');
        $projectSummary = trim($_POST['project_summary'] ?? '');
        $requestedAmount = (float) ($_POST['requested_amount'] ?? 0);
        $errors = [];

        if ($projectTitle === '') {
            $errors[] = 'Project title is required.';
        }
        if ($projectSummary === '') {
            $errors[] = 'Project summary is required.';
        }
        if ($requestedAmount <= 0) {
            $errors[] = 'Requested amount must be greater than zero.';
        }

        $existingStmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE user_id = :user_id AND grant_id = :grant_id');
        $existingStmt->execute(['user_id' => $user['id'], 'grant_id' => $grantId]);
        if ($existingStmt->fetchColumn()) {
            $errors[] = 'You already submitted an application for this grant.';
        }

        $requirementPayloads = [];
        foreach ($grant['requirements'] as $requirement) {
            $fieldName = 'requirement_' . $requirement['id'];
            if ($requirement['requirement_type'] === 'document') {
                $file = $_FILES[$fieldName] ?? null;
                if ($requirement['is_required'] && (!$file || $file['error'] === UPLOAD_ERR_NO_FILE)) {
                    $errors[] = 'Please upload ' . $requirement['title'] . '.';
                    continue;
                }
                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    if ($file['size'] > 10 * 1024 * 1024) {
                        $errors[] = $requirement['title'] . ' exceeds the 10MB limit.';
                        continue;
                    }
                    $requirementPayloads[] = ['type' => 'file', 'requirement' => $requirement, 'file' => $file];
                }
            } else {
                $value = trim($_POST[$fieldName] ?? '');
                if ($requirement['is_required'] && $value === '') {
                    $errors[] = $requirement['title'] . ' is required.';
                    continue;
                }
                if ($value !== '') {
                    $requirementPayloads[] = ['type' => 'text', 'requirement' => $requirement, 'value' => $value];
                }
            }
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('available-grants.php?grant_id=' . $grantId);
        }

        $pdo->beginTransaction();
        $savedFiles = [];
        try {
            global $config;
            $applicationCode = generate_application_code();
            $statusId = application_status_id('submitted');
            $insertApp = $pdo->prepare('INSERT INTO applications (application_code, user_id, grant_id, project_title, project_summary, requested_amount, status_id, submitted_at) VALUES (:code, :user_id, :grant_id, :title, :summary, :amount, :status_id, NOW())');
            $insertApp->execute([
                'code' => $applicationCode,
                'user_id' => $user['id'],
                'grant_id' => $grantId,
                'title' => $projectTitle,
                'summary' => $projectSummary,
                'amount' => $requestedAmount,
                'status_id' => $statusId,
            ]);
            $applicationId = (int) $pdo->lastInsertId();

            $insertDoc = $pdo->prepare('INSERT INTO application_documents (application_id, requirement_id, original_filename, stored_filename, mime_type, file_size) VALUES (:application_id, :requirement_id, :original_filename, :stored_filename, :mime_type, :file_size)');
            foreach ($requirementPayloads as $payload) {
                $requirement = $payload['requirement'];
                $storedName = strtolower($applicationCode . '_' . $requirement['requirement_code'] . '_' . time());
                if ($payload['type'] === 'file') {
                    $extension = pathinfo($payload['file']['name'], PATHINFO_EXTENSION);
                    if ($extension) {
                        $storedName .= '.' . preg_replace('/[^a-zA-Z0-9]/', '', $extension);
                    }
                    $targetPath = $config['uploads_path'] . DIRECTORY_SEPARATOR . $storedName;
                    if (!move_uploaded_file($payload['file']['tmp_name'], $targetPath)) {
                        throw new RuntimeException('Failed to save ' . $requirement['title']);
                    }
                    $savedFiles[] = $targetPath;
                    $insertDoc->execute([
                        'application_id' => $applicationId,
                        'requirement_id' => $requirement['id'],
                        'original_filename' => $payload['file']['name'],
                        'stored_filename' => basename($storedName),
                        'mime_type' => $payload['file']['type'],
                        'file_size' => $payload['file']['size'],
                    ]);
                } else {
                    $storedName .= '.txt';
                    $targetPath = $config['uploads_path'] . DIRECTORY_SEPARATOR . $storedName;
                    if (file_put_contents($targetPath, $payload['value']) === false) {
                        throw new RuntimeException('Failed to store response for ' . $requirement['title']);
                    }
                    $savedFiles[] = $targetPath;
                    $insertDoc->execute([
                        'application_id' => $applicationId,
                        'requirement_id' => $requirement['id'],
                        'original_filename' => $requirement['title'] . '.txt',
                        'stored_filename' => basename($storedName),
                        'mime_type' => 'text/plain',
                        'file_size' => strlen($payload['value']),
                    ]);
                }
            }

            $historyStmt = $pdo->prepare('INSERT INTO application_status_history (application_id, status_id, remarks, created_by) VALUES (:application_id, :status_id, :remarks, :created_by)');
            $historyStmt->execute([
                'application_id' => $applicationId,
                'status_id' => $statusId,
                'remarks' => 'Application submitted via founder portal.',
                'created_by' => $user['id'],
            ]);

            $pdo->commit();
            flash('success', 'Application submitted! Track it under My Applications.');
            redirect('my-applications.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            foreach ($savedFiles as $filePath) {
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
            flash('error', 'Unable to submit application. Please try again.');
            redirect('available-grants.php?grant_id=' . $grantId);
        }
    }
}

$flashSuccess = flash('success');
$flashError = flash('error');

$siteRowCount = max(
    1,
    count($dostFormData['site_number'] ?? []),
    count($dostFormData['site_country'] ?? []),
    count($dostFormData['site_region'] ?? []),
    count($dostFormData['site_municipality'] ?? [])
);
$personnelRowCount = max(
    1,
    count($dostFormData['personnel_position'] ?? []),
    count($dostFormData['personnel_quantity'] ?? []),
    count($dostFormData['personnel_percent'] ?? []),
    count($dostFormData['personnel_responsibility'] ?? [])
);
$budgetRowCount = max(
    1,
    count($dostFormData['budget_year_label'] ?? []),
    count($dostFormData['budget_agency'] ?? [])
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Grants - Founder | <?php echo sanitize(app_name()); ?></title>
    <link rel="stylesheet" href="../css/shared.css">
    <link rel="stylesheet" href="../css/founder-styles.css">
    <link rel="stylesheet" href="../css/application-form.css">
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
                <li><a href="available-grants.php" class="menu-link active">🎯 Available Grants</a></li>
                <li><a href="my-applications.php" class="menu-link">📄 My Applications</a></li>
                <li><a href="notifications.php" class="menu-link">🔔 Notifications</a></li>
                <li><a href="profile.php" class="menu-link">👤 Profile</a></li>
                <li><a href="<?php echo base_path('auth/logout.php'); ?>">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <div class="content">
            <div class="header">
                <h1>Available Grants</h1>
                <div class="user-info">
                    <span><?php echo sanitize($user['full_name']); ?></span>
                    <a class="logout-btn" href="<?php echo base_path('auth/logout.php'); ?>">Logout</a>
                </div>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="alert success"><?php echo sanitize($flashSuccess); ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert error"><?php echo sanitize($flashError); ?></div>
            <?php endif; ?>

            <?php if (!$selectedGrant): ?>
                <div class="grants-grid">
                    <?php if (!$grants): ?>
                        <div class="no-data">No grants available yet.</div>
                    <?php else: ?>
                        <?php foreach ($grants as $grant): ?>
                            <?php $hasApplied = isset($existingApplications[$grant['id']]); ?>
                            <div class="grant-card">
                                <div class="grant-title"><?php echo sanitize($grant['name']); ?></div>
                                <div class="grant-amount">
                                    <?php echo $grant['max_funding'] ? '₱' . number_format($grant['max_funding'], 2) : 'Funding TBD'; ?>
                                </div>
                                <div class="grant-desc"><?php echo sanitize($grant['description']); ?></div>
                                <div class="grant-meta">
                                    <span>Deadline: <?php echo $grant['deadline'] ? date('M d, Y', strtotime($grant['deadline'])) : 'Rolling'; ?></span>
                                    <span>Status: <?php echo ucfirst($grant['status']); ?></span>
                                </div>
                                <div class="grant-actions">
                                    <?php if ($hasApplied): ?>
                                        <button class="btn-apply" disabled>Already Applied</button>
                                    <?php else: ?>
                                        <a class="btn-apply" href="available-grants.php?grant_id=<?php echo (int) $grant['id']; ?>">
                                            Apply Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($selectedGrant): ?>
                <?php $alreadyApplied = isset($existingApplications[$selectedGrant['id']]); ?>
                <div class="card" style="margin-top:30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h2 style="color:#5a3fa3; margin: 0;">Apply for <?php echo sanitize($selectedGrant['name']); ?></h2>
                            <p style="margin: 5px 0 0 0; color: #7d6b8f;"><?php echo sanitize($selectedGrant['description']); ?></p>
                        </div>
                        <a href="available-grants.php" style="padding: 8px 16px; background: #a8c5e0; color: white; text-decoration: none; border-radius: 6px; font-size: 14px;">← Back to Grants</a>
                    </div>

                    <?php if ($alreadyApplied): ?>
                        <div class="alert error">You already have an application for this grant.</div>
                    <?php else: ?>
                        <?php if ($selectedGrant['slug'] === $dostGrantSlug): ?>
                            <form method="post" id="dostApplicationForm" novalidate style="margin-top:20px;">
                                <input type="hidden" name="apply_grant_id" value="<?php echo (int) $selectedGrant['id']; ?>">
                                <input type="hidden" name="form_context" value="dost_grant">
                                
                                <!-- Step Indicator -->
                                <div class="form-steps">
                                    <div class="step-indicator">
                                        <div class="step active" data-step="1">Step 1: Personal Information</div>
                                        <div class="step" data-step="2">Step 2: Project Details</div>
                                        <div class="step" data-step="3">Step 3: Personnel</div>
                                        <div class="step" data-step="4">Step 4: Budget</div>
                                        <div class="step" data-step="5">Step 5: Submission</div>
                                    </div>
                                    
                                    <!-- STEP 1: Personal Information to Applicable DOST Strategic Program -->
                                    <div class="step-content active" id="step1">
                                        <h3 style="color: #5a3fa3; margin-bottom: 20px; border-bottom: 2px solid #e8d9f0; padding-bottom: 10px;">Step 1: Personal Information & DOST Alignment</h3>

                                        <div class="grid-2" style="gap:20px;">
                                            <div class="form-group">
                                                <label>Program Title *</label>
                                                <input type="text" name="program_title" value="<?php echo old_value('program_title'); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Project Title *</label>
                                                <input type="text" name="project_title" value="<?php echo old_value('project_title'); ?>" required>
                                            </div>
                                        </div>

                                        <div class="grid-2" style="gap:20px;">
                                            <div class="form-group">
                                                <label>Requested Amount (PHP) *</label>
                                                <input type="number" name="requested_amount" value="<?php echo old_value('requested_amount'); ?>" min="1" step="0.01" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Type of Research *</label>
                                                <select name="type_of_research" required style="width: 100%; padding: 12px; border: 2px solid #e8d9f0; border-radius: 10px;">
                                                    <option value="">Select Type</option>
                                                    <option value="Basic Research" <?php echo old_value('type_of_research') === 'Basic Research' ? 'selected' : ''; ?>>Basic Research</option>
                                                    <option value="Applied Research" <?php echo old_value('type_of_research') === 'Applied Research' ? 'selected' : ''; ?>>Applied Research</option>
                                                    <option value="Development" <?php echo old_value('type_of_research') === 'Development' ? 'selected' : ''; ?>>Development</option>
                                                    <option value="Technology Transfer" <?php echo old_value('type_of_research') === 'Technology Transfer' ? 'selected' : ''; ?>>Technology Transfer</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid-2" style="gap:20px;">
                                            <div class="form-group">
                                                <label>Project Leader *</label>
                                                <input type="text" name="project_leader" value="<?php echo old_value('project_leader'); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Email *</label>
                                                <input type="email" name="email" value="<?php echo old_value('email'); ?>" required>
                                            </div>
                                        </div>

                                        <div class="grid-2" style="gap:20px;">
                                            <div class="form-group">
                                                <label>Sex *</label>
                                                <select name="sex" required style="width: 100%; padding: 12px; border: 2px solid #e8d9f0; border-radius: 10px; background: white; cursor: pointer;">
                                                    <option value="">Select</option>
                                                    <option value="Male" <?php echo old_value('sex') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo old_value('sex') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Prefer not to say" <?php echo old_value('sex') === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Telephone</label>
                                                <input type="text" name="telephone" value="<?php echo old_value('telephone'); ?>" placeholder="+63 XXX XXX XXXX">
                                            </div>
                                        </div>

                                        <h4 style="color: #5a3fa3; margin: 25px 0 15px;">Address Information</h4>
                                        <div class="grid-3" style="gap:20px;">
                                            <div class="form-group">
                                                <label>House Number</label>
                                                <input type="text" name="house_number" value="<?php echo old_value('house_number'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Street Name</label>
                                                <input type="text" name="street_name" value="<?php echo old_value('street_name'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Barangay</label>
                                                <input type="text" name="barangay" value="<?php echo old_value('barangay'); ?>">
                                            </div>
                                        </div>

                                        <div class="grid-3" style="gap:20px;">
                                            <div class="form-group">
                                                <label>City / Municipality *</label>
                                                <select name="city" style="width: 100%; padding: 12px; border: 2px solid #e8d9f0; border-radius: 10px; background: white; cursor: pointer;">
                                                    <option value="">Select City</option>
                                                    <?php foreach ($philippineCities as $city): ?>
                                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo old_value('city') === $city ? 'selected' : ''; ?>><?php echo htmlspecialchars($city); ?></option>
                                                    <?php endforeach; ?>
                                                    <option value="Other">Other (specify below)</option>
                                                </select>
                                                <input type="text" name="city_other" value="<?php echo old_value('city_other'); ?>" placeholder="If Other, specify here" style="margin-top: 8px; display: none;" id="cityOtherInput">
                                            </div>
                                            <div class="form-group">
                                                <label>District</label>
                                                <input type="text" name="district" value="<?php echo old_value('district'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Province *</label>
                                                <select name="province" style="width: 100%; padding: 12px; border: 2px solid #e8d9f0; border-radius: 10px; background: white; cursor: pointer;">
                                                    <option value="">Select Province</option>
                                                    <?php foreach ($philippineProvinces as $province): ?>
                                                        <option value="<?php echo htmlspecialchars($province); ?>" <?php echo old_value('province') === $province ? 'selected' : ''; ?>><?php echo htmlspecialchars($province); ?></option>
                                                    <?php endforeach; ?>
                                                    <option value="Other">Other (specify below)</option>
                                                </select>
                                                <input type="text" name="province_other" value="<?php echo old_value('province_other'); ?>" placeholder="If Other, specify here" style="margin-top: 8px; display: none;" id="provinceOtherInput">
                                            </div>
                                        </div>

                                        <div class="grid-2" style="gap:20px;">
                                            <div class="form-group">
                                                <label>Region *</label>
                                                <select name="region" style="width: 100%; padding: 12px; border: 2px solid #e8d9f0; border-radius: 10px; background: white; cursor: pointer;">
                                                    <option value="">Select Region</option>
                                                    <?php foreach ($philippineRegions as $region): ?>
                                                        <option value="<?php echo htmlspecialchars($region); ?>" <?php echo old_value('region') === $region ? 'selected' : ''; ?>><?php echo htmlspecialchars($region); ?></option>
                                                    <?php endforeach; ?>
                                                    <option value="Other">Other (specify below)</option>
                                                </select>
                                                <input type="text" name="region_other" value="<?php echo old_value('region_other'); ?>" placeholder="If Other, specify here" style="margin-top: 8px; display: none;" id="regionOtherInput">
                                            </div>
                                            <div class="form-group">
                                                <label>Country *</label>
                                                <select name="country" required style="width: 100%; padding: 12px; border: 2px solid #e8d9f0; border-radius: 10px; background: white; cursor: pointer;">
                                                    <option value="">Select Country</option>
                                                    <option value="Philippines" <?php echo old_value('country', 'Philippines') === 'Philippines' ? 'selected' : ''; ?>>Philippines</option>
                                                    <option value="Other">Other (specify below)</option>
                                                </select>
                                                <input type="text" name="country_other" value="<?php echo old_value('country_other'); ?>" placeholder="If Other, specify here" style="margin-top: 8px; display: none;" id="countryOtherInput">
                                            </div>
                                        </div>

                                        <div class="grid-2" style="gap:20px; margin-top: 20px;">
                                            <div class="form-group">
                                                <label>Implementing Agency *</label>
                                                <input type="text" name="implementing_agency" value="<?php echo old_value('implementing_agency'); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Cooperating Agency</label>
                                                <input type="text" name="cooperating_agency" value="<?php echo old_value('cooperating_agency'); ?>">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>R&D Priority Area and Program *</label>
                                            <textarea name="rd_priority_area_program" rows="3" required><?php echo old_value('rd_priority_area_program'); ?></textarea>
                                        </div>

                                        <div class="grid-2" style="gap:20px;">
                                            <div class="form-group">
                                                <label>Sustainable Development Goal Addressed *</label>
                                                <textarea name="sustainable_development_goal" rows="3" required><?php echo old_value('sustainable_development_goal'); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>DOST Pillars Pursued *</label>
                                                <textarea name="dost_pillars_pursued" rows="3" required><?php echo old_value('dost_pillars_pursued'); ?></textarea>
                                            </div>
                                        </div>

                                        <div class="grid-2" style="gap:20px;">
                                            <div class="form-group">
                                                <label>DOST Thematic Areas Covered *</label>
                                                <textarea name="dost_thematic_areas" rows="3" required><?php echo old_value('dost_thematic_areas'); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Applicable DOST Strategic Program *</label>
                                                <textarea name="dost_strategic_program" rows="3" required><?php echo old_value('dost_strategic_program'); ?></textarea>
                                            </div>
                                        </div>

                                        <div style="display: flex; gap: 10px; margin-top: 30px;">
                                            <button type="button" class="btn-submit" id="nextToStep2" style="flex: 1;">Next: Project Details →</button>
                                        </div>
                                    </div>
                                    
                                    <!-- STEP 2: GAD Score to Implementing Sites -->
                                    <div class="step-content" id="step2" style="display: none;">
                                        <h3 style="color: #5a3fa3; margin-bottom: 20px; border-bottom: 2px solid #e8d9f0; padding-bottom: 10px;">Step 2: Project Details & Implementation Sites</h3>
                                        
                                        <div class="form-group">
                                            <label>Gender and Development (GAD) Score *</label>
                                            <input type="number" name="gad_score" value="<?php echo old_value('gad_score'); ?>" min="0" max="100" required style="width: 200px;">
                                            <small style="display: block; color: #7d6b8f; margin-top: 5px;">Enter a score from 0 to 100</small>
                                        </div>

                                        <div class="alert info" style="margin: 20px 0;">
                                            <strong>Narrative Requirements:</strong> Each section below has specific word count requirements (minimum 1 word, maximum varies by field). Word counts are displayed as you type. Literature Cited has no maximum limit.
                                        </div>

                                        <?php foreach ($dostNarrativeFields as $field => $config): ?>
                                            <div class="form-group">
                                                <label><?php echo $config['label']; ?> * 
                                                    <?php if ($field === 'literature_cited'): ?>
                                                        <small style="color: #7d6b8f;">(No word limit)</small>
                                                    <?php else: ?>
                                                        <small style="color: #7d6b8f;">(<?php echo $config['min']; ?>-<?php echo $config['max']; ?> words)</small>
                                                    <?php endif; ?>
                                                </label>
                                                <textarea name="<?php echo $field; ?>" rows="6" required data-min-words="<?php echo $config['min']; ?>" data-max-words="<?php echo $config['max']; ?>" data-field-name="<?php echo $field; ?>" class="narrative-field"><?php echo old_value($field); ?></textarea>
                                                <div class="word-count" data-field="<?php echo $field; ?>" style="font-size: 12px; color: #7d6b8f; margin-top: 5px;">0 words</div>
                                            </div>
                                        <?php endforeach; ?>

                                        <h4 style="color: #5a3fa3; margin: 30px 0 15px;">Site(s) of Implementation</h4>
                                        <div class="table-container" style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px;">
                                            <table class="dynamic-table" style="width: 100%; border-collapse: collapse;">
                                                <thead>
                                                    <tr style="background: #f9f7fc;">
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Site No.</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Country</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Region</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Province</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">District</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Municipality</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Barangay</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sitesTableBody">
                                                    <?php for ($i = 0; $i < $siteRowCount; $i++): ?>
                                                        <tr class="site-row">
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="site_number[]" value="<?php echo old_array_value('site_number', $i); ?>" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_country[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option><option value="Philippines" <?php echo old_array_value('site_country', $i) === 'Philippines' ? 'selected' : ''; ?>>Philippines</option><option value="Other">Other</option></select></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_region[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option><?php foreach ($philippineRegions as $reg): ?><option value="<?php echo htmlspecialchars($reg); ?>" <?php echo old_array_value('site_region', $i) === $reg ? 'selected' : ''; ?>><?php echo htmlspecialchars($reg); ?></option><?php endforeach; ?></select></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_province[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option><?php foreach ($philippineProvinces as $prov): ?><option value="<?php echo htmlspecialchars($prov); ?>" <?php echo old_array_value('site_province', $i) === $prov ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov); ?></option><?php endforeach; ?></select></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="site_district[]" value="<?php echo old_array_value('site_district', $i); ?>" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_municipality[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option><?php foreach ($philippineCities as $city): ?><option value="<?php echo htmlspecialchars($city); ?>" <?php echo old_array_value('site_municipality', $i) === $city ? 'selected' : ''; ?>><?php echo htmlspecialchars($city); ?></option><?php endforeach; ?></select></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="site_barangay[]" value="<?php echo old_array_value('site_barangay', $i); ?>" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><button type="button" class="remove-row" style="background: #ff9a9a; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">Remove</button></td>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn-small" id="addSiteRowBtn" style="background: #d4a5e8; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-bottom: 20px;">+ Add Site</button>

                                        <div style="display: flex; gap: 10px; margin-top: 30px;">
                                            <button type="button" class="btn-submit" data-prev-step="1" style="background: #a8c5e0; flex: 1;">← Previous</button>
                                            <button type="button" class="btn-submit" data-next-step="3" style="flex: 1;">Next: Personnel →</button>
                                        </div>
                                    </div>
                                    
                                    <!-- STEP 3: Personnel Requirements -->
                                    <div class="step-content" id="step3" style="display: none;">
                                        <h3 style="color: #5a3fa3; margin-bottom: 20px; border-bottom: 2px solid #e8d9f0; padding-bottom: 10px;">Step 3: Personnel Requirements</h3>
                                        
                                        <div class="table-container" style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px;">
                                            <table class="dynamic-table" style="width: 100%; border-collapse: collapse;">
                                                <thead>
                                                    <tr style="background: #f9f7fc;">
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Position</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Quantity</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">% Time Devoted</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Responsibility</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="personnelTableBody">
                                                    <?php for ($i = 0; $i < $personnelRowCount; $i++): ?>
                                                        <tr class="personnel-row">
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="personnel_position[]" value="<?php echo old_array_value('personnel_position', $i); ?>" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="personnel_quantity[]" value="<?php echo old_array_value('personnel_quantity', $i); ?>" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="personnel_percent[]" value="<?php echo old_array_value('personnel_percent', $i); ?>" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><textarea name="personnel_responsibility[]" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px; min-height: 50px; resize: vertical;"><?php echo old_array_value('personnel_responsibility', $i); ?></textarea></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><button type="button" class="remove-row" style="background: #ff9a9a; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">Remove</button></td>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn-small" id="addPersonnelRowBtn" style="background: #d4a5e8; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-bottom: 20px;">+ Add Personnel</button>

                                        <div style="display: flex; gap: 10px; margin-top: 30px;">
                                            <button type="button" class="btn-submit" data-prev-step="2" style="background: #a8c5e0; flex: 1;">← Previous</button>
                                            <button type="button" class="btn-submit" data-next-step="4" style="flex: 1;">Next: Budget →</button>
                                        </div>
                                    </div>
                                    
                                    <!-- STEP 4: Budget Allocation -->
                                    <div class="step-content" id="step4" style="display: none;">
                                        <h3 style="color: #5a3fa3; margin-bottom: 20px; border-bottom: 2px solid #e8d9f0; padding-bottom: 10px;">Step 4: Budget Allocation</h3>
                                        <p style="color: #7d6b8f; margin-bottom: 20px;">Credit Limit: 1.5M Philippine Pesos per year. Totals are calculated automatically.</p>
                                        
                                        <div class="table-container" style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; overflow-x: auto;">
                                            <table class="dynamic-table" style="width: 100%; border-collapse: collapse; font-size: 12px; min-width: 1200px;">
                                                <thead>
                                                    <tr style="background: #f9f7fc;">
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Year</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Agency</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">PS DOST</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">PS Counterpart</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">MOOE DOST</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">MOOE Counterpart</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">CO DOST</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">CO Counterpart</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600; background: #e8f5e9;">Total DOST</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600; background: #e8f5e9;">Total Counterpart</th>
                                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e8d9f0; color: #5a3fa3; font-weight: 600;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="budgetTableBody">
                                                    <?php for ($i = 0; $i < $budgetRowCount; $i++): ?>
                                                        <tr class="budget-row">
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="budget_year_label[]" value="<?php echo old_array_value('budget_year_label', $i, 'Year ' . ($i + 1)); ?>" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="budget_agency[]" value="<?php echo old_array_value('budget_agency', $i); ?>" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_ps_dost[]" value="<?php echo old_array_value('budget_ps_dost', $i); ?>" class="budget-input" data-type="dost" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_ps_counterpart[]" value="<?php echo old_array_value('budget_ps_counterpart', $i); ?>" class="budget-input" data-type="counterpart" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_mooe_dost[]" value="<?php echo old_array_value('budget_mooe_dost', $i); ?>" class="budget-input" data-type="dost" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_mooe_counterpart[]" value="<?php echo old_array_value('budget_mooe_counterpart', $i); ?>" class="budget-input" data-type="counterpart" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_co_dost[]" value="<?php echo old_array_value('budget_co_dost', $i); ?>" class="budget-input" data-type="dost" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_co_counterpart[]" value="<?php echo old_array_value('budget_co_counterpart', $i); ?>" class="budget-input" data-type="counterpart" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0; background: #e8f5e9;"><input type="number" step="0.01" name="budget_total_dost[]" readonly style="width: 100%; padding: 8px; border: 1px solid #c8e6c9; border-radius: 6px; background: #f1f8f4; font-weight: 600;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0; background: #e8f5e9;"><input type="number" step="0.01" name="budget_total_counterpart[]" readonly style="width: 100%; padding: 8px; border: 1px solid #c8e6c9; border-radius: 6px; background: #f1f8f4; font-weight: 600;"></td>
                                                            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><button type="button" class="remove-row" style="background: #ff9a9a; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">Remove</button></td>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn-small" id="addBudgetRowBtn" style="background: #d4a5e8; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-bottom: 20px;">+ Add Budget Row</button>

                                        <div style="display: flex; gap: 10px; margin-top: 30px;">
                                            <button type="button" class="btn-submit" data-prev-step="3" style="background: #a8c5e0; flex: 1;">← Previous</button>
                                            <button type="button" class="btn-submit" data-next-step="5" style="flex: 1;">Next: Submission →</button>
                                        </div>
                                    </div>
                                    
                                    <!-- STEP 5: Submission -->
                                    <div class="step-content" id="step5" style="display: none;">
                                        <h3 style="color: #5a3fa3; margin-bottom: 20px; border-bottom: 2px solid #e8d9f0; padding-bottom: 10px;">Step 5: Final Submission</h3>
                                        
                                        <div class="grid-3" style="gap:20px;">
                                            <div class="form-group">
                                                <label>Submitted By *</label>
                                                <input type="text" name="submitted_by" value="<?php echo old_value('submitted_by', $user['full_name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Endorsed By</label>
                                                <input type="text" name="endorsed_by" value="<?php echo old_value('endorsed_by'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Remarks</label>
                                                <textarea name="remarks" rows="3" style="width: 100%; padding: 12px; border: 2px solid #e8d9f0; border-radius: 10px;"><?php echo old_value('remarks'); ?></textarea>
                                            </div>
                                        </div>

                                        <div class="alert info" style="margin: 30px 0;">
                                            <strong>Review your application:</strong> Please review all information before submitting. Once submitted, you cannot edit the application.
                                        </div>

                                        <div style="display: flex; gap: 10px; margin-top: 30px;">
                                            <button type="button" class="btn-submit" onclick="prevStep(4)" style="background: #a8c5e0; flex: 1;">← Previous</button>
                                            <button type="submit" class="btn-submit" style="background: #85d88a; flex: 1;">✓ Submit Application</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <form method="post" enctype="multipart/form-data" style="margin-top:20px;">
                                <input type="hidden" name="apply_grant_id" value="<?php echo (int) $selectedGrant['id']; ?>">
                                <input type="hidden" name="form_context" value="generic_grant">

                                <div class="grid-2" style="gap:20px;">
                                    <div class="form-group">
                                        <label>Project Title *</label>
                                        <input type="text" name="project_title" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Requested Amount (PHP) *</label>
                                        <input type="number" name="requested_amount" min="1" step="0.01" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Project Summary *</label>
                                    <textarea name="project_summary" rows="4" required></textarea>
                                </div>

                                <div class="requirements-grid">
                                    <?php foreach ($selectedGrant['requirements'] as $requirement): ?>
                                        <div class="requirement-card">
                                            <strong><?php echo sanitize($requirement['title']); ?></strong>
                                            <p><?php echo sanitize($requirement['description']); ?></p>
                                            <div class="form-group">
                                                <?php
                                                $fieldName = 'requirement_' . $requirement['id'];
                                                $requiredAttr = $requirement['is_required'] ? 'required' : '';
                                                switch ($requirement['requirement_type']) {
                                                    case 'number':
                                                        echo '<input type="number" name="' . $fieldName . '" ' . $requiredAttr . '>';
                                                        break;
                                                    case 'url':
                                                        echo '<input type="url" name="' . $fieldName . '" ' . $requiredAttr . '>';
                                                        break;
                                                    case 'date':
                                                        echo '<input type="date" name="' . $fieldName . '" ' . $requiredAttr . '>';
                                                        break;
                                                    case 'text':
                                                        echo '<textarea name="' . $fieldName . '" ' . $requiredAttr . ' rows="3"></textarea>';
                                                        break;
                                                    default:
                                                        echo '<input type="file" name="' . $fieldName . '" ' . $requiredAttr . '>';
                                                        echo '<small>Accepted: PDF, DOC, DOCX, XLS, PPT, ZIP up to 10MB.</small>';
                                                        break;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="submit" class="btn-submit" style="margin-top:20px;">Submit Application</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Step navigation - make sure it's globally accessible
    window.nextStep = function(stepNum) {
        console.log('nextStep called with stepNum:', stepNum);
        try {
            const currentStep = document.querySelector('.step-content.active');
            if (!currentStep) {
                console.error('No active step found');
                alert('No active step found. Please refresh the page.');
                return;
            }
            
            console.log('Current step:', currentStep.id);
            
            const nextStepEl = document.getElementById('step' + stepNum);
            if (!nextStepEl) {
                console.error('Step ' + stepNum + ' not found');
                alert('Step ' + stepNum + ' not found. Please refresh the page.');
                return;
            }
            
            const currentStepNum = parseInt(currentStep.id.replace('step', ''));
            console.log('Current step number:', currentStepNum, 'Target step:', stepNum);
            
            // Validate current step before proceeding
            if (currentStepNum < stepNum) {
                const inputs = currentStep.querySelectorAll('input[required], textarea[required], select[required]');
                console.log('Found', inputs.length, 'required inputs to validate');
                let isValid = true;
                let firstInvalid = null;
                
                inputs.forEach(input => {
                    // Skip hidden inputs and disabled inputs
                    if (input.offsetParent === null || input.disabled) {
                        return;
                    }
                    
                    const value = input.value.trim();
                    if (!value) {
                        isValid = false;
                        input.style.borderColor = '#ff9a9a';
                        if (!firstInvalid) {
                            firstInvalid = input;
                        }
                        console.log('Invalid field:', input.name || input.id);
                    } else {
                        input.style.borderColor = '';
                    }
                });
                
                if (!isValid) {
                    alert('Please fill in all required fields before proceeding.');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
            }
            
            console.log('Validation passed, proceeding to step', stepNum);
            
            // Update step indicators
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
                if (parseInt(step.dataset.step) <= stepNum) {
                    step.classList.add('active');
                }
            });
            
            // Show/hide step content
            document.querySelectorAll('.step-content').forEach(step => {
                step.classList.remove('active');
                step.style.display = 'none';
            });
            
            if (nextStepEl) {
                nextStepEl.classList.add('active');
                nextStepEl.style.display = 'block';
                console.log('Step', stepNum, 'is now active');
                // Scroll to the form container, not just the step
                setTimeout(() => {
                    const formContainer = document.querySelector('.form-steps');
                    if (formContainer) {
                        formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        nextStepEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            }
        } catch (error) {
            console.error('Error in nextStep:', error);
            alert('An error occurred: ' + error.message);
        }
    }
    
    window.prevStep = function(stepNum) {
        try {
            const prevStepEl = document.getElementById('step' + stepNum);
            if (!prevStepEl) {
                console.error('Step ' + stepNum + ' not found');
                return;
            }
            
            // Update step indicators
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
                if (parseInt(step.dataset.step) <= stepNum) {
                    step.classList.add('active');
                }
            });
            
            // Show/hide step content
            document.querySelectorAll('.step-content').forEach(step => {
                step.classList.remove('active');
                step.style.display = 'none';
            });
            
            if (prevStepEl) {
                prevStepEl.classList.add('active');
                prevStepEl.style.display = 'block';
                const formContainer = document.querySelector('.form-steps');
                if (formContainer) {
                    formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    prevStepEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        } catch (error) {
            console.error('Error in prevStep:', error);
            alert('An error occurred. Please try again.');
        }
    }
    
    // Handle "Other" option in dropdowns
    function handleOtherOption(select, otherInputId) {
        const otherInput = document.getElementById(otherInputId);
        if (select.value === 'Other') {
            otherInput.style.display = 'block';
            otherInput.required = true;
        } else {
            otherInput.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    }
    
    // Add site row - make globally accessible
    window.addSiteRow = function() {
        const tbody = document.getElementById('sitesTableBody');
        const row = tbody.insertRow();
        row.className = 'site-row';
        
        const regions = <?php echo json_encode($philippineRegions); ?>;
        const provinces = <?php echo json_encode($philippineProvinces); ?>;
        const cities = <?php echo json_encode($philippineCities); ?>;
        
        row.innerHTML = `
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="site_number[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_country[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option><option value="Philippines">Philippines</option><option value="Other">Other</option></select></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_region[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option>${regions.map(r => `<option value="${r}">${r}</option>`).join('')}</select></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_province[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option>${provinces.map(p => `<option value="${p}">${p}</option>`).join('')}</select></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="site_district[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><select name="site_municipality[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"><option value="">Select</option>${cities.map(c => `<option value="${c}">${c}</option>`).join('')}</select></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="site_barangay[]" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><button type="button" class="remove-row" style="background: #ff9a9a; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">Remove</button></td>
        `;
    }
    
    // Add personnel row - make globally accessible
    window.addPersonnelRow = function() {
        const tbody = document.getElementById('personnelTableBody');
        const row = tbody.insertRow();
        row.className = 'personnel-row';
        
        row.innerHTML = `
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="personnel_position[]" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="personnel_quantity[]" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="personnel_percent[]" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><textarea name="personnel_responsibility[]" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px; min-height: 50px; resize: vertical;"></textarea></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><button type="button" class="remove-row" style="background: #ff9a9a; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">Remove</button></td>
        `;
    }
    
    // Add budget row - make globally accessible
    window.addBudgetRow = function() {
        const tbody = document.getElementById('budgetTableBody');
        const row = tbody.insertRow();
        row.className = 'budget-row';
        
        row.innerHTML = `
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="budget_year_label[]" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="text" name="budget_agency[]" required style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_ps_dost[]" class="budget-input" data-type="dost" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_ps_counterpart[]" class="budget-input" data-type="counterpart" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_mooe_dost[]" class="budget-input" data-type="dost" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_mooe_counterpart[]" class="budget-input" data-type="counterpart" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_co_dost[]" class="budget-input" data-type="dost" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><input type="number" step="0.01" name="budget_co_counterpart[]" class="budget-input" data-type="counterpart" style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 6px;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0; background: #e8f5e9;"><input type="number" step="0.01" name="budget_total_dost[]" readonly style="width: 100%; padding: 8px; border: 1px solid #c8e6c9; border-radius: 6px; background: #f1f8f4; font-weight: 600;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0; background: #e8f5e9;"><input type="number" step="0.01" name="budget_total_counterpart[]" readonly style="width: 100%; padding: 8px; border: 1px solid #c8e6c9; border-radius: 6px; background: #f1f8f4; font-weight: 600;"></td>
            <td style="padding: 10px; border-bottom: 1px solid #e8d9f0;"><button type="button" class="remove-row" style="background: #ff9a9a; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">Remove</button></td>
        `;
        
        // Attach budget calculator to new row
        attachBudgetCalculators(row);
    }
    
    // Budget calculation
    function calculateBudgetTotal(row) {
        const psDost = parseFloat(row.querySelector('input[name="budget_ps_dost[]"]').value) || 0;
        const psCounterpart = parseFloat(row.querySelector('input[name="budget_ps_counterpart[]"]').value) || 0;
        const mooeDost = parseFloat(row.querySelector('input[name="budget_mooe_dost[]"]').value) || 0;
        const mooeCounterpart = parseFloat(row.querySelector('input[name="budget_mooe_counterpart[]"]').value) || 0;
        const coDost = parseFloat(row.querySelector('input[name="budget_co_dost[]"]').value) || 0;
        const coCounterpart = parseFloat(row.querySelector('input[name="budget_co_counterpart[]"]').value) || 0;
        
        row.querySelector('input[name="budget_total_dost[]"]').value = (psDost + mooeDost + coDost).toFixed(2);
        row.querySelector('input[name="budget_total_counterpart[]"]').value = (psCounterpart + mooeCounterpart + coCounterpart).toFixed(2);
    }
    
    function attachBudgetCalculators(container) {
        container.querySelectorAll('.budget-input').forEach(input => {
            input.addEventListener('input', function() {
                const row = this.closest('tr');
                if (row) calculateBudgetTotal(row);
            });
        });
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        // Ensure novalidate is set on all forms to prevent browser validation
        document.querySelectorAll('form[method="post"]').forEach(form => {
            if (!form.hasAttribute('novalidate')) {
                form.setAttribute('novalidate', 'novalidate');
            }
        });
        // Handle "Other" option in location dropdowns
        ['city', 'province', 'region', 'country'].forEach(field => {
            const select = document.querySelector(`select[name="${field}"]`);
            const otherInput = document.getElementById(field + 'OtherInput');
            if (select && otherInput) {
                select.addEventListener('change', () => handleOtherOption(select, field + 'OtherInput'));
                if (select.value === 'Other') {
                    otherInput.style.display = 'block';
                }
            }
        });
        
        // Remove row functionality
        document.body.addEventListener('click', (event) => {
            if (event.target.matches('.remove-row')) {
                const row = event.target.closest('tr');
                if (row) {
                    row.remove();
                }
            }
        });
        
        // Word count validation for narrative fields with variable limits
        document.querySelectorAll('.narrative-field').forEach(field => {
            const minWords = parseInt(field.dataset.minWords) || 1;
            const maxWords = parseInt(field.dataset.maxWords) || 500;
            const fieldName = field.dataset.fieldName || '';
            const isLiteratureCited = fieldName === 'literature_cited';
            const wordCountDiv = field.parentNode.querySelector('.word-count');
            
            const updateWordCount = () => {
                const text = field.value.trim();
                const words = text.split(/\s+/).filter(w => w.length > 0).length;
                
                if (wordCountDiv) {
                    if (isLiteratureCited) {
                        wordCountDiv.textContent = `${words} words (No limit)`;
                    } else {
                        wordCountDiv.textContent = `${words} words (${minWords}-${maxWords} required)`;
                    }
                    
                    if (words < minWords) {
                        wordCountDiv.style.color = '#ff9a9a';
                        field.setCustomValidity(`Please enter at least ${minWords} words. Currently: ${words} words.`);
                    } else if (!isLiteratureCited && words > maxWords) {
                        wordCountDiv.style.color = '#ff9a9a';
                        field.setCustomValidity(`Please enter no more than ${maxWords} words. Currently: ${words} words.`);
                    } else {
                        wordCountDiv.style.color = '#85d88a';
                        field.setCustomValidity('');
                    }
                }
            };
            
            field.addEventListener('input', updateWordCount);
            updateWordCount(); // Initial count
        });
        
        // Budget calculation for existing rows
        document.querySelectorAll('.budget-row').forEach(row => {
            attachBudgetCalculators(row);
            // Calculate initial totals
            calculateBudgetTotal(row);
        });
        
        // Step indicator click handlers
        document.querySelectorAll('.step').forEach(step => {
            step.addEventListener('click', function() {
                const stepNum = parseInt(this.dataset.step);
                const currentStep = document.querySelector('.step-content.active');
                if (!currentStep) return;
                const currentStepNum = parseInt(currentStep.id.replace('step', ''));
                
                if (stepNum < currentStepNum) {
                    window.prevStep(stepNum);
                } else if (stepNum > currentStepNum) {
                    window.nextStep(stepNum);
                }
            });
        });
        
        // Add event listeners for Next/Previous buttons using data attributes
        document.querySelectorAll('button[data-next-step]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const stepNum = parseInt(this.getAttribute('data-next-step'));
                window.nextStep(stepNum);
            });
        });
        
        document.querySelectorAll('button[data-prev-step]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const stepNum = parseInt(this.getAttribute('data-prev-step'));
                window.prevStep(stepNum);
            });
        });
        
        // Add event listeners for add row buttons
        const addSiteRowBtn = document.getElementById('addSiteRowBtn');
        if (addSiteRowBtn) {
            addSiteRowBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.addSiteRow();
            });
        }
        
        const addPersonnelRowBtn = document.getElementById('addPersonnelRowBtn');
        if (addPersonnelRowBtn) {
            addPersonnelRowBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.addPersonnelRow();
            });
        }
        
        const addBudgetRowBtn = document.getElementById('addBudgetRowBtn');
        if (addBudgetRowBtn) {
            addBudgetRowBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.addBudgetRow();
            });
        }
        
        // Add event listener for the "Next: Project Details" button
        const nextToStep2Btn = document.getElementById('nextToStep2');
        if (nextToStep2Btn) {
            nextToStep2Btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.nextStep(2);
            });
        }

        function calculateBudgetRow(row) {
            const psDost = parseFloat(row.querySelector('input[name="budget_ps_dost[]"]')?.value || 0);
            const psCounterpart = parseFloat(row.querySelector('input[name="budget_ps_counterpart[]"]')?.value || 0);
            const mooeDost = parseFloat(row.querySelector('input[name="budget_mooe_dost[]"]')?.value || 0);
            const mooeCounterpart = parseFloat(row.querySelector('input[name="budget_mooe_counterpart[]"]')?.value || 0);
            const coDost = parseFloat(row.querySelector('input[name="budget_co_dost[]"]')?.value || 0);
            const coCounterpart = parseFloat(row.querySelector('input[name="budget_co_counterpart[]"]')?.value || 0);

            const totalDost = psDost + mooeDost + coDost;
            const totalCounterpart = psCounterpart + mooeCounterpart + coCounterpart;

            const totalDostInput = row.querySelector('input[name="budget_total_dost[]"]');
            const totalCounterpartInput = row.querySelector('input[name="budget_total_counterpart[]"]');
            
            if (totalDostInput) totalDostInput.value = totalDost.toFixed(2);
            if (totalCounterpartInput) totalCounterpartInput.value = totalCounterpart.toFixed(2);
        }

        // Attach to existing budget rows and calculate initial totals
        document.querySelectorAll('#budgetContainer .repeatable-row').forEach(row => {
            attachBudgetCalculators(row);
            calculateBudgetRow(row);
        });

        // Form submission validation - target the DOST form specifically
        const form = document.getElementById('dostApplicationForm') || document.querySelector('form[method="post"]');
        if (form) {
            // Store a flag to prevent infinite loops
            let isSubmitting = false;
            
            // Intercept submit button clicks to prevent browser validation
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!isSubmitting) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Trigger our custom validation
                        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                }, true); // Use capture phase to intercept early
            });
            
            form.addEventListener('submit', function submitHandler(e) {
                // If we're already in the submission process, allow it to proceed
                if (isSubmitting) {
                    return true;
                }
                
                e.preventDefault(); // Prevent default to handle validation manually
                e.stopPropagation(); // Stop event propagation
                
                let hasErrors = false;
                let firstInvalidField = null;
                let firstInvalidStep = null;
                
                // Temporarily show all steps to validate all fields
                const allSteps = form.querySelectorAll('.step-content');
                const originalDisplays = [];
                allSteps.forEach((step, index) => {
                    originalDisplays[index] = step.style.display;
                    step.style.display = 'block';
                });
                
                // Validate all required fields (now that all steps are visible)
                const allRequiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');
                allRequiredFields.forEach(field => {
                    // Skip disabled fields and hidden input types (like hidden inputs)
                    if (field.disabled || field.type === 'hidden') {
                        return;
                    }
                    
                    const value = field.value.trim();
                    let isValid = true;
                    let errorMessage = '';
                    
                    // Basic required validation
                    if (!value) {
                        isValid = false;
                        errorMessage = 'This field is required.';
                    }
                    // Email validation
                    else if (field.type === 'email') {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid email address.';
                        }
                    }
                    // Word count validation for narrative fields
                    else if (field.classList.contains('narrative-field')) {
                        const wordCount = value.split(/\s+/).filter(w => w.length > 0).length;
                        const minWords = parseInt(field.dataset.minWords) || 1;
                        const maxWords = parseInt(field.dataset.maxWords) || 500;
                        const fieldName = field.dataset.fieldName || '';
                        const isLiteratureCited = fieldName === 'literature_cited';
                        
                        if (wordCount < minWords) {
                            isValid = false;
                            errorMessage = `Must contain at least ${minWords} words. Currently: ${wordCount} words.`;
                        } else if (!isLiteratureCited && wordCount > maxWords) {
                            isValid = false;
                            errorMessage = `Must contain no more than ${maxWords} words. Currently: ${wordCount} words.`;
                        }
                        // Literature Cited has no max limit, so we skip the max check for it
                    }
                    
                    if (!isValid) {
                        hasErrors = true;
                        field.style.borderColor = '#ff9a9a';
                        
                        // Find which step this field belongs to
                        const stepContent = field.closest('.step-content');
                        if (stepContent && !firstInvalidStep) {
                            firstInvalidStep = stepContent;
                            firstInvalidField = field;
                        }
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                // Restore original step visibility
                allSteps.forEach((step, index) => {
                    step.style.display = originalDisplays[index];
                });
                
                // If there are errors, show the first invalid step and focus the field
                if (hasErrors) {
                    if (firstInvalidStep) {
                        // Show the step with the error
                        const stepNum = parseInt(firstInvalidStep.id.replace('step', ''));
                        window.nextStep(stepNum);
                        
                        // Wait a bit for the step to be visible, then focus
                        setTimeout(() => {
                            if (firstInvalidField) {
                                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                firstInvalidField.focus();
                                
                                const label = firstInvalidField.previousElementSibling ? 
                                    firstInvalidField.previousElementSibling.textContent : 'A field';
                                alert(`${label} has an error. Please check and correct it before submitting.`);
                            }
                        }, 300);
                    } else {
                        alert('Please fill in all required fields before submitting.');
                    }
                    return false;
                }
                
                // All validation passed - remove required from hidden fields and submit
                // Temporarily remove required from ALL fields in hidden steps to prevent browser validation
                allSteps.forEach((step, index) => {
                    const wasHidden = originalDisplays[index] === 'none' || 
                                     (!step.classList.contains('active') && originalDisplays[index] !== 'block');
                    if (wasHidden) {
                        const requiredFields = step.querySelectorAll('[required]');
                        requiredFields.forEach(field => {
                            field.removeAttribute('required');
                        });
                    }
                });
                
                // Remove the event listener to prevent loop
                isSubmitting = true;
                form.removeEventListener('submit', submitHandler);
                
                // Create a new submit button that will trigger natural form submission
                // This bypasses our event handler since we removed it
                const newSubmitBtn = document.createElement('button');
                newSubmitBtn.type = 'submit';
                newSubmitBtn.style.display = 'none';
                form.appendChild(newSubmitBtn);
                
                // Click the new button to submit - this will submit naturally
                // Since required is removed from hidden fields and form has novalidate, no validation will occur
                setTimeout(() => {
                    newSubmitBtn.click();
                }, 10);
            });
        }
    });

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