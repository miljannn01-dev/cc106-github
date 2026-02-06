// Founder Notifications
const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
if (!currentUser || currentUser.type !== 'founder') {
    window.location.href = '../auth/login.html';
}

document.addEventListener('DOMContentLoaded', function() {
    displayNotifications();
    checkNotifications();
});

function displayNotifications() {
    const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
    const myNotifications = notifications
        .filter(n => n.userId === currentUser.id)
        .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    
    const notificationsList = document.getElementById('notificationsList');
    if (!notificationsList) return;
    
    if (myNotifications.length === 0) {
        notificationsList.innerHTML = '<div class="no-data">No notifications yet</div>';
        return;
    }
    
    notificationsList.innerHTML = myNotifications.map(notif => `
        <div class="notification-item ${notif.read ? '' : 'unread'}">
            <div class="notification-text">
                <strong>${notif.title}</strong>
                <p style="margin-top: 5px; color: #7d6b8f;">${notif.message}</p>
            </div>
            <div class="notification-time">${new Date(notif.createdAt).toLocaleDateString()}</div>
        </div>
    `).join('');
    
    // Mark as read
    myNotifications.forEach(notif => {
        if (!notif.read) {
            notif.read = true;
        }
    });
    
    notifications.forEach(n => {
        const myNotif = myNotifications.find(mn => mn.id === n.id);
        if (myNotif) {
            n.read = true;
        }
    });
    
    localStorage.setItem('notifications', JSON.stringify(notifications));
    checkNotifications();
}


