<?php
// Assurer que la session est bien démarrée
session_start();

// Connexion à la base de données avec mysqli
include '../common/php/db.php';

// Vérifier si l'utilisateur est connecté en vérifiant la présence de 'user_id' dans la session
$isUserLoggedIn = isset($_SESSION['user_id']);

if ($isUserLoggedIn) {
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
}
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-adsense-account" content="ca-pub-5454113071023745">
    <meta name="description" content="Universee est un réseau social universitaire qui vous permet de partager des posts, des photos, des vidéos et des GIFs avec vos camarades.">
    <meta name="keywords" content="universee, université, réseau social, étudiants, études, partage, posts, photos, vidéos, GIFs">
    <meta name="author" content="GET MEDIA">
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="1 day">
    <meta name="language" content="French">
    <link rel="icon" href="../common/assets/logos/favicon.ico" type="image/x-icon">
    <title>Universee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../common/css/post.css">
    <link rel="stylesheet" href="../common/css/base.css">
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
                <div class="card d-flex justify-content-center">
                    <a class="navbar-brand d-flex flex-row" href="homepage.php"><h4>Universee</h4></a>    
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
                <!-- Modal followers -->
                <div class="modal fade" id="fllwrsModal" tabindex="-1" aria-labelledby="gifModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="fllwrsModalLabel">Liste de vos followers</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                // Requête pour récupérer les followers de l'utilisateur connecté
                                $queryFollowers = $conn->prepare("SELECT followed_by FROM users WHERE id = ?");
                                $queryFollowers->bind_param("i", $currentUserId);
                                $queryFollowers->execute();
                                $resultFollowers = $queryFollowers->get_result();

                                $followersData = $resultFollowers->fetch_assoc();
                                $followersIds = json_decode($followersData['followed_by'], true);

                                if (!empty($followersIds)) {
                                    $ids = implode(",", $followersIds);
                                    $queryFollowersDetails = $conn->query("SELECT pp, username, id FROM users WHERE id IN ($ids)");

                                    while ($follower = $queryFollowersDetails->fetch_assoc()) {
                                        echo '<div class="d-flex align-items-center mb-2">';
                                        echo '<img src="' . htmlspecialchars($follower['pp']) . '" alt="PP" class="profile-pic me-2">';
                                        echo '<div>';
                                        echo '<h5 class="mb-0">' . htmlspecialchars($follower['username']) . '</h5>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>Vous n\'avez aucun follower pour le moment.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal GIF -->
                <div class="modal fade" id="gifModal" tabindex="-1" aria-labelledby="gifModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="gifModalLabel">Choisir un GIF</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Barre de recherche -->
                                <input type="text" id="gifSearch" class="form-control mb-3" placeholder="Rechercher un GIF...">
                                
                                <!-- Grille des GIFs -->
                                <div id="gifGrid" class="d-flex flex-wrap justify-content-center gap-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($isUserLoggedIn): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <form id="post-form">
                                <textarea class="form-control" name="content" id="content" rows="3" placeholder="Exprimez-vous..."></textarea>
                                <div id="mention-suggestions" class="dropdown-menu" style="display: none; position: absolute;"></div>
                                <!-- preview de l'image & gif -->
                                <div id="preview" class="mt-2"></div>
                                <!-- Input caché pour l'image -->
                                <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                                <!-- Input caché pour stocker l'URL du GIF -->
                                <input type="hidden" id="selectedGifUrl" name="gif_url">
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
                                    <!-- Bouton pour ouvrir le image-->
                                    <button type="button" class="btn btn-outline-secondary mt-2" id="openImage" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Ajouter une image" aria-label="Ajouter une image">
                                        <i class="bi bi-image"></i>
                                    </button>
                                    <!-- Bouton pour ouvrir le modal gif-->
                                    <button type="button" class="btn btn-outline-secondary mt-2" id="openGifModal" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Ajouter un GIF Tenor" aria-label="Ajouter un GIF Tenor">
                                        <i class="bi bi-filetype-gif"></i>
                                    </button>
                                    <!-- Bouton pour ajouter un sondage-->
                                    <button type="button" class="btn btn-outline-secondary mt-2" id="poll-toggle-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Ajouter un sondage" aria-label="Ajouter un sondage">
                                        <i class="bi bi-card-checklist"></i>
                                    </button>
                                    <button class="btn btn-primary mt-2" type="submit">Publier</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Liste des posts (sera mise à jour dynamiquement) -->
                <div id="posts-list">
                    <?php include '../common/php/post.php'; ?>
                </div>
                <div class="text-center mt-3">
                    <button id="load-more-btn" class="btn btn-primary">Charger plus</button>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        let currentPage = 1;

                        document.getElementById('load-more-btn').addEventListener('click', function() {
                            currentPage++;
                            fetch(`../common/php/post.php?page=${currentPage}`)
                                .then(response => response.text())
                                .then(data => {
                                    const postsList = document.getElementById('posts-list');
                                    postsList.insertAdjacentHTML('beforeend', data);
                                })
                                .catch(error => {
                                    console.error('Erreur:', error);
                                    alert('Une erreur est survenue lors du chargement des posts.');
                                });
                        });
                    });
                </script>
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
                            <p id="fllwrs"><strong><?= htmlspecialchars($user_data['nb_follows']) ?> followers</strong></p>
                            <div class="d-flex justify-content-center">
                                <div class="btn-group me-1" role="group" aria-label="Basic example">
                                    <a href="edit_profile.php" type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Modifier votre profil" aria-label="Modifier votre profil"><i class="bi bi-pencil"></i></a>
                                    <a href="#" type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Paramètres" aria-label="Paramètre de votre compte"><i class="bi bi-gear"></i></a>
                                    <a href="profile.php?u=<?= htmlspecialchars($user_data['id']) ?>" type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Votre profil" aria-label="Votre profil"><i class="bi bi-person-circle"></i></a>
                                    <?php if ($_SESSION['user_id'] != $user_data['id']) echo 'style="display:none;"'; ?>
                                </div>
                                <a href="../common/php/logout.php" type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Se déconnecter" aria-label="Se déconnecter de votre compte"><i class="bi bi-box-arrow-right"></i></a>
                            </div>
                        <?php else: ?>
                            <a href="../log_system/login.html" class="btn btn-outline-primary btn-sm">Se connecter</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isUserLoggedIn): ?>
                    <div class="card mt-3">
                        <a class="btn" data-bs-toggle="collapse" href="#notif" aria-expanded="false" aria-controls="notifications">
                            Vos notifications <span id="notif-count" class="badge rounded-pill bg-primary ms-2">0</span>
                            <i class="bi bi-chevron-right" id="notif-icon"></i>
                        </a>
                        <ul class="collapse list-group list-group-flush" id="notif">
                            <!-- Les notifications seront insérées ici -->
                        </ul>
                    </div>
                    <div class="card mt-3">
                        <a class="btn" data-bs-toggle="collapse" href="#followed-users" aria-expanded="false" aria-controls="followed-users">
                            Vos follows <i class="bi bi-chevron-right" id="followed-users-icon"></i>
                        </a>
                        <ul class="collapse list-group list-group-flush" id="followed-users">
                            <?php if (count($followedUserList) > 0): ?>
                                <?php foreach ($followedUserList as $followedUser): ?>
                                    <li class="list-group-item overflow-hidden"><a href="profile?u=<?= htmlspecialchars($followedUser['id']) ?>" class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($followedUser['pp']) ?>" alt="PP" class="profile-pic">
                                        <div class="d-flex flex-column ms-2">
                                            <h4><?= htmlspecialchars($followedUser['username']) ?></h4>
                                        </div>
                                    </a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item">Vous ne suivez personne pour le moment.</li>
                            <?php endif; ?>
                        </ul>    
                    </div>
                    <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                        <h6>Nouveautés</h6>
                        <p>Découvrez dès maintenant votre carte de profil -> <a href="user_card.php?u=<?= $_SESSION['user_id'] ?>" class="link-info">Ma carte de profil</a></p>
                        <p>Universee ouvre un programme de test pour le site, afin de développer et d'affiner les nouvelles versions d'Universee vous pouvez vous rendre sur le site de test -> <a href="https://test.universee.fr" class="link-info">test.universee.fr</a></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../common/js/at_fast_mention.js"></script>
    <script src="../common/js/vote.js"></script>
    <script src="../common/js/post.js"></script>
    <script src="../common/js/trending_hashtags.js"></script>
    <script src="../common/js/poll.js"></script>
    <script>

        // JavaScript pour afficher ou masquer les notifications
        document.addEventListener("DOMContentLoaded", function () {
            let collapseElement = document.getElementById("notif");
            let icon = document.getElementById("notif-icon");

            collapseElement.addEventListener("show.bs.collapse", function () {
                icon.classList.remove("bi-chevron-right");
                icon.classList.add("bi-chevron-down");
            });

            collapseElement.addEventListener("hide.bs.collapse", function () {
                icon.classList.remove("bi-chevron-down");
                icon.classList.add("bi-chevron-right");
            });
        });

        // JavaScript pour afficher ou masquer les follows
        document.addEventListener("DOMContentLoaded", function () {
            let collapseElement = document.getElementById("followed-users");
            let icon = document.getElementById("followed-users-icon");

            collapseElement.addEventListener("show.bs.collapse", function () {
                icon.classList.remove("bi-chevron-right");
                icon.classList.add("bi-chevron-down");
            });

            collapseElement.addEventListener("hide.bs.collapse", function () {
                icon.classList.remove("bi-chevron-down");
                icon.classList.add("bi-chevron-right");
            });
        });
        
        document.getElementById('fllwrs').addEventListener('click', function() {
            var fllwrsModal = new bootstrap.Modal(document.getElementById('fllwrsModal'));
            fllwrsModal.show();
        });

        // gestionnaire de clic pour ouvrir l'input de fichier
        document.getElementById('openImage').addEventListener('click', function() {
            document.getElementById('image').click();
        });

        // Gestionnaire de preview de l'image & gif
        document.getElementById('image').addEventListener('change', function() {
            const file = this.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                const preview = document.getElementById('preview');
                preview.innerHTML = '';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-fluid', 'rounded', 'mb-2');
                preview.appendChild(img);
            };

            reader.readAsDataURL(file);
        });
    </script>
    <script src="../common/js/like.js"></script>
    <script src="../common/js/follow.js"></script>
    <script src="../common/js/see_more.js"></script>
    <script src="../common/js/gif.js"></script>
    <script src="../common/js/pixel.js"></script>
    <script src="../common/js/comment.js"></script>
    <script src="../common/js/del_post.js"></script>
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
    <script src="../common/js/notif.js"></script>

</body>
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container text-center">
        <p>&copy; <?= date("Y") ?> Universee - Tous droits réservés</p>
        <ul class="list-inline">
            <li class="list-inline-item"><a href="legal/cgu.pdf" class="text-light">Conditions Générales d'Utilisation</a></li>
            <li class="list-inline-item"><a href="confidentialite.php" class="text-light">Politique de confidentialité</a></li>
            <li class="list-inline-item"><a href="contact.php" class="text-light">Contact</a></li>
        </ul>
    </div>
</footer>
</html>
