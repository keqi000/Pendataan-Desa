// Laporan Template Management
(function() {
    'use strict';
    
    window.useTemplate = function(templateId) {
        switchLaporanTab('generate');
        window.currentTemplateId = templateId;
        
        fetch(`${window.location.href}?endpoint=report-templates&template_id=${templateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const template = data.data.template;
                    
                    if (!template) {
                        showNotification('Template data tidak ditemukan', 'error');
                        return;
                    }
                    
                    let config;
                    try {
                        config = JSON.parse(template.konfigurasi || '{}');
                    } catch (e) {
                        config = template.konfigurasi || {};
                    }
                    
                    const templateName = template.nama_template || 'Template';
                    document.getElementById('reportName').value = templateName.replace(' (Template)', '');
                    document.getElementById('reportDesa').value = config.desa || 'all';
                    document.getElementById('reportYear').value = config.tahun || new Date().getFullYear();
                    
                    // Clear all checkboxes first
                    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // Collapse all sections
                    document.querySelectorAll('.level-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    document.querySelectorAll('.category-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Apply template configuration
                    if (config.selected_data) {
                        // Handle new structure with analysis levels
                        Object.keys(config.selected_data).forEach(level => {
                            const items = config.selected_data[level];
                            if (Array.isArray(items)) {
                                items.forEach(item => {
                                    const checkbox = document.querySelector(`input[name="${level}"][value="${item}"]`);
                                    if (checkbox) {
                                        checkbox.checked = true;
                                        
                                        // Trigger the hierarchical checkbox logic
                                        const event = new Event('change', { bubbles: true });
                                        checkbox.dispatchEvent(event);
                                    }
                                });
                            }
                        });
                    } else if (config.sections) {
                        // Handle old structure for backward compatibility
                        config.sections.forEach(section => {
                            const checkbox = document.querySelector(`input[value="${section}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                                const event = new Event('change', { bubbles: true });
                                checkbox.dispatchEvent(event);
                            }
                        });
                    }
                    
                    showNotification('Template berhasil dimuat', 'success');
                    
                    if (window.laporanAdminInstance) {
                        window.laporanAdminInstance.saveFormState();
                    }
                } else {
                    showNotification('Gagal memuat template', 'error');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan saat memuat template', 'error');
            });
    };
    
    window.deleteTemplate = function(templateId) {
        if (confirm('Apakah Anda yakin ingin menghapus template ini?')) {
            fetch(`${window.location.href}?endpoint=delete-template`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ template_id: templateId })
            })
            .then(response => response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    throw new Error('Server returned invalid JSON: ' + text.substring(0, 200));
                }
            }))
            .then(data => {
                if (data.success) {
                    showNotification('Template berhasil dihapus', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Gagal menghapus template: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan saat menghapus template: ' + error.message, 'error');
            });
        }
    };
    
    window.saveAsTemplate = function() {
        if (!window.laporanAdminInstance.validateForm()) {
            return;
        }
        
        const templateName = document.getElementById('reportName').value.trim() + ' (Template)';
        
        // Get selected data in new format
        const selectedData = {
            tingkat1: Array.from(document.querySelectorAll('input[name="tingkat1"]:checked')).map(cb => cb.value),
            tingkat2: Array.from(document.querySelectorAll('input[name="tingkat2"]:checked')).map(cb => cb.value),
            tingkat3: Array.from(document.querySelectorAll('input[name="tingkat3"]:checked')).map(cb => cb.value)
        };
        
        const config = {
            nama_template: templateName,
            desa: document.getElementById('reportDesa').value,
            tahun: document.getElementById('reportYear').value,
            selected_data: selectedData
        };
        
        const action = window.currentTemplateId ? 'update_template' : 'save_template';
        const body = window.currentTemplateId ? 
            `action=${action}&template_id=${window.currentTemplateId}&template_name=${encodeURIComponent(templateName)}&template_config=${encodeURIComponent(JSON.stringify(config))}` :
            `action=${action}&template_name=${encodeURIComponent(templateName)}&template_config=${encodeURIComponent(JSON.stringify(config))}`;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(window.currentTemplateId ? 'Template berhasil diupdate' : 'Template berhasil disimpan', 'success');
                window.currentTemplateId = null;
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Gagal menyimpan template', 'error');
            }
        })
        .catch(error => {
            showNotification('Terjadi kesalahan saat menyimpan template', 'error');
        });
    };
})();