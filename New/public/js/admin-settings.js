// Admin Settings
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'admin') {
    window.location.href = '../auth/login.html';
}

document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
    document.getElementById('saveSettingsBtn')?.addEventListener('click', saveSettings);
});

function loadSettings() {
    if (!currentUser) return;
    
    const adminName = document.getElementById('adminName');
    const adminEmail = document.getElementById('adminEmail');
    
    if (adminName) adminName.value = currentUser.name || '';
    if (adminEmail) adminEmail.value = currentUser.email || '';
}

function saveSettings() {
    const adminName = document.getElementById('adminName').value;
    const adminEmail = document.getElementById('adminEmail').value;
    
    currentUser.name = adminName;
    currentUser.email = adminEmail;
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    
    const userNameEl = document.getElementById('userName');
    if (userNameEl) userNameEl.textContent = adminName;
    showNotification('Settings saved successfully!', 'success');
}

