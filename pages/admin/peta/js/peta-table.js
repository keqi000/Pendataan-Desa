// Peta Table Functions
(function() {
    'use strict';
    
    class PetaTable {
        constructor() {
            if (document.querySelector('.peta-admin-container')) {
                this.init();
            }
        }
        
        init() {
            // Table management functions
        }
        
        setupAllTables() {
            this.setupTableFeatures('desa', 'searchDesa', 'desaTable');
            this.setupTableFeatures('fasilitas', 'searchFasilitas', 'fasilitasTable');
            this.setupTableFeatures('jalan', 'searchJalan', 'jalanTable');
            this.setupTableFeatures('jembatan', 'searchJembatan', 'jembatanTable');
        }
        
        setupTableFeatures(type, searchId, tableId) {
            var table = document.getElementById(tableId);
            if (!table) return;
            
            var rows = Array.from(table.querySelectorAll('tbody tr'));
            window.petaAdmin.pagination[type].filteredRows = rows;
            
            var searchInput = document.getElementById(searchId);
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    var searchTerm = e.target.value.toLowerCase();
                    var filteredRows = rows.filter(row => {
                        return row.textContent.toLowerCase().includes(searchTerm);
                    });
                    
                    window.petaAdmin.pagination[type].filteredRows = filteredRows;
                    window.petaAdmin.pagination[type].currentPage = 1;
                    this.updateTableDisplay(type, tableId);
                });
            }
            
            this.updateTableDisplay(type, tableId);
        }
        
        updateTableDisplay(type, tableId) {
            var table = document.getElementById(tableId);
            var pagination = window.petaAdmin.pagination[type];
            var currentPage = pagination.currentPage;
            var itemsPerPage = pagination.itemsPerPage;
            var filteredRows = pagination.filteredRows;
            
            var totalPages = Math.ceil(filteredRows.length / itemsPerPage);
            var startIndex = (currentPage - 1) * itemsPerPage;
            var endIndex = startIndex + itemsPerPage;
            
            var allRows = table.querySelectorAll('tbody tr');
            allRows.forEach(row => row.style.display = 'none');
            
            filteredRows.slice(startIndex, endIndex).forEach(row => {
                row.style.display = '';
            });
            
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
            
            var prevBtn = document.getElementById(type + 'Prev');
            var nextBtn = document.getElementById(type + 'Next');
            
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
        }
        
        showOnMap(type, id) {
            document.querySelector('.peta-admin-map-section').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
            
            if (type === 'desa') {
                var desa = window.petaData.desa.find(d => d.id_desa == id);
                if (desa && window.PetaBoundary) {
                    window.PetaBoundary.showDesaBoundary(desa.nama_desa);
                    return;
                }
            }
            
            var foundMarker = null;
            window.petaAdmin.markers.forEach(markerData => {
                if (markerData.type === type && markerData.id == id) {
                    foundMarker = markerData.marker;
                }
            });
            
            if (foundMarker) {
                var latLng = foundMarker.getLatLng();
                
                window.petaAdmin.map.setView(latLng, 15, {
                    animate: true,
                    duration: 1
                });
                
                setTimeout(() => {
                    foundMarker.openPopup();
                    this.pulseMarker(foundMarker);
                }, 1000);
            } else {
                this.createTemporaryMarker(type, id);
            }
        }
        
        pulseMarker(marker) {
            var originalStyle = {
                fillColor: marker.options.fillColor,
                color: marker.options.color,
                weight: marker.options.weight,
                radius: marker.options.radius
            };
            
            var pulseCount = 0;
            var pulseInterval = setInterval(() => {
                if (pulseCount % 2 === 0) {
                    marker.setStyle({ 
                        fillColor: '#ff0000', 
                        color: '#ff0000', 
                        weight: 4,
                        radius: originalStyle.radius + 3
                    });
                } else {
                    marker.setStyle(originalStyle);
                }
                pulseCount++;
                
                if (pulseCount >= 6) {
                    clearInterval(pulseInterval);
                    marker.setStyle(originalStyle);
                }
            }, 300);
        }
        
        createTemporaryMarker(type, id) {
            var targetData = null;
            var lat = null, lng = null;
            
            if (type === 'desa') {
                targetData = window.petaData.desa.find(d => d.id_desa == id);
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_latitude);
                    lng = parseFloat(targetData.koordinat_longitude);
                }
            } else if (type === 'fasilitas') {
                targetData = window.petaData.fasilitas.find(f => f.id_fasilitas == id);
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_latitude);
                    lng = parseFloat(targetData.koordinat_longitude);
                }
            } else if (type === 'jalan') {
                targetData = window.petaData.jalan.find(j => j.id_jalan == id);
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_start_lat);
                    lng = parseFloat(targetData.koordinat_start_lng);
                }
            } else if (type === 'jembatan') {
                targetData = window.petaData.jembatan.find(j => j.id_jembatan == id);
                if (targetData) {
                    lat = parseFloat(targetData.koordinat_latitude);
                    lng = parseFloat(targetData.koordinat_longitude);
                }
            }
            
            if (targetData && lat && lng && !isNaN(lat) && !isNaN(lng)) {
                var tempMarker = L.circleMarker([lat, lng], {
                    radius: 10,
                    fillColor: type === 'fasilitas' ? '#e74c3c' : type === 'jalan' ? '#3498db' : type === 'jembatan' ? '#f39c12' : '#1abc9c',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(window.petaAdmin.map);
                
                var popupContent = this.createPopupContent(type, targetData);
                tempMarker.bindPopup(popupContent);
                
                window.petaAdmin.map.setView([lat, lng], 15, {
                    animate: true,
                    duration: 1
                });
                
                setTimeout(() => {
                    tempMarker.openPopup();
                }, 1000);
                
                setTimeout(() => {
                    window.petaAdmin.map.removeLayer(tempMarker);
                }, 10000);
            } else {
                alert('Lokasi tidak dapat ditampilkan karena koordinat tidak tersedia.');
            }
        }
        
        createPopupContent(type, data) {
            if (type === 'desa') {
                return `
                    <div class="popup-content">
                        <h5>${data.nama_desa}</h5>
                        <p><strong>Luas:</strong> ${data.luas_wilayah || 'N/A'} ha</p>
                        <p><strong>Dusun:</strong> ${data.jumlah_dusun || 0}</p>
                        <p><strong>RT/RW:</strong> ${data.jumlah_rt || 0}/${data.jumlah_rw || 0}</p>
                    </div>
                `;
            } else if (type === 'fasilitas') {
                return `
                    <div class="popup-content">
                        <h5>${data.nama_fasilitas}</h5>
                        <p><strong>Jenis:</strong> ${data.jenis_pendidikan}</p>
                        <p><strong>Desa:</strong> ${data.nama_desa}</p>
                        <p><strong>Kondisi:</strong> ${data.kondisi_bangunan}</p>
                        <p><strong>Kapasitas:</strong> ${data.kapasitas_siswa} siswa</p>
                    </div>
                `;
            } else if (type === 'jalan') {
                return `
                    <div class="popup-content">
                        <h5>${data.nama_jalan}</h5>
                        <p><strong>Desa:</strong> ${data.nama_desa}</p>
                        <p><strong>Panjang:</strong> ${data.panjang_jalan} km</p>
                        <p><strong>Kondisi:</strong> ${data.kondisi_jalan}</p>
                    </div>
                `;
            } else if (type === 'jembatan') {
                return `
                    <div class="popup-content">
                        <h5>${data.nama_jembatan}</h5>
                        <p><strong>Desa:</strong> ${data.nama_desa}</p>
                        <p><strong>Panjang:</strong> ${data.panjang_jembatan} m</p>
                        <p><strong>Kondisi:</strong> ${data.kondisi_jembatan}</p>
                    </div>
                `;
            }
            return '';
        }
    }
    
    // Global functions
    window.showOnMap = function(type, id) {
        if (window.PetaTable) {
            window.PetaTable.showOnMap(type, id);
        }
    };
    
    window.changePage = function(type, direction) {
        if (window.petaAdmin && window.petaAdmin.pagination) {
            var pagination = window.petaAdmin.pagination[type];
            var totalPages = Math.ceil(pagination.filteredRows.length / pagination.itemsPerPage);
            
            if (direction === 'prev' && pagination.currentPage > 1) {
                pagination.currentPage--;
            } else if (direction === 'next' && pagination.currentPage < totalPages) {
                pagination.currentPage++;
            }
            
            if (window.PetaTable) {
                window.PetaTable.updateTableDisplay(type, type + 'Table');
            }
        }
    };
    
    window.changeItemsPerPage = function(type, value) {
        if (window.petaAdmin && window.petaAdmin.pagination) {
            window.petaAdmin.pagination[type].itemsPerPage = parseInt(value);
            window.petaAdmin.pagination[type].currentPage = 1;
            if (window.PetaTable) {
                window.PetaTable.updateTableDisplay(type, type + 'Table');
            }
        }
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.PetaTable) {
            window.PetaTable = new PetaTable();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.PetaTable) {
            window.PetaTable = new PetaTable();
        }
    }
})();