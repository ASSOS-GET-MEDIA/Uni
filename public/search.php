<?php
include '../common/php/fetch_posts.php'; // On inclut le script qui récupère les posts en fonction des filtres

session_start();

$isUserLoggedIn = isset($_SESSION['user_id']); // Vérifie si l'utilisateur est connecté
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
    <link rel="icon" href="../common/assets/logos/favicon.ico" type="image/x-icon">
    <title>Universee</title>
    <link rel="icon" href="../common/assets/logos/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../common/css/post.css">
    <link rel="stylesheet" href="../common/css/base.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .profile-pic-big {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <aside class="col-md-3 d-none d-md-block">
                <div class="card">
                    <a class="navbar-brand d-flex flex-row" href="homepage.php"><h4>Universee</h4><p class="ms-2 text-secondary">v2 alpha II</p></a>    
                </div>
                <div class="card mt-3 justify-content-center">
                    <div class="card-header">Tendances</div>
                    <ul class="list-group list-group-flush" id="trending-hashtags">
                        <?php if ($isUserLoggedIn): ?>
                            <li class="list-group-item">Chargement...</li>
                        <?php else: ?>
                            <li class="list-group-item">Connectez-vous pour naviguer les tendances</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </aside>

            <main class="col-md-6">

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
                    <div class="card-body text-center">
                        <?php if ($isUserLoggedIn): ?>
                            <?php
                                // Requête pour récupérer les utilisateurs suivis par l'utilisateur connecté
                                $query = $conn->prepare("SELECT * FROM users WHERE id = ?");
                                $query->bind_param("i", $_SESSION['user_id']);
                                $query->execute();
                                $result = $query->get_result();
                                $user_data = $result->fetch_assoc();
                            ?>
                            <img src="<?= htmlspecialchars($user_data['pp']) ?>" alt="Photo de profil" class="profile-pic-big mb-3">
                            <h3><?= htmlspecialchars($user_data['username']) ?></h3>
                            <p>@<?= htmlspecialchars($user_data['at']) ?></p>
                            <p><strong><?= htmlspecialchars($user_data['nb_follows']) ?> followers</strong></p>
                            <div class="d-flex justify-content-center">
                                <div class="btn-group me-1" role="group" aria-label="Basic example">
                                    <a href="edit_profile.php" type="button" class="btn btn-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="#" type="button" class="btn btn-primary"><i class="bi bi-bell"></i></a>
                                    <a href="#" type="button" class="btn btn-primary"><i class="bi bi-gear"></i></a>
                                    <a href="profile.php?u=<?= htmlspecialchars($user_data['id']) ?>" type="button" class="btn btn-primary"><i class="bi bi-person-circle"></i></a>
                                    <?php if ($_SESSION['user_id'] != $user_data['id']) echo 'style="display:none;"'; ?>
                                </div>
                                <a href="../common/php/logout.php" type="button" class="btn btn-primary"><i class="bi bi-box-arrow-right"></i></a>
                            </div>
                        <?php else: ?>
                            <a href="../log_system/login.html" class="btn btn-outline-primary btn-sm">Se connecter</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card mt-3">
                    <?php if ($isUserLoggedIn): ?>
                        <div class="card-header">Personnes que vous suivez</div>
                        <ul class="list-group list-group-flush">
                            <?php if (count($followedUserList) > 0): ?>
                                <?php foreach ($followedUserList as $followedUser): ?>
                                    <a href="profile?u=<?= htmlspecialchars($followedUser['id']) ?>"><li class="list-group-item d-flex align-items-center overflow-hidden">
                                        <img src="<?= htmlspecialchars($followedUser['pp']) ?>" alt="PP" class="profile-pic">
                                        <div class="d-flex flex-column ms-2">
                                            <h4><?= htmlspecialchars($followedUser['username']) ?></h4>
                                        </div>
                                    </li></a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item">Vous ne suivez personne pour le moment.</li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../common/js/vote.js"></script>
    <script src="../common/js/post.js"></script>
    <script src="../common/js/trending_hashtags.js"></script>
    <script src="../common/js/poll.js"></script>
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
                            location.reload();
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
    <script src="../common/js/follow.js"></script>
    <script src="../common/js/see_more.js"></script>
    <script src="../common/js/gif.js"></script>
    <script src="../common/js/pixel.js"></script>
    <script src="../common/js/comment.js"></script>
    <script>
        // JavaScript pour afficher ou masquer le formulaire de commentaire
        document.getElementById('toggle-comment-form-<?= $post['id'] ?>').addEventListener('click', function() {
            var commentForm = document.getElementById('comment-form-<?= $post['id'] ?>');
            if (commentForm.style.display === 'none') {
                commentForm.style.display = 'block';
            } else {
                commentForm.style.display = 'none';
            }
        });
    </script>
    <script>
        // JavaScript pour rediriger l'utilisateur vers la page de commentaires du post
        document.querySelectorAll('.card.post').forEach(card => {
            card.addEventListener('click', function(event) {
                // Vérifier si l'élément cliqué est un bouton ou un autre élément d'interaction
                const target = event.target;
                if (target.closest('button') || target.closest('.like-btn') || target.closest('.add-comment') || target.closest('.actions') || target.closest('.comment-box')) {
                    return;  // Ne pas rediriger si on a cliqué sur un bouton
                }

                // Si ce n'est pas un bouton, procéder à la redirection
                const postId = card.getAttribute('data-post-id');
                window.location.href = `comment.php?id=${postId}`;
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
