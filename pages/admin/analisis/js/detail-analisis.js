// Detail Analisis Namespace
const DetailAnalisis = {
    loadedTabs: new Set(),
    currentTab: null,
    
    init: function() {
        if (!document.querySelector('.detail-analisis-container')) return;
        
        this.initializeTabSwitching();
        this.setupNavigation();
        
        // Get initial tab from URL
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('type') || 'kependudukan';
        this.currentTab = initialTab;
        
        // Don't add to loadedTabs since initial content is loaded server-side
        // This ensures first tab switch will work properly
        
        // Initialize features for initially loaded content
        setTimeout(() => {
            this.initializeTableFeatures();
            this.setupSearchFilters();
            this.setupPagination();
            // DON'T load initial data via AJAX - data already rendered from server
            // Only update pagination controls with current state
            this.updateInitialPaginationState();
        }, 100);
    },

    setupNavigation: function() {
        const breadcrumbLink = document.querySelector('.detail-analisis-breadcrumb a');
        if (breadcrumbLink) {
            breadcrumbLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigateToAnalisis();
            });
        }
    },

    navigateToAnalisis: function() {
        window.location.href = window.location.origin + '/Pendataan-desa/pages/admin/analisis/analisis.php';
    },

    setupSearchFilters: function() {
        this.setupTableSearch('searchKKPerempuan', 'kkPerempuanTable');
        this.setupTableSearch('searchProduktif', 'produktifTable');
        this.setupTableSearch('searchLansia', 'lansiaTable');
        this.setupTableSearch('searchRasio', 'rasioTable');
        this.setupTableSearch('searchPetani', 'petaniTable');
        this.setupTableSearch('searchKorelasi', 'korelasiTable');
        this.setupTableSearch('searchPendidikanTinggi', 'pendidikanTinggiTable');
        this.setupTableSearch('searchKesenjangan', 'kesenjanganTable');
        this.setupTableSearch('searchDistribusi', 'distribusiTable');
        this.setupTableSearch('searchNonPertanian', 'nonPertanianTable');
        // Integrasi tab
        this.setupTableSearch('searchKKMiskin', 'kkMiskinTable');
        this.setupTableSearch('searchRasioEkonomi', 'rasioEkonomiTable');
        this.setupTableSearch('searchPendidikanTinggiMiskin', 'pendidikanTinggiMiskinTable');
        this.setupTableSearch('searchAnakTidakSekolah', 'anakTidakSekolahTable');
        this.setupTableSearch('searchAnakPetani', 'anakPetaniTable');
        // Spasial tab
        this.setupTableSearch('searchSpasial', 'spasialTable');
        this.setupTableSearch('searchAksesibilitas', 'aksesibilitasTable');
        this.setupTableSearch('searchPotensi', 'potensiTable');
    },

    setupPagination: function() {
        // Initialize AJAX-based pagination for each table type
        this.initAjaxPagination();
    },

    initAjaxPagination: function() {
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        this.currentPage = parseInt(urlParams.get('page')) || 1;
        this.currentLimit = parseInt(urlParams.get('limit')) || 10;
        this.currentSearch = urlParams.get('search') || '';
        
        // Initialize pagination state per table
        this.tablePaginationState = {};
        
        // Add pagination controls to all table sections
        const tableSections = document.querySelectorAll('.data-table-section');
        tableSections.forEach(section => {
            const table = section.querySelector('.data-table');
            if (table) {
                const tableId = table.id;
                // Initialize state for this table
                this.tablePaginationState[tableId] = {
                    page: 1,
                    limit: 10,
                    search: ''
                };
                this.addServerPaginationControls(section, tableId);
            }
        });
        
        // Set initial search values
        this.setInitialSearchValues();
    },

    addServerPaginationControls: function(tableSection, tableId) {
        // Check if pagination already exists
        if (tableSection.querySelector('.pagination-controls')) {
            return; // Don't add duplicate pagination
        }
        
        // Get state for this table
        const state = this.tablePaginationState[tableId] || { page: 1, limit: 10, search: '' };
        
        const paginationHtml = `
            <div class="pagination-controls" id="${tableId}Pagination">
                <div class="pagination-info">
                    <select class="items-per-page" data-table="${tableId}">
                        <option value="5" ${state.limit === 5 ? 'selected' : ''}>5 per halaman</option>
                        <option value="10" ${state.limit === 10 ? 'selected' : ''}>10 per halaman</option>
                        <option value="25" ${state.limit === 25 ? 'selected' : ''}>25 per halaman</option>
                        <option value="50" ${state.limit === 50 ? 'selected' : ''}>50 per halaman</option>
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
        
        const itemsPerPageSelect = tableSection.querySelector('.items-per-page');
        const prevBtn = tableSection.querySelector('.prev-btn');
        const nextBtn = tableSection.querySelector('.next-btn');
        
        itemsPerPageSelect.addEventListener('change', (e) => {
            const newLimit = parseInt(e.target.value);
            this.tablePaginationState[tableId].limit = newLimit;
            this.tablePaginationState[tableId].page = 1;
            this.loadTableDataAjax(tableId);
        });
        
        prevBtn.addEventListener('click', () => {
            if (this.tablePaginationState[tableId].page > 1) {
                this.tablePaginationState[tableId].page--;
                this.loadTableDataAjax(tableId);
            }
        });
        
        nextBtn.addEventListener('click', () => {
            const totalPagesSpan = tableSection.querySelector('.total-pages');
            const totalPages = parseInt(totalPagesSpan.textContent) || 1;
            if (this.tablePaginationState[tableId].page < totalPages) {
                this.tablePaginationState[tableId].page++;
                this.loadTableDataAjax(tableId);
            }
        });
    },

    setInitialSearchValues: function() {
        // Set search input values from URL
        const searchInputs = document.querySelectorAll('.search-input');
        searchInputs.forEach(input => {
            if (this.currentSearch) {
                input.value = this.currentSearch;
            }
        });
    },

    
    updatePaginationInfo: function() {
        // This method is now handled by updatePaginationControls in AJAX response
        // Keeping for backward compatibility
    },

    setupTableSearch: function(searchInputId, tableId) {
        const searchInput = document.getElementById(searchInputId);
        
        if (!searchInput) return;
        
        // Debounce search to avoid too many requests
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchValue = e.target.value.trim();
                // Update state for this specific table
                if (this.tablePaginationState[tableId]) {
                    this.tablePaginationState[tableId].search = searchValue;
                    this.tablePaginationState[tableId].page = 1; // Reset to first page
                }
                this.loadTableDataAjax(tableId);
            }, 500); // 500ms delay
        });
    },
    
    loadTableDataAjax: function(tableId) {
        // Map table IDs to API actions
        const tableActionMap = {
            'kkPerempuanTable': 'get_kk_perempuan',
            'produktifTable': 'get_produktif', 
            'lansiaTable': 'get_lansia',
            'petaniTable': 'get_petani',
            'korelasiTable': 'get_korelasi',
            'kesenjanganTable': 'get_kesenjangan',
            'distribusiTable': 'get_distribusi',
            'nonPertanianTable': 'get_non_pertanian',
            'kkMiskinTable': 'get_kk_miskin',
            'rasioEkonomiTable': 'get_rasio_ekonomi',
            'pendidikanTinggiMiskinTable': 'get_pendidikan_tinggi_miskin',
            'anakTidakSekolahTable': 'get_anak_tidak_sekolah',
            'anakPetaniTable': 'get_anak_petani',
            'spasialTable': 'get_spasial',
            'aksesibilitasTable': 'get_aksesibilitas',
            'potensiTable': 'get_potensi'
        };
        
        const action = tableActionMap[tableId];
        if (!action) {
            console.error('Unknown table ID:', tableId);
            return;
        }
        
        // Get state for this specific table
        const state = this.tablePaginationState[tableId] || { page: 1, limit: 10, search: '' };
        
        const urlParams = new URLSearchParams(window.location.search);
        const currentDesa = urlParams.get('desa') || 'all';
        
        // Show loading state
        this.showTableLoading(tableId);
        
        // Build API URL with table-specific state
        const apiUrl = `${window.location.origin}/Pendataan-desa/pages/admin/analisis/php/detail-analisis-ajax.php?action=${action}&desa=${currentDesa}&page=${state.page}&limit=${state.limit}&search=${encodeURIComponent(state.search)}`;
        
        fetch(apiUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                this.updateTableContent(tableId, result.data);
                this.updatePaginationControls(tableId, result.data);
            } else {
                throw new Error(result.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error loading table data:', error);
            this.showTableError(tableId, error.message);
        })
        .finally(() => {
            this.hideTableLoading(tableId);
        });
    },
    
    showTableLoading: function(tableId) {
        const table = document.getElementById(tableId);
        if (table) {
            const tbody = table.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr class="loading-row">
                        <td colspan="100%" style="text-align: center; padding: 32px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                            Memuat data...
                        </td>
                    </tr>
                `;
            }
        }
    },
    
    hideTableLoading: function(tableId) {
        // Loading will be hidden when content is updated
    },
    
    showTableError: function(tableId, message) {
        const table = document.getElementById(tableId);
        if (table) {
            const tbody = table.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr class="error-row">
                        <td colspan="100%" style="text-align: center; padding: 32px; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                            Error: ${message}
                            <br><button onclick="DetailAnalisis.loadTableDataAjax('${tableId}')" class="retry-btn" style="margin-top: 8px;">Coba Lagi</button>
                        </td>
                    </tr>
                `;
            }
        }
    },
    
    updateTableContent: function(tableId, data) {
        const table = document.getElementById(tableId);
        if (!table || !data.data) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        let html = '';
        const startNumber = (data.page - 1) * data.limit + 1;
        
        if (data.data.length === 0) {
            html = `
                <tr class="no-results-row">
                    <td colspan="100%" style="text-align: center; padding: 32px; color: #666;">
                        <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        ${this.currentSearch ? `Tidak ada data yang sesuai dengan pencarian "${this.currentSearch}"` : 'Tidak ada data tersedia'}
                    </td>
                </tr>
            `;
        } else {
            // Generate table rows based on table type
            html = this.generateTableRows(tableId, data.data, startNumber);
        }
        
        tbody.innerHTML = html;
    },
    
    generateTableRows: function(tableId, data, startNumber) {
        let html = '';
        
        switch (tableId) {
            case 'kkPerempuanTable':
                data.forEach((item, index) => {
                    const anakUsia = item.anak_sekolah.map(anak => anak.usia).join(', ');
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.kepala_keluarga.nama_lengkap || 'Data Tidak Tersedia'}</td>
                            <td>${item.desa}</td>
                            <td>${item.anak_sekolah.length} anak</td>
                            <td>${anakUsia} tahun</td>
                            <td><span class="status-badge status-miskin">Miskin</span></td>
                        </tr>
                    `;
                });
                break;
                
            case 'produktifTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.penduduk.nama_lengkap}</td>
                            <td>${item.desa}</td>
                            <td>${item.penduduk.pendidikan_terakhir}</td>
                            <td>${item.penduduk.jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                            <td>${item.penduduk.usia} tahun</td>
                            <td><span class="status-badge status-tidak-tetap">Tidak Tetap</span></td>
                        </tr>
                    `;
                });
                break;
                
            case 'lansiaTable':
                data.forEach((item, index) => {
                    const usiaTanggungan = item.tanggungan.map(t => t.usia).join(', ');
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.kepala_keluarga.nama_lengkap || 'Data Tidak Tersedia'}</td>
                            <td>${item.desa}</td>
                            <td>${item.kepala_keluarga.usia || 'N/A'} tahun</td>
                            <td>${item.tanggungan.length} orang</td>
                            <td>${usiaTanggungan || 'Tidak ada tanggungan'} tahun</td>
                        </tr>
                    `;
                });
                break;
                
            case 'petaniTable':
                data.forEach((item, index) => {
                    const kategori = item.luas_lahan <= 0.5 ? 
                        '<span class="kategori-badge kategori-kecil">0.1-0.5 Ha</span>' :
                        '<span class="kategori-badge kategori-sedang">0.5-1 Ha</span>';
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.nama_petani}</td>
                            <td>${item.desa}</td>
                            <td>${parseFloat(item.luas_lahan).toFixed(2)} Ha</td>
                            <td>${item.jenis_komoditas}</td>
                            <td>Rp ${parseInt(item.pendapatan_per_musim).toLocaleString()}</td>
                            <td>${kategori}</td>
                        </tr>
                    `;
                });
                break;
                
            case 'korelasiTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.penduduk.nama_lengkap}</td>
                            <td>${item.desa}</td>
                            <td>${item.penduduk.pendidikan_terakhir}</td>
                            <td>${item.pekerjaan.sektor_pekerjaan}</td>
                            <td>Rp ${parseInt(item.pekerjaan.penghasilan_perbulan || 0).toLocaleString()}</td>
                            <td>
                                ${item.pekerjaan.sektor_pekerjaan === 'pertanian' ?
                                    '<span class="sektor-badge sektor-pertanian">Pertanian</span>' :
                                    '<span class="sektor-badge sektor-non-pertanian">Non-Pertanian</span>'
                                }
                            </td>
                        </tr>
                    `;
                });
                break;
                
            case 'kesenjanganTable':
                data.forEach((item, index) => {
                    const totalDesa = data.length;
                    let kategori = '';
                    if (index < totalDesa / 3) {
                        kategori = '<span class="kategori-badge kategori-tinggi">Tinggi</span>';
                    } else if (index < (totalDesa * 2) / 3) {
                        kategori = '<span class="kategori-badge kategori-sedang">Sedang</span>';
                    } else {
                        kategori = '<span class="kategori-badge kategori-rendah">Rendah</span>';
                    }
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.nama_desa}</td>
                            <td>Rp ${parseInt(item.total_pendapatan).toLocaleString()}</td>
                            <td>${item.jumlah_pekerja} orang</td>
                            <td>Rp ${parseInt(item.rata_pendapatan).toLocaleString()}</td>
                            <td>${kategori}</td>
                        </tr>
                    `;
                });
                break;
                
            case 'distribusiTable':
                data.forEach((item, index) => {
                    const dominasi = item.pendapatan_pertanian > item.pendapatan_non_pertanian ?
                        '<span class="sektor-badge sektor-pertanian">Pertanian</span>' :
                        '<span class="sektor-badge sektor-non-pertanian">Non-Pertanian</span>';
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.nama_desa}</td>
                            <td>Rp ${parseInt(item.pendapatan_pertanian).toLocaleString()}</td>
                            <td>Rp ${parseInt(item.pendapatan_non_pertanian).toLocaleString()}</td>
                            <td>Rp ${parseInt(item.total_pendapatan).toLocaleString()}</td>
                            <td>${dominasi}</td>
                        </tr>
                    `;
                });
                break;
                
            case 'nonPertanianTable':
                data.forEach((item, index) => {
                    const statusBadge = item.pekerjaan.status_pekerjaan == 'tetap' ?
                        '<span class="status-badge status-tetap">Tetap</span>' :
                        '<span class="status-badge status-tidak-tetap">Tidak Tetap</span>';
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.penduduk.nama_lengkap}</td>
                            <td>${item.desa}</td>
                            <td>${item.pekerjaan.sektor_pekerjaan.charAt(0).toUpperCase() + item.pekerjaan.sektor_pekerjaan.slice(1)}</td>
                            <td>${item.pekerjaan.jenis_pekerjaan}</td>
                            <td>Rp ${parseInt(item.pekerjaan.penghasilan_perbulan || 0).toLocaleString()}</td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                });
                break;
                
            case 'kkMiskinTable':
                data.forEach((item, index) => {
                    const anakList = item.anak_sekolah.map(anak => `${anak.nama_lengkap} (${anak.usia} tahun)`).join(', ');
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.kepala_keluarga.nama_lengkap || 'Data Tidak Tersedia'}</td>
                            <td>${item.desa}</td>
                            <td>${item.anak_sekolah.length} anak</td>
                            <td>${anakList || 'Data Anak Sekolah Tidak Tersedia'}</td>
                        </tr>
                    `;
                });
                break;
                
            case 'rasioEkonomiTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.kepala_keluarga}</td>
                            <td>${item.desa}</td>
                            <td>${item.total_anggota} orang</td>
                            <td>${item.anggota_bekerja} orang</td>
                            <td>${item.tanggungan} orang</td>
                        </tr>
                    `;
                });
                break;
                
            case 'pendidikanTinggiMiskinTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.penduduk.nama_lengkap}</td>
                            <td>${item.desa}</td>
                            <td>${item.penduduk.pendidikan_terakhir}</td>
                            <td>${item.penduduk.pekerjaan || 'Belum Bekerja'}</td>
                            <td><span class="status-badge status-miskin">${item.faktor_penyebab.charAt(0).toUpperCase() + item.faktor_penyebab.slice(1)}</span></td>
                        </tr>
                    `;
                });
                break;
                
            case 'anakTidakSekolahTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.penduduk.nama_lengkap}</td>
                            <td>${item.desa}</td>
                            <td>${item.penduduk.usia} tahun</td>
                            <td>${item.penduduk.jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                            <td><span class="status-badge status-miskin">Miskin</span></td>
                        </tr>
                    `;
                });
                break;
                
            case 'anakPetaniTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.penduduk.nama_lengkap}</td>
                            <td>${item.desa}</td>
                            <td>${item.penduduk.pendidikan_terakhir}</td>
                            <td>${item.parent.nama_lengkap}</td>
                            <td><span class="status-badge status-tetap">${item.parent.pekerjaan}</span></td>
                        </tr>
                    `;
                });
                break;
                
            case 'spasialTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.nama_desa}</td>
                            <td>${parseInt(item.total_penduduk).toLocaleString()} jiwa</td>
                            <td><span class="status-badge status-miskin">0 unit</span></td>
                        </tr>
                    `;
                });
                break;
                
            case 'aksesibilitasTable':
                data.forEach((item, index) => {
                    let statusBadge = '';
                    if (item.aksesibilitas >= 75) {
                        statusBadge = '<span class="status-badge status-mampu">Baik</span>';
                    } else if (item.aksesibilitas >= 50) {
                        statusBadge = '<span class="kategori-badge kategori-sedang">Sedang</span>';
                    } else {
                        statusBadge = '<span class="status-badge status-miskin">Buruk</span>';
                    }
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.nama_desa}</td>
                            <td>${item.jalan_baik} unit</td>
                            <td>${item.total_jalan} unit</td>
                            <td>${item.aksesibilitas}%</td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                });
                break;
                
            case 'potensiTable':
                data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td>${startNumber + index}</td>
                            <td>${item.nama_desa}</td>
                            <td>${parseInt(item.total_penduduk).toLocaleString()} jiwa</td>
                            <td>${parseInt(item.total_laki).toLocaleString()} orang</td>
                            <td>${parseInt(item.total_perempuan).toLocaleString()} orang</td>
                            <td>${parseInt(item.total_kk).toLocaleString()} KK</td>
                        </tr>
                    `;
                });
                break;
                
            default:
                console.warn('Unknown table type for row generation:', tableId);
                // Fallback: generate basic rows
                data.forEach((item, index) => {
                    html += `<tr><td colspan="100%">Data row ${startNumber + index}</td></tr>`;
                });
        }
        
        return html;
    },
    
    updatePaginationControls: function(tableId, data) {
        const tableSection = document.getElementById(tableId).closest('.data-table-section');
        if (!tableSection) return;
        
        const paginationControl = tableSection.querySelector('.pagination-controls');
        if (!paginationControl) return;
        
        // Update pagination info
        const startItem = data.total > 0 ? (data.page - 1) * data.limit + 1 : 0;
        const endItem = Math.min(startItem + data.data.length - 1, data.total);
        
        paginationControl.querySelector('.start-item').textContent = startItem;
        paginationControl.querySelector('.end-item').textContent = endItem;
        paginationControl.querySelector('.total-items').textContent = data.total;
        paginationControl.querySelector('.current-page').textContent = data.page;
        paginationControl.querySelector('.total-pages').textContent = data.total_pages;
        
        // Update button states
        const prevBtn = paginationControl.querySelector('.prev-btn');
        const nextBtn = paginationControl.querySelector('.next-btn');
        
        prevBtn.disabled = data.page <= 1;
        nextBtn.disabled = data.page >= data.total_pages;
        
        // Update limit select
        const limitSelect = paginationControl.querySelector('.items-per-page');
        limitSelect.value = data.limit;
    },
    
    updateInitialPaginationState: function() {
        // Load data for all visible tables via AJAX on initial page load
        const visibleTables = document.querySelectorAll('.data-table');
        visibleTables.forEach(table => {
            if (table.id && table.offsetParent !== null) {
                // Load data via AJAX to populate pagination info
                this.loadTableDataAjax(table.id);
            }
        });
    },
    
    loadInitialTableData: function() {
        // This is now only called when switching tabs via AJAX
        // NOT called on initial page load
        const visibleTables = document.querySelectorAll('.data-table');
        visibleTables.forEach(table => {
            if (table.id && table.offsetParent !== null) {
                this.loadTableDataAjax(table.id);
            }
        });
    },

    toggleNoResultsMessage: function(table, searchTerm, filteredRows) {
        // This method is now handled by updateTableContent
        // Keeping for backward compatibility
    },

    initializeTableFeatures: function() {
        const tables = document.querySelectorAll('.data-table');
        
        tables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                row.addEventListener('click', function() {
                    // Remove previous selection
                    rows.forEach(r => r.classList.remove('selected'));
                    // Add selection to current row
                    this.classList.add('selected');
                });
            });
        });
    },

    initializeTabSwitching: function() {
        const tabButtons = document.querySelectorAll('.detail-analisis-tab-btn');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const type = button.getAttribute('data-type');
                if (type) {
                    this.switchAnalysisType(type);
                }
            });
        });
    },

    switchAnalysisType: function(type) {
        if (this.currentTab === type) return;
        
        // Show loading
        this.showTabLoading();
        
        // Update tab buttons
        this.updateTabButtons(type);
        
        // Always load tab content via AJAX for consistency
        this.loadTabContent(type);
    },
    
    showTabLoading: function() {
        const loading = document.getElementById('tabLoading');
        const content = document.getElementById('tabContent');
        if (loading && content) {
            loading.style.display = 'block';
            content.style.display = 'none';
        }
    },
    
    hideTabLoading: function() {
        const loading = document.getElementById('tabLoading');
        const content = document.getElementById('tabContent');
        if (loading && content) {
            loading.style.display = 'none';
            content.style.display = 'block';
        }
    },
    
    updateTabButtons: function(activeType) {
        const tabButtons = document.querySelectorAll('.detail-analisis-tab-btn');
        tabButtons.forEach(button => {
            button.classList.remove('active');
            const buttonType = button.getAttribute('data-type');
            if (buttonType === activeType) {
                button.classList.add('active');
            }
        });
    },

    
    loadTabContent: function(type) {
        const urlParams = new URLSearchParams(window.location.search);
        const currentDesa = urlParams.get('desa') || 'all';
        const page = urlParams.get('page') || '1';
        const limit = urlParams.get('limit') || '10';
        const search = urlParams.get('search') || '';
        
        const formData = new FormData();
        formData.append('type', type);
        formData.append('desa', currentDesa);
        formData.append('page', page);
        formData.append('limit', limit);
        formData.append('search', search);
        
        fetch(window.location.origin + '/Pendataan-desa/pages/admin/analisis/php/detail-analisis-ajax.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            const content = document.getElementById('tabContent');
            if (content) {
                content.innerHTML = html;
                this.loadedTabs.add(type);
                this.currentTab = type;
                
                // Re-initialize features for newly loaded content
                setTimeout(() => {
                    this.initializeTableFeatures();
                    this.setupSearchFilters();
                    this.setupPagination();
                    // Load data via AJAX for tab switching
                    this.loadInitialTableData();
                }, 100);
                
                this.hideTabLoading();
                this.updateUrl(type);
            }
        })
        .catch(error => {
            console.error('Error loading tab content:', error);
            this.hideTabLoading();
            
            const content = document.getElementById('tabContent');
            if (content) {
                content.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Gagal memuat data. Silakan coba lagi.</p>
                        <button onclick="DetailAnalisis.loadTabContent('${type}')" class="retry-btn">Coba Lagi</button>
                    </div>
                `;
            }
        });
    },
    
    updateUrl: function(type) {
        const urlParams = new URLSearchParams(window.location.search);
        const currentDesa = urlParams.get('desa') || 'all';
        
        const newUrl = `${window.location.pathname}?desa=${currentDesa}&type=${type}`;
        window.history.replaceState({}, '', newUrl);
    },

    goBackToAnalisis: function() {
        this.navigateToAnalisis();
    }
};

// Global functions for backward compatibility
function switchAnalysisType(type) {
    if (window.detailAnalisisInstance) {
        window.detailAnalisisInstance.switchAnalysisType(type);
    } else {
        DetailAnalisis.switchAnalysisType(type);
    }
}

function goBackToAnalisis() {
    window.location.href = window.location.origin + '/Pendataan-desa/pages/admin/analisis/analisis.php';
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for DetailAnalisis');
    
    if (document.querySelector('.detail-analisis-container')) {
        console.log('Initializing DetailAnalisis');
        window.detailAnalisisInstance = DetailAnalisis;
        DetailAnalisis.init();
    } else {
        console.log('DetailAnalisis container not found');
    }
});

// Also initialize if DOM is already loaded
if (document.readyState !== 'loading' && document.querySelector('.detail-analisis-container')) {
    console.log('DOM already loaded, initializing DetailAnalisis');
    window.detailAnalisisInstance = DetailAnalisis;
    DetailAnalisis.init();
}