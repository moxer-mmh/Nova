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

// Get categories for dropdown
$categories = $categoryModel->getAllCategories();

// Get book ID from URL
$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$book = $bookId ? $bookModel->getBookById($bookId) : null;

// Check if book exists
if ($bookId && !$book) {
    redirect('/pages/admin/books.php');
    exit;
}

// Process form submission
$errors = [];
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $isbn = trim($_POST['isbn'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publicationDate = trim($_POST['publication_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval(str_replace(',', '.', $_POST['price'] ?? 0));
    $stock = intval($_POST['stock'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate form data
    if (empty($title)) $errors[] = "Le titre est requis.";
    if (empty($author)) $errors[] = "L'auteur est requis.";
    if ($categoryId <= 0) $errors[] = "La catégorie est requise.";
    if ($price <= 0) $errors[] = "Le prix doit être supérieur à 0.";
    if ($stock < 0) $errors[] = "Le stock ne peut pas être négatif.";
    
    // Handle image upload if provided
    $imageUrl = $book ? $book['IMAGE_URL'] : '';
    if (!empty($_FILES['image']['name'])) {
        $uploadResult = imageUpload($_FILES['image'], 'book_');
        if ($uploadResult['success']) {
            $imageUrl = $uploadResult['filename'];
        } else {
            $errors[] = "Erreur lors du téléchargement de l'image: " . $uploadResult['message'];
        }
    }
    
    // If no errors, save book
    if (empty($errors)) {
        $bookData = [
            'title' => $title,
            'author' => $author,
            'category_id' => $categoryId,
            'isbn' => $isbn,
            'publisher' => $publisher,
            'publication_date' => $publicationDate,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image_url' => $imageUrl,
            'featured' => $featured
        ];
        
        if ($bookId) {
            // Update existing book
            $result = $bookModel->updateBook($bookId, $bookData);
            if ($result) {
                $successMessage = "Livre mis à jour avec succès.";
            } else {
                $errors[] = "Erreur lors de la mise à jour du livre.";
            }
        } else {
            // Create new book
            $newBookId = $bookModel->createBook($bookData);
            if ($newBookId) {
                redirect('/pages/admin/books.php?success=created');
            } else {
                $errors[] = "Erreur lors de la création du livre.";
            }
        }
    }
}

// Set page title
$pageTitle = $bookId ? 'Modifier un livre' : 'Ajouter un livre';

// Include header
require_once __DIR__ . '/../../backend/includes/header.php';
?>

<main class="container">
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../backend/includes/admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <h1><?php echo $pageTitle; ?></h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($successMessage): ?>
                <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Titre*</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo isset($book) ? htmlspecialchars($book['TITLE']) : htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Auteur*</label>
                        <input type="text" id="author" name="author" class="form-control" required
                               value="<?php echo isset($book) ? htmlspecialchars($book['AUTHOR']) : htmlspecialchars($_POST['author'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Catégorie*</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['CATEGORY_ID']; ?>" <?php echo (isset($book) && $book['CATEGORY_ID'] == $category['CATEGORY_ID']) || (isset($_POST['category_id']) && $_POST['category_id'] == $category['CATEGORY_ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['NAME']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn" class="form-control"
                               value="<?php echo isset($book) ? htmlspecialchars($book['ISBN']) : htmlspecialchars($_POST['isbn'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="publisher">Éditeur</label>
                        <input type="text" id="publisher" name="publisher" class="form-control"
                               value="<?php echo isset($book) ? htmlspecialchars($book['PUBLISHER']) : htmlspecialchars($_POST['publisher'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="publication_date">Date de publication</label>
                        <input type="date" id="publication_date" name="publication_date" class="form-control"
                               value="<?php echo isset($book) ? htmlspecialchars(date('Y-m-d', strtotime($book['PUBLICATION_DATE']))) : htmlspecialchars($_POST['publication_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5"><?php 
                        if (isset($book) && $book['DESCRIPTION'] instanceof OCILob) {
                            echo htmlspecialchars($book['DESCRIPTION']->load());
                        } else if (isset($book)) {
                            echo htmlspecialchars($book['DESCRIPTION'] ?? '');
                        } else {
                            echo htmlspecialchars($_POST['description'] ?? '');
                        }
                    ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Prix*</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required
                               value="<?php echo isset($book) ? htmlspecialchars($book['PRICE']) : htmlspecialchars($_POST['price'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock*</label>
                        <input type="number" id="stock" name="stock" class="form-control" min="0" required
                               value="<?php echo isset($book) ? htmlspecialchars($book['STOCK']) : htmlspecialchars($_POST['stock'] ?? '0'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Image</label>
                    <?php if (isset($book) && $book['IMAGE_URL']): ?>
                        <div class="current-image">
                            <img src="/Nova/frontend/assets/images/books/<?php echo htmlspecialchars($book['IMAGE_URL']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['TITLE']); ?>" 
                                 style="max-height: 100px; margin-bottom: 10px;"
                                 onerror="this.src='/Nova/frontend/assets/images/books/placeholder.jpg'">
                            <p>Image actuelle: <?php echo htmlspecialchars($book['IMAGE_URL']); ?></p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/gif">
                    <small class="form-text">Format JPEG, PNG ou GIF, max 2Mo</small>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="featured" name="featured" value="1"
                               <?php echo isset($book) && $book['FEATURED'] == 1 ? 'checked' : ''; ?>>
                        <label for="featured">Mettre en avant sur la page d'accueil</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="/Nova/pages/admin/books.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary"><?php echo $bookId ? 'Mettre à jour' : 'Ajouter'; ?></button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../backend/includes/footer.php'; ?>
