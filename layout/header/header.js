// Header Component JavaScript
class HeaderComponent {
    constructor() {
        if (document.querySelector('.header-main-header')) {
            this.init();
        }
    }
    
    init() {
        this.setupUserMenu();
        this.setupThemeToggle();
    }
    
    setupUserMenu() {
        const userMenuToggle = document.querySelector('.header-user-menu-toggle');
        const userMenuDropdown = document.getElementById('userMenuDropdown');
        
        if (userMenuToggle && userMenuDropdown) {
            userMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenuDropdown.classList.toggle('show');
            });
            
            document.addEventListener('click', () => {
                userMenuDropdown.classList.remove('show');
            });
        }
    }
    
    setupThemeToggle() {
        const themeToggle = document.querySelector('.header-theme-toggle');
        if (themeToggle) {
            // Load saved theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            this.updateThemeIcon();
            
            themeToggle.addEventListener('click', () => {
                this.toggleTheme();
            });
        }
    }
    
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        this.updateThemeIcon();
    }
    
    updateThemeIcon() {
        const themeToggle = document.querySelector('.header-theme-toggle i');
        const currentTheme = document.documentElement.getAttribute('data-theme');
        
        if (themeToggle) {
            themeToggle.className = currentTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
}

// Global functions for header
function toggleUserMenu() {
    const dropdown = document.getElementById('userMenuDropdown');
    
    if (dropdown) {
        // Force show with inline style
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        } else {
            dropdown.style.display = 'block';
            dropdown.style.position = 'absolute';
            dropdown.style.top = '100%';
            dropdown.style.right = '0';
            dropdown.style.background = 'white';
            dropdown.style.border = '1px solid #ccc';
            dropdown.style.borderRadius = '8px';
            dropdown.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            dropdown.style.minWidth = '160px';
            dropdown.style.zIndex = '9999';
            dropdown.style.marginTop = '8px';
        }
        console.log('Dropdown display:', dropdown.style.display);
    }
}

function logout() {
    if (confirm('Yakin ingin logout?')) {
        // Traditional logout redirect - use window.BASE_URL or fallback
        const baseURL = window.BASE_URL || (window.location.protocol + '//' + window.location.host + '/Pendataan-desa/');
        // Clear hash and redirect
        window.location.hash = '';
        window.location.replace(baseURL + 'pages/auth/logout.php');
    }
}

// Remove header toggleTheme completely to avoid conflicts

function toggleSidebar() {
    if (window.sidebarComponent) {
        window.sidebarComponent.toggle();
    }
}

// Initialize header component
document.addEventListener('DOMContentLoaded', () => {
    window.headerComponent = new HeaderComponent();
});

// Initialize theme immediately
(function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Update icon if toggle exists
    setTimeout(() => {
        const themeToggle = document.querySelector('.header-theme-toggle i');
        if (themeToggle) {
            themeToggle.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }, 100);
})();