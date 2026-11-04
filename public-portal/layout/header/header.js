// Header JavaScript untuk Sistem Informasi Publik
// File: public-portal/layout/header/header.js

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Update header statistics periodically
        updateHeaderStats();
        
        // Update every 5 minutes
        setInterval(updateHeaderStats, 300000);
        
        // Add smooth animations to header elements
        animateHeaderElements();
    });
    
    function updateHeaderStats() {
        // This could fetch real-time data from API
        // For now, we'll just ensure the display is correct
        const infoItems = document.querySelectorAll('.info-item span');
        
        // You could update these with real API data
        // Example:
        // fetch('/api/public/overview')
        //   .then(response => response.json())
        //   .then(data => {
        //     // Update the spans with new data
        //   });
    }
    
    function animateHeaderElements() {
        const headerElements = document.querySelectorAll('.info-item');
        
        headerElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(-20px)';
            element.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100 + (index * 100));
        });
    }
    
})();