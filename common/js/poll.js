// Afficher/masquer la section du sondage
document.getElementById('poll-toggle-btn').addEventListener('click', function() {
    var pollSection = document.getElementById('poll-section');
    pollSection.style.display = (pollSection.style.display === 'none') ? 'block' : 'none';
});

// Ajouter un champ de réponse supplémentaire (jusqu'à 5)
document.getElementById('add-option-btn').addEventListener('click', function() {
    var pollOptions = document.getElementById('poll-options');
    var existingOptions = pollOptions.getElementsByTagName('input').length;

    // Ajouter une nouvelle option si il y en a moins de 5
    if (existingOptions < 4) {  // 10 champ maximum (2 initialement + 8 ajoutés)
        var newOption = document.createElement('div');
        newOption.classList.add('input-group', 'mb-2');
        newOption.innerHTML = `
            <input type="text" class="form-control" name="poll_options[]" placeholder="Option ${existingOptions + 1}">
            <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">❌</button>
        `;
        pollOptions.appendChild(newOption);
    } else {
        alert("Vous ne pouvez ajouter que 5 réponses.");
    }
});

// Supprimer une option, mais garder au moins deux champs
function removeOption(button) {
    var pollOptions = document.getElementById('poll-options');
    var optionsCount = pollOptions.getElementsByTagName('input').length;

    // S'assurer qu'il reste au moins deux options
    if (optionsCount > 2) {
        var inputGroup = button.parentNode;
        inputGroup.remove();
    } else {
        alert("Vous devez garder au moins deux réponses.");
    }
}