document.addEventListener("DOMContentLoaded", function () {
    fetch('../common/php/fetch_trending_hashtags.php')
        .then(response => response.json())
        .then(data => {
            let hashtagList = document.getElementById('trending-hashtags');
            hashtagList.innerHTML = ''; // Vide la liste avant d'ajouter les hashtags

            if (data.length > 0) {
                data.forEach(hashtags => {
                    let listItem = document.createElement('li');
                    listItem.className = 'list-group-item';
                    listItem.innerHTML = `<a href="search.php?tag=${encodeURIComponent(hashtags.hashtags)}"><p class='hashtag'>#${hashtags.hashtags}</p></a>`;
                    hashtagList.appendChild(listItem);
                });
            } else {
                hashtagList.innerHTML = '<li class="list-group-item">Aucune tendance pour l\'instant</li>';
            }
        })
        .catch(error => console.error('Erreur de chargement des hashtags:', error));
});