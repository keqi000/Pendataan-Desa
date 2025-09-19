// Infrastruktur Namespace
if (typeof window.Infrastruktur === 'undefined') {
window.Infrastruktur = {
    currentTab: 'jalan',
    editingId: null,

    init() {
        if (!document.querySelector('.infrastruktur-container')) return;
        
        this.setupEventListeners();
        this.loadJalanData();
    },

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.infrastruktur-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Search handlers
        document.getElementById('searchJalan').addEventListener('input', 
            debounce(() => this.loadJalanData(), 300));
        document.getElementById('searchJembatan').addEventListener('input', 
            debounce(() => this.loadJembatanData(), 300));

        // Filter handlers
        document.getElementById('filterKondisiJalan').addEventListener('change', () => this.loadJalanData());
        document.getElementById('filterPermukaan').addEventListener('change', () => this.loadJalanData());
        document.getElementById('filterKondisiJembatan').addEventListener('change', () => this.loadJembatanData());

        // Form submissions
        document.getElementById('formJalan').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveJalan();
        });

        document.getElementById('formJembatan').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveJembatan();
        });
    },

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.infrastruktur-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Update tab content
        document.querySelectorAll('.infrastruktur-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const tabId = tabName.charAt(0).toUpperCase() + tabName.slice(1);
        const tabContent = document.getElementById(`tab${tabId}`);
        if (tabContent) {
            tabContent.classList.add('active');
        }

        this.currentTab = tabName;

        // Load appropriate data
        if (tabName === 'jalan') {
            this.loadJalanData();
        } else if (tabName === 'jembatan') {
            this.loadJembatanData();
        }
    },

    async loadJalanData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchJalan').value;
            const kondisi = document.getElementById('filterKondisiJalan').value;
            const permukaan = document.getElementById('filterPermukaan').value;

            const params = new URLSearchParams({
                endpoint: 'infrastruktur-jalan',
                search: search,
                kondisi: kondisi,
                permukaan: permukaan
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            
            console.log('Infrastruktur Jalan Response status:', response.status);
            const responseText = await response.text();
            console.log('Infrastruktur Jalan Raw response:', responseText);
            
            const data = JSON.parse(responseText);

            if (data.success) {
                this.renderJalanTable(data.data);
            } else {
                this.renderEmptyTable('jalanTableBody', 7, 'Tidak ada data jalan');
            }
        } catch (error) {
            console.error('Error loading jalan data:', error);
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
                        <button class="action-btn btn-view" onclick="viewJalanLocation(${item.koordinat_start_lat}, ${item.koordinat_start_lng}, ${item.koordinat_end_lat}, ${item.koordinat_end_lng})" title="Lihat Lokasi">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteJalan(${item.id_jalan})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    async loadJembatanData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchJembatan').value;
            const kondisi = document.getElementById('filterKondisiJembatan').value;

            const params = new URLSearchParams({
                endpoint: 'infrastruktur-jembatan',
                search: search,
                kondisi: kondisi
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            
            console.log('Infrastruktur Jembatan Response status:', response.status);
            const responseText = await response.text();
            console.log('Infrastruktur Jembatan Raw response:', responseText);
            
            const data = JSON.parse(responseText);

            if (data.success) {
                this.renderJembatanCards(data.data);
            } else {
                this.renderEmptyCards('jembatanCards', 'Tidak ada data jembatan');
            }
        } catch (error) {
            console.error('Error loading jembatan data:', error);
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
                    <div class="infrastruktur-card-item">
                        <span class="infrastruktur-card-label">Kapasitas Beban:</span>
                        <span class="infrastruktur-card-value">${item.kapasitas_beban || '-'} ton</span>
                    </div>
                </div>
            </div>
        `).join('');
    },

    renderEmptyTable(tbodyId, colspan, message) {
        document.getElementById(tbodyId).innerHTML = `
            <tr>
                <td colspan="${colspan}" class="text-center">
                    <i class="fas fa-info-circle"></i> ${message}
                </td>
            </tr>
        `;
    },

    renderEmptyCards(containerId, message) {
        document.getElementById(containerId).innerHTML = `
            <div class="infrastruktur-card-loading">
                <i class="fas fa-info-circle"></i> ${message}
            </div>
        `;
    },

    async saveJalan() {
        try {
            const formData = new FormData(document.getElementById('formJalan'));
            const endpoint = this.editingId ? 'update-jalan' : 'create-jalan';
            
            if (this.editingId) {
                formData.append('id_jalan', this.editingId);
            }

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
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
            console.error('Error saving jalan:', error);
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },

    async saveJembatan() {
        try {
            const formData = new FormData(document.getElementById('formJembatan'));
            const endpoint = this.editingId ? 'update-jembatan' : 'create-jembatan';
            
            if (this.editingId) {
                formData.append('id_jembatan', this.editingId);
            }

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
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
            console.error('Error saving jembatan:', error);
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
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
};
}

// Global functions
window.openJalanModal = function() {
    Infrastruktur.openJalanModal();
};

window.closeJalanModal = function() {
    Infrastruktur.closeJalanModal();
};

window.openJembatanModal = function() {
    Infrastruktur.openJembatanModal();
};

window.closeJembatanModal = function() {
    Infrastruktur.closeJembatanModal();
};

window.editJalan = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=jalan-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Infrastruktur.openJalanModal(data.data);
        }
    } catch (error) {
        console.error('Error loading jalan detail:', error);
    }
};

window.deleteJalan = async function(id) {
    if (!confirm('Yakin ingin menghapus data jalan ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-jalan`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_jalan: id })
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

window.editJembatan = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=jembatan-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Infrastruktur.openJembatanModal(data.data);
        }
    } catch (error) {
        console.error('Error loading jembatan detail:', error);
    }
};

window.deleteJembatan = async function(id) {
    if (!confirm('Yakin ingin menghapus data jembatan ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-jembatan`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_jembatan: id })
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

window.viewJalanLocation = function(startLat, startLng, endLat, endLng) {
    if (!startLat || !startLng) {
        if (typeof showNotification === 'function') showNotification('Koordinat tidak tersedia', 'error');
        return;
    }
    
    let url = `https://www.google.com/maps?q=${startLat},${startLng}`;
    if (endLat && endLng) {
        url = `https://www.google.com/maps/dir/${startLat},${startLng}/${endLat},${endLng}`;
    }
    window.open(url, '_blank');
};

window.viewPhoto = function(photoPath) {
    window.open(`${window.BASE_URL || '/Pendataan-desa/'}uploads/${photoPath}`, '_blank');
};

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.Infrastruktur) window.Infrastruktur.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Infrastruktur) window.Infrastruktur.init();
    }, 100);
}

// Global init function for AJAX calls
window.initInfrastruktur = function() {
    if (window.Infrastruktur) {
        window.Infrastruktur.init();
    }
};