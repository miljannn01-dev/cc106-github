// Admin Applications Review
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'admin') {
    window.location.href = '../auth/login.html';
}

let currentReviewAppId = null;

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    displayApplications();
});

function setupEventListeners() {
    document.getElementById('closeReviewModal')?.addEventListener('click', closeReviewModal);
    document.getElementById('approveBtn')?.addEventListener('click', () => updateApplicationStatus('approved'));
    document.getElementById('underReviewBtn')?.addEventListener('click', () => updateApplicationStatus('under_review'));
    document.getElementById('rejectBtn')?.addEventListener('click', () => updateApplicationStatus('rejected'));
    document.getElementById('searchApps')?.addEventListener('input', filterApplications);
    
    window.addEventListener('click', function(e) {
        const reviewModal = document.getElementById('reviewAppModal');
        if (e.target === reviewModal) closeReviewModal();
    });
}

function getGrants() {
    const grants = localStorage.getItem('grants');
    return grants ? JSON.parse(grants) : [];
}

function getApplications() {
    const applications = localStorage.getItem('applications');
    return applications ? JSON.parse(applications) : [];
}

function saveApplications(applications) {
    localStorage.setItem('applications', JSON.stringify(applications));
}

function displayApplications() {
    const applications = getApplications();
    const grants = getGrants();
    const tableBody = document.getElementById('applicationsTable');
    if (!tableBody) return;
    
    if (applications.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #a89bb8; padding: 40px;">No applications yet</td></tr>';
        return;
    }
    
    tableBody.innerHTML = applications.map(app => {
        const grant = grants.find(g => g.id === app.grantId);
        const statusClass = `badge-${app.status === 'under_review' ? 'underreview' : app.status}`;
        
        return `
            <tr>
                <td>${app.companyName || 'N/A'}</td>
                <td>${grant ? grant.title : 'Unknown Grant'}</td>
                <td>${new Date(app.submittedAt).toLocaleDateString()}</td>
                <td><span class="status-badge ${statusClass}">${app.status.charAt(0).toUpperCase() + app.status.slice(1).replace('_', ' ')}</span></td>
                <td><button class="btn-small" onclick="reviewApplication('${app.id}')">Review</button></td>
            </tr>
        `;
    }).join('');
}

function filterApplications() {
    const searchTerm = document.getElementById('searchApps').value.toLowerCase();
    const applications = getApplications();
    const grants = getGrants();
    const tableBody = document.getElementById('applicationsTable');
    
    const filtered = applications.filter(app => {
        const grant = grants.find(g => g.id === app.grantId);
        return (app.companyName && app.companyName.toLowerCase().includes(searchTerm)) ||
               (grant && grant.title.toLowerCase().includes(searchTerm));
    });
    
    if (filtered.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #a89bb8; padding: 40px;">No applications found</td></tr>';
        return;
    }
    
    tableBody.innerHTML = filtered.map(app => {
        const grant = grants.find(g => g.id === app.grantId);
        const statusClass = `badge-${app.status === 'under_review' ? 'underreview' : app.status}`;
        
        return `
            <tr>
                <td>${app.companyName || 'N/A'}</td>
                <td>${grant ? grant.title : 'Unknown Grant'}</td>
                <td>${new Date(app.submittedAt).toLocaleDateString()}</td>
                <td><span class="status-badge ${statusClass}">${app.status.charAt(0).toUpperCase() + app.status.slice(1).replace('_', ' ')}</span></td>
                <td><button class="btn-small" onclick="reviewApplication('${app.id}')">Review</button></td>
            </tr>
        `;
    }).join('');
}

