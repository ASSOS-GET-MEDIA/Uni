<?php
// Assurer que la session est bien démarrée
session_start();

// Connexion à la base de données avec mysqli
include '../common/php/db.php';

// Récupérer l'ID du post à partir de l'URL
$postId = $_GET['id'];

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
                    // Requête pour récupérer les informations du post
                    $queryPost = $conn->prepare("SELECT post.*, users.username, users.pp, users.grade FROM post JOIN users ON post.user_id = users.id WHERE post.id = ?");
                    $queryPost->bind_param("i", $postId);
                    $queryPost->execute();
                    $resultPost = $queryPost->get_result();

                    // Requête pour vérifier si l'utilisateur a déjà voté
                    $queryPollVotes = $conn->prepare("SELECT poll_votes FROM post WHERE id = ?");
                    $queryPollVotes->bind_param("i", $postId);
                    $queryPollVotes->execute();
                    $resultPollVotes = $queryPollVotes->get_result();
                    $pollVotesData = $resultPollVotes->fetch_assoc();

                    $pollVotes = json_decode($pollVotesData['poll_votes'], true); // Décoder les votes en tableau associatif
                    $userVoted = false;
                    $userVoteChoice = '';

                    if ($pollVotes && is_array($pollVotes)) {
                        foreach ($pollVotes as $vote) {
                            if ($vote['user_id'] == $currentUserId) {
                                $userVoted = true;
                                $userVoteChoice = $vote['choice'];
                                break;
                            }
                        }
                    }

                    if ($resultPost->num_rows > 0) {
                        $post = $resultPost->fetch_assoc();
                        ?>
                        <div class="card post mb-3" data-post-id="<?= $postId ?>">
                            <div class="card-body">
                            <h5 class="card-title">
                                <a href="profile.php?u=<?= htmlspecialchars($post['user_id']) ?>" class="text-decoration-none">
                                    <img src="<?= htmlspecialchars($post['pp']) ?>" alt="PP" class="profile-pic me-2">
                                    <?= htmlspecialchars($post['username']) ?>
                                    <?php 
                                        $grade = $post['grade'];
                                        $gradeText = "";
                                        $gradeClass = "";

                                        switch ($grade) {
                                            case 0:
                                                $gradeClass = "";
                                                break;
                                            case 1:
                                                $gradeClass = "bi bi-check-circle-fill";
                                                break;
                                            case 2:
                                                $gradeClass = "badge-info";
                                                break;
                                            case 3:
                                                $gradeClass = "bi bi-shield-fill";
                                                break;
                                            case 4:
                                                $gradeClass = "bi bi-command"; // Admin en rouge
                                                break;
                                        }
                                    ?>
                                    <!-- Afficher le badge du grade -->
                                    <i class="<?= $gradeClass ?> text-primary"></i>
                                </a>
                                <!-- Bouton Follow/Unfollow -->
                                <?php if ($currentUserId && $currentUserId != $authorId): // Empêcher de se suivre soi-même ?>
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
                                        return htmlspecialchars_decode($content); // Convertir les retours à la ligne et appliquer htmlspecialchars
                                    }
                                }

                                $maxLength = 300; // Nombre de caractères à afficher par défaut
                                // Appliquer la mise en forme
                                $formattedContent = highlightHashtags(htmlspecialchars_decode($post['content']), $post['hashtags']);

                                // Si le contenu est plus long que la longueur maximale, on le coupe
                                if (strlen($formattedContent) > $maxLength) {
                                    $shortContent = substr($formattedContent, 0, $maxLength) . '...';  // Partie visible
                                    $fullContent = $formattedContent;  // Contenu complet
                                } else {
                                    $shortContent = $formattedContent;  // Si le contenu est court, on l'affiche complètement
                                    $fullContent = '';  // Pas besoin de contenu complet ici
                                }
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

                            <?php if (!empty($post['image_url'])): ?>
                                <img src="<?= htmlspecialchars($post['image_url']) ?>" class="img-fluid mb-2 rounded" alt="image">
                            <?php endif; ?>

                            <?php if (!empty($post['gif_url'])): ?>
                                <img src="<?= htmlspecialchars($post['gif_url']) ?>" class="img-fluid mb-2 rounded" alt="GIF">
                            <?php endif; ?>
                            
                            <!-- Affichage du sondage -->
                            <?php 
                            // Affichage du sondage
                            if (!empty($post['poll_question']) && !empty($post['poll_options'])): ?>
                                <div class="poll-section mt-3">
                                    <h6><strong><?= htmlspecialchars($post['poll_question']) ?></strong></h6>
                                    <?php
                                        $pollOptions = json_decode($post['poll_options'], true);
                                        $totalVotes = count($pollVotes); // Nombre total de votes

                                        if (is_array($pollOptions)) {
                                            foreach ($pollOptions as $index => $option) {
                                                // Compter les votes pour cette option
                                                $votesForOption = array_count_values($pollVotes)[$option] ?? 0;
                                                $votePercentage = ($totalVotes > 0) ? round(($votesForOption / $totalVotes) * 100, 2) : 0;

                                                // Vérifier si l'utilisateur a voté pour cette option
                                                $isDisabled = $userVoted ? 'disabled' : '';

                                                echo '<div class="d-flex align-items-center mb-2">
                                                        <button class="btn btn-outline-primary btn-sm vote-btn me-2 w-100 ' . $isDisabled . '" 
                                                                data-post-id="' . $postId . '" 
                                                                data-option="' . htmlspecialchars($option) . '" ' . ($currentUserId ? '' : 'disabled') . '>
                                                            <span class="badge rounded-pill bg-white text-primary float-start">' . htmlspecialchars($option) . '</span>
                                                            <span class="votes float-end text-muted">' . $votesForOption . ' votes (' . $votePercentage . '%)</span>
                                                            <div class="progress-bar" style="width: ' . $votePercentage . '%;"></div>
                                                        </button>
                                                    </div>';                             
                                            }
                                        }
                                    ?>
                                    <?php if ($userVoted): ?>
                                        <p class="text-muted mt-2">Vous avez déjà voté pour <strong><?= htmlspecialchars($userVoteChoice) ?></strong></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                                <div class="actions">
                                    <!-- Formulaire pour le Like -->
                                    <button class="btn btn-sm like-btn <?= $userLiked ? 'btn-primary' : 'btn-outline-secondary' ?>" 
                                            data-post-id="<?= $postId ?>" <?= $currentUserId ? '' : 'disabled' ?>>
                                        <i class="bi <?= $userLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?>"></i> 
                                        <span class="like-count"><?= $post['nb_likes'] ?></span>
                                    </button>
                                    
                                    <!-- Bouton pour afficher/masquer la boîte de commentaires -->
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#comment-box-<?= htmlspecialchars($post['id']) ?>" aria-expanded="false" aria-controls="comment-box-<?= htmlspecialchars($post['id']) ?>">
                                        <i class="bi bi-chat-left"></i> <?= $post['nb_coms'] ?>
                                    </button>

                                    <!-- Bouton pour afficher/masquer la boîte de partage -->
                                    <button class="disabled btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-send"></i> <?= $post['nb_share'] ?>
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
                    } else {
                        echo "<p>Post non trouvé.</p>";
                    }
                    ?>
                </div>

                <!-- Section des commentaires -->
                <div class="card mt-3">
                    <div class="card-header">Commentaires</div>
                    <div class="card-body">
                        <?php
                        // Requête pour récupérer les commentaires du post
                        $queryComments = $conn->prepare("SELECT comments.content, comments.created_at, users.username, users.pp, users.grade FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ?");
                        $queryComments->bind_param("i", $postId);
                        $queryComments->execute();
                        $resultComments = $queryComments->get_result();

                        if ($resultComments->num_rows > 0) {
                            while ($comment = $resultComments->fetch_assoc()) {
                                ?>
                                <div class="d-flex mb-3">
                                    <img src="<?= htmlspecialchars($comment['pp']) ?>" alt="PP" class="profile-pic me-2">
                                    <div>
                                        <?php 
                                        $grade = $comment['grade'];
                                        $gradeText = "";
                                        $gradeClass = "";

                                        switch ($grade) {
                                            case 0:
                                                $gradeClass = "";
                                                break;
                                            case 1:
                                                $gradeClass = "bi bi-check-circle-fill";
                                                break;
                                            case 2:
                                                $gradeClass = "badge-info";
                                                break;
                                            case 3:
                                                $gradeClass = "badge-warning";
                                                break;
                                            case 4:
                                                $gradeClass = "bi bi-command"; // Admin en rouge
                                                break;
                                        }
                                        ?>
                                        <!-- Afficher le badge du grade -->
                                        <h5><?= htmlspecialchars($comment['username']) ?><i class="<?= $gradeClass ?> ms-2 text-primary"></i></h5>
                                        <p class="mb-1"><?= htmlspecialchars($comment['content']) ?></p>
                                        <small class="text-muted"><?= htmlspecialchars($comment['created_at']) ?></small>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>Aucun commentaire pour le moment.</p>";
                        }
                        ?>
                    </div>
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
