// Data Umum Namespace
if (typeof window.DataUmum === 'undefined') {
window.DataUmum = class {
    constructor() {
        this.isEditing = false;
        this.mapLayers = {};
        this.init();
    }
    
    init() {
        if (!document.querySelector('.data-umum-container')) return;
        
        this.loadDataDesa();
        this.setupEventListeners();
        this.initializePetaDesa();
    }
    
    setupEventListeners() {
        const form = document.getElementById('formInformasiDasar');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveInformasiDasar();
            });
        }
        
        // Setup legend toggle listeners
        document.querySelectorAll('.legend-item').forEach(item => {
            item.addEventListener('click', () => {
                const layer = item.dataset.layer;
                this.toggleLayer(layer);
            });
        });
    }
    
    async loadDataDesa() {
        // Data sudah tersedia dari server-side PHP, tidak perlu AJAX
        console.log('Data desa loaded from server-side');
    }
    
    populateForm(data) {
        const fields = ['namaDesa', 'namaKecamatan', 'namaKabupaten', 'namaProvinsi', 'luasWilayah', 'jumlahDusun', 'jumlahRt', 'jumlahRw'];
        fields.forEach(field => {
            const element = document.getElementById(field);
            const value = data[field.replace(/([A-Z])/g, '_$1').toLowerCase()] || '';
            if (element) {
                element.value = value;
            }
        });
    }
    
    async initializePetaDesa() {
        const mapContainer = document.getElementById('petaDesaMap');
        if (!mapContainer) return;
        
        try {
            if (typeof L === 'undefined') {
                const leafletCSS = document.createElement('link');
                leafletCSS.rel = 'stylesheet';
                leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(leafletCSS);
                
                const leafletJS = document.createElement('script');
                leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                leafletJS.onload = () => this.createDesaMap();
                document.head.appendChild(leafletJS);
            } else {
                this.createDesaMap();
            }
        } catch (error) {
            // Map initialization failed
        }
    }
    
    async createDesaMap() {
        const mapContainer = document.getElementById('petaDesaMap');
        if (!mapContainer) return;
        
        try {
            if (this.desaMap) {
                this.desaMap.remove();
                this.desaMap = null;
            }
            
            this.desaMap = L.map('petaDesaMap').setView([0.5547, 123.0581], 12);
            
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap contributors, © CARTO'
            }).addTo(this.desaMap);
            
            try {
                const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/admin/peta/peta.php?endpoint=administrative-boundaries`);
                
                if (response.status !== 401) {
                    const boundaries = await response.json();
                    const boundariesData = boundaries.success ? boundaries.data : boundaries;
                    
                    if (Array.isArray(boundariesData) && boundariesData.length > 0) {
                        const desaName = document.getElementById('namaDesa').value;
                        
                        const desaBoundary = boundariesData.find(b => {
                            if (b.boundary_type !== 'desa') return false;
                            const boundaryName = (b.nm_desa || b.nm_kelurahan || '').toLowerCase().trim();
                            const kecamatanKode = b.kd_kecamatan || '';
                            const searchName = desaName.toLowerCase().trim();
                            return boundaryName === searchName && kecamatanKode === '004';
                        });
                        
                        if (desaBoundary) {
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
                        }
                    }
                }
            } catch (boundaryError) {
                // Boundary loading failed, continue without boundaries
            }
            
            this.loadDesaData();
        } catch (error) {
            // Map creation failed
        }
    }
    
    async loadDesaData() {
        try {
            const baseUrl = window.BASE_URL || '/Pendataan-desa/';
            // Get user's desa ID from session or form data
            const desaId = window.USER_DESA_ID || document.querySelector('input[name="id_desa"]')?.value || 
                          document.querySelector('[data-desa-id]')?.dataset.desaId;
            
            if (!desaId) {
                console.log('No desa ID found, skipping marker loading');
                return;
            }
            
            // Load facilities for user's village only
            const fasilitasResponse = await fetch(`${baseUrl}pages/admin/peta/peta.php?endpoint=fasilitas-pendidikan&id_desa=${desaId}`);
            const fasilitasResult = await fasilitasResponse.json();
            
            if (fasilitasResult.success && fasilitasResult.data) {
                this.mapLayers.fasilitas = L.layerGroup();
                fasilitasResult.data.forEach(fasilitas => {
                    let lat = parseFloat(fasilitas.koordinat_latitude) || 0.5547 + (Math.random() - 0.5) * 0.01;
                    let lng = parseFloat(fasilitas.koordinat_longitude) || 123.0581 + (Math.random() - 0.5) * 0.01;
                    
                    const marker = L.circleMarker([lat, lng], {
                        radius: 8, fillColor: '#e74c3c', color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.8
                    }).bindPopup(`<h5>${fasilitas.nama_fasilitas}</h5><p><strong>Jenis:</strong> ${fasilitas.jenis_pendidikan}</p>`);
                    
                    this.mapLayers.fasilitas.addLayer(marker);
                });
                this.mapLayers.fasilitas.addTo(this.desaMap);
            }
            
            // Load bridges for user's village only
            const jembatanResponse = await fetch(`${baseUrl}pages/admin/peta/peta.php?endpoint=infrastruktur-jembatan&id_desa=${desaId}`);
            const jembatanResult = await jembatanResponse.json();
            
            if (jembatanResult.success && jembatanResult.data) {
                this.mapLayers.jembatan = L.layerGroup();
                jembatanResult.data.forEach(jembatan => {
                    let lat = parseFloat(jembatan.koordinat_latitude) || 0.5547 + (Math.random() - 0.5) * 0.01;
                    let lng = parseFloat(jembatan.koordinat_longitude) || 123.0581 + (Math.random() - 0.5) * 0.01;
                    
                    const marker = L.circleMarker([lat, lng], {
                        radius: 8, fillColor: '#f39c12', color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.8
                    }).bindPopup(`<h5>${jembatan.nama_jembatan}</h5><p><strong>Panjang:</strong> ${jembatan.panjang_jembatan} m</p>`);
                    
                    this.mapLayers.jembatan.addLayer(marker);
                });
                this.mapLayers.jembatan.addTo(this.desaMap);
            }
        } catch (error) {
            // Error loading village data
        }
    }
    
    toggleLayer(layerName) {
        if (!this.desaMap || !this.mapLayers[layerName]) return;
        
        const legendItem = document.querySelector(`[data-layer="${layerName}"]`);
        if (!legendItem) return;
        
        if (this.desaMap.hasLayer(this.mapLayers[layerName])) {
            this.desaMap.removeLayer(this.mapLayers[layerName]);
            legendItem.classList.add('disabled');
        } else {
            this.desaMap.addLayer(this.mapLayers[layerName]);
            legendItem.classList.remove('disabled');
        }
    }
    
    async saveInformasiDasar() {
        try {
            const formData = new FormData(document.getElementById('formInformasiDasar'));
            formData.append('action', 'update-data-desa');
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}pages/user/data-umum/php/data-umum-functions.php`, {
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
            showNotification('Terjadi kesalahan saat menyimpan data', 'error');
        }
    }
};

