<?php
include 'db.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Utilisateur non connecté.'
    ]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['content'])) {
    $content = htmlspecialchars($_POST['content']);
    $user_id = $_SESSION['user_id']; // Utilisateur connecté

    // Extraire les hashtags du contenu
    preg_match_all('/#(\w+)/', $content, $matches);
    $hashtags = !empty($matches[1]) ? implode(',', $matches[1]) : null; // Hashtags sous forme de chaîne, ou null

    // Récupérer les informations du sondage, si présentes
    $poll_question = isset($_POST['poll_question']) ? htmlspecialchars($_POST['poll_question']) : null;
    $poll_options = isset($_POST['poll_options']) ? json_encode($_POST['poll_options']) : null;

    $date = date('Y-m-d H:i:s');

    // Vérifier les options de sondage (au cas où elles sont vides)
    if (!empty($_POST['poll_options'])) {
        $poll_options = $_POST['poll_options'];
        
        // Vérifie si c'est déjà une chaîne JSON
        if (is_string($poll_options) && is_array(json_decode($poll_options, true)) && (json_last_error() == JSON_ERROR_NONE)) {
            // Si c'est une chaîne JSON valide, on l’utilise telle quelle
        } else {
            // Sinon, on encode en JSON
            $poll_options = json_encode($poll_options);
        }
    } else {
        $poll_options = null; // Pas de sondage
    }

    // Préparer la requête d'insertion
    $stmt = $conn->prepare("INSERT INTO post (content, date, user_id, hashtags, poll_question, poll_options) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $content, $date, $user_id, $hashtags, $poll_question, $poll_options);

    // Exécuter la requête
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de l\'insertion du post: ' . $stmt->error
        ]);
    }
}
?>
