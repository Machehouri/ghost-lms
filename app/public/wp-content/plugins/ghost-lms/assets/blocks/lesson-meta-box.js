(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('glms-select-attachment');
        var hiddenInput = document.getElementById('_glms_attachment_id');
        var nameDisplay = document.getElementById('glms-attachment-name');

        if (!button || !hiddenInput || !nameDisplay) {
            return;
        }

        button.addEventListener('click', function () {
            var frame = wp.media({
                title: 'Select lesson attachment',
                button: {
                    text: 'Use this file'
                },
                multiple: false,
                library: {
                    type: 'application, image, video, audio, pdf'
                }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                hiddenInput.value = attachment.id || '';
                nameDisplay.textContent = attachment.filename || attachment.name || '';
            });

            frame.open();
        });
    });
})();
