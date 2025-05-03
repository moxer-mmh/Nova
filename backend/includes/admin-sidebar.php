<div class="admin-sidebar">
    <h3>Administration</h3>
    <ul class="admin-menu">
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], '/pages/admin/dashboard.php') !== false ? 'active' : ''; ?>">
            <a href="/Nova/pages/admin/dashboard.php">Tableau de bord</a>
        </li>
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], '/pages/admin/books.php') !== false || strpos($_SERVER['REQUEST_URI'], '/pages/admin/edit-book.php') !== false ? 'active' : ''; ?>">
            <a href="/Nova/pages/admin/books.php">Gérer les livres</a>
        </li>
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], '/pages/admin/categories.php') !== false ? 'active' : ''; ?>">
            <a href="/Nova/pages/admin/categories.php">Gérer les catégories</a>
        </li>
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], '/pages/admin/orders.php') !== false || strpos($_SERVER['REQUEST_URI'], '/pages/admin/order-details.php') !== false ? 'active' : ''; ?>">
            <a href="/Nova/pages/admin/orders.php">Gérer les commandes</a>
        </li>
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], '/pages/admin/users.php') !== false ? 'active' : ''; ?>">
            <a href="/Nova/pages/admin/users.php">Gérer les utilisateurs</a>
        </li>
        <li>
            <a href="/Nova/index.php">Retour au site</a>
        </li>
    </ul>
</div>
