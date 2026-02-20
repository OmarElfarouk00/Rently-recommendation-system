        // Category Navigation
        const categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(item => {
            item.addEventListener('click', () => {
                categoryItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });

                document.addEventListener('DOMContentLoaded', function () {
            // // Menu toggle
            // const menuToggle = document.getElementById('menuToggle');
            // const menuContent = document.getElementById('menuContent');
            // console.log('menuToggle:', menuToggle); // Will print 'null' if not found



            // menuToggle.addEventListener('click', function (e) {
            //     e.stopPropagation();
            //     menuContent.classList.toggle('active');
            // });

            // document.addEventListener('click', function (e) {
            //     if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
            //         menuContent.classList.remove('active');
            //     }
            // });

            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }
            });


        // Favorite Button Functionality
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', () => {
                button.classList.toggle('active');
                const icon = button.querySelector('i');
                if (button.classList.contains('active')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        });

//         // Add this to your existing JavaScript
// const menuToggle = document.getElementById('menuToggle');
// const menuContent = document.getElementById('menuContent');
// // const languageSelector = document.querySelector('.language-selector');

// // Toggle menu on click
// menuToggle.addEventListener('click', (e) => {
//     e.stopPropagation();
//     menuContent.classList.toggle('active');
// });

// // Close menu when clicking outside
// document.addEventListener('click', (e) => {
//     if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
//         menuContent.classList.remove('active');
//     }
// });

// Language selection function

    // Change language on click

    // Function to change the language

// Mobile-specific handling for language selector
if (window.innerWidth <= 768) {
    languageSelector.addEventListener('click', (e) => {
        e.stopPropagation();
        languageSelector.classList.toggle('active');
    });
}

// Initialize language from localStorage if available
document.addEventListener('DOMContentLoaded', () => {
    const savedLanguage = localStorage.getItem('selectedLanguage');
    if (savedLanguage) {
        changeLanguage(savedLanguage);
    }
});


// Function to show the negotiation popup-----------------
function showNegotiationPopup() {
    const popup = document.getElementById('negotiationPopup');
    popup.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

// Close popup when clicking the close button
document.querySelector('.close-popup').addEventListener('click', () => {
    document.getElementById('negotiationPopup').style.display = 'none';
    document.body.style.overflow = ''; // Restore scrolling
});

// Close popup when clicking outside
document.getElementById('negotiationPopup').addEventListener('click', (e) => {
    if (e.target.classList.contains('popup-overlay')) {
        document.getElementById('negotiationPopup').style.display = 'none';
        document.body.style.overflow = '';
    }
});

// Show success message
function showSuccessMessage() {
    const message = document.createElement('div');
    message.className = 'success-message';
    message.textContent = 'Your offer has been sent to the property owner!';
    document.body.appendChild(message);

    // Remove the message after 3 seconds
    setTimeout(() => {
        message.remove();
    }, 3000);
}

// Handle form submission
document.getElementById('negotiationForm').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const offerPrice = document.getElementById('offerPrice').value;
    const message = document.getElementById('message').value;
    
    // Here you would typically send this data to your server
    console.log('Negotiation submitted:', {
        price: offerPrice,
        message: message
    });
    
    // Show success message
    showSuccessMessage();
    
    // Close popup
    document.getElementById('negotiationPopup').style.display = 'none';
    document.body.style.overflow = '';
    
    // Reset form
    e.target.reset();
});

// Validate offer price
document.getElementById('offerPrice').addEventListener('input', (e) => {
    const input = e.target;
    const listedPrice = 850; // Get this from your property data
    
    if (input.value > listedPrice) {
        input.setCustomValidity('Your offer cannot be higher than the listed price');
    } else if (input.value < 1) {
        input.setCustomValidity('Please enter a valid offer amount');
    } else {
        input.setCustomValidity('');
    }
});

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${type === 'error' ? '⚠️' : type === 'success' ? '✅' : 'ℹ️'}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;

            // Add to page
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container';
                document.body.appendChild(toastContainer);
            }

            toastContainer.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => toast.remove(), 5000);

            // Slide in animation
            setTimeout(() => toast.classList.add('show'), 100);
        }


                document.addEventListener('DOMContentLoaded', function () {
            // Menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const menuContent = document.getElementById('menuContent');


            menuToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                menuContent.classList.toggle('active');
            });

            document.addEventListener('click', function (e) {
                if (!menuContent.contains(e.target) && !menuToggle.contains(e.target)) {
                    menuContent.classList.remove('active');
                }
            });

            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationContainer = document.getElementById('notificationContainer');
            const notificationList = document.getElementById('notificationList');

            // Toggle dropdown
            notificationIcon.addEventListener('click', function () {
                notificationContainer.classList.toggle('show');
                fetch('php files/fetch_notifications.php?action=mark_read');
            });

                    fetch('php files/fetch_notifications.php?action=fetch')
                .then(response => response.json())
                .then(notifications => {
                    notificationList.innerHTML = ''; // Clear previous content

                    if (!notifications || notifications.length === 0) {
                        notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                        return;
                    }
                    notifications.forEach(notification => {
                        const notif = document.createElement('div');
                        notif.classList.add('notification-item');
                        console.log(notification.is_read);
                        // if (notification.is_read === 0) {
                        //     notif.style.backgroundColor = '#f0f0f0';
                        // } else {
                        //     notif.style.backgroundColor = '#dff0d8';
                        // }
                        notif.innerHTML = `
                    <h3>${notification.message}</h3>
                    <strong?><span>${new Date(notification.timestamp).toLocaleString()}</span></strong>
                `;
                        notificationList.appendChild(notif);
                    });
                })
                .catch(err => {
                    console.error('Error fetching notifications:', err);
                    notificationList.innerHTML = '<p class="notification-item">Nothing here</p>';
                });

            // Optional: Clear notifications logic
            document.querySelector('.clear-notifications').addEventListener('click', () => {
                fetch('php files/fetch_notifications.php?action=clear', {
                    method: 'POST'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const notificationList = document.querySelector('.notification-list');
                            notificationList.innerHTML = '<p class="notification-item">No notifications</p>';
                        } else {
                            console.error('Clear failed:', data.error);
                        }
                    })
                    .catch(err => console.error('Error:', err));
            });

            // notif.addEventListener('click', () => {
            //     fetch('php files/fetch_notifications.php?action=mark_read', {
            //         method: 'POST',
            //     })
            //         .then(res => res.json())
            //         .then(data => {
            //             if (data.success) {
            //                 notif.style.backgroundColor = '#dff0d8'; // Mark visually as read
            //             }
            //         });
            // });

        });
