// Founder Available Grants
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'founder') {
    window.location.href = '../auth/login.html';
}

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    displayAvailableGrants();
});

function setupEventListeners() {
    document.getElementById('closeModal')?.addEventListener('click', closeApplicationModal);
    document.getElementById('applicationForm')?.addEventListener('submit', handleApplicationSubmit);
    
    window.addEventListener('click', function(e) {
        const appModal = document.getElementById('applicationModal');
        if (e.target === appModal) closeApplicationModal();
    });
}

function getPublishedGrants() {
    const grants = JSON.parse(localStorage.getItem('grants') || '[]');
    return grants.filter(g => g.status === 'published');
}

function getMyApplications() {
    const applications = JSON.parse(localStorage.getItem('applications') || '[]');
    return applications.filter(a => a.userId === currentUser.id);
}

function displayAvailableGrants() {
    const grants = getPublishedGrants();
    const myApplications = getMyApplications();
    const grantsList = document.getElementById('grantsList');
    if (!grantsList) return;
    
    if (grants.length === 0) {
        grantsList.innerHTML = '<div class="no-data">No grants available yet. Check back later!</div>';
        return;
    }
    
    grantsList.innerHTML = grants.map(grant => {
        const hasApplied = myApplications.some(a => a.grantId === grant.id);
        const deadline = new Date(grant.deadline);
        const isExpired = deadline < new Date();
        
        return `
            <div class="grant-card">
                <div class="grant-title">${grant.title}</div>
                <div class="grant-amount">$${grant.amount.toLocaleString()}</div>
                <div class="grant-desc">${grant.description}</div>
                <div class="grant-meta">
                    <span>Deadline: ${deadline.toLocaleDateString()}</span>
                    ${isExpired ? '<span style="color: #ff9a9a;">Expired</span>' : ''}
                </div>
                <div class="grant-actions">
                    <button class="btn-apply" 
                            onclick="openApplicationForm('${grant.id}')" 
                            ${hasApplied || isExpired ? 'disabled' : ''}>
                        ${hasApplied ? 'Already Applied' : isExpired ? 'Expired' : 'Apply Now'}
                    </button>
                    ${hasApplied ? '<button class="btn-apply" style="background: #a8c5e0;" onclick="window.location.href=\'my-applications.html\'">View Application</button>' : ''}
                </div>
            </div>
        `;
    }).join('');
}

function openApplicationForm(grantId) {
    const grants = getPublishedGrants();
    const grant = grants.find(g => g.id === grantId);
    if (!grant) return;
    
    const myApplications = getMyApplications();
    if (myApplications.some(a => a.grantId === grantId)) {
        showNotification('You have already applied for this grant!', 'info');
        return;
    }
    
    document.getElementById('modalTitle').textContent = `Apply for ${grant.title}`;
    
    const grantInfo = document.getElementById('grantInfo');
    grantInfo.innerHTML = `
        <h3 style="color: #5a3fa3; margin-bottom: 10px;">${grant.title}</h3>
        <p><strong>Amount:</strong> $${grant.amount.toLocaleString()}</p>
        <p><strong>Deadline:</strong> ${new Date(grant.deadline).toLocaleDateString()}</p>
        <p><strong>Description:</strong> ${grant.description}</p>
        ${grant.requirements && grant.requirements.length > 0 ? `
            <div style="margin-top: 10px;">
                <strong>Requirements:</strong>
                <ul style="margin-top: 5px; padding-left: 20px;">
                    ${grant.requirements.map(req => `<li>${req}</li>`).join('')}
                </ul>
            </div>
        ` : ''}
    `;
    
    const formFields = document.getElementById('formFields');
    const submitContainer = document.getElementById('formSubmitContainer');
    
    // Use form generator if available, otherwise use simple form
    if (typeof generateApplicationForm === 'function') {
        formFields.innerHTML = generateApplicationForm(grant);
        
        // Check if it's a step-based form (DOST form) or custom form
        if (grant.formType === 'dost_research') {
            submitContainer.style.display = 'none'; // Hide default submit, form has its own
        } else if (grant.formType === 'custom' && grant.requirementSections && grant.requirementSections.length > 0) {
            submitContainer.style.display = 'block'; // Show submit button for custom forms
        } else {
            submitContainer.style.display = 'block';
        }
    } else {
        formFields.innerHTML = `
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
        submitContainer.style.display = 'block';
    }
    
    document.getElementById('applicationForm').setAttribute('data-grant-id', grantId);
    document.getElementById('applicationModal').classList.add('active');
}

function closeApplicationModal() {
    document.getElementById('applicationModal').classList.remove('active');
    document.getElementById('applicationForm').reset();
    
    // Reset step-based forms
    const stepContents = document.querySelectorAll('.step-content');
    stepContents.forEach((step, idx) => {
        step.style.display = idx === 0 ? 'block' : 'none';
    });
    
    // Reset step indicators
    const steps = document.querySelectorAll('.step');
    steps.forEach((step, idx) => {
        if (idx === 0) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

function handleApplicationSubmit(e) {
    e.preventDefault();
    
    const grantId = document.getElementById('applicationForm').getAttribute('data-grant-id');
    const formData = new FormData(e.target);
    const responses = {};
    
    // Collect all form data including arrays
    for (const [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const arrayKey = key.slice(0, -2);
            if (!responses[arrayKey]) {
                responses[arrayKey] = [];
            }
            responses[arrayKey].push(value);
        } else {
            responses[key] = value;
        }
    }
    
    // Convert arrays to objects for better storage
    const processedResponses = {};
    for (const [key, value] of Object.entries(responses)) {
        if (Array.isArray(value)) {
            processedResponses[key] = value;
        } else {
            processedResponses[key] = value;
        }
    }
    
    const applications = JSON.parse(localStorage.getItem('applications') || '[]');
    
    // Get company name from appropriate field - check various common field names
    const companyName = processedResponses.companyName || 
                       processedResponses.Implementing_Agency || 
                       processedResponses.Project_Title ||
                       processedResponses['section_0_field_0'] || // First field of first section
                       Object.values(processedResponses).find(v => v && typeof v === 'string' && v.length > 0) ||
                       'N/A';
    
    const application = {
        id: Date.now().toString(),
        userId: currentUser.id,
        grantId: grantId,
        companyName: companyName,
        responses: processedResponses,
        status: 'pending',
        submittedAt: new Date().toISOString()
    };
    
    applications.push(application);
    localStorage.setItem('applications', JSON.stringify(applications));
    
    closeApplicationModal();
    displayAvailableGrants();
    showNotification('Application submitted successfully!', 'success');
}


