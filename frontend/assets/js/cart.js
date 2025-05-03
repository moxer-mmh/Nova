/**
 * Cart functionality for Nova Book Store
 */

// DOM Elements
const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
const quantityInputs = document.querySelectorAll('.cart-quantity-input');
const removeButtons = document.querySelectorAll('.remove-from-cart-btn');
const cartTotalElement = document.getElementById('cart-total');
const cartForm = document.getElementById('cart-form');

// Add to cart functionality
if (addToCartButtons.length > 0) {
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const bookId = this.dataset.id;
            const quantityElement = document.getElementById('quantity');
            const quantity = quantityElement ? parseInt(quantityElement.value, 10) : 1;
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = 'Ajout en cours...';
            const originalText = this.innerHTML;
            
            // Add to cart via AJAX
            fetch('/Nova/backend/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add&book_id=${bookId}&quantity=${quantity}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server responded with ${response.status}`);
                }
                return response.text(); // Get response as text first
            })
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text); // Try to parse as JSON
                } catch(e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid server response');
                }
                
                if (data.success) {
                    showToast('Livre ajouté au panier!', 'success');
                    updateCartCount();
                } else {
                    showToast('Erreur: ' + (data.message || 'Une erreur est survenue'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Une erreur est survenue lors de l\'ajout au panier', 'error');
            })
            .finally(() => {
                // Restore button state
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    });
}

// Update quantity functionality
if (quantityInputs.length > 0) {
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const itemId = this.dataset.id;
            const quantity = parseInt(this.value);
            
            if (quantity < 1) {
                this.value = 1;
                return;
            }
            
            updateCartItem(itemId, quantity);
        });
    });
}

// Remove from cart functionality
if (removeButtons.length > 0) {
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const itemId = this.dataset.id;
            
            // Remove from cart via AJAX
            fetch('/Nova/backend/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=remove&item_id=${itemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove row from table
                    this.closest('tr').remove();
                    updateCartTotal(data.total);
                    updateCartCount();
                    
                    if (data.count === 0) {
                        document.querySelector('.cart-table').style.display = 'none';
                        document.getElementById('cart-empty-message').style.display = 'block';
                        document.getElementById('checkout-btn').style.display = 'none';
                    }
                    
                    showToast('Article supprimé du panier', 'info');
                } else {
                    showToast('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Une erreur est survenue', 'error');
            });
        });
    });
}

// Update cart item quantity
function updateCartItem(itemId, quantity) {
    fetch('/Nova/backend/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=update&item_id=${itemId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update item subtotal
            document.getElementById(`item-subtotal-${itemId}`).textContent = 
                formatPrice(data.item_subtotal);
                
            // Update cart total
            updateCartTotal(data.total);
            
            showToast('Panier mis à jour', 'info');
        } else {
            showToast('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Une erreur est survenue', 'error');
    });
}

// Update cart total display
function updateCartTotal(total) {
    if (cartTotalElement) {
        cartTotalElement.textContent = formatPrice(total);
    }
}

// Increment and decrement buttons
document.querySelectorAll('.quantity-btn').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.closest('.cart-quantity').querySelector('input');
        const itemId = input.dataset.id;
        let quantity = parseInt(input.value);
        
        if (this.classList.contains('increment-btn')) {
            quantity++;
        } else {
            quantity = Math.max(1, quantity - 1);
        }
        
        input.value = quantity;
        updateCartItem(itemId, quantity);
    });
});
