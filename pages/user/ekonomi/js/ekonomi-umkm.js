// Ekonomi UMKM Tab Functions
if (typeof window.Ekonomi !== 'undefined') {
    
    // Extend Ekonomi with UMKM functions
    Object.assign(window.Ekonomi, {
        
        async loadUmkmData() {
            try {
                // Show loading in cards
                const container = document.getElementById('umkmCards');
                if (container) {
                    container.innerHTML = `
                        <div class="ekonomi-card-loading">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data UMKM...
                        </div>
                    `;
                }
                
                const paginationState = this.pagination['umkm'];
                const params = new URLSearchParams({
                    action: 'get_umkm',
                    search: paginationState.search,
                    page: paginationState.page,
                    limit: paginationState.limit
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    this.renderUmkmCards(data.data.data);
                    this.renderPagination('umkmPagination', data.data.pagination, 'umkm');
                    this.updateTableInfo('umkmInfo', data.data.pagination);
                } else {
                    this.renderEmptyCards('umkmCards', 'Tidak ada data UMKM');
                }
            } catch (error) {
                console.error('Error loading UMKM data:', error);
                this.renderEmptyCards('umkmCards', 'Gagal memuat data UMKM');
            }
        },

        renderUmkmCards(data) {
            const container = document.getElementById('umkmCards');
            
            if (data.length === 0) {
                this.renderEmptyCards('umkmCards', 'Tidak ada data UMKM');
                return;
            }

            container.innerHTML = data.map(item => `
                <div class="ekonomi-card">
                    <div class="ekonomi-card-header">
                        <div>
                            <h4 class="ekonomi-card-title">${item.nama_usaha}</h4>
                            <p class="ekonomi-card-subtitle">${item.jenis_usaha || 'Jenis usaha tidak disebutkan'}</p>
                        </div>
                        <div class="ekonomi-card-actions">
                            <button class="action-btn btn-edit" onclick="editUmkm(${item.id_umkm})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteUmkm(${item.id_umkm})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="ekonomi-card-content">
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Pemilik:</span>
                            <span class="ekonomi-card-value">${item.nama_pemilik || '-'}</span>
                        </div>
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Modal:</span>
                            <span class="ekonomi-card-value currency">${this.formatCurrency(item.modal_usaha)}</span>
                        </div>
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Omzet/Bulan:</span>
                            <span class="ekonomi-card-value currency">${this.formatCurrency(item.omzet_perbulan)}</span>
                        </div>
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Karyawan:</span>
                            <span class="ekonomi-card-value">${item.jumlah_karyawan || 0} orang</span>
                        </div>
                        <div class="ekonomi-card-item">
                            <span class="ekonomi-card-label">Status Izin:</span>
                            <span class="status-badge status-${item.status_izin.toLowerCase().replace(' ', '-')}">${item.status_izin}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        },

        async saveUmkm() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formUmkm'));
                const action = this.editingId ? 'update_umkm' : 'create_umkm';
                formData.append('action', action);
                
                if (this.editingId) {
                    formData.append('id_umkm', this.editingId);
                }

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data UMKM berhasil diupdate' : 'Data UMKM berhasil ditambahkan', 'success');
                    this.closeUmkmModal();
                    this.loadUmkmData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        closeUmkmModal() {
            document.getElementById('modalUmkm').classList.remove('show');
            this.editingId = null;
        }
    });
}

// Global functions for UMKM
window.openUmkmModal = function() {
    Ekonomi.openUmkmModal();
};

window.closeUmkmModal = function() {
    Ekonomi.closeUmkmModal();
};

window.editUmkm = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?action=get_umkm_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openUmkmModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail UMKM', 'error');
    }
};

window.deleteUmkm = async function(id) {
    if (!confirm('Yakin ingin menghapus data UMKM ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_umkm');
        formData.append('id_umkm', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data UMKM berhasil dihapus', 'success');
            Ekonomi.loadUmkmData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};