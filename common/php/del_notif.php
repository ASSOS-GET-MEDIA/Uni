<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Utilisateur non connecté.'
    ]);
    exit();
}

// Récupérer le notif_id envoyé en POST
$data = json_decode(file_get_contents('php://input'), true);
$notif_id = $data['notif_id'] ?? null;

if (!$notif_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Notification ID manquant.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Supprimer la notification
$stmt = $conn->prepare("DELETE FROM notifications WHERE notif_id = ? AND target_id = ?");
$stmt->bind_param("ii", $notif_id, $user_id);
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur lors de la suppression de la notification.'
    ]);
}
?>