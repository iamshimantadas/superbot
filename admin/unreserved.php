<?php
if (!defined('ABSPATH')) {
    exit;
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Unreserved Queries List Table Class
 */
class MC_Bot_Unreserved_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct([
            'singular' => 'unreserved_query',
            'plural'   => 'unreserved_queries',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'    => '<input type="checkbox" />',
            'id'    => 'ID',
            'query' => 'Query',
            'date'  => 'Date'
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'   => ['id', true],
            'date' => ['date', false]
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'query':
            case 'date':
                return $item[$column_name];
            default:
                return '';
        }
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="query_ids[]" value="%s" />', $item['id']);
    }

    public function column_query($item) {
        $add_url = add_query_arg([
            'page'      => 'reply_edit_remove',
            'showModal' => 'true',
            'query'     => urlencode($item['query'])
        ], admin_url('admin.php'));

        $delete_nonce = wp_create_nonce('delete_unreserved_' . $item['id']);
        
        $actions = [
            'add'    => sprintf('<a href="%s"><span class="dashicons dashicons-plus-alt"></span> Add as Reply</a>', 
                              esc_url($add_url)),
            'delete' => sprintf('<a href="#" class="delete-unreserved" data-id="%d" data-nonce="%s"><span class="dashicons dashicons-trash"></span> Delete</a>', 
                              $item['id'], 
                              $delete_nonce)
        ];

        return sprintf('%s %s', esc_html($item['query']), $this->row_actions($actions));
    }

    public function get_bulk_actions() {
        return [
            'bulk-delete' => 'Delete'
        ];
    }

    public function prepare_items() {
        global $wpdb;

        $per_page = $this->get_items_per_page('unreserved_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $orderby = (!empty($_GET['orderby'])) ? sanitize_sql_orderby($_GET['orderby']) : 'id';
        $order = (!empty($_GET['order'])) ? sanitize_text_field($_GET['order']) : 'DESC';

        $table_name = $wpdb->prefix . 'mc_bot_unreserved_queries';
        $chats_table = $wpdb->prefix . 'mc_bot_chats';

        // Get total items (only queries not in mc_bot_chats)
        $total_items = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name 
             WHERE query NOT IN (SELECT question FROM $chats_table)"
        );

        // Get items for current page
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE query NOT IN (SELECT question FROM $chats_table)
             ORDER BY $orderby $order 
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ), ARRAY_A);

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

// Handle AJAX delete action
add_action('wp_ajax_delete_unreserved_query', 'mc_bot_delete_unreserved_query');
function mc_bot_delete_unreserved_query() {
    check_ajax_referer('delete_unreserved_' . $_POST['id'], 'nonce');
    
    global $wpdb;
    $id = intval($_POST['id']);
    
    $result = $wpdb->delete($wpdb->prefix . 'mc_bot_unreserved_queries', ['id' => $id]);
    
    if ($result) {
        wp_send_json_success('Query deleted successfully');
    } else {
        wp_send_json_error('Failed to delete query');
    }
}

// Create list table instance
$unreserved_table = new MC_Bot_Unreserved_List_Table();
$unreserved_table->prepare_items();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unreserved Queries - Super Bot</title>
    <style>
        .mc-bot-info-box {
            background: #e7f3ff;
            border-left: 4px solid #0073aa;
            padding: 12px;
            margin: 20px 0;
        }
        .mc-bot-info-box p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="mc-bot-info-box">
            <p><strong>What are Unreserved Queries?</strong></p>
            <p>These are questions from users that the bot couldn't answer. Review them and add responses to improve your bot!</p>
        </div>

        <form method="get" id="unreserved-form">
            <input type="hidden" name="page" value="unreserved_queries" />
            <?php $unreserved_table->display(); ?>
        </form>

        <?php if (empty($unreserved_table->items)): ?>
            <div class="mc-bot-info-box" style="background: #f0f0f1; border-left-color: #72aee6;">
                <p style="text-align: center; font-size: 16px;">
                    <span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size: 24px; vertical-align: middle;"></span>
                    No unreserved queries! Your bot is handling all questions perfectly.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Handle delete with AJAX
        $(document).on('click', '.delete-unreserved', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this query?')) {
                return;
            }
            
            var $link = $(this);
            var id = $link.data('id');
            var nonce = $link.data('nonce');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_unreserved_query',
                    id: id,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $link.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Reload if no items left
                            if ($('#unreserved-form tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert('Error deleting query: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error deleting query. Please try again.');
                }
            });
        });
    });
    </script>
</body>
</html>