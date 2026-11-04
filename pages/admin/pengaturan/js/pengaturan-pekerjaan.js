/**
 * Pengaturan Pekerjaan Management
 */

class PengaturanPekerjaanManager {
    constructor() {
        this.baseUrl = window.BASE_URL || '';
        this.init();
    }

    init() {
        if (document.getElementById('pekerjaan-tab')) {
            this.loadPekerjaanData();
        }
    }

    async loadPekerjaanData() {
        try {
            console.log('=== LOADING PEKERJAAN DATA (MASTERS) ===');
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_all`);
            const data = await response.json();
            
            if (data.success) {
                console.log('Masters loaded:', data.data);
                data.data.forEach((item, index) => {
                    console.log(`MASTER ${index + 1}: "${item.standard_name}" - Usage: ${item.usage_count || 0} (${item.source_tables || 'none'})`);
                    
                    // Show debug info if available
                    if (item.debug_info) {
                        const debug = item.debug_info;
                        console.log(`  Debug - Penduduk: ${debug.penduduk_count}, Mata Pencaharian: ${debug.mata_pencaharian_count}`);
                        console.log(`  Debug (Case Insensitive) - Penduduk: ${debug.penduduk_count_case_insensitive}, Mata Pencaharian: ${debug.mata_pencaharian_count_case_insensitive}`);
                        
                        // Check for case sensitivity issues
                        const exact_total = debug.penduduk_count + debug.mata_pencaharian_count;
                        const ci_total = debug.penduduk_count_case_insensitive + debug.mata_pencaharian_count_case_insensitive;
                        
                        if (ci_total > exact_total) {
                            console.warn(`  ⚠️ CASE SENSITIVITY ISSUE: Master "${item.standard_name}" - Exact: ${exact_total}, Case-insensitive: ${ci_total}`);
                            console.warn(`    This means there are ${ci_total - exact_total} records with different case variations that should be normalized!`);
                        }
                        
                        if (debug.sample_penduduk && debug.sample_penduduk.length > 0) {
                            console.log(`  Sample Penduduk:`, debug.sample_penduduk);
                        }
                        
                        if (debug.sample_mata_pencaharian && debug.sample_mata_pencaharian.length > 0) {
                            console.log(`  Sample Mata Pencaharian:`, debug.sample_mata_pencaharian);
                        }
                        
                        if (debug.penduduk_count === 0 && debug.mata_pencaharian_count === 0) {
                            console.warn(`  ⚠️ MASTER "${item.standard_name}" has no actual data in database!`);
                        }
                    }
                });
                this.renderPekerjaanTable(data.data);
            } else {
                console.error('Failed to load pekerjaan data:', data.message);
                this.showAlert('Gagal memuat data pekerjaan', 'danger');
            }
        } catch (error) {
            console.error('Error loading pekerjaan data:', error);
            this.showAlert('Terjadi kesalahan saat memuat data', 'danger');
        }
    }

    renderPekerjaanTable(data) {
        const tbody = document.getElementById('pekerjaanTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';
        
        data.forEach((item, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${this.escapeHtml(item.standard_name)}</td>
                <td><span class="badge badge-info">${this.escapeHtml(item.kategori || 'Lainnya')}</span></td>
                <td><span class="badge badge-${item.status === 'aktif' ? 'success' : 'danger'}">${item.status === 'aktif' ? 'Aktif' : 'Non-aktif'}</span></td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="pengaturanPekerjaanManager.editPekerjaan(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="pengaturanPekerjaanManager.deletePekerjaan(${item.id}, '${this.escapeHtml(item.standard_name)}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    async createPekerjaan() {
        const nama = document.getElementById('createNamaPekerjaan').value.trim();
        const kategori = document.getElementById('createKategori').value;

        if (!nama || !kategori) {
            this.showAlert('Semua field harus diisi', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=create_master&standard_name=${encodeURIComponent(nama)}&kategori=${encodeURIComponent(kategori)}`
            });

            const result = await response.json();
            
