// Authentication helper functions
function getCurrentUser() {
  const user = localStorage.getItem('currentUser');
  return user ? JSON.parse(user) : null;
}

function setCurrentUser(user) {
  localStorage.setItem('currentUser', JSON.stringify(user));
}

function logout() {
  localStorage.removeItem('currentUser');
  window.location.href = '/index.html';
}

function isLoggedIn() {
  return getCurrentUser() !== null;
}

function requireLogin(redirectTo = '/auth/login.html') {
  if (!isLoggedIn()) {
    window.location.href = redirectTo;
  }
}
