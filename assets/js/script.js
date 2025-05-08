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
});