// Peta Upload Functions
(function() {
    'use strict';
    
    class PetaUpload {
        constructor() {
            if (document.querySelector('.peta-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupFilePreview();
        }
        
        setupFilePreview() {
            const fileInput = document.getElementById('administrativeBoundariesFile');
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            try {
                                const geojson = JSON.parse(e.target.result);
                                if (geojson.type === 'FeatureCollection' && geojson.features.length > 0) {
                                    const types = {};
                                    geojson.features.forEach(feature => {
                                        const props = feature.properties || {};
                                        if (props.NAMOBJ || props.nama_desa || props.desa) types.desa = (types.desa || 0) + 1;
                                        if (props.NAMKEC || props.kecamatan) types.kecamatan = (types.kecamatan || 0) + 1;
                                        if (props.NAMKAB || props.kabupaten) types.kabupaten = (types.kabupaten || 0) + 1;
                                        if (props.NAMPROV || props.provinsi) types.provinsi = (types.provinsi || 0) + 1;
                                    });
                                    
                                    let preview = `<small>Ditemukan ${geojson.features.length} features.<br>`;
                                    preview += `Tipe: ${Object.entries(types).map(([k,v]) => `${k}: ${v}`).join(', ')}</small>`;
                                    
                                    document.getElementById('boundariesPreviewContent').innerHTML = preview;
                                    document.getElementById('boundariesPreviewInfo').style.display = 'block';
                                }
                            } catch (error) {
                                // Silent error handling
                            }
                        };
                        reader.readAsText(file);
                    }
                });
            }
        }
        
        async processAdministrativeBoundaries() {
            const fileInput = document.getElementById('administrativeBoundariesFile');
            const statusDiv = document.getElementById('administrativeBoundariesUploadStatus');
            
            if (!fileInput.files[0]) {
                statusDiv.innerHTML = 'Pilih file GeoJSON terlebih dahulu';
                statusDiv.style.background = '#f8d7da';
                statusDiv.style.color = '#721c24';
                statusDiv.style.display = 'block';
                return;
            }
            
            const formData = new FormData();
            formData.append('administrative_boundaries_file', fileInput.files[0]);
            
            try {
                statusDiv.innerHTML = 'Memproses batas administratif...';
                statusDiv.style.background = '#d1ecf1';
                statusDiv.style.color = '#0c5460';
                statusDiv.style.display = 'block';
                
                const response = await fetch(window.BASE_URL + 'pages/admin/peta/peta.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    statusDiv.innerHTML = `Berhasil! ${result.inserted} batas administratif diproses.`;
                    statusDiv.style.background = '#d4edda';
                    statusDiv.style.color = '#155724';
                    
                    setTimeout(() => {
                        this.closeAdministrativeBoundariesModal();
                        location.reload();
                    }, 3000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                statusDiv.innerHTML = 'Error: ' + error.message;
                statusDiv.style.background = '#f8d7da';
                statusDiv.style.color = '#721c24';
            }
        }
        
        uploadAdministrativeBoundaries() {
            document.getElementById('uploadAdministrativeBoundariesModal').style.display = 'flex';
        }
        
        closeAdministrativeBoundariesModal() {
            document.getElementById('uploadAdministrativeBoundariesModal').style.display = 'none';
            document.getElementById('administrativeBoundariesFile').value = '';
            document.getElementById('administrativeBoundariesUploadStatus').style.display = 'none';
            document.getElementById('boundariesPreviewInfo').style.display = 'none';
        }
    }
    
    // Global functions
    window.uploadAdministrativeBoundaries = function() {
        if (window.PetaUpload) {
            window.PetaUpload.uploadAdministrativeBoundaries();
        }
    };
    
    window.closeAdministrativeBoundariesModal = function() {
        if (window.PetaUpload) {
            window.PetaUpload.closeAdministrativeBoundariesModal();
        }
    };
    
    window.processAdministrativeBoundaries = async function() {
        if (window.PetaUpload) {
            await window.PetaUpload.processAdministrativeBoundaries();
        }
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.PetaUpload) {
            window.PetaUpload = new PetaUpload();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.PetaUpload) {
            window.PetaUpload = new PetaUpload();
        }
    }
})();