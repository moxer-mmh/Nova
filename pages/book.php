<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../backend/models/Book.php';

// Vérification de l'ID du livre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /Nova/index.php');
    exit;
}

$bookId = intval($_GET['id']);

// Récupération des informations du livre
$book = new Book();
$bookInfo = $book->getBookById($bookId);

// Si le livre n'existe pas, redirection
if (!$bookInfo) {
    header('Location: /Nova/index.php');
    exit;
}

// Titre de la page
$pageTitle = htmlspecialchars($bookInfo['TITLE']);

// Scripts supplémentaires
$extraScripts = ['/Nova/frontend/assets/js/cart.js'];

// Inclusion de l'en-tête
require_once __DIR__ . '/../backend/includes/header.php';
?>

<div class="breadcrumb">
    <a href="/Nova/index.php">Accueil</a> &gt; 
    <a href="/Nova/pages/search.php?category=<?php echo $bookInfo['CATEGORY_ID']; ?>">
        <?php echo htmlspecialchars($bookInfo['CATEGORY_NAME']); ?>
    </a> &gt; 
    <span><?php echo htmlspecialchars($bookInfo['TITLE']); ?></span>
</div>

<div class="book-detail">
    <div class="book-detail-image">
        <img src="/Nova/frontend/assets/images/books/<?php echo $bookInfo['IMAGE_URL'] ? $bookInfo['IMAGE_URL'] : 'default.jpg'; ?>" 
             alt="<?php echo htmlspecialchars($bookInfo['TITLE']); ?>">
    </div>
    
    <div class="book-detail-info">
        <h1 class="book-detail-title"><?php echo htmlspecialchars($bookInfo['TITLE']); ?></h1>
        <div class="book-detail-author">par <?php echo htmlspecialchars($bookInfo['AUTHOR']); ?></div>
        <div class="book-detail-price"><?php echo formatPrice($bookInfo['PRICE']); ?></div>
        
        <div class="book-availability">
            <?php if ($bookInfo['STOCK'] > 0): ?>
                <span class="in-stock">En stock (<?php echo $bookInfo['STOCK']; ?> exemplaires)</span>
            <?php else: ?>
                <span class="out-of-stock">Épuisé</span>
            <?php endif; ?>
        </div>
        
        <?php if ($bookInfo['STOCK'] > 0): ?>
            <div class="book-actions">
                <div class="quantity-selector">
                    <label for="quantity">Quantité:</label>
                    <select id="quantity" name="quantity">
                        <?php for($i = 1; $i <= min(10, $bookInfo['STOCK']); $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button class="btn btn-primary add-to-cart-btn" data-id="<?php echo $bookInfo['BOOK_ID']; ?>">
                    Ajouter au panier
                </button>
            </div>
        <?php endif; ?>
        
        <div class="book-detail-description">
            <h3>Description</h3>
            <?php 
            // Fixed CLOB handling
            if ($book['DESCRIPTION'] instanceof OCILob) {
                echo nl2br(htmlspecialchars($book['DESCRIPTION']->load()));
            } else {
                echo nl2br(htmlspecialchars($book['DESCRIPTION'] ?? ''));
            }
            ?>
        </div>
        
        <div class="book-meta">
            <div class="book-meta-item">
                <span class="book-meta-label">ISBN:</span>
                <span class="book-meta-value"><?php echo htmlspecialchars($bookInfo['ISBN']); ?></span>
            </div>
            <div class="book-meta-item">
                <span class="book-meta-label">Éditeur:</span>
                <span class="book-meta-value"><?php echo htmlspecialchars($bookInfo['PUBLISHER']); ?></span>
            </div>
            <div class="book-meta-item">
                <span class="book-meta-label">Date de publication:</span>
                <span class="book-meta-value">
                    <?php 
                        $date = new DateTime($bookInfo['PUBLICATION_DATE']);
                        echo $date->format('d/m/Y'); 
                    ?>
                </span>
            </div>
            <div class="book-meta-item">
                <span class="book-meta-label">Catégorie:</span>
                <span class="book-meta-value">
                    <a href="/Nova/pages/search.php?category=<?php echo $bookInfo['CATEGORY_ID']; ?>">
                        <?php echo htmlspecialchars($bookInfo['CATEGORY_NAME']); ?>
                    </a>
                </span>
            </div>
        </div>
    </div>
</div>

<?php
// Récupérer des livres similaires
$similarBooks = $book->searchBooks('', $bookInfo['CATEGORY_ID']);
// Filtrer pour exclure le livre actuel et limiter à 4 livres
$similarBooks = array_filter($similarBooks, function($item) use ($bookId) {
    return $item['BOOK_ID'] != $bookId;
});
$similarBooks = array_slice($similarBooks, 0, 4);

if (!empty($similarBooks)):
?>
<section class="similar-books">
    <h2>Vous pourriez aussi aimer</h2>
    <div class="books-container">
        <?php foreach ($similarBooks as $similarBook): ?>
            <div class="book-card">
                <div class="book-image">
                    <a href="/Nova/pages/book.php?id=<?php echo $similarBook['BOOK_ID']; ?>">
                        <img src="/Nova/frontend/assets/images/books/<?php echo $similarBook['IMAGE_URL'] ? $similarBook['IMAGE_URL'] : 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($similarBook['TITLE']); ?>">
                    </a>
                </div>
                <div class="book-info">
                    <h3 class="book-title">
                        <a href="/Nova/pages/book.php?id=<?php echo $similarBook['BOOK_ID']; ?>">
                            <?php echo htmlspecialchars($similarBook['TITLE']); ?>
                        </a>
                    </h3>
                    <div class="book-author"><?php echo htmlspecialchars($similarBook['AUTHOR']); ?></div>
                    <div class="book-price"><?php echo formatPrice($similarBook['PRICE']); ?></div>
                    <button class="btn btn-primary add-to-cart-btn" data-id="<?php echo $similarBook['BOOK_ID']; ?>">
                        Ajouter au panier
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../backend/includes/footer.php';
?>
