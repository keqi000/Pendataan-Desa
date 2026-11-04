// Ekonomi Pertanian Tab Functions
if (typeof window.Ekonomi !== 'undefined') {
    
    // Extend Ekonomi with Pertanian functions
    Object.assign(window.Ekonomi, {
        
        async loadPertanianData() {
            try {
                // Show loading in table
                const tbody = document.getElementById('pertanianTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Memuat data...
                            </td>
                        </tr>
                    `;
                }
                
                const paginationState = this.pagination['pertanian'];
                const params = new URLSearchParams({
                    action: 'get_pertanian',
                    search: paginationState.search,
                    page: paginationState.page,
                    limit: paginationState.limit
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    console.log(`[Pertanian] Search: "${paginationState.search}" | Total: ${data.data.pagination?.total_items} | Rows: ${data.data.data?.length}`);
                    this.renderPertanianTable(data.data.data);
                    this.renderPagination('pertanianPagination', data.data.pagination, 'pertanian');
                    this.updateTableInfo('pertanianInfo', data.data.pagination);
                } else {
                    console.error('[Pertanian] API error:', data.message);
                    this.renderEmptyTable('pertanianTableBody', 7, 'Tidak ada data pertanian');
                }
            } catch (error) {
                console.error('Error loading pertanian data:', error);
                this.renderEmptyTable('pertanianTableBody', 7, 'Gagal memuat data');
            }
        },

        renderPertanianTable(data) {
            const tbody = document.getElementById('pertanianTableBody');
            
            if (data.length === 0) {
                this.renderEmptyTable('pertanianTableBody', 7, 'Tidak ada data pertanian');
                return;
            }

            tbody.innerHTML = data.map(item => `
                <tr>
                    <td>${item.nik_petani}</td>
                    <td>${item.nama_petani || '-'}</td>
                    <td>${item.jenis_komoditas || '-'}</td>
                    <td>${item.luas_lahan && item.luas_lahan > 0 ? item.luas_lahan + ' Ha' : '-'}</td>
                    <td>${item.jenis_lahan || '-'}</td>
                    <td><span class="currency">${this.formatCurrency(item.pendapatan_per_musim)}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn btn-view" onclick="viewPertanianDetail(${item.id_pertanian})" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn btn-edit" onclick="editPertanian(${item.id_pertanian})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deletePertanian(${item.id_pertanian})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        },

        async savePertanian() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formPertanian'));
                const action = this.editingPertanianId ? 'update_pertanian' : 'create_pertanian';
                formData.append('action', action);
                
                if (this.editingPertanianId) {
                    formData.append('id_pertanian', this.editingPertanianId);
                }
                
                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                
                const responseText = await response.text();
                
                // Extract JSON from response that might have PHP warnings
                const jsonMatch = responseText.match(/\{.*\}/);
                if (!jsonMatch) {
                    throw new Error('No JSON found in response');
                }
                
                const result = JSON.parse(jsonMatch[0]);
                
                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingPertanianId ? 'Data pertanian berhasil diupdate' : 'Data pertanian berhasil ditambahkan', 'success');
                    window.closePertanianModal();
                    this.loadPertanianData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        openPertanianModal(data = null) {
            const modal = document.getElementById('modalPertanian');
            const form = document.getElementById('formPertanian');
            const title = document.getElementById('modalPertanianTitle');

            form.reset();
            this.editingPertanianId = null;

            if (data) {
                title.textContent = 'Edit Data Pertanian';
                this.editingPertanianId = data.id_pertanian;
                
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key] || '';
                    }
                });
                
                if (data.nama_lengkap && data.nik_petani) {
                    const searchInput = document.getElementById('searchPendudukPertanian');
                    const hiddenInput = document.getElementById('selectedNikPertanian');
                    if (searchInput && hiddenInput) {
                        searchInput.value = `${data.nama_lengkap} (${data.nik_petani})`;
                        hiddenInput.value = data.nik_petani;
                    }
                }
            } else {
                title.textContent = 'Tambah Data Pertanian';
            }

            modal.classList.add('show');
        }
    });
}

// Global functions for Pertanian
window.openPertanianModal = function() {
    Ekonomi.openPertanianModal();
};

window.closePertanianModal = function() {
    document.getElementById('modalPertanian').classList.remove('show');
    Ekonomi.editingPertanianId = null;
};

