// Laporan Namespace
if (typeof window.Laporan === 'undefined') {
window.Laporan = {
    currentTab: 'buat-laporan',

    init() {
        if (!document.querySelector('.laporan-container')) return;
        
        this.setupEventListeners();
        this.loadRiwayatLaporan();
    },

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.laporan-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Search and filters
        document.getElementById('searchLaporan').addEventListener('input', 
            debounce(() => this.loadRiwayatLaporan(), 300));
        document.getElementById('filterJenis').addEventListener('change', () => this.loadRiwayatLaporan());
        document.getElementById('filterStatus').addEventListener('change', () => this.loadRiwayatLaporan());

        // Form submissions
        document.getElementById('formCustomReport').addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateCustomReport();
        });

        document.getElementById('formTemplate').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveTemplate();
        });
    },

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.laporan-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update tab content
        document.querySelectorAll('.laporan-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const tabId = tabName.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join('');
        document.getElementById(`tab${tabId}`).classList.add('active');

        this.currentTab = tabName;

        // Load appropriate data
        switch(tabName) {
            case 'riwayat-laporan':
                this.loadRiwayatLaporan();
                break;
            case 'template':
                this.loadTemplates();
                break;
        }
    },

    async generateQuickReport(type) {
        try {
            showPageLoader();
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=generate-quick-report`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type })
            });

            const result = await response.json();

            if (result.success) {
                showNotification(`Laporan ${type} berhasil dibuat`, 'success');
                
                // Download the report
                if (result.file_url) {
                    window.open(result.file_url, '_blank');
                }
                
                // Refresh riwayat laporan
                this.loadRiwayatLaporan();
            } else {
                showNotification(result.message || 'Gagal membuat laporan', 'error');
            }
        } catch (error) {
            console.error('Error generating quick report:', error);
            showNotification('Gagal membuat laporan', 'error');
        } finally {
            hidePageLoader();
        }
    },

    async generateCustomReport() {
        try {
            showPageLoader();
            
            const formData = new FormData(document.getElementById('formCustomReport'));
            
            // Get selected categories
            const categories = [];
            document.querySelectorAll('input[name="kategori[]"]:checked').forEach(checkbox => {
                categories.push(checkbox.value);
            });
            
            if (categories.length === 0) {
                showNotification('Pilih minimal satu kategori data', 'error');
                return;
            }
            
            formData.append('kategori', JSON.stringify(categories));

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=generate-custom-report`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Laporan custom berhasil dibuat', 'success');
                
                // Download the report
                if (result.file_url) {
                    window.open(result.file_url, '_blank');
                }
                
                // Reset form and refresh riwayat
                document.getElementById('formCustomReport').reset();
                this.loadRiwayatLaporan();
            } else {
                showNotification(result.message || 'Gagal membuat laporan', 'error');
            }
        } catch (error) {
            console.error('Error generating custom report:', error);
            showNotification('Gagal membuat laporan', 'error');
        } finally {
            hidePageLoader();
        }
    },

    async loadRiwayatLaporan() {
        try {
            showContentLoader();
            
            const search = document.getElementById('searchLaporan').value;
            const jenis = document.getElementById('filterJenis').value;
            const status = document.getElementById('filterStatus').value;

            const params = new URLSearchParams({
                endpoint: 'riwayat-laporan',
                search: search,
                jenis: jenis,
                status: status
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderRiwayatTable(data.data);
            } else {
                this.renderEmptyTable('laporanTableBody', 6, 'Tidak ada riwayat laporan');
            }
        } catch (error) {
            console.error('Error loading riwayat laporan:', error);
            this.renderEmptyTable('laporanTableBody', 6, 'Gagal memuat data');
        } finally {
            hideContentLoader();
        }
    },

    renderRiwayatTable(data) {
        const tbody = document.getElementById('laporanTableBody');
        
        if (data.length === 0) {
            this.renderEmptyTable('laporanTableBody', 6, 'Tidak ada riwayat laporan');
            return;
        }

        tbody.innerHTML = data.map(item => `
            <tr>
                <td>${item.judul_laporan}</td>
                <td><span class="status-badge status-${item.jenis_laporan}">${item.jenis_laporan}</span></td>
                <td>${item.periode_laporan || '-'}</td>
                <td><span class="status-badge status-${item.status_laporan}">${item.status_laporan}</span></td>
                <td>${this.formatDate(item.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        ${item.file_laporan ? 
                            `<button class="action-btn btn-download" onclick="downloadLaporan('${item.file_laporan}')" title="Download">
                                <i class="fas fa-download"></i>
                            </button>` : ''
                        }
                        <button class="action-btn btn-edit" onclick="editLaporan(${item.id_laporan})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteLaporan(${item.id_laporan})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    async loadTemplates() {
        try {
            showContentLoader();
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=laporan-templates`);
            const data = await response.json();

            if (data.success) {
                this.renderTemplateCards(data.data);
            } else {
                this.renderEmptyCards('templateCards', 'Tidak ada template laporan');
            }
        } catch (error) {
            console.error('Error loading templates:', error);
            this.renderEmptyCards('templateCards', 'Gagal memuat template');
        } finally {
            hideContentLoader();
        }
    },

    renderTemplateCards(data) {
        const container = document.getElementById('templateCards');
        
        if (data.length === 0) {
            this.renderEmptyCards('templateCards', 'Tidak ada template laporan');
            return;
        }

        container.innerHTML = data.map(item => `
            <div class="template-card">
                <div class="template-card-header">
                    <h4>${item.nama_template}</h4>
                    <div class="action-buttons">
                        <button class="action-btn btn-edit" onclick="editTemplate(${item.id_template})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteTemplate(${item.id_template})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="template-card-content">
                    <p>Dibuat: ${this.formatDate(item.created_at)}</p>
                    <button class="laporan-btn-secondary" onclick="useTemplate(${item.id_template})">
                        <i class="fas fa-play"></i> Gunakan Template
                    </button>
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
            <div class="template-card-loading">
                <i class="fas fa-info-circle"></i> ${message}
            </div>
        `;
    },

    async saveTemplate() {
        try {
            const formData = new FormData(document.getElementById('formTemplate'));

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=save-template`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Template berhasil disimpan', 'success');
                this.closeTemplateModal();
                this.loadTemplates();
            } else {
                showNotification(result.message || 'Gagal menyimpan template', 'error');
            }
        } catch (error) {
            console.error('Error saving template:', error);
            showNotification('Gagal menyimpan template', 'error');
        }
    },

    openTemplateModal() {
        document.getElementById('modalTemplate').classList.add('show');
    },

    closeTemplateModal() {
        document.getElementById('modalTemplate').classList.remove('show');
        document.getElementById('formTemplate').reset();
    },

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
};
}

// Global functions
window.generateQuickReport = function(type) {
    Laporan.generateQuickReport(type);
};

window.openTemplateModal = function() {
    Laporan.openTemplateModal();
};

window.closeTemplateModal = function() {
    Laporan.closeTemplateModal();
};

window.downloadLaporan = function(fileName) {
    window.open(`${window.BASE_URL || '/Pendataan-desa/'}uploads/laporan/${fileName}`, '_blank');
};

window.editLaporan = async function(id) {
    // Implementation for editing laporan
    showNotification('Fitur edit laporan akan segera tersedia', 'info');
};

window.deleteLaporan = async function(id) {
    if (!confirm('Yakin ingin menghapus laporan ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-laporan`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_laporan: id })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Laporan berhasil dihapus', 'success');
            Laporan.loadRiwayatLaporan();
        } else {
            showNotification(result.message || 'Gagal menghapus laporan', 'error');
        }
    } catch (error) {
        console.error('Error deleting laporan:', error);
        showNotification('Gagal menghapus laporan', 'error');
    }
};

