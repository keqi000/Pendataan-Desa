// Pengaturan Desa Tab Functions
(function() {
    'use strict';
    
    class PengaturanDesa {
        constructor() {
            this.init();
        }
        
        init() {
            this.setupDesaModals();
            this.setupDesaFunctions();
        }
        
        setupDesaModals() {
            // Global desa modal functions
            window.showCreateDesaModal = () => {
                document.getElementById('createDesaModal').classList.add('show');
            };
            
            window.closeCreateDesaModal = () => {
                document.getElementById('createDesaModal').classList.remove('show');
                document.getElementById('createDesaForm').reset();
            };
            
            window.closeEditDesaModal = () => {
                document.getElementById('editDesaModal').classList.remove('show');
                document.getElementById('editDesaForm').reset();
            };
        }
        
        setupDesaFunctions() {
            window.createDesa = async () => {
                const namaDesa = document.getElementById('createNamaDesa').value;
                const luasWilayah = document.getElementById('createLuasWilayah').value;
                const jumlahDusun = document.getElementById('createJumlahDusun').value;
                const jumlahRT = document.getElementById('createJumlahRT').value;
                const jumlahRW = document.getElementById('createJumlahRW').value;
                
                if (!namaDesa || !luasWilayah || !jumlahDusun || !jumlahRT || !jumlahRW) {
                    alert('Field wajib harus diisi!');
                    return;
                }
                
                try {
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'create_desa',
                            nama_desa: namaDesa,
                            luas_wilayah: parseFloat(luasWilayah),
                            jumlah_dusun: parseInt(jumlahDusun),
                            jumlah_rt: parseInt(jumlahRT),
                            jumlah_rw: parseInt(jumlahRW)
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Desa berhasil ditambahkan!');
                        window.closeCreateDesaModal();
                        await this.reloadDesaTab();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan: ' + error.message);
                }
            };
            
            window.editDesa = async (desaId) => {
                try {
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php?action=get_desa&id=' + desaId);
                    const result = await response.json();
                    
                    if (result.success) {
                        const desa = result.data;
                        document.getElementById('editDesaId').value = desa.id_desa;
                        document.getElementById('editNamaDesa').value = desa.nama_desa;
                        document.getElementById('editLuasWilayah').value = desa.luas_wilayah || '';
                        document.getElementById('editJumlahDusun').value = desa.jumlah_dusun || '';
                        document.getElementById('editJumlahRT').value = desa.jumlah_rt || '';
                        document.getElementById('editJumlahRW').value = desa.jumlah_rw || '';
                        
                        document.getElementById('editDesaModal').classList.add('show');
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan: ' + error.message);
                }
            };
            
            window.updateDesa = async () => {
                const desaId = document.getElementById('editDesaId').value;
                const namaDesa = document.getElementById('editNamaDesa').value;
                const luasWilayah = document.getElementById('editLuasWilayah').value;
                const jumlahDusun = document.getElementById('editJumlahDusun').value;
                const jumlahRT = document.getElementById('editJumlahRT').value;
                const jumlahRW = document.getElementById('editJumlahRW').value;
                
                if (!namaDesa || !luasWilayah || !jumlahDusun || !jumlahRT || !jumlahRW) {
                    alert('Field wajib harus diisi!');
                    return;
                }
                
                try {
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_desa',
                            desa_id: desaId,
                            nama_desa: namaDesa,
                            luas_wilayah: parseFloat(luasWilayah),
                            jumlah_dusun: parseInt(jumlahDusun),
                            jumlah_rt: parseInt(jumlahRT),
                            jumlah_rw: parseInt(jumlahRW)
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Desa berhasil diupdate!');
                        window.closeEditDesaModal();
                        await this.reloadDesaTab();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan: ' + error.message);
                }
            };
            
            window.deleteDesa = async (desaId, desaName) => {
                if (!confirm(`Yakin ingin menghapus desa "${desaName}"?\\n\\nPerhatian: Semua data terkait desa ini akan ikut terhapus!`)) {
                    return;
                }
                
                try {
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_desa',
                            desa_id: desaId
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Desa berhasil dihapus!');
                        await this.reloadDesaTab();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan: ' + error.message);
                }
            };
        }
        
        async reloadDesaTab() {
            try {
                const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newDesaTab = doc.querySelector('#desa-tab');
                
                if (newDesaTab) {
                    document.querySelector('#desa-tab').innerHTML = newDesaTab.innerHTML;
                }
                
                if (window.petaData && window.petaAdmin) {
                    await this.reloadPetaData();
                }
            } catch (error) {
                location.reload();
            }
        }
        
        async reloadPetaData() {
            try {
                if (window.petaData && window.petaAdmin) {
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/peta/peta.php', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const html = await response.text();
                    const scriptMatch = html.match(/window\\.petaData = (\\{[\\s\\S]*?\\});/);
                    
                    if (scriptMatch) {
                        const oldPetaData = window.petaData;
                        eval('window.petaData = ' + scriptMatch[1]);
                        
                        if (JSON.stringify(oldPetaData.desa) !== JSON.stringify(window.petaData.desa)) {
                            window.petaAdmin.markers = window.petaAdmin.markers.filter(m => m.type !== 'desa');
                            window.petaAdmin.layerGroups.desa.clearLayers();
                            
                            window.petaData.desa.forEach((desa) => {
                                const lat = parseFloat(desa.koordinat_latitude);
                                const lng = parseFloat(desa.koordinat_longitude);
                                
                                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
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
                            });
                        }
                    }
                }
            } catch (error) {
                // Silent fail
            }
        }
    }
    
    // Initialize Desa functionality
    window.PengaturanDesa = new PengaturanDesa();
})();