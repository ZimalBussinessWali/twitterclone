document.addEventListener('DOMContentLoaded', function () {
    // Character counter for tweet drafting
    const tweetTextarea = document.querySelector('.tweet-textarea');
    const charCounter = document.querySelector('.char-counter');
    const tweetBtn = document.querySelector('.btn-tweet');

    if (tweetTextarea) {
        tweetTextarea.addEventListener('input', function () {
            const length = this.value.length;
            charCounter.textContent = `${length}/280`;

            if (length > 280) {
                charCounter.style.color = '#e0245e';
                tweetBtn.disabled = true;
            } else {
                charCounter.style.color = '#8899a6';
                tweetBtn.disabled = length === 0;
            }
        });
    }

    // Like functionality via AJAX
    window.toggleLike = function (tweetId, btn) {
        fetch('like_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `tweet_id=${tweetId}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const countSpan = btn.querySelector('.like-count');
                    countSpan.textContent = data.likes;
                    if (data.liked) {
                        btn.classList.add('liked');
                        btn.querySelector('i').classList.replace('far', 'fas');
                    } else {
                        btn.classList.remove('liked');
                        btn.querySelector('i').classList.replace('fas', 'far');
                    }
                } else if (data.status === 'error') {
                    alert(data.message);
                }
            });
    };

    // Follow functionality via AJAX
    window.toggleFollow = function (userId, btn) {
        fetch('follow_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.following) {
                        btn.textContent = 'Following';
                        btn.classList.replace('btn-follow', 'btn-unfollow');
                    } else {
                        btn.textContent = 'Follow';
                        btn.classList.replace('btn-unfollow', 'btn-follow');
                    }
                    const followersSpan = document.getElementById('followers-count');
                    if (followersSpan) followersSpan.textContent = data.followers;
                }
            });
    };

    // Comment Modal logic
    window.showComments = function (tweetId) {
        const modal = document.getElementById('commentModal');
        const list = document.getElementById('commentsList');
        const input = document.getElementById('commentTweetId');

        input.value = tweetId;
        list.innerHTML = '<p style="padding: 20px; text-align: center;">Loading...</p>';
        modal.style.display = 'flex';

        fetch(`comment_action.php?tweet_id=${tweetId}`)
            .then(response => response.json())
            .then(data => {
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p style="padding: 20px; text-align: center; color: #8899a6;">No comments yet.</p>';
                } else {
                    data.forEach(comment => {
                        list.innerHTML += `
                        <div class="comment-item" style="padding: 10px; border-bottom: 1px solid #38444d;">
                            <div style="font-weight: bold;">@${comment.username}</div>
                            <div>${comment.comment_text}</div>
                            <div style="font-size: 0.8rem; color: #8899a6;">${comment.created_at}</div>
                        </div>
                    `;
                    });
                }
            });
    };

    window.closeModal = function () {
        document.getElementById('commentModal').style.display = 'none';
    };

    window.submitComment = function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch('comment_action.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    form.reset();
                    showComments(formData.get('tweet_id'));
                }
            });
    };

    // Edit Tweet Modal
    window.showEditModal = function (tweetId, content) {
        const modal = document.getElementById('editModal');
        const textarea = document.getElementById('editTweetContent');
        const input = document.getElementById('editTweetId');

        input.value = tweetId;
        textarea.value = content;
        modal.style.display = 'flex';
    };

    window.closeEditModal = function () {
        document.getElementById('editModal').style.display = 'none';
    };

    // Delete tweet
    window.deleteTweet = function (tweetId) {
        if (confirm('Delete this tweet?')) {
            fetch('tweet_action.php?delete=' + tweetId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('tweet-' + tweetId).remove();
                    }
                });
        }
    };

    // Smooth scroll animations
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.tweet-card').forEach(card => observer.observe(card));
});
