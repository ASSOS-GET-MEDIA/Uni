<?php
$host = 'univeradb.mysql.db'; // ou l'adresse de ton serveur MySQL
$dbname = 'univeradb'; // Remplace par le nom de ta base
$user = 'univeradb'; // Ton utilisateur MySQL
$password = 'yDgK4fRnsyJnqJPMLaHM7aqkT'; // Ton mot de passe MySQL

// Créer la connexion
$conn = new mysqli($host, $user, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("La connexion à la base de données a échoué : " . $conn->connect_error);
}
?>
