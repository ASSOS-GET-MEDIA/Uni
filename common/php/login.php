<?php
// Inclure la connexion à la base de données
include('db.php');

// Démarrer la session pour stocker l'ID de l'utilisateur
session_start();

// Vérifier si les données nécessaires sont envoyées
if (isset($_POST['email'], $_POST['password'])) {
    // Récupérer les données
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Rechercher l'utilisateur par son email
    $query = "SELECT id, username, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Erreur de préparation de la requête.']);
        exit;
    }

    // Lier les paramètres
    $stmt->bind_param("s", $email);

    // Exécuter la requête
    $stmt->execute();
    $result = $stmt->get_result();

    // Vérifier si l'utilisateur existe
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email ou mot de passe incorrect.']);
        exit;
    }

    // Récupérer les données de l'utilisateur
    $user = $result->fetch_assoc();
    
    // Vérifier si le mot de passe est correct
    if (password_verify($password, $user['password'])) {
        // Stocker l'ID de l'utilisateur et son nom d'utilisateur dans la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Répondre avec succès
        echo json_encode(['status' => 'success', 'message' => 'Connexion réussie.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email ou mot de passe incorrect.']);
    }

    // Fermer la requête préparée
    $stmt->close();
} else {
    // Si les données ne sont pas envoyées correctement
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes.']);
}

// Fermer la connexion à la base de données
$conn->close();
?>
