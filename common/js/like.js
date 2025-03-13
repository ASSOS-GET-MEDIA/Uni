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