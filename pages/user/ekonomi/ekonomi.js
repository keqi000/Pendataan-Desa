// Ekonomi Namespace
if (typeof window.Ekonomi === 'undefined') {
window.Ekonomi = {
    currentTab: 'mata-pencaharian',
    editingId: null,

    init() {
        if (!document.querySelector('.ekonomi-container')) return;
        
        this.setupEventListeners();
        this.loadMataPencaharianData();
        this.loadPendudukOptions();
        this.setupSearchAutocomplete();
    },

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.ekonomi-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const tabName = e.target.dataset.tab || e.target.closest('.ekonomi-tab').dataset.tab;
                if (tabName) this.switchTab(tabName);
            });
        });

        // Search handlers
        document.getElementById('searchMataPencaharian').addEventListener('input', 
            debounce(() => this.loadMataPencaharianData(), 300));
        document.getElementById('searchUmkm').addEventListener('input', 
            debounce(() => this.loadUmkmData(), 300));
        document.getElementById('searchPertanian').addEventListener('input', 
            debounce(() => this.loadPertanianData(), 300));
        document.getElementById('searchBantuan').addEventListener('input', 
            debounce(() => this.loadBantuanData(), 300));

        // Filter handlers
        document.getElementById('filterSektor').addEventListener('change', () => this.loadMataPencaharianData());

        // Form submissions
        const formUmkm = document.getElementById('formUmkm');
        if (formUmkm) {
            formUmkm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveUmkm();
            });
        }
        
        const formMataPencaharian = document.getElementById('formMataPencaharian');
        if (formMataPencaharian) {
            formMataPencaharian.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveMataPencaharian();
            });
        }
        
        const formPertanian = document.getElementById('formPertanian');
        if (formPertanian) {
            formPertanian.addEventListener('submit', (e) => {
                e.preventDefault();
                this.savePertanian();
            });
        }
        
        const formPasar = document.getElementById('formPasar');
        if (formPasar) {
            formPasar.addEventListener('submit', (e) => {
                e.preventDefault();
                this.savePasar();
            });
        }
        
        const formBantuan = document.getElementById('formBantuan');
        if (formBantuan) {
            formBantuan.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveBantuan();
            });
        }
    },

    switchTab(tabName) {
        if (!tabName) return;
        
        // Update tab buttons
        document.querySelectorAll('.ekonomi-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Update tab content
        document.querySelectorAll('.ekonomi-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const tabId = tabName.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join('');
        
        const tabContent = document.getElementById(`tab${tabId}`);
        if (tabContent) {
            tabContent.classList.add('active');
        }

        this.currentTab = tabName;

        // Load appropriate data
        switch(tabName) {
            case 'mata-pencaharian':
                this.loadMataPencaharianData();
                break;
            case 'umkm':
                this.loadUmkmData();
                break;
            case 'pertanian':
                this.loadPertanianData();
                break;
            case 'pasar':
                this.loadPasarData();
                break;
            case 'bantuan-sosial':
                this.loadBantuanData();
                break;
        }
    },

    async loadMataPencaharianData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchMataPencaharian').value;
            const sektor = document.getElementById('filterSektor').value;

            const params = new URLSearchParams({
                endpoint: 'mata-pencaharian',
                search: search,
                sektor: sektor
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            
            console.log('Mata Pencaharian Response status:', response.status);
            const responseText = await response.text();
            console.log('Mata Pencaharian Raw response:', responseText);
            
            const data = JSON.parse(responseText);

            if (data.success) {
                this.renderMataPencaharianTable(data.data);
            } else {
                this.renderEmptyTable('mataPencaharianTableBody', 7, 'Tidak ada data mata pencaharian');
            }
        } catch (error) {
            console.error('Error loading mata pencaharian data:', error);
            this.renderEmptyTable('mataPencaharianTableBody', 7, 'Gagal memuat data');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderMataPencaharianTable(data) {
        const tbody = document.getElementById('mataPencaharianTableBody');
        
        if (data.length === 0) {
            this.renderEmptyTable('mataPencaharianTableBody', 7, 'Tidak ada data mata pencaharian');
            return;
        }

        tbody.innerHTML = data.map(item => `
            <tr>
                <td>${item.nik}</td>
                <td>${item.nama_lengkap || '-'}</td>
                <td>${item.jenis_pekerjaan}</td>
                <td><span class="status-badge status-${item.sektor_pekerjaan}">${item.sektor_pekerjaan}</span></td>
                <td><span class="currency">${this.formatCurrency(item.penghasilan_perbulan)}</span></td>
                <td><span class="status-badge status-${item.status_pekerjaan.replace('_', '-')}">${item.status_pekerjaan.replace('_', ' ')}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-edit" onclick="editMataPencaharian(${item.id_pencaharian})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteMataPencaharian(${item.id_pencaharian})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    async loadUmkmData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchUmkm').value;

            const params = new URLSearchParams({
                endpoint: 'umkm',
                search: search
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderUmkmCards(data.data);
            } else {
                this.renderEmptyCards('umkmCards', 'Tidak ada data UMKM');
            }
        } catch (error) {
            console.error('Error loading UMKM data:', error);
            this.renderEmptyCards('umkmCards', 'Gagal memuat data UMKM');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderUmkmCards(data) {
        const container = document.getElementById('umkmCards');
        
        if (data.length === 0) {
            this.renderEmptyCards('umkmCards', 'Tidak ada data UMKM');
            return;
        }

        container.innerHTML = data.map(item => `
            <div class="ekonomi-card">
                <div class="ekonomi-card-header">
                    <div>
                        <h4 class="ekonomi-card-title">${item.nama_usaha}</h4>
                        <p class="ekonomi-card-subtitle">${item.jenis_usaha || 'Jenis usaha tidak disebutkan'}</p>
                    </div>
                    <div class="ekonomi-card-actions">
                        <button class="action-btn btn-edit" onclick="editUmkm(${item.id_umkm})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteUmkm(${item.id_umkm})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="ekonomi-card-content">
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Pemilik:</span>
                        <span class="ekonomi-card-value">${item.nama_pemilik || '-'}</span>
                    </div>
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Modal:</span>
                        <span class="ekonomi-card-value currency">${this.formatCurrency(item.modal_usaha)}</span>
                    </div>
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Omzet/Bulan:</span>
                        <span class="ekonomi-card-value currency">${this.formatCurrency(item.omzet_perbulan)}</span>
                    </div>
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Karyawan:</span>
                        <span class="ekonomi-card-value">${item.jumlah_karyawan || 0} orang</span>
                    </div>
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Status Izin:</span>
                        <span class="status-badge status-${item.status_izin.toLowerCase().replace(' ', '-')}">${item.status_izin}</span>
                    </div>
                </div>
            </div>
        `).join('');
    },

    async loadPertanianData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchPertanian').value;

            const params = new URLSearchParams({
                endpoint: 'pertanian',
                search: search
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderPertanianTable(data.data);
            } else {
                this.renderEmptyTable('pertanianTableBody', 7, 'Tidak ada data pertanian');
            }
        } catch (error) {
            console.error('Error loading pertanian data:', error);
            this.renderEmptyTable('pertanianTableBody', 7, 'Gagal memuat data');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderPertanianTable(data) {
        const tbody = document.getElementById('pertanianTableBody');
        
        if (data.length === 0) {
            this.renderEmptyTable('pertanianTableBody', 7, 'Tidak ada data pertanian');
            return;
        }

        tbody.innerHTML = data.map(item => `
            <tr>
                <td>${item.nama_petani || '-'}</td>
                <td>${item.jenis_komoditas}</td>
                <td>${item.luas_lahan} Ha</td>
                <td><span class="status-badge status-${item.jenis_lahan}">${item.jenis_lahan}</span></td>
                <td>${item.produksi_per_musim || 0} ${item.satuan_produksi || 'kg'}</td>
                <td><span class="currency">${this.formatCurrency(item.pendapatan_per_musim)}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-edit" onclick="editPertanian(${item.id_pertanian})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deletePertanian(${item.id_pertanian})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    async loadPasarData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=pasar`);
            const data = await response.json();

            if (data.success) {
                this.renderPasarCards(data.data);
            } else {
                this.renderEmptyCards('pasarCards', 'Tidak ada data pasar');
            }
        } catch (error) {
            console.error('Error loading pasar data:', error);
            this.renderEmptyCards('pasarCards', 'Gagal memuat data pasar');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderPasarCards(data) {
        const container = document.getElementById('pasarCards');
        
        if (data.length === 0) {
            this.renderEmptyCards('pasarCards', 'Tidak ada data pasar');
            return;
        }

        container.innerHTML = data.map(item => `
            <div class="ekonomi-card">
                <div class="ekonomi-card-header">
                    <div>
                        <h4 class="ekonomi-card-title">${item.nama_pasar}</h4>
                        <p class="ekonomi-card-subtitle">Pasar ${item.jenis_pasar}</p>
                    </div>
                    <div class="ekonomi-card-actions">
                        <button class="action-btn btn-edit" onclick="editPasar(${item.id_pasar})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deletePasar(${item.id_pasar})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="ekonomi-card-content">
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Jumlah Pedagang:</span>
                        <span class="ekonomi-card-value">${item.jumlah_pedagang || 0} orang</span>
                    </div>
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Hari Operasional:</span>
                        <span class="ekonomi-card-value">${item.hari_operasional || '-'}</span>
                    </div>
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Kondisi:</span>
                        <span class="status-badge status-${item.kondisi_fasilitas.toLowerCase()}">${item.kondisi_fasilitas}</span>
                    </div>
                    <div class="ekonomi-card-item">
                        <span class="ekonomi-card-label">Alamat:</span>
                        <span class="ekonomi-card-value">${item.alamat_pasar || '-'}</span>
                    </div>
                </div>
            </div>
        `).join('');
    },

    async loadBantuanData() {
        try {
            if (typeof showContentLoader === 'function') showContentLoader();
            
            const search = document.getElementById('searchBantuan').value;

            const params = new URLSearchParams({
                endpoint: 'bantuan-sosial',
                search: search
            });

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderBantuanTable(data.data);
            } else {
                this.renderEmptyTable('bantuanTableBody', 7, 'Tidak ada data bantuan sosial');
            }
        } catch (error) {
            console.error('Error loading bantuan data:', error);
            this.renderEmptyTable('bantuanTableBody', 7, 'Gagal memuat data');
        } finally {
            if (typeof hideContentLoader === 'function') hideContentLoader();
        }
    },

    renderBantuanTable(data) {
        const tbody = document.getElementById('bantuanTableBody');
        
        if (data.length === 0) {
            this.renderEmptyTable('bantuanTableBody', 7, 'Tidak ada data bantuan sosial');
            return;
        }

        tbody.innerHTML = data.map(item => `
            <tr>
                <td>${item.nik}</td>
                <td>${item.nama_lengkap || '-'}</td>
                <td>${item.jenis_bantuan}</td>
                <td><span class="currency">${this.formatCurrency(item.nominal_bantuan)}</span></td>
                <td>${item.periode_bantuan || '-'}</td>
                <td><span class="status-badge status-${item.status_penerima.replace('_', '-')}">${item.status_penerima.replace('_', ' ')}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-edit" onclick="editBantuan(${item.id_warga_miskin})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteBantuan(${item.id_warga_miskin})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    renderEmptyTable(tbodyId, colspan, message) {
        document.getElementById(tbodyId).innerHTML = `
            <tr>
                <td colspan="${colspan}" class="text-center">
                    <i class="fas fa-info-circle"></i> ${message}
                </td>
            </tr>
        `;
    },

    renderEmptyCards(containerId, message) {
        document.getElementById(containerId).innerHTML = `
            <div class="ekonomi-card-loading">
                <i class="fas fa-info-circle"></i> ${message}
            </div>
        `;
    },

    async loadPendudukOptions() {
        try {
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=penduduk-options`);
            const data = await response.json();

            // This method is no longer needed since we use search autocomplete
            // Keeping for backward compatibility
        } catch (error) {
            console.error('Error loading penduduk options:', error);
        }
    },

    async saveUmkm() {
        try {
            const formData = new FormData(document.getElementById('formUmkm'));
            const endpoint = this.editingId ? 'update-umkm' : 'create-umkm';
            
            if (this.editingId) {
                formData.append('id_umkm', this.editingId);
            }

            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (typeof showNotification === 'function') showNotification(this.editingId ? 'Data UMKM berhasil diupdate' : 'Data UMKM berhasil ditambahkan', 'success');
                this.closeUmkmModal();
                this.loadUmkmData();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            console.error('Error saving UMKM:', error);
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },

    openMataPencaharianModal(data = null) {
        const modal = document.getElementById('modalMataPencaharian');
        const form = document.getElementById('formMataPencaharian');
        const title = document.getElementById('modalMataPencaharianTitle');

        form.reset();
        this.editingMataPencaharianId = null;

        if (data) {
            title.textContent = 'Edit Mata Pencaharian';
            this.editingMataPencaharianId = data.id_pencaharian;
            
            // Populate form
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
            
            // Set search input with name and NIK
            if (data.nama_lengkap && data.nik) {
                const searchInput = document.getElementById('searchPendudukMataPencaharian');
                const hiddenInput = document.getElementById('selectedNikMataPencaharian');
                if (searchInput && hiddenInput) {
                    searchInput.value = `${data.nama_lengkap} (${data.nik})`;
                    hiddenInput.value = data.nik;
                }
            }
        } else {
            title.textContent = 'Tambah Mata Pencaharian';
        }

        modal.classList.add('show');
    },

    openUmkmModal(data = null) {
        const modal = document.getElementById('modalUmkm');
        const form = document.getElementById('formUmkm');
        const title = document.getElementById('modalUmkmTitle');

        form.reset();
        this.editingId = null;

        if (data) {
            title.textContent = 'Edit UMKM';
            this.editingId = data.id_umkm;
            
            // Populate form
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
            
            // Set search input with name and NIK
            if (data.nama_pemilik && data.nik_pemilik) {
                const searchInput = document.getElementById('searchPendudukUmkm');
                const hiddenInput = document.getElementById('selectedNikUmkm');
                if (searchInput && hiddenInput) {
                    searchInput.value = `${data.nama_pemilik} (${data.nik_pemilik})`;
                    hiddenInput.value = data.nik_pemilik;
                }
            }
        } else {
            title.textContent = 'Tambah UMKM';
        }

        modal.classList.add('show');
    },

    closeUmkmModal() {
        document.getElementById('modalUmkm').classList.remove('show');
        this.editingId = null;
    },
    
    openPertanianModal(data = null) {
        const modal = document.getElementById('modalPertanian');
        const form = document.getElementById('formPertanian');
        const title = document.getElementById('modalPertanianTitle');

        form.reset();
        this.editingPertanianId = null;

        if (data) {
            title.textContent = 'Edit Data Pertanian';
            this.editingPertanianId = data.id_pertanian;
            
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
            
            if (data.nama_lengkap && data.nik_petani) {
                const searchInput = document.getElementById('searchPendudukPertanian');
                const hiddenInput = document.getElementById('selectedNikPertanian');
                if (searchInput && hiddenInput) {
                    searchInput.value = `${data.nama_lengkap} (${data.nik_petani})`;
                    hiddenInput.value = data.nik_petani;
                }
            }
        } else {
            title.textContent = 'Tambah Data Pertanian';
        }

        modal.classList.add('show');
    },
    
    openPasarModal(data = null) {
        const modal = document.getElementById('modalPasar');
        const form = document.getElementById('formPasar');
        const title = document.getElementById('modalPasarTitle');

        form.reset();
        this.editingPasarId = null;

        if (data) {
            title.textContent = 'Edit Pasar';
            this.editingPasarId = data.id_pasar;
            
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
        } else {
            title.textContent = 'Tambah Pasar';
        }

        modal.classList.add('show');
    },
    
    openBantuanModal(data = null) {
        const modal = document.getElementById('modalBantuan');
        const form = document.getElementById('formBantuan');
        const title = document.getElementById('modalBantuanTitle');

        form.reset();
        this.editingBantuanId = null;

        if (data) {
            title.textContent = 'Edit Penerima Bantuan';
            this.editingBantuanId = data.id_warga_miskin;
            
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            });
            
            if (data.nama_lengkap && data.nik) {
                const searchInput = document.getElementById('searchPendudukBantuan');
                const hiddenInput = document.getElementById('selectedNikBantuan');
                if (searchInput && hiddenInput) {
                    searchInput.value = `${data.nama_lengkap} (${data.nik})`;
                    hiddenInput.value = data.nik;
                }
            }
        } else {
            title.textContent = 'Tambah Penerima Bantuan';
        }

        modal.classList.add('show');
    },
    
    async saveMataPencaharian() {
        try {
            const formData = new FormData(document.getElementById('formMataPencaharian'));
            const endpoint = this.editingMataPencaharianId ? 'update-mata-pencaharian' : 'create-mata-pencaharian';
            
            if (this.editingMataPencaharianId) {
                formData.append('id_pencaharian', this.editingMataPencaharianId);
            }
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if (typeof showNotification === 'function') showNotification(this.editingMataPencaharianId ? 'Data mata pencaharian berhasil diupdate' : 'Data mata pencaharian berhasil ditambahkan', 'success');
                window.closeMataPencaharianModal();
                this.loadMataPencaharianData();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },
    
    async savePertanian() {
        try {
            const formData = new FormData(document.getElementById('formPertanian'));
            const endpoint = this.editingPertanianId ? 'update-pertanian' : 'create-pertanian';
            
            if (this.editingPertanianId) {
                formData.append('id_pertanian', this.editingPertanianId);
            }
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if (typeof showNotification === 'function') showNotification(this.editingPertanianId ? 'Data pertanian berhasil diupdate' : 'Data pertanian berhasil ditambahkan', 'success');
                window.closePertanianModal();
                this.loadPertanianData();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },
    
    async savePasar() {
        try {
            const formData = new FormData(document.getElementById('formPasar'));
            const endpoint = this.editingPasarId ? 'update-pasar' : 'create-pasar';
            
            if (this.editingPasarId) {
                formData.append('id_pasar', this.editingPasarId);
            }
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if (typeof showNotification === 'function') showNotification(this.editingPasarId ? 'Data pasar berhasil diupdate' : 'Data pasar berhasil ditambahkan', 'success');
                window.closePasarModal();
                this.loadPasarData();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },
    
    async saveBantuan() {
        try {
            const formData = new FormData(document.getElementById('formBantuan'));
            const endpoint = this.editingBantuanId ? 'update-bantuan' : 'create-bantuan';
            
            if (this.editingBantuanId) {
                formData.append('id_warga_miskin', this.editingBantuanId);
            }
            
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=${endpoint}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if (typeof showNotification === 'function') showNotification(this.editingBantuanId ? 'Data bantuan sosial berhasil diupdate' : 'Data bantuan sosial berhasil ditambahkan', 'success');
                window.closeBantuanModal();
                this.loadBantuanData();
            } else {
                if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menyimpan data', 'error');
            }
        } catch (error) {
            if (typeof showNotification === 'function') showNotification('Gagal menyimpan data', 'error');
        }
    },

    setupSearchAutocomplete() {
        this.setupSearch('searchPendudukMataPencaharian', 'searchResultsMataPencaharian', 'selectedNikMataPencaharian');
        this.setupSearch('searchPendudukBantuan', 'searchResultsBantuan', 'selectedNikBantuan');
        this.setupSearch('searchPendudukPertanian', 'searchResultsPertanian', 'selectedNikPertanian');
        this.setupSearch('searchPendudukUmkm', 'searchResultsUmkm', 'selectedNikUmkm');
    },
    
    setupSearch(inputId, resultsId, hiddenId) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        const hidden = document.getElementById(hiddenId);
        
        if (!input || !results || !hidden) return;
        
        let timeout;
        input.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                results.style.display = 'none';
                hidden.value = '';
                return;
            }
            
            timeout = setTimeout(() => this.searchPenduduk(query, results, hidden, input), 300);
        });
        
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.style.display = 'none';
            }
        });
    },
    
    async searchPenduduk(query, resultsContainer, hiddenInput, textInput) {
        try {
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=penduduk-options&search=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.success && data.data.length > 0) {
                resultsContainer.innerHTML = data.data.map(penduduk => `
                    <div class="search-result-item" onclick="Ekonomi.selectPenduduk('${penduduk.nik}', '${penduduk.nama_lengkap}', '${hiddenInput.id}', '${textInput.id}', '${resultsContainer.id}')">
                        <div class="search-result-name">${penduduk.nama_lengkap}</div>
                        <div class="search-result-nik">NIK: ${penduduk.nik}</div>
                    </div>
                `).join('');
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = '<div class="search-result-item">Tidak ada data ditemukan</div>';
                resultsContainer.style.display = 'block';
            }
        } catch (error) {
            console.error('Error searching penduduk:', error);
        }
    },
    
    selectPenduduk(nik, nama, hiddenId, textId, resultsId) {
        document.getElementById(hiddenId).value = nik;
        document.getElementById(textId).value = `${nama} (${nik})`;
        document.getElementById(resultsId).style.display = 'none';
    },

    formatCurrency(amount) {
        if (!amount || amount == 0) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
};
}

// Global functions
window.openMataPencaharianModal = function() {
    Ekonomi.openMataPencaharianModal();
};

window.closeMataPencaharianModal = function() {
    document.getElementById('modalMataPencaharian').classList.remove('show');
    Ekonomi.editingMataPencaharianId = null;
};

window.openUmkmModal = function() {
    Ekonomi.openUmkmModal();
};

window.closeUmkmModal = function() {
    Ekonomi.closeUmkmModal();
};

window.openPertanianModal = function() {
    Ekonomi.openPertanianModal();
};

window.closePertanianModal = function() {
    document.getElementById('modalPertanian').classList.remove('show');
    Ekonomi.editingPertanianId = null;
};

window.openPasarModal = function() {
    Ekonomi.openPasarModal();
};

window.closePasarModal = function() {
    document.getElementById('modalPasar').classList.remove('show');
    Ekonomi.editingPasarId = null;
};

window.openBantuanModal = function() {
    Ekonomi.openBantuanModal();
};

window.closeBantuanModal = function() {
    document.getElementById('modalBantuan').classList.remove('show');
    Ekonomi.editingBantuanId = null;
};

window.editMataPencaharian = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=mata-pencaharian-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openMataPencaharianModal(data.data);
        }
    } catch (error) {
        console.error('Error loading mata pencaharian detail:', error);
    }
};

window.deleteMataPencaharian = async function(id) {
    if (!confirm('Yakin ingin menghapus data mata pencaharian ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-mata-pencaharian`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pencaharian: id })
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data mata pencaharian berhasil dihapus', 'success');
            Ekonomi.loadMataPencaharianData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        console.error('Error deleting mata pencaharian:', error);
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.editPertanian = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=pertanian-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openPertanianModal(data.data);
        }
    } catch (error) {
        console.error('Error loading pertanian detail:', error);
    }
};

window.deletePertanian = async function(id) {
    if (!confirm('Yakin ingin menghapus data pertanian ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-pertanian`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pertanian: id })
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data pertanian berhasil dihapus', 'success');
            Ekonomi.loadPertanianData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.editPasar = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=pasar-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openPasarModal(data.data);
        }
    } catch (error) {
        console.error('Error loading pasar detail:', error);
    }
};

window.deletePasar = async function(id) {
    if (!confirm('Yakin ingin menghapus data pasar ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-pasar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pasar: id })
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data pasar berhasil dihapus', 'success');
            Ekonomi.loadPasarData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.editBantuan = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=bantuan-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openBantuanModal(data.data);
        }
    } catch (error) {
        console.error('Error loading bantuan detail:', error);
    }
};

window.deleteBantuan = async function(id) {
    if (!confirm('Yakin ingin menghapus data bantuan ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-bantuan`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_warga_miskin: id })
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data bantuan sosial berhasil dihapus', 'success');
            Ekonomi.loadBantuanData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

window.editUmkm = async function(id) {
    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=umkm-detail&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            Ekonomi.openUmkmModal(data.data);
        }
    } catch (error) {
        console.error('Error loading UMKM detail:', error);
    }
};

window.deleteUmkm = async function(id) {
    if (!confirm('Yakin ingin menghapus data UMKM ini?')) return;

    try {
        const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/user-endpoints.php?endpoint=delete-umkm`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_umkm: id })
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') showNotification('Data UMKM berhasil dihapus', 'success');
            Ekonomi.loadUmkmData();
        } else {
            if (typeof showNotification === 'function') showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        console.error('Error deleting UMKM:', error);
        if (typeof showNotification === 'function') showNotification('Gagal menghapus data', 'error');
    }
};

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.Ekonomi) window.Ekonomi.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Ekonomi) window.Ekonomi.init();
    }, 100);
}

// Global init function for AJAX calls
window.initEkonomi = function() {
    if (window.Ekonomi) {
        window.Ekonomi.init();
    }
};