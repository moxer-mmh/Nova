/**
 * Main JavaScript for Nova Book Store
 */

// DOM Elements
const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

// Mobile menu toggle
if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });
}

// Search functionality
const searchForm = document.getElementById('search-form');
const searchInput = document.getElementById('search-input');

if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
        if (searchInput.value.trim() === '') {
            e.preventDefault();
            alert('Veuillez entrer un terme de recherche');
        }
    });
}

// Flash messages auto-hide
const flashMessages = document.querySelectorAll('.alert');
if (flashMessages.length > 0) {
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 500);
        }, 5000);
    });
}

// Function to format prices
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

// Function to show a toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    const toastContainer = document.querySelector('.toast-container') || (() => {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    })();
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }, 100);
}

// Function to update cart count in header - using a fallback URL if the main one fails
function updateCartCount() {
    const cartCountElement = document.getElementById('cart-count');
    
    if (!cartCountElement) {
        return;
    }
    
    // Try to get the cart count
    fetch('/Nova/backend/api/cart.php?action=count')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text(); // Get as text first to check
        })
        .then(text => {
            let data;
            try {
                data = JSON.parse(text); // Try to parse as JSON
            } catch(e) {
                // If not valid JSON, fall back to stub
                throw new Error('Invalid JSON response');
            }
            
            // Update the cart count display
            if (data && typeof data.count !== 'undefined') {
                if (data.count > 0) {
                    cartCountElement.textContent = data.count;
                    cartCountElement.style.display = 'inline-block';
                } else {
                    cartCountElement.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
            // Fall back to stub cart for stability
            fetch('/Nova/utils/stub_cart.php')
                .then(response => response.json())
                .then(data => {
                    if (data.count > 0) {
                        cartCountElement.textContent = data.count;
                        cartCountElement.style.display = 'inline-block';
                    } else {
                        cartCountElement.style.display = 'none';
                    }
                })
                .catch(e => {
                    // If all fails, just hide the cart count
                    cartCountElement.style.display = 'none';
                });
        });
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
});
