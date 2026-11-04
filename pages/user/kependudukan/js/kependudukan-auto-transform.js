// Auto Transform Module for Kependudukan
// File: pages/user/kependudukan/js/kependudukan-auto-transform.js

class KependudukanAutoTransform {
    constructor() {
        this.currentFile = null;
        this.detectedMapping = null;
        this.customMapping = {};
        this.transformedData = null;
        this.detectedHeaderRow = 1; // FIX #5: simpan header row yang terdeteksi
        
        this.initializeEventListeners();
    }
    
    initializeEventListeners() {
        const autoImportBtn = document.getElementById('autoImportBtn');
        if (autoImportBtn) {
            autoImportBtn.addEventListener('click', () => this.openAutoTransformModal());
        }
        
        const fileInput = document.getElementById('autoTransformFile');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }
        
        const uploadArea = document.getElementById('autoTransformUploadArea');
        if (uploadArea) {
            uploadArea.addEventListener('click', () => fileInput?.click());
            uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
            uploadArea.addEventListener('drop', (e) => this.handleFileDrop(e));
        }
        
        // FIX #5: Re-detect saat user mengubah manual header row
        const manualHeaderRowInput = document.getElementById('manualHeaderRow');
        if (manualHeaderRowInput) {
            manualHeaderRowInput.addEventListener('change', () => {
                if (this.currentFile) this.detectFileFormat();
            });
        }
        
        const templateSelect = document.getElementById('mappingTemplateSelect');
        if (templateSelect) {
            templateSelect.addEventListener('change', (e) => this.loadMappingTemplate(e.target.value));
        }
        
        const processBtn = document.getElementById('processAutoTransformBtn');
        if (processBtn) {
            processBtn.addEventListener('click', () => this.processAutoTransform());
        }
        
