<?php
include 'db.php';
session_start();

if (isset($_POST['author_id'], $_POST['user_id'], $_POST['action'])) {
    $authorId = (int) $_POST['author_id'];
    $userId = (int) $_POST['user_id'];
    $action = $_POST['action'];

    // Vérifier si l'utilisateur est connecté
    if ($userId !== $_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
        exit;
    }

    // Récupérer les informations actuelles de l'utilisateur et de l'auteur
    $queryUser = $conn->prepare("SELECT follow, followed_by FROM users WHERE id = ?");
    $queryUser->bind_param("i", $userId);
    $queryUser->execute();
    $userResult = $queryUser->get_result();
    $user = $userResult->fetch_assoc();

    $followedUsers = json_decode($user['follow'], true); // Les utilisateurs suivis par l'utilisateur actuel
    $queryAuthor = $conn->prepare("SELECT follow, followed_by, nb_follows FROM users WHERE id = ?");
    $queryAuthor->bind_param("i", $authorId);
    $queryAuthor->execute();
    $authorResult = $queryAuthor->get_result();
    $author = $authorResult->fetch_assoc();

    $followers = json_decode($author['followed_by'], true); // Les utilisateurs qui suivent l'auteur
    $followersCount = $author['nb_follows']; // Le nombre actuel de followers

    if ($action === 'follow') {
        // Ajouter l'auteur à la liste des suivis de l'utilisateur
        $followedUsers[] = $authorId;

        // Ajouter l'utilisateur à la liste des suiveurs de l'auteur
        $followers[] = $userId;

        // Incrémenter le compteur de followers
        $followersCount++;
    } else {
        // Retirer l'auteur de la liste des suivis de l'utilisateur
        $followedUsers = array_diff($followedUsers, [$authorId]);

        // Retirer l'utilisateur de la liste des suiveurs de l'auteur
        $followers = array_diff($followers, [$userId]);

        // Décrémenter le compteur de followers
        $followersCount--;
    }

    // Mettre à jour la table `users` avec les nouvelles valeurs
    $newFollowJson = json_encode($followedUsers);
    $newFollowersJson = json_encode($followers);

    $updateUser = $conn->prepare("UPDATE users SET follow = ? WHERE id = ?");
    $updateUser->bind_param("si", $newFollowJson, $userId);
    $updateAuthor = $conn->prepare("UPDATE users SET nb_follows = ?, followed_by = ? WHERE id = ?");
    $updateAuthor->bind_param("isi", $followersCount, $newFollowersJson, $authorId);

    // Exécuter les deux mises à jour
    $updateUser->execute();
    $updateAuthor->execute();

    // Répondre en JSON
    if ($updateUser->affected_rows > 0 && $updateAuthor->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Impossible de mettre à jour les informations.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes.']);
}
?>
