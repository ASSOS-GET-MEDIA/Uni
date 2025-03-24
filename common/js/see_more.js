function toggleContent(postId) {
    var shortContent = document.getElementById('short-content-' + postId);
    var fullContent = document.getElementById('full-content-' + postId);
    var btn = document.getElementById('toggle-btn-' + postId);

    if (fullContent.style.display === 'none') {
        fullContent.style.display = 'inline';  // Afficher le contenu complet
        shortContent.style.display = 'none';  // Cacher le contenu tronqué
        btn.textContent = 'Voir moins';  // Changer le texte du bouton
    } else {
        fullContent.style.display = 'none';  // Cacher le contenu complet
        shortContent.style.display = 'inline';  // Afficher le contenu tronqué
        btn.textContent = 'Voir plus';  // Restaurer le texte du bouton
    }
}
