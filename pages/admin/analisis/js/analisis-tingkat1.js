// Analisis Tingkat 1 Functions
(function() {
    'use strict';
    
    class AnalisisTingkat1 {
        constructor() {
            this.charts = {};
        }
        
        init() {
            // Prevent multiple initializations
            if (this.isInitialized) {
                console.log('AnalisisTingkat1 already initialized, skipping...');
                return;
            }
            
            this.isInitialized = true;
            
            setTimeout(() => {
                this.initCharts();
            }, 100);
        }
        
        initCharts() {
            console.log('Initializing all tingkat1 charts...');
            
            // Destroy existing charts first
            Object.keys(this.charts).forEach(key => {
                if (this.charts[key]) {
                    this.charts[key].destroy();
                    delete this.charts[key];
                }
            });
            
            this.initKependudukanChart();
            this.initEkonomiChart();
            this.initPendidikanChart();
            this.initInfrastrukturChart();
            this.initTingkat1Charts();
        }
        
        initKependudukanChart() {
            const ctx = document.getElementById('chartKependudukan');
            if (!ctx) return;
            
            let data = [0, 0, 0, 0, 0];
            const breakdownItems = document.querySelectorAll('#tingkat1 .analisis-breakdown .breakdown-item');
            
            breakdownItems.forEach(item => {
                const spans = item.querySelectorAll('span');
                if (spans.length >= 2) {
                    const label = spans[0].textContent.trim();
                    const valueText = spans[1].textContent.replace(/[^0-9]/g, '');
                    const value = parseInt(valueText) || 0;
                    
                    if (label.includes('Laki-laki')) {
                        data[0] = value;
                    } else if (label.includes('Perempuan')) {
                        data[1] = value;
                    } else if (label.includes('Anak')) {
                        data[2] = value;
                    } else if (label.includes('Dewasa')) {
                        data[3] = value;
                    } else if (label.includes('Lansia')) {
                        data[4] = value;
                    }
                }
            });
            
            this.charts.kependudukan = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Laki-laki', 'Perempuan', 'Anak', 'Dewasa', 'Lansia'],
                    datasets: [{
                        label: 'Jumlah',
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
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        initEkonomiChart() {
            const ctx = document.getElementById('chartEkonomi');
            if (!ctx) return;
            
            let labels = [];
            let data = [];
            const breakdownItems = document.querySelectorAll('#tingkat1 .analisis-breakdown h5');
            
            breakdownItems.forEach(heading => {
                if (heading.textContent.includes('Kelompok Usia')) {
                    const items = heading.parentElement.querySelectorAll('.breakdown-item');
                    items.forEach(item => {
                        const spans = item.querySelectorAll('span');
                        if (spans.length >= 2) {
                            const label = spans[0].textContent.trim();
                            const valueText = spans[1].textContent.replace(/[^0-9]/g, '');
                            const value = parseInt(valueText) || 0;
                            
                            if (label.includes('Balita') || label.includes('Anak') || label.includes('Remaja') || label.includes('Dewasa') || label.includes('Lansia')) {
                                labels.push(label.split('(')[0].trim());
                                data.push(value);
                            }
                        }
                    });
                }
            });
            
            this.charts.ekonomi = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#112D4E',
                            '#3F72AF',
                            '#DBE2EF',
                            '#27AE60',
                            '#E74C3C'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribusi Kelompok Usia'
                        }
                    }
                }
            });
        }
        
        initPendidikanChart() {
            const ctx = document.getElementById('chartPendidikan');
            if (!ctx) return;
            
            let lakiLaki = 0;
            let perempuan = 0;
            
            const statsItems = document.querySelectorAll('#tingkat1 .analisis-stats-grid .stat-item');
            statsItems.forEach(item => {
                const title = item.querySelector('h4');
                const value = item.querySelector('.stat-value');
                
                if (title && value) {
                    const titleText = title.textContent.trim();
                    const valueText = value.textContent.replace(/[^0-9]/g, '');
                    const numValue = parseInt(valueText) || 0;
                    
                    if (titleText.includes('Laki-laki')) {
                        lakiLaki = numValue;
                    } else if (titleText.includes('Perempuan')) {
                        perempuan = numValue;
                    }
                }
            });
            
            this.charts.pendidikan = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Laki-laki', 'Perempuan'],
                    datasets: [{
                        label: 'Jumlah Penduduk',
                        data: [lakiLaki, perempuan],
                        borderColor: '#112D4E',
                        backgroundColor: 'rgba(17, 45, 78, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribusi Jenis Kelamin'
                        }
                    }
                }
            });
        }
        
        initInfrastrukturChart() {
            const ctx = document.getElementById('chartInfrastruktur');
            if (!ctx) return;
            
            let totalKK = 0;
            let totalPenduduk = 0;
            
            const statsItems = document.querySelectorAll('#tingkat1 .analisis-stats-grid .stat-item');
            statsItems.forEach(item => {
                const title = item.querySelector('h4');
                const value = item.querySelector('.stat-value');
                
                if (title && value) {
                    const titleText = title.textContent.trim();
                    const valueText = value.textContent.replace(/[^0-9]/g, '');
                    const numValue = parseInt(valueText) || 0;
                    
                    if (titleText.includes('Total KK')) {
                        totalKK = numValue;
                    } else if (titleText.includes('Total Penduduk')) {
                        totalPenduduk = numValue;
                    }
                }
            });
            
            const rataAnggotaKK = totalKK > 0 ? Math.round(totalPenduduk / totalKK) : 0;
            
            this.charts.infrastruktur = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Total KK', 'Rata-rata Anggota/KK'],
                    datasets: [{
                        label: 'Jumlah',
                        data: [totalKK, rataAnggotaKK],
                        backgroundColor: [
                            '#112D4E',
                            '#3F72AF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Statistik Keluarga'
                        }
                    }
                }
            });
        }
        
        initTingkat1Charts() {
            this.initKependudukanTingkat1Chart();
            this.initEkonomiTingkat1Chart();
            this.initPendidikanTingkat1Chart();
            this.initInfrastrukturTingkat1Chart();
        }
        
        initKependudukanTingkat1Chart() {
            const ctx = document.getElementById('chartKependudukanTingkat1');
            if (!ctx) return;
            
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded yet, skipping chart initialization');
                return;
            }
            
            let lakiLaki = 0;
            let perempuan = 0;
            
            const statsItems = document.querySelectorAll('#kependudukanTingkat1 .analisis-stats-grid .stat-item');
            statsItems.forEach(item => {
                const title = item.querySelector('h4');
                const value = item.querySelector('.stat-value');
                
                if (title && value) {
                    const titleText = title.textContent.trim();
                    const valueText = value.textContent.replace(/[^0-9]/g, '');
                    const numValue = parseInt(valueText) || 0;
                    
                    if (titleText.includes('Laki-laki')) {
                        lakiLaki = numValue;
                    } else if (titleText.includes('Perempuan')) {
                        perempuan = numValue;
                    }
                }
            });
            
            try {
                this.charts.kependudukanTingkat1 = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Laki-laki', 'Perempuan'],
                        datasets: [{
                            data: [lakiLaki, perempuan],
                            backgroundColor: ['#112D4E', '#3F72AF']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating chart:', error);
            }
        }
        
        initEkonomiTingkat1Chart() {
            const ctx = document.getElementById('chartEkonomiTingkat1');
            if (!ctx) return;
            
            let umkm = 0;
            let pertanian = 0;
            let pasar = 0;
            
            const statsItems = document.querySelectorAll('#ekonomiTingkat1 .analisis-stats-grid .stat-item');
            statsItems.forEach(item => {
                const title = item.querySelector('h4');
                const value = item.querySelector('.stat-value');
                
                if (title && value) {
                    const titleText = title.textContent.trim();
                    const valueText = value.textContent.replace(/[^0-9]/g, '');
                    const numValue = parseInt(valueText) || 0;
                    
                    if (titleText.includes('UMKM')) {
                        umkm = numValue;
                    } else if (titleText.includes('Pertanian')) {
                        pertanian = numValue;
                    } else if (titleText.includes('Pasar')) {
                        pasar = numValue;
                    }
                }
            });
            
            this.charts.ekonomiTingkat1 = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['UMKM', 'Pertanian', 'Pasar'],
                    datasets: [{
                        label: 'Jumlah Unit',
                        data: [umkm, pertanian, pasar],
                        backgroundColor: ['#112D4E', '#3F72AF', '#DBE2EF']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        initPendidikanTingkat1Chart() {
            const ctx = document.getElementById('chartPendidikanTingkat1');
            if (!ctx) return;
            
            let labels = [];
            let data = [];
            
            const breakdownItems = document.querySelectorAll('#pendidikanTingkat1 .analisis-breakdown');
            breakdownItems.forEach(breakdown => {
                const heading = breakdown.querySelector('h5');
                if (heading && heading.textContent.includes('Jenis Fasilitas')) {
                    const items = breakdown.querySelectorAll('.breakdown-item');
                    items.forEach(item => {
                        const spans = item.querySelectorAll('span');
                        if (spans.length >= 2) {
                            const label = spans[0].textContent.trim();
                            const valueText = spans[1].textContent.replace(/[^0-9]/g, '');
                            const value = parseInt(valueText) || 0;
                            
                            if (value > 0) {
                                labels.push(label);
                                data.push(value);
                            }
                        }
                    });
                }
            });
            
            this.charts.pendidikanTingkat1 = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#112D4E',
                            '#3F72AF',
                            '#DBE2EF',
                            '#27AE60',
                            '#F39C12'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        initInfrastrukturTingkat1Chart() {
            const ctx = document.getElementById('chartInfrastrukturTingkat1');
            if (!ctx) return;
            
            let jalanBaik = 0;
            let jalanSedang = 0;
            let jalanRusak = 0;
            
            const breakdownItems = document.querySelectorAll('#infrastrukturTingkat1 .analisis-breakdown');
            breakdownItems.forEach(breakdown => {
                const heading = breakdown.querySelector('h5');
                if (heading && heading.textContent.includes('Kondisi Jalan')) {
                    const items = breakdown.querySelectorAll('.breakdown-item');
                    items.forEach(item => {
                        const spans = item.querySelectorAll('span');
                        if (spans.length >= 2) {
                            const label = spans[0].textContent.trim();
                            const valueText = spans[1].textContent.replace(/[^0-9]/g, '');
                            const value = parseInt(valueText) || 0;
                            
                            if (label.includes('Baik')) {
                                jalanBaik = value;
                            } else if (label.includes('Sedang')) {
                                jalanSedang = value;
                            } else if (label.includes('Rusak')) {
                                jalanRusak = value;
                            }
                        }
                    });
                }
            });
            
            this.charts.infrastrukturTingkat1 = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Baik', 'Sedang', 'Rusak'],
                    datasets: [{
                        data: [jalanBaik, jalanSedang, jalanRusak],
                        backgroundColor: ['#27AE60', '#F39C12', '#E74C3C']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    // Initialize and export
    window.AnalisisTingkat1 = new AnalisisTingkat1();
})();