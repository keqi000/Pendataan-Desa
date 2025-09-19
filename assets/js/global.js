// Global JavaScript - Sistem Pendataan Desa
class PendataanDesa {
    constructor() {
        this.baseURL = this.getBaseURL();
        this.apiURL = this.baseURL + 'api/';
        this.init();
    }
    
    getBaseURL() {
        const hostname = window.location.hostname;
        const protocol = window.location.protocol;
        const port = window.location.port;
        
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            return `${protocol}//${hostname}${port ? ':' + port : ''}/Pendataan-desa/`;
        }
        return `${protocol}//${hostname}/`;
    }
    
    init() {
        this.setupPageLoader();
        this.setupContentLoader();
        this.setupThemeToggle();
        this.setupHashNavigation();
        this.setupGlobalEvents();
    }
    
    // Page Loader Management
    setupPageLoader() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                this.hidePageLoader();
            }, 500);
        });
        
        window.addEventListener('beforeunload', () => {
            this.showPageLoader();
        });
    }
    
    showPageLoader() {
        const loader = document.querySelector('.page-loader');
        if (loader) {
            loader.classList.remove('hidden');
        }
    }
    
    hidePageLoader() {
        const loader = document.querySelector('.page-loader');
        if (loader) {
            loader.classList.add('hidden');
        }
    }
    
    // Content Loader Management
    setupContentLoader() {
        this.contentLoader = null;
    }
    
    showContentLoader(container = 'main') {
        const target = typeof container === 'string' ? document.querySelector(container) : container;
        if (!target) return;
        
        target.style.position = 'relative';
        
        const loader = document.createElement('div');
        loader.className = 'content-loader';
        loader.innerHTML = '<div class="loader-spinner"></div>';
        
        target.appendChild(loader);
        this.contentLoader = loader;
    }
    
    hideContentLoader() {
        if (this.contentLoader) {
            this.contentLoader.classList.add('hidden');
            setTimeout(() => {
                if (this.contentLoader && this.contentLoader.parentNode) {
                    this.contentLoader.parentNode.removeChild(this.contentLoader);
                }
                this.contentLoader = null;
            }, 300);
        }
    }
    
    // Theme Management
    setupThemeToggle() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
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
    }
    
    // Hash Navigation
    setupHashNavigation() {
        window.addEventListener('hashchange', () => {
            this.handleHashChange();
        });
        
        // Handle initial hash
        if (window.location.hash) {
            this.handleHashChange();
        }
    }
    
    handleHashChange() {
        const hash = window.location.hash.substring(1);
        if (hash) {
            this.navigateToPage(hash);
        }
    }
    
    navigateToPage(page) {
        this.showContentLoader();
        
        // Simulate page transition
        setTimeout(() => {
            window.location.href = this.baseURL + page;
        }, 300);
    }
    
    // Global Events
    setupGlobalEvents() {
        // Form validation
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('validate-form')) {
                if (!this.validateForm(e.target)) {
                    e.preventDefault();
                }
            }
        });
        
        // Auto-save forms
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('auto-save')) {
                this.debounce(() => {
                    this.autoSaveForm(e.target.form);
                }, 1000)();
            }
        });
    }
    
    // API Methods
    async apiRequest(endpoint, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(this.apiURL + endpoint, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Request failed:', error);
            this.showNotification('Terjadi kesalahan saat mengambil data', 'error');
            throw error;
        }
    }
    
    // Utility Methods
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Field ini wajib diisi');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });
        
        return isValid;
    }
    
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = 'var(--danger)';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '4px';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
        field.style.borderColor = 'var(--danger)';
    }
    
    clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.style.borderColor = '';
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        const colors = {
            success: 'var(--success)',
            error: 'var(--danger)',
            warning: 'var(--warning)',
            info: 'var(--info)'
        };
        
        notification.style.backgroundColor = colors[type] || colors.info;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }
    
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR'
        }).format(amount);
    }
    
    formatDate(date) {
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    }
}

// Global Functions
let pendataanDesa;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    pendataanDesa = new PendataanDesa();
});

// Global utility functions
function showLoader(container) {
    if (pendataanDesa) {
        pendataanDesa.showContentLoader(container);
    }
}

function hideLoader() {
    if (pendataanDesa) {
        pendataanDesa.hideContentLoader();
    }
}

function apiRequest(endpoint, options) {
    if (pendataanDesa) {
        return pendataanDesa.apiRequest(endpoint, options);
    }
}

function showNotification(message, type) {
    if (pendataanDesa) {
        pendataanDesa.showNotification(message, type);
    }
}

function navigateToPage(page) {
    if (pendataanDesa) {
        pendataanDesa.navigateToPage(page);
    }
}

function loadPage(page) {
    if (window.mainLayoutComponent) {
        window.mainLayoutComponent.loadPage(page);
    }
}