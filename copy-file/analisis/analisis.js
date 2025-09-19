// Analisis Namespace
if (typeof window.Analisis === 'undefined') {
window.Analisis = {
    currentTab: 'tingkat1',
    editingId: null,

    init() {
        if (!document.querySelector('.analisis-container')) return;
        
        this.setupEventListeners();
        this.loadAnalisisData();
        this.loadStatistics();
    },

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.analisis-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Refresh button
        document.getElementById('refreshAnalisis').addEventListener('click', () => {
            this.loadAnalisisData();
        });
    },

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.analisis-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Update tab content
        document.querySelectorAll('.analisis-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const tabContent = document.getElementById(`tab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`);
        if (tabContent) {
            tabContent.classList.add('active');
        }

        this.currentTab = tabName;

        // Load appropriate data
        if (tabName === 'tingkat1') {
            this.loadAnalisisData();
        } else if (tabName === 'tingkat2') {
            this.loadAnalisisLanjutan();
        }
    },

    async loadAnalisisData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=analisis-statistik`);
            
            console.log('Analisis Response status:', response.status);
            const responseText = await response.text();
            console.log('Analisis Raw response:', responseText);
            
            const data = JSON.parse(responseText);

            if (data.success) {
                this.renderAnalisisData(data.data);
            } else {
                this.renderEmptyAnalisis('Tidak ada data analisis');
            }
        } catch (error) {
            console.error('Error loading analisis data:', error);
            this.renderEmptyAnalisis('Gagal memuat data analisis');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    async loadStatistics() {
        try {
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=analisis-stats`);
            
            console.log('Analisis Stats Response status:', response.status);
            const responseText = await response.text();
            console.log('Analisis Stats Raw response:', responseText);
            
            const data = JSON.parse(responseText);

            if (data.success && data.data) {
                this.updateStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    },

    renderAnalisisData(data) {
        if (data.kependudukan) {
            this.renderKependudukanStats(data.kependudukan);
        }
        if (data.ekonomi) {
            this.renderEkonomiStats(data.ekonomi);
        }
        if (data.pendidikan) {
            this.renderPendidikanStats(data.pendidikan);
        }
        if (data.infrastruktur) {
            this.renderInfrastrukturStats(data.infrastruktur);
        }
    },

    renderKependudukanStats(data) {
        const container = document.getElementById('kependudukanStats');
        if (container) {
            container.innerHTML = `
                <div class="stat-card">
                    <h4>Total Penduduk</h4>
                    <div class="stat-number">${data.total_penduduk || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Laki-laki</h4>
                    <div class="stat-number">${data.total_laki || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Perempuan</h4>
                    <div class="stat-number">${data.total_perempuan || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Total Keluarga</h4>
                    <div class="stat-number">${data.total_keluarga || 0}</div>
                </div>
            `;
        }
    },

    renderEkonomiStats(data) {
        const container = document.getElementById('ekonomiStats');
        if (container) {
            container.innerHTML = `
                <div class="stat-card">
                    <h4>Total UMKM</h4>
                    <div class="stat-number">${data.total_umkm || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Petani</h4>
                    <div class="stat-number">${data.total_petani || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Penerima Bantuan</h4>
                    <div class="stat-number">${data.total_bantuan || 0}</div>
                </div>
            `;
        }
    },

    renderPendidikanStats(data) {
        const container = document.getElementById('pendidikanStats');
        if (container) {
            container.innerHTML = `
                <div class="stat-card">
                    <h4>Fasilitas Pendidikan</h4>
                    <div class="stat-number">${data.total_fasilitas || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Tidak Sekolah</h4>
                    <div class="stat-number">${data.tidak_sekolah || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Perguruan Tinggi</h4>
                    <div class="stat-number">${data.perguruan_tinggi || 0}</div>
                </div>
            `;
        }
    },

    renderInfrastrukturStats(data) {
        const container = document.getElementById('infrastrukturStats');
        if (container) {
            container.innerHTML = `
                <div class="stat-card">
                    <h4>Total Jalan</h4>
                    <div class="stat-number">${data.total_jalan || 0} km</div>
                </div>
                <div class="stat-card">
                    <h4>Total Jembatan</h4>
                    <div class="stat-number">${data.total_jembatan || 0}</div>
                </div>
                <div class="stat-card">
                    <h4>Kondisi Baik</h4>
                    <div class="stat-number">${data.kondisi_baik || 0}%</div>
                </div>
            `;
        }
    },

    renderEmptyAnalisis(message) {
        const container = document.getElementById('analisisContent');
        if (container) {
            container.innerHTML = `
                <div class="analisis-empty">
                    <i class="fas fa-info-circle"></i> ${message}
                </div>
            `;
        }
    },

    updateStatistics(data) {
        // Update any additional statistics if needed
        console.log('Statistics updated:', data);
    },

    async loadAnalisisLanjutan() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=analisis-lanjutan`);
            
            console.log('Analisis Lanjutan Response status:', response.status);
            const responseText = await response.text();
            console.log('Analisis Lanjutan Raw response:', responseText);
            
            const data = JSON.parse(responseText);

            if (data.success) {
                this.renderAnalisisLanjutan(data.data);
            } else {
                this.renderEmptyAnalisis('Tidak ada data analisis lanjutan');
            }
        } catch (error) {
            console.error('Error loading analisis lanjutan:', error);
            this.renderEmptyAnalisis('Gagal memuat data analisis lanjutan');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderAnalisisLanjutan(data) {
        const container = document.getElementById('tabTingkat2');
        if (container) {
            container.innerHTML = `
                <div class="analisis-lanjutan">
                    <h3>Analisis Tingkat 2</h3>
                    <p>Fitur analisis lanjutan akan segera tersedia.</p>
                </div>
            `;
        }
    }
};
}

// Global functions
window.refreshAnalisis = function() {
    if (window.Analisis) {
        window.Analisis.loadAnalisisData();
    }
};

// Utility function for debouncing
function debounce(func, wait) {
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.Analisis) window.Analisis.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Analisis) window.Analisis.init();
    }, 100);
}

// Global init function for AJAX calls
window.initAnalisis = function() {
    if (window.Analisis) {
        window.Analisis.init();
    }
};