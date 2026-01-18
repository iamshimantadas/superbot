<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Super Bot</title>
    <style>
        .mc-bot-settings-wrap {
            max-width: 900px;
            margin: 20px 0;
            background: #fff;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .mc-bot-form-row {
            margin-bottom: 25px;
        }
        .mc-bot-form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .mc-bot-form-row input[type="text"],
        .mc-bot-form-row textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .mc-bot-form-row textarea {
            min-height: 120px;
            font-family: monospace;
        }
        .mc-bot-form-row .description {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .mc-bot-submit-btn {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .mc-bot-submit-btn:hover {
            background: #005177;
        }
        .mc-bot-submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .mc-bot-success {
            background: #46b450;
            color: #fff;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }
        .mc-bot-image-preview {
            margin-top: 10px;
            max-width: 100px;
            display: none;
        }
        .mc-bot-image-preview img {
            max-width: 100%;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
        .mc-bot-upload-btn {
            background: #f0f0f1;
            border: 1px solid #ddd;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 8px;
            display: inline-block;
        }
        .mc-bot-upload-btn:hover {
            background: #ddd;
        }
        .mc-bot-color-preview {
            width: 50px;
            height: 50px;
            border: 2px solid #ddd;
            border-radius: 4px;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }
        .mc-bot-settings-section {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .mc-bot-settings-section:last-child {
            border-bottom: none;
        }
        .mc-bot-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1d2327;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="mc-bot-settings-wrap">
            <div class="mc-bot-success" id="success-message">
                Settings saved successfully!
            </div>

            <form id="mc-bot-settings-form">
                
                <!-- Appearance Settings -->
                <div class="mc-bot-settings-section">
                    <h2 class="mc-bot-section-title">Appearance Settings</h2>
                    
                    <div class="mc-bot-form-row">
                        <label for="header-name">Chatbot Header Name</label>
                        <input type="text" id="header-name" name="header_name" 
                               placeholder="e.g., SuperBot, Customer Support">
                        <p class="description">This name will appear in the chatbot header.</p>
                    </div>

                    <div class="mc-bot-form-row">
                        <label for="icon-url">Bot Icon Image URL</label>
                        <input type="text" id="icon-url" name="icon_url" 
                               placeholder="Enter image URL or upload below">
                        <button type="button" class="mc-bot-upload-btn" id="upload-icon-btn">
                            <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span> Upload Image
                        </button>
                        <p class="description">Upload a custom icon for your bot (recommended: 100x100px). Leave empty to use default icon.</p>
                        <div class="mc-bot-image-preview" id="icon-preview">
                            <img src="" alt="Icon Preview">
                        </div>
                    </div>

                    <div class="mc-bot-form-row">
                        <label for="bot-color">Chatbot Color Theme</label>
                        <input type="text" id="bot-color" name="bot_color" class="color-picker" 
                               value="#0073aa">
                        <span class="mc-bot-color-preview" id="color-preview"></span>
                        <p class="description">Choose the primary color for your chatbot interface.</p>
                    </div>
                </div>

                <!-- Response Settings -->
                <div class="mc-bot-settings-section">
                    <h2 class="mc-bot-section-title">Response Settings</h2>
                    
                    <div class="mc-bot-form-row">
                        <label for="default-reply">Default Reply (When No Answer Found)</label>
                        <?php 
                        wp_editor('', 'default-reply', [
                            'textarea_name' => 'default_reply',
                            'textarea_rows' => 8,
                            'media_buttons' => false,
                            'teeny' => true,
                            'quicktags' => true
                        ]); 
                        ?>
                        <p class="description">This message will be shown when the bot doesn't understand the user's query.</p>
                    </div>
                </div>

                <!-- Custom CSS -->
                <div class="mc-bot-settings-section">
                    <h2 class="mc-bot-section-title">Advanced Customization</h2>
                    
                    <div class="mc-bot-form-row">
                        <label for="custom-css">Custom CSS (Plugin Scope Only)</label>
                        <textarea id="custom-css" name="custom_css" rows="10" 
                                  placeholder="/* Add your custom CSS here */
.chatbot {
    /* Your styles */
}"></textarea>
                        <p class="description">Add custom CSS to style your chatbot. This CSS will only affect the chatbot plugin.</p>
                    </div>
                </div>

                <button type="button" id="save-btn" class="mc-bot-submit-btn">
                    <span class="dashicons dashicons-saved" style="vertical-align: middle;"></span> Save All Settings
                </button>
            </form>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Initialize color picker
        $('.color-picker').wpColorPicker({
            change: function(event, ui) {
                $('#color-preview').css('background-color', ui.color.toString());
            }
        });

        // Media uploader for icon
        var mediaUploader;
        $('#upload-icon-btn').click(function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Choose Bot Icon',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#icon-url').val(attachment.url);
                $('#icon-preview img').attr('src', attachment.url);
                $('#icon-preview').show();
            });
            
            mediaUploader.open();
        });

        // Show preview if icon URL exists
        $('#icon-url').on('input', function() {
            var url = $(this).val();
            if (url) {
                $('#icon-preview img').attr('src', url);
                $('#icon-preview').show();
            } else {
                $('#icon-preview').hide();
            }
        });

        // Load existing settings
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'view_settings'
            },
            success: function(response) {
                var settings = JSON.parse(response);
                
                // Set default reply in TinyMCE
                if (typeof tinymce !== 'undefined' && tinymce.get('default-reply')) {
                    tinymce.get('default-reply').setContent(settings.default_reply || '');
                } else {
                    $('#default-reply').val(settings.default_reply || '');
                }
                
                $('#header-name').val(settings.header_name || 'SuperBot');
                $('#icon-url').val(settings.icon_url || '');
                $('#bot-color').val(settings.bot_color || '#0073aa').trigger('change');
                $('#custom-css').val(settings.custom_css || '');
                
                // Update color preview
                $('#color-preview').css('background-color', settings.bot_color || '#0073aa');
                
                // Show icon preview if URL exists
                if (settings.icon_url) {
                    $('#icon-preview img').attr('src', settings.icon_url);
                    $('#icon-preview').show();
                }
            }
        });

        // Save settings
        $('#save-btn').click(function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="vertical-align: middle;"></span> Saving...');

            // Get content from TinyMCE
            var defaultReply = '';
            if (typeof tinymce !== 'undefined' && tinymce.get('default-reply')) {
                defaultReply = tinymce.get('default-reply').getContent();
            } else {
                defaultReply = $('#default-reply').val();
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_settings',
                    default_reply: defaultReply,
                    header_name: $('#header-name').val(),
                    icon_url: $('#icon-url').val(),
                    bot_color: $('#bot-color').val(),
                    custom_css: $('#custom-css').val()
                },
                success: function(response) {
                    if (response === 'success') {
                        $('#success-message').fadeIn().delay(3000).fadeOut();
                    } else {
                        alert('Error saving settings. Please try again.');
                    }
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="vertical-align: middle;"></span> Save All Settings');
                },
                error: function() {
                    alert('Error saving settings. Please try again.');
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="vertical-align: middle;"></span> Save All Settings');
                }
            });
        });
    });
    </script>
    
    <style>
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>