document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".vote-btn").forEach(button => {
        button.addEventListener("click", function () {
            let postId = this.dataset.postId;
            let selectedOption = this.dataset.option;
            
            fetch("../common/php/vote_poll.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `post_id=${postId}&option=${encodeURIComponent(selectedOption)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    // Désactiver tous les boutons après le vote
                    document.querySelectorAll(`.vote-btn[data-post-id='${postId}']`).forEach(btn => {
                        btn.disabled = true;
                    });

                    // Mettre à jour les résultats des votes
                    let totalVotes = Object.keys(data.votes).length;
                    document.querySelectorAll(`.vote-btn[data-post-id='${postId}']`).forEach(btn => {
                        let optionText = btn.dataset.option;
                        let voteCount = Object.values(data.votes).filter(v => v === optionText).length;
                        let percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
                        btn.innerHTML = `${optionText} <span class="float-end text-muted">${voteCount} votes (${percentage}%)</span>`;
                    });
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error("Erreur:", error));
        });
    });
});
