// Data Umum Main - Global functions and initialization
(function() {
    'use strict';
    
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
        if (window.dataUmumInstance) {
            window.dataUmumInstance.isEditing = true;
        }
    };

    window.cancelEdit = function() {
        const inputs = document.querySelectorAll('#formInformasiDasar input');
        const actions = document.getElementById('formActions');
        
        inputs.forEach(input => input.setAttribute('readonly', true));
        actions.style.display = 'none';
        
        if (window.dataUmumInstance) {
            window.dataUmumInstance.isEditing = false;
            window.dataUmumInstance.loadDataDesa();
        }
    };

    window.viewFile = function(filePath) {
        const baseUrl = window.DataUmumUtils ? window.DataUmumUtils.getBaseUrl() : '/Pendataan-desa/';
        window.open(`${baseUrl}uploads/${filePath}`, '_blank');
    };

    window.deleteFile = async function(filePath, previewId) {
        if (!confirm('Yakin ingin menghapus file ini?')) return;

        try {
            const baseUrl = window.DataUmumUtils ? window.DataUmumUtils.getBaseUrl() : '/Pendataan-desa/';
            const response = await fetch(`${baseUrl}api/router.php?endpoint=delete-file`, {
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
            showNotification('Gagal menghapus file', 'error');
        }
    };

    // Global functions for peta desa
    window.toggleDesaFullscreen = function() {
        const wrapper = document.querySelector('.peta-desa-wrapper');
        if (!document.fullscreenElement) {
            wrapper.requestFullscreen().then(() => {
                setTimeout(() => {
                    if (window.dataUmumInstance && window.dataUmumInstance.desaMap) {
                        window.dataUmumInstance.desaMap.invalidateSize();
                    }
                }, 100);
            });
        } else {
            document.exitFullscreen().then(() => {
                setTimeout(() => {
                    if (window.dataUmumInstance && window.dataUmumInstance.desaMap) {
                        window.dataUmumInstance.desaMap.invalidateSize();
                    }
                }, 100);
            });
        }
    };

document.addEventListener('DOMContentLoaded', () => {
    if (window.DataUmum && !window.dataUmumInstance) {
        window.dataUmumInstance = new window.DataUmum();
    }
});

if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.DataUmum && !window.dataUmumInstance) {
            window.dataUmumInstance = new window.DataUmum();
        }
    }, 100);
}

window.initDataUmum = function() {
    if (window.DataUmum) {
        if (window.dataUmumInstance) {
            window.dataUmumInstance.init();
        } else {
            window.dataUmumInstance = new window.DataUmum();
        }
    }
};
})();