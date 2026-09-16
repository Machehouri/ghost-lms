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
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({course_id: window.ghostLmsLessonPlayer.courseId}),
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

                button.textContent = window.ghostLmsLessonPlayer.completedLabel;
                document.querySelectorAll('.glms-lesson-player__lesson.is-current .glms-lesson-player__status').forEach(function (status) {
                    status.textContent = '\u2713';
                });
            })
            .catch(function () {
                button.disabled = false;
            });
    });

    // Handle attachment download via REST API
    const attachmentLink = document.querySelector('.glms-lesson-player__attachment');
    if (attachmentLink) {
        attachmentLink.addEventListener('click', function (e) {
            e.preventDefault();
            const url = attachmentLink.href;

            fetch(url)
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to get attachment URL.');
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data.url) {
                        window.location.href = data.url;
                    } else {
                        throw new Error('Invalid attachment response.');
                    }
                })
                .catch(function () {
                    alert('Failed to download attachment.');
                });
        });
    }
}());