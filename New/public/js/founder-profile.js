// Founder Profile
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'founder') {
    window.location.href = '../auth/login.html';
}

document.addEventListener('DOMContentLoaded', function() {
    loadProfile();
    document.getElementById('saveProfileBtn')?.addEventListener('click', saveProfile);
    checkNotifications();
});

function loadProfile() {
    if (!currentUser) return;
    
    const founderName = document.getElementById('founderName');
    const companyName = document.getElementById('companyName');
    const email = document.getElementById('email');
    
    if (founderName) founderName.value = currentUser.name || '';
    if (companyName) companyName.value = currentUser.companyName || '';
    if (email) email.value = currentUser.email || '';
}

function saveProfile() {
    const founderName = document.getElementById('founderName').value;
    const companyName = document.getElementById('companyName').value;
    const email = document.getElementById('email').value;
    
    currentUser.name = founderName;
    currentUser.companyName = companyName;
    currentUser.email = email;
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    
    const userNameEl = document.getElementById('userName');
    if (userNameEl) userNameEl.textContent = founderName;
    showNotification('Profile saved successfully!', 'success');
}


