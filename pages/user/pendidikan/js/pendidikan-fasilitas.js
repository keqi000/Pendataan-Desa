// Pendidikan Fasilitas Functions
if (typeof window.Pendidikan !== 'undefined') {
    
    // Extend Pendidikan with Fasilitas functions
    Object.assign(window.Pendidikan, {
        
        async loadFasilitasData() {
            try {
                if (typeof showContentLoader === 'function') showContentLoader();
                
                const search = document.getElementById('searchFasilitas').value;
                const jenis = document.getElementById('filterJenis').value;
                const kondisi = document.getElementById('filterKondisi').value;

                const params = new URLSearchParams({
                    action: 'get_fasilitas',
                    search: search,
                    jenis: jenis,
                    kondisi: kondisi
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/pendidikan/php/pendidikan-view.php?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    this.renderFasilitasCards(data.data);
                } else {
                    this.renderEmptyCards('Tidak ada data fasilitas pendidikan');
                }
            } catch (error) {
                this.renderEmptyCards('Gagal memuat data fasilitas');
            } finally {
                if (typeof hideContentLoader === 'function') hideContentLoader();
            }
        },

        renderFasilitasCards(data) {
            const container = document.getElementById('fasilitasCards');
            
            if (data.length === 0) {
                this.renderEmptyCards('Tidak ada data fasilitas pendidikan');
                return;
            }

            container.innerHTML = data.map(item => `
                <div class="pendidikan-card">
                    <div class="pendidikan-card-header">
                        <div>
                            <h4 class="pendidikan-card-title">${item.nama_fasilitas}</h4>
                            <p class="pendidikan-card-subtitle">${item.jenis_pendidikan}</p>
                        </div>
                        <div class="pendidikan-card-actions">
                            <button class="action-btn btn-edit" onclick="editFasilitas(${item.id_fasilitas})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-view" onclick="viewLocation(${item.koordinat_latitude}, ${item.koordinat_longitude})" title="Lihat Lokasi">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteFasilitas(${item.id_fasilitas})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="pendidikan-card-content">
                        <div class="pendidikan-card-item">
                            <span class="pendidikan-card-label">Kapasitas Siswa</span>
                            <span class="pendidikan-card-value">${item.kapasitas_siswa || 0} siswa</span>
                        </div>
                        <div class="pendidikan-card-item">
                            <span class="pendidikan-card-label">Jumlah Guru</span>
                            <span class="pendidikan-card-value">${item.jumlah_guru || 0} guru</span>
                        </div>
                        <div class="pendidikan-card-item">
                            <span class="pendidikan-card-label">Kondisi Bangunan</span>
                            <span class="status-badge status-${item.kondisi_bangunan.toLowerCase()}">${item.kondisi_bangunan}</span>
                        </div>
                        <div class="pendidikan-card-item">
                            <span class="pendidikan-card-label">Alamat</span>
                            <span class="pendidikan-card-value">${item.alamat_fasilitas || '-'}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        },

        async loadStatistics() {
            try {
                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/pendidikan/php/pendidikan-view.php?action=get_stats`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success && data.data) {
                    const stats = data.data;
                    document.getElementById('totalPAUD').textContent = stats.PAUD || 0;
                    document.getElementById('totalTK').textContent = stats.TK || 0;
                    document.getElementById('totalSD').textContent = stats.SD || 0;
                    document.getElementById('totalSMP').textContent = stats.SMP || 0;
                    document.getElementById('totalSMA').textContent = stats.SMA || 0;
                    document.getElementById('totalSMK').textContent = stats.SMK || 0;
                }
            } catch (error) {
                // Statistics loading failed silently
            }
        },

        async saveFasilitas() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formFasilitas'));
                const action = this.editingId ? 'update_fasilitas' : 'create_fasilitas';
                formData.append('action', action);
                
                if (this.editingId) {
                    formData.append('id_fasilitas', this.editingId);
                }

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/pendidikan/php/pendidikan-view.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingId ? 'Fasilitas berhasil diupdate' : 'Fasilitas berhasil ditambahkan', 'success');
                    this.closeFasilitasModal();
                    this.loadFasilitasData();
                    this.loadStatistics();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        openFasilitasModal(data = null) {
            const modal = document.getElementById('modalFasilitas');
            const form = document.getElementById('formFasilitas');
            const title = document.getElementById('modalFasilitasTitle');

            form.reset();
            this.editingId = null;

            if (data) {
                title.textContent = 'Edit Fasilitas Pendidikan';
                this.editingId = data.id_fasilitas;
                
                // Populate form
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key] || '';
                    }
                });
            } else {
                title.textContent = 'Tambah Fasilitas Pendidikan';
            }

            modal.classList.add('show');
        },

        closeFasilitasModal() {
            document.getElementById('modalFasilitas').classList.remove('show');
            this.editingId = null;
        }
    });
}

// Global functions for Fasilitas
window.openFasilitasModal = function() {
    Pendidikan.openFasilitasModal();
};

window.closeFasilitasModal = function() {
    Pendidikan.closeFasilitasModal();
};

window.editFasilitas = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/pendidikan/php/pendidikan-view.php?action=get_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Pendidikan.openFasilitasModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail fasilitas', 'error');
    }
};

window.deleteFasilitas = async function(id) {
    if (!confirm('Yakin ingin menghapus fasilitas ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_fasilitas');
        formData.append('id_fasilitas', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/pendidikan/php/pendidikan-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Fasilitas berhasil dihapus', 'success');
            Pendidikan.loadFasilitasData();
            Pendidikan.loadStatistics();
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