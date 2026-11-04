// Analisis Prediksi Functions
(function() {
    'use strict';
    
    class AnalisisPrediksi {
        constructor() {
            this.charts = {};
        }
        
        init() {
            this.initPrediksiCharts();
            this.initPredictionTable();
        }
        
        initPrediksiCharts() {
            if (this.charts.prediksiLine) {
                this.charts.prediksiLine.destroy();
            }
            if (this.charts.prediksiBar) {
                this.charts.prediksiBar.destroy();
            }
            
            this.initPrediksiLineChart();
            this.initPrediksiBarChart();
        }
        
        initPrediksiLineChart() {
            const ctx = document.getElementById('chartPrediksiLine');
            if (!ctx) return;
            
            let years = ['2025', '2026', '2027', '2028', '2029'];
            let totalPenduduk = [5500, 5610, 5722, 5836, 5952];
            let totalLaki = [2750, 2805, 2861, 2918, 2976];
            let totalPerempuan = [2750, 2805, 2861, 2918, 2976];
            
            const tableRows = document.querySelectorAll('#prediksiTable tbody tr');
            if (tableRows.length > 0) {
                years = [];
                totalPenduduk = [];
                totalLaki = [];
                totalPerempuan = [];
                
                tableRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 4) {
                        const year = cells[1].textContent.trim();
                        const total = parseInt(cells[2].textContent.replace(/,/g, '')) || 0;
                        const laki = parseInt(cells[3].textContent.replace(/,/g, '')) || 0;
                        const perempuan = parseInt(cells[4].textContent.replace(/,/g, '')) || 0;
                        
                        if (!years.includes(year)) {
                            years.push(year);
                            totalPenduduk.push(total);
                            totalLaki.push(laki);
                            totalPerempuan.push(perempuan);
                        }
                    }
                });
            }
            
            this.charts.prediksiLine = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: years,
                    datasets: [{
                        label: 'Total Penduduk',
                        data: totalPenduduk,
                        borderColor: '#112D4E',
                        backgroundColor: 'rgba(17, 45, 78, 0.1)',
                        fill: true
                    }, {
                        label: 'Laki-laki',
                        data: totalLaki,
                        borderColor: '#3F72AF',
                        backgroundColor: 'rgba(63, 114, 175, 0.1)',
                        fill: false
                    }, {
                        label: 'Perempuan',
                        data: totalPerempuan,
                        borderColor: '#DBE2EF',
                        backgroundColor: 'rgba(219, 226, 239, 0.1)',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        initPrediksiBarChart() {
            const ctx = document.getElementById('chartPrediksiBar');
            if (!ctx) return;
            
            // Create chart with empty data initially
            let labels = ['Balita (0-5)', 'Anak (6-12)', 'Remaja (13-17)', 'Dewasa (18-64)', 'Lansia (65+)'];
            let data = [0, 0, 0, 0, 0];
            
            this.charts.prediksiBar = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: `Prediksi Struktur Usia ${new Date().getFullYear() + 5}`,
                        data: data,
                        backgroundColor: [
                            '#112D4E',
                            '#3F72AF',
                            '#27AE60',
                            '#F39C12',
                            '#E74C3C'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: `Prediksi Struktur Usia ${new Date().getFullYear() + 5}`
                        }
                    }
                }
            });
        }
        
        initPredictionTable() {
            if (document.getElementById('prediksiTable')) {
                window.predictionTableInstance = new PredictionTable();
            }
        }
        
        updatePredictionSection(predictions) {
            if (!predictions || predictions.length === 0) return;
            
            this.updatePredictionStats(predictions);
            this.updatePredictionTable(predictions);
            this.updateChartsFromTable();
        }
        
        updateChartsFromTable() {
            // Update bar chart with data from table
            const tableRows = document.querySelectorAll('#prediksiTable tbody tr');
            if (tableRows.length > 0 && this.charts.prediksiBar) {
                const targetYear = new Date().getFullYear() + 5;
                let data = [0, 0, 0, 0, 0];
                
                tableRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 10) {
                        const year = parseInt(cells[1].textContent.trim());
                        if (year === targetYear) {
                            data[0] += parseInt(cells[5].textContent.replace(/,/g, '')) || 0;
                            data[1] += parseInt(cells[6].textContent.replace(/,/g, '')) || 0;
                            data[2] += parseInt(cells[7].textContent.replace(/,/g, '')) || 0;
                            data[3] += parseInt(cells[8].textContent.replace(/,/g, '')) || 0;
                            data[4] += parseInt(cells[9].textContent.replace(/,/g, '')) || 0;
                        }
                    }
                });
                
                // Update chart data
                this.charts.prediksiBar.data.datasets[0].data = data;
                this.charts.prediksiBar.update();
            }
        }
        
        updatePredictionStats(predictions) {
            const currentYear = new Date().getFullYear();
            const targetYear = currentYear + 5;
            
            let totalPredicted = 0;
            let avgGrowthRate = 0;
            let avgConfidence = 0;
            let predictionCount = 0;
            
            let ageStructure = {
                balita: 0, anak: 0, remaja: 0, dewasa: 0, lansia: 0
            };
            
            predictions.forEach(pred => {
                if (pred.tahun_prediksi == targetYear) {
                    totalPredicted += pred.total_penduduk_prediksi || 0;
                    ageStructure.balita += pred.total_balita_prediksi || 0;
                    ageStructure.anak += pred.total_anak_prediksi || 0;
                    ageStructure.remaja += pred.total_remaja_prediksi || 0;
                    ageStructure.dewasa += pred.total_dewasa_prediksi || 0;
                    ageStructure.lansia += pred.total_lansia_prediksi || 0;
                }
                avgGrowthRate += pred.growth_rate || 0;
                avgConfidence += pred.confidence_level || 0;
                predictionCount++;
            });
            
            avgGrowthRate = predictionCount > 0 ? (avgGrowthRate / predictionCount).toFixed(2) : 0;
            avgConfidence = predictionCount > 0 ? (avgConfidence / predictionCount).toFixed(1) : 0;
            
            const statItems = document.querySelectorAll('#prediksi .stat-item');
            
            if (statItems.length >= 4) {
                statItems[0].querySelector('.stat-value').textContent = `${totalPredicted.toLocaleString()} jiwa`;
                statItems[1].querySelector('.stat-value').textContent = `${avgGrowthRate}% per tahun`;
                statItems[2].querySelector('.stat-value').textContent = `${avgConfidence}%`;
                statItems[3].querySelector('.stat-value').textContent = `Linear Regression`;
            }
            
            const breakdownItems = document.querySelectorAll('#prediksi .analisis-breakdown .breakdown-item');
            const totalAge = Object.values(ageStructure).reduce((a, b) => a + b, 0);
            
            if (breakdownItems.length >= 5 && totalAge > 0) {
                const ageData = [
                    { name: 'Balita (0-5 tahun)', value: ageStructure.balita },
                    { name: 'Anak (6-12 tahun)', value: ageStructure.anak },
                    { name: 'Remaja (13-17 tahun)', value: ageStructure.remaja },
                    { name: 'Dewasa (18-64 tahun)', value: ageStructure.dewasa },
                    { name: 'Lansia (65+ tahun)', value: ageStructure.lansia }
                ];
                
                ageData.forEach((age, index) => {
                    if (breakdownItems[index]) {
                        const percentage = ((age.value / totalAge) * 100).toFixed(1);
                        breakdownItems[index].innerHTML = `
                            <span>${age.name}</span>
                            <span>${age.value.toLocaleString()} (${percentage}%)</span>
                        `;
                    }
                });
            }
        }
        
        updatePredictionTable(predictions) {
            const tableBody = document.querySelector('#prediksiTableBody');
            if (!tableBody) return;
            
            let tableRows = '';
            predictions.forEach(pred => {
                tableRows += `
                    <tr>
                        <td>${pred.nama_desa}</td>
                        <td>${pred.tahun_prediksi}</td>
                        <td>${pred.total_penduduk_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.total_laki_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.total_perempuan_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.total_balita_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.total_anak_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.total_remaja_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.total_dewasa_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.total_lansia_prediksi?.toLocaleString() || 0}</td>
                        <td>${pred.growth_rate?.toFixed(2) || 0}%</td>
                        <td>${pred.confidence_level?.toFixed(1) || 0}%</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = tableRows;
            
            // Update charts after table is populated
            setTimeout(() => {
                if (window.AnalisisPrediksi) {
                    window.AnalisisPrediksi.updateChartsFromTable();
                }
            }, 100);
            
            const paginationInfo = document.querySelector('#paginationInfo');
            if (paginationInfo) {
                paginationInfo.textContent = `Showing 1 to ${Math.min(10, predictions.length)} of ${predictions.length} entries`;
            }
        }
    }
    
    // Prediction Table Class
    class PredictionTable {
        constructor() {
            this.currentPage = 1;
            this.entriesPerPage = 10;
            this.filteredData = [];
            this.allData = [];
            this.init();
        }
        
        init() {
            this.loadTableData();
            this.setupEventListeners();
            this.renderTable();
        }
        
        loadTableData() {
            const rows = document.querySelectorAll('#prediksiTableBody tr');
            this.allData = Array.from(rows).map(row => {
                const cells = row.querySelectorAll('td');
                return {
                    element: row,
                    desa: cells[0]?.textContent || '',
                    tahun: cells[1]?.textContent || ''
                };
            });
            this.filteredData = [...this.allData];
        }
        
        setupEventListeners() {
            const searchInput = document.getElementById('searchPrediksi');
            const entriesSelect = document.getElementById('entriesPerPage');
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            
            if (searchInput) {
                searchInput.addEventListener('input', () => this.handleSearch());
            }
            
            if (entriesSelect) {
                entriesSelect.addEventListener('change', () => this.handleEntriesChange());
            }
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => this.goToPreviousPage());
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => this.goToNextPage());
            }
        }
        
        handleSearch() {
            const searchTerm = document.getElementById('searchPrediksi').value.toLowerCase();
            this.filteredData = this.allData.filter(item => 
                item.desa.toLowerCase().includes(searchTerm) ||
                item.tahun.toLowerCase().includes(searchTerm)
            );
            this.currentPage = 1;
            this.renderTable();
        }
        
        handleEntriesChange() {
            this.entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            this.currentPage = 1;
            this.renderTable();
        }
        
        renderTable() {
            const startIndex = (this.currentPage - 1) * this.entriesPerPage;
            const endIndex = startIndex + this.entriesPerPage;
            const pageData = this.filteredData.slice(startIndex, endIndex);
            
            this.allData.forEach(item => {
                item.element.style.display = 'none';
            });
            
            pageData.forEach(item => {
                item.element.style.display = '';
            });
            
            this.updatePaginationInfo();
            this.updatePaginationControls();
        }
        
        updatePaginationInfo() {
            const info = document.getElementById('paginationInfo');
            if (!info) return;
            
            const startIndex = (this.currentPage - 1) * this.entriesPerPage + 1;
            const endIndex = Math.min(this.currentPage * this.entriesPerPage, this.filteredData.length);
            const total = this.filteredData.length;
            
            info.textContent = `Showing ${startIndex} to ${endIndex} of ${total} entries`;
        }
        
        updatePaginationControls() {
            const totalPages = Math.ceil(this.filteredData.length / this.entriesPerPage);
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            const pageNumbers = document.getElementById('pageNumbers');
            
            if (prevBtn) {
                prevBtn.disabled = this.currentPage === 1;
            }
            
            if (nextBtn) {
                nextBtn.disabled = this.currentPage === totalPages || totalPages === 0;
            }
            
            if (pageNumbers) {
                pageNumbers.innerHTML = '';
                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.textContent = i;
                    pageBtn.className = `page-btn ${i === this.currentPage ? 'active' : ''}`;
                    pageBtn.addEventListener('click', () => this.goToPage(i));
                    pageNumbers.appendChild(pageBtn);
                }
            }
        }
        
        goToPage(page) {
            this.currentPage = page;
            this.renderTable();
        }
        
        goToPreviousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.renderTable();
            }
        }
        
        goToNextPage() {
            const totalPages = Math.ceil(this.filteredData.length / this.entriesPerPage);
            if (this.currentPage < totalPages) {
                this.currentPage++;
                this.renderTable();
            }
        }
    }
    
    // Global prediction functions
    window.generatePredictions = function() {
        event.preventDefault();
        
        let baseUrl;
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            baseUrl = window.location.origin + '/Pendataan-desa/';
        } else {
            const pathParts = window.location.pathname.split('/');
            if (pathParts.includes('Pendataan-desa')) {
                baseUrl = window.location.origin + '/Pendataan-desa/';
            } else {
                baseUrl = window.location.origin + '/';
            }
        }
        
        const apiUrl = `${baseUrl}pages/admin/analisis/analisis.php?endpoint=prediksi-data&action=generate`;
        
        if (window.AnalisisUtils) {
            window.AnalisisUtils.showNotification('Generating predictions...', 'info');
        }
        
        const generateBtn = document.querySelector('button[onclick="generatePredictions()"]');
        if (generateBtn) {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        }
        
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'generate=1'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (window.AnalisisUtils) {
                    window.AnalisisUtils.showNotification('Prediksi berhasil dibuat!', 'success');
                }
                
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('tab', 'prediksi');
                currentUrl.searchParams.set('show_predictions', '1');
                window.history.pushState({}, '', currentUrl.toString());
                
                const hasElements = document.querySelector('#prediksi .stat-item') && 
                                  document.querySelector('#prediksiTableBody');
                
                if (!hasElements) {
                    sessionStorage.setItem('pendingPredictions', JSON.stringify(data.data.data));
                    window.location.href = currentUrl.toString();
                } else if (window.AnalisisPrediksi) {
                    window.AnalisisPrediksi.updatePredictionSection(data.data.data);
                }
                
            } else {
                if (window.AnalisisUtils) {
                    window.AnalisisUtils.showNotification('Gagal membuat prediksi: ' + (data.message || data.error || 'Unknown error'), 'error');
                }
            }
        })
        .catch(error => {
            if (window.AnalisisUtils) {
                window.AnalisisUtils.showNotification('Terjadi kesalahan saat membuat prediksi: ' + error.message, 'error');
            }
        })
        .finally(() => {
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fas fa-sync"></i> Generate Prediksi';
            }
        });
        
        return false;
    };
    
    window.initPredictionTable = function() {
        if (document.getElementById('prediksiTable')) {
            window.predictionTableInstance = new PredictionTable();
        }
    };
    
    // Initialize and export
    window.AnalisisPrediksi = new AnalisisPrediksi();
})();