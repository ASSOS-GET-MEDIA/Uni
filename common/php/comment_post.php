<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['content']) && isset($_POST['post_id'])) {
    $content = htmlspecialchars($_POST['content']);
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id']; // ID de l'utilisateur connecté

    $stmt = $pdo->prepare("INSERT INTO com_tbl (content, date, user_id, post_id) VALUES (?, NOW(), ?, ?)");
    $stmt->execute([$content, $user_id, $post_id]);

    // Mettre à jour le nombre de commentaires
    $pdo->query("UPDATE post SET nb_comments = nb_comments + 1 WHERE id = $post_id");

    header("Location: homepage.php");
    exit;
}
?>
