<div class="book-card">
    <div class="book-image">
        <img src="/Nova/frontend/assets/images/books/<?php echo htmlspecialchars($book['IMAGE_URL'] ?: 'placeholder.jpg'); ?>" 
             alt="<?php echo htmlspecialchars($book['TITLE']); ?>"
             onerror="handleImageError(this)">
    </div>
    <div class="book-info">
        <h3 class="book-title">
            <a href="/Nova/pages/book.php?id=<?php echo $book['BOOK_ID']; ?>">
                <?php echo htmlspecialchars($book['TITLE']); ?>
            </a>
        </h3>
        <div class="book-author"><?php echo htmlspecialchars($book['AUTHOR']); ?></div>
        <div class="book-price"><?php echo formatPrice($book['PRICE']); ?></div>
        <button class="btn btn-primary add-to-cart-btn" data-id="<?php echo $book['BOOK_ID']; ?>">
            Ajouter au panier
        </button>
    </div>
</div>