            if (result.success) {
                this.closeCreatePekerjaanModal();
                this.loadPekerjaanData();
                this.showAlert('Pekerjaan berhasil ditambahkan', 'success');
            } else {
                this.showAlert(result.message || 'Gagal menambahkan pekerjaan', 'danger');
            }
        } catch (error) {
            console.error('Error creating pekerjaan:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }

    async editPekerjaan(id) {
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_by_id&id=${id}`);
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                document.getElementById('editPekerjaanId').value = data.id;
                document.getElementById('editNamaPekerjaan').value = data.standard_name;
                document.getElementById('editKategori').value = data.kategori || 'Lainnya';
                document.getElementById('editStatusPekerjaan').value = data.status || 'aktif';
                
                this.showEditPekerjaanModal();
            }
        } catch (error) {
            console.error('Error loading pekerjaan data:', error);
            this.showAlert('Gagal memuat data pekerjaan', 'danger');
        }
    }

    async updatePekerjaan() {
        const id = document.getElementById('editPekerjaanId').value;
        const nama = document.getElementById('editNamaPekerjaan').value.trim();
        const kategori = document.getElementById('editKategori').value;
        const status = document.getElementById('editStatusPekerjaan').value;

        if (!nama || !kategori) {
            this.showAlert('Semua field harus diisi', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_master&id=${id}&standard_name=${encodeURIComponent(nama)}&kategori=${encodeURIComponent(kategori)}&status=${status}`
            });

            const result = await response.json();
            
            if (result.success) {
                this.closeEditPekerjaanModal();
                this.loadPekerjaanData();
                this.showAlert('Pekerjaan berhasil diupdate', 'success');
            } else {
                this.showAlert(result.message || 'Gagal mengupdate pekerjaan', 'danger');
            }
        } catch (error) {
            console.error('Error updating pekerjaan:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }

    async deletePekerjaan(id, nama) {
        if (!confirm(`Yakin ingin menghapus pekerjaan "${nama}"?`)) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_master&id=${id}`
            });

            const result = await response.json();
            
            if (result.success) {
                this.loadPekerjaanData();
                this.showAlert('Pekerjaan berhasil dihapus', 'success');
            } else {
                this.showAlert(result.message || 'Gagal menghapus pekerjaan', 'danger');
            }
        } catch (error) {
            console.error('Error deleting pekerjaan:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }

    showCreatePekerjaanModal() {
        document.getElementById('createNamaPekerjaan').value = '';
        document.getElementById('createKategori').value = '';
        document.getElementById('createPekerjaanModal').style.display = 'flex';
    }

    closeCreatePekerjaanModal() {
        document.getElementById('createPekerjaanModal').style.display = 'none';
    }

    showEditPekerjaanModal() {
        document.getElementById('editPekerjaanModal').style.display = 'flex';
    }

    closeEditPekerjaanModal() {
        document.getElementById('editPekerjaanModal').style.display = 'none';
    }

    showAlert(message, type) {
        // Create alert element mengikuti style kependudukan
        const alertDiv = document.createElement('div');
        alertDiv.className = `kependudukan-alert kependudukan-alert-${type}`;
        alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; padding: 15px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
        
        // Set colors based on type
        const colors = {
            'success': { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
            'danger': { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
            'warning': { bg: '#fff3cd', border: '#ffeaa7', text: '#856404' },
            'info': { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' }
        };
        
        const color = colors[type] || colors.info;
        alertDiv.style.backgroundColor = color.bg;
        alertDiv.style.borderLeft = `4px solid ${color.border}`;
        alertDiv.style.color = color.text;
        
        alertDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: ${color.text}; margin-left: 10px;">&times;</button>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    async scanExistingData() {
        if (!confirm('Scan semua data pekerjaan dari kedua tabel (penduduk & mata_pencaharian)? Ini akan mendeteksi case sensitivity issues dan menambahkan data yang belum ada ke dalam mapping untuk review.')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=scan_existing'
            });
            
            const result = await response.json();
            
            if (result.success) {
                const data = result.result;
                let message = `Scan selesai!\n\n`;
                message += `📊 HASIL SCAN:\n`;
                message += `Total pekerjaan ditemukan: ${data.total_found}\n`;
                message += `Ditambahkan untuk review: ${data.processed}\n`;
                message += `Sudah ada di mapping: ${data.already_mapped}\n`;
                
                // Show case sensitivity issues if found
                if (data.case_sensitive_detected > 0) {
                    message += `\n⚠️ CASE SENSITIVITY ISSUES:\n`;
                    message += `Issues ditemukan: ${data.case_sensitive_detected}\n`;
                    
                    if (data.case_sensitive_issues && data.case_sensitive_issues.length > 0) {
                        message += `\nContoh issues:\n`;
                        data.case_sensitive_issues.slice(0, 3).forEach(issue => {
                            message += `• "${issue.found_in_data}" → "${issue.existing_mapping}"\n`;
                        });
                        
                        message += `\n🔧 Gunakan tombol "Auto-Fix Issues" untuk memperbaiki otomatis.`;
                    }
                }
                
                message += `\n\n📈 SUMBER DATA:\n`;
                message += `Dari tabel penduduk: ${data.sources.penduduk_count}\n`;
                message += `Dari tabel mata_pencaharian: ${data.sources.mata_pencaharian_count}`;
                
                if (data.processed > 0 || data.case_sensitive_detected > 0) {
                    message += '\n\nSilakan refresh halaman untuk melihat perubahan.';
                }
                
                alert(message);
                
                if (data.processed > 0) {
                    this.loadPekerjaanData();
                    this.loadStatistikData();
                }
                
                // Show case sensitivity fix option
                if (data.case_sensitive_detected > 0) {
                    if (confirm('Ditemukan case sensitivity issues. Ingin memperbaiki otomatis sekarang?')) {
                        this.autoFixScanIssues();
                    }
                }
            } else {
                this.showAlert('Gagal melakukan scan data: ' + (result.message || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error scanning data:', error);
            this.showAlert('Terjadi kesalahan sistem: ' + error.message, 'danger');
        }
    }
    
    async transformExistingData() {
        if (!confirm('Transform semua data pekerjaan existing sesuai dengan mapping yang sudah ada? Ini akan mengubah data pekerjaan di kedua tabel (penduduk & mata_pencaharian) sesuai dengan mapping yang telah disetujui.')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=normalize_existing'
            });
            
            const result = await response.json();
            
            if (result.success) {
                const processed = result.processed;
                let message = `Transform selesai!\n\n`;
                message += `🔄 HASIL TRANSFORM:\n`;
                message += `Total data yang berhasil ditransform: ${processed}\n\n`;
                
                if (processed > 0) {
                    message += '✅ Data pekerjaan telah diupdate sesuai dengan mapping yang ada di kedua tabel (penduduk & mata_pencaharian).';
                } else {
                    message += 'ℹ️ Tidak ada data yang perlu ditransform atau semua mapping belum disetujui.';
                }
                
                alert(message);
                this.showAlert(`${processed} data pekerjaan berhasil ditransform`, processed > 0 ? 'success' : 'info');
                
                // Refresh data
                this.loadStatistikData();
            } else {
                this.showAlert('Gagal melakukan transform data: ' + (result.message || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error transforming data:', error);
            this.showAlert('Terjadi kesalahan sistem: ' + error.message, 'danger');
        }
    }
    

    
    async loadStatistikData() {
        try {
            // Load mapping stats
            const mappingResponse = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_mapping_stats`);
            const mappingResult = await mappingResponse.json();
            
            if (mappingResult.success) {
                const stats = mappingResult.data;
                document.getElementById('totalMapping').textContent = stats.total_mapping || 0;
                document.getElementById('approvedMapping').textContent = stats.approved_mapping || 0;
                document.getElementById('pendingMapping').textContent = stats.pending_mapping || 0;
                document.getElementById('masterPekerjaan').textContent = stats.master_pekerjaan || 0;
            }
            
            // Load pekerjaan distribution stats (now returns dual structure)
            const statsResponse = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_stats`);
            const statsResult = await statsResponse.json();
            
            if (statsResult.success) {
                // Handle new dual structure: {distribusi_pekerjaan: [], distribusi_mapping: []}
                this.renderStatistikTable(statsResult.data);
            }
        } catch (error) {
            console.error('Error loading statistik data:', error);
            this.showAlert('Terjadi kesalahan saat memuat statistik', 'danger');
        }
    }
    
    renderStatistikTable(data) {
        const tbody = document.getElementById('statistikTableBody');
        if (!tbody) return;
        
        // Handle new dual structure: {distribusi_pekerjaan: [], distribusi_mapping: []}
        let pekerjaanData = [];
        
        if (data && typeof data === 'object') {
            if (data.distribusi_pekerjaan && Array.isArray(data.distribusi_pekerjaan)) {
                // New structure with dual display
                pekerjaanData = data.distribusi_pekerjaan;
                
                // Also render mapping table if available
                if (data.distribusi_mapping && Array.isArray(data.distribusi_mapping)) {
                    this.renderMappingTable(data.distribusi_mapping);
                }
            } else if (Array.isArray(data)) {
                // Old structure (backward compatibility)
                pekerjaanData = data;
            }
        }
        
        if (!pekerjaanData || pekerjaanData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data pekerjaan</td></tr>';
            return;
        }
        
        // Calculate total for percentage
        const total = pekerjaanData.reduce((sum, item) => sum + parseInt(item.jumlah), 0);
        
        tbody.innerHTML = '';
        pekerjaanData.forEach((item, index) => {
            const percentage = total > 0 ? ((parseInt(item.jumlah) / total) * 100).toFixed(1) : 0;
            const sourceTables = Array.isArray(item.source_tables) ? item.source_tables.join(', ') : (item.source_tables || 'N/A');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${this.escapeHtml(item.pekerjaan)}</td>
                <td>${item.jumlah}</td>
                <td>${percentage}%</td>
                <td><small class="text-muted">${sourceTables}</small></td>
            `;
            tbody.appendChild(row);
        });
    }
    
    renderMappingTable(mappingData) {
        const tbody = document.getElementById('mappingTableBody');
        if (!tbody) {
            // Create mapping table if it doesn't exist
            this.createMappingTableSection();
            return;
        }
        
        if (!mappingData || mappingData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data mapping</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        mappingData.forEach((item, index) => {
            const statusBadge = this.getStatusBadge(item.status);
            const masterBadge = item.is_master ? '<span class="badge badge-primary">Master</span>' : '<span class="badge badge-secondary">Mapping</span>';
            const sourceTables = item.source_tables || 'none';
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${this.escapeHtml(item.pekerjaan)}</td>
                <td>${this.escapeHtml(item.standard_name || '-')}</td>
                <td>${statusBadge}</td>
                <td>${masterBadge}</td>
                <td>${item.usage_count || 0}</td>
                <td><small class="text-muted">${sourceTables}</small></td>
            `;
            tbody.appendChild(row);
        });
    }
    
    getStatusBadge(status) {
        const badges = {
            'aktif': '<span class="badge badge-success">Aktif</span>',
            'approved': '<span class="badge badge-success">Approved</span>',
            'pending': '<span class="badge badge-warning">Pending</span>',
            'rejected': '<span class="badge badge-danger">Rejected</span>',
            'nonaktif': '<span class="badge badge-secondary">Non-aktif</span>'
        };
        return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
    }
    
    createMappingTableSection() {
        // This function would create the mapping table section if it doesn't exist
        // For now, we'll just log that it's missing
        console.log('Mapping table section not found. Please add mappingTableBody element to HTML.');
    }
    
    async loadReviewedData() {
        try {
            console.log('=== LOADING REVIEWED DATA ===');
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_reviewed_mappings`);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check if response has content
            const text = await response.text();
            if (!text.trim()) {
                throw new Error('Empty response from server');
            }
            
            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Response text:', text);
                throw new Error('Invalid JSON response: ' + parseError.message);
            }
            
            if (result.success) {
                console.log('Reviewed data loaded:', result.data);
                result.data.forEach((item, index) => {
                    const type = item.is_master ? 'MASTER' : 'NON-MASTER';
                    console.log(`${type} ${index + 1}: "${item.input_text}" -> "${item.standard_name}" (${item.status}) - Usage: ${item.usage_count || 0} (${item.source_tables || 'none'})`);
                });
                this.renderReviewedData(result.data);
            } else {
                this.showAlert(result.message || 'Gagal memuat data reviewed mappings', 'danger');
            }
        } catch (error) {
            console.error('Error loading reviewed data:', error);
            this.showAlert('Terjadi kesalahan sistem: ' + error.message, 'danger');
        }
    }
    
    renderReviewedData(data) {
        const container = document.getElementById('reviewedDataContainer');
        
        if (!data || data.length === 0) {
            container.innerHTML = '<p class="text-center">Tidak ada mapping yang sudah direview</p>';
            return;
        }
        
        // Group data by status
        const approved = data.filter(item => item.status === 'approved' || item.status === 'aktif');
        const rejected = data.filter(item => item.status === 'rejected');
        
        let html = '<div class="reviewed-mappings">';
        
        // Approved mappings section
        if (approved.length > 0) {
            html += `
                <div class="mapping-section" style="margin-bottom: 30px;">
                    <h5 style="color: #28a745; margin-bottom: 15px;">
                        <i class="fas fa-check-circle"></i> Mapping yang Disetujui (${approved.length})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Input Text</th>
                                    <th>Standard Name</th>
                                    <th>Kategori</th>
                                    <th>Usage Count</th>
                                    <th>Source Tables</th>
                                    <th>Type</th>
                                    <th>Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            approved.forEach((item, index) => {
                const masterBadge = item.is_master ? '<span class="badge badge-primary">Master</span>' : '<span class="badge badge-success">Approved</span>';
                const sourceTables = item.source_tables || 'none';
                const updatedDate = item.updated_at ? new Date(item.updated_at).toLocaleDateString('id-ID') : '-';
                
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${this.escapeHtml(item.input_text)}</strong></td>
                        <td>${this.escapeHtml(item.standard_name || '-')}</td>
                        <td><span class="badge badge-info">${this.escapeHtml(item.kategori || 'Lainnya')}</span></td>
                        <td>${item.usage_count || 0}</td>
                        <td><small class="text-muted">${sourceTables}</small></td>
                        <td>${masterBadge}</td>
                        <td><small class="text-muted">${updatedDate}</small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-warning" onclick="window.pengaturanPekerjaanManager.editReviewedMapping(${item.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${!item.is_master ? `<button class="btn btn-sm btn-outline-danger" onclick="window.pengaturanPekerjaanManager.deleteReviewedMapping(${item.id}, '${this.escapeHtml(item.input_text)}')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>` : ''}
                        </td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        // Rejected mappings section
        if (rejected.length > 0) {
            html += `
                <div class="mapping-section">
                    <h5 style="color: #dc3545; margin-bottom: 15px;">
                        <i class="fas fa-times-circle"></i> Mapping yang Ditolak (${rejected.length})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Input Text</th>
                                    <th>Usage Count</th>
                                    <th>Source Tables</th>
                                    <th>Rejected Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            rejected.forEach((item, index) => {
                const sourceTables = item.source_tables || 'none';
                const rejectedDate = item.updated_at ? new Date(item.updated_at).toLocaleDateString('id-ID') : '-';
                
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${this.escapeHtml(item.input_text)}</strong></td>
                        <td>${item.usage_count || 0}</td>
                        <td><small class="text-muted">${sourceTables}</small></td>
                        <td><small class="text-muted">${rejectedDate}</small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-success" onclick="window.pengaturanPekerjaanManager.reactivateMapping(${item.id})" title="Reactivate for Review">
                                <i class="fas fa-redo"></i> Reactivate
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="window.pengaturanPekerjaanManager.deleteReviewedMapping(${item.id}, '${this.escapeHtml(item.input_text)}')" title="Delete Permanently">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    async editReviewedMapping(id) {
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_by_id&id=${id}`);
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                
                // Show edit modal for reviewed mapping
                const newStandardName = prompt(`Edit Standard Name untuk "${data.input_text}":`, data.standard_name || data.input_text);
                
                if (newStandardName && newStandardName.trim() !== '') {
                    await this.updateReviewedMapping(id, newStandardName.trim(), data.kategori || 'Lainnya');
                }
            }
        } catch (error) {
            console.error('Error loading mapping data:', error);
            this.showAlert('Gagal memuat data mapping', 'danger');
        }
    }
    
    async updateReviewedMapping(id, standardName, kategori) {
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_mapping&id=${id}&standard_name=${encodeURIComponent(standardName)}&kategori=${encodeURIComponent(kategori)}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Mapping berhasil diupdate', 'success');
                this.loadReviewedData(); // Refresh reviewed data
                this.loadStatistikData(); // Refresh statistics
            } else {
                this.showAlert('Gagal mengupdate mapping', 'danger');
            }
        } catch (error) {
            console.error('Error updating mapping:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }
    
    async deleteReviewedMapping(id, inputText) {
        if (!confirm(`Yakin ingin menghapus mapping "${inputText}"? Tindakan ini tidak dapat dibatalkan.`)) {
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_mapping&id=${id}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Mapping berhasil dihapus', 'success');
                this.loadReviewedData(); // Refresh reviewed data
                this.loadStatistikData(); // Refresh statistics
            } else {
                this.showAlert('Gagal menghapus mapping: ' + (result.message || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error deleting mapping:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }
    
    async reactivateMapping(id) {
        if (!confirm('Yakin ingin mengaktifkan kembali mapping ini untuk direview ulang?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reactivate_mapping&id=${id}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Mapping berhasil diaktifkan kembali', 'success');
                this.loadReviewedData(); // Refresh reviewed data
                this.loadReviewData(); // Refresh pending review data
            } else {
                this.showAlert('Gagal mengaktifkan mapping', 'danger');
            }
        } catch (error) {
            console.error('Error reactivating mapping:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }
    
    async loadReviewData() {
        try {
            console.log('=== LOADING REVIEW DATA (PENDING) ===');
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_pending_review`);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check if response has content
            const text = await response.text();
            if (!text.trim()) {
                throw new Error('Empty response from server');
            }
            
            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Response text:', text);
                throw new Error('Invalid JSON response: ' + parseError.message);
            }
            
            if (result.success) {
                console.log('Pending review data loaded:', result.data);
                result.data.forEach((item, index) => {
                    console.log(`PENDING ${index + 1}: "${item.input_text}" - Usage: ${item.usage_count || 0} (${item.source_tables || 'none'})`);
                });
                this.renderReviewData(result.data);
            } else {
                this.showAlert(result.message || 'Gagal memuat data review', 'danger');
            }
        } catch (error) {
            console.error('Error loading review data:', error);
            this.showAlert('Terjadi kesalahan sistem: ' + error.message, 'danger');
        }
    }
    
    renderReviewData(data) {
        const container = document.getElementById('reviewDataContainer');
        
        if (!data || data.length === 0) {
            container.innerHTML = '<p class="text-center">Tidak ada data yang perlu direview</p>';
            return;
        }
        
        let html = '<div class="review-items">';
        
        data.forEach((item, index) => {
            html += `
                <div class="review-item" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; margin-bottom: 12px; background: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h5 style="margin: 0 0 8px 0; color: #112D4E;">${this.escapeHtml(item.input_text)}</h5>
                            <small style="color: #666;">Digunakan oleh ${item.usage_count || 0} orang</small>
                            <br><small style="color: #999;">Sumber: ${item.source_tables}</small>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <select id="masterSelect${item.id}" style="padding: 6px; border: 1px solid #ccc; border-radius: 4px; min-width: 150px;">
                                <option value="">Pilih Master Pekerjaan</option>
                            </select>
                            <button class="btn btn-sm btn-success" onclick="window.pengaturanPekerjaanManager.approveMapping(${item.id})" style="padding: 6px 12px;">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="window.pengaturanPekerjaanManager.createMasterFromReview(${item.id}, '${this.escapeHtml(item.input_text)}')" style="padding: 6px 12px;">
                                <i class="fas fa-plus"></i> Buat Master
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="window.pengaturanPekerjaanManager.rejectMapping(${item.id})" style="padding: 6px 12px;">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        this.populateMasterOptions(data);
    }
    
    async populateMasterOptions(reviewData) {
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php?action=get_all`);
            const result = await response.json();
            
            if (result.success) {
                const masters = result.data;
                
                reviewData.forEach(item => {
                    const select = document.getElementById(`masterSelect${item.id}`);
                    if (select) {
                        masters.forEach(master => {
                            const option = document.createElement('option');
                            option.value = master.standard_name;
                            option.textContent = master.standard_name;
                            select.appendChild(option);
                        });
                    }
                });
            }
        } catch (error) {
            console.error('Error loading master options:', error);
        }
    }
    
    async approveMapping(id) {
        const select = document.getElementById(`masterSelect${id}`);
        const standardName = select ? select.value : '';
        
        if (!standardName) {
            this.showAlert('Pilih master pekerjaan terlebih dahulu', 'warning');
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=approve_mapping&id=${id}&standard_name=${encodeURIComponent(standardName)}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('Mapping berhasil disetujui', 'success');
                this.loadReviewData(); // Refresh review data
            } else {
                this.showAlert('Gagal menyetujui mapping', 'danger');
            }
        } catch (error) {
            console.error('Error approving mapping:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }
    
    async createMasterFromReview(id, inputText) {
        // Show modal to input master details
        const standardName = prompt(`Buat Master Pekerjaan dari "${inputText}"\n\nMasukkan nama standard:`, inputText);
        
        if (!standardName || standardName.trim() === '') {
            return;
        }
        
        const kategori = prompt('Pilih kategori:\n\n1. Pertanian\n2. Perdagangan\n3. Jasa\n4. Industri\n5. Pemerintahan\n6. Pendidikan\n7. Kesehatan\n8. Lainnya\n\nMasukkan nomor (1-8):', '8');
        
        const kategoriMap = {
            '1': 'Pertanian',
            '2': 'Perdagangan', 
            '3': 'Jasa',
            '4': 'Industri',
            '5': 'Pemerintahan',
            '6': 'Pendidikan',
            '7': 'Kesehatan',
            '8': 'Lainnya'
        };
        
        const selectedKategori = kategoriMap[kategori] || 'Lainnya';
        
        if (!confirm(`Konfirmasi:\n\nInput Text: ${inputText}\nStandard Name: ${standardName.trim()}\nKategori: ${selectedKategori}\n\nBuat sebagai Master Pekerjaan?`)) {
            return;
        }
        
        console.log('=== CREATE MASTER FROM REVIEW ===');
        console.log('ID:', id);
        console.log('Input Text:', inputText);
        console.log('Standard Name:', standardName.trim());
        console.log('Kategori:', selectedKategori);
        
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=create_master_from_review&id=${id}&input_text=${encodeURIComponent(inputText)}&standard_name=${encodeURIComponent(standardName.trim())}&kategori=${encodeURIComponent(selectedKategori)}`
            });
            
            const result = await response.json();
            console.log('Create master result:', result);
            
            // Show debug info if available
            if (result.debug_info) {
                console.log('=== CREATE MASTER DEBUG INFO ===');
                console.log('Input:', result.debug_info.input);
                console.log('Before Update (Case Insensitive):', result.debug_info.before_update);
                console.log('After Update (Exact Match):', result.debug_info.after_update);
                
                const before = result.debug_info.before_update;
                const after = result.debug_info.after_update;
                
                console.log('\n📊 DATA MOVEMENT ANALYSIS:');
                console.log(`Before: "${result.debug_info.input.input_text}" had ${before.penduduk_count_case_insensitive + before.mata_pencaharian_count_case_insensitive} records (case-insensitive)`);
                console.log(`After: "${result.debug_info.input.input_text}" has ${after.old_input_penduduk_exact + after.old_input_mata_pencaharian_exact} records (exact match)`);
                console.log(`After: "${result.debug_info.input.standard_name}" has ${after.new_standard_penduduk_exact + after.new_standard_mata_pencaharian_exact} records (exact match)`);
                
                if (after.old_input_penduduk_exact > 0 || after.old_input_mata_pencaharian_exact > 0) {
                    console.warn(`⚠️ WARNING: Old input text still has data! This means case-insensitive update didn't work properly.`);
                }
                
                if (after.new_standard_penduduk_exact === 0 && after.new_standard_mata_pencaharian_exact === 0) {
                    console.warn(`⚠️ WARNING: New master has no data! Data might not have been moved properly.`);
                }
                
                console.log('=== END DEBUG INFO ===\n');
            }
            
            if (result.success) {
                this.showAlert('Master pekerjaan berhasil dibuat', 'success');
                console.log('Refreshing data after master creation...');
                this.loadReviewData(); // Refresh review data
                this.loadPekerjaanData(); // Refresh master data
                this.loadStatistikData(); // Refresh statistics
            } else {
                console.error('Failed to create master:', result.message);
                this.showAlert('Gagal membuat master pekerjaan: ' + (result.message || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error creating master from review:', error);
            this.showAlert('Terjadi kesalahan sistem', 'danger');
        }
    }
    
    async normalizeCaseVariations() {
        if (!confirm('Normalisasi semua variasi case untuk master yang ada? Ini akan mengubah data seperti "MENGURUS RUMAH TANGGA" menjadi "mengurus rumah tangga" sesuai dengan nama master.')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=normalize_case_variations'
            });
            
            const result = await response.json();
            
            if (result.success) {
                const normalized = result.normalized;
                let message = `Normalisasi case selesai!\n\n`;
                message += `🔄 HASIL NORMALISASI:\n`;
                message += `Total data yang dinormalisasi: ${normalized}\n\n`;
                
                if (normalized > 0) {
                    message += '✅ Data dengan variasi case telah dinormalisasi sesuai dengan nama master.';
                } else {
                    message += 'ℹ️ Tidak ada data yang perlu dinormalisasi.';
                }
                
                alert(message);
                this.showAlert(`${normalized} data berhasil dinormalisasi`, normalized > 0 ? 'success' : 'info');
                
                // Refresh data
                this.loadPekerjaanData();
                this.loadStatistikData();
            } else {
                this.showAlert('Gagal melakukan normalisasi: ' + (result.message || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error normalizing case variations:', error);
            this.showAlert('Terjadi kesalahan sistem: ' + error.message, 'danger');
        }
    }
    
    async cleanDuplicates() {
        try {
            const response = await fetch(`${this.baseUrl}pages/user/kependudukan/php/PekerjaanManager.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clean_duplicates'
            });
            
            const result = await response.json();
            
            if (result.success) {
                const data = result.result;
                let message = `Pembersihan duplikat selesai!\n\n`;
                message += `🧹 HASIL PEMBERSIHAN:\n`;
                message += `Total record dihapus: ${data.total_cleaned}\n`;
                message += `Grup duplikat ditemukan: ${data.duplicate_groups}\n\n`;
                
                if (data.details && data.details.length > 0) {
                    message += `📝 DETAIL PEMBERSIHAN:\n`;
                    data.details.slice(0, 5).forEach(detail => {
                        message += `• Disimpan: "${detail.kept}"\n`;
                        message += `  Dihapus: ${detail.deleted.join(', ')} (${detail.count_deleted} item)\n\n`;
                    });
                    
                    if (data.details.length > 5) {
                        message += `... dan ${data.details.length - 5} grup lainnya\n`;
                    }
                }
                
                if (data.total_cleaned > 0) {
                    message += '✅ Database sudah bersih dari duplikasi case-insensitive!';
                } else {
                    message += 'ℹ️ Tidak ada duplikasi yang ditemukan.';
                }
                
                alert(message);
                this.showAlert(`${data.total_cleaned} duplikat berhasil dibersihkan`, data.total_cleaned > 0 ? 'success' : 'info');
                
                // Refresh data
                this.loadPekerjaanData();
                this.loadStatistikData();
            } else {
                this.showAlert('Gagal membersihkan duplikat: ' + (result.message || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error cleaning duplicates:', error);
            this.showAlert('Terjadi kesalahan sistem: ' + error.message, 'danger');
        }
    }
}

// Global functions for modal actions
function showCreatePekerjaanModal() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.showCreatePekerjaanModal();
    }
}

function closeCreatePekerjaanModal() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.closeCreatePekerjaanModal();
    }
}

function createPekerjaan() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.createPekerjaan();
    }
}

function closeEditPekerjaanModal() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.closeEditPekerjaanModal();
    }
}

function updatePekerjaan() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.updatePekerjaan();
    }
}

function scanExistingData() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.scanExistingData();
    }
}

function transformExistingData() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.transformExistingData();
    }
}

function normalizeCaseVariations() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.normalizeCaseVariations();
    }
}

function showPekerjaanSubTab(tabName) {
    // Hide all subtabs
    document.querySelectorAll('.pekerjaan-subtab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active from all subtab buttons
    document.querySelectorAll('#pekerjaan-tab .tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected subtab
    document.getElementById(tabName + '-subtab').classList.add('active');
    
    // Add active to clicked button
    event.target.classList.add('active');
    
    // Load data based on tab
    if (tabName === 'review') {
        loadReviewData();
    } else if (tabName === 'statistik') {
        loadStatistikData();
    } else if (tabName === 'reviewed') {
        loadReviewedData();
    }
}

function loadReviewData() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.loadReviewData();
    }
}

function loadReviewedData() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.loadReviewedData();
    }
}

function loadStatistikData() {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.loadStatistikData();
    }
}

function approveMapping(id) {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.approveMapping(id);
    }
}

function rejectMapping(id) {
    if (window.pengaturanPekerjaanManager) {
        window.pengaturanPekerjaanManager.rejectMapping(id);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.pengaturanPekerjaanManager = new PengaturanPekerjaanManager();
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('kependudukan-modal')) {
            if (e.target.id === 'createPekerjaanModal') {
                closeCreatePekerjaanModal();
            } else if (e.target.id === 'editPekerjaanModal') {
                closeEditPekerjaanModal();
            }
        }
    });
});