<?php
$host = ''; // ou l'adresse de ton serveur MySQL
$dbname = ''; // Remplace par le nom de ta base
$user = ''; // Ton utilisateur MySQL
$password = ''; // Ton mot de passe MySQL

// Créer la connexion
$conn = new mysqli($host, $user, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("La connexion à la base de données a échoué : " . $conn->connect_error);
}
?>
