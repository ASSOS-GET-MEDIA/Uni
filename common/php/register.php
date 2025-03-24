<?php
// Inclure la connexion à la base de données
include('db.php');

// Vérifier si toutes les données sont envoyées
if (isset($_POST['username'], $_POST['email'], $_POST['at'], $_POST['password'], $_POST['cgu'])) {
    // Récupérer les valeurs
    $username = $_POST['username'];
    $email = $_POST['email'];
    $at = $_POST['at'];
    $password = $_POST['password'];
    $randint = rand(1, 3);
    $pp_path = '../common/assets/profile_picture/pp_uni_base_' . $randint . '.png';
    $cgu_accepted = isset($_POST['cgu']) && $_POST['cgu'] == "1" ? 1 : 0;

    // Hacher le mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Obtenir la date actuelle (au format 'Y-m-d H:i:s')
    $created_at = date('Y-m-d H:i:s');

    // Préparer la requête d'insertion dans la base de données
    $query = "INSERT INTO users (username, password, email, date, at, pp, cgu) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Erreur de préparation de la requête.']);
        exit;
    }

    // Lier les paramètres
    $stmt->bind_param("ssssssi", $username, $hashed_password, $email, $created_at, $at, $pp_path, $cgu_accepted);

    // Exécuter la requête
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Utilisateur inscrit avec succès.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'inscription.']);
    }

    // Fermer la requête préparée
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes ou CGU non acceptées.']);
}

// Fermer la connexion à la base de données
$conn->close();
?>
