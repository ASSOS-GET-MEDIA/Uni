<?php
include '../common/php/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Requête pour récupérer les informations de l'utilisateur
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Utilisateur introuvable.";
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];
    $new_pp = $_FILES['pp']['name'];

    // Vérifier si une nouvelle photo de profil a été téléchargée
    if (!empty($new_pp)) {
        $upload_dir = '../common/assets/pp_uploaded/';
        $file_tmp = $_FILES['pp']['tmp_name'];
        $file_name = basename($new_pp);
        $target_file = $upload_dir . $file_name;

        // Déplacer le fichier vers le dossier "uploads"
        if (move_uploaded_file($file_tmp, $target_file)) {
            // Si le téléchargement est réussi, on met à jour la photo de profil dans la base de données
            $update_pp = $target_file;
        } else {
            echo "Erreur lors du téléchargement de la photo de profil.";
            exit;
        }
    } else {
        // Si aucune photo n'est téléchargée, conserver l'ancienne
        $update_pp = $user['pp'];
    }

    // Mise à jour des informations de l'utilisateur
    $update_query = "UPDATE users SET username = ?, email = ?, info = ?, pp = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $username, $email, $bio, $update_pp, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Profil mis à jour avec succès');</script>";
    } else {
        echo "<script>alert('Erreur lors de la mise à jour du profil');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le profil</title>
    <link rel="icon" href="../common/assets/logos/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../common/css/post.css">
    <link rel="stylesheet" href="../common/css/base.css">
    <style>
        .big-profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 align-items-center">
        <a class="navbar-brand d-flex flex-row" href="homepage.php">Universee<p class="ms-2 text-secondary">v2</p></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse " id="navbarNav">
            <!--
            <form class="d-flex mx-auto">
                <input class="form-control me-2" type="search" placeholder="Rechercher...">
                <button class="btn btn-outline-light" type="submit">Rechercher</button>
            </form>
            -->

            <!-- Bouton de profil avec menu déroulant -->
            <div class="dropdown ms-auto">
                <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($user['pp']) ?>" alt="PP" class="profile-pic">
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="profile.php?u=<?= $currentUserId ?>">Profil</a></li>
                    <li><a class="dropdown-item" href="#">Paramètres</a></li>
                    <li><a class="dropdown-item" href="../common/php/logout.php">Se déconnecter</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-3">
        <div class="row">
            <div class="col-md-3">
                <!-- Sidebar avec photo de profil -->
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($user['pp']) ?>" alt="Photo de profil" class="big-profile-pic mb-3">
                        <h4><?= htmlspecialchars($user['username']) ?></h4>
                        <p>@<?= htmlspecialchars($user['at']) ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <!-- Formulaire de modification du profil -->
                <div class="card">
                    <div class="card-header">
                        <h5>Modifier le profil de <?= htmlspecialchars($user['username']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($user['info']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="pp" class="form-label">Changer la photo de profil</label>
                                <input type="file" class="form-control" id="pp" name="pp" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
