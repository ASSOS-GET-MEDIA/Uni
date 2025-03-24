document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const buttonElement = e.target.closest('.like-btn'); // Trouver le bon bouton
            const postId = buttonElement?.getAttribute('data-post-id'); // Récupérer l'ID du post
            
            // Trouver uniquement le compteur de likes associé à CE bouton
            const likeCountElement = buttonElement.querySelector('.like-count'); 
            const iconElement = buttonElement.querySelector('i'); 

            if (!postId) {
                console.error("Erreur : aucun ID de post trouvé.");
                return;
            }

            if (!likeCountElement) {
                console.error("Erreur : élément .like-count introuvable dans le bouton.");
                return;
            }

            // Désactiver temporairement le bouton pour éviter le spam
            buttonElement.disabled = true;

            // Envoi de la requête AJAX
            fetch('../common/php/like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `post_id=${encodeURIComponent(postId)}`
            })
            .then(response => response.json()) // Convertir la réponse en JSON
            .then(data => {
                if (data.status === 'success') {
                    likeCountElement.textContent = data.new_like_count; // Mettre à jour le nombre de likes
                    
                    // Mettre à jour la classe du bouton
                    buttonElement.classList.toggle('btn-primary', data.userLiked);
                    buttonElement.classList.toggle('btn-outline-secondary', !data.userLiked);
                    
                    // Mettre à jour l'icône
                    if (iconElement) {
                        iconElement.classList.toggle('bi-hand-thumbs-up-fill', data.userLiked);
                        iconElement.classList.toggle('bi-hand-thumbs-up', !data.userLiked);
                    }
                } else {
                    alert('Une erreur est survenue lors du like.');
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
                alert('Une erreur est survenue.');
            })
            .finally(() => {
                buttonElement.disabled = false; // Réactiver le bouton après la requête
            });
        });
    });
});
