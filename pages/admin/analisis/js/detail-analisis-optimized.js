// Optimized Detail Analisis with Server-side Pagination
(function() {
    'use strict';
    
    class OptimizedDetailAnalisis {
        constructor() {
            if (document.querySelector('.detail-analisis-container')) {
                this.baseUrl = window.BASE_URL || (window.location.protocol + '//' + window.location.host + '/Pendataan-desa/');
                this.pagination = {};
                this.searchTimeouts = {};
                this.loadedTabs = new Set();
                this.currentAnalysisType = this.getCurrentAnalysisType();
                this.currentDesa = this.getCurrentDesa();
                this.init();
            }
        }
        
        init() {
            this.setupNavigation();
            this.setupTabSwitching();
            this.initializeCurrentTab();
        }
        
        getCurrentAnalysisType() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('type') || 'kependudukan';
        }
        
        getCurrentDesa() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('desa') || 'all';
        }
        
        setupNavigation() {
            const breadcrumbLink = document.querySelector('.detail-analisis-breadcrumb a');
            if (breadcrumbLink) {
                breadcrumbLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.location.href = this.baseUrl + 'pages/admin/analisis/analisis.php';
                });
            }
        }
        
        setupTabSwitching() {
            const tabButtons = document.querySelectorAll('.detail-analisis-tab-btn');
            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const onclick = button.getAttribute('onclick');
                    if (onclick) {
                        const match = onclick.match(/'([^']+)'/);
                        if (match) {
                            this.switchAnalysisType(match[1]);
                        }
                    }
                });
            });
        }
        
        switchAnalysisType(type) {
            const newUrl = `${this.baseUrl}pages/admin/analisis/detail-analisis.php?desa=${this.currentDesa}&type=${type}`;
            window.location.href = newUrl;
        }
        
        initializeCurrentTab() {
            // Initialize tables for current tab
            this.loadedTabs.add(this.currentAnalysisType);
            
            switch (this.currentAnalysisType) {
                case 'kependudukan':
                    this.initializeKependudukanTables();
                    break;
                case 'ekonomi':
                    this.initializeEkonomiTables();
                    break;
                case 'integrasi':
                    this.initializeIntegrasiTables();
                    break;
                case 'spasial':
                    this.initializeSpasialTables();
                    break;
            }
        }
        
        initializeKependudukanTables() {
            this.initTable('kkPerempuanTable', 'get_kk_perempuan', 'searchKKPerempuan');
            this.initTable('produktifTable', 'get_produktif', 'searchProduktif');
            this.initTable('lansiaTable', 'get_lansia', 'searchLansia');
        }
        
        initializeEkonomiTables() {
            this.initTable('petaniTable', 'get_petani', 'searchPetani');
            this.initTable('korelasiTable', 'get_korelasi', 'searchKorelasi');
            this.initTable('kesenjanganTable', 'get_kesenjangan', 'searchKesenjangan');
            this.initTable('distribusiTable', 'get_distribusi', 'searchDistribusi');
            this.initTable('nonPertanianTable', 'get_non_pertanian', 'searchNonPertanian');
        }
        
        initializeIntegrasiTables() {
            // Integrasi tables use static data for now
            this.setupStaticTableSearch('searchKKMiskin', 'kkMiskinTable');
            this.setupStaticTableSearch('searchRasioEkonomi', 'rasioEkonomiTable');
            this.setupStaticTableSearch('searchPendidikanTinggiMiskin', 'pendidikanTinggiMiskinTable');
            this.setupStaticTableSearch('searchAnakTidakSekolah', 'anakTidakSekolahTable');
            this.setupStaticTableSearch('searchAnakPetani', 'anakPetaniTable');
        }
        
        initializeSpasialTables() {
            // Spasial tables use static data for now
            this.setupStaticTableSearch('searchSpasial', 'spasialTable');
            this.setupStaticTableSearch('searchAksesibilitas', 'aksesibilitasTable');
            this.setupStaticTableSearch('searchPotensi', 'potensiTable');
        }
        
        initTable(tableId, action, searchId) {
            const table = document.getElementById(tableId);
            
            if (!table) return;
            
            this.pagination[tableId] = {
                currentPage: 1,
                itemsPerPage: 10,
                totalItems: 0,
                totalPages: 1,
                action: action,
                loading: false
            };
            
            this.setupTableControls(tableId, searchId);
            this.loadTableData(tableId);
        }
        
        setupTableControls(tableId, searchId) {
            const table = document.getElementById(tableId);
            const tableSection = table.closest('.data-table-section');
            
            // Add pagination controls if not exists
            if (!tableSection.querySelector('.pagination-controls')) {
                this.addPaginationControls(tableSection, tableId);
            }
            
            // Setup search
            const searchInput = document.getElementById(searchId);
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value;
                    
                    if (this.searchTimeouts[tableId]) {
                        clearTimeout(this.searchTimeouts[tableId]);
                    }
                    
                    this.searchTimeouts[tableId] = setTimeout(() => {
                        this.pagination[tableId].currentPage = 1;
                        this.loadTableData(tableId, searchTerm);
                    }, 500);
                });
            }
        }
        
        addPaginationControls(tableSection, tableId) {
            const paginationHtml = `
                <div class="pagination-controls" id="${tableId}Pagination">
                    <div class="pagination-info">
                        <select class="items-per-page" data-table="${tableId}">
                            <option value="5">5 per halaman</option>
                            <option value="10" selected>10 per halaman</option>
                            <option value="25">25 per halaman</option>
                            <option value="50">50 per halaman</option>
                        </select>
                        <span class="pagination-text">Menampilkan <span class="start-item">0</span>-<span class="end-item">0</span> dari <span class="total-items">0</span> data</span>
                    </div>
                    <div class="pagination-buttons">
                        <button class="pagination-btn prev-btn" data-table="${tableId}" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="page-info">Halaman <span class="current-page">1</span> dari <span class="total-pages">1</span></span>
                        <button class="pagination-btn next-btn" data-table="${tableId}" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            `;
            
            tableSection.insertAdjacentHTML('beforeend', paginationHtml);
            
            // Setup event listeners
            const itemsPerPageSelect = tableSection.querySelector('.items-per-page');
            const prevBtn = tableSection.querySelector('.prev-btn');
            const nextBtn = tableSection.querySelector('.next-btn');
            
            itemsPerPageSelect.addEventListener('change', (e) => {
                this.pagination[tableId].itemsPerPage = parseInt(e.target.value);
                this.pagination[tableId].currentPage = 1;
                this.loadTableData(tableId);
            });
            
            prevBtn.addEventListener('click', () => {
                if (this.pagination[tableId].currentPage > 1) {
                    this.pagination[tableId].currentPage--;
                    this.loadTableData(tableId);
                }
            });
            
            nextBtn.addEventListener('click', () => {
                if (this.pagination[tableId].currentPage < this.pagination[tableId].totalPages) {
                    this.pagination[tableId].currentPage++;
                    this.loadTableData(tableId);
                }
            });
        }
        
        async loadTableData(tableId, search = '') {
            const pagination = this.pagination[tableId];
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            
            if (pagination.loading) return;
            pagination.loading = true;
            
            // Show loading
            tbody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 32px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>';
            
            try {
                const params = new URLSearchParams({
                    action: pagination.action,
                    desa: this.currentDesa,
                    page: pagination.currentPage,
                    limit: pagination.itemsPerPage,
                    search: search
                });
                
                const response = await fetch(`${this.baseUrl}pages/admin/analisis/detail-analisis.php?${params}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    this.renderTableData(tableId, result.data);
                    this.updatePaginationInfo(tableId, result.data);
                } else {
                    this.showError(tbody, result.message || 'Gagal memuat data');
                }
            } catch (error) {
                console.error('Error loading table data:', error);
                this.showError(tbody, 'Terjadi kesalahan saat memuat data');
            } finally {
                pagination.loading = false;
            }
        }
        
        renderTableData(tableId, data) {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 32px; color: #666;"><i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>Tidak ada data</td></tr>';
                return;
            }
            
            let html = '';
            data.data.forEach((item, index) => {
                const rowNumber = ((data.page - 1) * data.limit) + index + 1;
                html += '<tr>' + this.renderRowByTable(tableId, item, rowNumber) + '</tr>';
            });
            
            tbody.innerHTML = html;
        }
        
        renderRowByTable(tableId, item, rowNumber) {
            const getValue = (obj, key, fallback = '') => {
                return obj && obj[key] !== undefined && obj[key] !== null ? obj[key] : fallback;
            };
            
            switch (tableId) {
                case 'kkPerempuanTable':
                    const usiaList = item.anak_sekolah ? item.anak_sekolah.map(anak => anak.usia).join(', ') : '';
                    const statusBadge = item.is_miskin ? 'status-miskin' : 'status-mampu';
                    const statusText = item.is_miskin ? 'Miskin' : 'Mampu';
                    return `<td>${rowNumber}</td><td>${getValue(item.kepala_keluarga, 'nama_lengkap')}</td><td>${getValue(item, 'desa')}</td><td>${item.anak_sekolah ? item.anak_sekolah.length : 0} anak</td><td>${usiaList} tahun</td><td><span class="status-badge ${statusBadge}">${statusText}</span></td>`;
                    
                case 'produktifTable':
                    const jk = getValue(item.penduduk, 'jenis_kelamin') === 'L' ? 'Laki-laki' : 'Perempuan';
                    return `<td>${rowNumber}</td><td>${getValue(item.penduduk, 'nama_lengkap')}</td><td>${getValue(item, 'desa')}</td><td>${getValue(item.penduduk, 'pendidikan_terakhir')}</td><td>${jk}</td><td>${getValue(item.penduduk, 'usia')} tahun</td><td><span class="status-badge status-tidak-tetap">Tidak Tetap</span></td>`;
                    
                case 'lansiaTable':
                    const usiaListLansia = item.anak_sekolah ? item.anak_sekolah.map(anak => anak.usia).join(', ') : '';
                    return `<td>${rowNumber}</td><td>${getValue(item.kepala_keluarga, 'nama_lengkap')}</td><td>${getValue(item, 'desa')}</td><td>${getValue(item.kepala_keluarga, 'usia')} tahun</td><td>${item.anak_sekolah ? item.anak_sekolah.length : 0} anak</td><td>${usiaListLansia} tahun</td>`;
                    
                case 'petaniTable':
                    const luas = parseFloat(getValue(item, 'luas_lahan', 0));
                    let kategoriBadge = '';
                    if (luas >= 0.1 && luas <= 0.5) {
                        kategoriBadge = '<span class="kategori-badge kategori-kecil">0.1-0.5 Ha</span>';
                    } else if (luas > 0.5 && luas < 1) {
                        kategoriBadge = '<span class="kategori-badge kategori-sedang">0.5-1 Ha</span>';
                    }
                    return `<td>${rowNumber}</td><td>${getValue(item, 'nama_petani')}</td><td>${getValue(item, 'desa')}</td><td>${luas.toFixed(2)} Ha</td><td>${getValue(item, 'jenis_komoditas')}</td><td>Rp ${this.formatNumber(getValue(item, 'pendapatan_per_musim', 0))}</td><td>${kategoriBadge}</td>`;
                    
                case 'korelasiTable':
                    const sektor = getValue(item.pekerjaan, 'sektor_pekerjaan');
                    const sektorBadge = sektor === 'pertanian' ? 'sektor-pertanian' : 'sektor-non-pertanian';
                    const sektorText = sektor === 'pertanian' ? 'Pertanian' : 'Non-Pertanian';
                    return `<td>${rowNumber}</td><td>${getValue(item.penduduk, 'nama_lengkap')}</td><td>${getValue(item, 'desa')}</td><td>${getValue(item.penduduk, 'pendidikan_terakhir')}</td><td>${getValue(item.pekerjaan, 'sektor_pekerjaan')}</td><td>Rp ${this.formatNumber(getValue(item.pekerjaan, 'penghasilan_perbulan', 0))}</td><td><span class="sektor-badge ${sektorBadge}">${sektorText}</span></td>`;
                    
                case 'kesenjanganTable':
                    let kategoriKesenjangan = '';
                    const totalDesa = this.pagination[tableId].totalItems;
                    if (rowNumber <= Math.ceil(totalDesa / 3)) {
                        kategoriKesenjangan = '<span class="kategori-badge kategori-tinggi">Tinggi</span>';
                    } else if (rowNumber <= Math.ceil(totalDesa * 2 / 3)) {
                        kategoriKesenjangan = '<span class="kategori-badge kategori-sedang">Sedang</span>';
                    } else {
                        kategoriKesenjangan = '<span class="kategori-badge kategori-rendah">Rendah</span>';
                    }
                    return `<td>${rowNumber}</td><td>${getValue(item, 'nama_desa')}</td><td>Rp ${this.formatNumber(getValue(item, 'total_pendapatan', 0))}</td><td>${getValue(item, 'jumlah_pekerja')} orang</td><td>Rp ${this.formatNumber(getValue(item, 'rata_pendapatan', 0))}</td><td>${kategoriKesenjangan}</td>`;
                    
                case 'distribusiTable':
                    const dominasiSektor = getValue(item, 'pendapatan_pertanian', 0) > getValue(item, 'pendapatan_non_pertanian', 0) ? 'Pertanian' : 'Non-Pertanian';
                    const dominasiBadge = dominasiSektor === 'Pertanian' ? 'sektor-pertanian' : 'sektor-non-pertanian';
                    return `<td>${rowNumber}</td><td>${getValue(item, 'nama_desa')}</td><td>Rp ${this.formatNumber(getValue(item, 'pendapatan_pertanian', 0))}</td><td>Rp ${this.formatNumber(getValue(item, 'pendapatan_non_pertanian', 0))}</td><td>Rp ${this.formatNumber(getValue(item, 'total_pendapatan', 0))}</td><td><span class="sektor-badge ${dominasiBadge}">${dominasiSektor}</span></td>`;
                    
                case 'nonPertanianTable':
                    const statusPekerjaan = getValue(item.pekerjaan, 'status_pekerjaan');
                    const statusBadgePekerjaan = statusPekerjaan === 'tetap' ? 'status-tetap' : 'status-tidak-tetap';
                    return `<td>${rowNumber}</td><td>${getValue(item.penduduk, 'nama_lengkap')}</td><td>${getValue(item, 'desa')}</td><td>${this.capitalize(getValue(item.pekerjaan, 'sektor_pekerjaan'))}</td><td>${getValue(item.pekerjaan, 'jenis_pekerjaan')}</td><td>Rp ${this.formatNumber(getValue(item.pekerjaan, 'penghasilan_perbulan', 0))}</td><td><span class="status-badge ${statusBadgePekerjaan}">${this.capitalize(statusPekerjaan)}</span></td>`;
                    
                default:
                    return '<td colspan="100%">Data tidak dikenali</td>';
            }
        }
        
        updatePaginationInfo(tableId, data) {
            const pagination = this.pagination[tableId];
            const paginationControls = document.getElementById(`${tableId}Pagination`);
            
            if (!paginationControls) return;
            
            pagination.totalItems = data.total;
            pagination.totalPages = Math.max(data.total_pages, 1);
            pagination.currentPage = data.page;
            
            const startItem = data.data.length > 0 ? ((data.page - 1) * data.limit) + 1 : 0;
            const endItem = Math.min(startItem + data.data.length - 1, data.total);
            
            paginationControls.querySelector('.start-item').textContent = startItem;
            paginationControls.querySelector('.end-item').textContent = endItem;
            paginationControls.querySelector('.total-items').textContent = data.total;
            paginationControls.querySelector('.current-page').textContent = data.page;
            paginationControls.querySelector('.total-pages').textContent = pagination.totalPages;
            
            const prevBtn = paginationControls.querySelector('.prev-btn');
            const nextBtn = paginationControls.querySelector('.next-btn');
            
            prevBtn.disabled = data.page <= 1;
            nextBtn.disabled = data.page >= data.total_pages || data.total_pages <= 1;
        }
        
        setupStaticTableSearch(searchId, tableId) {
            const searchInput = document.getElementById(searchId);
            const table = document.getElementById(tableId);
            
            if (!searchInput || !table) return;
            
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr:not(.no-results-row)');
                let visibleCount = 0;
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                this.toggleNoResultsMessage(table, searchTerm, visibleCount);
            });
        }
        
        toggleNoResultsMessage(table, searchTerm, visibleCount) {
            const tbody = table.querySelector('tbody');
            let noResultsRow = tbody.querySelector('.no-results-row');
            
            if (searchTerm && visibleCount === 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML = `
                        <td colspan="100%" style="text-align: center; padding: 32px; color: #666;">
                            <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                            Tidak ada data yang sesuai dengan pencarian "${searchTerm}"
                        </td>
                    `;
                    tbody.appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
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
    
    // Global functions for backward compatibility
    window.switchAnalysisType = function(type) {
        if (window.optimizedDetailAnalisisInstance) {
            window.optimizedDetailAnalisisInstance.switchAnalysisType(type);
        }
    };
    
    window.goBackToAnalisis = function() {
        window.location.href = window.location.origin + '/Pendataan-desa/pages/admin/analisis/analisis.php';
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.optimizedDetailAnalisisInstance) {
            window.optimizedDetailAnalisisInstance = new OptimizedDetailAnalisis();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.optimizedDetailAnalisisInstance) {
            window.optimizedDetailAnalisisInstance = new OptimizedDetailAnalisis();
        }
    }
})();