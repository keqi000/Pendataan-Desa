// Dashboard Charts JavaScript untuk Sistem Informasi Publik
// File: public-portal/pages/dashboard/js/dashboard-charts.js

class DashboardCharts {
    constructor() {
        this.charts = {};
        this.init();
    }
    
    async init() {
        // Load Chart.js if not already loaded
        if (typeof Chart === 'undefined') {
            await this.loadChartJS();
        }
        
        // Initialize charts when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initializeCharts();
            });
        } else {
            this.initializeCharts();
        }
    }
    
    async loadChartJS() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    async initializeCharts() {
        try {
            // Get chart data from API
            const ageData = await this.getChartData('age-distribution');
            const educationData = await this.getChartData('education');
            
            // Initialize charts
            this.createAgeChart(ageData);
            this.createEducationChart(educationData);
            
        } catch (error) {
            console.error('Error initializing charts:', error);
            this.showChartError();
        }
    }
    
    async getChartData(endpoint) {
        try {
            const response = await fetch(`${PublicPortalConfig.baseUrl}pages/dashboard/php/dashboard-functions.php?endpoint=${endpoint}`);
            const result = await response.json();
            
            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.message || 'Failed to fetch chart data');
            }
        } catch (error) {
            console.error(`Error fetching ${endpoint} data:`, error);
            return this.getFallbackData(endpoint);
        }
    }
    
    getFallbackData(endpoint) {
        // Fallback data in case API fails
        const fallbackData = {
            'age-distribution': {
                labels: ['0-5 Tahun', '6-12 Tahun', '13-17 Tahun', '18-64 Tahun', '65+ Tahun'],
                data: [5417, 6587, 3814, 25267, 2251],
                colors: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            },
            'education': {
                labels: ['Tidak Sekolah', 'SD', 'SMP', 'SMA', 'D3', 'S1', 'S2+'],
                data: [3684, 15254, 11183, 9577, 1387, 2080, 171],
                colors: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384']
            }
        };
        
        return fallbackData[endpoint] || null;
    }
    
    createAgeChart(data) {
        const canvas = document.getElementById('ageChart');
        if (!canvas || !data) return;
        
        const ctx = canvas.getContext('2d');
        
        this.charts.ageChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: data.colors,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff'
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
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    duration: 1000
                }
            }
        });
    }
    
    createEducationChart(data) {
        const canvas = document.getElementById('educationChart');
        if (!canvas || !data) return;
        
        const ctx = canvas.getContext('2d');
        
        this.charts.educationChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: data.data,
                    backgroundColor: data.colors.map(color => color + '80'), // Add transparency
                    borderColor: data.colors,
                    borderWidth: 2,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed.y.toLocaleString()} orang`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
    
    showChartError() {
        const chartContainers = ['ageChart', 'educationChart'];
        
        chartContainers.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                const parent = container.parentElement;
                parent.innerHTML = `
                    <div class="chart-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Gagal memuat grafik</p>
                        <button class="btn btn-sm btn-primary" onclick="location.reload()">
                            Muat Ulang
                        </button>
                    </div>
                `;
                parent.style.cssText = `
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 300px;
                    color: #666;
                    text-align: center;
                `;
            }
        });
    }
    
    // Method to update charts with new data
    async updateCharts() {
        try {
            const ageData = await this.getChartData('age-distribution');
            const educationData = await this.getChartData('education');
            
            if (this.charts.ageChart && ageData) {
                this.charts.ageChart.data.labels = ageData.labels;
                this.charts.ageChart.data.datasets[0].data = ageData.data;
                this.charts.ageChart.update();
            }
            
            if (this.charts.educationChart && educationData) {
                this.charts.educationChart.data.labels = educationData.labels;
                this.charts.educationChart.data.datasets[0].data = educationData.data;
                this.charts.educationChart.update();
            }
            
            PublicUtils.showNotification('Grafik berhasil diperbarui', 'success');
            
        } catch (error) {
            console.error('Error updating charts:', error);
            PublicUtils.showNotification('Gagal memperbarui grafik', 'error');
        }
    }
    
    // Method to resize charts
    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }
    
    // Method to destroy charts
    destroyCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }
}

// Initialize charts when script loads
window.dashboardCharts = new DashboardCharts();

// Handle window resize
window.addEventListener('resize', PublicUtils.debounce(() => {
    if (window.dashboardCharts) {
        window.dashboardCharts.resizeCharts();
    }
}, 250));

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.dashboardCharts) {
        window.dashboardCharts.destroyCharts();
    }
});