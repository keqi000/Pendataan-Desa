// Laporan Generate Functions
(function() {
    'use strict';
    
    window.generateReport = function() {
        if (!window.laporanAdminInstance.validateForm()) {
            return;
        }
        
        // Get selected data types by analysis level
        const selectedData = {
            tingkat1: Array.from(document.querySelectorAll('input[name="tingkat1"]:checked')).map(cb => cb.value),
            tingkat2: Array.from(document.querySelectorAll('input[name="tingkat2"]:checked')).map(cb => cb.value),
            tingkat3: Array.from(document.querySelectorAll('input[name="tingkat3"]:checked')).map(cb => cb.value)
        };
        
        // Check if at least one item is selected
        const totalSelected = Object.values(selectedData).reduce((sum, arr) => sum + arr.length, 0);
        if (totalSelected === 0) {
            showNotification('Pilih minimal satu data untuk disertakan dalam laporan', 'warning');
            return;
        }
        
        // Check if tingkat1 or tingkat2 data is selected
        const needsAnalysisCache = selectedData.tingkat1.length > 0 || selectedData.tingkat2.length > 0;
        
        if (needsAnalysisCache) {
            showNotification('Laporan akan menggunakan data dari cache analisis. Jika belum pernah generate analisis, proses akan lebih lambat.', 'info');
        }
        
        let config = {
            nama_laporan: document.getElementById('reportName').value,
            desa: document.getElementById('reportDesa').value,
            tahun: document.getElementById('reportYear').value,
            selected_data: selectedData,
            format: 'pdf'
        };
        
        showNotification('Sedang generate laporan PDF...', 'info');
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        form.target = '_blank';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'generate_pdf';
        form.appendChild(actionInput);
        
        const configInput = document.createElement('input');
        configInput.name = 'config';
        configInput.value = JSON.stringify(config);
        form.appendChild(configInput);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        setTimeout(() => {
            showNotification('Laporan PDF berhasil digenerate', 'success');
        }, 2000);
    };
    
    window.generateExcelReport = function() {
        // Redirect to PDF generation
        generateReport();
    };
    
    function clearGenerateForm() {
        document.getElementById('reportName').value = '';
        document.getElementById('reportDesa').value = 'all';
        document.getElementById('reportYear').value = new Date().getFullYear();
        
        // Clear all checkboxes and collapse all sections
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Collapse all level contents
        document.querySelectorAll('.level-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Collapse all category contents
        document.querySelectorAll('.category-content').forEach(content => {
            content.classList.remove('active');
        });
        
        window.currentTemplateId = null;
        
        if (window.laporanAdminInstance) {
            window.laporanAdminInstance.clearFormState();
        }
    }
    
    window.clearFormAndState = function() {
        clearGenerateForm();
        if (window.laporanAdminInstance) {
            window.laporanAdminInstance.clearFormState();
        }
        showNotification('Form berhasil direset', 'success');
    };
    
    // Export for internal use
    window.LaporanGenerate = {
        clearGenerateForm
    };
})();