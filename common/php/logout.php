<?php
// Déconnexion de l'utilisateur
session_start();
session_destroy();
header('Location: ../../log_system/login.html');
exit;
?>