// Data Umum File Upload Functions
(function() {
    'use strict';
    
    class DataUmumFileUpload {
        constructor() {
            this.init();
        }
        
        init() {
            // File upload functionality can be initialized here if needed
        }
        
        setupFileUpload(uploadAreaId, fileInputId, previewId) {
            const uploadArea = document.getElementById(uploadAreaId);
            const fileInput = document.getElementById(fileInputId);

            if (!uploadArea || !fileInput) return;

            uploadArea.addEventListener('click', () => fileInput.click());
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--secondary-color)';
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.borderColor = 'var(--border-color)';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--border-color)';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.handleFileUpload(files[0], previewId);
                }
            });

            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.handleFileUpload(e.target.files[0], previewId);
                }
            });
        }
        
        async handleFileUpload(file, previewId) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

            if (file.size > maxSize) {
                showNotification('Ukuran file terlalu besar (max 5MB)', 'error');
                return;
            }

            if (!allowedTypes.includes(file.type)) {
                showNotification('Format file tidak didukung', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', previewId.includes('Fisik') ? 'fisik' : 'administratif');

                const baseUrl = window.DataUmumUtils ? window.DataUmumUtils.getBaseUrl() : '/Pendataan-desa/';
                const response = await fetch(`${baseUrl}api/router.php?endpoint=upload-peta`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (window.dataUmumCoreInstance) {
                        window.dataUmumCoreInstance.showUploadedFile(previewId, result.file_path, file.name);
                    }
                    showNotification('File berhasil diupload', 'success');
                } else {
                    showNotification(result.message || 'Gagal upload file', 'error');
                }
            } catch (error) {
                showNotification('Gagal upload file', 'error');
            }
        }
    }
    
    // Initialize file upload instance
    window.DataUmumFileUpload = new DataUmumFileUpload();
})();