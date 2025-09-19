// Peta Admin JavaScript
console.log('🗺️ [SCRIPT] Peta.js script loaded');
console.log('🗺️ [SCRIPT] Current URL:', window.location.href);
console.log('🗺️ [SCRIPT] Document ready state:', document.readyState);

// Clear existing class
if (window.PetaAdminComponent) {
    delete window.PetaAdminComponent;
}

// Use function constructor
window.PetaAdminComponent = function() {
    console.log('🗺️ [INIT] Starting PetaAdminComponent constructor');
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
    this.currentPage = { fasilitas: 1, jalan: 1, jembatan: 1 };
    this.itemsPerPage = 10;
    this.filteredData = { fasilitas: [], jalan: [], jembatan: [] };
    this.isInitialized = false;
    
    // Tibawa coordinates (approximate center)
    this.tibawaCenter = {
        lat: 0.5547, // Approximate latitude for Tibawa
        lng: 123.0581 // Approximate longitude for Tibawa
    };
    
    // Define all methods first before calling init
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
    
    this.setupTableFeatures = function(type, searchId, tableId) {
        var table = document.getElementById(tableId);
        if (!table) return;
        
        var rows = Array.from(table.querySelectorAll('tbody tr'));
        this.pagination[type].filteredRows = rows;
        
        // Setup search
        var searchInput = document.getElementById(searchId);
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                var searchTerm = e.target.value.toLowerCase();
                var filteredRows = rows.filter(function(row) {
                    return row.textContent.toLowerCase().includes(searchTerm);
                });
                
                self.pagination[type].filteredRows = filteredRows;
                self.pagination[type].currentPage = 1;
                self.updateTableDisplay(type, tableId);
            });
        }
        
        self.updateTableDisplay(type, tableId);
    };
    
    this.updateTableDisplay = function(type, tableId) {
        var table = document.getElementById(tableId);
        var pagination = self.pagination[type];
        var currentPage = pagination.currentPage;
        var itemsPerPage = pagination.itemsPerPage;
        var filteredRows = pagination.filteredRows;
        
        var totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = startIndex + itemsPerPage;
        
        // Hide all rows
        var allRows = table.querySelectorAll('tbody tr');
        allRows.forEach(function(row) { row.style.display = 'none'; });
        
        // Show filtered rows for current page
        filteredRows.slice(startIndex, endIndex).forEach(function(row) {
            row.style.display = '';
        });
        
        // Update pagination info
        var startItem = filteredRows.length > 0 ? startIndex + 1 : 0;
        var endItem = Math.min(endIndex, filteredRows.length);
        
        var startEl = document.getElementById(type + 'Start');
        var endEl = document.getElementById(type + 'End');
        var totalEl = document.getElementById(type + 'Total');
        var pageInfoEl = document.getElementById(type + 'PageInfo');
        
        if (startEl) startEl.textContent = startItem;
        if (endEl) endEl.textContent = endItem;
        if (totalEl) totalEl.textContent = filteredRows.length;
        if (pageInfoEl) pageInfoEl.textContent = 'Halaman ' + currentPage + ' dari ' + Math.max(totalPages, 1);
        
        // Update buttons
        var prevBtn = document.getElementById(type + 'Prev');
        var nextBtn = document.getElementById(type + 'Next');
        
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
    };
    
    this.showOnMap = function(type, id) {
        console.log('🎯 [SHOW-ON-MAP] Called with type:', type, 'id:', id);
        
        // Scroll to map first
        document.querySelector('.peta-admin-map-section').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
        
        // Special handling for desa - use boundary method
        if (type === 'desa') {
            var desa = window.petaData.desa.find(function(d) { return d.id_desa == id; });
            if (desa) {
                self.showDesaBoundary(desa.nama_desa);
                return;
            }
        }
        
        // For other types (fasilitas, jalan, jembatan) - use marker method
        var foundMarker = null;
        self.markers.forEach(function(markerData) {
            if (markerData.type === type && markerData.id == id) {
                foundMarker = markerData.marker;
                console.log('🎯 [SHOW-ON-MAP] Found matching marker by ID!');
            }
        });
        
        if (foundMarker) {
            var latLng = foundMarker.getLatLng();
            console.log('🎯 [SHOW-ON-MAP] Moving map to:', latLng.lat, latLng.lng);
            
            // Zoom to marker with animation
            self.map.setView(latLng, 17, {
                animate: true,
                duration: 1
            });
            
            // Wait for zoom animation then open popup
            setTimeout(function() {
                foundMarker.openPopup();
                
                // Highlight marker with pulse effect
                var originalStyle = {
                    fillColor: foundMarker.options.fillColor,
                    color: foundMarker.options.color,
                    weight: foundMarker.options.weight,
                    radius: foundMarker.options.radius
                };
                
                // Pulse animation
                var pulseCount = 0;
                var pulseInterval = setInterval(function() {
                    if (pulseCount % 2 === 0) {
                        foundMarker.setStyle({ 
                            fillColor: '#ff0000', 
                            color: '#ff0000', 
                            weight: 4,
                            radius: originalStyle.radius + 3
                        });
                    } else {
                        foundMarker.setStyle(originalStyle);
                    }
                    pulseCount++;
                    
                    if (pulseCount >= 6) {
                        clearInterval(pulseInterval);
                        foundMarker.setStyle(originalStyle);
                    }
                }, 300);
            }, 1000);
            
            console.log('✅ [SHOW-ON-MAP] Successfully navigated to marker');
        } else {
            console.log('⚠️ [SHOW-ON-MAP] Marker not found, creating temporary marker for ID:', id);
            
            // Try to find data and create temporary marker if coordinates exist
            var targetData = null;
            var lat = null, lng = null;
            
            if (type === 'desa') {
                targetData = window.petaData.desa.find(function(d) { return d.id_desa == id; });
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_latitude);
                    lng = parseFloat(targetData.koordinat_longitude);
                }
            } else if (type === 'fasilitas') {
                targetData = window.petaData.fasilitas.find(function(f) { return f.id_fasilitas == id; });
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_latitude);
                    lng = parseFloat(targetData.koordinat_longitude);
                }
            } else if (type === 'jalan') {
                targetData = window.petaData.jalan.find(function(j) { return j.id_jalan == id; });
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_start_lat);
                    lng = parseFloat(targetData.koordinat_start_lng);
                }
            } else if (type === 'jembatan') {
                targetData = window.petaData.jembatan.find(function(j) { return j.id_jembatan == id; });
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_latitude);
                    lng = parseFloat(targetData.koordinat_longitude);
                }
            }
            
            if (targetData && lat && lng && !isNaN(lat) && !isNaN(lng)) {
                console.log('🎯 [SHOW-ON-MAP] Creating temporary marker at:', lat, lng);
                
                // Create temporary marker
                var tempMarker = L.circleMarker([lat, lng], {
                    radius: 10,
                    fillColor: type === 'fasilitas' ? '#e74c3c' : type === 'jalan' ? '#3498db' : type === 'jembatan' ? '#f39c12' : '#1abc9c',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(self.map);
                
                // Create popup content based on type
                var popupContent = '';
                if (type === 'desa') {
                    popupContent = `
                        <div class="popup-content">
                            <h5>${targetData.nama_desa}</h5>
                            <p><strong>Luas:</strong> ${targetData.luas_wilayah || 'N/A'} ha</p>
                            <p><strong>Dusun:</strong> ${targetData.jumlah_dusun || 0}</p>
                            <p><strong>RT/RW:</strong> ${targetData.jumlah_rt || 0}/${targetData.jumlah_rw || 0}</p>
                        </div>
                    `;
                } else if (type === 'fasilitas') {
                    popupContent = `
                        <div class="popup-content">
                            <h5>${targetData.nama_fasilitas}</h5>
                            <p><strong>Jenis:</strong> ${targetData.jenis_pendidikan}</p>
                            <p><strong>Desa:</strong> ${targetData.nama_desa}</p>
                            <p><strong>Kondisi:</strong> ${targetData.kondisi_bangunan}</p>
                            <p><strong>Kapasitas:</strong> ${targetData.kapasitas_siswa} siswa</p>
                        </div>
                    `;
                } else if (type === 'jalan') {
                    popupContent = `
                        <div class="popup-content">
                            <h5>${targetData.nama_jalan}</h5>
                            <p><strong>Desa:</strong> ${targetData.nama_desa}</p>
                            <p><strong>Panjang:</strong> ${targetData.panjang_jalan} km</p>
                            <p><strong>Kondisi:</strong> ${targetData.kondisi_jalan}</p>
                        </div>
                    `;
                } else if (type === 'jembatan') {
                    popupContent = `
                        <div class="popup-content">
                            <h5>${targetData.nama_jembatan}</h5>
                            <p><strong>Desa:</strong> ${targetData.nama_desa}</p>
                            <p><strong>Panjang:</strong> ${targetData.panjang_jembatan} m</p>
                            <p><strong>Kondisi:</strong> ${targetData.kondisi_jembatan}</p>
                        </div>
                    `;
                }
                
                tempMarker.bindPopup(popupContent);
                
                // Zoom to marker
                self.map.setView([lat, lng], 17, {
                    animate: true,
                    duration: 1
                });
                
                // Open popup after zoom
                setTimeout(function() {
                    tempMarker.openPopup();
                }, 1000);
                
                // Remove temporary marker after 10 seconds
                setTimeout(function() {
                    self.map.removeLayer(tempMarker);
                }, 10000);
                
                console.log('✅ [SHOW-ON-MAP] Created temporary marker successfully');
            } else {
                console.log('❌ [SHOW-ON-MAP] No valid coordinates found');
                alert('Lokasi tidak dapat ditampilkan karena koordinat tidak tersedia.');
            }
        }
    };
    
    this.initMapNow = function() {
        console.log('🗺️ [MAP] Starting initMapNow');
        if (typeof L === 'undefined') {
            console.error('❌ [MAP] Leaflet not available');
            return;
        }
        var mapContainer = document.getElementById('mapContainer');
        if (!mapContainer) {
            console.error('❌ [MAP] Map container not found');
            return;
        }
        if (self.isInitialized) {
            console.log('🗺️ [MAP] Map already initialized');
            return;
        }
        try {
            if (self.map) {
                self.map.remove();
                self.map = null;
            }
            self.map = L.map('mapContainer').setView([self.tibawaCenter.lat, self.tibawaCenter.lng], 12);
            Object.keys(self.layerGroups).forEach(function(key) {
                self.layerGroups[key] = L.layerGroup().addTo(self.map);
            });
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap contributors, © CARTO',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(self.map);
            // Add map controls
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
            
            // Load data
            self.loadAllData();
            setTimeout(function() {
                var loading = document.querySelector('.map-loading');
                if (loading) loading.style.display = 'none';
            }, 500);
            self.isInitialized = true;
            console.log('✅ [MAP] Map initialized successfully');
        } catch (error) {
            console.error('❌ [MAP] Error initializing map:', error);
        }
    };
    
    this.loadLeafletAndInitMap = function() {
        console.log('🗺️ [LEAFLET] Starting loadLeafletAndInitMap');
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
    
    this.init = function() {
        console.log('🗺️ [INIT] Initializing Peta Admin Component');
        self.bindEvents();
        self.loadLeafletAndInitMap();
    };
    
    // Add loadAllData method
    this.loadAllData = function() {
        console.log('🗺️ [DATA] Loading all data');
        
        // Wait for data if not available, but with limit
        if (!window.petaData) {
            if (!self.dataLoadAttempts) self.dataLoadAttempts = 0;
            self.dataLoadAttempts++;
            
            if (self.dataLoadAttempts > 10) {
                console.log('🗺️ [DATA] Max attempts reached, loading from server');
                self.loadDataFromServer();
                return;
            }
            
            setTimeout(function() { self.loadAllData(); }, 100);
            return;
        }
        
        // Load boundaries and markers
        if (window.petaData.desa) {
            window.petaData.desa.forEach(function(desa) {
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
                    }).addTo(self.layerGroups.desa);
                    marker.bindPopup('<h5>' + desa.nama_desa + '</h5>');
                }
            });
        }
        
        // Load facilities
        if (window.petaData.fasilitas) {
            window.petaData.fasilitas.forEach(function(fasilitas) {
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
            });
        }
        
        // Load roads
        if (window.petaData.jalan) {
            window.petaData.jalan.forEach(function(jalan) {
                var lat = parseFloat(jalan.koordinat_start_lat) || self.tibawaCenter.lat + (Math.random() - 0.5) * 0.01;
                var lng = parseFloat(jalan.koordinat_start_lng) || self.tibawaCenter.lng + (Math.random() - 0.5) * 0.01;
                
                var marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#3498db',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(self.layerGroups.jalan);
                marker.bindPopup('<h5>' + jalan.nama_jalan + '</h5>');
            });
        }
        
        // Load bridges
        if (window.petaData.jembatan) {
            window.petaData.jembatan.forEach(function(jembatan) {
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
            });
        }
        
        // Load administrative boundaries
        if (window.BASE_URL) {
            fetch(window.BASE_URL + 'api/administrative-boundaries.php')
                .then(function(response) { return response.json(); })
                .then(function(boundaries) {
                    console.log('🗺️ [BOUNDARIES] Loaded', boundaries.length, 'boundaries');
                    boundaries.forEach(function(boundary) {
                        try {
                            var geojson = JSON.parse(boundary.geojson_data);
                            var color = boundary.boundary_type === 'desa' ? '#1abc9c' : 
                                       boundary.boundary_type === 'kecamatan' ? '#2980b9' : 
                                       boundary.boundary_type === 'kabupaten' ? '#e67e22' : '#8e44ad';
                            
                            L.geoJSON(geojson, {
                                style: {
                                    color: color,
                                    fillColor: color,
                                    fillOpacity: 0.2,
                                    weight: boundary.boundary_type === 'desa' ? 2 : 3
                                }
                            }).addTo(self.layerGroups[boundary.boundary_type]);
                            
                            // Add labels
                            var bounds = L.geoJSON(geojson).getBounds();
                            var center = bounds.getCenter();
                            
                            if (boundary.boundary_type === 'desa' && (boundary.nm_desa || boundary.nm_kelurahan)) {
                                var name = boundary.nm_desa || boundary.nm_kelurahan;
                                L.marker(center, {
                                    icon: L.divIcon({
                                        className: 'desa-label',
                                        html: '<div class="label-text-desa">' + name + '</div>',
                                        iconSize: [80, 16],
                                        iconAnchor: [40, 8]
                                    })
                                }).addTo(self.layerGroups.desa);
                            }
                            
                            if (boundary.boundary_type === 'kecamatan' && boundary.nm_kecamatan) {
                                L.marker(center, {
                                    icon: L.divIcon({
                                        className: 'kecamatan-label',
                                        html: '<div class="label-text">' + boundary.nm_kecamatan + '</div>',
                                        iconSize: [100, 20],
                                        iconAnchor: [50, 10]
                                    })
                                }).addTo(self.layerGroups.kecamatan);
                            }
                        } catch (e) {
                            console.error('Error loading boundary:', e);
                        }
                    });
                })
                .catch(function(error) {
                    console.error('Error fetching boundaries:', error);
                });
        }
        
        // Setup search and pagination after data loaded
        setTimeout(function() {
            self.setupTableFeatures('desa', 'searchDesa', 'desaTable');
            self.setupTableFeatures('fasilitas', 'searchFasilitas', 'fasilitasTable');
            self.setupTableFeatures('jalan', 'searchJalan', 'jalanTable');
            self.setupTableFeatures('jembatan', 'searchJembatan', 'jembatanTable');
        }, 500);
        
        console.log('✅ [DATA] All data loaded');
    };
    
    this.loadDataFromServer = function() {
        console.log('🗺️ [SERVER] Loading data from server');
        
        // Load full page to get PHP data
        fetch(window.BASE_URL + 'pages/admin/peta/peta.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
            // Extract petaData from script
            var scriptMatch = html.match(/window\.petaData = (\{[\s\S]*?\});/);
            if (scriptMatch) {
                try {
                    eval('window.petaData = ' + scriptMatch[1]);
                    window.petaDataReady = true;
                    console.log('🗺️ [SERVER] Data loaded from PHP:', {
                        desa: window.petaData.desa.length,
                        fasilitas: window.petaData.fasilitas.length,
                        jalan: window.petaData.jalan.length,
                        jembatan: window.petaData.jembatan.length
                    });
                    self.dataLoadAttempts = 0;
                    self.loadAllData();
                } catch (e) {
                    console.error('Error parsing PHP data:', e);
                }
            }
        })
        .catch(function(error) {
            console.error('Error loading from API:', error);
        });
    };
    
    console.log('🗺️ [INIT] Constructor completed, calling init()');
    this.init();
    
    this.loadData = function() {
        console.log('🗺️ [DATA] Starting loadData');
        
        // Wait for peta data to be available
        if (!window.petaData) {
            console.log('⏳ [DATA] Waiting for peta data...');
            setTimeout(() => this.loadData(), 100);
            return;
        }
        
        console.log('📊 [DATA] Loading peta data:', {
            desa: window.petaData.desa?.length || 0,
            fasilitas: window.petaData.fasilitas?.length || 0,
            jalan: window.petaData.jalan?.length || 0,
            jembatan: window.petaData.jembatan?.length || 0
        });
        
        // Load village boundaries (simplified polygons)
        console.log('🗺️ [DATA] Loading village boundaries');
        this.loadVillageBoundaries();
        
        // Load facilities
        console.log('🗺️ [DATA] Loading facilities');
        this.loadFacilities();
        
        // Load roads
        console.log('🗺️ [DATA] Loading roads');
        this.loadRoads();
        
        // Load bridges
        console.log('🗺️ [DATA] Loading bridges');
        this.loadBridges();
        
        console.log('✅ [DATA] All data loaded');
    };
    
    this.loadVillageBoundaries = function() {
        console.log('🗺️ [BOUNDARIES] Loading village boundaries...');
        
        // Load administrative boundaries from database
        this.loadAdministrativeBoundaries();
        
        // Create village markers only if they have coordinates
        let addedMarkers = 0;
        window.petaData.desa.forEach((desa, index) => {
            const lat = parseFloat(desa.koordinat_latitude);
            const lng = parseFloat(desa.koordinat_longitude);
            
            // Only add marker if coordinates exist
            if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                console.log(`🗺️ [BOUNDARIES] Adding village marker for ${desa.nama_desa} at [${lat}, ${lng}]`);
                
                const marker = L.circleMarker([lat, lng], {
                    radius: 10,
                    fillColor: '#1abc9c',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(this.layerGroups.desa);
                
                marker.bindPopup(`
                    <div class="popup-content">
                        <h5>${desa.nama_desa}</h5>
                        <p><strong>Luas:</strong> ${desa.luas_wilayah || 'N/A'} ha</p>
                        <p><strong>Dusun:</strong> ${desa.jumlah_dusun || 0}</p>
                        <p><strong>RT/RW:</strong> ${desa.jumlah_rt || 0}/${desa.jumlah_rw || 0}</p>
                    </div>
                `);
                
                this.markers.push({
                    type: 'desa',
                    id: desa.id_desa,
                    marker: marker
                });
                addedMarkers++;
            } else {
                console.log(`⚠️ [BOUNDARIES] Skipping ${desa.nama_desa} - no valid coordinates`);
            }
        });
        
        console.log(`✅ [BOUNDARIES] Added ${addedMarkers} village markers`);
    };
    
    this.loadAdministrativeBoundaries = async function() {
        console.log('🗺️ [ADMIN-BOUNDARIES] Loading administrative boundaries...');
        try {
            const response = await fetch(window.BASE_URL + 'api/administrative-boundaries.php');
            const boundaries = await response.json();
            this.allBoundaries = boundaries;
            
            console.log(`🗺️ [ADMIN-BOUNDARIES] Loaded ${boundaries.length} boundaries`);
            this.renderBoundaries();
        } catch (error) {
            console.error('❌ [ADMIN-BOUNDARIES] Error loading administrative boundaries:', error);
        }
    };
    
    this.loadFacilities = function() {
        console.log('🗺️ [FACILITIES] Loading facilities...');
        let addedFacilities = 0;
        
        window.petaData.fasilitas.forEach(fasilitas => {
            // Use coordinates from database or fallback to village center
            let lat = parseFloat(fasilitas.koordinat_latitude);
            let lng = parseFloat(fasilitas.koordinat_longitude);
            
            if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
                const desa = window.petaData.desa.find(d => d.nama_desa === fasilitas.nama_desa);
                lat = parseFloat(desa?.koordinat_latitude) || this.tibawaCenter.lat;
                lng = parseFloat(desa?.koordinat_longitude) || this.tibawaCenter.lng;
                lat += (Math.random() - 0.5) * 0.01;
                lng += (Math.random() - 0.5) * 0.01;
            }
            
            console.log(`🗺️ [FACILITIES] Adding facility ${fasilitas.nama_fasilitas} at [${lat}, ${lng}]`);
            
            const marker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: '#e74c3c',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(this.layerGroups.fasilitas);
            
            marker.bindPopup(`
                <div class="popup-content">
                    <h5>${fasilitas.nama_fasilitas}</h5>
                    <p><strong>Jenis:</strong> ${fasilitas.jenis_pendidikan}</p>
                    <p><strong>Desa:</strong> ${fasilitas.nama_desa}</p>
                    <p><strong>Kondisi:</strong> ${fasilitas.kondisi_bangunan}</p>
                    <p><strong>Kapasitas:</strong> ${fasilitas.kapasitas_siswa} siswa</p>
                    <p><strong>Guru:</strong> ${fasilitas.jumlah_guru} orang</p>
                </div>
            `);
            
            this.markers.push({
                type: 'fasilitas',
                id: fasilitas.id_fasilitas,
                marker: marker
            });
            addedFacilities++;
        });
        
        console.log(`✅ [FACILITIES] Added ${addedFacilities} facility markers`);
    };
    
    this.renderBoundaries = function() {
        console.log('🗺️ [RENDER] Rendering boundaries...');
        
        // Clear existing boundaries
        this.layerGroups.desa.clearLayers();
        this.layerGroups.kecamatan.clearLayers();
        this.layerGroups.kabupaten.clearLayers();
        this.layerGroups.provinsi.clearLayers();
        
        let boundariesToShow = this.allBoundaries;
        
        if (this.tibawaFilter) {
            console.log('🗺️ [RENDER] Applying Tibawa filter');
            // Filter only Tibawa related boundaries (kd_kecamatan = "004")
            boundariesToShow = this.allBoundaries.filter(boundary => {
                if (boundary.boundary_type === 'kecamatan' || boundary.boundary_type === 'desa') {
                    return boundary.kd_kecamatan === '004';
                }
                return boundary.boundary_type === 'kabupaten' || boundary.boundary_type === 'provinsi';
            });
        }
        
        console.log(`🗺️ [RENDER] Rendering ${boundariesToShow.length} boundaries`);
        
        boundariesToShow.forEach(boundary => {
            if (this.tibawaFilter && (boundary.boundary_type === 'kabupaten' || boundary.boundary_type === 'provinsi')) {
                return; // Skip kabupaten and provinsi when filter is on
            }
            

            
            try {
                const geojson = JSON.parse(boundary.geojson_data);
                const color = this.getBoundaryColor(boundary.boundary_type);
                
                const layer = L.geoJSON(geojson, {
                    style: {
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.2,
                        weight: boundary.boundary_type === 'desa' ? 2 : 3
                    }
                }).addTo(this.layerGroups[boundary.boundary_type]).bindPopup(`
                    <div class="popup-content">
                        <h5>${boundary.nm_desa || boundary.nm_kelurahan || boundary.nm_kecamatan || 'Boundary'}</h5>
                        <p><strong>Tipe:</strong> ${boundary.boundary_type}</p>
                    </div>
                `);
                
                // Add labels for boundaries (skip desa labels)
                if (boundary.boundary_type === 'kecamatan' && boundary.nm_kecamatan) {
                    const bounds = layer.getBounds();
                    const center = bounds.getCenter();
                    
                    L.marker(center, {
                        icon: L.divIcon({
                            className: 'kecamatan-label',
                            html: `<div class="label-text">${boundary.nm_kecamatan}</div>`,
                            iconSize: [100, 20],
                            iconAnchor: [50, 10]
                        })
                    }).addTo(this.layerGroups.kecamatan);
                }
                
                if (boundary.boundary_type === 'kabupaten' && boundary.nm_kabupaten) {
                    const bounds = layer.getBounds();
                    const center = bounds.getCenter();
                    
                    L.marker(center, {
                        icon: L.divIcon({
                            className: 'kabupaten-label',
                            html: `<div class="label-text-kabupaten">${boundary.nm_kabupaten}</div>`,
                            iconSize: [120, 24],
                            iconAnchor: [60, 12]
                        })
                    }).addTo(this.layerGroups.kabupaten);
                }
                
                if (boundary.boundary_type === 'desa' && (boundary.nm_desa || boundary.nm_kelurahan)) {
                    const bounds = layer.getBounds();
                    const center = bounds.getCenter();
                    const name = boundary.nm_desa || boundary.nm_kelurahan;
                    
                    L.marker(center, {
                        icon: L.divIcon({
                            className: 'desa-label',
                            html: `<div class="label-text-desa">${name}</div>`,
                            iconSize: [80, 16],
                            iconAnchor: [40, 8]
                        })
                    }).addTo(this.layerGroups.desa);
                }
            } catch (e) {
                console.error('❌ [RENDER] Error loading boundary:', boundary.nm_desa || boundary.nm_kelurahan || boundary.nm_kecamatan, e);
            }
        });
        
        console.log('✅ [RENDER] Boundaries rendered');
    };
    
    this.getBoundaryColor = function(type) {
        const colors = {
            'provinsi': '#8e44ad',
            'kabupaten': '#e67e22', 
            'kecamatan': '#2980b9',
            'desa': '#1abc9c'
        };
        return colors[type] || '#666';
    };
    
    this.createCircleBoundary = function(lat, lng) {
        return L.circle([lat, lng], {
            color: '#27ae60',
            fillColor: '#27ae60',
            fillOpacity: 0.2,
            weight: 3,
            radius: 1500 // 1.5km radius
        }).addTo(this.map);
    };
    
    this.loadRoads = function() {
        console.log('🗺️ [ROADS] Loading roads...');
        let addedRoads = 0;
        
        window.petaData.jalan.forEach(jalan => {
            // Use center point coordinates
            let lat = parseFloat(jalan.koordinat_start_lat);
            let lng = parseFloat(jalan.koordinat_start_lng);
            
            if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
                const desa = window.petaData.desa.find(d => d.nama_desa === jalan.nama_desa);
                lat = parseFloat(desa?.koordinat_latitude) || this.tibawaCenter.lat;
                lng = parseFloat(desa?.koordinat_longitude) || this.tibawaCenter.lng;
                lat += (Math.random() - 0.5) * 0.01;
                lng += (Math.random() - 0.5) * 0.01;
            }
            
            console.log(`🗺️ [ROADS] Adding road ${jalan.nama_jalan} at [${lat}, ${lng}]`);
            
            const marker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: '#3498db',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(this.layerGroups.jalan);
            
            marker.bindPopup(`
                <div class="popup-content">
                    <h5>${jalan.nama_jalan}</h5>
                    <p><strong>Desa:</strong> ${jalan.nama_desa}</p>
                    <p><strong>Panjang:</strong> ${jalan.panjang_jalan} km</p>
                    <p><strong>Lebar:</strong> ${jalan.lebar_jalan || 'N/A'} m</p>
                    <p><strong>Kondisi:</strong> ${jalan.kondisi_jalan}</p>
                    <p><strong>Permukaan:</strong> ${jalan.jenis_permukaan}</p>
                </div>
            `);
            
            this.markers.push({
                type: 'jalan',
                id: jalan.id_jalan,
                marker: marker
            });
            addedRoads++;
        });
        
        console.log(`✅ [ROADS] Added ${addedRoads} road markers`);
    };
    
    this.loadBridges = function() {
        console.log('🗺️ [BRIDGES] Loading bridges...');
        let addedBridges = 0;
        
        window.petaData.jembatan.forEach(jembatan => {
            // Use coordinates from database or fallback
            let lat = parseFloat(jembatan.koordinat_latitude);
            let lng = parseFloat(jembatan.koordinat_longitude);
            
            if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
                const desa = window.petaData.desa.find(d => d.nama_desa === jembatan.nama_desa);
                lat = parseFloat(desa?.koordinat_latitude) || this.tibawaCenter.lat;
                lng = parseFloat(desa?.koordinat_longitude) || this.tibawaCenter.lng;
                lat += (Math.random() - 0.5) * 0.01;
                lng += (Math.random() - 0.5) * 0.01;
            }
            
            console.log(`🗺️ [BRIDGES] Adding bridge ${jembatan.nama_jembatan} at [${lat}, ${lng}]`);
            
            const marker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: '#f39c12',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(this.layerGroups.jembatan);
            
            marker.bindPopup(`
                <div class="popup-content">
                    <h5>${jembatan.nama_jembatan}</h5>
                    <p><strong>Desa:</strong> ${jembatan.nama_desa}</p>
                    <p><strong>Panjang:</strong> ${jembatan.panjang_jembatan} m</p>
                    <p><strong>Lebar:</strong> ${jembatan.lebar_jembatan || 'N/A'} m</p>
                    <p><strong>Kondisi:</strong> ${jembatan.kondisi_jembatan}</p>
                    <p><strong>Material:</strong> ${jembatan.material_jembatan || 'N/A'}</p>
                </div>
            `);
            
            this.markers.push({
                type: 'jembatan',
                id: jembatan.id_jembatan,
                marker: marker
            });
            addedBridges++;
        });
        
        console.log(`✅ [BRIDGES] Added ${addedBridges} bridge markers`);
    };
    

    
    this.setupTableFeatures = function(type, searchId, tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        this.pagination[type].filteredRows = rows;
        
        // Setup search
        const searchInput = document.getElementById(searchId);
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const filteredRows = rows.filter(row => {
                    return row.textContent.toLowerCase().includes(searchTerm);
                });
                
                this.pagination[type].filteredRows = filteredRows;
                this.pagination[type].currentPage = 1;
                this.updateTableDisplay(type, tableId);
            });
        }
        
        // Setup items per page
        const perPageSelect = document.getElementById(`${type}PerPage`);
        if (perPageSelect) {
            perPageSelect.addEventListener('change', (e) => {
                this.pagination[type].itemsPerPage = parseInt(e.target.value);
                this.pagination[type].currentPage = 1;
                this.updateTableDisplay(type, tableId);
            });
        }
        
        this.updateTableDisplay(type, tableId);
    };
    
    this.updateTableDisplay = function(type, tableId) {
        const table = document.getElementById(tableId);
        const pagination = this.pagination[type];
        const { currentPage, itemsPerPage, filteredRows } = pagination;
        
        const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        // Hide all rows
        const allRows = table.querySelectorAll('tbody tr');
        allRows.forEach(row => row.style.display = 'none');
        
        // Show filtered rows for current page
        filteredRows.slice(startIndex, endIndex).forEach(row => {
            row.style.display = '';
        });
        
        // Update pagination info
        const startItem = filteredRows.length > 0 ? startIndex + 1 : 0;
        const endItem = Math.min(endIndex, filteredRows.length);
        
        document.getElementById(`${type}Start`).textContent = startItem;
        document.getElementById(`${type}End`).textContent = endItem;
        document.getElementById(`${type}Total`).textContent = filteredRows.length;
        document.getElementById(`${type}PageInfo`).textContent = `Halaman ${currentPage} dari ${Math.max(totalPages, 1)}`;
        
        // Update buttons
        const prevBtn = document.getElementById(`${type}Prev`);
        const nextBtn = document.getElementById(`${type}Next`);
        
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
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
    

    
    this.showDesaBoundary = async function(desaName) {
        try {
            const response = await fetch(window.BASE_URL + 'api/administrative-boundaries.php');
            const boundaries = await response.json();
            
            // Find matching boundary (case insensitive)
            const matchingBoundary = boundaries.find(b => {
                if (b.boundary_type !== 'desa') return false;
                const boundaryName = (b.nm_desa || b.nm_kelurahan || '').toLowerCase().trim();
                const searchName = desaName.toLowerCase().trim();
                return boundaryName === searchName;
            });
            
            if (matchingBoundary) {
                const geojson = JSON.parse(matchingBoundary.geojson_data);
                const tempLayer = L.geoJSON(geojson);
                const bounds = tempLayer.getBounds();
                
                // Zoom to boundary
                this.map.fitBounds(bounds, { padding: [20, 20] });
                
                // Highlight boundary temporarily
                const highlightLayer = L.geoJSON(geojson, {
                    style: {
                        color: '#ff0000',
                        weight: 4,
                        fillOpacity: 0.3
                    }
                }).addTo(this.map);
                
                setTimeout(() => {
                    this.map.removeLayer(highlightLayer);
                }, 3000);
            }
        } catch (error) {
            console.error('Error finding boundary:', error);
        }
    };
};

