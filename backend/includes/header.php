<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/cookie.php';
require_once __DIR__ . '/../../backend/config/config.php';

// Fonction pour afficher un message flash
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $message) {
            echo '<div class="alert alert-' . $type . '">' . $message . '</div>';
        }
        unset($_SESSION['flash']);
    }
}

// Définir un message flash
function setFlash($message, $type = 'info') {
    $_SESSION['flash'][$type] = $message;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Nova' : 'Nova - Librairie en ligne'; ?></title>
    <link rel="stylesheet" href="/Nova/frontend/assets/css/style.css">
    <link rel="stylesheet" href="/Nova/frontend/assets/css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Add error handler for images -->
    <script>
    function handleImageError(img) {
        img.onerror = null; // Prevent infinite loop
        img.src = '/Nova/frontend/assets/images/books/placeholder.jpg';
    }
    </script>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="/Nova/index.php">Nova</a>
            </div>
            
            <div class="menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <ul class="nav-links">
                <li><a href="/Nova/index.php">Accueil</a></li>
                <li><a href="/Nova/pages/search.php">Catalogue</a></li>
                <?php if (Session::isAdmin()): ?>
                    <li><a href="/Nova/pages/admin/dashboard.php">Administration</a></li>
                <?php endif; ?>
                
                <li>
                    <a href="/Nova/pages/cart.php">
                        Panier <span id="cart-count" style="display:none;">0</span>
                    </a>
                </li>
                
                <?php if (Session::isLoggedIn()): ?>
                    <li><a href="/Nova/pages/account.php">Mon compte</a></li>
                    <li><a href="/Nova/pages/logout.php">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="/Nova/pages/login.php">Connexion</a></li>
                    <li><a href="/Nova/pages/register.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <main>
        <div class="container">
            <?php displayFlash(); ?>
