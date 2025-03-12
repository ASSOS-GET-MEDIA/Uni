<?php
header('Content-Type: application/json'); // Forcer la sortie JSON

include 'db.php';

// Activer le debug pour voir les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier la connexion
if (!$conn) {
    echo json_encode(['error' => 'Connexion à la BDD échouée']);
    exit;
}

// Requête SQL pour récupérer les 10 hashtags les plus utilisés récemment, excluant les valeurs NULL
$query = "SELECT hashtags, COUNT(*) as count 
          FROM post
          WHERE date >= NOW() - INTERVAL 7 DAY 
          AND hashtags IS NOT NULL
          AND hashtags != ''
          GROUP BY hashtags 
          ORDER BY count DESC 
          LIMIT 10";

$result = $conn->query($query);

if (!$result) {
    echo json_encode(['error' => 'Erreur SQL : ' . $conn->error]);
    exit;
}

$hashtags = [];
while ($row = $result->fetch_assoc()) {
    $hashtags[] = $row;
}

// Retourner le JSON proprement
echo json_encode($hashtags);
?>
