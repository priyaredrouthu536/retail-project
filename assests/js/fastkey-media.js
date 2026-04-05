jQuery(document).ready(function ($) {

    let mediaUploader;

    $('.upload-fastkey-image').on('click', function (e) {
        e.preventDefault();
        // If already opened
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Fast Key Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function () {
            let attachment = mediaUploader.state().get('selection').first().toJSON();

            // Set image URL in input
            $('#fastkey_image').val(attachment.url);

            // Preview image
            $('#fastkey-image-preview').html(
                '<img src="' + attachment.url + '" style="max-width:150px; border:1px solid #ddd;">'
            );
        });

        mediaUploader.open();
    });

});
