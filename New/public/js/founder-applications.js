// Founder My Applications
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'founder') {
    window.location.href = '../auth/login.html';
}

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    displayMyApplications();
});

function setupEventListeners() {
    document.getElementById('closeViewModal')?.addEventListener('click', closeViewModal);
    
    window.addEventListener('click', function(e) {
        const viewModal = document.getElementById('viewAppModal');
        if (e.target === viewModal) closeViewModal();
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

function displayMyApplications() {
    const applications = getMyApplications();
    const grants = getPublishedGrants();
    const tableBody = document.getElementById('applicationsTable');
    if (!tableBody) return;
    
    if (applications.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #a89bb8; padding: 40px;">No applications yet</td></tr>';
        return;
    }
    
    tableBody.innerHTML = applications.map(app => {
        const grant = grants.find(g => g.id === app.grantId);
        const statusClass = `badge-${app.status === 'under_review' ? 'underreview' : app.status}`;
        
        return `
            <tr>
                <td>${grant ? grant.title : 'Unknown Grant'}</td>
                <td>${new Date(app.submittedAt).toLocaleDateString()}</td>
                <td><span class="status-badge ${statusClass}">${app.status.charAt(0).toUpperCase() + app.status.slice(1).replace('_', ' ')}</span></td>
                <td><button class="btn-apply" style="padding: 8px 15px; font-size: 12px;" onclick="viewMyApplication('${app.id}')">View Details</button></td>
            </tr>
        `;
    }).join('');
}

function viewMyApplication(appId) {
    const applications = getMyApplications();
    const grants = getPublishedGrants();
    const app = applications.find(a => a.id === appId);
    if (!app) return;
    
    const grant = grants.find(g => g.id === app.grantId);
    const statusClass = `badge-${app.status === 'under_review' ? 'underreview' : app.status}`;
    
    const detailsDiv = document.getElementById('appDetailsContent');
    detailsDiv.innerHTML = `
        <div style="margin-bottom: 20px;">
            <h3 style="color: #5a3fa3; margin-bottom: 15px;">Application Details</h3>
            <div style="background: #f9f7fc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <p><strong>Grant:</strong> ${grant ? grant.title : 'Unknown'}</p>
                <p><strong>Amount:</strong> ${grant ? '$' + grant.amount.toLocaleString() : 'N/A'}</p>
                <p><strong>Submitted:</strong> ${new Date(app.submittedAt).toLocaleString()}</p>
                <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${app.status.charAt(0).toUpperCase() + app.status.slice(1).replace('_', ' ')}</span></p>
            </div>
            <h4 style="color: #5a3fa3; margin-bottom: 10px;">Your Responses:</h4>
            <div style="background: #f9f7fc; padding: 15px; border-radius: 8px;">
                ${Object.entries(app.responses || {}).map(([key, value]) => `
                    <div style="margin-bottom: 15px;">
                        <strong>${key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())}:</strong>
                        <p style="margin-top: 5px; color: #7d6b8f;">${value}</p>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    document.getElementById('viewAppModal').classList.add('active');
}

function closeViewModal() {
    document.getElementById('viewAppModal').classList.remove('active');
}


