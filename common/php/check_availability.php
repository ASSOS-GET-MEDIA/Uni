<?php
// Inclure votre connexion à la base de données
include('db.php');

$field = $_POST['field']; // soit 'email' ou 'at'
$value = $_POST['value'];

// Préparer la requête en fonction du champ à vérifier
$query = "";
if ($field === 'email') {
    $query = "SELECT COUNT(*) AS count FROM users WHERE email = ?";
} elseif ($field === 'at') {
    $query = "SELECT COUNT(*) AS count FROM users WHERE at = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $value);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(['status' => 'taken']);
} else {
    echo json_encode(['status' => 'available']);
}
?>
