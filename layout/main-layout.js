// Main Layout JavaScript - Traditional Navigation
// AJAX components disabled - using traditional page navigation

// Keep only page loader functionality
document.addEventListener('DOMContentLoaded', () => {
    // Hide page loader after page loads
    setTimeout(() => {
        const pageLoader = document.querySelector('.page-loader');
        if (pageLoader) {
            pageLoader.classList.add('hidden');
        }
    }, 500);
});