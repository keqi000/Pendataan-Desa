// Infrastruktur Jembatan Tab Functions
if (typeof window.Infrastruktur !== 'undefined') {
    
    // Extend Infrastruktur with Jembatan functions
    Object.assign(window.Infrastruktur, {
        
        async loadJembatanData() {
            try {
                if (typeof showContentLoader === 'function') showContentLoader();
                
                const search = document.getElementById('searchJembatan').value;
                const kondisi = document.getElementById('filterKondisiJembatan').value;

                const params = new URLSearchParams({
                    action: 'get_jembatan',
                    search: search,
                    kondisi: kondisi
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    this.renderJembatanCards(data.data);
                } else {
                    this.renderEmptyCards('jembatanCards', 'Tidak ada data jembatan');
                }
            } catch (error) {
                this.renderEmptyCards('jembatanCards', 'Gagal memuat data jembatan');
            } finally {
                if (typeof hideContentLoader === 'function') hideContentLoader();
            }
        },

        renderJembatanCards(data) {
            const container = document.getElementById('jembatanCards');
            
            if (data.length === 0) {
                this.renderEmptyCards('jembatanCards', 'Tidak ada data jembatan');
                return;
            }

            container.innerHTML = data.map(item => `
                <div class="infrastruktur-card">
                    <div class="infrastruktur-card-header">
                        <h4 class="infrastruktur-card-title">${item.nama_jembatan}</h4>
                        <div class="infrastruktur-card-actions">
                            <button class="action-btn btn-edit" onclick="editJembatan(${item.id_jembatan})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${item.foto_jembatan ? 
                                `<button class="action-btn btn-photo" onclick="viewPhoto('${item.foto_jembatan}')" title="Lihat Foto">
                                    <i class="fas fa-camera"></i>
                                </button>` : ''
                            }
                            <button class="action-btn btn-view" onclick="viewLocation(${item.koordinat_latitude}, ${item.koordinat_longitude})" title="Lihat Lokasi">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteJembatan(${item.id_jembatan})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="infrastruktur-card-content">
                        <div class="infrastruktur-card-item">
                            <span class="infrastruktur-card-label">Panjang:</span>
                            <span class="infrastruktur-card-value">${item.panjang_jembatan} m</span>
                        </div>
                        <div class="infrastruktur-card-item">
                            <span class="infrastruktur-card-label">Lebar:</span>
                            <span class="infrastruktur-card-value">${item.lebar_jembatan || '-'} m</span>
                        </div>
                        <div class="infrastruktur-card-item">
                            <span class="infrastruktur-card-label">Kondisi:</span>
                            <span class="status-badge status-${item.kondisi_jembatan}">${item.kondisi_jembatan}</span>
                        </div>
                        <div class="infrastruktur-card-item">
                            <span class="infrastruktur-card-label">Material:</span>
                            <span class="infrastruktur-card-value">${item.material_jembatan || '-'}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        },

        async saveJembatan() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formJembatan'));
                const action = this.editingId ? 'update_jembatan' : 'create_jembatan';
                formData.append('action', action);
                
                if (this.editingId) {
                    formData.append('id_jembatan', this.editingId);
                }

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data jembatan berhasil diupdate' : 'Data jembatan berhasil ditambahkan', 'success');
                    this.closeJembatanModal();
                    this.loadJembatanData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        openJembatanModal(data = null) {
            const modal = document.getElementById('modalJembatan');
            const form = document.getElementById('formJembatan');
            const title = document.getElementById('modalJembatanTitle');

            form.reset();
            this.editingId = null;

            if (data) {
                title.textContent = 'Edit Jembatan';
                this.editingId = data.id_jembatan;
                
                // Populate form
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input && input.type !== 'file') {
                        input.value = data[key] || '';
                    }
                });
            } else {
                title.textContent = 'Tambah Jembatan';
            }

            modal.classList.add('show');
        },

        closeJembatanModal() {
            document.getElementById('modalJembatan').classList.remove('show');
            this.editingId = null;
        }
    });
}

// Global functions for Jembatan
window.openJembatanModal = function() {
    Infrastruktur.openJembatanModal();
};

window.closeJembatanModal = function() {
    Infrastruktur.closeJembatanModal();
};

window.editJembatan = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php?action=get_jembatan_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Infrastruktur.openJembatanModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail jembatan', 'error');
    }
};

window.deleteJembatan = async function(id) {
    if (!confirm('Yakin ingin menghapus data jembatan ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_jembatan');
        formData.append('id_jembatan', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data jembatan berhasil dihapus', 'success');
            Infrastruktur.loadJembatanData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.viewLocation = function(lat, lng) {
    if (!lat || !lng) {
        if (typeof showNotification === 'function') showNotification('Koordinat tidak tersedia', 'error');
        return;
    }
    
    const url = `https://www.google.com/maps?q=${lat},${lng}`;
    window.open(url, '_blank');
};

window.viewPhoto = function(photoPath) {
    window.open(`${window.BASE_URL || '/Pendataan-desa/'}uploads/${photoPath}`, '_blank');
};