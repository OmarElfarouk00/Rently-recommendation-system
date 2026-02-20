// DOM Elements
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const profileTrigger = document.getElementById('profileTrigger');
const profileMenu = document.getElementById('profileMenu');

// Create overlay for mobile sidebar
const overlay = document.createElement('div');
overlay.className = 'sidebar-overlay';
overlay.id = 'sidebarOverlay';
document.body.appendChild(overlay);

// Mobile menu functionality
function toggleSidebar() {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
}

function closeSidebar() {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

// Event listeners for sidebar
if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', toggleSidebar);
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', closeSidebar);
}

if (overlay) {
    overlay.addEventListener('click', closeSidebar);
}

// Profile dropdown functionality
function toggleProfileMenu() {
    profileMenu.classList.toggle('show');
}

function closeProfileMenu() {
    profileMenu.classList.remove('show');
}

if (profileTrigger) {
    profileTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleProfileMenu();
    });
}

// Close profile menu when clicking outside
document.addEventListener('click', (e) => {
    if (profileTrigger && profileMenu && !profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
        closeProfileMenu();
    }
});

// Navigation active state
const navLinks = document.querySelectorAll('.nav-link');
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        // Close sidebar on mobile after navigation
        if (window.innerWidth <= 768) {
            closeSidebar();
        }
    });
});

// Search functionality
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        console.log('Searching for:', searchTerm);
    });
}

// Table action buttons


// Chart initialization function
function initializeCharts(propertyTypeData) {
    const ctx = document.getElementById('propertyTypeChart');
    if (ctx && propertyTypeData) {
        const labels = propertyTypeData.map(item => item.propertyType);

        // const data = propertyTypeData.map(item => item.count);
        const data = propertyTypeData.map(item => Number(item.count)); 
        const total = data.reduce((acc, val) => acc + val, 0); 
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ef4444',
                        '#06b6d4'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter:
                        function (value) {
                            let percentage = ((value * 100) / total).toFixed(1);
                            return percentage + '%';
                        }
                    }
                }
            },
            plugins: [ChartDataLabels] 
        });
    }
}

// Auto-refresh data every 5 minutes
setInterval(() => {
    console.log('Refreshing dashboard data...');
}, 300000);

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        closeSidebar();
    }
});

// Form validation for search
const searchInputPage = document.querySelector('.search-input-page');
if (searchInputPage) {
    searchInputPage.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const form = e.target.closest('form');
            if (form) {
                form.submit();
            }
        }
    });
}

// Loading states for buttons
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    button.disabled = true;
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}

// Add loading to primary buttons
const primaryButtons = document.querySelectorAll('.btn-primary');
primaryButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        if (!button.disabled && !button.getAttribute('onclick')) {
            showLoading(button);
// filter the output 
            e.preventDefault();
            const form = e.target.closest('form');
            if (form) {
                form.submit();
            }
        }
    });
});

 function toggleProperty(element) {
        const arrow = element.querySelector('.arrow');
        const property = element.querySelector('.property-name');
        const propertyInfo = element.querySelector('.propertyOwner-name');
        propertyInfo.classList.toggle('show');
        arrow.classList.toggle('open');
        property.classList.toggle('show');
    }

function initializeReportsCharts() {
    const data = monthlyData;

    if (!data || !data.monthly || data.monthly.length === 0) {
        console.error('No rental data available.');
        return;
    }

    const labels = data.monthly.map(entry => entry.month);
    const rentals = data.monthly.map(entry => entry.rentals);

    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Monthly Rentals',
                data: rentals,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Rentals per Month'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return value + ' rentals';
                        }
                    }
                }
            }
        }
    });
}

function initializeReportStatsChart() {
    const data = monthlyReportData;

    if (!data || !data.monthly || data.monthly.length === 0) {
        console.error('No report data available.');
        return;
    }

    const labels = data.monthly.map(entry => entry.month);
    const reports = data.monthly.map(entry => entry.reports);

    const ctx = document.getElementById('reportChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Reports per Month',
                data: reports,
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: '#ff4d6d',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'User Reports per Month'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function openUserModal(clientId) {
    const url = window.location.pathname + '?show_receipt=1&id=' + clientId;
    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.getElementById('userDetails').innerHTML = html;
            document.getElementById('userModal').style.display = 'block';
        });
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}


window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        modal.style.display = "none";
    }
}

function updateStatus(clientId, newStatus) {
    fetch(window.location.pathname, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `update_status=1&id=${clientId}&status=${newStatus}`
    })
    .then(response => response.text())
    .then(result => {
         // You can improve this to a styled toast/snackbar
        document.getElementById('userModal').style.display = 'none';
        location.reload(); // Optional: Refresh the table to reflect status change
    });
}


// document.addEventListener('DOMContentLoaded', function () {
//     const select = document.getElementById('propertyTypeSelect');
//     const filters = {
//         apartment: document.getElementById('apartmentFilters'),
//         house: document.getElementById('houseFilters'),
//         villa: document.getElementById('villaFilters'),
//         room: document.getElementById('roomFilters')
//     };

//     function updateFilters() {
//         Object.values(filters).forEach(div => div.style.display = 'none');
//         const type = select.value;
//         if (filters[type]) {
//             filters[type].style.display = 'block';
//         }
//     }

//     select.addEventListener('change', updateFilters);
//     updateFilters(); 
// });


// document.addEventListener('DOMContentLoaded', () => {
//     document.querySelectorAll('.btn-action.delete').forEach(button => {
//         button.addEventListener('click', function () {
//             const userId = this.getAttribute('data-user-id');
//             const row = this.closest('tr');

//             if (!confirm('Are you sure you want to delete this user?')) return;

//             const formData = new FormData();
//             formData.append('action', 'delete_user');
//             formData.append('user_id', userId);

//             fetch(window.location.href, {
//                 method: 'POST',
//                 body: formData
//             })
//             .then(res => res.json())
//             .then(data => {
//                 if (data.success) {
//                     row.remove();
//                     showToast('User deleted successfully', 'success');
//                 } else {
//                     showToast(data.error || 'Failed to delete user', 'error');
//                 }
//             })
//             .catch(() => {
//                 showToast('Something went wrong', 'error');
//             });
//         });
//     });

// 

console.log('Dashboard initialized successfully');