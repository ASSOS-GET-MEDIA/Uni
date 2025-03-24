<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id']) && isset($_POST['option'])) {
    $postId = intval($_POST['post_id']);
    $selectedOption = htmlspecialchars($_POST['option']);
    $userId = $_SESSION['user_id']; // L'utilisateur doit être connecté

    // Récupérer le post et ses votes actuels
    $stmt = $conn->prepare("SELECT poll_votes FROM post WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if ($post) {
        $votes = !empty($post['poll_votes']) ? json_decode($post['poll_votes'], true) : [];

        // Vérifier si l'utilisateur a déjà voté
        if (isset($votes[$userId])) {
            echo json_encode(['status' => 'error', 'message' => 'Vous avez déjà voté.']);
            exit;
        }

        // Ajouter le vote de l'utilisateur
        $votes[$userId] = $selectedOption;

        // Mettre à jour la base de données avec les nouveaux votes
        $votesJson = json_encode($votes);
        $updateStmt = $conn->prepare("UPDATE post SET poll_votes = ? WHERE id = ?");
        $updateStmt->bind_param("si", $votesJson, $postId);

        if ($updateStmt->execute()) {
            echo json_encode(['status' => 'success', 'votes' => $votes]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erreur lors du vote.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Post non trouvé.']);
    }
}
?>
