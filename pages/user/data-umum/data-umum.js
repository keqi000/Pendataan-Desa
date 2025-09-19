// Data Umum Namespace
if (typeof window.DataUmum === 'undefined') {
window.DataUmum = {
    isEditing: false,
    currentMap: null,
    currentMarker: null,
    desaMap: null,

    init() {
        if (!document.querySelector('.data-umum-container')) return;
        
        this.loadDataDesa();
        this.setupEventListeners();
        this.initializePetaDesa();
    },

    setupEventListeners() {
        // Form submission
        const form = document.getElementById('formInformasiDasar');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveInformasiDasar();
            });
        }
    },

    async loadDataDesa() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=profile-desa`);
            
            console.log('Data Desa Response status:', response.status);
            console.log('Data Desa Response headers:', response.headers);
            
            const responseText = await response.text();
            console.log('Data Desa Raw response:', responseText);
            
            const data = JSON.parse(responseText);
            
            if (data.success && data.data) {
                this.populateForm(data.data);
            }
        } catch (error) {
            console.error('Error loading data desa:', error);
            if (typeof showNotification === 'function') showNotification('Gagal memuat data desa', 'error');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    populateForm(data) {
        document.getElementById('namaDesa').value = data.nama_desa || '';
        document.getElementById('namaKecamatan').value = data.nama_kecamatan || '';
        document.getElementById('namaKabupaten').value = data.nama_kabupaten || '';
        document.getElementById('namaProvinsi').value = data.nama_provinsi || '';
        document.getElementById('luasWilayah').value = data.luas_wilayah || '';
        document.getElementById('jumlahDusun').value = data.jumlah_dusun || '';
        document.getElementById('jumlahRt').value = data.jumlah_rt || '';
        document.getElementById('jumlahRw').value = data.jumlah_rw || '';
        // Initialize village map after data is loaded
        setTimeout(() => this.initializePetaDesa(), 500);

        // Load uploaded files
        if (data.peta_fisik) {
            this.showUploadedFile('previewPetaFisik', data.peta_fisik, 'Peta Fisik');
        }
        if (data.peta_administratif) {
            this.showUploadedFile('previewPetaAdministratif', data.peta_administratif, 'Peta Administratif');
        }
    },

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
    },

    async handleFileUpload(file, previewId) {
        // Validate file
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

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=upload-peta`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showUploadedFile(previewId, result.file_path, file.name);
                showNotification('File berhasil diupload', 'success');
            } else {
                showNotification(result.message || 'Gagal upload file', 'error');
            }
        } catch (error) {
            console.error('Error uploading file:', error);
            showNotification('Gagal upload file', 'error');
        }
    },

    showUploadedFile(previewId, filePath, fileName) {
        const preview = document.getElementById(previewId);
        const fileExt = filePath.split('.').pop().toLowerCase();
        const icon = fileExt === 'pdf' ? 'fas fa-file-pdf' : 'fas fa-image';

        preview.innerHTML = `
            <div class="file-info">
                <div class="file-icon">
                    <i class="${icon}"></i>
                </div>
                <div class="file-details">
                    <h5>${fileName}</h5>
                    <p>File berhasil diupload</p>
                </div>
            </div>
            <div class="file-actions">
                <button class="file-action-btn btn-view" onclick="viewFile('${filePath}')">
                    <i class="fas fa-eye"></i> Lihat
                </button>
                <button class="file-action-btn btn-delete" onclick="deleteFile('${filePath}', '${previewId}')">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
        `;
        preview.style.display = 'flex';
    },

    async initializePetaDesa() {
        console.log('🗺️ [DESA-MAP] Starting initializePetaDesa');
        const mapContainer = document.getElementById('petaDesaMap');
        console.log('🗺️ [DESA-MAP] Map container found:', !!mapContainer);
        if (!mapContainer) {
            console.error('❌ [DESA-MAP] Map container not found');
            return;
        }
        
        try {
            console.log('🗺️ [DESA-MAP] Leaflet available:', typeof L !== 'undefined');
            // Load Leaflet if not available
            if (typeof L === 'undefined') {
                console.log('🗺️ [DESA-MAP] Loading Leaflet...');
                const leafletCSS = document.createElement('link');
                leafletCSS.rel = 'stylesheet';
                leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(leafletCSS);
                
                const leafletJS = document.createElement('script');
                leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                leafletJS.onload = () => {
                    console.log('🗺️ [DESA-MAP] Leaflet loaded, creating map');
                    this.createDesaMap();
                };
                leafletJS.onerror = () => {
                    console.error('❌ [DESA-MAP] Failed to load Leaflet');
                };
                document.head.appendChild(leafletJS);
            } else {
                console.log('🗺️ [DESA-MAP] Leaflet already available, creating map');
                this.createDesaMap();
            }
        } catch (error) {
            console.error('❌ [DESA-MAP] Error initializing peta desa:', error);
        }
    },
    
    async createDesaMap() {
        console.log('🗺️ [DESA-MAP] Starting createDesaMap');
        const mapContainer = document.getElementById('petaDesaMap');
        console.log('🗺️ [DESA-MAP] Map container in createDesaMap:', !!mapContainer);
        if (!mapContainer) {
            console.error('❌ [DESA-MAP] Map container not found in createDesaMap');
            return;
        }
        
        try {
            // Remove existing map if any
            if (this.desaMap) {
                console.log('🗺️ [DESA-MAP] Removing existing map');
                this.desaMap.remove();
                this.desaMap = null;
            }
            
            console.log('🗺️ [DESA-MAP] Creating new map');
            // Create map
            this.desaMap = L.map('petaDesaMap').setView([0.5547, 123.0581], 12);
            console.log('🗺️ [DESA-MAP] Map created successfully');
            
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap contributors, © CARTO'
            }).addTo(this.desaMap);
            console.log('🗺️ [DESA-MAP] Tile layer added');
            
            // Load village boundary
            console.log('🗺️ [DESA-MAP] Loading village boundary...');
            const response = await fetch(`${window.BASE_URL}api/administrative-boundaries.php`);
            console.log('🗺️ [DESA-MAP] Boundary API response status:', response.status);
            const boundaries = await response.json();
            console.log('🗺️ [DESA-MAP] Boundaries loaded:', boundaries.length);
            
            // Get current village name
            const desaName = document.getElementById('namaDesa').value;
            console.log('🗺️ [DESA-MAP] Looking for desa:', desaName);
            
            // Find matching boundary
            const desaBoundary = boundaries.find(b => {
                const boundaryName = (b.nm_desa || b.nm_kelurahan || '').toLowerCase();
                const searchName = desaName.toLowerCase();
                console.log('🗺️ [DESA-MAP] Comparing:', boundaryName, 'vs', searchName);
                return b.boundary_type === 'desa' && boundaryName === searchName;
            });
            
            console.log('🗺️ [DESA-MAP] Matching boundary found:', !!desaBoundary);
            
            if (desaBoundary) {
                console.log('🗺️ [DESA-MAP] Adding boundary to map');
                const geojson = JSON.parse(desaBoundary.geojson_data);
                const layer = L.geoJSON(geojson, {
                    style: {
                        color: '#1abc9c',
                        fillColor: '#1abc9c',
                        fillOpacity: 0.3,
                        weight: 3
                    }
                }).addTo(this.desaMap);
                
                this.desaMap.fitBounds(layer.getBounds());
                layer.bindPopup(`<h5>${desaName}</h5><p>Batas wilayah desa</p>`);
                console.log('✅ [DESA-MAP] Village boundary added successfully');
            } else {
                console.log('⚠️ [DESA-MAP] No matching boundary found for:', desaName);
            }
            
            // Load village-specific data
            this.loadDesaData(desaName);
        } catch (error) {
            console.error('❌ [DESA-MAP] Error in createDesaMap:', error);
        }
    },
    
    async loadDesaData(desaName) {
        try {
            // Load facilities
            const fasilitasResponse = await fetch(`${window.BASE_URL}api/user-endpoints.php?endpoint=fasilitas-pendidikan`);
            const fasilitasResult = await fasilitasResponse.json();
            if (fasilitasResult.success) {
                fasilitasResult.data.forEach(fasilitas => {
                    let lat = parseFloat(fasilitas.koordinat_latitude) || 0.5547 + (Math.random() - 0.5) * 0.01;
                    let lng = parseFloat(fasilitas.koordinat_longitude) || 123.0581 + (Math.random() - 0.5) * 0.01;
                    
                    L.circleMarker([lat, lng], {
                        radius: 8, fillColor: '#e74c3c', color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.8
                    }).addTo(this.desaMap).bindPopup(`<h5>${fasilitas.nama_fasilitas}</h5><p><strong>Jenis:</strong> ${fasilitas.jenis_pendidikan}</p>`);
                });
            }
            
            // Load roads
            const jalanResponse = await fetch(`${window.BASE_URL}api/user-endpoints.php?endpoint=infrastruktur-jalan`);
            const jalanResult = await jalanResponse.json();
            if (jalanResult.success) {
                jalanResult.data.forEach(jalan => {
                    let lat = parseFloat(jalan.koordinat_start_lat) || 0.5547 + (Math.random() - 0.5) * 0.01;
                    let lng = parseFloat(jalan.koordinat_start_lng) || 123.0581 + (Math.random() - 0.5) * 0.01;
                    
                    L.circleMarker([lat, lng], {
                        radius: 8, fillColor: '#3498db', color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.8
                    }).addTo(this.desaMap).bindPopup(`<h5>${jalan.nama_jalan}</h5><p><strong>Panjang:</strong> ${jalan.panjang_jalan} km</p>`);
                });
            }
            
            // Load bridges
            const jembatanResponse = await fetch(`${window.BASE_URL}api/user-endpoints.php?endpoint=infrastruktur-jembatan`);
            const jembatanResult = await jembatanResponse.json();
            if (jembatanResult.success) {
                jembatanResult.data.forEach(jembatan => {
                    let lat = parseFloat(jembatan.koordinat_latitude) || 0.5547 + (Math.random() - 0.5) * 0.01;
                    let lng = parseFloat(jembatan.koordinat_longitude) || 123.0581 + (Math.random() - 0.5) * 0.01;
                    
                    L.circleMarker([lat, lng], {
                        radius: 8, fillColor: '#f39c12', color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.8
                    }).addTo(this.desaMap).bindPopup(`<h5>${jembatan.nama_jembatan}</h5><p><strong>Panjang:</strong> ${jembatan.panjang_jembatan} m</p>`);
                });
            }
        } catch (error) {
            console.error('❌ [DESA-DATA] Error loading village data:', error);
        }
    },



    async saveInformasiDasar() {
        try {
            const formData = new FormData(document.getElementById('formInformasiDasar'));
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=update-data-desa`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Data berhasil disimpan', 'success');
                window.cancelEdit();
                setTimeout(() => {
                    if (typeof loadPage === 'function') {
                        loadPage('data-umum');
                    }
                }, 1000);
            } else {
                showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            console.error('Error saving data:', error);
            showNotification('Gagal menyimpan data', 'error');
        }
    },


};
}

// Global functions
window.editInformasiDasar = function() {
    const inputs = document.querySelectorAll('#formInformasiDasar input');
    const actions = document.getElementById('formActions');
    
    inputs.forEach(input => {
        if (input.name !== 'nama_desa' && input.name !== 'nama_kecamatan' && 
            input.name !== 'nama_kabupaten' && input.name !== 'nama_provinsi') {
            input.removeAttribute('readonly');
        }
    });
    
    actions.style.display = 'flex';
    DataUmum.isEditing = true;
};

window.cancelEdit = function() {
    const inputs = document.querySelectorAll('#formInformasiDasar input');
    const actions = document.getElementById('formActions');
    
    inputs.forEach(input => input.setAttribute('readonly', true));
    actions.style.display = 'none';
    DataUmum.isEditing = false;
    DataUmum.loadDataDesa();
};



window.viewFile = function(filePath) {
    window.open(`${window.BASE_URL || '/Pendataan-desa/'}uploads/${filePath}`, '_blank');
};

window.deleteFile = async function(filePath, previewId) {
    if (!confirm('Yakin ingin menghapus file ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-file`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file_path: filePath })
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById(previewId).style.display = 'none';
            showNotification('File berhasil dihapus', 'success');
        } else {
            showNotification(result.message || 'Gagal menghapus file', 'error');
        }
    } catch (error) {
        console.error('Error deleting file:', error);
        showNotification('Gagal menghapus file', 'error');
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    DataUmum.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    // DOM is already loaded, initialize immediately
    setTimeout(() => {
        DataUmum.init();
    }, 100);
}

// Global functions for peta desa
window.toggleDesaFullscreen = function() {
    const wrapper = document.querySelector('.peta-desa-wrapper');
    if (!document.fullscreenElement) {
        wrapper.requestFullscreen().then(() => {
            // Invalidate map size after fullscreen
            setTimeout(() => {
                if (window.DataUmum && window.DataUmum.desaMap) {
                    window.DataUmum.desaMap.invalidateSize();
                }
            }, 100);
        });
    } else {
        document.exitFullscreen().then(() => {
            // Invalidate map size after exit fullscreen
            setTimeout(() => {
                if (window.DataUmum && window.DataUmum.desaMap) {
                    window.DataUmum.desaMap.invalidateSize();
                }
            }, 100);
        });
    }
};

// Global init function for AJAX calls
window.initDataUmum = function() {
    if (window.DataUmum) {
        window.DataUmum.init();
    }
};