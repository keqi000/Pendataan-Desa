// Indeks Ketahanan Ekonomi JavaScript Functions
(function() {
    'use strict';
    
    class IndeksKetahananEkonomi {
        constructor() {
            this.charts = {};
            this.isInitialized = false;
        }
        
        init() {
            // Prevent multiple initializations
            if (this.isInitialized) {
                console.log('IndeksKetahananEkonomi already initialized, skipping...');
                return;
            }
            
            console.log('Initializing IndeksKetahananEkonomi...');
            
            // Wait for Chart.js to be loaded
            if (typeof Chart === 'undefined') {
                console.log('Chart.js not loaded yet, retrying...');
                setTimeout(() => {
                    this.init();
                }, 200);
                return;
            }
            
            this.isInitialized = true;
            
            // Initialize charts and interactions immediately
            setTimeout(() => {
                this.initCharts();
                this.initTableFilters();
                this.initModalHandlers();
            }, 100);
        }
        
        initCharts() {
            console.log('Initializing Indeks Ketahanan Ekonomi charts...');
            
            // Check if data is available
            console.log('Checking window.indeksEkonomiData:', window.indeksEkonomiData);
            
            // Destroy existing charts more thoroughly
            this.destroyAllCharts();
            
            // Wait a bit for data to be available
            setTimeout(() => {
                console.log('Delayed check for indeksEkonomiData:', window.indeksEkonomiData);
                this.initDistribusiStatusChart();
                this.initRankingChart();
            }, 500);
        }
        
        destroyAllCharts() {
            // Destroy charts in our tracking object
            Object.keys(this.charts).forEach(key => {
                if (this.charts[key]) {
                    try {
                        this.charts[key].destroy();
                    } catch (e) {
                        console.log('Error destroying chart:', key, e);
                    }
                    delete this.charts[key];
                }
            });
            
            // Also destroy any Chart.js instances on our canvas elements
            const canvasElements = [
                document.getElementById('chartDistribusiStatus'),
                document.getElementById('chartRankingIndeks')
            ];
            
            canvasElements.forEach(canvas => {
                if (canvas) {
                    // Get Chart.js instance from canvas
                    const chartInstance = Chart.getChart(canvas);
                    if (chartInstance) {
                        try {
                            chartInstance.destroy();
                            console.log('Destroyed existing chart on canvas:', canvas.id);
                        } catch (e) {
                            console.log('Error destroying chart instance:', e);
                        }
                    }
                }
            });
        }
        
        initDistribusiStatusChart() {
            const ctx = document.getElementById('chartDistribusiStatus');
            if (!ctx) {
                console.log('chartDistribusiStatus canvas not found');
                return;
            }
            
            console.log('Creating distribusi status chart...');
            console.log('Available data:', window.indeksEkonomiData);
            
            // Use fallback data if window.indeksEkonomiData is not available
            let chartData;
            if (window.indeksEkonomiData && window.indeksEkonomiData.distribusiStatus) {
                chartData = window.indeksEkonomiData.distribusiStatus;
                console.log('Using window.indeksEkonomiData:', chartData);
            } else {
                // Fallback: try to get data from DOM
                const legendItems = document.querySelectorAll('.indeks-ekonomi-container .chart-legend .legend-item span:last-child');
                if (legendItems.length >= 3) {
                    const kuatText = legendItems[0].textContent;
                    const sedangText = legendItems[1].textContent;
                    const lemahText = legendItems[2].textContent;
                    
                    const kuatMatch = kuatText.match(/Kuat: (\d+) desa/);
                    const sedangMatch = sedangText.match(/Sedang: (\d+) desa/);
                    const lemahMatch = lemahText.match(/Lemah: (\d+) desa/);
                    
                    chartData = {
                        labels: ['Kuat', 'Sedang', 'Lemah'],
                        data: [
                            kuatMatch ? parseInt(kuatMatch[1]) : 0,
                            sedangMatch ? parseInt(sedangMatch[1]) : 0,
                            lemahMatch ? parseInt(lemahMatch[1]) : 0
                        ],
                        colors: ['#27AE60', '#F39C12', '#E74C3C']
                    };
                    console.log('Using fallback data from DOM:', chartData);
                } else {
                    console.log('Legend items not found, using sample data...');
                    // Ultimate fallback
                    chartData = {
                        labels: ['Kuat', 'Sedang', 'Lemah'],
                        data: [6, 8, 2], // Sample realistic data
                        colors: ['#27AE60', '#F39C12', '#E74C3C']
                    };
                    console.log('Using sample fallback data:', chartData);
                }
            }
            
            try {
                this.charts.distribusiStatus = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.data,
                            backgroundColor: chartData.colors,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} desa (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: {
                                top: 10,
                                bottom: 10,
                                left: 10,
                                right: 10
                            }
                        }
                    }
                });
                console.log('Distribusi status chart created successfully');
            } catch (error) {
                console.error('Error creating distribusi status chart:', error);
            }
        }
        
        initRankingChart() {
            const ctx = document.getElementById('chartRankingIndeks');
            if (!ctx) {
                console.log('chartRankingIndeks canvas not found');
                return;
            }
            
            console.log('Creating ranking chart...');
            
            // Use fallback data if window.indeksEkonomiData is not available
            let chartData;
            if (window.indeksEkonomiData && window.indeksEkonomiData.rankingData) {
                chartData = window.indeksEkonomiData.rankingData;
                console.log('Using window.indeksEkonomiData for ranking:', chartData);
            } else {
                // Fallback: try to get data from table
                const tableRows = document.querySelectorAll('.indeks-ekonomi-container .desa-row');
                if (tableRows.length > 0) {
                    const labels = [];
                    const data = [];
                    const colors = [];
                    
                    tableRows.forEach((row, index) => {
                        if (index < 10) { // Limit to first 10 for readability
                            const desaName = row.querySelector('.desa-name')?.textContent || `Desa ${index + 1}`;
                            const scoreValue = row.querySelector('.score-value')?.textContent || '0';
                            const statusBadge = row.querySelector('.status-badge');
                            
                            // Get color from status badge background
                            let color = '#112D4E';
                            if (statusBadge) {
                                const bgColor = statusBadge.style.backgroundColor;
                                if (bgColor) {
                                    color = bgColor;
                                } else {
                                    // Fallback based on status text
                                    const statusText = statusBadge.textContent.trim();
                                    if (statusText === 'Kuat') color = '#27AE60';
                                    else if (statusText === 'Sedang') color = '#F39C12';
                                    else if (statusText === 'Lemah') color = '#E74C3C';
                                }
                            }
                            
                            labels.push(desaName);
                            data.push(parseFloat(scoreValue) || 0);
                            colors.push(color);
                        }
                    });
                    
                    chartData = { labels, data, colors };
                    console.log('Using fallback data from table:', chartData);
                } else {
                    console.log('No table rows found, using sample data...');
                    // Ultimate fallback
                    chartData = {
                        labels: ['Desa A', 'Desa B', 'Desa C', 'Desa D', 'Desa E'],
                        data: [85, 72, 68, 55, 42],
                        colors: ['#27AE60', '#27AE60', '#F39C12', '#F39C12', '#E74C3C']
                    };
                    console.log('Using sample fallback data for ranking:', chartData);
                }
            }
            
            try {
                this.charts.ranking = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Indeks Ketahanan Ekonomi',
                            data: chartData.data,
                            backgroundColor: chartData.colors,
                            borderWidth: 1,
                            borderColor: 'rgba(255, 255, 255, 0.8)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.5,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 0,
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Indeks: ${context.parsed.y}%`;
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: {
                                top: 10,
                                bottom: 10,
                                left: 10,
                                right: 10
                            }
                        }
                    }
                });
                console.log('Ranking chart created successfully');
            } catch (error) {
                console.error('Error creating ranking chart:', error);
            }
        }
        
        initTableFilters() {
            const searchInput = document.getElementById('searchIndeksEkonomi');
            const filterStatus = document.getElementById('filterStatus');
            
            if (searchInput) {
                searchInput.addEventListener('input', this.filterTable.bind(this));
            }
            
            if (filterStatus) {
                filterStatus.addEventListener('change', this.filterTable.bind(this));
            }
            
            // Add event listeners for detail buttons
            this.initDetailButtons();
        }
        
        initDetailButtons() {
            const detailButtons = document.querySelectorAll('.btn-detail');
            console.log('Found detail buttons:', detailButtons.length);
            
            detailButtons.forEach(button => {
                // Remove existing event listeners
                button.removeEventListener('click', this.handleDetailClick);
                
                // Add new event listener
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Get desa ID from onclick attribute or data attribute
                    const onclickAttr = button.getAttribute('onclick');
                    let desaId = null;
                    
                    if (onclickAttr) {
                        const match = onclickAttr.match(/showDesaDetail\(['"](.*?)['"]\)/);
                        if (match) {
                            desaId = match[1];
                        }
                    }
                    
                    // Fallback: try to get from data attribute or closest row
                    if (!desaId) {
                        desaId = button.dataset.desaId;
                    }
                    
                    if (!desaId) {
                        const row = button.closest('tr');
                        if (row) {
                            const cells = row.querySelectorAll('td');
                            if (cells.length > 1) {
                                desaId = cells[1].textContent.trim(); // Use desa name as fallback
                            }
                        }
                    }
                    
                    console.log('Detail button clicked for desa ID:', desaId);
                    
                    if (desaId) {
                        this.showDesaDetail(desaId);
                    } else {
                        console.error('Could not determine desa ID from button');
                        alert('Tidak dapat menentukan ID desa');
                    }
                });
            });
        }
        
        filterTable() {
            const searchTerm = document.getElementById('searchIndeksEkonomi')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('filterStatus')?.value || '';
            const rows = document.querySelectorAll('.desa-row');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const desaName = row.querySelector('.desa-name')?.textContent.toLowerCase() || '';
                const status = row.dataset.status || '';
                
                const matchesSearch = desaName.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update visible count if needed
            console.log(`Filtered table: ${visibleCount} rows visible`);
        }
        
        initModalHandlers() {
            // Close modal when clicking outside
            window.addEventListener('click', (event) => {
                const modal = document.getElementById('desaDetailModal');
                if (event.target === modal) {
                    this.closeDesaDetail();
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.closeDesaDetail();
                }
            });
        }
        
        showDesaDetail(idDesa) {
            console.log('showDesaDetail called with ID:', idDesa);
            
            // Try to get data from window.indeksEkonomiData first
            let desaData = null;
            if (window.indeksEkonomiData && window.indeksEkonomiData.desaDetails) {
                desaData = window.indeksEkonomiData.desaDetails[idDesa];
                console.log('Found data from window.indeksEkonomiData:', desaData);
            }
            
            // If not found, try to extract data from table row
            if (!desaData) {
                console.log('Data not found in window object, extracting from DOM...');
                const tableRow = document.querySelector(`tr.desa-row[data-desa-id="${idDesa}"], tr.desa-row:has(.btn-detail[onclick*="${idDesa}"])`);
                if (tableRow) {
                    desaData = this.extractDesaDataFromRow(tableRow, idDesa);
                    console.log('Extracted data from table row:', desaData);
                }
            }
            
            if (!desaData) {
                console.error('Desa data not found for ID:', idDesa);
                alert('Data desa tidak ditemukan');
                return;
            }
            
            const modal = document.getElementById('desaDetailModal');
            const modalTitle = document.getElementById('modalDesaName');
            const modalContent = document.getElementById('modalDesaContent');
            
            if (!modal || !modalTitle || !modalContent) {
                console.error('Modal elements not found');
                alert('Modal tidak ditemukan');
                return;
            }
            
            modalTitle.textContent = `Detail Analisis - ${desaData.nama_desa}`;
            
            const content = this.generateDetailContent(desaData);
            modalContent.innerHTML = content;
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
        
        extractDesaDataFromRow(row, idDesa) {
            const desaName = row.querySelector('.desa-name')?.textContent || 'Unknown';
            const indeksTotal = parseFloat(row.querySelector('.score-value')?.textContent) || 0;
            const statusBadge = row.querySelector('.status-badge');
            const ranking = parseInt(row.querySelector('.ranking-badge')?.textContent) || 0;
            const prioritas = row.querySelector('.priority-badge')?.textContent || 'Unknown';
            
            // Get status info
            let statusKetahanan = {
                status: statusBadge?.textContent || 'Unknown',
                warna: '#112D4E',
                keterangan: 'Status tidak diketahui'
            };
            
            if (statusBadge) {
                const bgColor = statusBadge.style.backgroundColor;
                if (bgColor) {
                    statusKetahanan.warna = bgColor;
                } else {
                    const statusText = statusBadge.textContent.trim();
                    if (statusText === 'Kuat') {
                        statusKetahanan.warna = '#27AE60';
                        statusKetahanan.keterangan = 'Ketahanan ekonomi sangat baik';
                    } else if (statusText === 'Sedang') {
                        statusKetahanan.warna = '#F39C12';
                        statusKetahanan.keterangan = 'Ketahanan ekonomi cukup baik';
                    } else if (statusText === 'Lemah') {
                        statusKetahanan.warna = '#E74C3C';
                        statusKetahanan.keterangan = 'Ketahanan ekonomi perlu diperkuat';
                    }
                }
            }
            
            // Extract sub-scores from table
            const subScores = row.querySelectorAll('.sub-score');
            const skorDetail = {
                keragaman_produksi: parseFloat(subScores[0]?.textContent) || 0,
                pusat_perdagangan: parseFloat(subScores[1]?.textContent) || 0,
                distribusi: parseFloat(subScores[2]?.textContent) || 0,
                keuangan: parseFloat(subScores[3]?.textContent) || 0,
                lembaga_ekonomi: parseFloat(subScores[4]?.textContent) || 0,
                transportasi: parseFloat(subScores[5]?.textContent) || 0
            };
            
            // Generate basic recommendations based on lowest scores
            const rekomendasi = [];
            const sortedScores = Object.entries(skorDetail).sort((a, b) => a[1] - b[1]);
            const lowestScores = sortedScores.slice(0, 2);
            
            lowestScores.forEach(([dimensi, skor]) => {
                if (skor < 50) {
                    switch (dimensi) {
                        case 'keragaman_produksi':
                            rekomendasi.push('Diversifikasi kegiatan ekonomi dengan mengembangkan sektor non-pertanian');
                            break;
                        case 'pusat_perdagangan':
                            rekomendasi.push('Bangun pusat perdagangan dan kembangkan usaha makanan/jasa');
                            break;
                        case 'distribusi':
                            rekomendasi.push('Tingkatkan akses distribusi dan logistik');
                            break;
                        case 'keuangan':
                            rekomendasi.push('Fasilitasi akses perbankan dan lembaga keuangan mikro');
                            break;
                        case 'lembaga_ekonomi':
                            rekomendasi.push('Perkuat koperasi dan lembaga ekonomi masyarakat');
                            break;
                        case 'transportasi':
                            rekomendasi.push('Perbaiki infrastruktur jalan dan transportasi umum');
                            break;
                    }
                }
            });
            
            if (rekomendasi.length === 0) {
                rekomendasi.push('Pertahankan dan tingkatkan kualitas ketahanan ekonomi yang sudah baik');
            }
            
            return {
                nama_desa: desaName,
                indeks_total: indeksTotal,
                status_ketahanan: statusKetahanan,
                ranking: ranking,
                prioritas: prioritas,
                skor_detail: skorDetail,
                rekomendasi: rekomendasi
            };
        }
        
        generateDetailContent(desaData) {
            return `
                <div class="desa-detail-content">
                    <div class="detail-summary">
                        <div class="detail-score">
                            <h4>Indeks Total</h4>
                            <div class="score-display" style="color: ${desaData.status_ketahanan.warna}">
                                ${desaData.indeks_total}
                            </div>
                            <div class="status-display" style="background-color: ${desaData.status_ketahanan.warna}">
                                ${desaData.status_ketahanan.status}
                            </div>
                        </div>
                        <div class="detail-ranking">
                            <h4>Ranking</h4>
                            <div class="ranking-display">#${desaData.ranking}</div>
                            <div class="priority-display">Prioritas: ${desaData.prioritas}</div>
                        </div>
                    </div>
                    
                    <div class="detail-dimensions">
                        <h4>Skor Per Dimensi</h4>
                        <div class="dimensions-grid">
                            ${this.generateDimensionItems(desaData.skor_detail)}
                        </div>
                    </div>
                    
                    <div class="detail-recommendations">
                        <h4>Rekomendasi Khusus</h4>
                        <ul class="recommendations-list">
                            ${desaData.rekomendasi.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;
        }
        
        generateDimensionItems(skorDetail) {
            const dimensionLabels = {
                'keragaman_produksi': 'Keragaman Produksi',
                'pusat_perdagangan': 'Pusat Perdagangan',
                'distribusi': 'Distribusi',
                'keuangan': 'Keuangan',
                'lembaga_ekonomi': 'Lembaga Ekonomi',
                'transportasi': 'Transportasi'
            };
            
            return Object.entries(skorDetail).map(([key, value]) => {
                const label = dimensionLabels[key] || key;
                return `
                    <div class="dimension-item">
                        <span>${label}</span>
                        <div class="dimension-bar">
                            <div class="dimension-fill" style="width: ${value}%"></div>
                        </div>
                        <span>${value}</span>
                    </div>
                `;
            }).join('');
        }
        
        closeDesaDetail() {
            const modal = document.getElementById('desaDetailModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        }
        
        // Public method to refresh charts when tab becomes active
        refreshCharts() {
            setTimeout(() => {
                Object.values(this.charts).forEach(chart => {
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }, 100);
        }
    }
    
    // Create global instance
    window.IndeksKetahananEkonomi = new IndeksKetahananEkonomi();
    
    // Global functions for HTML onclick handlers
    window.showDesaDetail = function(idDesa) {
        window.IndeksKetahananEkonomi.showDesaDetail(idDesa);
    };
    
    window.closeDesaDetail = function() {
        window.IndeksKetahananEkonomi.closeDesaDetail();
    };
    
    // Auto-initialize when DOM is ready - hanya jika belum diinisialisasi
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Hanya initialize jika tab indeks_ekonomi ada dan belum diinisialisasi
            setTimeout(() => {
                const indeksTab = document.getElementById('indeks_ekonomi');
                if (indeksTab && !window.IndeksKetahananEkonomi.isInitialized) {
                    console.log('Auto-initializing IndeksKetahananEkonomi from DOMContentLoaded');
                    window.IndeksKetahananEkonomi.init();
                }
            }, 1000);
        });
    } else {
        // DOM already loaded
        setTimeout(() => {
            const indeksTab = document.getElementById('indeks_ekonomi');
            if (indeksTab && !window.IndeksKetahananEkonomi.isInitialized) {
                console.log('Auto-initializing IndeksKetahananEkonomi from ready state');
                window.IndeksKetahananEkonomi.init();
            }
        }, 1000);
    }
    
})();