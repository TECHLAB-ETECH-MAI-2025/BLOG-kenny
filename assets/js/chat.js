function formatMessageDate(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    // Même jour
    if (date.toDateString() === now.toDateString()) {
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    // Hier
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return 'Hier';
    }

    // Cette semaine
    const weekDays = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    if (diff < 7 * 24 * 60 * 60 * 1000) {
        return weekDays[date.getDay()];
    }

    // Cette année
    const months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    if (date.getFullYear() === now.getFullYear()) {
        return `${date.getDate()} ${months[date.getMonth()]}`;
    }

    // Années précédentes
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

let userChooseId = null;
let userConnectedId = null;
let eventSource = null;
let chatId = null;
let lastDate = null;

$(document).ready(function () {
    console.log("ato aii")
    $('.user-item').click(function () {
        userChooseId = $(this).data('user-choose');
        $('.user-item').removeClass('active');
        $(this).addClass('active');

        $('#receiver-id').val(userChooseId);
        $('.message-form').show();
        $('.chat-header').show();

        // Mise à jour du header du chat
        const userName = $(this).find('.user-name').text();
        const userInitial = userName.charAt(0).toUpperCase();
        $('.selected-user-name').text(userName);
        $('.selected-user-avatar').show().find('.avatar-initial').text(userInitial);

        userConnectedId = $(this).data('user-connected');
        chatId = $(this).data('chat-id');

        loadMessages(userChooseId);
        subscribeToMessages(userConnectedId, userChooseId);
    });

    // Recherche d'utilisateurs
    $('.search-input').on('input', function () {
        const searchTerm = $(this).val().toLowerCase();
        $('.user-item').each(function () {
            const userName = $(this).find('.user-name').text().toLowerCase();
            $(this).toggle(userName.includes(searchTerm));
        });
    });

    function formatTime(date) {
        return date.getHours().toString().padStart(2, '0') + ':' +
            date.getMinutes().toString().padStart(2, '0');
    }

    function formatDate(date) {
        const now = new Date();
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);

        if (date.toDateString() === now.toDateString()) {
            return "Aujourd'hui";
        } else if (date.toDateString() === yesterday.toDateString()) {
            return "Hier";
        } else {
            const options = { day: 'numeric', month: 'short' };
            if (date.getFullYear() !== now.getFullYear()) {
                options.year = 'numeric';
            }
            return date.toLocaleDateString('fr-FR', options);
        }
    }

    function shouldShowNewDateSeparator(newDate) {
        if (!lastDate) return true;
        return newDate.toDateString() !== lastDate.toDateString();
    }

    function appendMessage(message, isReceived = false) {
        const messageDate = new Date(message.createdAt);
        let html = '';

        // Ajouter le séparateur de date si nécessaire
        if (shouldShowNewDateSeparator(messageDate)) {
            html += `
                <div class="date-separator">
                    <span>${formatDate(messageDate)}</span>
                </div>
            `;
            lastDate = messageDate;
        }

        // Ajouter le message
        html += `
            <div class="message ${isReceived ? 'received' : 'sent'}">
                <div class="message-content">${message.content}</div>
                <div class="message-time">${formatTime(messageDate)}</div>
            </div>
        `;

        $('#messages-container').append(html);
        scrollToBottom();
    }

    function loadMessages(receiverId) {
        $.ajax({
            url: '/chat/conversation/' + receiverId,
            type: 'GET',
            success: function (response) {
                $('#messages-container').html(response);
                scrollToBottom();

                const lastMessage = $('#messages-container .message').last();
                if (lastMessage.length) {
                    const dateStr = lastMessage.data('date');
                    if (dateStr) {
                        lastDate = new Date(dateStr);
                    }
                }
            },
            error: function () {
                console.error('Erreur lors du chargement des messages');
            }
        });
    }

    function scrollToBottom() {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    $('#send-message').click(function (e) {
        e.preventDefault();
        const content = $('#message-content').val();
        const receiver = $('#receiver-id').val();

        if (!content.trim()) {
            return;
        }

        $.ajax({
            url: '/chat/send',
            type: 'POST',
            data: {
                content: content,
                receiver: receiver,
                chat: chatId,
            },
            success: function () {
                $('#message-content').val('');
            },
            error: function () {
                console.error('Erreur lors de l\'envoi du message');
            }
        });
    });

    // Ajustement automatique de la hauteur du textarea
    $('#message-content').on('input', function () {
        this.style.height = '20px';
        this.style.height = (this.scrollHeight) + 'px';
    });

    function subscribeToMessages(userConnectedId, userChooseId) {
        if (!chatId) {
            return;
        }

        if (eventSource) {
            eventSource.close();
        }

        $.get('/chat/mercure-token', (data) => {
            const url = new URL("http://localhost:3001/.well-known/mercure");
            url.searchParams.append('topic', 'chat/' + chatId);
            url.searchParams.append('jwt', data.token);

            eventSource = new EventSource(url.toString());
            eventSource.onmessage = function (event) {
                try {
                    let data = JSON.parse(event.data);
                    data = data.data
                    if (data && data.userId) {
                        if (parseInt(data.userId) === parseInt(userChooseId) ||
                            parseInt(data.userId) === parseInt(userConnectedId)) {
                            appendMessage({
                                content: data.content,
                                createdAt: data.createdAt
                            }, parseInt(data.userId) !== parseInt(userConnectedId));
                        }
                    }
                } catch (e) {
                    console.error('Erreur de parsing du message:', e);
                }
            };

            eventSource.onerror = (error) => {
                console.error('Erreur de connexion EventSource:', error);
            };
        }).fail(() => {
            console.error('Impossible de récupérer le token Mercure');
        });
    }
});

