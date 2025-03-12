<?php
include '../common/php/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_GET['u'];

// Requête pour récupérer les informations de l'utilisateur connecté
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$con_user = $result->fetch_assoc();

// Requête pour récupérer les informations de l'utilisateur du profile
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

// Fonction pour formater les posts avec des hashtags cliquables
function highlightHashtags($content) {
    return preg_replace('/#(\w+)/', '<a href="search.php?hashtag=$1" class="hashtag">#$1</a>', $content);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .profile-pic {
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
        <a class="navbar-brand d-flex flex-row" href="homepage.php">Universee<p class="ms-2 text-secondary">v2</p></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse " id="navbarNav">
            <!-- Bouton de profil avec menu déroulant -->
            <div class="dropdown ms-auto">
                <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($con_user['pp']) ?>" alt="PP" class="rounded-circle" width="40">
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
                        <img src="<?= htmlspecialchars($user['pp']) ?>" alt="Photo de profil" class="profile-pic mb-3">
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
                $stmt->bind_param("i", $user_id);
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
                                    <img src="<?= htmlspecialchars($user['pp']) ?>" alt="PP" class="rounded-circle" width="40"> 
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
                                                    $content = preg_replace("/(#" . preg_quote($tag, '/') . ")/i", "<a href='search.php?tag=$tag' class='hashtag'>$1</a>", $content);
                                                }
                                            }
                                            return nl2br(htmlspecialchars_decode($content));
                                        }
                                    }                    

                                    // Appliquer la mise en forme
                                    $formattedContent = highlightHashtags(htmlspecialchars_decode($post['content']), $post['hashtags']);
                                ?>
                                <p class="card-text"><?= nl2br($formattedContent) ?></p>
                                
                                <!-- Formulaire pour le Like -->
                                <button class="btn btn-outline-secondary btn-sm like-btn" data-post-id="<?= $post['id'] ?>">
                                    👍 <span class="like-count"><?= $post['nb_likes'] ?></span>
                                </button>
                                
                                <!-- Bouton pour afficher/masquer la boîte de commentaires -->
                                <button class="disabled btn btn-outline-secondary btn-sm" onclick="toggleCommentBox(<?= htmlspecialchars($post['id']) ?>)">
                                    💬 <?= $post['nb_coms'] ?>
                                </button>

                                <div id="comment-box-<?= htmlspecialchars($post['id']) ?>" style="display:none; margin-top:10px;">
                                    <form action="add_comment.php" method="POST">
                                        <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                        <textarea class="form-control" name="content" rows="2" placeholder="Votre commentaire..."></textarea>
                                        <button class="btn btn-primary mt-2 btn-sm" type="submit">Commenter</button>
                                    </form>
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
</body>
</html>
