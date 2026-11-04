// Ekonomi Core Functions
if (typeof window.Ekonomi === 'undefined') {
window.Ekonomi = {
    currentTab: 'mata-pencaharian',
    editingId: null,
    isSubmitting: false,
    tabsLoaded: new Set(), // Track loaded tabs - start empty
    
    // Pagination state for each tab
    pagination: {
        'mata-pencaharian': { page: 1, limit: 10, search: '', filters: {} },
        'umkm': { page: 1, limit: 10, search: '', filters: {} },
        'pertanian': { page: 1, limit: 10, search: '', filters: {} },
        'pasar': { page: 1, limit: 10, search: '', filters: {} },
        'bantuan-sosial': { page: 1, limit: 50, search: '', filters: {} }
    },

    init() {
        if (!document.querySelector('.ekonomi-container')) {
            return;
        }
        
        this.setupEventListeners();
        this.setupSearchAutocomplete();
        // Load initial tab data and mark as loaded
        this.loadTabData('mata-pencaharian');
        this.tabsLoaded.add('mata-pencaharian');
    },

    setupEventListeners() {
        // Tab switching with lazy loading
        const tabs = document.querySelectorAll('.ekonomi-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const tabName = e.target.dataset.tab || e.target.closest('.ekonomi-tab').dataset.tab;
                if (tabName && tabName !== this.currentTab) {
                    this.switchTab(tabName);
                }
            });
        });

        // Search handlers with debounce
        this.setupSearchHandlers();
        this.setupFilterHandlers();
        this.setupFormHandlers();
    },

    setupSearchHandlers() {
        const searchElements = {
            'searchMataPencaharian': 'mata-pencaharian',
            'searchUmkm': 'umkm',
            'searchPertanian': 'pertanian',
            'searchPasar': 'pasar',
            'searchBantuan': 'bantuan-sosial'
        };
        
        Object.keys(searchElements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', debounce((e) => {
                    const tabName = searchElements[id];
                    this.pagination[tabName].search = e.target.value;
                    this.pagination[tabName].page = 1;
                    this.loadTabData(tabName);
                }, 300));
            }
        });
    },

    setupFilterHandlers() {
        const filterSektor = document.getElementById('filterSektor');
        if (filterSektor) {
            filterSektor.addEventListener('change', (e) => {
                this.pagination['mata-pencaharian'].filters.sektor = e.target.value;
                this.pagination['mata-pencaharian'].page = 1;
                this.loadTabData('mata-pencaharian');
            });
        }
    },

    setupFormHandlers() {
        const forms = ['formUmkm', 'formMataPencaharian', 'formPertanian', 'formPasar', 'formBantuan'];
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const method = formId.replace('form', 'save');
                    this[method]();
                });
            }
        });
    },

    switchTab(tabName) {
        if (!tabName) return;
        
        // Update tab buttons
        document.querySelectorAll('.ekonomi-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Update tab content
        document.querySelectorAll('.ekonomi-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const tabId = tabName.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join('');
        
        const tabContent = document.getElementById(`tab${tabId}`);
        if (tabContent) {
            tabContent.classList.add('active');
        }

        this.currentTab = tabName;

        // Lazy load tab data only if not loaded before
        if (!this.tabsLoaded.has(tabName)) {
            this.loadTabData(tabName);
            this.tabsLoaded.add(tabName);
        }
    },

    // Generic method to load tab data
    loadTabData(tabName) {
        const loadMethods = {
            'mata-pencaharian': 'loadMataPencaharianData',
            'umkm': 'loadUmkmData',
            'pertanian': 'loadPertanianData',
            'pasar': 'loadPasarData',
            'bantuan-sosial': 'loadBantuanData'
        };
        
        if (loadMethods[tabName] && this[loadMethods[tabName]]) {
            this[loadMethods[tabName]]();
        }
    },

    // Generic pagination handler
    changePage(tabName, page) {
        this.pagination[tabName].page = page;
        this.loadTabData(tabName);
    },

    // Generic items per page handler
    changeItemsPerPage(tabName, limit) {
        this.pagination[tabName].limit = parseInt(limit);
        this.pagination[tabName].page = 1; // Reset to first page
        this.loadTabData(tabName);
    },

    // Pagination renderer
    renderPagination(containerId, pagination, tabName) {
        const container = document.getElementById(containerId);
        if (!container || !pagination) return;
        
        const { current_page, total_pages, total_items, items_per_page } = pagination;
        
        if (total_pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        html += `<button class="pagination-btn" ${current_page <= 1 ? 'disabled' : ''} onclick="Ekonomi.changePage('${tabName}', ${current_page - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>`;
        
        // Page numbers
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);
        
        if (startPage > 1) {
            html += `<button class="pagination-btn" onclick="Ekonomi.changePage('${tabName}', 1)">1</button>`;
            if (startPage > 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="pagination-btn ${i === current_page ? 'active' : ''}" onclick="Ekonomi.changePage('${tabName}', ${i})">${i}</button>`;
        }
        
        if (endPage < total_pages) {
            if (endPage < total_pages - 1) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
            html += `<button class="pagination-btn" onclick="Ekonomi.changePage('${tabName}', ${total_pages})">${total_pages}</button>`;
        }
        
        // Next button
        html += `<button class="pagination-btn" ${current_page >= total_pages ? 'disabled' : ''} onclick="Ekonomi.changePage('${tabName}', ${current_page + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>`;
        
        container.innerHTML = html;
    },

    // Update table info
    updateTableInfo(infoId, pagination) {
        const infoElement = document.getElementById(infoId);
        if (!infoElement || !pagination) return;
        
        const { current_page, total_items, items_per_page } = pagination;
        const start = ((current_page - 1) * items_per_page) + 1;
        const end = Math.min(current_page * items_per_page, total_items);
        
        infoElement.textContent = `Menampilkan ${start} - ${end} dari ${total_items} data`;
    },

    renderEmptyTable(tbodyId, colspan, message) {
        document.getElementById(tbodyId).innerHTML = `
            <tr>
                <td colspan="${colspan}" class="text-center">
                    <i class="fas fa-info-circle"></i> ${message}
                </td>
            </tr>
        `;
    },

    renderEmptyCards(containerId, message) {
        document.getElementById(containerId).innerHTML = `
            <div class="ekonomi-card-loading">
                <i class="fas fa-info-circle"></i> ${message}
            </div>
        `;
    },

    formatCurrency(amount) {
        if (!amount || amount == 0) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    },

    setupSearchAutocomplete() {
        this.setupSearch('searchPendudukUmkm', 'searchResultsUmkm', 'selectedNikUmkm');
        this.setupSearch('searchPendudukBantuan', 'searchResultsBantuan', 'selectedNikBantuan');
        this.setupSearch('searchPendudukPertanian', 'searchResultsPertanian', 'selectedNikPertanian');
    },
    
    setupSearch(inputId, resultsId, hiddenId) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        const hidden = document.getElementById(hiddenId);
        
        if (!input || !results || !hidden) return;
        
        let timeout;
        input.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                results.style.display = 'none';
                hidden.value = '';
                return;
            }
            
            timeout = setTimeout(() => this.searchPenduduk(query, results, hidden, input), 300);
        });
        
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.style.display = 'none';
            }
        });
    },
    
    async searchPenduduk(query, resultsContainer, hiddenInput, textInput) {
        try {
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?action=penduduk_search&search=${encodeURIComponent(query)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            
            if (data.success && data.data.length > 0) {
                resultsContainer.innerHTML = data.data.map(penduduk => `
                    <div class="search-result-item" onclick="Ekonomi.selectPenduduk('${penduduk.nik}', '${penduduk.nama_lengkap}', '${hiddenInput.id}', '${textInput.id}', '${resultsContainer.id}')">
                        <div class="search-result-name">${penduduk.nama_lengkap}</div>
                        <div class="search-result-nik">NIK: ${penduduk.nik}</div>
                    </div>
                `).join('');
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = '<div class="search-result-item">Tidak ada data ditemukan</div>';
                resultsContainer.style.display = 'block';
            }
        } catch (error) {
            // Search failed silently
        }
    },
    
    selectPenduduk(nik, nama, hiddenId, textId, resultsId) {
        document.getElementById(hiddenId).value = nik;
        document.getElementById(textId).value = `${nama} (${nik})`;
        document.getElementById(resultsId).style.display = 'none';
        
        // Validate required field for bantuan sosial
        if (hiddenId === 'selectedNikBantuan') {
            const textInput = document.getElementById(textId);
            if (textInput) {
                textInput.setCustomValidity('');
            }
        }
    },

    openUmkmModal(data = null) {
        const modal = document.getElementById('modalUmkm');
        const form = document.getElementById('formUmkm');
        const title = document.getElementById('modalUmkmTitle');

        form.reset();
        this.editingId = null;

        if (data) {
            title.textContent = 'Edit UMKM';
            this.editingId = data.id_umkm;
            
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
            
            if (data.nama_pemilik && data.nik_pemilik) {
                const searchInput = document.getElementById('searchPendudukUmkm');
                const hiddenInput = document.getElementById('selectedNikUmkm');
                if (searchInput && hiddenInput) {
                    searchInput.value = `${data.nama_pemilik} (${data.nik_pemilik})`;
                    hiddenInput.value = data.nik_pemilik;
                }
            }
        } else {
            title.textContent = 'Tambah UMKM';
        }

        modal.classList.add('show');
    },

    openBantuanModal(data = null) {
        const modal = document.getElementById('modalBantuan');
        const form = document.getElementById('formBantuan');
        const title = document.getElementById('modalBantuanTitle');

        form.reset();
        this.editingBantuanId = null;

        if (data) {
            title.textContent = 'Edit Penerima Bantuan';
            this.editingBantuanId = data.id_warga_miskin;
            
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
            
            if (data.nama_lengkap && data.nik) {
                const searchInput = document.getElementById('searchPendudukBantuan');
                const hiddenInput = document.getElementById('selectedNikBantuan');
                if (searchInput && hiddenInput) {
                    searchInput.value = `${data.nama_lengkap} (${data.nik})`;
                    hiddenInput.value = data.nik;
                }
            }
        } else {
            title.textContent = 'Tambah Penerima Bantuan';
        }

        modal.classList.add('show');
    },

    openPasarModal(data = null) {
        const modal = document.getElementById('modalPasar');
        const form = document.getElementById('formPasar');
        const title = document.getElementById('modalPasarTitle');

        form.reset();
        this.editingPasarId = null;

        if (data) {
            title.textContent = 'Edit Pasar';
            this.editingPasarId = data.id_pasar;
            
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
        } else {
            title.textContent = 'Tambah Pasar';
        }

        modal.classList.add('show');
    }
};
}
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