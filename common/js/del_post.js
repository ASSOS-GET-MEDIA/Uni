// Gestionnaire d'événement pour la suppression de post
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-post');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Empêcher un comportement par défaut éventuel

            const buttonElement = e.target.closest('.delete-post'); // Trouver le bouton parent
            const postId = buttonElement.getAttribute('data-post-id'); // Récupérer l'ID du post

            if (!postId) {
                console.error("Erreur : aucun ID de post trouvé.");
                return;
            }

            // Envoi de la requête AJAX pour supprimer le post
            fetch('../common/php/del_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `post_id=${encodeURIComponent(postId)}` // Encodage sécurisé des données
            })
            .then(response => response.json()) // Convertir la réponse en JSON
            .then(data => {
                if (data.status === 'success') {
                    // Supprimer visuellement le post
                    location.reload(); 
                } else {
                    alert('Une erreur est survenue lors de la suppression.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            });
        });
    });
});
