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
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 align-items-center">
        <a class="navbar-brand d-flex flex-row" href="#">Universee<p class="ms-2 text-secondary">v2</p></a>
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
                    <img src="<?= htmlspecialchars($user['pp']) ?>" alt="PP" class="rounded-circle" width="40">
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
                    <div class="card-header">Catégories</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Tendance</li>
                        <li class="list-group-item">Technologie</li>
                        <li class="list-group-item">Divertissement</li>
                    </ul>
                </div>
            </aside>

            <main class="col-md-6">
                <div class="card mb-3">
                    <form id="post-form">
                        <textarea class="form-control" name="content" id="content" rows="3" placeholder="Exprimez-vous..."></textarea>
                        <button class="btn btn-primary mt-2" type="submit">Publier</button>
                    </form>
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
                                    <img src="<?= htmlspecialchars($followedUser['pp']) ?>" alt="PP" class="rounded-circle" width="40">
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
            </aside>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('post-form').addEventListener('submit', function(e) {
            e.preventDefault(); // Empêche le rechargement de la page
            
            const content = document.getElementById('content').value;

            if (!content.trim()) {
                alert('Le contenu ne peut pas être vide.');
                return;
            }

            const formData = new FormData();
            formData.append('content', content);

            // Envoi des données via fetch (AJAX)
            fetch('../common/php/add_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // On s'attend à une réponse JSON
            .then(data => {
                if (data.status === 'success') {
                    // Créer un nouvel élément post dynamique sans recharger la page
                    const newPost = document.createElement('div');
                    newPost.innerHTML = `
                        <div class="alert alert-success" role="alert">
                            Posté avec succès !
                        </div>
                    `;
                    document.getElementById('posts-list').prepend(newPost);
                    document.getElementById('content').value = ''; // Réinitialiser le champ du texte
                } else {
                    alert('Une erreur est survenue. Veuillez réessayer.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue.');
            });
        });
    </script>

    <script>
        // Gestionnaire d'événement pour le bouton "Like"
        document.addEventListener('DOMContentLoaded', function() {
            const likeButtons = document.querySelectorAll('.like-btn');

            likeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const postId = e.target.getAttribute('data-post-id'); // Récupérer l'id du post
                    const likeCountElement = e.target.querySelector('.like-count');

                    // Envoi de la requête AJAX pour liker le post
                    fetch('../common/php/like_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `post_id=${postId}` // Paramètre post_id
                    })
                    .then(response => response.json()) // On s'attend à une réponse JSON
                    .then(data => {
                        if (data.status === 'success') {
                            // Mise à jour du nombre de likes
                            likeCountElement.textContent = data.new_like_count;
                        } else {
                            alert('Une erreur est survenue lors du like.');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        console.log(JSON.stringify(response));
                        alert('Une erreur est survenue.');
                    });
                });
            });
        });
    </script>

    <script>
        // Gérer le clic sur le bouton Follow/Unfollow
        document.querySelectorAll('.follow-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const authorId = this.getAttribute('data-author-id');
                const userId = this.getAttribute('data-user-id');
                const isFollowing = this.getAttribute('data-following') === 'true';

                // Créer une nouvelle instance de FormData
                const formData = new FormData();
                formData.append('author_id', authorId);
                formData.append('user_id', userId);
                formData.append('action', isFollowing ? 'unfollow' : 'follow');

                // Envoi des données via fetch (AJAX)
                fetch('../common/php/follow_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Mettre à jour le bouton Follow/Unfollow
                        button.textContent = isFollowing ? 'Follow' : 'Unfollow';
                        button.setAttribute('data-following', isFollowing ? 'false' : 'true');
                    } else {
                        alert('Une erreur est survenue. Veuillez réessayer.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue.');
                });
            });
        });
    </script>
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
