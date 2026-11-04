// Laporan Main JavaScript
(function() {
    'use strict';
    
    // Tab switching function
    window.switchLaporanTab = function(tabName) {
        document.querySelectorAll('.laporan-admin-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        document.querySelectorAll('.laporan-admin-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
        
        if (tabName === 'generate' && window.LaporanGenerate) {
            window.LaporanGenerate.clearGenerateForm();
        }
    };
    
    // Toggle analysis level function
    window.toggleLevel = function(levelId) {
        const checkbox = document.getElementById(levelId);
        const content = document.getElementById(levelId + '-content');
        
        if (checkbox.checked) {
            content.classList.add('active');
        } else {
            content.classList.remove('active');
            // Uncheck all subcategories
            const subcategories = content.querySelectorAll('input[type="checkbox"]');
            subcategories.forEach(sub => {
                sub.checked = false;
                const subContent = document.getElementById(sub.id + '-content');
                if (subContent) {
                    subContent.classList.remove('active');
                }
            });
        }
    };
    
    // Toggle category function
    window.toggleCategory = function(categoryId) {
        const checkbox = document.getElementById(categoryId);
        const content = document.getElementById(categoryId + '-content');
        
        if (checkbox.checked) {
            content.classList.add('active');
        } else {
            content.classList.remove('active');
            // Uncheck all subcategory items
            const items = content.querySelectorAll('input[type="checkbox"]');
            items.forEach(item => {
                item.checked = false;
            });
        }
    };
    
    // Initialize hierarchical checkboxes
    function initHierarchicalCheckboxes() {
        // Handle level checkboxes
        document.querySelectorAll('.level-header input[type="checkbox"]').forEach(levelCheckbox => {
            levelCheckbox.addEventListener('change', function() {
                const levelId = this.id;
                const content = document.getElementById(levelId + '-content');
                
                if (this.checked) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                    // Uncheck all subcategories
                    const subcategories = content.querySelectorAll('input[type="checkbox"]');
                    subcategories.forEach(sub => {
                        sub.checked = false;
                        const subContent = document.getElementById(sub.id + '-content');
                        if (subContent) {
                            subContent.classList.remove('active');
                        }
                    });
                }
            });
        });
        
        // Handle category checkboxes
        document.querySelectorAll('.category-header input[type="checkbox"]').forEach(categoryCheckbox => {
            categoryCheckbox.addEventListener('change', function() {
                const categoryId = this.id;
                const content = document.getElementById(categoryId + '-content');
                
                if (this.checked) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                    // Uncheck all items in this category
                    const items = content.querySelectorAll('input[type="checkbox"]');
                    items.forEach(item => {
                        item.checked = false;
                    });
                }
            });
        });
        
        // Handle individual item checkboxes
        document.querySelectorAll('.sub-category input[type="checkbox"]').forEach(itemCheckbox => {
            itemCheckbox.addEventListener('change', function() {
                // Check if parent category should be checked/unchecked
                const categoryContent = this.closest('.category-content');
                if (categoryContent) {
                    const categoryId = categoryContent.id.replace('-content', '');
                    const categoryCheckbox = document.getElementById(categoryId);
                    const checkedItems = categoryContent.querySelectorAll('input[type="checkbox"]:checked');
                    
                    if (checkedItems.length > 0) {
                        categoryCheckbox.checked = true;
                        categoryContent.classList.add('active');
                        
                        // Also check parent level
                        const levelContent = this.closest('.level-content');
                        if (levelContent) {
                            const levelId = levelContent.id.replace('-content', '');
                            const levelCheckbox = document.getElementById(levelId);
                            levelCheckbox.checked = true;
                            levelContent.classList.add('active');
                        }
                    } else {
                        // Check if any other categories in this level are checked
                        const levelContent = this.closest('.level-content');
                        if (levelContent) {
                            const allCheckedInLevel = levelContent.querySelectorAll('input[type="checkbox"]:checked');
                            if (allCheckedInLevel.length === 0) {
                                const levelId = levelContent.id.replace('-content', '');
                                const levelCheckbox = document.getElementById(levelId);
                                levelCheckbox.checked = false;
                                levelContent.classList.remove('active');
                            }
                        }
                    }
                }
            });
        });
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initHierarchicalCheckboxes();
    });
})();