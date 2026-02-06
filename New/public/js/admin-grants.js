// Admin Grants Management
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'admin') {
    window.location.href = '../auth/login.html';
}

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    // Initialize sample grant if it doesn't exist
    if (typeof initializeDOSTGrant === 'function') {
        initializeDOSTGrant();
    }
    displayGrants();
});

function setupEventListeners() {
    document.getElementById('createGrantBtn')?.addEventListener('click', openCreateGrantModal);
    document.getElementById('closeGrantModal')?.addEventListener('click', closeCreateGrantModal);
    document.getElementById('createGrantForm')?.addEventListener('submit', handleCreateGrant);
    
    window.addEventListener('click', function(e) {
        const createModal = document.getElementById('createGrantModal');
        if (e.target === createModal) closeCreateGrantModal();
    });
}

function getGrants() {
    const grants = localStorage.getItem('grants');
    return grants ? JSON.parse(grants) : [];
}

function saveGrants(grants) {
    localStorage.setItem('grants', JSON.stringify(grants));
}

function displayGrants() {
    const grants = getGrants();
    const grantsList = document.getElementById('grantsList');
    if (!grantsList) return;
    
    if (grants.length === 0) {
        grantsList.innerHTML = '<div class="no-data" style="text-align: center; color: #a89bb8; padding: 40px;">No grants created yet</div>';
        return;
    }
    
    grantsList.innerHTML = grants.map(grant => `
        <div class="grant-card">
            <div class="grant-title">${grant.title}</div>
            <div class="grant-amount">$${grant.amount.toLocaleString()}</div>
            <div class="grant-desc">${grant.description}</div>
            <div class="grant-meta">
                <span>Deadline: ${new Date(grant.deadline).toLocaleDateString()}</span>
                <span class="grant-status" style="background: ${grant.status === 'published' ? '#e8f5f0' : '#fff4e8'}; color: ${grant.status === 'published' ? '#2d6a5e' : '#c97c2b'};">${grant.status === 'published' ? 'Published' : 'Draft'}</span>
            </div>
            <div class="grant-actions">
                <button class="btn-small" onclick="editGrant('${grant.id}')">Edit</button>
                <button class="btn-small" onclick="toggleGrantStatus('${grant.id}')">${grant.status === 'published' ? 'Unpublish' : 'Publish'}</button>
                <button class="btn-small btn-delete" onclick="deleteGrant('${grant.id}')">Delete</button>
            </div>
        </div>
    `).join('');
}

function openCreateGrantModal() {
    document.getElementById('createGrantModal').classList.add('active');
    document.getElementById('createGrantForm').reset();
    document.getElementById('createGrantForm').setAttribute('data-grant-id', '');
    initializeRequirementBuilder(); // Reset requirement builder
}

function closeCreateGrantModal() {
    document.getElementById('createGrantModal').classList.remove('active');
}

function handleCreateGrant(e) {
    e.preventDefault();
    
    const grants = getGrants();
    const grantId = document.getElementById('createGrantForm').getAttribute('data-grant-id');
    const title = document.getElementById('grantTitle').value;
    const amount = parseFloat(document.getElementById('grantAmount').value);
    const description = document.getElementById('grantDescription').value;
    const deadline = document.getElementById('grantDeadline').value;
    
    // Get requirement sections from builder
    const requirementSections = getRequirementSections();
    
    // Validate that at least one section with fields exists
    const hasValidSections = requirementSections.some(section => 
        section.title && section.fields && section.fields.length > 0 && 
        section.fields.some(field => field.label)
    );
    
    if (!hasValidSections) {
        showNotification('Please add at least one requirement section with fields!', 'error');
        return;
    }
    
    // Convert old requirements format to new format for backward compatibility
    const requirements = requirementSections.map(section => section.title).filter(Boolean);
    
    const grant = {
        id: grantId || Date.now().toString(),
        title,
        amount,
        description,
        deadline,
        requirements, // Keep for backward compatibility
        requirementSections, // New structured format
        formType: 'custom', // Custom form type
        status: 'draft',
        createdAt: grantId ? grants.find(g => g.id === grantId)?.createdAt || new Date().toISOString() : new Date().toISOString(),
        createdBy: currentUser.id
    };
    
    if (grantId) {
        const index = grants.findIndex(g => g.id === grantId);
        if (index !== -1) {
            grants[index] = grant;
        }
    } else {
        grants.push(grant);
    }
    
    saveGrants(grants);
    closeCreateGrantModal();
    displayGrants();
    
    showNotification('Grant saved successfully!', 'success');
}

function editGrant(grantId) {
    const grants = getGrants();
    const grant = grants.find(g => g.id === grantId);
    if (!grant) return;
    
    document.getElementById('grantTitle').value = grant.title;
    document.getElementById('grantAmount').value = grant.amount;
    document.getElementById('grantDescription').value = grant.description;
    document.getElementById('grantDeadline').value = grant.deadline;
    
    // Load requirement sections if they exist, otherwise convert old format
    if (grant.requirementSections && grant.requirementSections.length > 0) {
        loadRequirementSections(grant.requirementSections);
    } else if (grant.requirements && grant.requirements.length > 0) {
        // Convert old format to new format
        const convertedSections = [{
            title: 'General Requirements',
            fields: grant.requirements.map(req => ({
                label: req,
                type: 'text',
                required: true,
                options: null
            }))
        }];
        loadRequirementSections(convertedSections);
    } else {
        initializeRequirementBuilder();
    }
    
    document.getElementById('createGrantForm').setAttribute('data-grant-id', grantId);
    
    openCreateGrantModal();
}

function deleteGrant(grantId) {
    if (!confirm('Are you sure you want to delete this grant?')) return;
    
    const grants = getGrants();
    const filtered = grants.filter(g => g.id !== grantId);
    saveGrants(filtered);
    displayGrants();
    showNotification('Grant deleted successfully!', 'success');
}

function toggleGrantStatus(grantId) {
    const grants = getGrants();
    const grant = grants.find(g => g.id === grantId);
    if (!grant) return;
    
    grant.status = grant.status === 'published' ? 'draft' : 'published';
    saveGrants(grants);
    
    if (grant.status === 'published') {
        notifyFounders(grant);
    }
    
    displayGrants();
    showNotification(grant.status === 'published' ? 'Grant published! Founders have been notified.' : 'Grant unpublished.', 'success');
}

function notifyFounders(grant) {
    const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
    const founders = JSON.parse(localStorage.getItem('founders') || '[]');
    
    founders.forEach(founder => {
        notifications.push({
            id: Date.now().toString() + Math.random(),
            userId: founder.id,
            type: 'new_grant',
            title: 'New Grant Available!',
            message: `A new grant "${grant.title}" is now available. Apply now!`,
            grantId: grant.id,
            read: false,
            createdAt: new Date().toISOString()
        });
    });
    
    localStorage.setItem('notifications', JSON.stringify(notifications));
}

