// Global JavaScript untuk Sistem Informasi Publik
// File: public-portal/assets/js/global.js

(function() {
    'use strict';
    
    // Global configuration
    window.PublicPortalConfig = {
        baseUrl: window.location.origin + '/public-portal/',
        apiUrl: window.location.origin + '/public-portal/api/',
        mainSystemUrl: window.location.origin + '/Pendataan-desa/',
        version: '1.0.0'
    };
    
    // Utility functions
    window.PublicUtils = {
        
        // Format number with thousand separator
        formatNumber: function(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        },
        
        // Format date to Indonesian format
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },
        
        // Show loading spinner
        showLoading: function(container) {
            const loadingHtml = `
                <div class="loading">
                    <div class="spinner"></div>
                    <span style="margin-left: 10px;">Memuat data...</span>
                </div>
            `;
            
            if (typeof container === 'string') {
                const element = document.querySelector(container);
                if (element) element.innerHTML = loadingHtml;
            } else if (container) {
                container.innerHTML = loadingHtml;
            }
        },
        
        // Hide loading spinner
        hideLoading: function(container) {
            if (typeof container === 'string') {
                const element = document.querySelector(container);
                if (element) {
                    const loading = element.querySelector('.loading');
                    if (loading) loading.remove();
                }
            } else if (container) {
                const loading = container.querySelector('.loading');
                if (loading) loading.remove();
            }
        },
        
        // Show notification
        showNotification: function(message, type = 'info', duration = 3000) {
            // Remove existing notifications
            const existing = document.querySelectorAll('.notification');
            existing.forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span>${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `;
            
            // Add styles
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                padding: 15px 20px;
                border-radius: 6px;
                color: white;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 400px;
            `;
            
            // Set background color based on type
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            notification.style.backgroundColor = colors[type] || colors.info;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto hide
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, duration);
            
            // Close button
            notification.querySelector('.notification-close').onclick = () => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            };
        },
        
        // API call helper
        apiCall: async function(endpoint, options = {}) {
            const url = PublicPortalConfig.apiUrl + endpoint;
            
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            };
            
            const finalOptions = { ...defaultOptions, ...options };
            
            try {
                const response = await fetch(url, finalOptions);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('API call failed:', error);
                throw error;
            }
        },
        
        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Get URL parameters
        getUrlParam: function(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        },
        
        // Set URL parameter without reload
        setUrlParam: function(param, value) {
            const url = new URL(window.location);
            url.searchParams.set(param, value);
            window.history.pushState({}, '', url);
        },
        
        // Sanitize HTML
        sanitizeHtml: function(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },
        
        // Copy to clipboard
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showNotification('Berhasil disalin ke clipboard', 'success', 2000);
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showNotification('Berhasil disalin ke clipboard', 'success', 2000);
            }
        }
    };
    
    // Global event handlers
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips if any
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', function() {
                const text = this.getAttribute('data-tooltip');
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'tooltip-popup';
                tooltipEl.textContent = text;
                tooltipEl.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 1000;
                    pointer-events: none;
                `;
                document.body.appendChild(tooltipEl);
                
                const rect = this.getBoundingClientRect();
                tooltipEl.style.left = rect.left + 'px';
                tooltipEl.style.top = (rect.top - tooltipEl.offsetHeight - 5) + 'px';
            });
            
            tooltip.addEventListener('mouseleave', function() {
                const tooltipEl = document.querySelector('.tooltip-popup');
                if (tooltipEl) tooltipEl.remove();
            });
        });
        
        // Handle external links
        const externalLinks = document.querySelectorAll('a[href^="http"]');
        externalLinks.forEach(link => {
            if (!link.href.includes(window.location.hostname)) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        });
    });
    
    // Handle errors globally
    window.addEventListener('error', function(e) {
        console.error('Global error:', e.error);
        // Could send to logging service in production
    });
    
    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled promise rejection:', e.reason);
        // Could send to logging service in production
    });
    
})();