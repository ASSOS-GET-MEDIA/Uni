<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['content'])) {
    $content = htmlspecialchars($_POST['content']);
    $user_id = $_SESSION['user_id']; // Supposons que l'utilisateur est connecté

    $date = date('Y-m-d H:i:s');

    // Préparer la requête d'insertion
    $stmt = $conn->prepare("INSERT INTO post (content, date, user_id) VALUES (?, NOW(), ?)");
    $stmt->bind_param("si", $content, $user_id); // "si" signifie que le premier paramètre est une chaîne (string) et le deuxième est un entier (integer)

    // Exécuter la requête
    if ($stmt->execute()) {
        // Retourner la réponse JSON sans le nom d'utilisateur

        echo json_encode([
            'status' => 'success'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de l\'insertion du post'
        ]);
    }
}
?>
