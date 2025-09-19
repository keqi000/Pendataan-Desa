// Dashboard JavaScript
if (typeof window.Dashboard === 'undefined') {
window.Dashboard = class {
    constructor() {
        this.charts = {};
        this.init();
    }
    
    init() {
        this.setupCharts();
        this.setupRealTimeUpdates();
        this.setupQuickActions();
    }
    
    setupCharts() {
        // Setup Chart.js if available
        if (typeof Chart !== 'undefined') {
            this.initPendudukChart();
        } else {
            // Load Chart.js dynamically
            this.loadChartJS().then(() => {
                this.initPendudukChart();
            });
        }
    }
    
    async loadChartJS() {
        return new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = resolve;
            document.head.appendChild(script);
        });
    }
    
    initPendudukChart() {
        const ctx = document.getElementById('chartPenduduk');
        if (!ctx) return;
        
        // Sample data - replace with real data from API
        const data = {
            labels: ['Laki-laki', 'Perempuan'],
            datasets: [{
                data: [150, 140],
                backgroundColor: [
                    'var(--primary-color)',
                    'var(--secondary-color)'
                ],
                borderWidth: 0
            }]
        };
        
        this.charts.penduduk = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
    
    setupRealTimeUpdates() {
        // Update dashboard data every 5 minutes
        setInterval(() => {
            this.updateDashboardData();
        }, 300000); // 5 minutes
    }
    
    async updateDashboardData() {
        try {
            showLoader('.dashboard-stats');
            
            // Fetch updated data from API
            const response = await apiRequest('dashboard/stats');
            
            if (response) {
                this.updateStatCards(response);
                this.updateCharts(response);
            }
            
        } catch (error) {
            console.error('Failed to update dashboard data:', error);
        } finally {
            hideLoader();
        }
    }
    
    updateStatCards(data) {
        // Update stat numbers
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            const numberEl = card.querySelector('.stat-number');
            if (numberEl && data.stats && data.stats[index]) {
                this.animateNumber(numberEl, data.stats[index]);
            }
        });
    }
    
    animateNumber(element, targetValue) {
        const currentValue = parseInt(element.textContent) || 0;
        const increment = (targetValue - currentValue) / 20;
        let current = currentValue;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= targetValue) || 
                (increment < 0 && current <= targetValue)) {
                current = targetValue;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 50);
    }
    
    updateCharts(data) {
        if (this.charts.penduduk && data.chartData) {
            this.charts.penduduk.data.datasets[0].data = data.chartData;
            this.charts.penduduk.update();
        }
    }
    
    setupQuickActions() {
        const actionBtns = document.querySelectorAll('.action-btn');
        actionBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Add loading state
                const icon = btn.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                // Reset after navigation
                setTimeout(() => {
                    icon.className = originalClass;
                }, 1000);
            });
        });
    }
};
}

// Global functions for dashboard
function refreshChart(chartType) {
    if (window.dashboardInstance) {
        showLoader('.card');
        
        setTimeout(() => {
            if (chartType === 'penduduk' && window.dashboardInstance.charts.penduduk) {
                // Simulate data refresh
                const newData = [Math.floor(Math.random() * 200), Math.floor(Math.random() * 200)];
                window.dashboardInstance.charts.penduduk.data.datasets[0].data = newData;
                window.dashboardInstance.charts.penduduk.update();
            }
            
            hideLoader();
            showNotification('Chart berhasil diperbarui', 'success');
        }, 1000);
    }
}

function viewDesa(desaId) {
    showLoader('.table-responsive');
    
    // Simulate API call
    setTimeout(() => {
        hideLoader();
        showNotification(`Membuka detail desa ID: ${desaId}`, 'info');
        // Navigate to desa detail page
        // window.location.href = `${pendataanDesa.baseURL}monitoring?desa=${desaId}`;
    }, 500);
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.Dashboard && !window.dashboardInstance) {
        window.dashboardInstance = new window.Dashboard();
    }
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Dashboard && !window.dashboardInstance) {
            window.dashboardInstance = new window.Dashboard();
        }
    }, 100);
}

// Global init function for AJAX calls
window.initDashboard = function() {
    if (window.Dashboard) {
        if (window.dashboardInstance) {
            window.dashboardInstance.init();
        } else {
            window.dashboardInstance = new window.Dashboard();
        }
    }
};