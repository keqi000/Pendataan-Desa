// Peta Core Functions
(function() {
    'use strict';
    
    if (window.PetaAdminComponent) {
        delete window.PetaAdminComponent;
    }

    window.PetaAdminComponent = function() {
        this.map = null;
        this.markers = [];
        this.layers = {};
        this.layerGroups = {
            fasilitas: null,
            jalan: null,
            jembatan: null,
            desa: null,
            kecamatan: null,
            kabupaten: null,
            provinsi: null
        };
        this.tibawaFilter = false;
        this.allBoundaries = [];
        this.isInitialized = false;
        
        this.tibawaCenter = {
            lat: 0.5547,
            lng: 123.0581
        };
        
        var self = this;
        
        this.initSearchAndPagination = function() {
            this.pagination = {
                desa: { currentPage: 1, itemsPerPage: 10, filteredRows: [] },
                fasilitas: { currentPage: 1, itemsPerPage: 10, filteredRows: [] },
                jalan: { currentPage: 1, itemsPerPage: 10, filteredRows: [] },
                jembatan: { currentPage: 1, itemsPerPage: 10, filteredRows: [] }
            };
        };
        
        this.bindEvents = function() {
            this.initSearchAndPagination();
        };
        
        this.initMapNow = function() {
            if (typeof L === 'undefined') {
                return;
            }
            var mapContainer = document.getElementById('mapContainer');
            if (!mapContainer) {
                return;
            }
            
            self.isInitialized = false;
            
            try {
                if (self.map) {
                    self.map.remove();
                    self.map = null;
                }
                
                var loading = document.querySelector('.map-loading');
                if (loading) loading.style.display = 'none';
                
                self.map = L.map('mapContainer').setView([self.tibawaCenter.lat, self.tibawaCenter.lng], 12);
                
                self.map.createPane('boundaryPane');
                self.map.getPane('boundaryPane').style.zIndex = 200;
                self.map.createPane('facilityPane');
                self.map.getPane('facilityPane').style.zIndex = 400;
                
                Object.keys(self.layerGroups).forEach(function(key) {
                    self.layerGroups[key] = L.layerGroup().addTo(self.map);
                });
                
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
                    attribution: '© OpenStreetMap contributors, © CARTO',
                    subdomains: 'abcd',
                    maxZoom: 19
                }).addTo(self.map);
                
                var controlsDiv = document.createElement('div');
                controlsDiv.className = 'map-controls';
                controlsDiv.innerHTML = `
                    <button class="map-control-btn" onclick="resetMapView()" title="Reset View">
                        <i class="fas fa-home"></i>
                    </button>
                    <button class="map-control-btn" onclick="toggleTibawaFilter()" title="Filter Tibawa">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="map-control-btn" onclick="toggleFullscreen()" title="Fullscreen">
                        <i class="fas fa-expand"></i>
                    </button>
                `;
                document.querySelector('.map-container').appendChild(controlsDiv);
                
                if (window.petaData) {
                    self.loadDataDirectly();
                } else if (window.PetaData) {
                    window.PetaData.loadAllData();
                }
                
                self.isInitialized = true;
            } catch (error) {
                // Silent error handling
            }
        };
        
        this.loadLeafletAndInitMap = function() {
            if (typeof L !== 'undefined') {
                self.initMapNow();
                return;
            }
            var leafletJS = document.createElement('script');
            leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            leafletJS.onload = function() {
                setTimeout(function() { self.initMapNow(); }, 100);
            };
            document.head.appendChild(leafletJS);
        };
        
        this.resetMapView = function() {
            this.map.setView([this.tibawaCenter.lat, this.tibawaCenter.lng], 12);
        };
        
        this.toggleFullscreen = function() {
            const mapContainer = document.querySelector('.map-container');
            if (!document.fullscreenElement) {
                mapContainer.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        };
        
        this.loadDataDirectly = function() {
            if (window.petaData.desa) {
                window.petaData.desa.forEach(desa => {
                    var lat = parseFloat(desa.koordinat_latitude);
                    var lng = parseFloat(desa.koordinat_longitude);
                    
                    if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                        // Filter markers based on Tibawa filter
                        const kecamatanName = (desa.nama_kecamatan || '').toLowerCase();
                        const isTibawaKecamatan = kecamatanName.includes('tibawa');
                        
                        if (!self.tibawaFilter || isTibawaKecamatan) {
                            var marker = L.circleMarker([lat, lng], {
                                radius: 10,
                                fillColor: '#1abc9c',
                                color: '#fff',
                                weight: 2,
                                opacity: 1,
                                fillOpacity: 0.8
                            }).addTo(self.layerGroups.desa);
                            marker.bindPopup('<h5>' + desa.nama_desa + '</h5>');
                            
                            self.markers.push({
                                type: 'desa',
                                id: desa.id_desa,
                                marker: marker
                            });
                        }
                    }
                });
            }
            
            if (window.petaData.fasilitas) {
                window.petaData.fasilitas.forEach(fasilitas => {
                    var lat = parseFloat(fasilitas.koordinat_latitude) || self.tibawaCenter.lat + (Math.random() - 0.5) * 0.01;
                    var lng = parseFloat(fasilitas.koordinat_longitude) || self.tibawaCenter.lng + (Math.random() - 0.5) * 0.01;
                    
                    var marker = L.circleMarker([lat, lng], {
                        radius: 8,
                        fillColor: '#e74c3c',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(self.layerGroups.fasilitas);
                    marker.bindPopup('<h5>' + fasilitas.nama_fasilitas + '</h5>');
                    
                    self.markers.push({
                        type: 'fasilitas',
                        id: fasilitas.id_fasilitas,
                        marker: marker
                    });
                });
            }
            
            if (window.petaData.jembatan) {
                window.petaData.jembatan.forEach(jembatan => {
                    var lat = parseFloat(jembatan.koordinat_latitude) || self.tibawaCenter.lat + (Math.random() - 0.5) * 0.01;
                    var lng = parseFloat(jembatan.koordinat_longitude) || self.tibawaCenter.lng + (Math.random() - 0.5) * 0.01;
                    
                    var marker = L.circleMarker([lat, lng], {
                        radius: 8,
                        fillColor: '#f39c12',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(self.layerGroups.jembatan);
                    marker.bindPopup('<h5>' + jembatan.nama_jembatan + '</h5>');
                    
                    self.markers.push({
                        type: 'jembatan',
                        id: jembatan.id_jembatan,
                        marker: marker
                    });
                });
            }
            
            self.loadBoundariesDirectly();
        };
        
        this.loadBoundariesDirectly = function() {
            fetch(window.BASE_URL + 'pages/admin/peta/peta.php?endpoint=administrative-boundaries')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        self.allBoundaries = result.data;
                        if (window.PetaBoundary) {
                            window.PetaBoundary.renderBoundaries();
                        }
                    } else {
                        self.allBoundaries = [];
                    }
                })
                .catch(error => {
                    self.allBoundaries = [];
                });
        };
        
        this.init = function() {
            self.bindEvents();
            self.loadLeafletAndInitMap();
        };
        
        this.init = function() {
            self.bindEvents();
            self.loadLeafletAndInitMap();
        };
        
        this.init();
    };
    
    // Global functions
    window.resetMapView = function() {
        if (window.petaAdmin) {
            petaAdmin.resetMapView();
        }
    };
    
    window.toggleFullscreen = function() {
        if (window.petaAdmin) {
            petaAdmin.toggleFullscreen();
        }
    };
    
    window.initializePetaIfNeeded = function() {
        if (window.petaAdmin) {
            if (window.petaAdmin.map) {
                window.petaAdmin.map.remove();
            }
            window.petaAdmin = null;
        }
        
        if (document.querySelector('.peta-admin-container')) {
            window.petaAdmin = new window.PetaAdminComponent();
        }
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.peta-admin-container')) {
            window.petaAdmin = new window.PetaAdminComponent();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (document.querySelector('.peta-admin-container')) {
            window.petaAdmin = new window.PetaAdminComponent();
        }
    }
})();