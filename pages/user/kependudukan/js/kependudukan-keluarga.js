// Kependudukan Keluarga Tab Functions
if (typeof window.Kependudukan !== 'undefined') {
    
    // Extend Kependudukan with Keluarga functions
    Object.assign(window.Kependudukan, {
        
        async loadKeluargaData() {
            try {
                if (typeof showContentLoader === 'function') showContentLoader();
                
                const search = document.getElementById('searchKeluarga').value;

                const params = new URLSearchParams({
                    action: 'get_keluarga',
                    page: this.keluargaPage,
                    limit: this.itemsPerPage,
                    search: search
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?${params}`, {
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
                    this.renderKeluargaTable(data.data.data);
                    this.renderPagination('keluargaPagination', data.data.pagination);
                    this.updateTableInfo('keluargaInfo', data.data.pagination);
                } else {
                    this.renderEmptyTable('keluargaTableBody', 5, data.message || 'Tidak ada data keluarga');
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

        setupKepalaKeluargaAutocomplete() {
            const input = document.getElementById('kepalaKeluargaInput');
            const dropdown = document.getElementById('kepalaKeluargaDropdown');
            const hiddenInput = document.getElementById('nikKepalaKeluarga');
            
            if (!input) return;
            
            let timeout;
            
            input.addEventListener('input', (e) => {
                clearTimeout(timeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    dropdown.style.display = 'none';
                    hiddenInput.value = '';
                    return;
                }
                
                timeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?action=get_penduduk_options&search=${encodeURIComponent(query)}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();
                        
                        if (data.success && data.data.length > 0) {
                            dropdown.innerHTML = data.data.map(penduduk => 
                                `<div class="autocomplete-item" data-nik="${penduduk.nik}" data-nama="${penduduk.nama_lengkap}">
                                    <strong>${penduduk.nama_lengkap}</strong><br>
                                    <small>NIK: ${penduduk.nik}</small>
                                </div>`
                            ).join('');
                            dropdown.style.display = 'block';
                        } else {
                            dropdown.innerHTML = '<div class="autocomplete-item no-results">Tidak ada data ditemukan</div>';
                            dropdown.style.display = 'block';
                        }
                    } catch (error) {
                        // Autocomplete search failed silently
                    }
                }, 300);
            });
            
            dropdown.addEventListener('click', (e) => {
                const item = e.target.closest('.autocomplete-item');
                if (item && !item.classList.contains('no-results')) {
                    const nik = item.dataset.nik;
                    const nama = item.dataset.nama;
                    
                    input.value = nama;
                    input.dataset.selectedName = nama; // Store selected name for validation
                    hiddenInput.value = nik;
                    dropdown.style.display = 'none';
                    input.setCustomValidity('');
                }
            });
            
            input.addEventListener('input', () => {
                // Clear any previous validation errors when user starts typing
                input.setCustomValidity('');
                // Clear hidden input if user modifies the selected value
                const selectedName = input.dataset.selectedName || '';
                if (input.value !== selectedName) {
                    hiddenInput.value = '';
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
        },

        async saveKeluarga() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                // Validasi kepala keluarga hanya untuk create, tidak untuk edit
                if (!this.editingId) {
                    const kepalaKeluargaInput = document.getElementById('kepalaKeluargaInput');
                    const nikKepalaKeluarga = document.getElementById('nikKepalaKeluarga');
                    const selectedName = kepalaKeluargaInput.dataset.selectedName || '';
                    
                    if (!nikKepalaKeluarga.value || kepalaKeluargaInput.value !== selectedName) {
                        kepalaKeluargaInput.setCustomValidity('Pilih nama kepala keluarga sesuai dengan data penduduk yang ada');
                        kepalaKeluargaInput.reportValidity();
                        this.isSubmitting = false;
                        return;
                    }
                }
                
                const formData = new FormData(document.getElementById('formKeluarga'));
                const nikKepalaKeluarga = document.getElementById('nikKepalaKeluarga');
                if (nikKepalaKeluarga.value) {
                    formData.append('nik_kepala_keluarga', nikKepalaKeluarga.value);
                }
                formData.append('action', this.editingId ? 'update_keluarga' : 'create_keluarga');
                
                if (this.editingId) {
                    formData.append('id_keluarga', this.editingId);
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
                    if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data keluarga berhasil diupdate' : 'Data keluarga berhasil ditambahkan', 'success');
                    this.closeKeluargaModal();
                    this.loadKeluargaData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
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
                
                // Set kepala keluarga data for edit
                const kepalaInput = document.getElementById('kepalaKeluargaInput');
                const nikKepala = document.getElementById('nikKepalaKeluarga');
                if (kepalaInput && nikKepala && data.nama_kepala_keluarga) {
                    kepalaInput.value = data.nama_kepala_keluarga;
                    kepalaInput.dataset.selectedName = data.nama_kepala_keluarga;
                    nikKepala.value = data.nik_kepala_keluarga || '';
                }
            } else {
                title.textContent = 'Tambah Keluarga';
            }

            modal.classList.add('show');
        },

        closeKeluargaModal() {
            document.getElementById('modalKeluarga').classList.remove('show');
            this.editingId = null;
        },

        setupKeluargaImportFunctionality() {
            const keluargaUploadArea = document.getElementById('importKeluargaUploadArea');
            const keluargaFileInput = document.getElementById('importKeluargaFile');
            
            if (keluargaUploadArea && !keluargaUploadArea.hasAttribute('data-listeners-attached')) {
                keluargaUploadArea.setAttribute('data-listeners-attached', 'true');
                
                keluargaUploadArea.addEventListener('click', () => {
                    keluargaFileInput.click();
                });
                
                // Drag and drop functionality for keluarga
                keluargaUploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    keluargaUploadArea.style.borderColor = '#3F72AF';
                    keluargaUploadArea.style.backgroundColor = 'rgba(63, 114, 175, 0.1)';
                });
                
                keluargaUploadArea.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    keluargaUploadArea.style.borderColor = '';
                    keluargaUploadArea.style.backgroundColor = '';
                });
                
                keluargaUploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    keluargaUploadArea.style.borderColor = '';
                    keluargaUploadArea.style.backgroundColor = '';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        this.handleKeluargaImportFile(files[0]);
                    }
                });
                
                keluargaFileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        this.handleKeluargaImportFile(e.target.files[0]);
                    }
                });
            }
        },
        
        handleKeluargaImportFile(file) {
            const uploadArea = document.getElementById('importKeluargaUploadArea');
            const fileInput = document.getElementById('importKeluargaFile');
            
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            
            uploadArea.innerHTML = `
                <i class="fas fa-file-excel"></i>
                <p><strong>${file.name}</strong></p>
                <small>File siap untuk diimport</small>
            `;
        },
        
        async processKeluargaImport() {
            const fileInput = document.getElementById('importKeluargaFile');
            
            if (!fileInput.files.length) {
                if (typeof showNotification === 'function') showNotification('Pilih file Excel terlebih dahulu', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'import_keluarga');
                formData.append('excel_file', fileInput.files[0]);

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (result.data.imported_count === 0) {
                        let message = `Import 0 dari ${result.data.total_processed || 0} data keluarga.`;
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
                        let message = `Berhasil import ${result.data.imported_count} data keluarga`;
                        if (result.data.logs && result.data.logs.length > 0) {
                            message += ` (${result.data.logs.length} gagal)`;
                        }
                        if (typeof showNotification === 'function') showNotification(message, 'success');
                        if (result.data.logs && result.data.logs.length > 0) {
                            console.log('Import warnings:', result.data.logs);
                        }
                    }
                    
                    if (result.data.imported_count > 0) {
                        closeImportKeluargaModal();
                        this.loadKeluargaData();
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

// Global functions for Keluarga
window.openKeluargaModal = function() {
    Kependudukan.openKeluargaModal();
};

window.closeKeluargaModal = function() {
    Kependudukan.closeKeluargaModal();
};

window.editKeluarga = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?action=get_keluarga_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Kependudukan.openKeluargaModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail keluarga', 'error');
    }
};

window.deleteKeluarga = async function(id) {
    if (!confirm('Yakin ingin menghapus data keluarga ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_keluarga');
        formData.append('id_keluarga', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data keluarga berhasil dihapus', 'success');
            Kependudukan.loadKeluargaData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.importKeluargaExcel = function() {
    document.getElementById('modalImportKeluarga').classList.add('show');
    // Setup import functionality when modal opens
    if (window.Kependudukan) {
        window.Kependudukan.setupKeluargaImportFunctionality();
    }
};

window.closeImportKeluargaModal = function() {
    document.getElementById('modalImportKeluarga').classList.remove('show');
    document.getElementById('importKeluargaFile').value = '';
    document.getElementById('importKeluargaUploadArea').innerHTML = `
        <i class="fas fa-file-excel"></i>
        <p>Pilih file Excel atau drag & drop di sini</p>
    `;
};

window.exportKeluargaExcel = function() {
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
    actionInput.value = 'export_keluarga';
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

window.downloadKeluargaTemplate = function() {
    window.open(`${window.BASE_URL || '/Pendataan-desa/'}assets/templates/template_keluarga.xlsx`, '_blank');
};

window.processKeluargaImport = function() {
    Kependudukan.processKeluargaImport();
};

window.viewKeluarga = function(id) {
    openAnggotaKeluargaModal(id);
};

// Family member management functions
window.openAnggotaKeluargaModal = async function(id_keluarga) {
    try {
        // Get keluarga detail
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?action=get_keluarga_detail&id=${id_keluarga}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            const keluarga = data.data;
            document.getElementById('infoKeluarga').textContent = `Keluarga: ${keluarga.nomor_kk}`;
            document.getElementById('infoKepala').textContent = `Kepala Keluarga: ${keluarga.nama_kepala_keluarga}`;
            
            // Store current family ID
            window.currentFamilyId = id_keluarga;
            
            // Load current members
            loadCurrentMembers(id_keluarga);
            
            // Show modal
            document.getElementById('modalAnggotaKeluarga').classList.add('show');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail keluarga', 'error');
    }
};

window.closeAnggotaKeluargaModal = function() {
    document.getElementById('modalAnggotaKeluarga').classList.remove('show');
    document.getElementById('searchAnggota').value = '';
    document.getElementById('pendudukList').innerHTML = '';
};

window.searchPendudukForFamily = async function() {
    const search = document.getElementById('searchAnggota').value;
    
    if (search.length < 2) {
        document.getElementById('pendudukList').innerHTML = '<p>Ketik minimal 2 karakter untuk mencari</p>';
        return;
    }
    
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?action=search_penduduk_for_family&search=${encodeURIComponent(search)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            const list = document.getElementById('pendudukList');
            
            if (data.data.length === 0) {
                list.innerHTML = '<p>Tidak ada data ditemukan</p>';
                return;
            }
            
            list.innerHTML = data.data.map(penduduk => {
                return `
                    <div class="penduduk-item">
                        <div class="penduduk-info">
                            <strong>${penduduk.nama_lengkap}</strong><br>
                            <small>NIK: ${penduduk.nik} | ${penduduk.jenis_kelamin} | ${penduduk.usia} tahun</small>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="addToFamily('${penduduk.nik}')">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                    </div>
                `;
            }).join('');
        } else {
            document.getElementById('pendudukList').innerHTML = '<p>Tidak ada data ditemukan</p>';
        }
    } catch (error) {
        document.getElementById('pendudukList').innerHTML = '<p>Gagal mencari data</p>';
    }
};

window.loadCurrentMembers = async function(id_keluarga) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?action=get_family_members&id_keluarga=${id_keluarga}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            const list = document.getElementById('currentMembersList');
            
            // Get kepala keluarga NIK from keluarga table
            const keluargaResponse = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php?action=get_keluarga_detail&id=${id_keluarga}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const keluargaData = await keluargaResponse.json();
            let kepalaKeluargaNIK = '';
            
            if (keluargaData.success) {
                // Extract NIK from nama_kepala_keluarga or use nik_kepala_keluarga if available
                kepalaKeluargaNIK = keluargaData.data.nik_kepala_keluarga || '';
            }
            
            list.innerHTML = data.data.map(member => {
                // Check if this member is kepala keluarga by NIK, not by name
                const isKepala = member.nik === kepalaKeluargaNIK;
                return `
                    <div class="member-item">
                        <div class="member-info">
                            <strong>${member.nama_lengkap}</strong>
                            ${isKepala ? '<span class="badge badge-primary">Kepala Keluarga</span>' : ''}<br>
                            <small>NIK: ${member.nik} | ${member.jenis_kelamin} | ${member.usia} tahun</small>
                        </div>
                        ${!isKepala ? `<button class="btn btn-sm btn-danger" onclick="removeFromFamily('${member.nik}')">
                            <i class="fas fa-times"></i> Hapus
                        </button>` : '<span class="text-muted">Kepala Keluarga</span>'}
                    </div>
                `;
            }).join('');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat anggota keluarga', 'error');
    }
};

window.addToFamily = async function(nik) {
    try {
        const formData = new FormData();
        formData.append('action', 'add_family_member');
        formData.append('nik', nik);
        formData.append('id_keluarga', window.currentFamilyId);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Anggota keluarga berhasil ditambahkan', 'success');
            loadCurrentMembers(window.currentFamilyId);
            searchPendudukForFamily(); // Refresh available list
            Kependudukan.loadKeluargaData(); // Refresh main table
        } else {
            if (typeof showNotification === 'function') showNotification(result.message, 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menambah anggota keluarga', 'error');
    }
};

window.removeFromFamily = async function(nik) {
    if (!confirm('Yakin ingin mengeluarkan dari keluarga ini?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'remove_family_member');
        formData.append('nik', nik);
        formData.append('id_keluarga', window.currentFamilyId);
        
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/kependudukan/php/kependudukan-functions.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Anggota keluarga berhasil dihapus', 'success');
            loadCurrentMembers(window.currentFamilyId);
            searchPendudukForFamily(); // Refresh available list
            Kependudukan.loadKeluargaData(); // Refresh main table
        } else {
            if (typeof showNotification === 'function') showNotification(result.message, 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus anggota keluarga', 'error');
    }
};