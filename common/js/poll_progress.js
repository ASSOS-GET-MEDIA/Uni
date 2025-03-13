$(document).ready(function() {
    $(".vote-btn").each(function() {
        var button = $(this);
        var progressBar = button.find(".progress-bar");
        var textElement = button.find("span");

        var progressWidth = progressBar.width();
        var buttonWidth = button.width();

        // Vérifie si la barre de progression a atteint ou dépasse le texte
        if (progressWidth >= buttonWidth) {
            button.addClass("text-white"); // Applique la classe pour changer la couleur du texte en blanc
        } else {
            button.removeClass("text-white"); // Retire la classe si ce n'est pas le cas
        }
    });
});