function reviewApplication(appId) {
    const applications = getApplications();
    const grants = getGrants();
    const app = applications.find(a => a.id === appId);
    if (!app) return;
    
    currentReviewAppId = appId;
    const grant = grants.find(g => g.id === app.grantId);
    
    const detailsDiv = document.getElementById('applicationDetails');
    detailsDiv.innerHTML = `
        <div style="margin-bottom: 20px;">
            <h3 style="color: #5a3fa3; margin-bottom: 15px;">Application Details</h3>
            <div style="background: #f9f7fc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <p><strong>Company Name:</strong> ${app.companyName || 'N/A'}</p>
                <p><strong>Grant:</strong> ${grant ? grant.title : 'Unknown'}</p>
                <p><strong>Submitted:</strong> ${new Date(app.submittedAt).toLocaleString()}</p>
                <p><strong>Current Status:</strong> <span class="status-badge badge-${app.status === 'under_review' ? 'underreview' : app.status}">${app.status.charAt(0).toUpperCase() + app.status.slice(1).replace('_', ' ')}</span></p>
            </div>
            <h4 style="color: #5a3fa3; margin-bottom: 10px;">Application Responses:</h4>
            <div style="background: #f9f7fc; padding: 15px; border-radius: 8px; max-height: 500px; overflow-y: auto;">
                ${Object.entries(app.responses || {}).map(([key, value]) => {
                    // Handle array values (from tables)
                    if (Array.isArray(value)) {
                        return `
                            <div style="margin-bottom: 15px;">
                                <strong>${formatFieldName(key)}:</strong>
                                <table style="width: 100%; margin-top: 10px; background: white; border-radius: 6px; overflow: hidden;">
                                    <thead style="background: #e8d9f0;">
                                        <tr>
                                            ${value.length > 0 ? Object.keys(JSON.parse(value[0] || '{}')).map(k => `<th style="padding: 8px; font-size: 11px;">${formatFieldName(k)}</th>`).join('') : ''}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${value.map((item, idx) => {
                                            try {
                                                const obj = typeof item === 'string' ? JSON.parse(item) : item;
                                                return `<tr>
                                                    ${Object.values(obj).map(v => `<td style="padding: 8px; font-size: 11px;">${v || ''}</td>`).join('')}
                                                </tr>`;
                                            } catch {
                                                return `<tr><td colspan="10" style="padding: 8px; font-size: 11px;">${item}</td></tr>`;
                                            }
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                    // Handle regular values
                    return `
                        <div style="margin-bottom: 15px;">
                            <strong>${formatFieldName(key)}:</strong>
                            <p style="margin-top: 5px; color: #7d6b8f; white-space: pre-wrap;">${value || 'N/A'}</p>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
    
    document.getElementById('reviewAppModal').classList.add('active');
}

function closeReviewModal() {
    document.getElementById('reviewAppModal').classList.remove('active');
    currentReviewAppId = null;
}

function updateApplicationStatus(status) {
    if (!currentReviewAppId) return;
    
    const applications = getApplications();
    const app = applications.find(a => a.id === currentReviewAppId);
    if (!app) return;
    
    app.status = status;
    app.reviewedAt = new Date().toISOString();
    app.reviewedBy = currentUser.id;
    
    saveApplications(applications);
    notifyFounderAboutStatus(app.userId, app.grantId, status);
    
    closeReviewModal();
    displayApplications();
    showNotification(`Application ${status}!`, 'success');
}

function formatFieldName(key) {
    return key.replace(/_/g, ' ')
              .replace(/([A-Z])/g, ' $1')
              .replace(/^./, str => str.toUpperCase())
              .trim();
}

function notifyFounderAboutStatus(userId, grantId, status) {
    const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
    const grants = getGrants();
    
    const statusMessages = {
        approved: 'Congratulations! Your application has been approved.',
        rejected: 'Your application has been rejected.',
        under_review: 'Your application is now under review.'
    };
    
    notifications.push({
        id: Date.now().toString() + Math.random(),
        userId: userId,
        type: 'application_status',
        title: `Application ${status.charAt(0).toUpperCase() + status.slice(1)}`,
        message: statusMessages[status] || `Your application status has been updated to ${status}.`,
        grantId: grantId,
        read: false,
        createdAt: new Date().toISOString()
    });
    
    localStorage.setItem('notifications', JSON.stringify(notifications));
}

