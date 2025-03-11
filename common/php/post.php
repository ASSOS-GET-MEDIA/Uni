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
?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">
                    <img src="<?= htmlspecialchars($post['pp']) ?>" alt="PP" class="rounded-circle" width="40"> 
                    <?= htmlspecialchars($post['username']) ?>
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
                <p class="card-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                
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
