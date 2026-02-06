// Application Form Generator for Complex Grant Forms
function generateApplicationForm(grant) {
    // Check if this is a DOST-style grant with complex requirements
    if (grant.formType === 'dost_research') {
        return generateDOSTResearchForm(grant);
    }
    
    // Check if this is a custom form with requirement sections
    if (grant.formType === 'custom' && grant.requirementSections && grant.requirementSections.length > 0) {
        return generateCustomForm(grant);
    }
    
    // Default simple form
    return generateSimpleForm();
}

function generateCustomForm(grant) {
    const sections = grant.requirementSections || [];
    
    if (sections.length === 0) {
        return generateSimpleForm();
    }
    
    return sections.map((section, sectionIndex) => `
        <div class="form-section" style="margin-bottom: 30px; padding: 20px; background: #f9f7fc; border-radius: 10px; border-left: 4px solid #d4a5e8;">
            <h3 style="color: #5a3fa3; margin-bottom: 20px; font-size: 18px;">${section.title || 'Section ' + (sectionIndex + 1)}</h3>
            ${section.fields.map((field, fieldIndex) => {
                const fieldName = `section_${sectionIndex}_field_${fieldIndex}`;
                const fieldId = fieldName.replace(/\s+/g, '_').toLowerCase();
                
                let fieldHTML = '';
                
                switch (field.type) {
                    case 'textarea':
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                <textarea name="${fieldId}" ${field.required ? 'required' : ''} rows="4" placeholder="Enter ${field.label || 'value'}..."></textarea>
                            </div>
                        `;
                        break;
                    case 'select':
                        const options = field.options || [];
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                <select name="${fieldId}" ${field.required ? 'required' : ''}>
                                    <option value="">Select ${field.label || 'option'}</option>
                                    ${options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                                </select>
                            </div>
                        `;
                        break;
                    case 'checkbox':
                        fieldHTML = `
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="${fieldId}" value="yes" ${field.required ? 'required' : ''}>
                                    <span>${field.label || 'Field'} ${field.required ? '*' : ''}</span>
                                </label>
                            </div>
                        `;
                        break;
                    case 'radio':
                        const radioOptions = field.options || [];
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                ${radioOptions.map((opt, optIndex) => `
                                    <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <input type="radio" name="${fieldId}" value="${opt}" ${field.required && optIndex === 0 ? 'required' : ''}>
                                        <span>${opt}</span>
                                    </label>
                                `).join('')}
                            </div>
                        `;
                        break;
                    case 'number':
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                <input type="number" name="${fieldId}" ${field.required ? 'required' : ''} placeholder="Enter ${field.label || 'number'}...">
                            </div>
                        `;
                        break;
                    case 'date':
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                <input type="date" name="${fieldId}" ${field.required ? 'required' : ''}>
                            </div>
                        `;
                        break;
                    case 'email':
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                <input type="email" name="${fieldId}" ${field.required ? 'required' : ''} placeholder="Enter ${field.label || 'email'}...">
                            </div>
                        `;
                        break;
                    case 'tel':
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                <input type="tel" name="${fieldId}" ${field.required ? 'required' : ''} placeholder="Enter ${field.label || 'phone number'}...">
                            </div>
                        `;
                        break;
                    default: // text
                        fieldHTML = `
                            <div class="form-group">
                                <label>${field.label || 'Field'} ${field.required ? '*' : ''}</label>
                                <input type="text" name="${fieldId}" ${field.required ? 'required' : ''} placeholder="Enter ${field.label || 'value'}...">
                            </div>
                        `;
                }
                
                return fieldHTML;
            }).join('')}
        </div>
    `).join('');
}

function generateSimpleForm() {
    return `
        <div class="form-group">
            <label>Company Name *</label>
            <input type="text" name="companyName" required>
        </div>
        <div class="form-group">
            <label>Company Description *</label>
            <textarea name="companyDescription" required placeholder="Describe your startup..."></textarea>
        </div>
        <div class="form-group">
            <label>Why do you need this grant? *</label>
            <textarea name="grantReason" required placeholder="Explain why your startup needs this grant..."></textarea>
        </div>
        <div class="form-group">
            <label>How will you use the funds? *</label>
            <textarea name="fundUsage" required placeholder="Describe how you plan to use the grant money..."></textarea>
        </div>
        <div class="form-group">
            <label>Company Website (optional)</label>
            <input type="url" name="website" placeholder="https://yourstartup.com">
        </div>
        <div class="form-group">
            <label>Contact Number *</label>
            <input type="tel" name="contactNumber" required placeholder="+1 234 567 8900">
        </div>
    `;
}

function generateDOSTResearchForm(grant) {
    return `
        <div class="form-steps">
            <div class="step-indicator">
                <div class="step active" data-step="1">Step 1: Basic Information</div>
                <div class="step" data-step="2">Step 2: Project Details</div>
                <div class="step" data-step="3">Step 3: Personnel</div>
                <div class="step" data-step="4">Step 4: Budget</div>
                <div class="step" data-step="5">Step 5: Submission</div>
            </div>

            <div class="step-content" id="step1">
                <h3 style="color: #5a3fa3; margin-bottom: 20px;">Step 1: Basic Information</h3>
                
                <div class="form-group">
                    <label>Program Title *</label>
                    <input type="text" name="Program_Title" required>
                </div>
                
                <div class="form-group">
                    <label>Project Title *</label>
                    <input type="text" name="Project_Title" required>
                </div>
                
                <div class="form-group">
                    <label>Project Leader *</label>
                    <input type="text" name="Project_Leader" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Email *</label>
                        <input type="email" name="Email" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Sex *</label>
                        <select name="Sex" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Telephone *</label>
                        <input type="tel" name="Telephone" required>
                    </div>
                </div>
                
                <h4 style="color: #5a3fa3; margin: 20px 0 10px;">Address</h4>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>House Number</label>
                        <input type="text" name="House_Number">
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label>Street Name</label>
                        <input type="text" name="Street_Name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Barangay *</label>
                        <input type="text" name="Barangay" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>City *</label>
                        <input type="text" name="City" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>District</label>
                        <input type="text" name="District">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Province *</label>
                        <input type="text" name="Province" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Region *</label>
                        <input type="text" name="Region" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Country *</label>
                        <input type="text" name="Country" value="Philippines" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Implementing Agency *</label>
                    <input type="text" name="Implementing_Agency" required>
                </div>
                
                <div class="form-group">
                    <label>Cooperating Agency</label>
                    <input type="text" name="Cooperating_Agency">
                </div>
                
                <div class="form-group">
                    <label>Type of Research *</label>
                    <select name="Type_of_Research" required>
                        <option value="">Select</option>
                        <option value="Basic Research">Basic Research</option>
                        <option value="Applied Research">Applied Research</option>
                        <option value="Development">Development</option>
                        <option value="Technology Transfer">Technology Transfer</option>
                    </select>
                </div>
                
                <h4 style="color: #5a3fa3; margin: 20px 0 10px;">Site(s) of Implementation</h4>
                <div id="implementationSitesTable">
                    <table class="dynamic-table" style="width: 100%; margin-bottom: 15px;">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th>Region</th>
                                <th>Province</th>
                                <th>District</th>
                                <th>Municipality</th>
                                <th>Barangay</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="implementationSitesBody">
                            <tr>
                                <td><input type="text" name="Site_Country[]" value="Philippines" required></td>
                                <td><input type="text" name="Site_Region[]" required></td>
                                <td><input type="text" name="Site_Province[]" required></td>
                                <td><input type="text" name="Site_District[]"></td>
                                <td><input type="text" name="Site_Municipality[]" required></td>
                                <td><input type="text" name="Site_Barangay[]" required></td>
                                <td><button type="button" class="btn-small" onclick="removeTableRow(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn-small" onclick="addImplementationSite()" style="margin-bottom: 20px;">+ Add Site</button>
                </div>
                
                <div class="form-group">
                    <label>R&D Priority Area and Program *</label>
                    <textarea name="R&D_Priority_Area_and_Program" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Sustainable Development Goal Addressed *</label>
                    <textarea name="Sustainable_Development_Goal_Addressed" required placeholder="List the SDGs addressed by this project"></textarea>
                </div>
                
                <div class="form-group">
                    <label>DOST Pillars Pursued *</label>
                    <textarea name="DOST_Pillars_Pursued" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>DOST Thematic Areas Covered *</label>
                    <textarea name="DOST_Thematic_Areas_Covered" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Applicable DOST Strategic Program *</label>
                    <textarea name="Applicable_DOST_Strategic_Program" required></textarea>
                </div>
                
                <button type="button" class="btn-submit" onclick="nextStep(2)" style="margin-top: 20px;">Next: Project Details</button>
            </div>

            <div class="step-content" id="step2" style="display: none;">
                <h3 style="color: #5a3fa3; margin-bottom: 20px;">Step 2: Project Details</h3>
                
                <div class="form-group">
                    <label>Executive Summary *</label>
                    <textarea name="Executive_Summary" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Introduction *</label>
                    <textarea name="Introduction" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Rationale and Significance *</label>
                    <textarea name="Rationale_Significance" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Scientific Basis and Theoretical Framework *</label>
                    <textarea name="Scientific_Basis_Theoretical_Framework" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Objectives *</label>
                    <textarea name="Objectives" required rows="5" placeholder="List the main objectives of the project"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Review of Literature *</label>
                    <textarea name="Review_of_Literature" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Methodology *</label>
                    <textarea name="Methodology" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Technology Roadmap *</label>
                    <textarea name="Technology_Roadmap" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Expected Outputs *</label>
                    <textarea name="Expected_Outputs" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Potential Outcomes *</label>
                    <textarea name="Potential_Outcomes" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Potential Impacts *</label>
                    <textarea name="Potential_Impacts" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Target Beneficiaries *</label>
                    <textarea name="Target_Beneficiaries" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Sustainability Plan *</label>
                    <textarea name="Sustainability_Plan" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Gender and Development (GAD) Score *</label>
                    <input type="number" name="Gender_and_Development_Score" min="0" max="100" required>
                </div>
                
                <div class="form-group">
                    <label>Limitations of the Project *</label>
                    <textarea name="Limitations_of_the_Project" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>List of Risk and Assumptions *</label>
                    <textarea name="List_of_Risk_and_Assumptions" required rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Literature Cited *</label>
                    <textarea name="Literature_Cited" required rows="5" placeholder="List all references and citations"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-submit" onclick="prevStep(1)" style="background: #a8c5e0;">Previous</button>
                    <button type="button" class="btn-submit" onclick="nextStep(3)">Next: Personnel</button>
                </div>
            </div>

            <div class="step-content" id="step3" style="display: none;">
                <h3 style="color: #5a3fa3; margin-bottom: 20px;">Step 3: Personnel Requirements</h3>
                
                <div id="personnelTable">
                    <table class="dynamic-table" style="width: 100%; margin-bottom: 15px;">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Quality/Qualifications</th>
                                <th>Percent Time Devoted</th>
                                <th>Responsibility</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="personnelBody">
                            <tr>
                                <td><input type="text" name="Personnel_Position[]" required></td>
                                <td><input type="text" name="Personnel_Quality[]" required></td>
                                <td><input type="number" name="Personnel_Percent_Time[]" min="0" max="100" required></td>
                                <td><textarea name="Personnel_Responsibility[]" required rows="2"></textarea></td>
                                <td><button type="button" class="btn-small" onclick="removeTableRow(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn-small" onclick="addPersonnelRow()" style="margin-bottom: 20px;">+ Add Personnel</button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-submit" onclick="prevStep(2)" style="background: #a8c5e0;">Previous</button>
                    <button type="button" class="btn-submit" onclick="nextStep(4)">Next: Budget</button>
                </div>
            </div>

            <div class="step-content" id="step4" style="display: none;">
                <h3 style="color: #5a3fa3; margin-bottom: 20px;">Step 4: Budget Allocation</h3>
                <p style="color: #7d6b8f; margin-bottom: 20px;">Credit Limit: 1.5M Philippine Pesos per year</p>
                
                <div id="budgetTable">
                    <table class="dynamic-table" style="width: 100%; margin-bottom: 15px; font-size: 12px;">
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="budgetBody">
                            <tr>
                                <td><input type="text" name="Budget_Year[]" value="Year 1" required></td>
                                <td><input type="text" name="Budget_Agency[]" required></td>
                                <td><input type="number" name="Budget_PS_DOST[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
                                <td><input type="number" name="Budget_PS_Counterpart[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
                                <td><input type="number" name="Budget_MOOE_DOST[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
                                <td><input type="number" name="Budget_MOOE_Counterpart[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
                                <td><input type="number" name="Budget_CO_DOST[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
                                <td><input type="number" name="Budget_CO_Counterpart[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
                                <td><input type="number" name="Budget_Total_DOST[]" min="0" step="0.01" readonly></td>
                                <td><input type="number" name="Budget_Total_Counterpart[]" min="0" step="0.01" readonly></td>
                                <td><button type="button" class="btn-small" onclick="removeTableRow(this)">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn-small" onclick="addBudgetRow()" style="margin-bottom: 20px;">+ Add Year</button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-submit" onclick="prevStep(3)" style="background: #a8c5e0;">Previous</button>
                    <button type="button" class="btn-submit" onclick="nextStep(5)">Next: Submission</button>
                </div>
            </div>

            <div class="step-content" id="step5" style="display: none;">
                <h3 style="color: #5a3fa3; margin-bottom: 20px;">Step 5: Submission</h3>
                
                <div class="form-group">
                    <label>Submitted By *</label>
                    <input type="text" name="Submitted_By" required>
                </div>
                
                <div class="form-group">
                    <label>Endorsed By</label>
                    <input type="text" name="Endorsed_By">
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="Remarks" rows="4"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-submit" onclick="prevStep(4)" style="background: #a8c5e0;">Previous</button>
                    <button type="submit" class="btn-submit" id="finalSubmitBtn">Submit Application</button>
                </div>
            </div>
        </div>
    `;
}

// Step navigation functions
function nextStep(stepNum) {
    document.getElementById('step' + (stepNum - 1)).style.display = 'none';
    document.getElementById('step' + stepNum).style.display = 'block';
    
    // Update step indicator
    document.querySelectorAll('.step').forEach((step, idx) => {
        if (idx + 1 <= stepNum) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

function prevStep(stepNum) {
    document.getElementById('step' + (stepNum + 1)).style.display = 'none';
    document.getElementById('step' + stepNum).style.display = 'block';
    
    // Update step indicator
    document.querySelectorAll('.step').forEach((step, idx) => {
        if (idx + 1 <= stepNum) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

// Table row management
function addImplementationSite() {
    const tbody = document.getElementById('implementationSitesBody');
    const row = tbody.insertRow();
    row.innerHTML = `
        <td><input type="text" name="Site_Country[]" value="Philippines" required></td>
        <td><input type="text" name="Site_Region[]" required></td>
        <td><input type="text" name="Site_Province[]" required></td>
        <td><input type="text" name="Site_District[]"></td>
        <td><input type="text" name="Site_Municipality[]" required></td>
        <td><input type="text" name="Site_Barangay[]" required></td>
        <td><button type="button" class="btn-small" onclick="removeTableRow(this)">Remove</button></td>
    `;
}

function addPersonnelRow() {
    const tbody = document.getElementById('personnelBody');
    const row = tbody.insertRow();
    row.innerHTML = `
        <td><input type="text" name="Personnel_Position[]" required></td>
        <td><input type="text" name="Personnel_Quality[]" required></td>
        <td><input type="number" name="Personnel_Percent_Time[]" min="0" max="100" required></td>
        <td><textarea name="Personnel_Responsibility[]" required rows="2"></textarea></td>
        <td><button type="button" class="btn-small" onclick="removeTableRow(this)">Remove</button></td>
    `;
}

function addBudgetRow() {
    const tbody = document.getElementById('budgetBody');
    const rowCount = tbody.rows.length;
    const row = tbody.insertRow();
    row.innerHTML = `
        <td><input type="text" name="Budget_Year[]" value="Year ${rowCount + 1}" required></td>
        <td><input type="text" name="Budget_Agency[]" required></td>
        <td><input type="number" name="Budget_PS_DOST[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
        <td><input type="number" name="Budget_PS_Counterpart[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
        <td><input type="number" name="Budget_MOOE_DOST[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
        <td><input type="number" name="Budget_MOOE_Counterpart[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
        <td><input type="number" name="Budget_CO_DOST[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
        <td><input type="number" name="Budget_CO_Counterpart[]" min="0" step="0.01" required onchange="calculateBudgetTotal(this)"></td>
        <td><input type="number" name="Budget_Total_DOST[]" min="0" step="0.01" readonly></td>
        <td><input type="number" name="Budget_Total_Counterpart[]" min="0" step="0.01" readonly></td>
        <td><button type="button" class="btn-small" onclick="removeTableRow(this)">Remove</button></td>
    `;
}

function removeTableRow(btn) {
    const row = btn.closest('tr');
    if (row.parentElement.rows.length > 1) {
        row.remove();
    } else {
        alert('At least one row is required');
    }
}

function calculateBudgetTotal(input) {
    const row = input.closest('tr');
    const psDost = parseFloat(row.querySelector('[name="Budget_PS_DOST[]"]').value) || 0;
    const mooeDost = parseFloat(row.querySelector('[name="Budget_MOOE_DOST[]"]').value) || 0;
    const coDost = parseFloat(row.querySelector('[name="Budget_CO_DOST[]"]').value) || 0;
    const psCounterpart = parseFloat(row.querySelector('[name="Budget_PS_Counterpart[]"]').value) || 0;
    const mooeCounterpart = parseFloat(row.querySelector('[name="Budget_MOOE_Counterpart[]"]').value) || 0;
    const coCounterpart = parseFloat(row.querySelector('[name="Budget_CO_Counterpart[]"]').value) || 0;
    
    const totalDost = psDost + mooeDost + coDost;
    const totalCounterpart = psCounterpart + mooeCounterpart + coCounterpart;
    
    row.querySelector('[name="Budget_Total_DOST[]"]').value = totalDost.toFixed(2);
    row.querySelector('[name="Budget_Total_Counterpart[]"]').value = totalCounterpart.toFixed(2);
    
    // Check credit limit
    if (totalDost > 1500000) {
        alert('Warning: Total DOST budget exceeds 1.5M PHP limit!');
    }
}

// Make functions global
window.nextStep = nextStep;
window.prevStep = prevStep;
window.addImplementationSite = addImplementationSite;
window.addPersonnelRow = addPersonnelRow;
window.addBudgetRow = addBudgetRow;
window.removeTableRow = removeTableRow;
window.calculateBudgetTotal = calculateBudgetTotal;

