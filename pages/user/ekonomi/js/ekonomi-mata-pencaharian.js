// Ekonomi Mata Pencaharian Tab Functions
if (typeof window.Ekonomi !== 'undefined') {
    
    // Extend Ekonomi with Mata Pencaharian functions
    Object.assign(window.Ekonomi, {
        
        async loadMataPencaharianData() {
            try {
                // Show loading in table
                const tbody = document.getElementById('mataPencaharianTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Memuat data...
                            </td>
                        </tr>
                    `;
                }
                
                const paginationState = this.pagination['mata-pencaharian'];
                const params = new URLSearchParams({
                    action: 'get_mata_pencaharian',
                    search: paginationState.search,
                    sektor: paginationState.filters.sektor || '',
                    page: paginationState.page,
                    limit: paginationState.limit
                });

                const url = `${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?${params}`;
                console.log('Loading mata pencaharian data from:', url);
                
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();

                if (data.success) {
                    this.renderMataPencaharianTable(data.data.data);
                    this.renderPagination('mataPencaharianPagination', data.data.pagination, 'mata-pencaharian');
                    this.updateTableInfo('mataPencaharianInfo', data.data.pagination);
                } else {
                    this.renderEmptyTable('mataPencaharianTableBody', 7, data.message || 'Tidak ada data mata pencaharian');
                }
            } catch (error) {
                console.error('Error loading mata pencaharian data:', error);
                this.renderEmptyTable('mataPencaharianTableBody', 7, 'Gagal memuat data: ' + error.message);
            }
        },

        renderMataPencaharianTable(data) {
            const tbody = document.getElementById('mataPencaharianTableBody');
            
            if (data.length === 0) {
                this.renderEmptyTable('mataPencaharianTableBody', 7, 'Tidak ada data mata pencaharian');
                return;
            }

            tbody.innerHTML = data.map(item => `
                <tr>
                    <td>${item.nik}</td>
                    <td>${item.nama_lengkap || '-'}</td>
                    <td>${item.jenis_pekerjaan}</td>
                    <td><span class="status-badge status-${item.sektor_pekerjaan || 'unknown'}">${item.sektor_pekerjaan || '-'}</span></td>
                    <td><span class="currency">${this.formatCurrency(item.penghasilan_perbulan)}</span></td>
                    <td><span class="status-badge status-${(item.status_pekerjaan || 'unknown').replace('_', '-')}">${(item.status_pekerjaan || '-').replace('_', ' ')}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn btn-edit" onclick="editMataPencaharian(${item.id_pencaharian})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteMataPencaharian(${item.id_pencaharian})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        },

        async saveMataPencaharian() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formMataPencaharian'));
                const action = this.editingMataPencaharianId ? 'update_mata_pencaharian' : 'create_mata_pencaharian';
                formData.append('action', action);
                
                if (this.editingMataPencaharianId) {
                    formData.append('id_pencaharian', this.editingMataPencaharianId);
                }
                
                // Convert formatted currency to number
                const penghasilanInput = document.querySelector('input[name="penghasilan_perbulan"]');
                if (penghasilanInput && penghasilanInput.value) {
                    const cleanValue = penghasilanInput.value.replace(/[^0-9]/g, '');
                    formData.set('penghasilan_perbulan', cleanValue);
                }
                
                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingMataPencaharianId ? 'Data mata pencaharian berhasil diupdate' : 'Data mata pencaharian berhasil ditambahkan', 'success');
                    window.closeMataPencaharianModal();
                    this.loadMataPencaharianData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        openMataPencaharianModal(data = null) {
            const modal = document.getElementById('modalMataPencaharian');
            const form = document.getElementById('formMataPencaharian');
            const title = document.getElementById('modalMataPencaharianTitle');

            form.reset();
            this.editingMataPencaharianId = null;

            if (data) {
                title.textContent = 'Edit Mata Pencaharian';
                this.editingMataPencaharianId = data.id_pencaharian;
                
                // Populate form
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (key === 'penghasilan_perbulan' && data[key]) {
                            // Format currency for display
                            input.value = parseInt(data[key]).toLocaleString('id-ID');
                        } else {
                            input.value = data[key] || '';
                        }
                    }
                });
            } else {
                title.textContent = 'Tambah Mata Pencaharian';
            }

            modal.classList.add('show');
        }
    });
}

