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
                location.reload();
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