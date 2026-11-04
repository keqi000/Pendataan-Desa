// Infrastruktur Jalan Tab Functions
if (typeof window.Infrastruktur !== 'undefined') {
    
    // Extend Infrastruktur with Jalan functions
    Object.assign(window.Infrastruktur, {
        
        async loadJalanData() {
            try {
                if (typeof showContentLoader === 'function') showContentLoader();
                
                const search = document.getElementById('searchJalan').value;
                const kondisi = document.getElementById('filterKondisiJalan').value;
                const permukaan = document.getElementById('filterPermukaan').value;

                const params = new URLSearchParams({
                    action: 'get_jalan',
                    search: search,
                    kondisi: kondisi,
                    permukaan: permukaan
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    this.renderJalanTable(data.data);
                } else {
                    this.renderEmptyTable('jalanTableBody', 7, 'Tidak ada data jalan');
                }
            } catch (error) {
                this.renderEmptyTable('jalanTableBody', 7, 'Gagal memuat data');
            } finally {
                if (typeof hideContentLoader === 'function') hideContentLoader();
            }
        },

        renderJalanTable(data) {
            const tbody = document.getElementById('jalanTableBody');
            
            if (data.length === 0) {
                this.renderEmptyTable('jalanTableBody', 7, 'Tidak ada data jalan');
                return;
            }

            tbody.innerHTML = data.map(item => `
                <tr>
                    <td>${item.nama_jalan}</td>
                    <td>${item.panjang_jalan} m</td>
                    <td>${item.lebar_jalan || '-'} m</td>
                    <td><span class="status-badge status-${item.kondisi_jalan}">${item.kondisi_jalan}</span></td>
                    <td><span class="status-badge status-${item.jenis_permukaan}">${item.jenis_permukaan}</span></td>
                    <td>
                        ${item.foto_jalan ? 
                            `<img src="${window.BASE_URL || '/Pendataan-desa/'}uploads/${item.foto_jalan}" class="photo-thumbnail" onclick="viewPhoto('${item.foto_jalan}')" alt="Foto Jalan">` : 
                            '<span class="text-muted">Tidak ada foto</span>'
                        }
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn btn-edit" onclick="editJalan(${item.id_jalan})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteJalan(${item.id_jalan})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        },

        async saveJalan() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formJalan'));
                const action = this.editingId ? 'update_jalan' : 'create_jalan';
                formData.append('action', action);
                
                if (this.editingId) {
                    formData.append('id_jalan', this.editingId);
                }

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data jalan berhasil diupdate' : 'Data jalan berhasil ditambahkan', 'success');
                    this.closeJalanModal();
                    this.loadJalanData();
                } else {
                    if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
                }
            } catch (error) {
                if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        openJalanModal(data = null) {
            const modal = document.getElementById('modalJalan');
            const form = document.getElementById('formJalan');
            const title = document.getElementById('modalJalanTitle');

            form.reset();
            this.editingId = null;

            if (data) {
                title.textContent = 'Edit Jalan';
                this.editingId = data.id_jalan;
                
                // Populate form
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input && input.type !== 'file') {
                        input.value = data[key] || '';
                    }
                });
            } else {
                title.textContent = 'Tambah Jalan';
            }

            modal.classList.add('show');
        },

        closeJalanModal() {
            document.getElementById('modalJalan').classList.remove('show');
            this.editingId = null;
        }
    });
}

// Global functions for Jalan
window.openJalanModal = function() {
    Infrastruktur.openJalanModal();
};

window.closeJalanModal = function() {
    Infrastruktur.closeJalanModal();
};

window.editJalan = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php?action=get_jalan_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Infrastruktur.openJalanModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail jalan', 'error');
    }
};

window.deleteJalan = async function(id) {
    if (!confirm('Yakin ingin menghapus data jalan ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_jalan');
        formData.append('id_jalan', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/infrastruktur/php/infrastruktur-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data jalan berhasil dihapus', 'success');
            Infrastruktur.loadJalanData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};