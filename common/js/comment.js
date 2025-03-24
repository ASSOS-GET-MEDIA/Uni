$(document).ready(function () {
    $(".add-comment").click(function () {
        let postId = $(this).data("post");
        let commentText = $("#comment-text-" + postId).val().trim();

        if (commentText.length === 0 || commentText.length > 500) {
            alert("Votre commentaire doit contenir entre 1 et 500 caractères.");
            return;
        }

        $.post("../common/php/comment.php", { post_id: postId, content: commentText }, function (response) {
            let data = JSON.parse(response);
            if (data.status === "success") {
                $("#comment-text-" + postId).val("");
                loadComments(postId);
            } else {
                alert(data.message);
            }
        });
    });

    function loadComments(postId) {
        $.get("../common/php/load_comments.php", { post_id: postId }, function (response) {
            let comments = JSON.parse(response);
            let commentHtml = "";
            comments.forEach(comment => {
                commentHtml += `
                    <div class="comment mb-2 p-2 border rounded">
                        <div class="d-flex align-items-center">
                            <img src="${comment.pp}" alt="PP" class="profile-pic-small">
                            <div class="ms-2">
                                <strong>${comment.username}</strong> <span class="text-muted">@${comment.at}</span>
                            </div>
                        </div>
                        <p class="mt-1">${comment.content}</p>
                        <small class="text-muted">${comment.created_at}</small>
                    </div>
                `;
            });
            $("#comments-" + postId).html(commentHtml);
        });
    }

    $(".comments-section").each(function () {
        let postId = $(this).find(".add-comment").data("post");
        if (postId) {
            loadComments(postId);
        }
    });
});