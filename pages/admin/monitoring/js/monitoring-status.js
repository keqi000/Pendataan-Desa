// Monitoring Status Tab Functions
(function() {
    'use strict';
    
    class MonitoringStatus {
        constructor() {
            if (document.querySelector('.monitoring-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupStatusFilters();
        }
        
        setupStatusFilters() {
            // Status filter functionality
        }
        
        filterByStatus(status) {
            const rows = document.querySelectorAll('#monitoringTable tbody tr');
            
            rows.forEach(row => {
                if (!status) {
                    row.style.display = '';
                    return;
                }
                
                const badge = row.querySelector('.badge');
                const badgeText = badge ? badge.textContent.toLowerCase() : '';
                
                let shouldShow = false;
                switch (status) {
                    case 'complete':
                        shouldShow = badgeText.includes('lengkap');
                        break;
                    case 'incomplete':
                        shouldShow = badgeText.includes('tidak lengkap');
                        break;
                    case 'outdated':
                        shouldShow = badgeText.includes('perlu update');
                        break;
                }
                
                row.style.display = shouldShow ? '' : 'none';
            });
        }
        
        async showDesaModal(desaId, desaName) {
            const modal = document.getElementById('desaDetailModal');
            const modalTitle = modal.querySelector('.modal-title');
            const modalContent = document.getElementById('desaDetailContent');
            
            modalTitle.textContent = `Detail Data ${window.MonitoringUtils.sanitizeHtml(desaName)}`;
            
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            try {
                const baseUrl = window.MonitoringUtils.getBaseUrl();
                const endpoints = [
                    `${baseUrl}api/router.php?endpoint=all-desa-data&type=penduduk&desa_id=${desaId}`,
                    `${baseUrl}api/router.php?endpoint=all-desa-data&type=ekonomi&desa_id=${desaId}`,
                    `${baseUrl}api/router.php?endpoint=all-desa-data&type=pendidikan&desa_id=${desaId}`,
                    `${baseUrl}api/router.php?endpoint=all-desa-data&type=infrastruktur&desa_id=${desaId}`
                ];
                
                const [pendudukData, ekonomiData, pendidikanData, infraData] = await Promise.all(
                    endpoints.map(url => window.MonitoringUtils.apiCall(url))
                );
                
                modalContent.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-users"></i> Data Kependudukan</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Total Penduduk:</strong> ${pendudukData.length || 0} orang</p>
                                    <p><strong>Laki-laki:</strong> ${pendudukData.filter(p => p.jenis_kelamin === 'L').length || 0} orang</p>
                                    <p><strong>Perempuan:</strong> ${pendudukData.filter(p => p.jenis_kelamin === 'P').length || 0} orang</p>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-chart-line"></i> Data Ekonomi</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Total Data Ekonomi:</strong> ${ekonomiData.length || 0} data</p>
                                    <p><strong>Jenis Usaha:</strong> ${[...new Set(ekonomiData.map(e => e.jenis_data))].join(', ') || 'Belum ada data'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-school"></i> Data Pendidikan</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Total Fasilitas:</strong> ${pendidikanData.length || 0} fasilitas</p>
                                    <p><strong>PAUD/TK:</strong> ${pendidikanData.filter(p => ['PAUD', 'TK'].includes(p.jenis_pendidikan)).length || 0}</p>
                                    <p><strong>SD/SMP/SMA:</strong> ${pendidikanData.filter(p => ['SD', 'SMP', 'SMA'].includes(p.jenis_pendidikan)).length || 0}</p>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-road"></i> Data Infrastruktur</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Total Jalan:</strong> ${infraData.jalan?.length || 0} jalan</p>
                                    <p><strong>Kondisi Baik:</strong> ${infraData.jalan?.filter(j => j.kondisi_jalan === 'baik').length || 0}</p>
                                    <p><strong>Total Jembatan:</strong> ${infraData.jembatan?.length || 0} jembatan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
            } catch (error) {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Gagal memuat detail data desa. Silakan coba lagi.
                    </div>
                `;
            }
        }
        
        closeDesaModal() {
            const modal = document.getElementById('desaDetailModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
        
        navigateToDetailDesa(desaId, desaName) {
            // Traditional redirect instead of AJAX
            const baseUrl = window.BASE_URL || (window.location.protocol + '//' + window.location.host + '/Pendataan-desa/');
            const detailUrl = `${baseUrl}pages/admin/monitoring/monitoring.php?desa=${desaId}`;
            window.location.href = detailUrl;
        }
    }
    
    // Global functions for status tab
    window.filterByStatus = function() {
        const statusFilter = document.getElementById('statusFilter');
        if (window.MonitoringStatus && statusFilter) {
            window.MonitoringStatus.filterByStatus(statusFilter.value);
        }
    };
    
    window.showDesaModal = async function(desaId, desaName) {
        if (window.MonitoringStatus) {
            await window.MonitoringStatus.showDesaModal(desaId, desaName);
        }
    };
    
    window.closeDesaModal = function() {
        if (window.MonitoringStatus) {
            window.MonitoringStatus.closeDesaModal();
        }
    };
    
    window.navigateToDetailDesa = function(desaId, desaName) {
        if (window.MonitoringStatus) {
            window.MonitoringStatus.navigateToDetailDesa(desaId, desaName);
        }
    };
    
    // Initialize status functions
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.MonitoringStatus) {
            window.MonitoringStatus = new MonitoringStatus();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.MonitoringStatus) {
            window.MonitoringStatus = new MonitoringStatus();
        }
    }
})();