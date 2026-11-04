// Kependudukan Core Namespace
if (typeof window.Kependudukan === 'undefined') {
window.Kependudukan = {
    currentTab: 'penduduk',
    pendudukPage: 1,
    keluargaPage: 1,
    itemsPerPage: 10,
    editingId: null,
    isSubmitting: false,
    pendudukLoaded: false,
    keluargaLoaded: false,

    init() {
        if (!document.querySelector('.kependudukan-container')) {
            return;
        }
        
        // Wait a bit for DOM to be fully ready and other scripts to load
        setTimeout(() => {
            this.setupEventListeners();
            
            // Check if loadPendudukData function exists before calling
            if (typeof this.loadPendudukData === 'function') {
                this.loadPendudukData();
                this.pendudukLoaded = true;
            } else {
                // Retry after a longer delay to allow other scripts to load
                setTimeout(() => {
                    if (typeof this.loadPendudukData === 'function') {
                        this.loadPendudukData();
                        this.pendudukLoaded = true;
                    }
                }, 500);
            }
            
            this.setupKepalaKeluargaAutocomplete();
        }, 100);
    },

    setupKepalaKeluargaAutocomplete() {
        // This function will be implemented when needed
    },

    setupEventListeners() {
        // Tab switching
        const tabs = document.querySelectorAll('.kependudukan-tab');
        
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = e.target.dataset.tab || e.target.closest('.kependudukan-tab')?.dataset.tab;
                if (tabName) {
                    this.switchTab(tabName);
                }
            });
        });

        // Search and filters - with null checks
        const searchPenduduk = document.getElementById('searchPenduduk');
        if (searchPenduduk) {
            searchPenduduk.addEventListener('input', 
                debounce(() => {
                    this.pendudukPage = 1;
                    if (typeof this.loadPendudukData === 'function') {
                        this.loadPendudukData();
                    }
                }, 300));
        }
        
        const filterJenisKelamin = document.getElementById('filterJenisKelamin');
        if (filterJenisKelamin) {
            filterJenisKelamin.addEventListener('change', () => {
                this.pendudukPage = 1;
                if (typeof this.loadPendudukData === 'function') {
                    this.loadPendudukData();
                }
            });
        }
        
        const filterUsia = document.getElementById('filterUsia');
        if (filterUsia) {
            filterUsia.addEventListener('change', () => {
                this.pendudukPage = 1;
                if (typeof this.loadPendudukData === 'function') {
                    this.loadPendudukData();
                }
            });
        }
        
        const searchKeluarga = document.getElementById('searchKeluarga');
        if (searchKeluarga) {
            searchKeluarga.addEventListener('input', 
                debounce(() => {
                    this.keluargaPage = 1;
                    if (typeof this.loadKeluargaData === 'function') {
                        this.loadKeluargaData();
                    }
                }, 300));
        }

        // Form submissions - with null checks
        const formPenduduk = document.getElementById('formPenduduk');
        if (formPenduduk) {
            formPenduduk.addEventListener('submit', (e) => {
                e.preventDefault();
                this.savePenduduk();
            });
        }

        const formKeluarga = document.getElementById('formKeluarga');
        if (formKeluarga) {
            formKeluarga.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveKeluarga();
            });
        }

        // NIK validation - with null check
        const nikPenduduk = document.getElementById('nikPenduduk');
        if (nikPenduduk) {
            nikPenduduk.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 16);
            });
        }
        
        // Auto calculate age from birth date
        const setupAgeCalculation = () => {
            const tanggalLahirInput = document.querySelector('#formPenduduk input[name="tanggal_lahir"]');
            if (tanggalLahirInput) {
                tanggalLahirInput.addEventListener('change', (e) => {
                    if (e.target.value) {
                        const birthDate = new Date(e.target.value);
                        const today = new Date();
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                    }
                });
            }
        };
        
        setupAgeCalculation();
    },

    switchTab(tabName) {
        if (!tabName) {
            return;
        }
        
        // Update tab buttons
        document.querySelectorAll('.kependudukan-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTabBtn = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTabBtn) {
            activeTabBtn.classList.add('active');
        }

        // Update tab content
        document.querySelectorAll('.kependudukan-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const tabContentId = `tab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`;
        const activeTabContent = document.getElementById(tabContentId);
        if (activeTabContent) {
            activeTabContent.classList.add('active');
        }

        this.currentTab = tabName;

        // Lazy loading - only load data when tab is activated for the first time or when switching
        if (tabName === 'penduduk') {
            if (!this.pendudukLoaded) {
                this.pendudukPage = 1;
                if (typeof this.loadPendudukData === 'function') {
                    this.loadPendudukData();
                    this.pendudukLoaded = true;
                }
            }
        } else if (tabName === 'keluarga') {
            if (!this.keluargaLoaded) {
                this.keluargaPage = 1;
                if (typeof this.loadKeluargaData === 'function') {
                    this.loadKeluargaData();
                    this.keluargaLoaded = true;
                }
            }
        }
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

    renderPagination(containerId, pagination) {
        const container = document.getElementById(containerId);
        
        if (!pagination || pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        const tabType = containerId.includes('penduduk') ? 'penduduk' : 'keluarga';
        
        // Previous button
        html += `<button class="pagination-btn" ${pagination.current_page <= 1 ? 'disabled' : ''} 
                 onclick="Kependudukan.changePage('${tabType}', ${pagination.current_page - 1})">
                 <i class="fas fa-chevron-left"></i>
                 </button>`;

        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.current_page) {
                html += `<button class="pagination-btn active">${i}</button>`;
            } else if (i === 1 || i === pagination.total_pages || 
                      (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                html += `<button class="pagination-btn" onclick="Kependudukan.changePage('${tabType}', ${i})">${i}</button>`;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                html += `<span>...</span>`;
            }
        }

        // Next button
        html += `<button class="pagination-btn" ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''} 
                 onclick="Kependudukan.changePage('${tabType}', ${pagination.current_page + 1})">
                 <i class="fas fa-chevron-right"></i>
                 </button>`;

        container.innerHTML = html;
    },
    
    updateTableInfo(infoId, pagination) {
        const info = document.getElementById(infoId);
        if (!info) return;
        
        const start = pagination.total_items > 0 ? ((pagination.current_page - 1) * pagination.items_per_page) + 1 : 0;
        const end = Math.min(pagination.current_page * pagination.items_per_page, pagination.total_items);
        
        info.textContent = `Menampilkan ${start} - ${end} dari ${pagination.total_items} data`;
    },

    changePage(tabType, page) {
        if (tabType === 'penduduk') {
            this.pendudukPage = page;
            if (typeof this.loadPendudukData === 'function') {
                this.loadPendudukData();
            }
        } else {
            this.keluargaPage = page;
            if (typeof this.loadKeluargaData === 'function') {
                this.loadKeluargaData();
            }
        }
    },
    
    changeItemsPerPage(type, value) {
        this.itemsPerPage = parseInt(value);
        if (type === 'penduduk') {
            this.pendudukPage = 1;
            if (typeof this.loadPendudukData === 'function') {
                this.loadPendudukData();
            }
        } else {
            this.keluargaPage = 1;
            if (typeof this.loadKeluargaData === 'function') {
                this.loadKeluargaData();
            }
        }
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.Kependudukan) {
        window.Kependudukan.init();
    }
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Kependudukan) {
            window.Kependudukan.init();
        }
    }, 100);
}

// Global init function for AJAX calls
window.initKependudukan = function() {
    if (window.Kependudukan) {
        window.Kependudukan.init();
    }
};