// Global functions for Mata Pencaharian
window.closeMataPencaharianModal = function() {
    document.getElementById('modalMataPencaharian').classList.remove('show');
    Ekonomi.editingMataPencaharianId = null;
};

window.formatCurrencyInput = function(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        value = parseInt(value).toLocaleString('id-ID');
    }
    input.value = value;
};

window.editMataPencaharian = async function(id) {
    try {
        const url = `${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?action=get_mata_pencaharian_detail&id=${id}`;
        
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            const itemData = Array.isArray(data.data) ? data.data[0] : data.data;
            
            if (itemData) {
                Ekonomi.openMataPencaharianModal(itemData);
            } else {
                if (typeof showNotification === 'function') showNotification('Data tidak ditemukan', 'error');
            }
        } else {
            if (typeof showNotification === 'function') showNotification('Gagal memuat detail mata pencaharian', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail mata pencaharian', 'error');
    }
};

window.deleteMataPencaharian = async function(id) {
    if (!confirm('Yakin ingin menghapus data mata pencaharian ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_mata_pencaharian');
        formData.append('id_pencaharian', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data mata pencaharian berhasil dihapus', 'success');
            Ekonomi.loadMataPencaharianData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};
// Import functions
window.openImportMataPencaharianModal = function() {
    document.getElementById('modalImportMataPencaharian').classList.add('show');
    setupMataPencaharianImport();
};

window.closeImportMataPencaharianModal = function() {
    document.getElementById('modalImportMataPencaharian').classList.remove('show');
    const fileInput = document.getElementById('importMataPencaharianFile');
    const uploadArea = document.getElementById('importMataPencaharianUploadArea');
    if (fileInput) fileInput.value = '';
    if (uploadArea) uploadArea.innerHTML = '<i class="fas fa-file-excel"></i><p>Pilih file Excel atau drag & drop di sini</p>';
};

window.downloadMataPencaharianData = function() {
    const id_desa = window.currentDesaId || 1;
    
    // Show loading notification
    if (typeof showNotification === 'function') {
        showNotification('Sedang memproses export...', 'info');
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-functions.php`;
    form.target = '_blank'; // Open in new tab to prevent page reload
    form.style.display = 'none';
    
    const actionInput = document.createElement('input');
    actionInput.name = 'action';
    actionInput.value = 'export_mata_pencaharian';
    form.appendChild(actionInput);
    
    const desaInput = document.createElement('input');
    desaInput.name = 'id_desa';
    desaInput.value = id_desa;
    form.appendChild(desaInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Hide loading after short delay
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification('Export selesai', 'success');
        }
    }, 2000);
};

window.processMataPencaharianImport = async function() {
    const fileInput = document.getElementById('importMataPencaharianFile');
    if (!fileInput.files[0]) {
        if (typeof showNotification === 'function') showNotification('Pilih file terlebih dahulu', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'import_mata_pencaharian');
    formData.append('excel_file', fileInput.files[0]);
    
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-functions.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data berhasil diimport', 'success');
            closeImportMataPencaharianModal();
            Ekonomi.loadMataPencaharianData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal import data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal import data', 'error');
    }
};

function setupMataPencaharianImport() {
    const uploadArea = document.getElementById('importMataPencaharianUploadArea');
    const fileInput = document.getElementById('importMataPencaharianFile');
    
    if (!uploadArea || !fileInput) return;
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            uploadArea.innerHTML = `<i class="fas fa-file-excel" style="color: #28a745;"></i><p><strong>${e.target.files[0].name}</strong></p><small>File siap untuk diimport</small>`;
        }
    });
}