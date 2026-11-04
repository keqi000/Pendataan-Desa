// Kependudukan Penduduk Tab Functions
if (typeof window.Kependudukan !== 'undefined') {
    
    // Extend Kependudukan with Penduduk functions
    Object.assign(window.Kependudukan, {
        
        async loadPendudukData() {
            try {
                if (typeof showContentLoader === 'function') showContentLoader();
                
                const search = document.getElementById('searchPenduduk')?.value || '';
                const jenisKelamin = document.getElementById('filterJenisKelamin')?.value || '';
                const usia = document.getElementById('filterUsia')?.value || '';

                const params = new URLSearchParams({
                    action: 'get_penduduk',
                    page: this.pendudukPage,
                    limit: this.itemsPerPage,
                    search: search,
                    jenis_kelamin: jenisKelamin,
                    usia: usia
                });

                const url = `${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`;
                const fullUrl = url + '?' + params.toString();

                const response = await fetch(fullUrl, {
                    method: 'GET',
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.renderPendudukTable(data.data.data);
                    this.renderPagination('pendudukPagination', data.data.pagination);
                    this.updateTableInfo('pendudukInfo', data.data.pagination);
                } else {
                    this.renderEmptyTable('pendudukTableBody', 9, data.message || 'Tidak ada data penduduk');
                    this.updateTableInfo('pendudukInfo', {current_page: 1, total_items: 0, items_per_page: this.itemsPerPage});
                }
            } catch (error) {
                this.renderEmptyTable('pendudukTableBody', 9, 'Gagal memuat data');
            } finally {
                if (typeof hideContentLoader === 'function') hideContentLoader();
            }
        },

        renderPendudukTable(data) {
            const tbody = document.getElementById('pendudukTableBody');
            
            if (!tbody) {
                return;
            }
            
            if (!data || data.length === 0) {
                this.renderEmptyTable('pendudukTableBody', 9, 'Tidak ada data penduduk');
                return;
            }

            const html = data.map(item => `
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
                            <button class="action-btn btn-delete" onclick="deletePenduduk('${item.nik}')" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            tbody.innerHTML = html;
        },

        async savePenduduk() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formPenduduk'));
                const action = this.editingId ? 'update_penduduk' : 'create_penduduk';
                formData.append('action', action);
                
                if (this.editingId) {
                    formData.append('nik_original', this.editingId);
                }

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const responseText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    throw new Error('Server returned invalid response');
                }

                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data penduduk berhasil diupdate' : 'Data penduduk berhasil ditambahkan', 'success');
                    this.closePendudukModal();
                    this.loadPendudukData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
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

            // Move modal to main-layout-content
            const mainContent = document.querySelector('.main-layout-content');
            if (mainContent) {
                mainContent.style.position = 'relative';
                if (modal.parentElement) {
                    modal.parentElement.removeChild(modal);
                }
                mainContent.appendChild(modal);
            }

            modal.classList.add('show');
            
            // Setup age calculation for the opened modal
            setTimeout(() => {
                const tanggalLahirInput = document.querySelector('#formPenduduk input[name="tanggal_lahir"]');
                if (tanggalLahirInput && !tanggalLahirInput.hasAttribute('data-age-listener')) {
                    tanggalLahirInput.setAttribute('data-age-listener', 'true');
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
            }, 100);
        },

        closePendudukModal() {
            document.getElementById('modalPenduduk').classList.remove('show');
            this.editingId = null;
        },

        setupImportFunctionality() {
            const uploadArea = document.getElementById('importUploadArea');
            const fileInput = document.getElementById('importFile');
            
            // Prevent duplicate event listeners
            if (!uploadArea.hasAttribute('data-listeners-attached')) {
                uploadArea.setAttribute('data-listeners-attached', 'true');
                
                uploadArea.addEventListener('click', () => {
                    fileInput.click();
                });
                
                // Drag and drop functionality
                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.style.borderColor = '#3F72AF';
                    uploadArea.style.backgroundColor = 'rgba(63, 114, 175, 0.1)';
                });
                
                uploadArea.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.style.borderColor = '';
                    uploadArea.style.backgroundColor = '';
                });
                
                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.style.borderColor = '';
                    uploadArea.style.backgroundColor = '';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        this.handleImportFile(files[0]);
                    }
                });

                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        this.handleImportFile(e.target.files[0]);
                    }
                });
            }
        },

        handleImportFile(file) {
            const uploadArea = document.getElementById('importUploadArea');
            const fileInput = document.getElementById('importFile');
            
            // Create a new FileList and set it to the input
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            
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
                formData.append('action', 'import_penduduk');
                formData.append('excel_file', fileInput.files[0]);

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (result.data.imported_count === 0) {
                        let message = `Import 0 dari ${result.data.total_processed || 0} data penduduk.`;
                        if (result.data.logs && result.data.logs.length > 0) {
                            const firstErrors = result.data.logs.slice(0, 3);
                            message += ` Masalah: ${firstErrors.join('; ')}`;
                            if (result.data.logs.length > 3) {
                                message += ` dan ${result.data.logs.length - 3} error lainnya.`;
                            }
                        }
                        if (typeof showNotification === 'function') {
                            showNotification(message, 'warning');
                        }
                        console.log('Import errors:', result.data.logs);
                    } else {
                        let message = `Berhasil import ${result.data.imported_count} data penduduk`;
                        if (result.data.logs && result.data.logs.length > 0) {
                            message += ` (${result.data.logs.length} gagal)`;
                        }
                        if (typeof showNotification === 'function') showNotification(message, 'success');
                        if (result.data.logs && result.data.logs.length > 0) {
                            console.log('Import warnings:', result.data.logs);
                        }
                    }
                    
                    if (result.data.imported_count > 0) {
                        closeImportModal();
                        this.loadPendudukData();
                    }
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal import data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal import data', 'error');
            }
        }
    });
}

// Global functions for Penduduk
window.openPendudukModal = function() {
    Kependudukan.openPendudukModal();
};

window.closePendudukModal = function() {
    Kependudukan.closePendudukModal();
};

window.editPenduduk = async function(nik) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?action=get_penduduk_detail&nik=${nik}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Kependudukan.openPendudukModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail penduduk', 'error');
    }
};

window.deletePenduduk = async function(nik) {
    if (!confirm('Yakin ingin menghapus data penduduk ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_penduduk');
        formData.append('nik', nik);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data penduduk berhasil dihapus', 'success');
            Kependudukan.loadPendudukData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.importExcel = function() {
    const modal = document.getElementById('modalImport');
    
    // Move modal to main-layout-content
    const mainContent = document.querySelector('.main-layout-content');
    if (mainContent) {
        mainContent.style.position = 'relative';
        if (modal.parentElement) {
            modal.parentElement.removeChild(modal);
        }
        mainContent.appendChild(modal);
    }
    
    modal.classList.add('show');
    // Setup import functionality when modal opens
    if (window.Kependudukan) {
        window.Kependudukan.setupImportFunctionality();
    }
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

window.exportPendudukExcel = function() {
    const id_desa = window.currentDesaId || 1;
    
    // Show loading notification
    if (typeof showNotification === 'function') {
        showNotification('Sedang memproses export...', 'info');
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`;
    form.target = '_blank'; // Open in new tab to prevent page reload
    form.style.display = 'none';
    
    const actionInput = document.createElement('input');
    actionInput.name = 'action';
    actionInput.value = 'export_penduduk';
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

window.downloadTemplate = function() {
    window.open(`${window.BASE_URL || '/Pendataan-desa/'}assets/templates/template_penduduk.xlsx`, '_blank');
};

