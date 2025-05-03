/**
 * Search functionality for Nova Book Store
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle category filter change
    const categoryFilter = document.getElementById('category');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            document.getElementById('search-form').submit();
        });
    }
    
    // Handle sort order change
    const sortOrder = document.getElementById('sort');
    if (sortOrder) {
        sortOrder.addEventListener('change', function() {
            document.getElementById('search-form').submit();
        });
    }
});
