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
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">
                <img src="<?= htmlspecialchars($post['pp']) ?>" alt="PP" class="profile-pic"> 
                <?= htmlspecialchars($post['username']) ?>
                <?php if ($currentUserId && $currentUserId != $authorId): ?>
                    <button class="btn btn-outline-primary btn-sm follow-btn float-end" 
                            data-author-id="<?= $authorId ?>" 
                            data-user-id="<?= $currentUserId ?>" 
                            data-following="<?= $isFollowing ? 'true' : 'false' ?>">
                        <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
                    </button>
                <?php endif; ?>
            </h5>
            <p class="card-text"><?= formatPostContent($post['content'], $post['hashtags']) ?></p>
            <?php if (!empty($post['gif'])): ?>
                <img src="<?= htmlspecialchars($post['gif']) ?>" alt="GIF du post" class="img-fluid">
            <?php endif; ?>
            
            <button class="btn btn-outline-secondary btn-sm like-btn" data-post-id="<?= $post['id'] ?>">
                👍 <span class="like-count"><?= $post['nb_likes'] ?></span>
            </button>

            <button class="btn btn-outline-secondary btn-sm" onclick="toggleCommentBox(<?= htmlspecialchars($post['id']) ?>)">
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
