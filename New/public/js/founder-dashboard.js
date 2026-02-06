// Founder Dashboard - Statistics only
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'founder') {
    window.location.href = '../auth/login.html';
}

function getPublishedGrants() {
    const grants = JSON.parse(localStorage.getItem('grants') || '[]');
    return grants.filter(g => g.status === 'published');
}

function getMyApplications() {
    const applications = JSON.parse(localStorage.getItem('applications') || '[]');
    return applications.filter(a => a.userId === currentUser.id);
}

function updateStats() {
    const grants = getPublishedGrants();
    const applications = getMyApplications();
    
    const approvedApps = applications.filter(a => a.status === 'approved');
    const totalFunding = approvedApps.reduce((sum, app) => {
        const grant = grants.find(g => g.id === app.grantId);
        return sum + (grant ? grant.amount : 0);
    }, 0);
    
    const grantCountEl = document.getElementById('grantCount');
    const appCountEl = document.getElementById('appCount');
    const approvedCountEl = document.getElementById('approvedCount');
    const totalFundingEl = document.getElementById('totalFunding');
    
    if (grantCountEl) grantCountEl.textContent = grants.length;
    if (appCountEl) appCountEl.textContent = applications.length;
    if (approvedCountEl) approvedCountEl.textContent = approvedApps.length;
    if (totalFundingEl) totalFundingEl.textContent = `$${totalFunding.toLocaleString()}`;
}


// Initialize founders list if needed
if (!localStorage.getItem('founders')) {
    localStorage.setItem('founders', JSON.stringify([]));
}

// Add current founder to founders list if not exists
const founders = JSON.parse(localStorage.getItem('founders') || '[]');
if (!founders.find(f => f.id === currentUser.id)) {
    founders.push({
        id: currentUser.id,
        email: currentUser.email,
        name: currentUser.name
    });
    localStorage.setItem('founders', JSON.stringify(founders));
}
