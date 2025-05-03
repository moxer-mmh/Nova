<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/models/Book.php';
require_once __DIR__ . '/../../backend/models/Category.php';

// Check if user is admin
if (!Session::get('is_admin')) {
    redirect('/pages/login.php');
    exit;
}

// Initialize models
$bookModel = new Book();
$categoryModel = new Category();

// Get categories for filtering
$categories = $categoryModel->getAllCategories();

// Handle filter
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get books with filtering and pagination
$books = $bookModel->getAllBooks($limit, $offset, $categoryFilter, $search);
$totalBooks = $bookModel->getTotalBooksCount($categoryFilter, $search);
$totalPages = ceil($totalBooks / $limit);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $bookId = intval($_POST['book_id']);
    $result = $bookModel->deleteBook($bookId);
    
    if ($result === true) {
        $deleteMessage = 'Livre supprimé avec succès.';
    } elseif ($result === 'inactive') {
        $deleteMessage = 'Le livre ne peut pas être supprimé car il a des commandes associées. Il a été désactivé (stock = 0).';
    } else {
        $deleteMessage = 'Erreur lors de la suppression du livre.';
    }
}

// Handle image upload for new book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $categoryId = intval($_POST['category_id']);
    $price = floatval(str_replace(',', '.', $_POST['price']));
    $stock = intval($_POST['stock']);
    
    $errors = [];
    if (empty($title)) $errors[] = "Le titre est requis.";
    if (empty($author)) $errors[] = "L'auteur est requis.";
    if ($categoryId <= 0) $errors[] = "La catégorie est requise.";
    if ($price <= 0) $errors[] = "Le prix doit être supérieur à 0.";
    if ($stock < 0) $errors[] = "Le stock ne peut pas être négatif.";
    
    // Handle image upload if provided
    $imageUrl = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadResult = imageUpload($_FILES['image'], 'book_');
        if ($uploadResult['success']) {
            $imageUrl = $uploadResult['filename'];
        } else {
            $errors[] = "Erreur lors du téléchargement de l'image: " . $uploadResult['message'];
        }
    }
    
    if (empty($errors)) {
        $bookData = [
            'title' => $title,
            'author' => $author,
            'category_id' => $categoryId,
            'price' => $price,
            'stock' => $stock,
            'image_url' => $imageUrl,
            'featured' => isset($_POST['featured']) ? 1 : 0
        ];
        
        $newBookId = $bookModel->createBook($bookData);
        if ($newBookId) {
            $createMessage = "Livre créé avec succès.";
        } else {
            $createError = "Erreur lors de la création du livre.";
        }
    }
}

// Set page title
$pageTitle = 'Gérer les livres';

// Include header
require_once __DIR__ . '/../../backend/includes/header.php';
?>

<main class="container">
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../backend/includes/admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <h1>Gestion des livres</h1>
            
            <?php if (isset($deleteMessage)): ?>
                <div class="alert alert-success"><?php echo $deleteMessage; ?></div>
            <?php endif; ?>
            
            <?php if (isset($createMessage)): ?>
                <div class="alert alert-success"><?php echo $createMessage; ?></div>
            <?php endif; ?>
            
            <?php if (isset($createError)): ?>
                <div class="alert alert-danger"><?php echo $createError; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="admin-actions">
                <a href="/Nova/pages/admin/edit-book.php" class="btn btn-primary">Ajouter un livre</a>
            </div>
            
            <div class="filter-container">
                <form method="get" class="search-form">
                    <div class="form-row">
                        <div class="form-group">
                            <select name="category" class="form-control">
                                <option value="0">Toutes les catégories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['CATEGORY_ID']; ?>" <?php echo $categoryFilter == $category['CATEGORY_ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Rechercher un livre..." 
                                   class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-secondary">Filtrer</button>
                            <a href="/Nova/pages/admin/books.php" class="btn btn-link">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if (empty($books)): ?>
                <div class="alert alert-info">Aucun livre trouvé.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Auteur</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <img src="/Nova/frontend/assets/images/books/<?php echo htmlspecialchars($book['IMAGE_URL'] ?: 'placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($book['TITLE']); ?>" 
                                         class="book-thumbnail"
                                         onerror="this.src='/Nova/frontend/assets/images/books/placeholder.jpg'">
                                </td>
                                <td><?php echo htmlspecialchars($book['TITLE']); ?></td>
                                <td><?php echo htmlspecialchars($book['AUTHOR']); ?></td>
                                <td><?php echo htmlspecialchars($book['CATEGORY_NAME']); ?></td>
                                <td><?php echo formatPrice($book['PRICE']); ?></td>
                                <td>
                                    <span class="<?php echo ($book['STOCK'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php echo $book['STOCK']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/Nova/pages/admin/edit-book.php?id=<?php echo $book['BOOK_ID']; ?>" class="btn btn-primary btn-sm">Éditer</a>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce livre ?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="book_id" value="<?php echo $book['BOOK_ID']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $categoryFilter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">&laquo; Précédent</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&category=<?php echo $categoryFilter; ?>&search=<?php echo urlencode($search); ?>" class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $categoryFilter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">Suivant &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../backend/includes/footer.php'; ?>
