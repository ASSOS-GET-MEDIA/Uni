<?php
// Connexion à la base de données avec mysqli
include 'db.php';

// Vérifier que la requête est envoyée avec un `post_id`
if (isset($_POST['post_id'])) {
    $postId = (int) $_POST['post_id'];

    // Vérifier si l'utilisateur a déjà liké ce post
    session_start();
    $userId = $_SESSION['user_id']; // Supposons que l'utilisateur est connecté

    // Obtenir les données actuelles du post
    $query = $conn->prepare("SELECT nb_likes, user_like FROM post WHERE id = ?");
    $query->bind_param("i", $postId);
    $query->execute();
    $result = $query->get_result();
    $post = $result->fetch_assoc();

    if (!$post) {
        echo json_encode(['status' => 'error']);
        exit;
    }

    // Vérifier si l'utilisateur est déjà dans l'array `user_like`
    $userLikes = json_decode($post['user_like'], true); // Décoder le JSON en tableau
    if ($userLikes === null) {
        $userLikes = [];
    }

    

    if (in_array($userId, $userLikes)) {
        // L'utilisateur a déjà liké ce post, on retire le like
        $newLikeCount = $post['nb_likes'] - 1;
        // Enlever l'ID de l'utilisateur
        $userLikes = array_diff($userLikes, [$userId]);
    } else {
        // Ajouter un like
        $newLikeCount = $post['nb_likes'] + 1;
        // Ajouter l'ID de l'utilisateur
        $userLikes[] = $userId;
    }

    // Mettre à jour la table `post` avec le nouveau nombre de likes et la nouvelle liste d'utilisateurs
    $newUserLikesJson = json_encode($userLikes);
    $updateQuery = $conn->prepare("UPDATE post SET nb_likes = ?, user_like = ? WHERE id = ?");
    $updateQuery->bind_param("isi", $newLikeCount, $newUserLikesJson, $postId);
    $updateQuery->execute();

    // Retourner le nouveau nombre de likes sous forme de JSON
    echo json_encode(['status' => 'success', 'new_like_count' => $newLikeCount]);
} else {
    echo json_encode(['status' => 'error']);
}
?>
