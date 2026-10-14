document.addEventListener("DOMContentLoaded", () => {
    const postForm = document.getElementById("postForm");
    const commentForm = document.getElementById("commentForm");

    if (postForm) {
        postForm.addEventListener("submit", (e) => {
            const title = postForm.querySelector("input[name='title']").value.trim();
            const content = postForm.querySelector("textarea[name='content']").value.trim();

            if (title.length < 3 || content.length < 10) {
                e.preventDefault();
                alert("Your post title or content is too short!");
            }
        });
    }

    if (commentForm) {
        commentForm.addEventListener("submit", (e) => {
            const comment = commentForm.querySelector("textarea[name='comment']").value.trim();
            if (comment.length < 3) {
                e.preventDefault();
                alert("Comment must be at least 3 characters long!");
            }
        });
    }
});