        const saveTemplateBtn = document.getElementById('saveTemplateBtn');
        if (saveTemplateBtn) {
            saveTemplateBtn.addEventListener('click', () => this.saveCurrentMapping());
        }
    }
    
    openAutoTransformModal() {
        const modal = document.getElementById('modalAutoTransform');
        if (modal) {
            const mainContent = document.querySelector('.main-layout-content');
            if (mainContent) {
                mainContent.style.position = 'relative';
                if (modal.parentElement) {
                    modal.parentElement.removeChild(modal);
                }
                mainContent.appendChild(modal);
            }
            modal.style.display = 'flex';
            this.loadMappingTemplates();
            this.resetModal();
        }
    }
    
    closeAutoTransformModal() {
        const modal = document.getElementById('modalAutoTransform');
        if (modal) {
            modal.style.display = 'none';
            this.resetModal();
        }
    }
    
    resetModal() {
        this.currentFile = null;
        this.detectedMapping = null;
        this.customMapping = {};
        this.transformedData = null;
        this.detectedHeaderRow = 1;
        
        document.getElementById('autoTransformFile').value = '';
        const hrInput = document.getElementById('manualHeaderRow');
        if (hrInput) hrInput.value = '';
        document.getElementById('detectionResults').style.display = 'none';
        document.getElementById('mappingConfiguration').style.display = 'none';
        document.getElementById('transformResults').style.display = 'none';
        document.getElementById('processAutoTransformBtn').style.display = 'none';
    }
    
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.currentFile = file;
            this.detectFileFormat();
        }
    }
    
    handleDragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('drag-over');
    }
    
    handleFileDrop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');
        
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.currentFile = files[0];
            document.getElementById('autoTransformFile').files = files;
            this.detectFileFormat();
        }
    }
    
    async detectFileFormat() {
        if (!this.currentFile) return;
        
        const formData = new FormData();
        formData.append('excel_file', this.currentFile);
        formData.append('action', 'detect_format');
        
        // FIX #5: kirim manual header row jika diisi user
        const manualHeaderRow = document.getElementById('manualHeaderRow')?.value;
        if (manualHeaderRow && manualHeaderRow !== '') {
            formData.append('header_row', manualHeaderRow);
        }
        
        try {
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'autoTransformLoader';
            loadingDiv.innerHTML = '<div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;color:white;"><i class="fas fa-spinner fa-spin"></i> Mendeteksi format Excel...</div>';
            const mainContent = document.querySelector('.main-layout-content');
            if (mainContent) {
                mainContent.style.position = 'relative';
                mainContent.appendChild(loadingDiv);
            } else {
                document.body.appendChild(loadingDiv);
            }
            
            const response = await fetch(BASE_URL + 'pages/user/kependudukan/php/kependudukan-auto-transform.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            const loader = document.getElementById('autoTransformLoader');
            if (loader) loader.remove();
            
            if (result.success) {
                this.detectedMapping = result.data.mapping;
                this.detectedHeaderRow = result.data.header_row || 1;
                this.displayDetectionResults(result.data);
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            const loader = document.getElementById('autoTransformLoader');
            if (loader) loader.remove();
            showNotification('Error mendeteksi format: ' + error.message, 'error');
        }
    }
    
    displayDetectionResults(data) {
        const resultsDiv = document.getElementById('detectionResults');
        const mappingDiv = document.getElementById('mappingConfiguration');
        
        const confidencePercent = Math.round(data.confidence * 100);
        const confidenceClass = confidencePercent >= 80 ? 'high' : confidencePercent >= 60 ? 'medium' : 'low';
        
        // FIX #5: tampilkan info header row yang terdeteksi
        const multiRowInfo = data.is_multi_row
            ? ` &nbsp;<span class="confidence-badge medium">Header 2 baris (baris ${data.header_row - 1} &amp; ${data.header_row})</span>`
            : ` &nbsp;<span class="confidence-badge high">Header 1 baris (baris ${data.header_row})</span>`;

        document.getElementById('detectionConfidence').innerHTML = `
            <span class="confidence-badge ${confidenceClass}">${confidencePercent}%</span>
            Tingkat kepercayaan deteksi otomatis${multiRowInfo}
        `;
        
        // FIX #5: update input manual header row dengan nilai yang terdeteksi
        const hrInput = document.getElementById('manualHeaderRow');
        if (hrInput && !hrInput.value) hrInput.value = data.header_row;
        
        const headersHtml = data.headers.map((header, index) => 
            `<span class="header-tag" data-index="${index}">${header}</span>`
        ).join('');
        document.getElementById('detectedHeaders').innerHTML = headersHtml;
        
        this.displayMappingTable(data.headers, data.mapping, data.suggestions);
        
        resultsDiv.style.display = 'block';
        mappingDiv.style.display = 'block';
        document.getElementById('processAutoTransformBtn').style.display = 'inline-block';
    }
    
    displayMappingTable(headers, mapping, suggestions) {
        const tbody = document.getElementById('mappingTableBody');
        const standardFields = [
            'nik', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir', 
            'agama', 'pendidikan_terakhir', 'pekerjaan', 'status_pernikahan', 
            'status_migrasi', 'alamat'
        ];
        
        const fieldLabels = {
            'nik': 'NIK',
            'nama_lengkap': 'Nama Lengkap',
            'jenis_kelamin': 'Jenis Kelamin',
            'tanggal_lahir': 'Tanggal Lahir',
            'agama': 'Agama',
            'pendidikan_terakhir': 'Pendidikan Terakhir',
            'pekerjaan': 'Pekerjaan',
            'status_pernikahan': 'Status Pernikahan',
            'status_migrasi': 'Status Migrasi',
            'alamat': 'Alamat'
        };
        
        tbody.innerHTML = '';
        
        standardFields.forEach(field => {
            const row = document.createElement('tr');
            const isRequired = ['nik', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir'].includes(field);
            const mappedData = mapping[field];
            
            const options = ['<option value="">-- Pilih Kolom --</option>'];
            headers.forEach((header, index) => {
                const selected = mappedData && mappedData.index === index ? 'selected' : '';
                options.push(`<option value="${index}" ${selected}>${header}</option>`);
            });
            
            const confidence = mappedData ? Math.round(mappedData.confidence * 100) + '%' : '-';
            const confidenceClass = mappedData ? (mappedData.confidence >= 0.8 ? 'high' : mappedData.confidence >= 0.6 ? 'medium' : 'low') : '';
            
            row.innerHTML = `
                <td>
                    ${fieldLabels[field]}
                    ${isRequired ? '<span class="required">*</span>' : ''}
                </td>
                <td>
                    <select class="mapping-select" data-field="${field}">
                        ${options.join('')}
                    </select>
                </td>
                <td>
                    <span class="confidence-badge ${confidenceClass}">${confidence}</span>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        tbody.querySelectorAll('.mapping-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const field = e.target.dataset.field;
                const index = e.target.value ? parseInt(e.target.value) : null;
                
                if (index !== null) {
                    this.customMapping[field] = {
                        index: index,
                        header: headers[index],
                        confidence: 1.0
                    };
                } else {
                    delete this.customMapping[field];
                }
            });
        });
    }
    
    async loadMappingTemplates() {
        try {
            const response = await fetch(BASE_URL + 'pages/user/kependudukan/php/kependudukan-auto-transform.php?action=get_mapping_templates');
            const result = await response.json();
            
            if (result.success) {
                const select = document.getElementById('mappingTemplateSelect');
                select.innerHTML = '<option value="">-- Pilih Template --</option>';
                
                result.data.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.template_name;
                    option.textContent = `${template.template_name} - ${template.description}`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            // Handle error silently
        }
    }
    
    async loadMappingTemplate(templateName) {
        if (!templateName) return;
        
        try {
            const response = await fetch(BASE_URL + `pages/user/kependudukan/php/kependudukan-auto-transform.php?action=load_mapping_template&template_name=${encodeURIComponent(templateName)}`);
            const result = await response.json();
            
            if (result.success) {
                this.customMapping = result.data;
                this.applyMappingToTable();
                showNotification('Template berhasil dimuat', 'success');
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Error loading template: ' + error.message, 'error');
        }
    }
    
    applyMappingToTable() {
        document.querySelectorAll('.mapping-select').forEach(select => {
            const field = select.dataset.field;
            if (this.customMapping[field]) {
                select.value = this.customMapping[field].index;
            }
        });
    }
    
    async processAutoTransform() {
        if (!this.currentFile) {
            showNotification('Pilih file Excel terlebih dahulu', 'error');
            return;
        }
        
        const finalMapping = { ...this.detectedMapping, ...this.customMapping };
        
        const requiredFields = ['nik', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir'];
        const missingFields = requiredFields.filter(field => !finalMapping[field]);
        
        if (missingFields.length > 0) {
            showNotification(`Field wajib belum dipetakan: ${missingFields.join(', ')}`, 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('excel_file', this.currentFile);
        formData.append('action', 'transform_data');
        formData.append('mapping', JSON.stringify(finalMapping));
        formData.append('custom_mapping', JSON.stringify(this.customMapping));
        formData.append('header_row', this.detectedHeaderRow); // FIX #5
        
        try {
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'processTransformLoader';
            loadingDiv.innerHTML = '<div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;color:white;"><i class="fas fa-spinner fa-spin"></i> Memproses transformasi data...</div>';
            const mainContainer = document.querySelector('.main-layout-container');
            if (mainContainer) {
                mainContainer.style.position = 'relative';
                mainContainer.appendChild(loadingDiv);
            } else {
                document.body.appendChild(loadingDiv);
            }
            
            const response = await fetch(BASE_URL + 'pages/user/kependudukan/php/kependudukan-auto-transform.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            const loader = document.getElementById('processTransformLoader');
            if (loader) loader.remove();
            
            if (result.success) {
                this.transformedData = result.data;
                this.displayTransformResults(result.data);
                this.showImportOptions();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            const loader = document.getElementById('processTransformLoader');
            if (loader) loader.remove();
            showNotification('Error processing transform: ' + error.message, 'error');
        }
    }
    
    displayTransformResults(data) {
        const resultsDiv = document.getElementById('transformResults');
        
        document.getElementById('transformSummary').innerHTML = `
            <div class="transform-stats">
                <div class="stat-item">
                    <span class="stat-number">${data.total_rows}</span>
                    <span class="stat-label">Total Baris</span>
                </div>
                <div class="stat-item success">
                    <span class="stat-number">${data.successful_rows}</span>
                    <span class="stat-label">Berhasil</span>
                </div>
                <div class="stat-item error">
                    <span class="stat-number">${data.errors.length}</span>
                    <span class="stat-label">Error</span>
                </div>
            </div>
        `;
        
        if (data.errors.length > 0) {
            const errorsHtml = data.errors.slice(0, 10).map(error => 
                `<div class="error-item">${error}</div>`
            ).join('');
            
            document.getElementById('transformErrors').innerHTML = `
                <h4>Error yang Ditemukan:</h4>
                <div class="error-list">${errorsHtml}</div>
                ${data.errors.length > 10 ? `<p>Dan ${data.errors.length - 10} error lainnya...</p>` : ''}
            `;
        } else {
            document.getElementById('transformErrors').innerHTML = '<p class="success-message">Tidak ada error ditemukan!</p>';
        }
        
        resultsDiv.style.display = 'block';
    }
    
    showImportOptions() {
        const optionsDiv = document.getElementById('importOptions');
        optionsDiv.innerHTML = `
            <div class="import-actions">
                <button class="kependudukan-btn-primary" onclick="autoTransform.importTransformedData()">
                    <i class="fas fa-database"></i> Import ke Database
                </button>
                <button class="kependudukan-btn-secondary" onclick="autoTransform.previewTransformedData()">
                    <i class="fas fa-eye"></i> Preview Data
                </button>
                <button class="kependudukan-btn-secondary" onclick="autoTransform.downloadTransformedData()">
                    <i class="fas fa-download"></i> Download Excel
                </button>
            </div>
        `;
        optionsDiv.style.display = 'block';
    }
    
    async importTransformedData() {
        if (!this.transformedData || !this.transformedData.transformed_data) {
            showNotification('Tidak ada data untuk diimport', 'error');
            return;
        }
        
        try {
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'importDataLoader';
            loadingDiv.innerHTML = '<div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;color:white;"><i class="fas fa-spinner fa-spin"></i> Mengimport data ke database...</div>';
            const mainContainer = document.querySelector('.main-layout-container');
            if (mainContainer) {
                mainContainer.style.position = 'relative';
                mainContainer.appendChild(loadingDiv);
            } else {
                document.body.appendChild(loadingDiv);
            }
            
            let imported = 0;
            let errors = [];
            
            for (const rowData of this.transformedData.transformed_data) {
                const formData = new FormData();
                formData.append('action', 'create_penduduk');
                
                Object.keys(rowData).forEach(key => {
                    formData.append(key, rowData[key]);
                });
                
                try {
                    const response = await fetch(BASE_URL + 'pages/user/kependudukan/php/kependudukan-functions.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    
                    const responseText = await response.text();
                    
                    if (!responseText.trim()) {
                        errors.push(`NIK ${rowData.nik}: Empty server response`);
                        continue;
                    }
                    
                    const cleanResponse = responseText.replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                    
                    let result;
                    try {
                        result = JSON.parse(cleanResponse);
                    } catch (parseError) {
                        errors.push(`NIK ${rowData.nik}: Invalid server response`);
                        continue;
                    }
                    
                    if (result.success) {
                        imported++;
                    } else {
                        errors.push(`NIK ${rowData.nik}: ${result.message}`);
                    }
                } catch (error) {
                    errors.push(`NIK ${rowData.nik}: ${error.message}`);
                }
            }
            
            const loader = document.getElementById('importDataLoader');
            if (loader) loader.remove();
            
            showNotification(`Import selesai! ${imported} data berhasil diimport${errors.length > 0 ? `, ${errors.length} error` : ''}`, imported > 0 ? 'success' : 'warning');
            
            if (errors.length > 0) {
                const errorDetails = errors.slice(0, 5).join('\n');
                const moreErrors = errors.length > 5 ? `\n... dan ${errors.length - 5} error lainnya` : '';
                setTimeout(() => {
                    alert(`Detail Error:\n${errorDetails}${moreErrors}`);
                }, 500);
            }
            
            if (typeof window.Kependudukan !== 'undefined' && window.Kependudukan.loadPendudukData) {
                window.Kependudukan.loadPendudukData();
            }
            this.closeAutoTransformModal();
            
        } catch (error) {
            const loader = document.getElementById('importDataLoader');
            if (loader) loader.remove();
            showNotification('Error importing data: ' + error.message, 'error');
        }
    }
    
    previewTransformedData() {
        if (!this.transformedData || !this.transformedData.transformed_data) {
            showNotification('Tidak ada data untuk preview', 'error');
            return;
        }
        
        const previewModal = document.createElement('div');
        previewModal.className = 'kependudukan-modal';
        previewModal.innerHTML = `
            <div class="kependudukan-modal-content large">
                <div class="kependudukan-modal-header">
                    <h3>Preview Data Hasil Transform</h3>
                    <button class="kependudukan-modal-close" onclick="this.closest('.kependudukan-modal').remove()">&times;</button>
                </div>
                <div class="kependudukan-modal-body">
                    <div class="preview-table-container">
                        ${this.generatePreviewTable()}
                    </div>
                </div>
                <div class="kependudukan-modal-footer">
                    <button class="kependudukan-btn-secondary" onclick="this.closest('.kependudukan-modal').remove()">Tutup</button>
                </div>
            </div>
        `;
        
        const mainContent = document.querySelector('.main-layout-content');
        if (mainContent) {
            mainContent.style.position = 'relative';
            mainContent.appendChild(previewModal);
        } else {
            document.body.appendChild(previewModal);
        }
        previewModal.style.display = 'flex';
    }
    
    generatePreviewTable() {
        const data = this.transformedData.transformed_data.slice(0, 20);
        const headers = ['NIK', 'Nama Lengkap', 'JK', 'Tanggal Lahir', 'Agama', 'Pendidikan', 'Pekerjaan', 'Status Nikah', 'Status Migrasi', 'Alamat'];
        const fields = ['nik', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir', 'agama', 'pendidikan_terakhir', 'pekerjaan', 'status_pernikahan', 'status_migrasi', 'alamat'];
        
        let tableHtml = '<table class="preview-table"><thead><tr>';
        headers.forEach(header => { tableHtml += `<th>${header}</th>`; });
        tableHtml += '</tr></thead><tbody>';
        
        data.forEach(row => {
            tableHtml += '<tr>';
            fields.forEach(field => { tableHtml += `<td>${row[field] || '-'}</td>`; });
            tableHtml += '</tr>';
        });
        
        tableHtml += '</tbody></table>';
        
        if (this.transformedData.transformed_data.length > 20) {
            tableHtml += `<p class="preview-note">Menampilkan 20 dari ${this.transformedData.transformed_data.length} data</p>`;
        }
        
        return tableHtml;
    }
    
    async saveCurrentMapping() {
        const templateName = prompt('Masukkan nama template:');
        if (!templateName) return;
        
        const description = prompt('Masukkan deskripsi template (opsional):') || '';
        const finalMapping = { ...this.detectedMapping, ...this.customMapping };
        
        const formData = new FormData();
        formData.append('action', 'save_mapping_template');
        formData.append('template_name', templateName);
        formData.append('mapping', JSON.stringify(finalMapping));
        formData.append('description', description);
        
        try {
            const response = await fetch(BASE_URL + 'pages/user/kependudukan/php/kependudukan-auto-transform.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Template berhasil disimpan', 'success');
                this.loadMappingTemplates();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Error saving template: ' + error.message, 'error');
        }
    }
}

// Global functions
function openAutoTransformModal() {
    if (typeof autoTransform !== 'undefined') {
        autoTransform.openAutoTransformModal();
    }
}

function closeAutoTransformModal() {
    if (typeof autoTransform !== 'undefined') {
        autoTransform.closeAutoTransformModal();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.autoTransform === 'undefined') {
        window.autoTransform = new KependudukanAutoTransform();
    }
});
