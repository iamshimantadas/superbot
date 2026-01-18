<?php
if (!defined('ABSPATH')) {
    exit;
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Replies List Table Class
 */
class MC_Bot_Replies_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct([
            'singular' => 'reply',
            'plural'   => 'replies',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'       => '<input type="checkbox" />',
            'id'       => 'Chat ID',
            'question' => 'Query',
            'answer'   => 'Reply',
            'tags'     => 'Tags'
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'       => ['id', true],
            'question' => ['question', false]
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'question':
                return $item[$column_name];
            case 'answer':
                return wp_trim_words(strip_tags($item[$column_name]), 15, '...');
            case 'tags':
                return $item[$column_name];
            default:
                return '';
        }
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="chat_ids[]" value="%s" />', $item['id']);
    }

    public function column_question($item) {
        $actions = [
            'edit'   => sprintf('<a href="#" class="edit-reply" data-id="%d"><span class="dashicons dashicons-edit"></span> Edit</a>', $item['id']),
            'delete' => sprintf('<a href="#" class="delete-reply" data-id="%d" data-question="%s"><span class="dashicons dashicons-trash"></span> Delete</a>', 
                              $item['id'], 
                              esc_attr($item['question']))
        ];

        return sprintf('%s %s', esc_html($item['question']), $this->row_actions($actions));
    }

    public function get_bulk_actions() {
        return [
            'bulk-delete' => 'Delete'
        ];
    }

    public function prepare_items() {
        global $wpdb;

        $per_page = $this->get_items_per_page('replies_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $orderby = (!empty($_GET['orderby'])) ? sanitize_sql_orderby($_GET['orderby']) : 'id';
        $order = (!empty($_GET['order'])) ? sanitize_text_field($_GET['order']) : 'DESC';

        $table_name = $wpdb->prefix . 'mc_bot_chats';
        $terms_table = $wpdb->prefix . 'mc_bot_chat_terms';

        // Get total items
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // Get items for current page
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ), ARRAY_A);

        // Get tags for each item
        foreach ($items as &$item) {
            $tags = $wpdb->get_results($wpdb->prepare(
                "SELECT tag FROM $terms_table WHERE chatid = %d",
                $item['id']
            ), ARRAY_A);
            
            $tag_list = array_column($tags, 'tag');
            $item['tags'] = !empty($tag_list) ? implode(', ', $tag_list) : 'No tags';
        }

        $this->items = $items;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
    }
}

// Check if coming from unreserved queries
$from_unreserved = isset($_GET['showModal']) && $_GET['showModal'] === 'true' && isset($_GET['query']);
$unreserved_query = $from_unreserved ? sanitize_text_field($_GET['query']) : '';

