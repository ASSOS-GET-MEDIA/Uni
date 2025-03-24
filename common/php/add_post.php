<?php
include 'db.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
    exit();
}

// Vérifier que la requête est bien en POST et que le contenu n'est pas vide
if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST['content'])) {
    echo json_encode(['status' => 'error', 'message' => 'Requête invalide ou contenu vide.']);
    exit();
}

$content = trim($_POST['content']);
$user_id = $_SESSION['user_id'];
$date = date('Y-m-d H:i:s');

// Sécuriser le contenu
$content = htmlspecialchars($content);

// Extraction des hashtags et mentions
preg_match_all('/#([a-zA-Z0-9_éèàçùïöë-]+)/', $content, $matches);
$hashtags = !empty($matches[1]) ? implode(',', $matches[1]) : null;

preg_match_all('/@([a-zA-Z0-9_éèàçùïöë-]+)/', $content, $matches_mentions);
$mentions = [];

foreach ($matches_mentions[1] as $mention) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE at = ?");
    $stmt->bind_param("s", $mention);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $mentions[] = $mention;
    }
    $stmt->close();
}

$mentions = !empty($mentions) ? implode(',', $mentions) : null;

// Gestion des images uploadées
$image_url = null;
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] == 0) {
    $upload_dir = '../assets/uploads/images/';
    $upload_dir_bdd = '../common/assets/uploads/images/';
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

    if (in_array($_FILES['image']['type'], $allowed_types)) {
        $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $dest = $upload_dir . $file_name;
        $destbdd = $upload_dir_bdd . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $image_url = $destbdd;
        }
    }
}

// Gestion du GIF
$gif_url = isset($_POST['gif_url']) ? trim($_POST['gif_url']) : null;
if (!empty($gif_url) && !filter_var($gif_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['status' => 'error', 'message' => 'L\'URL du GIF est invalide.']);
    exit();
}

// Gestion du sondage
$poll_question = isset($_POST['poll_question']) ? htmlspecialchars($_POST['poll_question']) : null;
$poll_options = isset($_POST['poll_options']) ? json_decode($_POST['poll_options'], true) : null;
if (!empty($poll_options) && is_array($poll_options)) {
    $poll_options = json_encode($poll_options, JSON_UNESCAPED_UNICODE);
} else {
    $poll_options = null;
}

// Insertion du post
$stmt = $conn->prepare("INSERT INTO post (content, date, user_id, mentions, hashtags, image_url, gif_url, poll_question, poll_options) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssissssss", $content, $date, $user_id, $mentions, $hashtags, $image_url, $gif_url, $poll_question, $poll_options);

if ($stmt->execute()) {
    $post_id = $stmt->insert_id;
    $notifications_inserted = true;

    // Traitement des mentions
    foreach ($matches_mentions[1] as $mention) {
        $notif_content = 'Vous avez été mentionné dans un post.';

        $stmt = $conn->prepare("SELECT id FROM users WHERE at = ?");
        $stmt->bind_param("s", $mention);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $target_id = $row['id'];

            error_log("Mention trouvée : @$mention -> ID: $target_id");

            $stmt = $conn->prepare("INSERT INTO notifications (target_id, content, date, post_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $target_id, $notif_content, $date, $post_id);

            if (!$stmt->execute()) {
                error_log("Échec d'insertion de la notification pour @$mention - Erreur SQL: " . $stmt->error);
                $notifications_inserted = false;
            }
            $stmt->close();
        } else {
            error_log("Utilisateur mentionné non trouvé : @$mention");
        }
    }

    echo json_encode([
        'status' => $notifications_inserted ? 'success' : 'error',
        'message' => $notifications_inserted ? 'Post ajouté avec succès !' : 'Erreur lors de l\'insertion des notifications.',
        'post_id' => $post_id
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur lors de l\'insertion du post: ' . $stmt->error
    ]);
}
?>