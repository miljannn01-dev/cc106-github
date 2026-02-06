// Initialize Sample DOST Research Grant
// This script creates a pre-made grant with all the specified requirements

function initializeDOSTGrant() {
    const grants = JSON.parse(localStorage.getItem('grants') || '[]');
    
    // Check if DOST grant already exists
    const existingGrant = grants.find(g => g.id === 'dost_research_grant_2024');
    if (existingGrant) {
        return; // Grant already exists
    }
    
    // Set deadline to 1 year from now
    const deadline = new Date();
    deadline.setFullYear(deadline.getFullYear() + 1);
    
    const dostGrant = {
        id: 'dost_research_grant_2024',
        title: 'DOST Research and Development Grant Program',
        amount: 1500000, // 1.5M PHP
        description: 'Comprehensive research and development grant program supporting innovative projects in science and technology. This grant follows a structured 5-step application process covering basic information, project details, personnel requirements, budget allocation, and submission.',
        deadline: deadline.toISOString().split('T')[0],
        requirements: [
            'Must be a registered research institution or startup',
            'Project must align with DOST priority areas',
            'Maximum budget of 1.5M PHP per year',
            'Complete all 5 steps of the application process',
            'Provide detailed project documentation',
            'Include sustainability plan and GAD score'
        ],
        formType: 'dost_research', // Special form type
        status: 'published',
        createdAt: new Date().toISOString(),
        createdBy: 'system'
    };
    
    grants.push(dostGrant);
    localStorage.setItem('grants', JSON.stringify(grants));
    console.log('DOST Research Grant initialized successfully!');
}

// Initialize on page load if admin is viewing grants
document.addEventListener('DOMContentLoaded', function() {
    const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
    if (currentUser && currentUser.type === 'admin') {
        initializeDOSTGrant();
    }
});

// Also initialize when admin opens manage-grants page
if (window.location.pathname.includes('manage-grants.html')) {
    initializeDOSTGrant();
}

