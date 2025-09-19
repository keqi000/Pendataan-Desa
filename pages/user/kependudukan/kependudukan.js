// Kependudukan Namespace
if (typeof window.Kependudukan === 'undefined') {
window.Kependudukan = {
    currentTab: 'penduduk',
    currentPage: 1,
    itemsPerPage: 10,
    editingId: null,

    init() {
        if (!document.querySelector('.kependudukan-container')) return;
        
        this.setupEventListeners();
        this.loadPendudukData();
        this.loadKeluargaOptions();
    },

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.kependudukan-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Search and filters
        document.getElementById('searchPenduduk').addEventListener('input', 
            debounce(() => this.loadPendudukData(), 300));
        document.getElementById('filterJenisKelamin').addEventListener('change', () => this.loadPendudukData());
        document.getElementById('filterUsia').addEventListener('change', () => this.loadPendudukData());
        document.getElementById('searchKeluarga').addEventListener('input', 
            debounce(() => this.loadKeluargaData(), 300));

        // Form submissions
        document.getElementById('formPenduduk').addEventListener('submit', (e) => {
            e.preventDefault();
            this.savePenduduk();
        });

        document.getElementById('formKeluarga').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveKeluarga();
        });

        // Import functionality
        document.getElementById('importUploadArea').addEventListener('click', () => {
            document.getElementById('importFile').click();
        });

        document.getElementById('importFile').addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleImportFile(e.target.files[0]);
            }
        });

        // NIK validation
        document.getElementById('nikPenduduk').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 16);
        });
    },

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.kependudukan-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update tab content
        document.querySelectorAll('.kependudukan-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`tab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`).classList.add('active');

        this.currentTab = tabName;

        // Load appropriate data
        if (tabName === 'penduduk') {
            this.loadPendudukData();
        } else if (tabName === 'keluarga') {
            this.loadKeluargaData();
        }
    },

    async loadPendudukData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchPenduduk').value;
            const jenisKelamin = document.getElementById('filterJenisKelamin').value;
            const usia = document.getElementById('filterUsia').value;

            const params = new URLSearchParams({
                endpoint: 'penduduk',
                page: this.currentPage,
                limit: this.itemsPerPage,
                search: search,
                jenis_kelamin: jenisKelamin,
                usia: usia
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderPendudukTable(data.data.data);
                this.renderPagination('pendudukPagination', data.data.pagination);
                this.updateTableInfo('pendudukInfo', data.data.pagination);
            } else {
                this.renderEmptyTable('pendudukTableBody', 9, 'Tidak ada data penduduk');
                this.updateTableInfo('pendudukInfo', {current_page: 1, total_items: 0, items_per_page: this.itemsPerPage});
            }
        } catch (error) {
            console.error('Error loading penduduk data:', error);
            this.renderEmptyTable('pendudukTableBody', 9, 'Gagal memuat data');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderPendudukTable(data) {
        const tbody = document.getElementById('pendudukTableBody');
        
        if (data.length === 0) {
            this.renderEmptyTable('pendudukTableBody', 9, 'Tidak ada data penduduk');
            return;
        }

        tbody.innerHTML = data.map(item => `
            <tr>
                <td>${item.nik}</td>
                <td>${item.nama_lengkap}</td>
                <td>${item.jenis_kelamin}</td>
                <td>${item.usia || '-'}</td>
                <td>${item.agama}</td>
                <td>${item.pendidikan_terakhir}</td>
                <td>${item.pekerjaan || '-'}</td>
                <td>${item.status_pernikahan}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-edit" onclick="editPenduduk('${item.nik}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-view" onclick="viewPenduduk('${item.nik}')" title="Lihat">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deletePenduduk('${item.nik}')" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    async loadKeluargaData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchKeluarga').value;

            const params = new URLSearchParams({
                endpoint: 'keluarga',
                page: this.currentPage,
                limit: this.itemsPerPage,
                search: search
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderKeluargaTable(data.data.data);
                this.renderPagination('keluargaPagination', data.data.pagination);
                this.updateTableInfo('keluargaInfo', data.data.pagination);
            } else {
                this.renderEmptyTable('keluargaTableBody', 5, 'Tidak ada data keluarga');
                this.updateTableInfo('keluargaInfo', {current_page: 1, total_items: 0, items_per_page: this.itemsPerPage});
            }
        } catch (error) {
            console.error('Error loading keluarga data:', error);
            this.renderEmptyTable('keluargaTableBody', 5, 'Gagal memuat data');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderKeluargaTable(data) {
        const tbody = document.getElementById('keluargaTableBody');
        
        if (data.length === 0) {
            this.renderEmptyTable('keluargaTableBody', 5, 'Tidak ada data keluarga');
            return;
        }

        tbody.innerHTML = data.map(item => `
            <tr>
                <td>${item.nomor_kk}</td>
                <td>${item.nama_kepala_keluarga}</td>
                <td>${item.alamat_keluarga || '-'}</td>
                <td>${item.jumlah_anggota || 0}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-edit" onclick="editKeluarga(${item.id_keluarga})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-view" onclick="viewKeluarga(${item.id_keluarga})" title="Lihat Anggota">
                            <i class="fas fa-users"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteKeluarga(${item.id_keluarga})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
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
        
        // Previous button
        html += `<button class="pagination-btn" ${pagination.current_page <= 1 ? 'disabled' : ''} 
                 onclick="Kependudukan.changePage(${pagination.current_page - 1})">
                 <i class="fas fa-chevron-left"></i>
                 </button>`;

        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.current_page) {
                html += `<button class="pagination-btn active">${i}</button>`;
            } else if (i === 1 || i === pagination.total_pages || 
                      (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                html += `<button class="pagination-btn" onclick="Kependudukan.changePage(${i})">${i}</button>`;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                html += `<span>...</span>`;
            }
        }

        // Next button
        html += `<button class="pagination-btn" ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''} 
                 onclick="Kependudukan.changePage(${pagination.current_page + 1})">
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

    changePage(page) {
        this.currentPage = page;
        if (this.currentTab === 'penduduk') {
            this.loadPendudukData();
        } else {
            this.loadKeluargaData();
        }
    },
    
    changeItemsPerPage(type, value) {
        this.itemsPerPage = parseInt(value);
        this.currentPage = 1;
        if (type === 'penduduk') {
            this.loadPendudukData();
        } else {
            this.loadKeluargaData();
        }
    },

    async loadKeluargaOptions() {
        try {
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=keluarga-options`);
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            const data = JSON.parse(responseText);

            const select = document.getElementById('selectKeluarga');
            select.innerHTML = '<option value="">Pilih Keluarga (Opsional)</option>';

            if (data.success && data.data) {
                data.data.forEach(keluarga => {
                    select.innerHTML += `<option value="${keluarga.id_keluarga}">
                        ${keluarga.nomor_kk} - ${keluarga.nama_kepala_keluarga}
                    </option>`;
                });
            }
        } catch (error) {
            console.error('Error loading keluarga options:', error);
        }
    },

    async savePenduduk() {
        try {
            const formData = new FormData(document.getElementById('formPenduduk'));
            const endpoint = this.editingId ? 'update-penduduk' : 'create-penduduk';
            
            if (this.editingId) {
                formData.append('nik_original', this.editingId);
            }

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data penduduk berhasil diupdate' : 'Data penduduk berhasil ditambahkan', 'success');
                this.closePendudukModal();
                this.loadPendudukData();
                this.loadKeluargaOptions();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            console.error('Error saving penduduk:', error);
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },

    async saveKeluarga() {
        try {
            const formData = new FormData(document.getElementById('formKeluarga'));
            const endpoint = this.editingId ? 'update-keluarga' : 'create-keluarga';
            
            if (this.editingId) {
                formData.append('id_keluarga', this.editingId);
            }

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data keluarga berhasil diupdate' : 'Data keluarga berhasil ditambahkan', 'success');
                this.closeKeluargaModal();
                this.loadKeluargaData();
                this.loadKeluargaOptions();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            console.error('Error saving keluarga:', error);
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },

    openPendudukModal(data = null) {
        const modal = document.getElementById('modalPenduduk');
        const form = document.getElementById('formPenduduk');
        const title = document.getElementById('modalPendudukTitle');

        form.reset();
        this.editingId = null;

        if (data) {
            title.textContent = 'Edit Penduduk';
            this.editingId = data.nik;
            
            // Populate form
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
        } else {
            title.textContent = 'Tambah Penduduk';
        }

        modal.classList.add('show');
    },

    closePendudukModal() {
        document.getElementById('modalPenduduk').classList.remove('show');
        this.editingId = null;
    },

    openKeluargaModal(data = null) {
        const modal = document.getElementById('modalKeluarga');
        const form = document.getElementById('formKeluarga');
        const title = document.getElementById('modalKeluargaTitle');

        form.reset();
        this.editingId = null;

        if (data) {
            title.textContent = 'Edit Keluarga';
            this.editingId = data.id_keluarga;
            
            // Populate form
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
        } else {
            title.textContent = 'Tambah Keluarga';
        }

        modal.classList.add('show');
    },

    closeKeluargaModal() {
        document.getElementById('modalKeluarga').classList.remove('show');
        this.editingId = null;
    },

    handleImportFile(file) {
        const uploadArea = document.getElementById('importUploadArea');
        uploadArea.innerHTML = `
            <i class="fas fa-file-excel"></i>
            <p><strong>${file.name}</strong></p>
            <small>File siap untuk diimport</small>
        `;
    },

    async processImport() {
        const fileInput = document.getElementById('importFile');
        
        if (!fileInput.files.length) {
            if (typeof showNotification === 'function') showNotification('Pilih file Excel terlebih dahulu', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('excel_file', fileInput.files[0]);

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=import-penduduk`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (typeof showNotification === 'function') showNotification(`Berhasil import ${result.imported_count} data penduduk`, 'success');
                this.closeImportModal();
                this.loadPendudukData();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal import data', 'error');
            }
        } catch (error) {
            console.error('Error importing data:', error);
            if (typeof showNotification === 'function') showNotification('Gagal import data', 'error');
        }
    }
};
}

// Global functions
window.openPendudukModal = function() {
    Kependudukan.openPendudukModal();
};

window.closePendudukModal = function() {
    Kependudukan.closePendudukModal();
};

window.openKeluargaModal = function() {
    Kependudukan.openKeluargaModal();
};

window.closeKeluargaModal = function() {
    Kependudukan.closeKeluargaModal();
};

window.editPenduduk = async function(nik) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=penduduk-detail&nik=${nik}`);
        const data = await response.json();
        
        if (data.success) {
            Kependudukan.openPendudukModal(data.data);
        }
    } catch (error) {
        console.error('Error loading penduduk detail:', error);
    }
};

window.deletePenduduk = async function(nik) {
    if (!confirm('Yakin ingin menghapus data penduduk ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-penduduk`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nik: nik })
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data penduduk berhasil dihapus', 'success');
            Kependudukan.loadPendudukData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        console.error('Error deleting penduduk:', error);
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.editKeluarga = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=keluarga-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Kependudukan.openKeluargaModal(data.data);
        }
    } catch (error) {
        console.error('Error loading keluarga detail:', error);
    }
};

window.deleteKeluarga = async function(id) {
    if (!confirm('Yakin ingin menghapus data keluarga ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-keluarga`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_keluarga: id })
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data keluarga berhasil dihapus', 'success');
            Kependudukan.loadKeluargaData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        console.error('Error deleting keluarga:', error);
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.importExcel = function() {
    document.getElementById('modalImport').classList.add('show');
};

window.closeImportModal = function() {
    document.getElementById('modalImport').classList.remove('show');
    document.getElementById('importFile').value = '';
    document.getElementById('importUploadArea').innerHTML = `
        <i class="fas fa-file-excel"></i>
        <p>Pilih file Excel atau drag & drop di sini</p>
    `;
};

window.processImport = function() {
    Kependudukan.processImport();
};

window.downloadTemplate = function() {
    window.open(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=download-template&type=penduduk`, '_blank');
};

window.viewPenduduk = function(nik) {
    if (typeof showNotification === 'function') showNotification('Fitur detail penduduk akan segera tersedia', 'info');
};

window.viewKeluarga = function(id) {
    if (typeof showNotification === 'function') showNotification('Fitur detail keluarga akan segera tersedia', 'info');
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
    if (window.Kependudukan) window.Kependudukan.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Kependudukan) window.Kependudukan.init();
    }, 100);
}

// Global init function for AJAX calls
window.initKependudukan = function() {
    if (window.Kependudukan) {
        window.Kependudukan.init();
    }
};