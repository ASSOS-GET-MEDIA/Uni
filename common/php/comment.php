<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Vous devez être connecté pour commenter."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'];
    $content = trim($_POST['content']);

    if (empty($content) || strlen($content) > 500) {
        echo json_encode(["status" => "error", "message" => "Le commentaire doit contenir entre 1 et 500 caractères."]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $postId, $userId, $content);
    if ($stmt->execute()) {
        $updateStmt = $conn->prepare("UPDATE post SET nb_coms = nb_coms + 1 WHERE id = ?");
        $updateStmt->bind_param("i", $postId);
        $updateStmt->execute();
        
        echo json_encode(["status" => "success", "message" => "Commentaire ajouté avec succès"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erreur lors de l'ajout du commentaire."]);
    }
}
?>