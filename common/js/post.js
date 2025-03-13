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