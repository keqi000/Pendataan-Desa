// Pengaturan Core Functions
(function() {
    'use strict';
    
    // API Helper functions
    function validateUrl(url) {
        try {
            const urlObj = new URL(url);
            const allowedHosts = ['localhost', '127.0.0.1', window.location.hostname];
            return allowedHosts.includes(urlObj.hostname) || urlObj.hostname.endsWith('.local');
        } catch {
            return false;
        }
    }
    
    async function apiCall(url, options = {}) {
        if (!validateUrl(url)) {
            throw new Error('Invalid URL');
        }
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    }
    
    function getBaseUrl() {
        return window.BASE_URL || (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
            ? window.location.origin + '/Pendataan-desa/' 
            : window.location.origin + '/');
    }
    
    class PengaturanCore {
        constructor() {
            if (document.querySelector('.pengaturan-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupTabSwitching();
            this.setupPasswordForm();
        }
        
        setupTabSwitching() {
            // Global tab switching function
            window.showTab = (tabName) => {
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                const targetTab = document.getElementById(tabName + '-tab');
                if (targetTab) {
                    targetTab.classList.add('active');
                }
                
                if (event && event.target) {
                    event.target.classList.add('active');
                }
            };
        }
        
        setupPasswordForm() {
            const changePasswordForm = document.getElementById('changePasswordForm');
            if (changePasswordForm) {
                changePasswordForm.addEventListener('submit', this.handleChangePassword.bind(this));
            }
        }
        
        async handleChangePassword(e) {
            e.preventDefault();
            
            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                alert('Password baru dan konfirmasi password tidak sama!');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Password baru minimal 6 karakter!');
                return;
            }
            
            try {
                const response = await fetch(getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'change_password',
                        old_password: oldPassword,
                        new_password: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Password berhasil diubah!');
                    document.getElementById('changePasswordForm').reset();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }
    }
    
    // Export utilities for other modules
    window.PengaturanUtils = {
        apiCall,
        getBaseUrl,
        validateUrl
    };
    
    // Initialize core
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.pengaturanCoreInstance) {
            window.pengaturanCoreInstance = new PengaturanCore();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.pengaturanCoreInstance) {
            window.pengaturanCoreInstance = new PengaturanCore();
        }
    }
})();