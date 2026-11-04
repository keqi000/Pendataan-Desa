// Dashboard Main JavaScript untuk Sistem Informasi Publik
// File: public-portal/pages/dashboard/dashboard.js

(function() {
    'use strict';
    
    class DashboardManager {
        constructor() {
            if (document.querySelector('.hero-section')) {
                this.init();
            }
        }
        
        init() {
            this.setupAnimations();
            this.setupInteractions();
            this.loadDynamicContent();
        }
        
        setupAnimations() {
            // Animate elements on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, observerOptions);
            
            // Observe stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
            
            // Observe other sections
            const sections = document.querySelectorAll('.card, .top-desa-item, .activity-item');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = `opacity 0.5s ease ${index * 0.05}s, transform 0.5s ease ${index * 0.05}s`;
                observer.observe(section);
            });
        }
        
        setupInteractions() {
            // Add hover effects to interactive elements
            const interactiveElements = document.querySelectorAll('.top-desa-item, .activity-item, .category-item');
            
            interactiveElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px) scale(1.02)';
                });
                
                element.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0) scale(1)';
                });
            });
            
            // Add click handlers for desa items
            const desaItems = document.querySelectorAll('.top-desa-item');
            desaItems.forEach(item => {
                item.addEventListener('click', function() {
                    const detailButton = this.querySelector('.btn');
                    if (detailButton) {
                        detailButton.click();
                    }
                });
                
                // Add cursor pointer
                item.style.cursor = 'pointer';
            });
            
            // Add loading states to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Don't add loading to external links
                    if (this.getAttribute('target') === '_blank') return;
                    
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
                    this.disabled = true;
                    
                    // Reset after 3 seconds (fallback)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 3000);
                });
            });
        }
        
        async loadDynamicContent() {
            // Load real-time statistics
            try {
                await this.updateStatistics();
            } catch (error) {
                console.error('Error loading dynamic content:', error);
            }
        }
        
        async updateStatistics() {
            // Update quick stats with real-time data
            const quickStatsData = await this.fetchData('quick-stats');
            if (quickStatsData) {
                this.updateQuickStatsDisplay(quickStatsData);
            }
        }
        
        async fetchData(endpoint) {
            try {
                const response = await fetch(`${PublicPortalConfig.baseUrl}pages/dashboard/php/dashboard-functions.php?endpoint=${endpoint}`);
                const result = await response.json();
                return result.success ? result.data : null;
            } catch (error) {
                console.error(`Error fetching ${endpoint}:`, error);
                return null;
            }
        }
        
        updateQuickStatsDisplay(data) {
            const statCards = document.querySelectorAll('.stat-card');
            
            statCards.forEach((card, index) => {
                if (data[index]) {
                    const valueElement = card.querySelector('.stat-value');
                    const trendElement = card.querySelector('.trend-indicator');
                    
                    if (valueElement) {
                        // Animate number change
                        this.animateNumber(valueElement, data[index].value);
                    }
                    
                    if (trendElement) {
                        trendElement.textContent = data[index].trend;
                    }
                }
            });
        }
        
        updateActivitiesDisplay(data) {
            const activityList = document.querySelector('.activity-list');
            if (!activityList || !data) return;
            
            // Clear existing activities
            activityList.innerHTML = '';
            
            // Add new activities
            data.forEach(activity => {
                const activityElement = document.createElement('div');
                activityElement.className = 'activity-item';
                activityElement.innerHTML = `
                    <div class="activity-icon">
                        <i class="${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <h5>${activity.title}</h5>
                        <p>${activity.description}</p>
                        <small class="activity-time">${activity.time}</small>
                    </div>
                `;
                
                activityList.appendChild(activityElement);
            });
        }
        
        animateNumber(element, targetValue) {
            const currentValue = parseInt(element.textContent.replace(/[^0-9]/g, '')) || 0;
            const target = parseInt(targetValue.toString().replace(/[^0-9]/g, ''));
            
            if (currentValue === target) return;
            
            const duration = 1000; // 1 second
            const steps = 30;
            const stepValue = (target - currentValue) / steps;
            const stepDuration = duration / steps;
            
            let current = currentValue;
            let step = 0;
            
            const timer = setInterval(() => {
                step++;
                current += stepValue;
                
                if (step >= steps) {
                    current = target;
                    clearInterval(timer);
                }
                
                // Format number with thousand separator
                const formattedValue = Math.round(current).toLocaleString('id-ID');
                element.textContent = formattedValue;
            }, stepDuration);
        }
        
        // Method to refresh all dashboard data
        async refreshDashboard() {
            PublicUtils.showLoading('.dashboard-content');
            
            try {
                await this.updateStatistics();
                
                // Update charts if available
                if (window.dashboardCharts) {
                    await window.dashboardCharts.updateCharts();
                }
                
                PublicUtils.showNotification('Dashboard berhasil diperbarui', 'success');
                
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                PublicUtils.showNotification('Gagal memperbarui dashboard', 'error');
            } finally {
                PublicUtils.hideLoading('.dashboard-content');
            }
        }
    }
    
    // CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        .animate-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        
        .chart-error {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: #666;
        }
        
        .chart-error i {
            font-size: 2rem;
            color: #ffc107;
        }
    `;
    document.head.appendChild(style);
    
    // Initialize dashboard manager
    document.addEventListener('DOMContentLoaded', () => {
        window.dashboardManager = new DashboardManager();
    });
    
    // Global refresh function
    window.refreshDashboard = function() {
        if (window.dashboardManager) {
            window.dashboardManager.refreshDashboard();
        }
    };
    
})();