// Global functions for template
function resetMapView() {
    if (window.petaAdmin) {
        petaAdmin.resetMapView();
    }
}

function toggleLayer(layerType) {
    if (window.petaAdmin && window.petaAdmin.layerGroups[layerType]) {
        const layerGroup = petaAdmin.layerGroups[layerType];
        const legendItem = document.querySelector(`[data-layer="${layerType}"]`);
        
        if (petaAdmin.map.hasLayer(layerGroup)) {
            petaAdmin.map.removeLayer(layerGroup);
            legendItem.style.opacity = '0.5';
        } else {
            petaAdmin.map.addLayer(layerGroup);
            legendItem.style.opacity = '1';
        }
    }
}

function showOnMap(type, id) {
    console.log('🔴 [GLOBAL] showOnMap called with:', type, id);
    console.log('🔴 [GLOBAL] petaAdmin exists:', !!window.petaAdmin);
    
    if (window.petaAdmin) {
        console.log('🔴 [GLOBAL] Calling petaAdmin.showOnMap');
        window.petaAdmin.showOnMap(type, id);
    } else {
        console.log('❌ [GLOBAL] petaAdmin not available');
    }
}

function changePage(type, direction) {
    if (window.petaAdmin && window.petaAdmin.pagination) {
        var pagination = window.petaAdmin.pagination[type];
        var totalPages = Math.ceil(pagination.filteredRows.length / pagination.itemsPerPage);
        
        if (direction === 'prev' && pagination.currentPage > 1) {
            pagination.currentPage--;
        } else if (direction === 'next' && pagination.currentPage < totalPages) {
            pagination.currentPage++;
        }
        
        window.petaAdmin.updateTableDisplay(type, type + 'Table');
    }
}

