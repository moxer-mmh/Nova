<?php
// Configuration globale du site
define('SITE_NAME', 'Nova Books');
define('BASE_URL', '/Nova');
define('UPLOAD_DIR', __DIR__ . '/../../frontend/assets/images/books/');

// Configuration de pagination
define('ITEMS_PER_PAGE', 12);

// Configuration des emails
define('EMAIL_FROM', 'noreply@nova-books.com');
define('EMAIL_NAME', 'Nova Books');

// Sécurité
define('CSRF_TOKEN_SECRET', 'change_this_to_a_random_string');
define('PASSWORD_MIN_LENGTH', 8);

// Configuration de la base de données
// REMPLACER PAR VOS INFORMATIONS DE CONNEXION
define('DB_USER', 'Nova');
define('DB_PASS', 'votre_mot_de_passe');
define('DB_CONN_STR', '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XE)))');

// Fonctions utilitaires
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

function cleanFilename($string) {
    $string = preg_replace('/[^\p{L}\p{N}]/u', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return strtolower(trim($string, '-'));
}

function imageUpload($file, $prefix = '') {
    $result = [
        'success' => false,
        'message' => '',
        'filename' => ''
    ];
    
    // Vérification des erreurs
    if ($file['error'] != UPLOAD_ERR_OK) {
        $result['message'] = 'Erreur lors du téléchargement';
        return $result;
    }
    
    // Vérification du type de fichier
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $result['message'] = 'Type de fichier non autorisé';
        return $result;
    }
    
    // Vérification de la taille (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        $result['message'] = 'Le fichier est trop volumineux (max 2Mo)';
        return $result;
    }
    
    // Make sure upload directory exists
    $uploadDir = UPLOAD_DIR;
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $result['message'] = 'Impossible de créer le répertoire de destination';
            return $result;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        $result['message'] = 'Le répertoire de destination n\'est pas accessible en écriture';
        return $result;
    }
    
    // Création d'un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = $prefix . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $uniqueName;
    
    // Déplacement du fichier avec proper path handling
    $targetPath = str_replace('/', DIRECTORY_SEPARATOR, $targetPath);
    $targetPath = str_replace('\\', DIRECTORY_SEPARATOR, $targetPath);
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $result['message'] = 'Erreur lors du déplacement du fichier';
        return $result;
    }
    
    $result['success'] = true;
    $result['filename'] = $uniqueName;
    return $result;
}

/**
 * Safely converts potential OCILob objects to strings
 * @param mixed $value The value that might be an OCILob
 * @return string The string value
 */
function convertLobToString($value) {
    if ($value instanceof OCILob) {
        return $value->load();
    }
    return (string)$value;
}
