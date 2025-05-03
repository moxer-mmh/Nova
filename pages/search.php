<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../backend/models/Book.php';

// Titre de la page
$pageTitle = 'Catalogue';

// Paramètres de recherche
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? intval($_GET['category']) : null;

// Récupération des catégories pour le filtre
$book = new Book();
$categories = $book->getCategories();

// Exécution de la recherche
$books = $book->searchBooks($query, $categoryId);

// Scripts supplémentaires
$extraScripts = ['/Nova/frontend/assets/js/cart.js', '/Nova/frontend/assets/js/search.js'];

// Inclusion de l'en-tête
require_once __DIR__ . '/../backend/includes/header.php';
?>

<div class="search-container">
    <div class="search-filters">
        <h2>Filtres</h2>
        <form action="" method="get" id="search-form">
            <div class="form-group">
                <label for="search-input">Recherche</label>
                <input type="text" id="search-input" name="q" class="form-control" value="<?php echo htmlspecialchars($query); ?>" placeholder="Titre, auteur...">
            </div>
            
            <div class="form-group">
                <label for="category">Catégorie</label>
                <select name="category" id="category" class="form-control">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['CATEGORY_ID']; ?>" <?php echo ($categoryId == $category['CATEGORY_ID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['NAME']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Filtrer</button>
        </form>
    </div>
    
    <div class="search-results">
        <h1>
            <?php if (!empty($query) || !is_null($categoryId)): ?>
                Résultats de recherche
            <?php else: ?>
                Tous les livres
            <?php endif; ?>
            <span class="result-count">(<?php echo count($books); ?> livres)</span>
        </h1>
        
        <?php if (empty($books)): ?>
            <div class="no-results">
                <p>Aucun livre ne correspond à votre recherche.</p>
            </div>
        <?php else: ?>
            <div class="books-container">
                <?php foreach ($books as $book): ?>
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
        <?php endif; ?>
    </div>
</div>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../backend/includes/footer.php';
?>
