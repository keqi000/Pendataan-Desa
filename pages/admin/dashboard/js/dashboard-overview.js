// Dashboard Overview Functions
(function() {
    'use strict';
    
    // Define global functions immediately
    window.showDesaOverview = function(desaId, desaName) {
        const modal = document.getElementById('desaOverviewModal');
        const modalTitle = modal.querySelector('.modal-overview-title');
        const modalContent = document.getElementById('desaOverviewContent');
        const mainContent = document.querySelector('.main-layout-main');
        
        modalTitle.textContent = `Overview ${desaName}`;
        modal.style.display = 'block';
        
        // Darken main content area
        if (mainContent) {
            mainContent.style.background = 'rgba(0, 0, 0, 0.6)';
        }
        
        // Show loading
        modalContent.innerHTML = '<div class="loading-text">Memuat data...</div>';
        
        // Fetch data untuk modal
        const baseUrl = window.BASE_URL || (window.location.protocol + '//' + window.location.host + '/Pendataan-desa/');
        fetch(`${baseUrl}pages/admin/dashboard/dashboard.php?action=get_desa_overview&id=${desaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const overview = data.data;
                    modalContent.innerHTML = `
                        <div class="overview-stats">
                            <div class="overview-stat-item">
                                <div class="overview-stat-number">${overview.total_penduduk || 0}</div>
                                <div class="overview-stat-label">Penduduk</div>
                            </div>
                            <div class="overview-stat-item">
                                <div class="overview-stat-number">${overview.desa.luas_wilayah || 0}</div>
                                <div class="overview-stat-label">Luas (Ha)</div>
                            </div>
                            <div class="overview-stat-item">
                                <div class="overview-stat-number">${overview.total_umkm || 0}</div>
                                <div class="overview-stat-label">UMKM</div>
                            </div>
                            <div class="overview-stat-item">
                                <div class="overview-stat-number">${overview.total_sekolah || 0}</div>
                                <div class="overview-stat-label">Sekolah</div>
                            </div>
                            <div class="overview-stat-item">
                                <div class="overview-stat-number">${overview.persentase_jalan_baik || 0}%</div>
                                <div class="overview-stat-label">Jalan Baik</div>
                            </div>
                        </div>
                    `;
                } else {
                    modalContent.innerHTML = '<div class="loading-text" style="color: #e74c3c;">Gagal memuat data overview</div>';
                }
            })
            .catch(error => {
                modalContent.innerHTML = '<div class="loading-text" style="color: #e74c3c;">Terjadi kesalahan saat memuat data</div>';
            });
    };
    
    window.closeDesaOverview = function() {
        const modal = document.getElementById('desaOverviewModal');
        const mainContent = document.querySelector('.main-layout-main');
        
        if (modal) modal.style.display = 'none';
        
        // Remove darkening effect from main content
        if (mainContent) {
            mainContent.style.background = '';
        }
    };
    
    class DashboardOverview {
        constructor() {
            if (document.querySelector('.dashboard-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupOverviewModal();
        }
        
        setupOverviewModal() {
            // Setup modal event listeners if needed
        }
        
        async showDesaOverview(desaId, desaName) {
            const modal = document.getElementById('desaOverviewModal');
            const modalTitle = modal.querySelector('.modal-overview-title');
            const modalContent = document.getElementById('desaOverviewContent');
            const mainContent = document.querySelector('.main-layout-main');
            
            modalTitle.textContent = `Overview ${desaName}`;
            modal.style.display = 'block';
            
            if (mainContent) {
                mainContent.style.background = 'rgba(0, 0, 0, 0.6)';
            }
            
            // Traditional redirect instead of AJAX
            modalContent.innerHTML = `
                <div class="overview-redirect">
                    <p>Untuk melihat detail desa, silakan klik tombol di bawah:</p>
                    <a href="${window.DashboardUtils.getBaseUrl()}pages/admin/monitoring/monitoring.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i> Lihat Detail Desa
                    </a>
                </div>
            `;
        }
        
        closeDesaOverview() {
            const modal = document.getElementById('desaOverviewModal');
            const mainContent = document.querySelector('.main-layout-main');
            
            if (mainContent) {
                mainContent.style.background = '';
            }
            
            modal.style.display = 'none';
        }
        
        navigateToDetailDesa(desaId, desaName) {
            // Traditional redirect instead of AJAX
            const baseUrl = window.DashboardUtils.getBaseUrl();
            const detailUrl = `${baseUrl}pages/admin/monitoring/monitoring.php?desa=${encodeURIComponent(desaId)}`;
            window.location.href = detailUrl;
        }
    }
    
    // Global functions for overview (already defined above)
    
    // Initialize dashboard overview
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.DashboardOverview) {
            window.DashboardOverview = new DashboardOverview();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.DashboardOverview) {
            window.DashboardOverview = new DashboardOverview();
        }
    }
})();