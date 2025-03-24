$(document).ready(function () {
    const selectedGifInput = $("#selectedGifUrl");
    const API_KEY = ""; // Clé API, à déplacer côté serveur pour plus de sécurité
    const LIMIT = 20;
    const gifGrid = $("#gifGrid");
    const gifSearchInput = $("#gifSearch");

    let debounceTimeout;

    // Fonction pour charger les GIFs
    function loadGifs(query = "") {
        const url = query
            ? `https://tenor.googleapis.com/v2/search?q=${query}&key=${API_KEY}&limit=${LIMIT}`
            : `https://tenor.googleapis.com/v2/categories?key=${API_KEY}`;

        $.getJSON(url)
        .done(function (data) {
            // Vérifier si 'data.results' existe et est un tableau
            if (data && data.results && Array.isArray(data.results)) {
                gifGrid.empty(); // Vider la grille avant d'ajouter les nouveaux GIFs
                let gifsHtml = '';
                data.results.forEach(gif => {
                    gifsHtml += `
                        <img src="${gif.media_formats.gif.url}" class="gif-item rounded border" data-url="${gif.media_formats.gif.url}" width="300" alt="GIF">
                    `;
                });
                gifGrid.append(gifsHtml);
            } else {
                console.error('La réponse de l\'API ne contient pas de champ "results" ou la structure est incorrecte');
            }
        })
        .fail(function () {
            alert("Une erreur est survenue lors du chargement des GIFs.");
        });
    }

    // Charger les GIFs tendances au démarrage
    loadGifs();

    // Rechercher un GIF avec délai pour éviter les requêtes fréquentes
    gifSearchInput.on("input", function () {
        const query = $(this).val().trim();
        clearTimeout(debounceTimeout);
        if (query.length > 2) {
            debounceTimeout = setTimeout(function () {
                loadGifs(query);
            }, 300); // Attendre 300ms après la saisie avant de rechercher
        }
    });

    // Ouvrir le modal et charger les GIFs tendances
    $("#openGifModal").click(function () {
        $("#gifModal").modal("show");
    });

    // Sélectionner un GIF et stocker l'URL dans l'input caché
    gifGrid.on("click", ".gif-item", function () {
        const gifUrl = $(this).data("url");
        selectedGifInput.val(gifUrl); // Stocke l'URL dans l'input caché
        $("#gifModal").modal("hide"); // Ferme le modal
    });
});