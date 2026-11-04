// Analisis Tingkat 2 Functions
(function() {
    'use strict';
    
    class AnalisisTingkat2 {
        constructor() {
            this.charts = {};
        }
        
        init() {
            console.log('Initializing AnalisisTingkat2...');
            
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.log('Chart.js not loaded yet, retrying...');
                setTimeout(() => {
                    this.init();
                }, 200);
                return;
            }
            
            // Check if tingkat2 tab is active or has data
            const tingkat2Tab = document.getElementById('tingkat2');
            if (!tingkat2Tab) {
                console.log('Tingkat2 tab not found');
                return;
            }
            
            // Wait a bit for DOM to be ready
            setTimeout(() => {
                console.log('Starting chart initialization for tingkat2...');
                this.initTingkat2Charts();
            }, 300);
        }
        
        initTingkat2Charts() {
            console.log('Initializing tingkat2 charts...');
            
            // Destroy existing charts first
            Object.keys(this.charts).forEach(key => {
                if (this.charts[key]) {
                    this.charts[key].destroy();
                    delete this.charts[key];
                }
            });
            
            // Initialize all charts
            this.initKependudukanTingkat2Chart();
            this.initEkonomiTingkat2Chart();
            this.initIntegrasiTingkat2Chart();
            this.initSpasialTingkat2Chart();
            
            console.log('Tingkat2 charts initialized:', Object.keys(this.charts));
        }
        
        initKependudukanTingkat2Chart() {
            const ctx = document.getElementById('chartKependudukanTingkat2');
            if (!ctx) {
                console.log('chartKependudukanTingkat2 canvas not found');
                return;
            }
            
            console.log('Initializing chartKependudukanTingkat2...');
            
            let kkPerempuan = 0;
            let produktifTanpaKerja = 0;
            let kkLansia = 0;
            let rasioKetergantungan = 0;
            
            // Look for stats in tingkat2 tab specifically
            const tingkat2Tab = document.getElementById('tingkat2');
            if (tingkat2Tab) {
                const statsItems = tingkat2Tab.querySelectorAll('.analisis-stats-grid .stat-item');
                console.log('Found', statsItems.length, 'stat items in tingkat2');
                
                statsItems.forEach(item => {
                    const title = item.querySelector('h4');
                    const value = item.querySelector('.stat-value');
                    
                    if (title && value) {
                        const titleText = title.textContent.trim();
                        const valueText = value.textContent.replace(/[^0-9.]/g, '');
                        const numValue = parseFloat(valueText) || 0;
                        
                        console.log('Processing stat:', titleText, '=', numValue);
                        
                        if (titleText.includes('KK Perempuan')) {
                            kkPerempuan = numValue;
                        } else if (titleText.includes('Produktif Tanpa Kerja')) {
                            produktifTanpaKerja = numValue;
                        } else if (titleText.includes('KK Lansia')) {
                            kkLansia = numValue;
                        } else if (titleText.includes('Rasio Ketergantungan')) {
                            rasioKetergantungan = numValue;
                        }
                    }
                });
            }
            
            console.log('Chart data:', {kkPerempuan, produktifTanpaKerja, kkLansia, rasioKetergantungan});
            
            try {
                this.charts.kependudukanTingkat2 = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['KK Perempuan', 'Produktif Tanpa Kerja (%)', 'KK Lansia', 'Rasio Ketergantungan (%)'],
                        datasets: [{
                            label: 'Jumlah',
                            data: [kkPerempuan, produktifTanpaKerja, kkLansia, rasioKetergantungan],
                            backgroundColor: ['#112D4E', '#3F72AF', '#DBE2EF', '#F39C12']
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
                console.log('chartKependudukanTingkat2 created successfully');
            } catch (error) {
                console.error('Error creating chartKependudukanTingkat2:', error);
            }
        }
        
        initEkonomiTingkat2Chart() {
            const ctx = document.getElementById('chartEkonomiTingkat2');
            if (!ctx) return;
            
            let petaniLahan = 0;
            let pendapatanPertanian = 0;
            let korelasiPendidikan = 0;
            let indeksGini = 0;
            
            const statsItems = document.querySelectorAll('#tingkat2 .card:nth-child(2) .analisis-stats-grid .stat-item');
            statsItems.forEach(item => {
                const title = item.querySelector('h4');
                const value = item.querySelector('.stat-value');
                
                if (title && value) {
                    const titleText = title.textContent.trim();
                    const valueText = value.textContent.replace(/[^0-9.]/g, '');
                    const numValue = parseFloat(valueText) || 0;
                    
                    if (titleText.includes('Petani Lahan')) {
                        petaniLahan = numValue;
                    } else if (titleText.includes('Pendapatan Pertanian')) {
                        pendapatanPertanian = numValue;
                    } else if (titleText.includes('Korelasi')) {
                        korelasiPendidikan = numValue;
                    } else if (titleText.includes('Indeks Gini')) {
                        indeksGini = numValue;
                    }
                }
            });
            
            this.charts.ekonomiTingkat2 = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Petani Lahan Kecil', 'Pendapatan Pertanian (%)', 'Korelasi Pendidikan', 'Indeks Gini'],
                    datasets: [{
                        data: [petaniLahan, pendapatanPertanian, korelasiPendidikan * 100, indeksGini * 100],
                        backgroundColor: ['#112D4E', '#3F72AF', '#DBE2EF', '#27AE60']
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
        
        initIntegrasiTingkat2Chart() {
            const ctx = document.getElementById('chartIntegrasiTingkat2');
            if (!ctx) return;
            
            let kkMiskinSekolah = 0;
            let pendidikanTinggiMiskin = 0;
            let rasioKetergantunganEkonomi = 0;
            let anakTidakSekolah = 0;
            
            const statsItems = document.querySelectorAll('#tingkat2 .card:nth-child(3) .analisis-stats-grid .stat-item');
            statsItems.forEach(item => {
                const title = item.querySelector('h4');
                const value = item.querySelector('.stat-value');
                
                if (title && value) {
                    const titleText = title.textContent.trim();
                    const valueText = value.textContent.replace(/[^0-9]/g, '');
                    const numValue = parseInt(valueText) || 0;
                    
                    if (titleText.includes('KK Miskin')) {
                        kkMiskinSekolah = numValue;
                    } else if (titleText.includes('Pendidikan Tinggi')) {
                        pendidikanTinggiMiskin = numValue;
                    } else if (titleText.includes('Rasio Ketergantungan')) {
                        rasioKetergantunganEkonomi = numValue;
                    } else if (titleText.includes('Anak Tidak Sekolah')) {
                        anakTidakSekolah = numValue;
                    }
                }
            });
            
            this.charts.integrasiTingkat2 = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['KK Miskin + Sekolah', 'Pendidikan Tinggi Miskin', 'Rasio Ketergantungan', 'Anak Tidak Sekolah'],
                    datasets: [{
                        data: [kkMiskinSekolah, pendidikanTinggiMiskin, rasioKetergantunganEkonomi, anakTidakSekolah],
                        backgroundColor: ['#112D4E', '#3F72AF', '#DBE2EF', '#E74C3C']
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
        
        initSpasialTingkat2Chart() {
            const ctx = document.getElementById('chartSpasialTingkat2');
            if (!ctx) return;
            
            let desaPadatMinim = 0;
            let indeksAksesibilitas = 0;
            let prioritasPembangunan = 0;
            
            const statsItems = document.querySelectorAll('#tingkat2 .card:nth-child(4) .analisis-stats-grid .stat-item');
            statsItems.forEach(item => {
                const title = item.querySelector('h4');
                const value = item.querySelector('.stat-value');
                
                if (title && value) {
                    const titleText = title.textContent.trim();
                    const valueText = value.textContent.replace(/[^0-9.]/g, '');
                    const numValue = parseFloat(valueText) || 0;
                    
                    if (titleText.includes('Desa Padat')) {
                        desaPadatMinim = numValue;
                    } else if (titleText.includes('Indeks Aksesibilitas')) {
                        indeksAksesibilitas = numValue;
                    } else if (titleText.includes('Prioritas Pembangunan')) {
                        prioritasPembangunan = numValue;
                    }
                }
            });
            
            this.charts.spasialTingkat2 = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Desa Padat Minim Fasilitas', 'Indeks Aksesibilitas (%)', 'Prioritas Pembangunan'],
                    datasets: [{
                        label: 'Nilai',
                        data: [desaPadatMinim, indeksAksesibilitas, prioritasPembangunan],
                        borderColor: '#112D4E',
                        backgroundColor: 'rgba(17, 45, 78, 0.1)',
                        fill: true,
                        tension: 0.4
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
    }
    
    // Initialize and export
    window.AnalisisTingkat2 = new AnalisisTingkat2();
})();