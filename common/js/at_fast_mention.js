document.addEventListener("DOMContentLoaded", function () {
    const textarea = document.getElementById("content");
    const mentionBox = document.getElementById("mention-suggestions");

    let users = []; // Liste des utilisateurs récupérée via AJAX

    // Fonction pour récupérer les utilisateurs en fonction de la saisie
    function fetchUsers(query) {
        if (query.length < 1) {
            mentionBox.style.display = "none";
            return;
        }

        console.log("Requête envoyée:", query); // Vérifier la requête
    
        fetch(`../common/php/get_mention.php?query=${query}`)
            .then(response => response.json())
            .then(data => {
                console.log("Réponse reçue:", data); // Vérifier la réponse JSON
                users = data;
                showSuggestions();
            })
            .catch(error => console.error("Erreur AJAX:", error)); // Vérifier les erreurs AJAX
    }    

    // Affiche la liste des suggestions
    function showSuggestions() {
        if (users.length === 0) {
            mentionBox.style.display = "none";
            return;
        }

        mentionBox.innerHTML = users
            .map(user => `<div class="dropdown-item mention-item">@${user.at}</div>`)
            .join("");

        mentionBox.style.display = "block";

        document.querySelectorAll(".mention-item").forEach(item => {
            item.addEventListener("click", function () {
                insertMention(this.textContent);
            });
        });

        // Ajuste la position
        positionDropdown();
    }

    // Insère la mention dans la textarea
    function insertMention(mention) {
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        const lastAtPos = textBefore.lastIndexOf("@");

        if (lastAtPos !== -1) {
            textarea.value = textBefore.substring(0, lastAtPos) + mention + " " + textAfter;
        }

        mentionBox.style.display = "none";
        textarea.focus();
    }

    // Gestion de la saisie dans la textarea
    textarea.addEventListener("input", function () {
        const cursorPos = textarea.selectionStart;
        const textBeforeCursor = textarea.value.substring(0, cursorPos);
        const match = textBeforeCursor.match(/@([\w]*)$/);

        if (match) {
            const query = match[1];
            fetchUsers(query);
        } else {
            mentionBox.style.display = "none";
        }
    });

    // Positionne dynamiquement la liste sous le curseur
    function positionDropdown() {
        const cursorPos = textarea.selectionStart;
        const textBeforeCursor = textarea.value.substring(0, cursorPos);
        const fakeSpan = document.createElement("span");

        // Création d'un élément invisible pour mesurer la position
        const computedStyle = window.getComputedStyle(textarea);
        fakeSpan.style.visibility = "hidden";
        fakeSpan.style.position = "absolute";
        fakeSpan.style.whiteSpace = "pre-wrap";
        fakeSpan.style.wordWrap = "break-word";
        fakeSpan.style.font = computedStyle.font;
        fakeSpan.style.padding = computedStyle.padding;
        fakeSpan.style.border = computedStyle.border;

        document.body.appendChild(fakeSpan);
        fakeSpan.textContent = textBeforeCursor.replace(/\n/g, "⏎"); // Simule les sauts de ligne

        const rect = fakeSpan.getBoundingClientRect();
        const textareaRect = textarea.getBoundingClientRect();

        mentionBox.style.left = `${rect.left}px`;
        mentionBox.style.top = `${textareaRect.top + rect.height + window.scrollY}px`;

        document.body.removeChild(fakeSpan);
    }

    // Cache la liste quand on clique ailleurs
    document.addEventListener("click", function (e) {
        if (!mentionBox.contains(e.target) && e.target !== textarea) {
            mentionBox.style.display = "none";
        }
    });

    // Ajuste la position à chaque frappe
    textarea.addEventListener("keyup", positionDropdown);
});
