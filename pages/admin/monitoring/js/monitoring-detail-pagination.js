(function() {
    'use strict';
    
    class DetailDesaPagination {
        constructor() {
            if (document.querySelector('.detail-desa-container')) {
                this.baseUrl = window.BASE_URL || (window.location.protocol + '//' + window.location.host + '/Pendataan-desa/');
                this.desaId = document.querySelector('.detail-desa-container').dataset.desaId;
                this.pagination = {};
                this.searchTimeouts = {};
                this.init();
            }
        }
        
        init() {
            this.setupNavigation();
            this.initializeTables();
        }
        
        setupNavigation() {
            const breadcrumbLink = document.querySelector('.detail-desa-breadcrumb a');
            if (breadcrumbLink) {
                breadcrumbLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.location.href = this.baseUrl + 'pages/admin/monitoring/monitoring.php';
                });
            }
        }
        
        initializeTables() {
            const tables = [
                { id: 'pendudukTable', type: 'penduduk' },
                { id: 'pendidikanTable', type: 'fasilitas' },
                { id: 'umkmTable', type: 'umkm' },
                { id: 'pasarTable', type: 'pasar' },
                { id: 'jalanTable', type: 'jalan' },
                { id: 'jembatanTable', type: 'jembatan' }
            ];
            
            tables.forEach(table => {
                this.initTable(table.id, table.type);
            });
        }
        
        initTable(tableId, dataType) {
            const tableSection = document.querySelector(`[data-type="${dataType}"]`);
            if (!tableSection) return;
            
            this.pagination[tableId] = {
                currentPage: 1,
                itemsPerPage: 10,
                totalItems: 0,
                totalPages: 1,
                dataType: dataType
            };
            
            this.setupTableControls(tableId);
            this.loadTableData(tableId);
        }
        
        setupTableControls(tableId) {
            const pagination = this.pagination[tableId];
            const tableSection = document.querySelector(`[data-type="${pagination.dataType}"]`);
            
            // Items per page selector
            const itemsPerPageSelect = tableSection.querySelector('.items-per-page');
            if (itemsPerPageSelect) {
                itemsPerPageSelect.addEventListener('change', (e) => {
                    pagination.itemsPerPage = parseInt(e.target.value);
                    pagination.currentPage = 1;
                    this.loadTableData(tableId);
                });
            }
            
            // Pagination buttons
            const prevBtn = tableSection.querySelector('.prev-btn');
            const nextBtn = tableSection.querySelector('.next-btn');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    if (pagination.currentPage > 1) {
                        pagination.currentPage--;
                        this.loadTableData(tableId);
                    }
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    if (pagination.currentPage < pagination.totalPages) {
                        pagination.currentPage++;
                        this.loadTableData(tableId);
                    }
                });
            }
            
            // Search input
            const searchInput = tableSection.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value;
                    
                    // Clear previous timeout
                    if (this.searchTimeouts[tableId]) {
                        clearTimeout(this.searchTimeouts[tableId]);
                    }
                    
                    // Set new timeout for search
                    this.searchTimeouts[tableId] = setTimeout(() => {
                        pagination.currentPage = 1;
                        this.loadTableData(tableId, searchTerm);
                    }, 500);
                });
            }
        }
        
        async loadTableData(tableId, search = '') {
            const pagination = this.pagination[tableId];
            const tableSection = document.querySelector(`[data-type="${pagination.dataType}"]`);
            const tbody = tableSection.querySelector('tbody');
            
            // Show loading
            tbody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 32px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>';
            
            try {
                const params = new URLSearchParams({
                    endpoint: 'desa-data-paginated',
                    desa_id: this.desaId,
                    type: pagination.dataType,
                    page: pagination.currentPage,
                    limit: pagination.itemsPerPage,
                    search: search
                });
                
                const response = await fetch(`${this.baseUrl}pages/admin/monitoring/monitoring.php?${params}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.renderTableData(tableId, result.data);
                    this.updatePaginationInfo(tableId, result.data);
                } else {
                    this.showError(tbody, result.message || 'Gagal memuat data');
                }
            } catch (error) {
                this.showError(tbody, 'Terjadi kesalahan saat memuat data');
            }
        }
        
        renderTableData(tableId, data) {
            const pagination = this.pagination[tableId];
            const tableSection = document.querySelector(`[data-type="${pagination.dataType}"]`);
            const tbody = tableSection.querySelector('tbody');
            
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 32px; color: #666;"><i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>Tidak ada data</td></tr>';
                return;
            }
            
            let html = '';
            data.data.forEach(item => {
                html += '<tr>' + this.renderRowByType(pagination.dataType, item) + '</tr>';
            });
            
            tbody.innerHTML = html;
        }
        
        renderRowByType(type, item) {
            // Safely get values with fallback
            const getValue = (obj, key, fallback = '') => {
                return obj && obj[key] !== undefined && obj[key] !== null ? obj[key] : fallback;
            };
            
            switch (type) {
                case 'penduduk':
                    return `<td>${getValue(item, 'nik')}</td><td>${getValue(item, 'nama_lengkap')}</td><td>${getValue(item, 'jenis_kelamin')}</td><td>${getValue(item, 'usia')}</td><td>${getValue(item, 'pendidikan_terakhir')}</td><td>${getValue(item, 'pekerjaan')}</td>`;
                    
                case 'fasilitas':
                    return `<td>${getValue(item, 'nama_fasilitas')}</td><td>${getValue(item, 'jenis_pendidikan')}</td><td>${getValue(item, 'alamat_fasilitas')}</td><td>${getValue(item, 'kapasitas_siswa')}</td><td>${getValue(item, 'kondisi_bangunan')}</td>`;
                    
                case 'umkm':
                    return `<td>${getValue(item, 'nama_usaha')}</td><td>${getValue(item, 'jenis_usaha')}</td><td>Rp ${this.formatNumber(getValue(item, 'modal_usaha', 0))}</td><td>Rp ${this.formatNumber(getValue(item, 'omzet_perbulan', 0))}</td><td>${getValue(item, 'jumlah_karyawan')}</td>`;
                    
                case 'pasar':
                    return `<td>${getValue(item, 'nama_pasar')}</td><td>${getValue(item, 'jenis_pasar')}</td><td>${getValue(item, 'jumlah_pedagang')}</td><td>${getValue(item, 'hari_operasional')}</td><td>${getValue(item, 'kondisi_fasilitas')}</td>`;
                    
                case 'jalan':
                    const kondisiJalan = getValue(item, 'kondisi_jalan');
                    return `<td>${getValue(item, 'nama_jalan')}</td><td>${getValue(item, 'panjang_jalan')}</td><td>${getValue(item, 'lebar_jalan')}</td><td><span class="status-badge status-${kondisiJalan}">${this.capitalize(kondisiJalan)}</span></td><td>${getValue(item, 'jenis_permukaan')}</td>`;
                    
                case 'jembatan':
                    const kondisiJembatan = getValue(item, 'kondisi_jembatan');
                    return `<td>${getValue(item, 'nama_jembatan')}</td><td>${getValue(item, 'panjang_jembatan')}</td><td>${getValue(item, 'lebar_jembatan')}</td><td><span class="status-badge status-${kondisiJembatan}">${this.capitalize(kondisiJembatan)}</span></td><td>${getValue(item, 'material_jembatan')}</td>`;
                    
                default:
                    return '<td colspan="100%">Data tidak dikenali</td>';
            }
        }
        
        updatePaginationInfo(tableId, data) {
            const pagination = this.pagination[tableId];
            const tableSection = document.querySelector(`[data-type="${pagination.dataType}"]`);
            const paginationControls = tableSection.querySelector('.pagination-controls');
            
            if (!paginationControls) return;
            
            // Update pagination data
            pagination.totalItems = data.total;
            pagination.totalPages = data.total_pages;
            pagination.currentPage = data.page;
            
            // Calculate display range
            const startItem = data.data.length > 0 ? ((data.page - 1) * data.limit) + 1 : 0;
            const endItem = Math.min(startItem + data.data.length - 1, data.total);
            
            // Update display
            paginationControls.querySelector('.start-item').textContent = startItem;
            paginationControls.querySelector('.end-item').textContent = endItem;
            paginationControls.querySelector('.total-items').textContent = data.total;
            paginationControls.querySelector('.current-page').textContent = data.page;
            paginationControls.querySelector('.total-pages').textContent = Math.max(data.total_pages, 1);
            
            // Update button states
            const prevBtn = paginationControls.querySelector('.prev-btn');
            const nextBtn = paginationControls.querySelector('.next-btn');
            
            prevBtn.disabled = data.page <= 1;
            nextBtn.disabled = data.page >= data.total_pages || data.total_pages <= 1;
        }
        
        showError(tbody, message) {
            tbody.innerHTML = `<tr><td colspan="100%" style="text-align: center; padding: 32px; color: #dc3545;"><i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>${message}</td></tr>`;
        }
        
        formatNumber(num) {
            if (num === null || num === undefined || num === '') return '0';
            const number = parseFloat(num);
            return isNaN(number) ? '0' : new Intl.NumberFormat('id-ID').format(number);
        }
        
        capitalize(str) {
            if (!str) return '';
            return str.toString().charAt(0).toUpperCase() + str.toString().slice(1);
        }
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.detailDesaPaginationInstance) {
            window.detailDesaPaginationInstance = new DetailDesaPagination();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.detailDesaPaginationInstance) {
            window.detailDesaPaginationInstance = new DetailDesaPagination();
        }
    }
    
    window.reinitializeDetailDesaPagination = function() {
        if (window.detailDesaPaginationInstance) {
            window.detailDesaPaginationInstance = null;
        }
        window.detailDesaPaginationInstance = new DetailDesaPagination();
    };
    
    window.addEventListener('beforeunload', () => {
        if (window.detailDesaPaginationInstance) {
            window.detailDesaPaginationInstance = null;
        }
    });
})();