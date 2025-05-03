<?php
/**
 * Script d'installation pour Nova Bookstore
 * Ce script vérifie les prérequis et installe la base de données
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Nova Bookstore</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        h2 { color: #3498db; margin-top: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .step { margin-bottom: 30px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Installation Nova Bookstore</h1>
        
        <div class="step">
            <h2>1. Vérification des prérequis</h2>
            
            <h3>Version PHP</h3>
            <?php
            $phpVersion = phpversion();
            $phpRequired = '8.0.0';
            $phpOk = version_compare($phpVersion, $phpRequired, '>=');
            echo '<p>' . ($phpOk 
                  ? '<span class="success">✓ PHP ' . $phpVersion . ' est installé.</span>' 
                  : '<span class="error">✗ PHP ' . $phpVersion . ' est installé. PHP ' . $phpRequired . ' ou supérieur est requis.</span>') . '</p>';
            ?>
            
            <h3>Extension Oracle OCI8</h3>
            <?php
            $ociLoaded = extension_loaded('oci8');
            echo '<p>' . ($ociLoaded 
                  ? '<span class="success">✓ Extension Oracle OCI8 est installée.</span>' 
                  : '<span class="error">✗ Extension Oracle OCI8 n\'est pas installée.</span>') . '</p>';
            ?>
            
            <h3>Permissions d'écriture</h3>
            <?php
            $uploadDir = __DIR__ . '/frontend/assets/images/books/';
            $logsDir = __DIR__ . '/logs/';
            $dirsToCheck = [$uploadDir, $logsDir];
            $allWritable = true;
            
            foreach ($dirsToCheck as $dir) {
                if (!file_exists($dir)) {
                    if (!mkdir($dir, 0755, true)) {
                        echo '<p><span class="error">✗ Impossible de créer le répertoire ' . $dir . '</span></p>';
                        $allWritable = false;
                    } else {
                        echo '<p><span class="success">✓ Répertoire ' . $dir . ' créé.</span></p>';
                    }
                } 
                else if (!is_writable($dir)) {
                    echo '<p><span class="error">✗ Le répertoire ' . $dir . ' n\'est pas accessible en écriture.</span></p>';
                    $allWritable = false;
                } else {
                    echo '<p><span class="success">✓ Le répertoire ' . $dir . ' est accessible en écriture.</span></p>';
                }
            }
            ?>
        </div>
        
        <div class="step">
            <h2>2. Configuration de la base de données</h2>
            
            <?php
            // Vérifier si le fichier de config existe
            $configSample = __DIR__ . '/backend/config/config.sample.php';
            $configLocal = __DIR__ . '/backend/config/config.php';
            
            if (!file_exists($configLocal) && file_exists($configSample)) {
                echo '<p><span class="warning">⚠️ Le fichier config.php n\'existe pas.</span></p>';
                echo '<p>Veuillez copier le fichier config.sample.php vers config.php et le modifier avec vos informations:</p>';
                echo '<pre>cp backend/config/config.sample.php backend/config/config.php</pre>';
            } else if (file_exists($configLocal)) {
                echo '<p><span class="success">✓ Le fichier config.php existe.</span></p>';
            } else {
                echo '<p><span class="error">✗ Le fichier config.sample.php est manquant.</span></p>';
            }
            ?>
            
            <h3>Scripts SQL à exécuter</h3>
            <p>Pour initialiser la base de données, exécutez les scripts SQL dans l'ordre suivant:</p>
            <ol>
                <li><code>database/schema.sql</code> - Création des tables et relations</li>
                <li><code>database/procedures.sql</code> - Création des procédures stockées</li>
                <li><code>database/triggers.sql</code> - Création des triggers</li>
                <li><code>database/test_data.sql</code> - (Optionnel) Ajout de données de test</li>
            </ol>
            
            <p>Exemple avec SQLPlus:</p>
            <pre>sqlplus username/password@service_name @database/schema.sql
sqlplus username/password@service_name @database/procedures.sql
sqlplus username/password@service_name @database/triggers.sql
sqlplus username/password@service_name @database/test_data.sql</pre>
        </div>
        
        <div class="step">
            <h2>3. Accès à l'application</h2>
            
            <p>Une fois l'installation terminée:</p>
            <ul>
                <li>Accédez à l'application: <a href="index.php">Page d'accueil</a></li>
                <li>Accédez à l'administration: <a href="pages/admin/dashboard.php">Tableau de bord admin</a></li>
                <li>Identifiants admin par défaut: <code>admin / admin123</code></li>
            </ul>
        </div>
        
        <p><small>Nova Bookstore &copy; <?php echo date('Y'); ?></small></p>
    </div>
</body>
</html>
