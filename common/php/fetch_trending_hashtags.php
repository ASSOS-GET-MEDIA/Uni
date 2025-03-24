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
$query = "
    SELECT hashtags, COUNT(*) as count 
    FROM (
        SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(hashtags, ',', n.n), ',', -1)) AS hashtags
        FROM post 
        CROSS JOIN (SELECT a.N + b.N * 10 + 1 n 
                    FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a, 
                         (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                    ORDER BY n) n 
        WHERE n.n <= (LENGTH(hashtags) - LENGTH(REPLACE(hashtags, ',', '')) + 1) AND hashtags IS NOT NULL
    ) AS separated_hashtags
    GROUP BY hashtags
    ORDER BY count DESC 
    LIMIT 6
";


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
