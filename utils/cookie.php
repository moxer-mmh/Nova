<?php
class Cookie {
    // Paramètres par défaut pour les cookies
    private static $defaultOptions = [
        'expires' => 0,          // Durée en secondes (0 = fin de session)
        'path' => '/',           // Chemin du cookie
        'domain' => '',          // Domaine du cookie
        'secure' => false,       // Cookie uniquement sur HTTPS
        'httponly' => true,      // Non accessible en JavaScript
        'samesite' => 'Lax'      // Protection contre CSRF
    ];
    
    public static function set($name, $value, $options = []) {
        $options = array_merge(self::$defaultOptions, $options);
        
        // Si une durée est spécifiée, calculer la date d'expiration
        if ($options['expires'] > 0) {
            $options['expires'] = time() + $options['expires'];
        }
        
        // En production, toujours utiliser HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $options['secure'] = true;
        }
        
        // PHP 7.3+ avec options d'array
        if (PHP_VERSION_ID >= 70300) {
            setcookie($name, $value, [
                'expires' => $options['expires'],
                'path' => $options['path'],
                'domain' => $options['domain'],
                'secure' => $options['secure'],
                'httponly' => $options['httponly'],
                'samesite' => $options['samesite']
            ]);
        } else {
            // PHP < 7.3 sans option samesite
            setcookie(
                $name,
                $value,
                $options['expires'],
                $options['path'] . '; samesite=' . $options['samesite'],
                $options['domain'],
                $options['secure'],
                $options['httponly']
            );
        }
    }
    
    public static function get($name, $default = null) {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
    }
    
    public static function delete($name) {
        if (isset($_COOKIE[$name])) {
            // Expirer le cookie en le définissant dans le passé
            self::set($name, '', ['expires' => -3600]);
            return true;
        }
        return false;
    }
    
    // Pour stocker un panier dans un cookie (sérialisé et encodé)
    public static function setCart($cartData) {
        $serialized = base64_encode(serialize($cartData));
        self::set('nova_cart', $serialized, ['expires' => 60 * 60 * 24 * 30]); // 30 jours
    }
    
    // Pour récupérer le panier depuis un cookie
    public static function getCart() {
        $cartData = self::get('nova_cart', '');
        if (!empty($cartData)) {
            try {
                return unserialize(base64_decode($cartData));
            } catch (Exception $e) {
                return [];
            }
        }
        return [];
    }
    
    // Pour les préférences utilisateur comme le thème
    public static function setPreference($key, $value) {
        $prefs = self::getPreferences();
        $prefs[$key] = $value;
        
        $serialized = base64_encode(serialize($prefs));
        self::set('nova_prefs', $serialized, ['expires' => 60 * 60 * 24 * 365]); // 1 an
    }
    
    public static function getPreferences() {
        $prefsData = self::get('nova_prefs', '');
        if (!empty($prefsData)) {
            try {
                return unserialize(base64_decode($prefsData));
            } catch (Exception $e) {
                return [];
            }
        }
        return [];
    }
    
    public static function getPreference($key, $default = null) {
        $prefs = self::getPreferences();
        return isset($prefs[$key]) ? $prefs[$key] : $default;
    }
}
?>
