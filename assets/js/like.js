document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const articleId = this.dataset.articleId;
            const liked = this.dataset.liked === 'true';
            const token = this.dataset.token;
            console.log('Bouton like cliqué pour l’article ID:', articleId);
            fetch(`/article/like/${articleId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
                .then(response => response.json())
                .then(data => {
                    const icon = this.querySelector('i');
                    const count = this.querySelector('.like-count');

                    if (data.liked) {
                        icon.className = 'fas fa-heart me-1';
                        this.dataset.liked = 'true';
                    } else {
                        icon.className = 'far fa-heart me-1';
                        this.dataset.liked = 'false';
                    }

                    count.textContent = data.count;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
        });
    });
});
