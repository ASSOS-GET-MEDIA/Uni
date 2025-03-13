<?php
// Assurer que la session est bien démarrée
session_start();

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
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../common/css/post.css">
    <link rel="stylesheet" href="../common/css/base.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 align-items-center">
        <a class="navbar-brand d-flex flex-row" href="homepage.php"><h4>Universee</h4><p class="ms-2 text-secondary">v2</p></a>
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
                    <img src="<?= htmlspecialchars($user['pp']) ?>" alt="PP" class="profile-pic">
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="profile.php?u=<?= $currentUserId ?>">Profil</a></li>
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
                    <div class="card-header">Tendances</div>
                    <ul class="list-group list-group-flush" id="trending-hashtags">
                        <li class="list-group-item">Chargement...</li>
                    </ul>
                </div>
            </aside>


            <main class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <form id="post-form">
                            <textarea class="form-control" name="content" id="content" rows="3" placeholder="Exprimez-vous..."></textarea>
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
                                <button type="button" class="btn btn-outline-secondary mt-2" id="poll-toggle-btn">
                                    Ajouter un sondage
                                </button>
                                <button class="btn btn-primary mt-2" type="submit">Publier</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des posts (sera mise à jour dynamiquement) -->
                <div id="posts-list">
                    <?php include '../common/php/post.php'; ?>
                </div>
            </main>

            <aside class="col-md-3 d-none d-md-block">
                <div class="card">
                <div class="card-header">Personnes que vous suivez</div>
                    <ul class="list-group list-group-flush">
                        <?php if (count($followedUserList) > 0): ?>
                            <?php foreach ($followedUserList as $followedUser): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($followedUser['pp']) ?>" alt="PP" class="profile-pic">
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
    <script src="../common/js/vote.js"></script>

    <script>
        function toggleCommentBox(postId) {
            let commentBox = document.getElementById("comment-box-" + postId);
            if (commentBox.style.display === "none" || commentBox.style.display === "") {
                commentBox.style.display = "block";
            } else {
                commentBox.style.display = "none";
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            $(".vote-btn").each(function() {
                var button = $(this);
                var progressBar = button.find(".progress-bar");
                var textElement = button.find("span");

                var progressWidth = progressBar.width();
                var buttonWidth = button.width();

                // Vérifie si la barre de progression a atteint ou dépasse le texte
                if (progressWidth >= buttonWidth) {
                    button.addClass("text-white"); // Applique la classe pour changer la couleur du texte en blanc
                } else {
                    button.removeClass("text-white"); // Retire la classe si ce n'est pas le cas
                }
            });
        });
    </script>

    <script>
        // Afficher/masquer la section du sondage
        document.getElementById('poll-toggle-btn').addEventListener('click', function() {
            var pollSection = document.getElementById('poll-section');
            pollSection.style.display = (pollSection.style.display === 'none') ? 'block' : 'none';
        });

        // Ajouter un champ de réponse supplémentaire (jusqu'à 5)
        document.getElementById('add-option-btn').addEventListener('click', function() {
            var pollOptions = document.getElementById('poll-options');
            var existingOptions = pollOptions.getElementsByTagName('input').length;

            // Ajouter une nouvelle option si il y en a moins de 5
            if (existingOptions < 4) {  // 10 champ maximum (2 initialement + 8 ajoutés)
                var newOption = document.createElement('div');
                newOption.classList.add('input-group', 'mb-2');
                newOption.innerHTML = `
                    <input type="text" class="form-control" name="poll_options[]" placeholder="Option ${existingOptions + 1}">
                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">❌</button>
                `;
                pollOptions.appendChild(newOption);
            } else {
                alert("Vous ne pouvez ajouter que 5 réponses.");
            }
        });

        // Supprimer une option, mais garder au moins deux champs
        function removeOption(button) {
            var pollOptions = document.getElementById('poll-options');
            var optionsCount = pollOptions.getElementsByTagName('input').length;

            // S'assurer qu'il reste au moins deux options
            if (optionsCount > 2) {
                var inputGroup = button.parentNode;
                inputGroup.remove();
            } else {
                alert("Vous devez garder au moins deux réponses.");
            }
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            fetch('../common/php/fetch_trending_hashtags.php')
                .then(response => response.json())
                .then(data => {
                    let hashtagList = document.getElementById('trending-hashtags');
                    hashtagList.innerHTML = ''; // Vide la liste avant d'ajouter les hashtags

                    if (data.length > 0) {
                        data.forEach(hashtag => {
                            let listItem = document.createElement('li');
                            listItem.className = 'list-group-item';
                            listItem.innerHTML = `<a href="search.php?tag=${encodeURIComponent(hashtag.hashtag)}"><p class='hashtag'>#${hashtag.hashtags} (${hashtag.count})</p></a>`;
                            hashtagList.appendChild(listItem);
                        });
                    } else {
                        hashtagList.innerHTML = '<li class="list-group-item">Aucune tendance pour l\'instant</li>';
                    }
                })
                .catch(error => console.error('Erreur de chargement des hashtags:', error));
        });
    </script>

    <script>
        document.getElementById('post-form').addEventListener('submit', function(e) {
            e.preventDefault(); // Empêche le rechargement de la page
            
            const content = document.getElementById('content').value;
            const pollQuestion = document.getElementById('poll-question') ? document.getElementById('poll-question').value : '';
            const pollOptions = [];
            if (document.getElementById('poll-options')) {
                const optionInputs = document.querySelectorAll('#poll-options input');
                optionInputs.forEach(input => {
                    pollOptions.push(input.value);
                });
            }

            if (!content.trim()) {
                alert('Le contenu ne peut pas être vide.');
                return;
            }

            const formData = new FormData();
            formData.append('content', content);
            formData.append('poll_question', pollQuestion);
            formData.append('poll_options', JSON.stringify(pollOptions));

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
            <li class="list-inline-item"><a href="legal/cgu.pdf" class="text-light">Conditions Générales d'Utilisation</a></li>
            <li class="list-inline-item"><a href="confidentialite.php" class="text-light">Politique de confidentialité</a></li>
            <li class="list-inline-item"><a href="contact.php" class="text-light">Contact</a></li>
        </ul>
    </div>
</footer>
</html>
