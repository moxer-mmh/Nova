<?php
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration de session sécurisée
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            
            // En production, ajouter HTTPS
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    public static function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }
    
    public static function destroy() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function regenerate() {
        return session_regenerate_id(true);
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /Nova/pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
    
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: /Nova/pages/login.php');
            exit;
        }
    }
}

// Démarrer la session sur toutes les pages qui incluent ce fichier
Session::start();
