<?php
include '../common/php/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_GET['u'];
$user_at = $_GET['at'];

// Requête pour récupérer les informations de l'utilisateur connecté
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$con_user = $result->fetch_assoc();

// Requête pour récupérer les informations de l'utilisateur du profile
$query = "SELECT * FROM users WHERE id = ? OR at = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $user_at);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Utilisateur introuvable.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?= htmlspecialchars($user['username']) ?></title>
    <link rel="icon" href="../common/assets/logos/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
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
        .follow-btn {
            float: right;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 align-items-center">
        <a class="navbar-brand d-flex flex-row" href="homepage.php"><h4>Universee</h4><p class="ms-2 text-secondary">v2</p></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse " id="navbarNav">
            <!-- Bouton de profil avec menu déroulant -->
            <div class="dropdown ms-auto">
                <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($con_user['pp']) ?>" alt="PP" class="profile-pic">
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="profile.php?u=<?= $_SESSION['user_id'] ?>">Profil</a></li>
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
                <div class="card profile-card">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($user['pp']) ?>" alt="Photo de profil" class="profile-pic-big mb-3">
                        <h3><?= htmlspecialchars($user['username']) ?></h3>
                        <p>@<?= htmlspecialchars($user['at']) ?></p>
                        <p><strong><?= htmlspecialchars($user['nb_follows']) ?> followers</strong></p>
                        <p><?= htmlspecialchars($user['info']) ?></p>
                        <a href="edit_profile.php" class="btn btn-outline-primary btn-sm" 
                            <?php if ($con_user['id'] != $user['id']) echo 'style="display:none;"'; ?>>
                            Modifier le profil
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <!-- Publications de l'utilisateur -->
                <?php
                $query = "SELECT * FROM post WHERE user_id = ?
                        ORDER BY post.date DESC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result) {
                    while ($post = $result->fetch_assoc()) {
                        $postId = $post['id'];
                        $authorId = $post['user_id']; // L'ID de l'utilisateur qui a posté
                        
                        // Vérifier si l'utilisateur actuel suit cet utilisateur
                        // Vérifie si la session est déjà démarrée
                        if (session_status() == PHP_SESSION_NONE) {
                            session_start(); // Démarre la session seulement si elle n'est pas déjà active
                        }
                        $currentUserId = $_SESSION['user_id']; // Supposons que l'utilisateur est connecté
                        
                        // Récupérer les informations de l'utilisateur connecté
                        $queryUser = $conn->prepare("SELECT nb_follows, follow, followed_by FROM users WHERE id = ?");
                        $queryUser->bind_param("i", $currentUserId);
                        $queryUser->execute();
                        $userResult = $queryUser->get_result();
                        $follow = $userResult->fetch_assoc();

                        $followedUsers = json_decode($follow['follow'], true); // Les utilisateurs suivis par l'utilisateur actuel
                        
                        // Vérifier si l'utilisateur actuel suit l'auteur du post
                        $isFollowing = in_array($authorId, $followedUsers);
                ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <img src="<?= htmlspecialchars($user['pp']) ?>" alt="PP" class="profile-pic"> 
                                    <?= htmlspecialchars($user['username']) ?>
                                    <!-- Bouton Follow/Unfollow -->
                                    <?php if ($currentUserId != $authorId): // Empêcher de se suivre soi-même ?>
                                        <button class="btn btn-outline-primary btn-sm follow-btn float-end" 
                                                data-author-id="<?= $authorId ?>" 
                                                data-user-id="<?= $currentUserId ?>" 
                                                data-following="<?= $isFollowing ? 'true' : 'false' ?>">
                                            <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
                                        </button>
                                    <?php endif; ?>
                                </h5>
                                <?php
                                    // Fonction pour transformer les hashtags en liens colorés
                                    if (!function_exists('highlightHashtags')) {
                                        function highlightHashtags($content, $hashtags) {
                                            if (!empty($hashtags)) {
                                                $tagsArray = explode(',', $hashtags);
                                                foreach ($tagsArray as $tag) {
                                                    $tag = trim($tag);
                                                    // Remplacer les hashtags par des liens cliquables
                                                    $content = preg_replace("/(#" . preg_quote($tag, '/') . ")/i", "<a href='search.php?tag=$tag' class='hashtag'>$1</a>", $content);
                                                }
                                            }
                                            return nl2br(htmlspecialchars_decode($content)); // Convertir les retours à la ligne et appliquer htmlspecialchars
                                        }
                                    }                  

                                    // Appliquer la mise en forme
                                    $formattedContent = highlightHashtags(htmlspecialchars_decode($post['content']), $post['hashtags']);
                                ?>
                                <div class="card-text" id="post-content-<?= $postId ?>">
                                    <?php
                                        // Vérifier si le contenu est plus long que 300 caractères
                                        $content = htmlspecialchars($post['content']);
                                        $shortContent = substr($content, 0, 300);
                                        $showButton = strlen($content) > 300;  // Si le contenu dépasse 300 caractères, afficher le bouton
                                        
                                        // Appliquer la fonction de mise en forme des hashtags sur le contenu complet et tronqué
                                        $formattedShortContent = highlightHashtags($shortContent, $post['hashtags']);
                                        $formattedContent = highlightHashtags($content, $post['hashtags']);
                                    ?>
                                    
                                    <!-- Contenu tronqué par défaut -->
                                    <span id="short-content-<?= $postId ?>">
                                        <?= nl2br($formattedShortContent) ?><?php if ($showButton) echo '...'; ?>
                                    </span>
                                    
                                    <!-- Contenu complet, masqué par défaut -->
                                    <span id="full-content-<?= $postId ?>" style="display:none;"><?= nl2br($formattedContent) ?></span>

                                    <!-- Bouton "Voir plus" uniquement si le contenu dépasse 300 caractères -->
                                    <?php if ($showButton): ?>
                                        <button class="btn btn-link" id="toggle-btn-<?= $postId ?>" onclick="toggleContent(<?= $postId ?>)">Voir plus</button>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($post['gif_url'])): ?>
                                    <img src="<?= htmlspecialchars($post['gif_url']) ?>" class="img-fluid mb-2 rounded" alt="GIF">
                                <?php endif; ?>
                                
                                <div class="">
                                    <!-- Formulaire pour le Like -->
                                    <button class="btn btn-outline-secondary btn-sm like-btn" data-post-id="<?= $post['id'] ?>">
                                        <i class="bi bi-hand-thumbs-up"></i> <span class="like-count"><?= $post['nb_likes'] ?></span>
                                    </button>
                                    
                                    <!-- Bouton pour afficher/masquer la boîte de commentaires -->
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#comment-box-<?= htmlspecialchars($post['id']) ?>" aria-expanded="false" aria-controls="comment-box-<?= htmlspecialchars($post['id']) ?>">
                                        <i class="bi bi-chat-left"></i> <?= $post['nb_coms'] ?>
                                    </button>

                                    <!-- Bouton pour afficher/masquer la boîte de commentaires -->
                                    <button class="disabled btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-send"></i></i> <?= $post['nb_share'] ?>
                                    </button>
                                </div>

                                <!-- Formulaire de commentaire, caché par défaut -->
                                <div id="comment-box-<?= htmlspecialchars($post['id']) ?>" class="collapse mt-3 comment-box">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <textarea class="form-control" id="comment-text-<?= $post['id'] ?>" placeholder="Ajoutez un commentaire..."></textarea>
                                        <button class="btn btn-sm btn-primary mt-2 add-comment" data-post="<?= $post['id'] ?>">Commenter</button>
                                    <?php else: ?>
                                        <p class="text-muted">Connectez-vous pour commenter.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                <?php 
                    }
                } else {
                    echo "Erreur de récupération des posts.";
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

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
