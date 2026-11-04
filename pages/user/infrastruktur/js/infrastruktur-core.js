// Infrastruktur Core Namespace
if (typeof window.Infrastruktur === 'undefined') {
window.Infrastruktur = {
    currentTab: 'jalan',
    editingId: null,
    isSubmitting: false,

    init() {
        if (!document.querySelector('.infrastruktur-container')) return;
        
        this.setupEventListeners();
        this.loadJalanData();
    },

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.infrastruktur-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Search handlers
        document.getElementById('searchJalan').addEventListener('input', 
            debounce(() => this.loadJalanData(), 300));
        document.getElementById('searchJembatan').addEventListener('input', 
            debounce(() => this.loadJembatanData(), 300));

        // Filter handlers
        document.getElementById('filterKondisiJalan').addEventListener('change', () => this.loadJalanData());
        document.getElementById('filterPermukaan').addEventListener('change', () => this.loadJalanData());
        document.getElementById('filterKondisiJembatan').addEventListener('change', () => this.loadJembatanData());

        // Form submissions
        document.getElementById('formJalan').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveJalan();
        });

        document.getElementById('formJembatan').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveJembatan();
        });
    },

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.infrastruktur-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Update tab content
        document.querySelectorAll('.infrastruktur-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const tabId = tabName.charAt(0).toUpperCase() + tabName.slice(1);
        const tabContent = document.getElementById(`tab${tabId}`);
        if (tabContent) {
            tabContent.classList.add('active');
        }

        this.currentTab = tabName;

        // Load appropriate data
        if (tabName === 'jalan') {
            this.loadJalanData();
        } else if (tabName === 'jembatan') {
            this.loadJembatanData();
        }
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
            <div class="infrastruktur-card-loading">
                <i class="fas fa-info-circle"></i> ${message}
            </div>
        `;
    }
};
}

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
    if (window.Infrastruktur) window.Infrastruktur.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Infrastruktur) window.Infrastruktur.init();
    }, 100);
}

// Global init function for AJAX calls
window.initInfrastruktur = function() {
    if (window.Infrastruktur) {
        window.Infrastruktur.init();
    }
};