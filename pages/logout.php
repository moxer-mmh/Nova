<?php
require_once __DIR__ . '/../utils/session.php';

// Détruire la session
Session::destroy();

// Redirection vers la page d'accueil
header('Location: /Nova/index.php');
exit;
?>
