        </div> <!-- Fin du container -->
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>À propos de Nova</h3>
                    <p>Nova est une librairie en ligne proposant un large choix de livres. Notre mission est de partager notre passion pour la littérature avec le plus grand nombre.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Liens utiles</h3>
                    <ul class="footer-links">
                        <li><a href="/Nova/index.php">Accueil</a></li>
                        <li><a href="/Nova/pages/search.php">Catalogue</a></li>
                        <li><a href="/Nova/pages/cart.php">Panier</a></li>
                        <li><a href="/Nova/pages/account.php">Mon compte</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contactez-nous</h3>
                    <p>Email: contact@nova-books.com</p>
                    <p>Téléphone: +33 1 23 45 67 89</p>
                    <p>Adresse: 123 Avenue des Livres, 75001 Paris</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Nova Books. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/Nova/frontend/assets/js/main.js"></script>
    <?php if (isset($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
