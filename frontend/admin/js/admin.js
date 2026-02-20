/**
 * Enhanced Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme
    initTheme();
    
    // Toggle sidebar
    const toggleBtn = document.getElementById('toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Save sidebar state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
        
        // Restore sidebar state from localStorage
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }
    
    // Mobile sidebar toggle
    const mobileToggleBtn = document.querySelector('.mobile-menu-toggle');
    if (mobileToggleBtn) {
        mobileToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show-mobile');
            document.body.classList.toggle('sidebar-open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992 && 
                sidebar.classList.contains('show-mobile') && 
                !sidebar.contains(e.target) && 
                !mobileToggleBtn.contains(e.target)) {
                sidebar.classList.remove('show-mobile');
                document.body.classList.remove('sidebar-open');
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }
    
    // Initialize dropdowns
    initDropdowns();
    
    // Form validation
    initFormValidation();
    
    // Image preview for file inputs
    initImagePreviews();
    
    // Confirm delete actions
    initDeleteConfirmations();
    
    // Initialize tooltips
    initTooltips();
    
    // Handle responsive tables
    initResponsiveTables();
    
    // Handle tabs
    initTabs();
    
    // Initialize charts if they exist
    if (typeof Chart !== 'undefined') {
        initCharts();
    }
});

/**
 * Initialize theme
 */
function initTheme() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (!themeToggle) return;
    
    // Check for saved theme preference or respect OS preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
        updateThemeIcon('dark');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        updateThemeIcon('light');
    }
    
    // Theme toggle click handler
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add transition class
        document.body.classList.add(newTheme === 'dark' ? 'dark-mode-transition' : 'light-mode-transition');
        
        // Update theme
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
        
        // Remove transition class
        setTimeout(() => {
            document.body.classList.remove('dark-mode-transition', 'light-mode-transition');
        }, 500);
        
        // Update charts if they exist
        if (typeof Chart !== 'undefined') {
            updateChartsTheme(newTheme);
        }
    });
}

/**
 * Update theme icon
 */
function updateThemeIcon(theme) {
    const themeToggle = document.querySelector('.theme-toggle');
    if (!themeToggle) return;
    
    const icon = themeToggle.querySelector('i');
    if (icon) {
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
}

/**
 * Initialize dropdowns
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close all other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        const m = d.querySelector('.dropdown-menu');
                        if (m) m.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                menu.classList.toggle('show');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });
    });
    
    // Prevent dropdown menu clicks from closing the dropdown
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    dropdownMenus.forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (form.classList.contains('needs-validation')) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        }
    });
}

/**
 * Initialize image previews
 */
function initImagePreviews() {
    const imageInputs = document.querySelectorAll('.image-upload input[type="file"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const preview = this.parentElement.querySelector('.image-preview');
            if (preview) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.style.backgroundImage = `url(${e.target.result})`;
                        preview.classList.add('has-image');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            }
        });
    });
}

/**
 * Initialize delete confirmations
 */
function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title');
            if (title) {
                this.setAttribute('data-original-title', title);
                this.removeAttribute('title');
                
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'tooltip';
                tooltipEl.textContent = title;
                document.body.appendChild(tooltipEl);
                
                const rect = this.getBoundingClientRect();
                tooltipEl.style.top = `${rect.top - tooltipEl.offsetHeight - 10}px`;
                tooltipEl.style.left = `${rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2)}px`;
                
                // Ensure tooltip is within viewport
                const tooltipRect = tooltipEl.getBoundingClientRect();
                if (tooltipRect.left < 0) {
                    tooltipEl.style.left = '10px';
                } else if (tooltipRect.right > window.innerWidth) {
                    tooltipEl.style.left = `${window.innerWidth - tooltipRect.width - 10}px`;
                }
                
                setTimeout(() => {
                    tooltipEl.style.opacity = '1';
                }, 10);
                
                this.addEventListener('mouseleave', function onMouseLeave() {
                    tooltipEl.style.opacity = '0';
                    setTimeout(() => {
                        tooltipEl.remove();
                    }, 300);
                    this.setAttribute('title', this.getAttribute('data-original-title'));
                    this.removeAttribute('data-original-title');
                    this.removeEventListener('mouseleave', onMouseLeave);
                });
            }
        });
    });
}

/**
 * Initialize responsive tables
 */
function initResponsiveTables() {
    if (window.innerWidth < 768) {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            const headerCells = table.querySelectorAll('thead th');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    if (headerCells[index]) {
                        const headerText = headerCells[index].textContent;
                        cell.setAttribute('data-label', headerText);
                    }
                });
            });
        });
    }
}

/**
 * Initialize tabs
 */
function initTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const tabId = this.getAttribute('href');
            const tabContent = document.querySelector(tabId);
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Deactivate all tab links
            document.querySelectorAll('.tab-link').forEach(tabLink => {
                tabLink.classList.remove('active');
            });
            
            // Activate current tab and content
            this.classList.add('active');
            if (tabContent) {
                tabContent.classList.add('active');
            }
        });
    });
    
    // Initialize first tab if exists
    const firstTab = document.querySelector('.tab-link');
    if (firstTab) {
        firstTab.click();
    }
}

/**
 * Initialize charts
 */
function initCharts() {
    // Set chart defaults based on current theme
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    setChartDefaults(currentTheme);
    
    // Initialize specific charts if they exist
    // This will be called by the specific page scripts
}

/**
 * Update charts theme
 */
function updateChartsTheme(theme) {
    setChartDefaults(theme);
    
    // Update all charts
    if (window.Chart && Chart.instances) {
        Object.values(Chart.instances).forEach(chart => {
            chart.update();
        });
    }
}

/**
 * Set chart defaults based on theme
 */
function setChartDefaults(theme) {
    if (!window.Chart) return;
    
    const textColor = theme === 'dark' ? '#e0e0e0' : '#666666';
    const gridColor = theme === 'dark' ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    
    Chart.defaults.color = textColor;
    Chart.defaults.scale.grid.color = gridColor;
}