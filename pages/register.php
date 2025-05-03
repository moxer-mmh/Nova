<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../backend/config/config.php';  // Add this line to fix PASSWORD_MIN_LENGTH
require_once __DIR__ . '/../backend/models/User.php';

// Redirection si déjà connecté
if (Session::isLoggedIn()) {
    header('Location: /Nova/index.php');
    exit;
}

// Titre de la page
$pageTitle = 'Inscription';

// Initialisation des variables
$username = '';
$email = '';
$firstName = '';
$lastName = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Tous les champs sont requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        $user = new User();
        
        // Vérifier si l'utilisateur existe déjà
        if ($user->getUserByUsername($username)) {
            $error = 'Ce nom d\'utilisateur est déjà utilisé';
        } elseif ($user->getUserByEmail($email)) {
            $error = 'Cet email est déjà utilisé';
        } else {
            // Création de l'utilisateur
            $userId = $user->createUser($username, $email, $password, $firstName, $lastName);
            
            if ($userId) {
                // Connexion automatique
                Session::set('user_id', $userId);
                Session::set('username', $username);
                Session::set('is_admin', 0);
                
                // Régénérer l'ID de session
                Session::regenerate();
                
                // Redirection
                header('Location: /Nova/pages/account.php');
                exit;
            } else {
                $error = 'Erreur lors de la création du compte';
            }
        }
    }
}

// Inclusion de l'en-tête
require_once __DIR__ . '/../backend/includes/header.php';
?>

<div class="auth-container">
    <h1>Créer un compte</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="post" action="" class="auth-form">
        <div class="form-row">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">Prénom</label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($firstName); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Nom</label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($lastName); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small class="form-text text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> caractères</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
        </div>
        
        <div class="auth-links">
            <p>Déjà inscrit? <a href="/Nova/pages/login.php">Se connecter</a></p>
        </div>
    </form>
</div>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../backend/includes/footer.php';
?>
