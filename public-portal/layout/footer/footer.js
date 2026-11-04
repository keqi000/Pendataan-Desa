// Footer JavaScript untuk Sistem Informasi Publik
// File: public-portal/layout/footer/footer.js

(function() {
    'use strict';
    
    // Back to top functionality
    window.scrollToTop = function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };
    
    // Show/hide back to top button based on scroll position
    function toggleBackToTopButton() {
        const backToTopButton = document.getElementById('backToTop');
        if (backToTopButton) {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        }
    }
    
    // Update last update time
    function updateLastUpdateTime() {
        const lastUpdateElement = document.getElementById('lastUpdate');
        if (lastUpdateElement) {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'Asia/Makassar'
            };
            const formattedDate = now.toLocaleDateString('id-ID', options);
            lastUpdateElement.textContent = formattedDate + ' WIT';
        }
    }
    
    // Initialize footer functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Set up scroll listener for back to top button
        window.addEventListener('scroll', toggleBackToTopButton);
        
        // Initial check for back to top button
        toggleBackToTopButton();
        
        // Update last update time
        updateLastUpdateTime();
        
        // Handle social media links
        const socialLinks = document.querySelectorAll('.social-links a');
        socialLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') {
                    e.preventDefault();
                    PublicUtils.showNotification('Link media sosial akan segera tersedia', 'info');
                }
            });
        });
        
        // Handle API URL copy
        const copyApiButton = document.querySelector('a[onclick*="copyToClipboard"]');
        if (copyApiButton) {
            copyApiButton.addEventListener('click', function(e) {
                e.preventDefault();
                const apiUrl = PublicPortalConfig.apiUrl;
                PublicUtils.copyToClipboard(apiUrl);
            });
        }
        
        // Add smooth scroll to footer links
        const footerLinks = document.querySelectorAll('.footer-links a[href^="#"]');
        footerLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        const offsetTop = target.offsetTop - 80;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
        
        // Add loading animation to external links
        const externalLinks = document.querySelectorAll('.footer-links a[target="_blank"]');
        externalLinks.forEach(link => {
            link.addEventListener('click', function() {
                const originalText = this.textContent;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + originalText;
                
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            });
        });
        
        // Animate footer elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe footer sections
        const footerSections = document.querySelectorAll('.footer-section');
        footerSections.forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(section);
        });
        
        // Add hover effects to footer badges
        const badgeItems = document.querySelectorAll('.badge-item');
        badgeItems.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.background = 'rgba(255, 255, 255, 0.2)';
            });
            
            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.background = 'rgba(255, 255, 255, 0.1)';
            });
        });
        
        // Update statistics periodically (every 5 minutes)
        setInterval(function() {
            updateFooterStatistics();
        }, 300000); // 5 minutes
    });
    
    // Update footer statistics
    function updateFooterStatistics() {
        // This could fetch real-time data from API
        // For now, we'll just update the timestamp
        updateLastUpdateTime();
    }
    
    // Handle keyboard navigation for back to top
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Home' && e.ctrlKey) {
            e.preventDefault();
            scrollToTop();
        }
    });
    
    // Add accessibility improvements
    document.addEventListener('DOMContentLoaded', function() {
        // Add ARIA labels to social links
        const socialLinks = document.querySelectorAll('.social-links a');
        const socialPlatforms = ['Facebook', 'Twitter', 'Instagram', 'YouTube'];
        
        socialLinks.forEach((link, index) => {
            if (socialPlatforms[index]) {
                link.setAttribute('aria-label', `Kunjungi halaman ${socialPlatforms[index]} kami`);
            }
        });
        
        // Add ARIA label to back to top button
        const backToTopButton = document.getElementById('backToTop');
        if (backToTopButton) {
            backToTopButton.setAttribute('aria-label', 'Kembali ke atas halaman');
        }
        
        // Improve focus management
        const focusableElements = document.querySelectorAll('.footer-links a, .social-links a, #backToTop');
        focusableElements.forEach(element => {
            element.addEventListener('focus', function() {
                this.style.outline = '2px solid #DBE2EF';
                this.style.outlineOffset = '2px';
            });
            
            element.addEventListener('blur', function() {
                this.style.outline = 'none';
            });
        });
    });
    
})();