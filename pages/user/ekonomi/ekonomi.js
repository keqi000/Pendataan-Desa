// Global init function for AJAX calls
window.initEkonomi = function() {
    if (window.Ekonomi && document.querySelector('.ekonomi-container')) {
        window.Ekonomi.init();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.Ekonomi && document.querySelector('.ekonomi-container')) {
        window.Ekonomi.init();
    }
});

// Re-initialize for AJAX navigation
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Ekonomi && document.querySelector('.ekonomi-container')) {
            window.Ekonomi.init();
        }
    }, 100);
}