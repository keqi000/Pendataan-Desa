// Pengaturan FAQ Tab Functions
(function() {
    'use strict';
    
    class PengaturanFAQ {
        constructor() {
            this.init();
        }
        
        init() {
            this.setupFAQToggle();
        }
        
        setupFAQToggle() {
            // Global FAQ toggle function
            window.toggleFaq = (element) => {
                const answer = element.nextElementSibling;
                
                if (answer.classList.contains('show')) {
                    answer.classList.remove('show');
                    element.classList.remove('active');
                } else {
                    document.querySelectorAll('.faq-answer').forEach(ans => {
                        ans.classList.remove('show');
                    });
                    document.querySelectorAll('.faq-question').forEach(q => {
                        q.classList.remove('active');
                    });
                    
                    answer.classList.add('show');
                    element.classList.add('active');
                }
            };
        }
    }
    
    // Initialize FAQ functionality
    window.PengaturanFAQ = new PengaturanFAQ();
})();