function changeItemsPerPage(type, value) {
    if (window.petaAdmin && window.petaAdmin.pagination) {
        window.petaAdmin.pagination[type].itemsPerPage = parseInt(value);
        window.petaAdmin.pagination[type].currentPage = 1;
        window.petaAdmin.updateTableDisplay(type, type + 'Table');
    }
}

function toggleFullscreen() {
    if (window.petaAdmin) {
        petaAdmin.toggleFullscreen();
    }
}

function toggleTibawaFilter() {
    if (window.petaAdmin) {
        window.petaAdmin.tibawaFilter = !window.petaAdmin.tibawaFilter;
        const btn = document.getElementById('tibawaFilterBtn');
        
        if (btn) {
            if (window.petaAdmin.tibawaFilter) {
                btn.style.backgroundColor = '#3F72AF';
                btn.style.color = 'white';
            } else {
                btn.style.backgroundColor = '';
                btn.style.color = '';
            }
        }
    }
}

// Administrative Boundaries upload functions
function uploadAdministrativeBoundaries() {
    document.getElementById('uploadAdministrativeBoundariesModal').style.display = 'flex';
}

function closeAdministrativeBoundariesModal() {
    document.getElementById('uploadAdministrativeBoundariesModal').style.display = 'none';
    document.getElementById('administrativeBoundariesFile').value = '';
    document.getElementById('administrativeBoundariesUploadStatus').style.display = 'none';
    document.getElementById('boundariesPreviewInfo').style.display = 'none';
}

