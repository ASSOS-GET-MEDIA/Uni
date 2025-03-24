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

$user_id = $_SESSION['user_id'];

// Récupérer toutes les notifications de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM notifications WHERE target_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode([
    'status' => 'success',
    'notifications' => $notifications
]);
?>