window.editTemplate = async function(id) {
    // Implementation for editing template
    showNotification('Fitur edit template akan segera tersedia', 'info');
};

window.deleteTemplate = async function(id) {
    if (!confirm('Yakin ingin menghapus template ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-template`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_template: id })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Template berhasil dihapus', 'success');
            Laporan.loadTemplates();
        } else {
            showNotification(result.message || 'Gagal menghapus template', 'error');
        }
    } catch (error) {
        console.error('Error deleting template:', error);
        showNotification('Gagal menghapus template', 'error');
    }
};

window.useTemplate = async function(id) {
    // Implementation for using template
    showNotification('Fitur gunakan template akan segera tersedia', 'info');
};

window.exportData = async function(format) {
    try {
        showPageLoader();
        
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=export-data&format=${format}`, {
            method: 'POST'
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `data_desa_${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showNotification(`Data berhasil diexport ke ${format.toUpperCase()}`, 'success');
        } else {
            showNotification('Gagal export data', 'error');
        }
    } catch (error) {
        console.error('Error exporting data:', error);
        showNotification('Gagal export data', 'error');
    } finally {
        hidePageLoader();
    }
};

window.backupDatabase = async function() {
    try {
        showPageLoader();
        
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=backup-database`, {
            method: 'POST'
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `backup_database_${new Date().toISOString().split('T')[0]}.sql`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showNotification('Database berhasil dibackup', 'success');
        } else {
            showNotification('Gagal backup database', 'error');
        }
    } catch (error) {
        console.error('Error backing up database:', error);
        showNotification('Gagal backup database', 'error');
    } finally {
        hidePageLoader();
    }
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
    if (window.Laporan) window.Laporan.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Laporan) window.Laporan.init();
    }, 100);
}

// Global init function for AJAX calls
window.initLaporan = function() {
    if (window.Laporan) {
        window.Laporan.init();
    }
};