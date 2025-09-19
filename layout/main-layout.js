// Main Layout JavaScript - AJAX Content Loading
class MainLayoutComponent {
    constructor() {
        this.contentArea = document.getElementById('dynamicContent');
        this.contentLoader = document.getElementById('contentLoader');
        this.currentPage = '';
        this.init();
    }
    
    init() {
        this.setupAjaxNavigation();
        this.setupPopState();
        this.currentPage = this.getCurrentPageFromURL();
        
        // Load current page content if different from initial
        const initialPage = document.querySelector('.dashboard-admin-container, .dashboard-user-container') ? 'dashboard' : null;
        if (this.currentPage !== 'dashboard' && this.currentPage !== initialPage) {
            setTimeout(() => {
                this.loadPageContent(this.currentPage, false);
                this.updateActiveNavigation(this.currentPage);
            }, 500);
        }
    }
    
    getCurrentPageFromURL() {
        const path = window.location.pathname;
        const segments = path.split('/').filter(segment => segment);
        
        // Extract page name from path like /pages/admin/monitoring/monitoring.php
        if (segments.length >= 3 && segments[0] === 'pages') {
            return segments[2]; // monitoring, analisis, etc
        }
        
        return 'dashboard';
    }
    
    setupAjaxNavigation() {
        // Override default navigation for sidebar links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.sidebar-nav-link');
            if (link && link.hasAttribute('onclick')) {
                e.preventDefault();
            }
        });
    }
    
    setupPopState() {
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.page) {
                this.loadPageContent(e.state.page, false);
            } else {
                // Handle direct URL access
                const currentPage = this.getCurrentPageFromURL();
                if (currentPage !== 'dashboard') {
                    this.loadPageContent(currentPage, false);
                }
            }
        });
    }
    
    async loadPage(page) {
        // Force reload currentPage to handle refresh scenarios
        this.currentPage = this.getCurrentPageFromURL();
        
        if (page === this.currentPage && page !== 'dashboard') return;
        
        // Show loader immediately
        this.showContentLoader();
        
        try {
            await this.loadPageContent(page, true);
            this.currentPage = page;
            this.updateActiveNavigation(page);
            
        } catch (error) {
            console.error('Failed to load page:', error);
            this.showError('Gagal memuat halaman. Silakan coba lagi.');
        } finally {
            this.hideContentLoader();
        }
    }
    
    async loadPageContent(page, updateHistory = true) {
        const userRole = this.getUserRole();
        const pageUrl = this.getPageUrl(page, userRole);
        
        try {
            const response = await fetch(pageUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const html = await response.text();
            
            // Update content
            this.contentArea.innerHTML = html;
            
            // Update page title
            this.updatePageTitle(page);
            
            // Update URL without page reload
            if (updateHistory) {
                const userRole = this.getUserRole();
                const rolePath = userRole === 'admin' ? 'admin/' : 'user/';
                const newUrl = `${pendataanDesa.baseURL}pages/${rolePath}${page}/${page}.php`;
                history.pushState({ page: page }, '', newUrl);
            }
            
            // Apply theme to new content
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Execute page-specific JavaScript if exists
            setTimeout(() => {
                this.executePageScript(page);
                
                // Reinitialize page-specific functionality
                if (page === 'dashboard') {
                    setTimeout(() => {
                        if (typeof window.initDashboard === 'function') {
                            window.initDashboard();
                        }
                    }, 300);
                } else if (page === 'detail-desa') {
                    setTimeout(() => {
                        if (typeof window.reinitializeDetailDesa === 'function') {
                            window.reinitializeDetailDesa();
                        }
                    }, 200);
                } else if (page === 'data-umum') {
                    setTimeout(() => {
                        if (typeof window.initDataUmum === 'function') {
                            window.initDataUmum();
                        }
                    }, 300);
                } else if (page === 'kependudukan') {
                    setTimeout(() => {
                        if (typeof window.initKependudukan === 'function') {
                            window.initKependudukan();
                        }
                    }, 300);
                } else if (page === 'ekonomi') {
                    setTimeout(() => {
                        if (typeof window.initEkonomi === 'function') {
                            window.initEkonomi();
                        }
                    }, 300);
                } else if (page === 'pendidikan') {
                    setTimeout(() => {
                        if (typeof window.initPendidikan === 'function') {
                            window.initPendidikan();
                        }
                    }, 300);
                } else if (page === 'infrastruktur') {
                    setTimeout(() => {
                        if (typeof window.initInfrastruktur === 'function') {
                            window.initInfrastruktur();
                        }
                    }, 300);
                } else if (page === 'laporan') {
                    setTimeout(() => {
                        if (typeof window.initLaporan === 'function') {
                            window.initLaporan();
                        }
                    }, 300);
                } else if (page === 'peta') {
                    setTimeout(() => {
                        console.log('🗺️ [MAIN-LAYOUT] Initializing peta page');
                        if (typeof window.initializePetaIfNeeded === 'function') {
                            window.initializePetaIfNeeded();
                        }
                    }, 500);
                }
            }, 200);
            
        } catch (error) {
            throw error;
        }
    }
    
    getUserRole() {
        // Get user role from session or DOM
        const userInfo = document.querySelector('.sidebar-user-role');
        if (userInfo) {
            const roleText = userInfo.textContent.toLowerCase();
            return roleText.includes('admin') ? 'admin' : 'user';
        }
        return 'user';
    }
    
    getPageUrl(page, userRole) {
        const basePath = pendataanDesa.baseURL + 'pages/';
        const rolePath = userRole === 'admin' ? 'admin/' : 'user/';
        return `${basePath}${rolePath}${page}/${page}.php`;
    }
    
    updatePageTitle(page) {
        const pageTitles = {
            'dashboard': 'Dashboard',
            'data-umum': 'Data Umum Desa',
            'kependudukan': 'Data Kependudukan',
            'ekonomi': 'Data Ekonomi',
            'pendidikan': 'Data Pendidikan',
            'infrastruktur': 'Data Infrastruktur',
            'analisis': 'Analisis Data',
            'laporan': 'Laporan',
            'monitoring': 'Monitoring Desa',
            'peta': 'Peta & Geografis'
        };
        
        const title = pageTitles[page] || 'Dashboard';
        document.title = `${title} - Sistem Pendataan Desa`;
        
        // Update header title
        const headerTitle = document.querySelector('.header-page-title');
        if (headerTitle) {
            headerTitle.textContent = title;
        }
    }
    
    updateActiveNavigation(page) {
        // Remove active class from all nav links
        const navLinks = document.querySelectorAll('.sidebar-nav-link');
        navLinks.forEach(link => {
            link.classList.remove('active');
            // Check if this link matches the current page
            const onclick = link.getAttribute('onclick');
            if (onclick && onclick.includes(`'${page}'`)) {
                link.classList.add('active');
            }
        });
    }
    
    executePageScript(page) {
        // Remove all existing page scripts and CSS
        const existingScripts = document.querySelectorAll('[id^="page-script-"]');
        existingScripts.forEach(script => script.remove());
        
        const existingCSS = document.querySelectorAll('[id^="page-css-"]');
        existingCSS.forEach(css => css.remove());
        
        const userRole = this.getUserRole();
        const rolePath = userRole === 'admin' ? 'admin/' : 'user/';
        
        // Load page CSS
        const cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.id = `page-css-${page}`;
        cssLink.href = `${pendataanDesa.baseURL}pages/${rolePath}${page}/${page}.css?v=${Date.now()}`;
        document.head.appendChild(cssLink);
        
        // Load page JS
        // Load page JS with timeout to ensure CSS is loaded first
        setTimeout(() => {
            const script = document.createElement('script');
            script.id = `page-script-${page}`;
            script.src = `${pendataanDesa.baseURL}pages/${rolePath}${page}/${page}.js?v=${Date.now()}`;
            script.onerror = () => {
                console.log(`No specific script found for page: ${page}`);
            };
            
            document.head.appendChild(script);
        }, 100);
    }
    
    showContentLoader() {
        // Hide main content immediately
        if (this.contentArea) {
            this.contentArea.style.display = 'none';
        }
        
        // Show loader immediately
        if (this.contentLoader) {
            this.contentLoader.classList.remove('hidden');
            this.contentLoader.style.display = 'flex';
        }
    }
    
    hideContentLoader() {
        if (this.contentLoader) {
            setTimeout(() => {
                this.contentLoader.classList.add('hidden');
                this.contentLoader.style.display = 'none';
                
                // Show main content after loading
                if (this.contentArea) {
                    this.contentArea.style.display = 'block';
                }
            }, 400);
        }
    }
    
    showError(message) {
        this.contentArea.innerHTML = `
            <div class="error-container" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--danger); margin-bottom: 20px;"></i>
                <h3 style="color: var(--text-dark); margin-bottom: 16px;">Terjadi Kesalahan</h3>
                <p style="color: var(--text-light); margin-bottom: 24px;">${message}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i>
                    Muat Ulang Halaman
                </button>
            </div>
        `;
    }
}

// Initialize main layout component
document.addEventListener('DOMContentLoaded', () => {
    window.mainLayoutComponent = new MainLayoutComponent();
});