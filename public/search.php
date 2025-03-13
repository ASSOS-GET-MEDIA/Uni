<?php
include '../common/php/fetch_posts.php'; // On inclut le script qui récupère les posts en fonction des filtres

session_start();

$searchQuery = isset($_GET['q']) ? $_GET['q'] : ''; // Recherche générale
$hashtag = isset($_GET['tag']) ? $_GET['tag'] : ''; // Recherche spécifique aux hashtags

// Vérifier si l'utilisateur est connecté en vérifiant la présence de 'user_id' dans la session
if (!isset($_SESSION['user_id'])) {
    // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
    header('Location: ../log_system/login.html'); // Remplace 'login.php' par l'URL de ta page de connexion
    exit();
}

// Connexion à la base de données avec mysqli
include '../common/php/db.php';

// Récupérer l'ID de l'utilisateur connecté
$currentUserId = $_SESSION['user_id'];

// Requête pour récupérer les utilisateurs suivis par l'utilisateur connecté
$query = $conn->prepare("SELECT follow, username, pp FROM users WHERE id = ?");
$query->bind_param("i", $currentUserId);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Décoder l'array JSON des utilisateurs suivis
$followedUsers = json_decode($user['follow'], true); // Tableau d'IDs des utilisateurs suivis

// Si l'utilisateur suit quelqu'un, on récupère leurs informations
$followedUserList = [];
if ($followedUsers) {
    $ids = implode(",", $followedUsers); // Convertir le tableau en une chaîne d'IDs séparés par des virgules
    $queryFollowed = $conn->query("SELECT pp, id, username, at FROM users WHERE id IN ($ids)");

    while ($followedUser = $queryFollowed->fetch_assoc()) {
        $followedUserList[] = $followedUser;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réseau Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../common/css/post.css">
    <link rel="stylesheet" href="../common/css/base.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 align-items-center">
        <a class="navbar-brand d-flex flex-row" href="#"><h4>Universee</h4><p class="ms-2 text-secondary">v2</p></a>
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
                    <li><a class="dropdown-item" href="#">Profil</a></li>
                    <li><a class="dropdown-item" href="#">Paramètres</a></li>
                    <li><a class="dropdown-item" href="../common/php/logout.php">Se déconnecter</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <aside class="col-md-3 d-none d-md-block">
                <div class="card">
                    <div class="card-header">Tendances</div>
                    <ul class="list-group list-group-flush" id="trending-hashtags">
                        <li class="list-group-item">Chargement...</li>
                    </ul>
                </div>
            </aside>

            <main class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <form id="post-form">
                            <textarea class="form-control" name="content" id="content" rows="3" placeholder="Exprimez-vous..."></textarea>
                            <!-- Section du sondage cachée au départ -->
                            <div id="poll-section" class="mt-2" style="display: none;">
                                <label for="poll-question">Question du sondage</label>
                                <input type="text" class="form-control" id="poll-question" name="poll_question" placeholder="Entrez votre question de sondage">
                                
                                <!-- 2 champs de réponses par défaut -->
                                <label for="poll-options">Réponses du sondage</label>
                                <div id="poll-options">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="poll_options[]" placeholder="Option 1">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">❌</button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="poll_options[]" placeholder="Option 2">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">❌</button>
                                    </div>
                                </div>

                                <!-- Bouton pour ajouter des options -->
                                <button type="button" id="add-option-btn" class="btn btn-outline-secondary mt-2">Ajouter une réponse</button>
                            </div>

                            <div>
                                <button type="button" class="btn btn-outline-secondary mt-2" id="poll-toggle-btn">
                                    Ajouter un sondage
                                </button>
                                <button class="btn btn-primary mt-2" type="submit">Publier</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des posts (sera mise à jour dynamiquement) -->
                <div id="posts-list">
                    <?php
                        // Affichage des posts trouvés
                        fetchPost($conn, $searchQuery, $hashtag);
                    ?>
                </div>
            </main>

            <aside class="col-md-3 d-none d-md-block">
                <div class="card">
                    <div class="card-header">Personnes que vous suivez</div>
                        <ul class="list-group list-group-flush">
                            <?php if (count($followedUserList) > 0): ?>
                                <?php foreach ($followedUserList as $followedUser): ?>
                                    <li class="list-group-item d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($followedUser['pp']) ?>" alt="PP" class="profile-pic">
                                        <div class="d-flex flex-column ms-2">
                                            <h4><?= htmlspecialchars($followedUser['username']) ?></h4>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item">Vous ne suivez personne pour le moment.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../common/js/vote.js"></script>
    <script src="../common/js/post.js"></script>
    <script src="../common/js/trending_hashtags.js"></script>
    <script src="../common/js/poll_progress.js"></script>
    <script src="../common/js/poll.js"></script>
    <script src="../common/js/like.js"></script>
    <script src="../common/js/follow.js"></script>

</body>
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container text-center">
        <p>&copy; <?= date("Y") ?> Universee - Tous droits réservés</p>
        <ul class="list-inline">
            <li class="list-inline-item"><a href="cgu.php" class="text-light">Conditions Générales d'Utilisation</a></li>
            <li class="list-inline-item"><a href="confidentialite.php" class="text-light">Politique de confidentialité</a></li>
            <li class="list-inline-item"><a href="contact.php" class="text-light">Contact</a></li>
            <li class="list-inline-item"><a href="a_propos.php" class="text-light">À propos</a></li>
        </ul>
    </div>
</footer>
</html>
