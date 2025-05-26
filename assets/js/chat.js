import $ from 'jquery';

$(document).ready(function () {
    let currentReceiverId = null;

    $('.user-item').click(function () {
        const userId = $(this).data('user-id');
        $('.user-item').removeClass('active');
        $(this).addClass('active');
        currentReceiverId = userId;
        $('#receiver-id').val(userId);
        $('.message-form').show();
        loadMessages(userId);
    });

    function loadMessages(receiverId) {
        $.ajax({
            url: '/chat/conversation/' + receiverId,
            type: 'GET',
            success: function (response) {
                $('#messages-container').html(response);
                scrollToBottom();
            },
            error: function () {
                console.error('Erreur lors du chargement des messages');
            }
        });
    }

    function scrollToBottom() {
        const container = document.getElementById('messages-container');
        container.scrollTop = container.scrollHeight;
    }

    $('#send-message').click(function (e) {
        e.preventDefault();

        const content = $('#message-content').val();
        if (!content.trim()) return;

        $.ajax({
            url: '/chat/send',
            type: 'POST',
            data: {
                content: content,
                receiver: $('#receiver-id').val()
            },
            success: function () {
                $('#message-content').val('');
                loadMessages(currentReceiverId);
            },
            error: function () {
                alert('Erreur lors de l\'envoi du message');
            }
        });
    });

    // Mettre à jour les messages toutes les 3 secondes si un utilisateur est sélectionné
    setInterval(function () {
        if (currentReceiverId) {
            loadMessages(currentReceiverId);
        }
    }, 3000);
});
