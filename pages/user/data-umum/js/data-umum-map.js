// Data Umum Map Functions
(function() {
    'use strict';
    
    class DataUmumMap {
        constructor() {
            this.currentMap = null;
            this.currentMarker = null;
            this.desaMap = null;
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
                    const baseUrl = window.DataUmumUtils ? window.DataUmumUtils.getBaseUrl() : '/Pendataan-desa/';
                    const response = await fetch(`${baseUrl}api/router.php?endpoint=administrative-boundaries`);
                    
                    if (response.status === 401) {
                        this.loadDesaData(document.getElementById('namaDesa').value);
                        return;
                    }
                    
                    const boundaries = await response.json();
                    const boundariesData = boundaries.success ? boundaries.data : boundaries;
                    
                    if (!Array.isArray(boundariesData) || boundariesData.length === 0) {
                        this.loadDesaData(document.getElementById('namaDesa').value);
                        return;
                    }
                    
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
                } catch (boundaryError) {
                    // Boundary loading failed, continue without boundaries
                }
                
                this.loadDesaData(document.getElementById('namaDesa').value);
            } catch (error) {
                // Map creation failed
            }
        }
        
        async loadDesaData(desaName) {
            try {
                const baseUrl = window.DataUmumUtils ? window.DataUmumUtils.getBaseUrl() : '/Pendataan-desa/';
                
                // Load facilities
                const fasilitasResponse = await fetch(`${baseUrl}api/router.php?endpoint=fasilitas-pendidikan`);
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
                
                // Load roads - data loaded but not displayed on map
                const jalanResponse = await fetch(`${baseUrl}api/router.php?endpoint=infrastruktur-jalan`);
                const jalanResult = await jalanResponse.json();
                
                // Load bridges
                const jembatanResponse = await fetch(`${baseUrl}api/router.php?endpoint=infrastruktur-jembatan`);
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
                // Error loading village data
            }
        }
    }
    
    // Initialize map instance
    window.DataUmumMap = new DataUmumMap();
})();