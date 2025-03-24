<?php

include 'db.php'; // Connexion à la base de données

function fetchPost($conn, $searchQuery = '', $hashtag = '') {
    if (!empty($hashtag)) {
        $sql = "SELECT post.*, users.username, users.pp FROM post 
                JOIN users ON post.user_id = users.id 
                WHERE FIND_IN_SET(?, post.hashtags)
                ORDER BY post.date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $hashtag);
    } 
    elseif (!empty($searchQuery)) {
        $sql = "SELECT post.*, users.username, users.pp FROM post 
                JOIN users ON post.user_id = users.id 
                WHERE post.content LIKE ?
                ORDER BY post.date DESC";
        $stmt = $conn->prepare($sql);
        $searchTerm = "%" . $searchQuery . "%";
        $stmt->bind_param("s", $searchTerm);
    } 
    else {
        $sql = "SELECT post.*, users.username, users.pp FROM post 
                JOIN users ON post.user_id = users.id 
                ORDER BY post.date DESC";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($post = $result->fetch_assoc()) {
            displayPost($conn, $post);
        }
    } else {
        echo "<p>Aucun post trouvé.</p>";
    }
}

function displayPost($conn, $post) {
    $postId = $post['id'];
    $authorId = $post['user_id'];

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $currentUserId = $_SESSION['user_id'] ?? null;

    // Récupérer les infos de l'utilisateur connecté
    $queryUser = $conn->prepare("SELECT follow FROM users WHERE id = ?");
    $queryUser->bind_param("i", $currentUserId);
    $queryUser->execute();
    $userResult = $queryUser->get_result();
    $user = $userResult->fetch_assoc();
    
    $followedUsers = json_decode($user['follow'] ?? '[]', true);
    $isFollowing = in_array($authorId, $followedUsers);

    ?>
        <div class="card post mb-3" data-post-id="<?= htmlspecialchars($post['id']) ?>">
            <div class="card-body">
                <h5 class="card-title">
                    <a href="profile.php?u=<?= htmlspecialchars($post['user_id']) ?>" class="text-decoration-none">
                        <img src="<?= htmlspecialchars($post['pp']) ?>" alt="PP" class="profile-pic">
                        <?= htmlspecialchars($post['username']) ?>
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
                            return nl2br(htmlspecialchars_decode($content)); // Convertir les retours à la ligne et appliquer htmlspecialchars
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
}

function formatPostContent($content, $hashtags) {
    if (!empty($hashtags)) {
        $tagsArray = explode(',', $hashtags);
        foreach ($tagsArray as $tag) {
            $tag = trim($tag);
            $content = preg_replace("/(#" . preg_quote($tag, '/') . ")/i", "<a href='search.php?tag=$tag' class='hashtag'>$1</a>", $content);
        }
    }
    return nl2br(htmlspecialchars_decode($content));
}
?>