// Preview GeoJSON when file selected
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('administrativeBoundariesFile');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const geojson = JSON.parse(e.target.result);
                        if (geojson.type === 'FeatureCollection' && geojson.features.length > 0) {
                            const types = {};
                            geojson.features.forEach(feature => {
                                const props = feature.properties || {};
                                // Try to detect boundary type from properties
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
                        console.log('Preview error:', error);
                    }
                };
                reader.readAsText(file);
            }
        });
    }
});

async function processAdministrativeBoundaries() {
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
        
        const response = await fetch(window.BASE_URL + 'pages/admin/peta/upload-administrative-boundaries.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            statusDiv.innerHTML = `Berhasil! ${result.inserted} batas administratif diproses.`;
            statusDiv.style.background = '#d4edda';
            statusDiv.style.color = '#155724';
            
            setTimeout(() => {
                closeAdministrativeBoundariesModal();
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

// Initialize when both DOM and data are ready
function initializePetaAdmin() {
    console.log('🗺️ [GLOBAL] Checking initialization conditions...');
    console.log('🗺️ [GLOBAL] Container exists:', !!document.querySelector('.peta-admin-container'));
    console.log('🗺️ [GLOBAL] Peta data exists:', !!window.petaData);
    console.log('🗺️ [GLOBAL] Peta data ready:', !!window.petaDataReady);
    console.log('🗺️ [GLOBAL] Already initialized:', !!window.petaAdmin);
    
    if (document.querySelector('.peta-admin-container') && window.petaData && window.petaDataReady && !window.petaAdmin) {
        console.log('✅ [GLOBAL] All conditions met, initializing Peta Admin...');
        window.petaAdmin = new window.PetaAdminComponent();
        return true;
    }
    return false;
}

// Wait for both DOM and data with timeout
function waitForPetaReady(attempts = 0) {
    const maxAttempts = 100; // 5 seconds max
    
    console.log(`🗺️ [GLOBAL] Waiting for peta ready... (attempt ${attempts + 1})`);
    
    if (attempts >= maxAttempts) {
        console.error('❌ [GLOBAL] Timeout waiting for peta data');
        return;
    }
    
    if (initializePetaAdmin()) {
        console.log('✅ [GLOBAL] Peta Admin initialized successfully');
        return;
    }
    
    setTimeout(() => waitForPetaReady(attempts + 1), 50);
}

// Force re-initialization for AJAX loads
function forceInitializePeta() {
    console.log('🗺️ [GLOBAL] Force initializing peta...');
    
    // Clear existing instance
    if (window.petaAdmin) {
        console.log('🗺️ [GLOBAL] Destroying existing instance');
        if (window.petaAdmin.map) {
            window.petaAdmin.map.remove();
        }
        window.petaAdmin = null;
    }
    
    // Reset data flags
    window.petaDataReady = false;
    
    // Wait for data to be set by PHP, then initialize
    setTimeout(() => {
        console.log('🗺️ [GLOBAL] Starting forced initialization');
        waitForPetaReady();
    }, 200);
}

// Only initialize if not AJAX load
if (!document.querySelector('.peta-admin-container') || window.petaDataReady) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForPetaReady);
    } else {
        waitForPetaReady();
    }
}

// FIXED: For AJAX navigation
window.initializePetaIfNeeded = function() {
    console.log('🗺️ [GLOBAL] initializePetaIfNeeded called');
    console.log('🗺️ [GLOBAL] Container exists:', !!document.querySelector('.peta-admin-container'));
    console.log('🗺️ [GLOBAL] Peta data exists:', !!window.petaData);
    console.log('🗺️ [GLOBAL] Peta data ready:', !!window.petaDataReady);
    
    // Clear existing instance
    if (window.petaAdmin) {
        if (window.petaAdmin.map) {
            window.petaAdmin.map.remove();
        }
        window.petaAdmin = null;
    }
    
    // Wait for container and data, then initialize
    setTimeout(function() {
        console.log('🗺️ [GLOBAL] Checking again - Container:', !!document.querySelector('.peta-admin-container'), 'Data:', !!window.petaData, 'Ready:', !!window.petaDataReady);
        if (document.querySelector('.peta-admin-container')) {
            console.log('🗺️ [GLOBAL] Creating new PetaAdminComponent');
            window.petaAdmin = new window.PetaAdminComponent();
        }
    }, 500);
};

// Add custom marker styles
const style = document.createElement('style');
style.textContent = `
    .custom-marker {
        background: white;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: white;
    }
    
    .facility-marker {
        background: #e74c3c;
    }
    
    .bridge-marker {
        background: #f39c12;
    }
    

    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    .popup-content h5 {
        color: #112D4E;
        margin-bottom: 12px;
        font-weight: 600;
    }
    
    .popup-content p {
        margin: 4px 0;
        font-size: 14px;
    }
    
    .kecamatan-label {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .label-text {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #2980b9;
        border-radius: 4px;
        padding: 4px 8px;
        font-weight: bold;
        font-size: 12px;
        color: #2980b9;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .label-text-kabupaten {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #e67e22;
        border-radius: 4px;
        padding: 6px 10px;
        font-weight: bold;
        font-size: 14px;
        color: #e67e22;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .label-text-desa {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #1abc9c;
        border-radius: 3px;
        padding: 2px 6px;
        font-weight: bold;
        font-size: 10px;
        color: #1abc9c;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    
    .kabupaten-label, .desa-label {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
    }
`;
document.head.appendChild(style);

// Add label styles
var labelStyle = document.createElement('style');
labelStyle.textContent = '.label-text { background: rgba(255,255,255,0.9); border: 2px solid #2980b9; border-radius: 4px; padding: 4px 8px; font-weight: bold; font-size: 12px; color: #2980b9; text-align: center; white-space: nowrap; box-shadow: 0 2px 4px rgba(0,0,0,0.2); } .kecamatan-label { background: none !important; border: none !important; box-shadow: none !important; }';
document.head.appendChild(labelStyle);