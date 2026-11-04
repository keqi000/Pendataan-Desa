// Dashboard Charts Functions
(function() {
    'use strict';
    
    // Define global functions immediately
    window.updateTrendData = function() {
        const year = document.getElementById('yearFilter').value;
        const period = document.getElementById('periodFilter').value;
        
        // Fetch new data and update charts
        const baseUrl = window.BASE_URL || (window.location.protocol + '//' + window.location.host + '/Pendataan-desa/');
        fetch(`${baseUrl}pages/admin/dashboard/dashboard.php?action=get_trend_data&year=${year}&period=${period}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update global data
                    window.trendData = data.data.trendData;
                    window.monthlyData = data.data.monthlyData;
                    window.selectedYear = data.data.year;
                    window.selectedPeriod = data.data.period;
                    
                    // Update charts
                    updateCharts();
                    
                    // Update trend summary
                    updateTrendSummary();
                } else {
                    console.error('Failed to fetch trend data');
                }
            })
            .catch(error => {
                console.error('Error fetching trend data:', error);
            });
    };
    
    function updateCharts() {
        // Update bar chart
        if (window.barChart && window.trendData) {
            window.barChart.data.datasets[0].data = [
                window.trendData.desa,
                window.trendData.penduduk,
                window.trendData.pendidikan,
                window.trendData.umkm,
                window.trendData.jalan
            ];
            window.barChart.update();
        }
        
        // Update line chart
        if (window.lineChart && window.monthlyData) {
            const months = window.selectedPeriod === 'jan-jun' ? 
                ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'] :
                ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            window.lineChart.data.labels = months;
            window.lineChart.data.datasets.forEach((dataset, index) => {
                const categories = ['desa', 'penduduk', 'pendidikan', 'umkm', 'jalan'];
                dataset.data = getMonthlyData(categories[index]);
            });
            window.lineChart.update();
        }
    }
    
    function updateTrendSummary() {
        const trendItems = document.querySelectorAll('.trend-item');
        const categories = ['desa', 'penduduk', 'pendidikan', 'umkm', 'jalan'];
        
        trendItems.forEach((item, index) => {
            const category = categories[index];
            const value = window.trendData[category] || 0;
            const valueEl = item.querySelector('.trend-value');
            
            if (valueEl) {
                const displayText = (value >= 0 ? '+' : '') + value + '%';
                valueEl.textContent = displayText;
                valueEl.className = 'trend-value ' + (value >= 0 ? 'positive' : 'negative');
            }
        });
    }
    
    // Initialize charts when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Chart.js to load
        if (typeof Chart !== 'undefined') {
            initializeCharts();
        } else {
            // Wait for Chart.js to load
            setTimeout(function() {
                if (typeof Chart !== 'undefined') {
                    initializeCharts();
                }
            }, 1000);
        }
    });
    
    function initializeCharts() {
        // Initialize bar chart
        const barCtx = document.getElementById('trendBarChart');
        if (barCtx && !window.barChart && window.trendData) {
            window.barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Desa', 'Penduduk', 'Pendidikan', 'UMKM', 'Jalan'],
                    datasets: [{
                        label: 'Tren 6 Bulan (%)',
                        data: [
                            window.trendData.desa,
                            window.trendData.penduduk,
                            window.trendData.pendidikan,
                            window.trendData.umkm,
                            window.trendData.jalan
                        ],
                        backgroundColor: '#27AE60',
                        borderColor: '#219A52',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize line chart
        const lineCtx = document.getElementById('trendLineChart');
        if (lineCtx && !window.lineChart && window.monthlyData) {
            const months = window.selectedPeriod === 'jan-jun' ? 
                ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'] :
                ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            window.lineChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Desa',
                            data: getMonthlyData('desa'),
                            borderColor: '#3F72AF',
                            tension: 0.4
                        },
                        {
                            label: 'Penduduk', 
                            data: getMonthlyData('penduduk'),
                            borderColor: '#112D4E',
                            tension: 0.4
                        },
                        {
                            label: 'Pendidikan',
                            data: getMonthlyData('pendidikan'),
                            borderColor: '#F39C12',
                            tension: 0.4
                        },
                        {
                            label: 'UMKM',
                            data: getMonthlyData('umkm'),
                            borderColor: '#27AE60',
                            tension: 0.4
                        },
                        {
                            label: 'Jalan',
                            data: getMonthlyData('jalan'),
                            borderColor: '#8E44AD',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }
    
    function getMonthlyData(category) {
        const startMonth = window.selectedPeriod === 'jan-jun' ? 1 : 7;
        const data = [];
        for (let i = 0; i < 6; i++) {
            const month = startMonth + i;
            const value = window.monthlyData[month] ? window.monthlyData[month][category] || 0 : 0;
            data.push(value);
        }
        return data;
    }
    
    class DashboardCharts {
        constructor() {
            if (document.querySelector('.dashboard-admin-container')) {
                this.charts = {};
                this.init();
            }
        }
        
        init() {
            // Charts will be initialized by core when ready
        }
        
        createTrendCharts() {
            this.destroyExistingCharts();
            
            const waitForContainer = () => {
                const barCanvas = document.getElementById('trendBarChart');
                const lineCanvas = document.getElementById('trendLineChart');
                
                if (barCanvas && lineCanvas && 
                    barCanvas.offsetParent !== null && 
                    lineCanvas.offsetParent !== null &&
                    barCanvas.clientWidth > 0 && 
                    lineCanvas.clientWidth > 0) {
                    
                    this.createBarChart();
                    this.createLineChart();
                } else {
                    setTimeout(waitForContainer, 100);
                }
            };
            
            waitForContainer();
        }
        
        destroyExistingCharts() {
            if (this.charts) {
                if (this.charts.barChart) {
                    this.charts.barChart.destroy();
                    this.charts.barChart = null;
                }
                if (this.charts.lineChart) {
                    this.charts.lineChart.destroy();
                    this.charts.lineChart = null;
                }
            }
        }
        
        createBarChart() {
            const ctx = document.getElementById('trendBarChart');
            if (!ctx) {
                return;
            }
            
            const trendData = window.trendData ? [
                window.trendData.desa || 0,
                window.trendData.penduduk || 0, 
                window.trendData.pendidikan || 0,
                window.trendData.umkm || 0,
                window.trendData.jalan || 0
            ] : [0, 0, 0, 0, 0];
            
            this.charts = this.charts || {};
            this.charts.barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Desa', 'Penduduk', 'Pendidikan', 'UMKM', 'Jalan'],
                    datasets: [{
                        label: 'Tren 6 Bulan (%)',
                        data: trendData,
                        backgroundColor: [
                            '#27AE60',
                            '#27AE60', 
                            '#27AE60',
                            '#27AE60',
                            '#27AE60'
                        ],
                        borderColor: [
                            '#219A52',
                            '#219A52',
                            '#219A52', 
                            '#219A52',
                            '#219A52'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Perubahan Data (%)',
                            font: { size: 14, weight: 'bold' }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#E5E7EB'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        createLineChart() {
            const ctx = document.getElementById('trendLineChart');
            if (!ctx) {
                return;
            }
            
            const selectedPeriod = window.selectedPeriod || 'jan-jun';
            const months = selectedPeriod === 'jan-jun' ? 
                ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'] :
                ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            const monthlyData = window.monthlyData || {};
            const startMonth = selectedPeriod === 'jan-jun' ? 1 : 7;
            
            const getMonthlyChartData = (category) => {
                const data = [];
                for (let i = 0; i < 6; i++) {
                    const month = startMonth + i;
                    const value = monthlyData[month] ? monthlyData[month][category] || 0 : 0;
                    data.push(value);
                }
                return data;
            };
            
            this.charts = this.charts || {};
            this.charts.lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Desa',
                            data: getMonthlyChartData('desa'),
                            borderColor: '#3F72AF',
                            backgroundColor: 'rgba(63, 114, 175, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Penduduk',
                            data: getMonthlyChartData('penduduk'),
                            borderColor: '#112D4E',
                            backgroundColor: 'rgba(17, 45, 78, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Pendidikan',
                            data: getMonthlyChartData('pendidikan'),
                            borderColor: '#F39C12',
                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'UMKM',
                            data: getMonthlyChartData('umkm'),
                            borderColor: '#27AE60',
                            backgroundColor: 'rgba(39, 174, 96, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Jalan',
                            data: getMonthlyChartData('jalan'),
                            borderColor: '#8E44AD',
                            backgroundColor: 'rgba(142, 68, 173, 0.1)',
                            tension: 0.4,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 11 }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Tren 6 Bulan Terakhir',
                            font: { size: 14, weight: 'bold' }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: '#E5E7EB'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }
        
        updateTrendData(trendData, monthlyData) {
            window.trendData = trendData;
            window.monthlyData = monthlyData;
            this.createTrendCharts();
        }
    }
    
    // Global function for updating trend data - Traditional form submission
    // (Already defined above)
    
    // Initialize dashboard charts
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.DashboardCharts) {
            window.DashboardCharts = new DashboardCharts();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.DashboardCharts) {
            window.DashboardCharts = new DashboardCharts();
        }
    }
})();