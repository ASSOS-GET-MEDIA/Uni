<?php
// Connexion à la base de données avec mysqli
include 'db.php';

// Requête pour récupérer les posts
$query = "SELECT post.*, users.username, users.pp FROM post 
          JOIN users ON post.user_id = users.id 
          ORDER BY post.date DESC";
$result = $conn->query($query);

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
        $user = $userResult->fetch_assoc();

        $followedUsers = json_decode($user['follow'], true); // Les utilisateurs suivis par l'utilisateur actuel
        
        // Vérifier si l'utilisateur actuel suit l'auteur du post
        $isFollowing = in_array($authorId, $followedUsers);

        // Vérifier si des votes sont enregistrés
        $pollVotes = !empty($post['poll_votes']) ? json_decode($post['poll_votes'], true) : [];
        $userVoted = false;
        $userVoteChoice = null;

        // Vérifier si l'utilisateur a déjà voté
        if (!empty($pollVotes) && isset($pollVotes[$currentUserId])) {
            $userVoted = true;
            $userVoteChoice = $pollVotes[$currentUserId]; // Option choisie par l'utilisateur
        }
?>
        <div class="card mb-3">
            <div class="card-body">
            <h5 class="card-title">
                <a href="profile.php?u=<?= htmlspecialchars($post['user_id']) ?>" class="text-decoration-none">
                    <img src="<?= htmlspecialchars($post['pp']) ?>" alt="PP" class="rounded-circle" width="40">
                    <?= htmlspecialchars($post['username']) ?>
                </a>
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
                
                <!-- Affichage du sondage -->
                <?php if (!empty($post['poll_question']) && !empty($post['poll_options'])): ?>
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
                                            <button class="btn btn-outline-primary btn-sm vote-btn me-2 w-100" data-post-id="' . $postId . '" data-option="' . htmlspecialchars($option) . '" ' . $isDisabled . '>
                                                ' . htmlspecialchars($option) . ' <span class="float-end text-muted">' . $votesForOption . ' votes (' . $votePercentage . '%)</span>
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
