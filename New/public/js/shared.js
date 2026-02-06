// Shared JavaScript for sidebar navigation and common functions
function getCurrentUser() {
    const user = localStorage.getItem('currentUser');
    return user ? JSON.parse(user) : null;
}

function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = '../index.html';
}

function setActiveNavItem() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidebar-menu a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function initializeSidebar() {
    const currentUser = getCurrentUser();
    const userName = document.getElementById('userName');
    if (userName && currentUser) {
        userName.textContent = currentUser.name || (currentUser.type === 'admin' ? 'Admin User' : 'Founder');
    }
    
    // Logout buttons
    document.getElementById('logoutBtn')?.addEventListener('click', logout);
    document.getElementById('logoutBtn2')?.addEventListener('click', logout);
    
    // Set active nav item
    setActiveNavItem();
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#85d88a' : '#d4a5e8'};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Check notifications count (for founder pages)
function checkNotifications() {
    const currentUser = getCurrentUser();
    if (!currentUser || currentUser.type !== 'founder') return;
    
    const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
    const unreadCount = notifications.filter(n => n.userId === currentUser.id && !n.read).length;
    const notificationCount = document.getElementById('notificationCount');
    
    if (notificationCount) {
        if (unreadCount > 0) {
            notificationCount.innerHTML = `<span class="notification-badge">${unreadCount}</span>`;
        } else {
            notificationCount.innerHTML = '';
        }
    }
}

// Add CSS for notifications if not already added
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

