// Peta Boundary Functions
(function() {
    'use strict';
    
    class PetaBoundary {
        constructor() {
            if (document.querySelector('.peta-admin-container')) {
                this.init();
            }
        }
        
        init() {
            // Boundary management functions
        }
        
        renderBoundaries() {
            if (!window.petaAdmin || !window.petaAdmin.allBoundaries || !Array.isArray(window.petaAdmin.allBoundaries)) {
                return;
            }
            
            window.petaAdmin.layerGroups.kecamatan.clearLayers();
            window.petaAdmin.layerGroups.kabupaten.clearLayers();
            window.petaAdmin.layerGroups.provinsi.clearLayers();
            window.petaAdmin.layerGroups.desa.clearLayers();
            
            let boundariesToShow = window.petaAdmin.allBoundaries;
            
            if (window.petaAdmin.tibawaFilter) {
                boundariesToShow = window.petaAdmin.allBoundaries.filter(boundary => {
                    const kecamatanName = (boundary.nm_kecamatan || '').toLowerCase();
                    const kecamatanKode = boundary.kd_kecamatan || '';
                    return kecamatanName.includes('tibawa') || kecamatanKode === '004';
                });
            }
            
            boundariesToShow.forEach(boundary => {
                try {
                    if (!boundary.geojson_data) return;
                    
                    const geojson = JSON.parse(boundary.geojson_data);
                    const color = this.getBoundaryColor(boundary.boundary_type);
                    
                    const layer = L.geoJSON(geojson, {
                        style: {
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.2,
                            weight: boundary.boundary_type === 'desa' ? 2 : 3
                        },
                        pane: 'boundaryPane'
                    }).addTo(window.petaAdmin.layerGroups[boundary.boundary_type]).bindPopup(`
                        <div class="popup-content">
                            <h5>${boundary.nm_desa || boundary.nm_kelurahan || boundary.nm_kecamatan || 'Boundary'}</h5>
                            <p><strong>Tipe:</strong> ${boundary.boundary_type}</p>
                        </div>
                    `);
                    
                    this.addBoundaryLabel(layer, boundary);
                } catch (e) {
                    // Continue with next boundary
                }
            });
            
            this.reloadVillageMarkers();
        }
        
        addBoundaryLabel(layer, boundary) {
            const bounds = layer.getBounds();
            const center = bounds.getCenter();
            
            if (boundary.boundary_type === 'kecamatan' && boundary.nm_kecamatan) {
                L.marker(center, {
                    icon: L.divIcon({
                        className: 'kecamatan-label',
                        html: `<div class="label-text">${boundary.nm_kecamatan}</div>`,
                        iconSize: [100, 20],
                        iconAnchor: [50, 10]
                    })
                }).addTo(window.petaAdmin.layerGroups.kecamatan);
            }
            
            if (boundary.boundary_type === 'kabupaten' && boundary.nm_kabupaten) {
                L.marker(center, {
                    icon: L.divIcon({
                        className: 'kabupaten-label',
                        html: `<div class="label-text-kabupaten">${boundary.nm_kabupaten}</div>`,
                        iconSize: [120, 24],
                        iconAnchor: [60, 12]
                    })
                }).addTo(window.petaAdmin.layerGroups.kabupaten);
            }
            
            if (boundary.boundary_type === 'desa' && (boundary.nm_desa || boundary.nm_kelurahan)) {
                const name = boundary.nm_desa || boundary.nm_kelurahan;
                L.marker(center, {
                    icon: L.divIcon({
                        className: 'desa-label',
                        html: `<div class="label-text-desa">${name}</div>`,
                        iconSize: [80, 16],
                        iconAnchor: [40, 8]
                    })
                }).addTo(window.petaAdmin.layerGroups.desa);
            }
        }
        
        reloadVillageMarkers() {
            if (window.petaData && window.petaData.desa) {
                window.petaData.desa.forEach((desa) => {
                    const lat = parseFloat(desa.koordinat_latitude);
                    const lng = parseFloat(desa.koordinat_longitude);
                    
                    if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                        const existingMarker = window.petaAdmin.markers.find(m => m.type === 'desa' && m.id == desa.id_desa);
                        if (!existingMarker) {
                            const kecamatanName = (desa.nama_kecamatan || '').toLowerCase();
                            const isTibawaKecamatan = kecamatanName.includes('tibawa');
                            
                            if (!window.petaAdmin.tibawaFilter || isTibawaKecamatan) {
                                const marker = L.circleMarker([lat, lng], {
                                    radius: 10,
                                    fillColor: '#1abc9c',
                                    color: '#fff',
                                    weight: 2,
                                    opacity: 1,
                                    fillOpacity: 0.8
                                }).addTo(window.petaAdmin.layerGroups.desa);
                                
                                marker.bindPopup(`
                                    <div class="popup-content">
                                        <h5>${desa.nama_desa}</h5>
                                        <p><strong>Luas:</strong> ${desa.luas_wilayah || 'N/A'} ha</p>
                                        <p><strong>Dusun:</strong> ${desa.jumlah_dusun || 0}</p>
                                        <p><strong>RT/RW:</strong> ${desa.jumlah_rt || 0}/${desa.jumlah_rw || 0}</p>
                                    </div>
                                `);
                                
                                window.petaAdmin.markers.push({
                                    type: 'desa',
                                    id: desa.id_desa,
                                    marker: marker
                                });
                            }
                        }
                    }
                });
            }
        }
        
        getBoundaryColor(type) {
            const colors = {
                'provinsi': '#8e44ad',
                'kabupaten': '#e67e22', 
                'kecamatan': '#2980b9',
                'desa': '#1abc9c'
            };
            return colors[type] || '#666';
        }
        
        async showDesaBoundary(desaName) {
            try {
                const response = await fetch(window.BASE_URL + 'pages/admin/peta/peta.php?endpoint=administrative-boundaries');
                const result = await response.json(); 
                const boundaries = result.success ? result.data : [];
                
                const matchingBoundary = boundaries.find(b => {
                    if (b.boundary_type !== 'desa') return false;
                    const boundaryName = (b.nm_desa || b.nm_kelurahan || '').toLowerCase().trim();
                    const kecamatanKode = b.kd_kecamatan || '';
                    const searchName = desaName.toLowerCase().trim();
                    return boundaryName === searchName && kecamatanKode === '004';
                });
                
                if (matchingBoundary) {
                    const geojson = JSON.parse(matchingBoundary.geojson_data);
                    const tempLayer = L.geoJSON(geojson);
                    const bounds = tempLayer.getBounds();
                    
                    window.petaAdmin.map.fitBounds(bounds, { padding: [20, 20] });
                    
                    const highlightLayer = L.geoJSON(geojson, {
                        style: {
                            color: '#ff0000',
                            fillColor: '#ff0000',
                            weight: 4,
                            fillOpacity: 0.4,
                            opacity: 1
                        }
                    }).addTo(window.petaAdmin.map);
                    
                    setTimeout(() => {
                        window.petaAdmin.map.removeLayer(highlightLayer);
                    }, 5000);
                } else {
                    alert('Batas desa tidak ditemukan di peta.');
                }
            } catch (error) {
                // Silent error handling
            }
        }
    }
    
    // Global functions
    window.toggleLayer = function(layerType) {
        if (window.petaAdmin && window.petaAdmin.layerGroups[layerType]) {
            const layerGroup = window.petaAdmin.layerGroups[layerType];
            const legendItem = document.querySelector(`[data-layer="${layerType}"]`);
            
            if (window.petaAdmin.map.hasLayer(layerGroup)) {
                window.petaAdmin.map.removeLayer(layerGroup);
                legendItem.style.opacity = '0.5';
            } else {
                window.petaAdmin.map.addLayer(layerGroup);
                legendItem.style.opacity = '1';
            }
        }
    };
    
    window.toggleTibawaFilter = function() {
        if (!window.petaAdmin) return;
        
        if (!window.petaAdmin.allBoundaries || window.petaAdmin.allBoundaries.length === 0) {
            fetch(window.BASE_URL + 'pages/admin/peta/peta.php?endpoint=administrative-boundaries')
                .then(response => response.json())
                .then(result => {
                    window.petaAdmin.allBoundaries = result.success ? result.data : [];
                    window.toggleTibawaFilter();
                })
                .catch(error => {
                    window.petaAdmin.allBoundaries = [];
                });
            return;
        }
        
        window.petaAdmin.tibawaFilter = !window.petaAdmin.tibawaFilter;
        
        const btn = document.querySelector('.map-control-btn[onclick="toggleTibawaFilter()"]');
        
        if (btn) {
            if (window.petaAdmin.tibawaFilter) {
                btn.style.backgroundColor = '#3F72AF';
                btn.style.color = 'white';
            } else {
                btn.style.backgroundColor = '';
                btn.style.color = '';
            }
        }
        
        // Clear all layers
        Object.keys(window.petaAdmin.layerGroups).forEach(key => {
            window.petaAdmin.layerGroups[key].clearLayers();
        });
        window.petaAdmin.markers = [];
        
        // Reload boundaries and markers
        if (window.PetaBoundary) {
            window.PetaBoundary.renderBoundaries();
        }
        
        // Reload village markers
        if (window.petaData && window.petaData.desa) {
            window.petaData.desa.forEach(desa => {
                var lat = parseFloat(desa.koordinat_latitude);
                var lng = parseFloat(desa.koordinat_longitude);
                
                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    const kecamatanName = (desa.nama_kecamatan || '').toLowerCase();
                    const isTibawaKecamatan = kecamatanName.includes('tibawa');
                    
                    if (!window.petaAdmin.tibawaFilter || isTibawaKecamatan) {
                        var marker = L.circleMarker([lat, lng], {
                            radius: 10,
                            fillColor: '#1abc9c',
                            color: '#fff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.8
                        }).addTo(window.petaAdmin.layerGroups.desa);
                        marker.bindPopup('<h5>' + desa.nama_desa + '</h5>');
                        
                        window.petaAdmin.markers.push({
                            type: 'desa',
                            id: desa.id_desa,
                            marker: marker
                        });
                    }
                }
            });
        }
        
        // Reload other markers
        if (window.petaData && window.petaData.fasilitas) {
            window.petaData.fasilitas.forEach(fasilitas => {
                var lat = parseFloat(fasilitas.koordinat_latitude) || window.petaAdmin.tibawaCenter.lat + (Math.random() - 0.5) * 0.01;
                var lng = parseFloat(fasilitas.koordinat_longitude) || window.petaAdmin.tibawaCenter.lng + (Math.random() - 0.5) * 0.01;
                
                var marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#e74c3c',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(window.petaAdmin.layerGroups.fasilitas);
                marker.bindPopup('<h5>' + fasilitas.nama_fasilitas + '</h5>');
                
                window.petaAdmin.markers.push({
                    type: 'fasilitas',
                    id: fasilitas.id_fasilitas,
                    marker: marker
                });
            });
        }
        
        if (window.petaData && window.petaData.jembatan) {
            window.petaData.jembatan.forEach(jembatan => {
                var lat = parseFloat(jembatan.koordinat_latitude) || window.petaAdmin.tibawaCenter.lat + (Math.random() - 0.5) * 0.01;
                var lng = parseFloat(jembatan.koordinat_longitude) || window.petaAdmin.tibawaCenter.lng + (Math.random() - 0.5) * 0.01;
                
                var marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#f39c12',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(window.petaAdmin.layerGroups.jembatan);
                marker.bindPopup('<h5>' + jembatan.nama_jembatan + '</h5>');
                
                window.petaAdmin.markers.push({
                    type: 'jembatan',
                    id: jembatan.id_jembatan,
                    marker: marker
                });
            });
        }
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.PetaBoundary) {
            window.PetaBoundary = new PetaBoundary();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.PetaBoundary) {
            window.PetaBoundary = new PetaBoundary();
        }
    }
})();