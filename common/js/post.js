document.addEventListener("DOMContentLoaded", function () {
    const gifGrid = document.getElementById("gifGrid");
    const gifInput = document.getElementById("selectedGifUrl");
    const gifSearch = document.getElementById("gifSearch");
    const openGifModalBtn = document.getElementById("openGifModal");

    // Gestion de la soumission du formulaire
    document.getElementById('post-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const content = document.getElementById('content').value;
        const fileInput = document.getElementById('image');
        const file = fileInput.files[0];
        const gifUrl = gifInput.value;
        const pollQuestion = document.getElementById('poll-question') ? document.getElementById('poll-question').value : '';
        const pollOptions = [];
        document.querySelectorAll('#poll-options input').forEach(input => {
            pollOptions.push(input.value);
        });

        if (!content.trim()) {
            alert('Le contenu ne peut pas être vide.');
            return;
        }

        const formData = new FormData();
        formData.append('content', content);
        if (file) {
            formData.append('image', file);
        }
        formData.append('gif_url', gifUrl);
        formData.append('poll_question', pollQuestion);
        if (pollOptions.length > 0) {
            formData.append('poll_options', JSON.stringify(pollOptions));
        }

        fetch('../common/php/add_post.php', {
            method: 'POST',
            enctype: 'multipart/form-data',
            body: formData
        })
        .then(response => response.json()) // Récupère bien la réponse en JSON
        .then(data => {
            if (data.status === 'success') {
                location.reload();
                const newPost = document.createElement('div');
                newPost.innerHTML = `
                    <div class="alert alert-success" role="alert">
                        Posté avec succès !
                    </div>
                `;
                
                document.getElementById('posts-list').prepend(newPost);
                document.getElementById('content').value = '';
                gifInput.value = '';
            } else {
                console.error('Erreur détectée:', data);
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi du post:', error);
            alert('Une erreur s\'est produite, veuillez réessayer.');
        });
    });
});
