/**
 * Nova Gaming E-commerce Website JavaScript
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Product quantity controls
    const quantityInputs = document.querySelectorAll('.quantity-input');
    if (quantityInputs) {
        quantityInputs.forEach(input => {
            const minusBtn = input.previousElementSibling;
            const plusBtn = input.nextElementSibling;
            
            if (minusBtn && minusBtn.classList.contains('quantity-minus')) {
                minusBtn.addEventListener('click', function() {
                    if (input.value > 1) {
                        input.value = parseInt(input.value) - 1;
                    }
                });
            }
            
            if (plusBtn && plusBtn.classList.contains('quantity-plus')) {
                plusBtn.addEventListener('click', function() {
                    const maxStock = input.getAttribute('data-max-stock');
                    if (!maxStock || parseInt(input.value) < parseInt(maxStock)) {
                        input.value = parseInt(input.value) + 1;
                    }
                });
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts) {
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    }
    
    // Mobile navigation toggle
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }
    
    // Collapsible filter sidebar on mobile
    const filterToggle = document.querySelector('.filter-toggle');
    const filterContent = document.querySelector('.filter-content');
    
    if (filterToggle && filterContent) {
        filterToggle.addEventListener('click', function() {
            filterToggle.classList.toggle('collapsed');
            filterContent.classList.toggle('collapsed');
        });
    }
    
    // Handle data-labels for responsive tables
    function setupResponsiveTables() {
        const tables = document.querySelectorAll('.cart-table, .order-table, .admin-table');
        
        tables.forEach(table => {
            const headerCells = table.querySelectorAll('thead th');
            if (!headerCells.length) return;
            
            const headerTexts = Array.from(headerCells).map(cell => cell.textContent.trim());
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    const headerIndex = index % headerTexts.length;
                    if (!cell.hasAttribute('data-label')) {
                        cell.setAttribute('data-label', headerTexts[headerIndex]);
                    }
                });
            });
        });
    }
    
    setupResponsiveTables();
    
    // Product image preview in admin forms
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Category filter on products page
    const categoryFilters = document.querySelectorAll('.category-filter');
    if (categoryFilters) {
        categoryFilters.forEach(filter => {
            filter.addEventListener('change', function() {
                document.getElementById('filter-form').submit();
            });
        });
    }
    
    // Price range filter
    const priceRange = document.getElementById('price-range');
    const priceDisplay = document.getElementById('price-display');
    
    if (priceRange && priceDisplay) {
        priceRange.addEventListener('input', function() {
            priceDisplay.textContent = '$' + this.value;
        });
    }
    
    // Improved filter toggle for mobile
    function setupFilterToggle() {
        const filterToggle = document.querySelector('.filter-toggle');
        const filterContent = document.querySelector('.filter-content');
        
        if (filterToggle && filterContent) {
            filterToggle.addEventListener('click', function() {
                filterToggle.classList.toggle('collapsed');
                filterContent.classList.toggle('collapsed');
                
                const icon = filterToggle.querySelector('.filter-toggle-icon');
                if (icon) {
                    icon.textContent = filterToggle.classList.contains('collapsed') ? '▼' : '▲';
                }
            });
        }
    }
    
    setupFilterToggle();
});

// Add window resize handler for responsive elements
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
        // Hide mobile menu when resizing to desktop
        const navLinks = document.querySelector('.nav-links');
        const windowWidth = window.innerWidth;
        
        if (windowWidth > 768 && navLinks) {
            navLinks.classList.remove('show');
        }
        
        // Refresh responsive tables
        setupResponsiveTables();
    }, 250);
});