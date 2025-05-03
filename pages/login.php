<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../backend/models/User.php';

// Redirection si déjà connecté
if (Session::isLoggedIn()) {
    header('Location: /Nova/index.php');
    exit;
}

// Titre de la page
$pageTitle = 'Connexion';

// Initialisation des variables
$username = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Tous les champs sont requis';
    } else {
        // Vérification des identifiants
        $user = new User();
        $loggedUser = $user->validateLogin($username, $password);
        
        if ($loggedUser) {
            // Connexion réussie
            Session::set('user_id', $loggedUser['USER_ID']);
            Session::set('username', $loggedUser['USERNAME']);
            Session::set('is_admin', $loggedUser['IS_ADMIN']);
            
            // Régénérer l'ID de session pour éviter la fixation de session
            Session::regenerate();
            
            // Redirection
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/Nova/index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Identifiants invalides';
        }
    }
}

// Inclusion de l'en-tête
require_once __DIR__ . '/../backend/includes/header.php';
?>

<div class="auth-container">
    <h1>Connexion</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="post" action="" class="auth-form">
        <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </div>
        
        <div class="auth-links">
            <p>Pas encore de compte? <a href="/Nova/pages/register.php">S'inscrire</a></p>
        </div>
    </form>
</div>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../backend/includes/footer.php';
?>
