<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/backend/models/Book.php';

// Titre de la page
$pageTitle = 'Accueil';

// Récupération des livres mis en avant
$book = new Book();
// Error handling for featured books
try {
    $featuredBooks = $book->getFeaturedBooks(4);
} catch (Exception $e) {
    error_log('Error getting featured books: ' . $e->getMessage());
    $featuredBooks = [];
}

// Error handling for new books
try {
    $newBooks = $book->getAllBooks(8, 0);
} catch (Exception $e) {
    error_log('Error getting new books: ' . $e->getMessage());
    $newBooks = [];
}

// Scripts supplémentaires
$extraScripts = ['/Nova/frontend/assets/js/cart.js'];

// Inclusion de l'en-tête
require_once __DIR__ . '/backend/includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <h1>Bienvenue sur Nova Books</h1>
        <p>Découvrez notre large sélection de livres pour tous les goûts</p>
        <a href="/Nova/pages/search.php" class="btn btn-primary">Explorer le catalogue</a>
    </div>
</section>

<section class="featured-books">
    <h2>Livres en vedette</h2>
    <div class="books-container">
        <?php foreach ($featuredBooks as $book): ?>
            <div class="book-card">
                <div class="book-image">
                    <a href="/Nova/pages/book.php?id=<?php echo $book['BOOK_ID']; ?>">
                        <img src="/Nova/frontend/assets/images/books/<?php echo $book['IMAGE_URL'] ? $book['IMAGE_URL'] : 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($book['TITLE']); ?>">
                    </a>
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
        <?php endforeach; ?>
    </div>
</section>

<section class="new-books">
    <h2>Nouveautés</h2>
    <div class="books-container">
        <?php foreach ($newBooks as $book): ?>
            <div class="book-card">
                <div class="book-image">
                    <a href="/Nova/pages/book.php?id=<?php echo $book['BOOK_ID']; ?>">
                        <img src="/Nova/frontend/assets/images/books/<?php echo $book['IMAGE_URL'] ? $book['IMAGE_URL'] : 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($book['TITLE']); ?>">
                    </a>
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
        <?php endforeach; ?>
    </div>
    <div class="view-all-link">
        <a href="/Nova/pages/search.php" class="btn btn-secondary">Voir tout le catalogue</a>
    </div>
</section>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/backend/includes/footer.php';
?>
