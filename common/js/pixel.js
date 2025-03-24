// Fonction pour générer des pixels
function createPixel() {
    // Créer un élément div
    var pixel = document.createElement("div");
    pixel.classList.add("pixel");
    
    // Tableau de couleurs possibles
    var colors = ['#ff3366', '#2ec4b6', '#20a4f3'];  // Liste des couleurs
    var randomColor = colors[Math.floor(Math.random() * colors.length)];  // Choisir une couleur aléatoire
    
    pixel.style.backgroundColor = randomColor;  // Appliquer la couleur aléatoire
    
    // Position aléatoire sur la page
    var randomX = Math.random() * window.innerWidth;  // Position X aléatoire
    var randomY = Math.random() * window.innerHeight;  // Position Y aléatoire
    
    pixel.style.left = randomX + "px";
    pixel.style.top = randomY + "px";
    
    // Ajouter le pixel à la page
    document.body.appendChild(pixel);
    
    // Supprimer le pixel après 4 secondes (pour correspondre à la durée de l'animation)
    setTimeout(function() {
        pixel.remove();
    }, 4000);  // La durée doit correspondre à la durée de l'animation
}

// Générer un pixel toutes les 300ms
setInterval(createPixel, 200);
