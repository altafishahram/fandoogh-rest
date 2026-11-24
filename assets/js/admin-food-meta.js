/**
 * Food badge image picker for the product editor (Fondogh Online Menu).
 */
(function ($) {
    'use strict';

    $(function () {
        var frame = null;

        $(document).on('click', '.rmm-badge-image-upload', function (e) {
            e.preventDefault();

            var $field = $(this).closest('.rmm-badge-image-field');

            if (typeof wp === 'undefined' || !wp.media) {
                return;
            }

            if (frame) {
                frame.off('select');
            }

            frame = wp.media({
                title: 'انتخاب تصویر نشان غذا',
                button: { text: 'استفاده از این تصویر' },
                library: { type: 'image' },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                var preview = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                $field.find('input[name="_rmm_badge_image"]').val(attachment.url);
                $field.find('.rmm-badge-image-preview').attr('src', preview).show();
                $field.find('.rmm-badge-image-remove').show();
            });

            frame.open();
        });

        $(document).on('click', '.rmm-badge-image-remove', function (e) {
            e.preventDefault();

            var $field = $(this).closest('.rmm-badge-image-field');

            $field.find('input[name="_rmm_badge_image"]').val('');
            $field.find('.rmm-badge-image-preview').attr('src', '').hide();
            $(this).hide();
        });
    });
})(jQuery);
