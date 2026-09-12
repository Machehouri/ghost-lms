(function () {
    'use strict';

    const button = document.querySelector('.glms-lesson-player__complete');
    if (!button || !window.ghostLmsLessonPlayer) {
        return;
    }

    button.addEventListener('click', function () {
        button.disabled = true;

        fetch(window.ghostLmsLessonPlayer.completeUrl, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': window.ghostLmsLessonPlayer.nonce,
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Lesson completion failed.');
                }

                return response.json();
            })
            .then(function (data) {
                if (!data.completed) {
                    throw new Error('Lesson completion failed.');
                }

                button.textContent = 'Completed';
                document.querySelectorAll('.glms-lesson-player__lesson.is-current .glms-lesson-player__status').forEach(function (status) {
                    status.textContent = '\u2713';
                });
            })
            .catch(function () {
                button.disabled = false;
            });
    });
}());