// Global functions
window.editInformasiDasar = function() {
    const form = document.getElementById('formInformasiDasar');
    const inputs = form.querySelectorAll('input');
    const actions = document.getElementById('formActions');
    
    inputs.forEach(input => {
        if (input.id !== 'namaDesa' && input.id !== 'namaKecamatan' && 
            input.id !== 'namaKabupaten' && input.id !== 'namaProvinsi') {
            input.removeAttribute('readonly');
        }
    });
    
    actions.style.display = 'flex';
};

window.cancelEdit = function() {
    const form = document.getElementById('formInformasiDasar');
    const inputs = form.querySelectorAll('input');
    const actions = document.getElementById('formActions');
    
    inputs.forEach(input => {
        input.setAttribute('readonly', true);
    });
    
    actions.style.display = 'none';
    form.reset();
    
    // Reload original data
    if (window.dataUmumInstance) {
        window.dataUmumInstance.loadDataDesa();
    }
};

window.toggleDesaFullscreen = function() {
    const mapContainer = document.querySelector('.peta-desa-container');
    if (!document.fullscreenElement) {
        mapContainer.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
};

window.initDataUmum = function() {
    if (window.dataUmumInstance) {
        delete window.dataUmumInstance;
    }
    window.dataUmumInstance = new window.DataUmum();
};

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.data-umum-container')) {
            window.dataUmumInstance = new window.DataUmum();
        }
    });
} else {
    if (document.querySelector('.data-umum-container')) {
        window.dataUmumInstance = new window.DataUmum();
    }
}
}