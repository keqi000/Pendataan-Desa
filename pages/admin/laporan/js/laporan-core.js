// Laporan Core Functions
(function() {
    'use strict';
    
    class LaporanAdmin {
        constructor() {
            if (document.querySelector('.laporan-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupEventListeners();
            this.loadFormState();
        }
        
        setupEventListeners() {
            const reportForm = document.getElementById('reportForm');
            if (reportForm) {
                reportForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.validateForm();
                });
                
                reportForm.addEventListener('change', () => this.saveFormState());
                reportForm.addEventListener('input', () => this.saveFormState());
            }
            
            // Listen to hierarchical checkboxes
            ['tingkat1', 'tingkat2', 'tingkat3'].forEach(level => {
                document.querySelectorAll(`input[name="${level}"]`).forEach(checkbox => {
                    checkbox.addEventListener('change', () => this.saveFormState());
                });
            });
        }
        
        validateForm() {
            const reportName = document.getElementById('reportName').value.trim();
            
            // Check for new hierarchical structure
            const tingkat1Selected = document.querySelectorAll('input[name="tingkat1"]:checked');
            const tingkat2Selected = document.querySelectorAll('input[name="tingkat2"]:checked');
            const tingkat3Selected = document.querySelectorAll('input[name="tingkat3"]:checked');
            
            const totalSelected = tingkat1Selected.length + tingkat2Selected.length + tingkat3Selected.length;
            
            if (!reportName) {
                this.showNotification('Nama laporan harus diisi', 'warning');
                return false;
            }
            
            if (totalSelected === 0) {
                this.showNotification('Pilih minimal satu data untuk disertakan dalam laporan', 'warning');
                return false;
            }
            
            return true;
        }
        
        saveFormState() {
            const formData = {
                reportName: document.getElementById('reportName')?.value || '',
                reportDesa: document.getElementById('reportDesa')?.value || 'all',
                reportYear: document.getElementById('reportYear')?.value || new Date().getFullYear(),
                tingkat1: Array.from(document.querySelectorAll('input[name="tingkat1"]:checked')).map(cb => cb.value),
                tingkat2: Array.from(document.querySelectorAll('input[name="tingkat2"]:checked')).map(cb => cb.value),
                tingkat3: Array.from(document.querySelectorAll('input[name="tingkat3"]:checked')).map(cb => cb.value)
            };
            
            localStorage.setItem('laporan_form_state', JSON.stringify(formData));
        }
        
        loadFormState() {
            const savedState = localStorage.getItem('laporan_form_state');
            if (!savedState) return;
            
            try {
                const formData = JSON.parse(savedState);
                
                if (document.getElementById('reportName')) {
                    document.getElementById('reportName').value = formData.reportName || '';
                }
                if (document.getElementById('reportDesa')) {
                    document.getElementById('reportDesa').value = formData.reportDesa || 'all';
                }
                if (document.getElementById('reportYear')) {
                    document.getElementById('reportYear').value = formData.reportYear || new Date().getFullYear();
                }
                
                // Load hierarchical selections
                ['tingkat1', 'tingkat2', 'tingkat3'].forEach(level => {
                    if (formData[level] && Array.isArray(formData[level])) {
                        formData[level].forEach(value => {
                            const checkbox = document.querySelector(`input[name="${level}"][value="${value}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                                const event = new Event('change', { bubbles: true });
                                checkbox.dispatchEvent(event);
                            }
                        });
                    }
                });
                
                const totalSelected = (formData.tingkat1?.length || 0) + (formData.tingkat2?.length || 0) + (formData.tingkat3?.length || 0);
                if (formData.reportName || totalSelected > 0) {
                    setTimeout(() => {
                        this.showNotification('Form state dimuat dari sesi sebelumnya', 'info');
                    }, 500);
                }
                
            } catch (error) {
                localStorage.removeItem('laporan_form_state');
            }
        }
        
        clearFormState() {
            localStorage.removeItem('laporan_form_state');
        }
        
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">&times;</button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
    }
    
    // Global notification function
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    };
    
    // Initialize core
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.laporanAdminInstance) {
            window.laporanAdminInstance = new LaporanAdmin();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.laporanAdminInstance) {
            window.laporanAdminInstance = new LaporanAdmin();
        }
    }
})();