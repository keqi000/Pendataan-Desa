// Ekonomi Pasar Tab Functions
if (typeof window.Ekonomi !== 'undefined') {
    
    // Extend Ekonomi with Pasar functions
    Object.assign(window.Ekonomi, {
        
        async loadPasarData() {
            try {
                // Show loading in cards
                const container = document.getElementById('pasarCards');
                if (container) {
                    container.innerHTML = `
                        <div class="ekonomi-card-loading">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data pasar...
                        </div>
                    `;
                }
                
                const paginationState = this.pagination['pasar'];
                const params = new URLSearchParams({
                    action: 'get_pasar',
                    page: paginationState.page,
                    limit: paginationState.limit,
                    search: paginationState.search
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    console.log(`[Pasar] Search: "${paginationState.search}" | Total: ${data.data.pagination?.total_items} | Rows: ${data.data.data?.length}`);
                    this.renderPasarCards(data.data.data);
                    this.renderPagination('pasarPagination', data.data.pagination, 'pasar');
                    this.updateTableInfo('pasarInfo', data.data.pagination);
                } else {
                    console.error('[Pasar] API error:', data.message);
                    this.renderEmptyCards('pasarCards', 'Tidak ada data pasar');
                }
            } catch (error) {
                console.error('Error loading pasar data:', error);
                this.renderEmptyCards('pasarCards', 'Gagal memuat data pasar');
            }
        },

        renderPasarCards(data) {
            const container = document.getElementById('pasarCards');
            
            if (data.length === 0) {
                this.renderEmptyCards('pasarCards', 'Tidak ada data pasar');
                return;
            }

            container.innerHTML = data.map(item => `
                <div class="ekonomi-card">
                    <div class="ekonomi-card-header">
                        <div>
                            <h4 class="ekonomi-card-title">${item.nama_pasar}</h4>
                            <p class="ekonomi-card-subtitle">Pasar ${item.jenis_pasar}</p>
                        </div>
                        <div class="ekonomi-card-actions">
                            <button class="action-btn btn-edit" onclick="editPasar(${item.id_pasar})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deletePasar(${item.id_pasar})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="ekonomi-card-content">
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Jumlah Pedagang:</span>
                            <span class="ekonomi-card-value">${item.jumlah_pedagang || 0} orang</span>
                        </div>
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Hari Operasional:</span>
                            <span class="ekonomi-card-value">${item.hari_operasional || '-'}</span>
                        </div>
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Kondisi:</span>
                            <span class="status-badge status-${item.kondisi_fasilitas.toLowerCase()}">${item.kondisi_fasilitas}</span>
                        </div>
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Alamat:</span>
                            <span class="ekonomi-card-value">${item.alamat_pasar || '-'}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        },

        async savePasar() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formPasar'));
                const action = this.editingPasarId ? 'update_pasar' : 'create_pasar';
                formData.append('action', action);
                
                if (this.editingPasarId) {
                    formData.append('id_pasar', this.editingPasarId);
                }
                
                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingPasarId ? 'Data pasar berhasil diupdate' : 'Data pasar berhasil ditambahkan', 'success');
                    window.closePasarModal();
                    this.loadPasarData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
        }
    });
}

// Global functions for Pasar
window.openPasarModal = function() {
    Ekonomi.openPasarModal();
};

window.closePasarModal = function() {
    document.getElementById('modalPasar').classList.remove('show');
    Ekonomi.editingPasarId = null;
};

window.editPasar = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?action=get_pasar_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openPasarModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail pasar', 'error');
    }
};

window.deletePasar = async function(id) {
    if (!confirm('Yakin ingin menghapus data pasar ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_pasar');
        formData.append('id_pasar', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data pasar berhasil dihapus', 'success');
            Ekonomi.loadPasarData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};