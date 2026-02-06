// Admin Dashboard - Statistics only
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'admin') {
    window.location.href = '../auth/login.html';
}

function getGrants() {
    const grants = localStorage.getItem('grants');
    return grants ? JSON.parse(grants) : [];
}

function getApplications() {
    const applications = localStorage.getItem('applications');
    return applications ? JSON.parse(applications) : [];
}

function updateStats() {
    const grants = getGrants();
    const applications = getApplications();
    
    const activeGrants = grants.filter(g => g.status === 'published').length;
    const pendingApps = applications.filter(a => a.status === 'pending').length;
    const approvedApps = applications.filter(a => a.status === 'approved').length;
    
    const totalGrantsEl = document.getElementById('totalGrants');
    const totalApplicationsEl = document.getElementById('totalApplications');
    const pendingAppsEl = document.getElementById('pendingApps');
    const approvedAppsEl = document.getElementById('approvedApps');
    
    if (totalGrantsEl) totalGrantsEl.textContent = activeGrants;
    if (totalApplicationsEl) totalApplicationsEl.textContent = applications.length;
    if (pendingAppsEl) pendingAppsEl.textContent = pendingApps;
    if (approvedAppsEl) approvedAppsEl.textContent = approvedApps;
}
