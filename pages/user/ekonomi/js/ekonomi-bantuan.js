// Ekonomi Bantuan Sosial Tab Functions
if (typeof window.Ekonomi !== 'undefined') {
    
    // Extend Ekonomi with Bantuan functions
    Object.assign(window.Ekonomi, {
        
        async loadBantuanData() {
            try {
                // Show loading in table
                const tbody = document.getElementById('bantuanTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Memuat data...
                            </td>
                        </tr>
                    `;
                }
                
                const paginationState = this.pagination['bantuan-sosial'];
                const params = new URLSearchParams({
                    action: 'get_bantuan_sosial',
                    search: paginationState.search
                });

                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();

                if (data.success) {
                    console.log(`[Bantuan] Search: "${paginationState.search}" | Rows: ${data.data?.length}`);
                    this.renderBantuanTable(data.data);
                } else {
                    console.error('[Bantuan] API error:', data.message);
                    this.renderEmptyTable('bantuanTableBody', 7, 'Tidak ada data bantuan sosial');
                }
            } catch (error) {
                console.error('Error loading bantuan data:', error);
                this.renderEmptyTable('bantuanTableBody', 7, 'Gagal memuat data');
            }
        },

        renderBantuanTable(data) {
            const tbody = document.getElementById('bantuanTableBody');
            
            if (data.length === 0) {
                this.renderEmptyTable('bantuanTableBody', 7, 'Tidak ada data bantuan sosial');
                return;
            }

            tbody.innerHTML = data.map(item => `
                <tr>
                    <td>${item.nik}</td>
                    <td>${item.nama_lengkap || '-'}</td>
                    <td>${item.jenis_bantuan}</td>
                    <td><span class="currency">${this.formatCurrency(item.nominal_bantuan)}</span></td>
                    <td>${item.periode_bantuan || '-'}</td>
                    <td><span class="status-badge status-${(item.status_penerima || 'aktif').replace('_', '-')}">${(item.status_penerima || 'aktif').replace('_', ' ')}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn btn-edit" onclick="editBantuan(${item.id_warga_miskin})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteBantuan(${item.id_warga_miskin})" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        },

        async saveBantuan() {
            if (this.isSubmitting) return;
            
            // Validate required fields
            const searchInput = document.getElementById('searchPendudukBantuan');
            const hiddenInput = document.getElementById('selectedNikBantuan');
            
            if (!hiddenInput.value) {
                searchInput.setCustomValidity('Pilih penduduk dari daftar autocomplete');
                searchInput.reportValidity();
                return;
            }
            
            this.isSubmitting = true;
            
            try {
                const formData = new FormData(document.getElementById('formBantuan'));
                const action = this.editingBantuanId ? 'update_bantuan' : 'create_bantuan';
                formData.append('action', action);
                
                if (this.editingBantuanId) {
                    formData.append('id_warga_miskin', this.editingBantuanId);
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
                    if (typeof showNotification === 'function') showNotification(this.editingBantuanId ? 'Data bantuan sosial berhasil diupdate' : 'Data bantuan sosial berhasil ditambahkan', 'success');
                    window.closeBantuanModal();
                    this.loadBantuanData();
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

// Global functions for Bantuan Sosial
window.openBantuanModal = function() {
    Ekonomi.openBantuanModal();
};

window.closeBantuanModal = function() {
    document.getElementById('modalBantuan').classList.remove('show');
    Ekonomi.editingBantuanId = null;
};

window.editBantuan = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php?action=get_bantuan_detail&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openBantuanModal(data.data);
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal memuat detail bantuan', 'error');
    }
};

window.deleteBantuan = async function(id) {
    if (!confirm('Yakin ingin menghapus data bantuan ini?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_bantuan');
        formData.append('id_warga_miskin', id);

        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/ekonomi/php/ekonomi-view.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data bantuan sosial berhasil dihapus', 'success');
            Ekonomi.loadBantuanData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};