(function() {
    'use strict';
    
    class DetailDesa {
        constructor() {
            if (document.querySelector('.detail-desa-container')) {
                this.pagination = {};
                this.init();
            }
        }
        
        init() {
            this.setupSearchFilters();
            this.setupNavigation();
            this.setupPagination();
        }
        
        setupNavigation() {
            const breadcrumbLink = document.querySelector('.detail-desa-breadcrumb a');
            if (breadcrumbLink) {
                breadcrumbLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Traditional redirect instead of AJAX
                    const baseUrl = window.BASE_URL || (window.location.protocol + '//' + window.location.host + '/Pendataan-desa/');
                    window.location.href = baseUrl + 'pages/admin/monitoring/monitoring.php';
                });
            }
        }
        
        setupSearchFilters() {
            this.setupTableSearch('searchPenduduk', 'pendudukTable');
            this.setupTableSearch('searchPendidikan', 'pendidikanTable');
            this.setupTableSearch('searchUMKM', 'umkmTable');
            this.setupTableSearch('searchPasar', 'pasarTable');
            this.setupTableSearch('searchJalan', 'jalanTable');
            this.setupTableSearch('searchJembatan', 'jembatanTable');
        }
        
        setupPagination() {
            const tables = ['pendudukTable', 'pendidikanTable', 'umkmTable', 'pasarTable', 'jalanTable', 'jembatanTable'];
            tables.forEach(tableId => this.initTablePagination(tableId));
        }
        
        initTablePagination(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tableSection = table.closest('.data-table-section');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            
            this.pagination[tableId] = {
                currentPage: 1,
                itemsPerPage: 5,
                totalItems: rows.length,
                filteredRows: rows
            };
            
            this.addPaginationControls(tableSection, tableId);
            this.updatePagination(tableId);
        }
        
        addPaginationControls(tableSection, tableId) {
            const paginationHtml = `
                <div class="pagination-controls" id="${tableId}Pagination">
                    <div class="pagination-info">
                        <select class="items-per-page" data-table="${tableId}">
                            <option value="5">5 per halaman</option>
                            <option value="10">10 per halaman</option>
                            <option value="25">25 per halaman</option>
                            <option value="50">50 per halaman</option>
                        </select>
                        <span class="pagination-text">Menampilkan <span class="start-item">1</span>-<span class="end-item">5</span> dari <span class="total-items">0</span> data</span>
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
                this.pagination[tableId].itemsPerPage = parseInt(e.target.value);
                this.pagination[tableId].currentPage = 1;
                this.updatePagination(tableId);
            });
            
            prevBtn.addEventListener('click', () => {
                if (this.pagination[tableId].currentPage > 1) {
                    this.pagination[tableId].currentPage--;
                    this.updatePagination(tableId);
                }
            });
            
            nextBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(this.pagination[tableId].filteredRows.length / this.pagination[tableId].itemsPerPage);
                if (this.pagination[tableId].currentPage < totalPages) {
                    this.pagination[tableId].currentPage++;
                    this.updatePagination(tableId);
                }
            });
        }
        
        updatePagination(tableId) {
            const pagination = this.pagination[tableId];
            const table = document.getElementById(tableId);
            const paginationControls = document.getElementById(`${tableId}Pagination`);
            
            if (!table || !paginationControls || !pagination) return;
            
            const { currentPage, itemsPerPage, filteredRows } = pagination;
            const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            
            const allRows = table.querySelectorAll('tbody tr');
            allRows.forEach(row => row.style.display = 'none');
            
            filteredRows.slice(startIndex, endIndex).forEach(row => {
                row.style.display = '';
            });
            
            const startItem = filteredRows.length > 0 ? startIndex + 1 : 0;
            const endItem = Math.min(endIndex, filteredRows.length);
            
            paginationControls.querySelector('.start-item').textContent = startItem;
            paginationControls.querySelector('.end-item').textContent = endItem;
            paginationControls.querySelector('.total-items').textContent = filteredRows.length;
            paginationControls.querySelector('.current-page').textContent = currentPage;
            paginationControls.querySelector('.total-pages').textContent = Math.max(totalPages, 1);
            
            const prevBtn = paginationControls.querySelector('.prev-btn');
            const nextBtn = paginationControls.querySelector('.next-btn');
            
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
        }
        
        setupTableSearch(searchInputId, tableId) {
            const searchInput = document.getElementById(searchInputId);
            const table = document.getElementById(tableId);
            
            if (!searchInput || !table) return;
            
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const allRows = Array.from(table.querySelectorAll('tbody tr:not(.no-results-row)'));
                
                const filteredRows = allRows.filter(row => {
                    return row.textContent.toLowerCase().includes(searchTerm);
                });
                
                if (this.pagination[tableId]) {
                    this.pagination[tableId].filteredRows = filteredRows;
                    this.pagination[tableId].currentPage = 1;
                    this.updatePagination(tableId);
                }
                
                this.toggleNoResultsMessage(table, searchTerm, filteredRows);
            });
        }
        
        toggleNoResultsMessage(table, searchTerm, filteredRows) {
            const tbody = table.querySelector('tbody');
            let noResultsRow = tbody.querySelector('.no-results-row');
            
            if (searchTerm && filteredRows.length === 0) {
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
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.detailDesaInstance) {
            window.detailDesaInstance = new DetailDesa();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.detailDesaInstance) {
            window.detailDesaInstance = new DetailDesa();
        }
    }
    
    window.reinitializeDetailDesa = function() {
        if (window.detailDesaInstance) {
            window.detailDesaInstance = null;
        }
        window.detailDesaInstance = new DetailDesa();
    };
    
    window.addEventListener('beforeunload', () => {
        if (window.detailDesaInstance) {
            window.detailDesaInstance = null;
        }
    });
})();