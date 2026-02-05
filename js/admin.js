jQuery(document).ready(function($) {
    // Media uploader
    function initMediaUploader(button) {
        button.off('click').on('click', function(e) {
            e.preventDefault();
            
            var frame = wp.media({
                title: 'Select or Upload Image',
                library: {
                    type: 'image'
                },
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });

            // Set selected image
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var parent = button.closest('.image-field');
                parent.find('.image-url').val(attachment.url).trigger('change');
            });

            frame.open();
        });
    }
    
    // Initialize media uploader for existing fields
    function initializeExistingFields() {
        $('.image-field').each(function() {
            var field = $(this);
            initMediaUploader(field.find('.upload-image'));
            
            // Initialize preview for existing URLs
            var url = field.find('.image-url').val();
            if (url) {
                field.find('.image-preview').html(
                    '<img src="' + url + '" style="max-width: 200px; margin-top: 10px;" />'
                );
            }
        });
    }
    
    // Initial setup
    initializeExistingFields();
    
    // Add new image field
    $('#add-image-field').on('click', function() {
        var newField = $(
            '<div class="image-field">' +
                '<div class="image-field-header">' +
                    '<input type="text" name="le_auto_img_options[images][]" value="" class="regular-text image-url" placeholder="Image URL" />' +
                    '<button type="button" class="button upload-image">Upload Image</button>' +
                    '<button type="button" class="button button-secondary remove-image">Remove</button>' +
                '</div>' +
                '<div class="image-preview"></div>' +
            '</div>'
        );
        
        $('#image-container').append(newField);
        initMediaUploader(newField.find('.upload-image'));
        newField.find('.image-url').focus();
    });
    
    // Remove image field
    $(document).on('click', '.remove-image', function() {
        var container = $('#image-container');
        if (container.children().length > 1) {
            $(this).closest('.image-field').fadeOut(300, function() {
                $(this).remove();
            });
        } else {
            alert('You must have at least one image field.');
        }
    });
    
    // Preview image when URL changes
    $(document).on('change', '.image-url', function() {
        var url = $(this).val();
        var preview = $(this).closest('.image-field').find('.image-preview');
        
        if (url) {
            var img = new Image();
            img.onload = function() {
                preview.html('<img src="' + url + '" style="max-width: 200px; margin-top: 10px;" />');
            };
            img.onerror = function() {
                preview.html('<p style="color: red;">Invalid image URL</p>');
            };
            img.src = url;
        } else {
            preview.empty();
        }
    });
}); 