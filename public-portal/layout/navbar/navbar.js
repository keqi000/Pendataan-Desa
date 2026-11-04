// Navbar JavaScript untuk Sistem Informasi Publik
// File: public-portal/layout/navbar/navbar.js

(function() {
    'use strict';
    
    // Toggle mobile menu
    window.toggleMobileMenu = function() {
        const navbarMenu = document.getElementById('navbarMenu');
        const navbarToggle = document.querySelector('.navbar-toggle');
        
        if (navbarMenu && navbarToggle) {
            navbarMenu.classList.toggle('active');
            navbarToggle.classList.toggle('active');
        }
    };
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        const navbarMenu = document.getElementById('navbarMenu');
        const navbarToggle = document.querySelector('.navbar-toggle');
        const navbar = document.querySelector('.public-navbar');
        
        if (navbarMenu && navbarToggle && navbar) {
            if (!navbar.contains(e.target)) {
                navbarMenu.classList.remove('active');
                navbarToggle.classList.remove('active');
            }
        }
    });
    
    // Close mobile menu when clicking on a link
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.nav-link');
        const navbarMenu = document.getElementById('navbarMenu');
        const navbarToggle = document.querySelector('.navbar-toggle');
        const navbar = document.querySelector('.public-navbar');
        
        // Close menu on nav link click
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (!this.classList.contains('dropdown-toggle')) {
                    if (navbarMenu && navbarToggle) {
                        navbarMenu.classList.remove('active');
                        navbarToggle.classList.remove('active');
                    }
                }
            });
        });
        
        // Handle dropdown on mobile
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    const dropdownMenu = this.closest('.dropdown').querySelector('.dropdown-menu');
                    if (dropdownMenu) {
                        dropdownMenu.style.display = 
                            dropdownMenu.style.display === 'block' ? 'none' : 'block';
                    }
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                if (navbarMenu && navbarToggle) {
                    navbarMenu.classList.remove('active');
                    navbarToggle.classList.remove('active');
                }
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = '';
                });
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
                    }
                }
            });
        });
        
        // Scroll effect on navbar
        if (navbar) {
            navbar.style.transition = 'transform 0.3s ease-in-out';
            let lastScrollTop = 0;
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                navbar.style.transform = (scrollTop > lastScrollTop && scrollTop > 100)
                    ? 'translateY(-100%)' : 'translateY(0)';
                lastScrollTop = scrollTop;
            });
        }
    });
    
})();