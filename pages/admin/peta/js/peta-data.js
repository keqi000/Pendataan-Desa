// Peta Data Functions
(function() {
    'use strict';
    
    class PetaData {
        constructor() {
            if (document.querySelector('.peta-admin-container')) {
                this.init();
            }
        }
        
        init() {
            // Data loading functions
        }
        
        loadAllData() {
            if (!window.petaData) {
                this.loadDataFromServer();
                return;
            }
            
            this.loadDesaMarkers();
            this.loadFasilitasMarkers();
            this.loadJembatanMarkers();
            this.loadAdministrativeBoundaries();
            
            setTimeout(() => {
                if (window.PetaTable) {
                    window.PetaTable.setupAllTables();
                }
            }, 100);
        }
        
        loadDataFromServer() {
            fetch(window.BASE_URL + 'pages/admin/peta/peta.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                var scriptMatch = html.match(/window\.petaData = (\{[\s\S]*?\});/);
                if (scriptMatch) {
                    try {
                        eval('window.petaData = ' + scriptMatch[1]);
                        window.petaDataReady = true;
                        this.loadAllData();
                    } catch (e) {
                        // Silent error handling
                    }
                }
            })
            .catch(error => {
                // Silent error handling
            });
        }
        
        loadDesaMarkers() {
            if (!window.petaAdmin || !window.petaData || !window.petaData.desa) return;
            
            window.petaData.desa.forEach(desa => {
                var lat = parseFloat(desa.koordinat_latitude);
                var lng = parseFloat(desa.koordinat_longitude);
                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
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
            });
        }
        
        loadFasilitasMarkers() {
            if (window.petaData.fasilitas) {
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
        }
        
        loadJembatanMarkers() {
            if (window.petaData.jembatan) {
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
        }
        
        async loadAdministrativeBoundaries() {
            try {
                const response = await fetch(window.BASE_URL + 'pages/admin/peta/peta.php?endpoint=administrative-boundaries');
                const result = await response.json();
                const boundaries = result.success ? result.data : [];
                window.petaAdmin.allBoundaries = boundaries;
                if (window.PetaBoundary) {
                    window.PetaBoundary.renderBoundaries();
                }
            } catch (error) {
                window.petaAdmin.allBoundaries = [];
            }
        }
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.PetaData) {
            window.PetaData = new PetaData();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.PetaData) {
            window.PetaData = new PetaData();
        }
    }
})();