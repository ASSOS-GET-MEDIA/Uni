document.addEventListener("DOMContentLoaded", function() {
    const notifList = document.getElementById("notif");
    const notifCountBadge = document.getElementById("notif-count");

    // Récupérer les notifications via AJAX
    fetch('../common/php/notifs.php') // Remplace 'path/to/your/php/script.php' par le chemin vers ton script PHP
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const notifications = data.notifications;
                notifCountBadge.textContent = notifications.length; // Mettre à jour le nombre de notifications

                // Affichage des notifications
                notifications.forEach(notification => {
                    const li = document.createElement('li');
                    li.classList.add('list-group-item');
                    li.classList.add('d-flex');
                    li.classList.add('justify-content-between');
                    li.classList.add('align-items-center');

                    const notifContent = document.createElement('span');
                    notifContent.textContent = notification.content;

                    const removeBtn = document.createElement('button');
                    removeBtn.classList.add('btn');
                    removeBtn.classList.add('btn-sm');
                    removeBtn.classList.add('btn-danger');
                    removeBtn.textContent = 'Supprimer';

                    // Si le post_id existe, ajouter un lien vers le post
                    if (notification.post_id) {
                        const link = document.createElement('a');
                        link.href = 'comment.php?id=' + notification.post_id; // Remplace 'path/to/post.php?id=' par l'URL du post
                        link.textContent = 'Voir le post';
                        link.classList.add('btn');
                        link.classList.add('btn-primary');
                        link.classList.add('btn-sm');
                        notifContent.appendChild(link);
                    }

                    removeBtn.addEventListener('click', function() {
                        // Appeler un autre PHP pour supprimer la notification
                        fetch('../common/php/del_notif.php', {
                            method: 'POST',
                            body: JSON.stringify({ notif_id: notification.notif_id })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Supprimer la notification de la liste
                                li.remove();
                                notifCountBadge.textContent = parseInt(notifCountBadge.textContent) - 1; // Mettre à jour le nombre de notifications
                            } else {
                                console.log('Erreur lors de la suppression de la notification');
                            }
                        });
                    });

                    li.appendChild(notifContent);
                    li.appendChild(removeBtn);
                    notifList.appendChild(li);
                });
            } else {
                console.log('Erreur lors du chargement des notifications');
            }
        })
        .catch(error => console.error('Erreur:', error));
});