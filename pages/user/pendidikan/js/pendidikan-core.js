// Pendidikan Core Namespace
if (typeof window.Pendidikan === 'undefined') {
window.Pendidikan = {
    editingId: null,
    isSubmitting: false,

    init() {
        if (!document.querySelector('.pendidikan-container')) return;
        
        this.setupEventListeners();
        this.loadFasilitasData();
        this.loadStatistics();
    },

    setupEventListeners() {
        // Search and filters
        document.getElementById('searchFasilitas').addEventListener('input', 
            debounce(() => this.loadFasilitasData(), 300));
        document.getElementById('filterJenis').addEventListener('change', () => this.loadFasilitasData());
        document.getElementById('filterKondisi').addEventListener('change', () => this.loadFasilitasData());

        // Form submission
        document.getElementById('formFasilitas').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveFasilitas();
        });
    },

    renderEmptyCards(message) {
        document.getElementById('fasilitasCards').innerHTML = `
            <div class="pendidikan-card-loading">
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
    if (window.Pendidikan) window.Pendidikan.init();
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Pendidikan) window.Pendidikan.init();
    }, 100);
}

// Global init function for AJAX calls
window.initPendidikan = function() {
    if (window.Pendidikan) {
        window.Pendidikan.init();
    }
};