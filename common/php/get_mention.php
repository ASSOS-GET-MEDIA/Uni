<?php
require 'db.php'; // Connexion à la BDD

$query = $_GET['query'] ?? '';

if (!$query) {
    echo json_encode([]);
    exit;
}

// Prépare la requête avec MySQLi
$sql = "SELECT username, pp, at, nb_follows FROM users WHERE at LIKE ? ORDER BY nb_follows DESC LIMIT 3";
$stmt = $conn->prepare($sql); // Assure-toi que `$conn` est bien ta connexion MySQLi

$likeQuery = "%$query%";
$stmt->bind_param("s", $likeQuery);
$stmt->execute();
$result = $stmt->get_result();

// Récupère les résultats sous forme de tableau associatif
$users = $result->fetch_all(MYSQLI_ASSOC);

// Envoie le JSON au JavaScript
header('Content-Type: application/json');
echo json_encode($users);
?>