// Create list table instance
$replies_table = new MC_Bot_Replies_List_Table();
$replies_table->prepare_items();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replies - Super Bot</title>
    <style>
        .mc-bot-header-actions {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal.show {
            display: block;
        }
        .modal-content {
            background-color: #fff;
            margin: 3% auto;
            padding: 0;
            border: 1px solid #888;
            width: 90%;
            max-width: 700px;
            border-radius: 5px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .modal-header h2 {
            margin: 0;
        }
        .modal-close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .modal-close:hover {
            color: #d63638;
        }
        .modal-body {
            padding: 20px;
        }
        .form-field {
            margin-bottom: 20px;
        }
        .form-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-field input,
        .form-field textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-field .description {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        .button-primary {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .button-primary:hover {
            background: #005177;
        }
        .button-secondary {
            background: #f0f0f1;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="mc-bot-header-actions">
            <button type="button" class="button button-primary" id="add-new-btn">
                <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span> Add Query
            </button>
            
            <button type="button" class="button" id="import-csv-btn">
                <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span> Import CSV
            </button>
            <input type="file" id="csv-file-input" style="display: none;" accept=".csv" />
            
            <button type="button" class="button" id="export-csv-btn">
                <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Export CSV
            </button>
        </div>

        <form method="get">
            <input type="hidden" name="page" value="reply_edit_remove" />
            <?php $replies_table->display(); ?>
        </form>
    </div>

    <!-- Add/Edit Modal -->
    <div id="chatModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Add New Query</h2>
                <span class="modal-close" id="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="chat-form">
                    <input type="hidden" id="chat-id" value="">
                    
                    <div class="form-field">
                        <label for="query">Query *</label>
                        <input type="text" id="query" required placeholder="e.g., What are your hours?">
                    </div>

                    <div class="form-field">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" id="tags" placeholder="e.g., hours, timing, schedule">
                        <p class="description">These help match user questions to this response</p>
                    </div>

                    <div class="form-field">
                        <label for="answer">Bot's Reply *</label>
                        <?php 
                        wp_editor('', 'answer', [
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true
                        ]); 
                        ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="button-primary" id="save-chat-btn">Save</button>
                <button type="button" class="button-secondary" id="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let isEditMode = false;

        // Open Add Modal
        $('#add-new-btn').click(function() {
            openAddModal();
        });

        // Edit reply
        $(document).on('click', '.edit-reply', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            openEditModal(id);
        });

        // Delete reply
        $(document).on('click', '.delete-reply', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var question = $(this).data('question');
            
            if (confirm('Are you sure you want to delete "' + question + '"?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_query',
                        chatid: id
                    },
                    success: function(response) {
                        if (response === 'success') {
                            location.reload();
                        } else {
                            alert('Error deleting query');
                        }
                    }
                });
            }
        });

        // Close modal
        $('#close-modal, #cancel-btn').click(function() {
            closeModal();
        });

        // Click outside to close
        $('#chatModal').click(function(e) {
            if (e.target.id === 'chatModal') {
                closeModal();
            }
        });

        // Save chat
        $('#save-chat-btn').click(function() {
            saveChatForm();
        });

        // Export CSV
        $('#export-csv-btn').click(function() {
            window.location.href = ajaxurl + '?action=export_csv';
        });

        // Import CSV
        $('#import-csv-btn').click(function() {
            $('#csv-file-input').click();
        });

        $('#csv-file-input').change(function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('action', 'import_csv');
            formData.append('file', file);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('CSV imported successfully!');
                        location.reload();
                    } else {
                        alert('Error importing CSV: ' + response.data);
                    }
                }
            });
        });

        function openAddModal() {
            isEditMode = false;
            $('#modal-title').text('Add New Query');
            $('#chat-id').val('');
            $('#query').val('');
            $('#tags').val('');
            
            if (typeof tinymce !== 'undefined' && tinymce.get('answer')) {
                tinymce.get('answer').setContent('');
            } else {
                $('#answer').val('');
            }
            
            $('#chatModal').addClass('show');
        }

        function openEditModal(id) {
            isEditMode = true;
            $('#modal-title').text('Edit Query');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_reply',
                    id: id
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    $('#chat-id').val(id);
                    $('#query').val(data.question);
                    $('#tags').val(data.tags.join(', '));
                    
                    if (typeof tinymce !== 'undefined' && tinymce.get('answer')) {
                        tinymce.get('answer').setContent(data.answer);
                    } else {
                        $('#answer').val(data.answer);
                    }
                    
                    $('#chatModal').addClass('show');
                }
            });
        }

        function closeModal() {
            $('#chatModal').removeClass('show');
        }

        function saveChatForm() {
            const chatId = $('#chat-id').val();
            const action = isEditMode ? 'update_query' : 'save_query';
            
            let answerContent = '';
            if (typeof tinymce !== 'undefined' && tinymce.get('answer')) {
                answerContent = tinymce.get('answer').getContent();
            } else {
                answerContent = $('#answer').val();
            }
            
            const data = {
                action: action,
                query: $('#query').val(),
                editor: answerContent,
                tags: $('#tags').val()
            };
            
            if (isEditMode) {
                data.chatid = chatId;
            }
            
            $('#save-chat-btn').prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response === 'success') {
                        closeModal();
                        location.reload();
                    } else {
                        alert('Error saving query: ' + response);
                        $('#save-chat-btn').prop('disabled', false).text('Save');
                    }
                },
                error: function() {
                    alert('Error saving query');
                    $('#save-chat-btn').prop('disabled', false).text('Save');
                }
            });
        }

        <?php if ($from_unreserved): ?>
        // Auto-open modal for unreserved query
        $(window).on('load', function() {
            openAddModal();
            $('#query').val('<?php echo esc_js($unreserved_query); ?>');
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>