window.viewPertanianDetail = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?action=get_pertanian_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            const item = data.data;
            document.getElementById('detailNikPetani').textContent = item.nik_petani || '-';
            document.getElementById('detailNamaPetani').textContent = item.nama_lengkap || '-';
            document.getElementById('detailJenisKomoditas').textContent = item.jenis_komoditas || '-';
            document.getElementById('detailLuasLahan').textContent = item.luas_lahan ? item.luas_lahan + ' Ha' : '-';
            document.getElementById('detailJenisLahan').textContent = item.jenis_lahan || '-';
            document.getElementById('detailStatusKepemilikan').textContent = item.status_kepemilikan ? item.status_kepemilikan.replace('_', ' ') : '-';
            document.getElementById('detailProduksiPerMusim').textContent = item.produksi_per_musim || '-';
            document.getElementById('detailSatuanProduksi').textContent = item.satuan_produksi || '-';
            document.getElementById('detailPendapatanPerMusim').textContent = item.pendapatan_per_musim ? Ekonomi.formatCurrency(item.pendapatan_per_musim) : '-';
            document.getElementById('detailMusimTanam').textContent = item.musim_tanam ? item.musim_tanam.replace('_', ' ') : '-';
            document.getElementById('detailBantuanPertanian').textContent = item.bantuan_pertanian || '-';
            document.getElementById('detailJenisBantuan').textContent = item.jenis_bantuan || '-';
            document.getElementById('detailTeknologiPertanian').textContent = item.teknologi_pertanian ? item.teknologi_pertanian.replace('_', ' ') : '-';
            document.getElementById('detailIrigasi').textContent = item.irigasi ? item.irigasi.replace('_', ' ') : '-';
            document.getElementById('detailJumlahPelaku').textContent = item.jumlah_pelaku || '-';
            document.getElementById('detailNilaiEkonomi').textContent = item.nilai_ekonomi ? Ekonomi.formatCurrency(item.nilai_ekonomi) : '-';
            document.getElementById('detailLokasi').textContent = item.lokasi || '-';
            document.getElementById('detailDeskripsi').textContent = item.deskripsi || '-';
            
            document.getElementById('modalDetailPertanian').classList.add('show');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail pertanian', 'error');
    }
};

window.closeDetailPertanianModal = function() {
    document.getElementById('modalDetailPertanian').classList.remove('show');
};

window.editPertanian = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?action=get_pertanian_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openPertanianModal(data.data);
        } else {
            if (typeof showNotification === 'function') showNotification('Gagal memuat detail pertanian', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail pertanian', 'error');
    }
};

window.deletePertanian = async function(id) {
    if (!confirm('Yakin ingin menghapus data pertanian ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_pertanian');
        formData.append('id_pertanian', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data pertanian berhasil dihapus', 'success');
            Ekonomi.loadPertanianData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

// Import functions
window.openImportPertanianModal = function() {
    document.getElementById('modalImportPertanian').classList.add('show');
    setupPertanianImport();
};

window.closeImportPertanianModal = function() {
    document.getElementById('modalImportPertanian').classList.remove('show');
    const fileInput = document.getElementById('importPertanianFile');
    const uploadArea = document.getElementById('importPertanianUploadArea');
    if (fileInput) fileInput.value = '';
    if (uploadArea) uploadArea.innerHTML = '<i class="fas fa-file-excel"></i><p>Pilih file Excel atau drag & drop di sini</p>';
};

window.downloadPertanianData = function() {
    const id_desa = window.currentDesaId || 1;
    
    if (typeof showNotification === 'function') {
        showNotification('Sedang memproses export...', 'info');
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-functions.php`;
    form.target = '_blank';
    form.style.display = 'none';
    
    const actionInput = document.createElement('input');
    actionInput.name = 'action';
    actionInput.value = 'export_pertanian';
    form.appendChild(actionInput);
    
    const desaInput = document.createElement('input');
    desaInput.name = 'id_desa';
    desaInput.value = id_desa;
    form.appendChild(desaInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    setTimeout(() => {
        if (typeof showNotification === 'function') {
            showNotification('Export selesai', 'success');
        }
    }, 2000);
};

window.processPertanianImport = async function() {
    const fileInput = document.getElementById('importPertanianFile');
    if (!fileInput.files[0]) {
        if (typeof showNotification === 'function') showNotification('Pilih file terlebih dahulu', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'import_pertanian');
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
            closeImportPertanianModal();
            Ekonomi.loadPertanianData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal import data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal import data', 'error');
    }
};

function setupPertanianImport() {
    const uploadArea = document.getElementById('importPertanianUploadArea');
    const fileInput = document.getElementById('importPertanianFile');
    
    if (!uploadArea || !fileInput) return;
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            uploadArea.innerHTML = `<i class="fas fa-file-excel" style="color: #28a745;"></i><p><strong>${e.target.files[0].name}</strong></p><small>File siap untuk diimport</small>`;
        }
    });
}