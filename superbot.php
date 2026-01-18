<?php
/*
 * Plugin Name: Super Bot
 * Plugin URI: https://wordpress.org/plugins/superbot/
 * Description: An AI-powered assistant for your WordPress site, providing 24x7 smart responses to visitor queries!
 * Version: 2.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Shimanta Das
 * Author URI: https://microcodes.in/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * Text Domain: super-bot
 */

if (!defined('ABSPATH')) {
    exit("You are restricted from accessing this page!");
}

require_once 'functions.php';

/**
 * Enqueue plugin assets (CSS & JS)
 */
function mc_bot_enqueue_assets()
{
    // Enqueue Styles - All local now, no CDN
    wp_enqueue_style('dashicons');
    wp_enqueue_style('mc_bot-bootstrap-css', plugins_url('assets/css/bootstrap.min.css', __FILE__), [], '2.0.0');
    wp_enqueue_style('mc_bot-custom-css', plugins_url('assets/css/custom.css', __FILE__), [], '2.0.0');
    wp_enqueue_style('mc_bot-style-css', plugins_url('assets/css/style.css', __FILE__), [], '2.0.0');
    
    // Add custom CSS from settings
    $custom_css = get_option('mc_bot_custom_css', '');
    if (!empty($custom_css)) {
        wp_add_inline_style('mc_bot-style-css', $custom_css);
    }

    // Enqueue Scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('mc_bot-bootstrap-bundle-js', plugins_url('assets/js/bootstrap.bundle.min.js', __FILE__), ['jquery'], '2.0.0', true);
    wp_enqueue_script('mc_bot-sweetalert-js', plugins_url('assets/js/sweetalert.js', __FILE__), ['jquery'], '2.0.0', true);
    wp_enqueue_script('mc_bot-script-js', plugins_url('assets/js/script.js', __FILE__), ['jquery'], '2.0.0', true);

    // Pass AJAX URL to scripts
    wp_localize_script('mc_bot-script-js', 'mc_bot_ajax', [ 'ajax_url' => admin_url('admin-ajax.php') ]);
}
add_action('wp_enqueue_scripts', 'mc_bot_enqueue_assets');

/**
 * Enqueue admin assets
 */
function mc_bot_enqueue_admin_assets($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'mc_bot') === false && strpos($hook, 'chatbot') === false && 
        strpos($hook, 'reply_edit') === false && strpos($hook, 'unreserved') === false &&
        strpos($hook, 'chat_global') === false) {
        return;
    }
    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_editor();
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'mc_bot_enqueue_admin_assets');

/**
 * Plugin Activation Hook - Creates necessary tables and default settings
 */
function mc_bot_activate()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tables = [
        'mc_bot_chats' => "(
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            question VARCHAR(250) NOT NULL,
            answer LONGTEXT NOT NULL,
            PRIMARY KEY (id)
        )",

        'mc_bot_chat_terms' => "(
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            chatid INT NOT NULL,
            tag VARCHAR(50) NOT NULL,
            PRIMARY KEY (id)
        )",

        'mc_bot_unreserved_queries' => "(
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            query VARCHAR(200) NOT NULL,
            date VARCHAR(50) NOT NULL,
            PRIMARY KEY (id)
        )"
    ];

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    foreach ($tables as $table => $schema) {
        $table_name = $wpdb->prefix . $table;
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            $sql = "CREATE TABLE $table_name $schema $charset_collate;";
            dbDelta($sql);
        }
    }

    // Add default reserved query if not exists
    $default_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mc_bot_chats WHERE question = %s",
            'hi'
        )
    );

    if (!$default_exists) {
        $wpdb->insert(
            $wpdb->prefix . 'mc_bot_chats',
            [
                'question' => 'hi',
                'answer' => 'Hello sir/madam, how may I help you?'
            ]
        );
        
        $chat_id = $wpdb->insert_id;
        
        // Add default tags
        $default_tags = ['hi', 'hello', 'hlw', 'hey', "what's up", 'whatsup'];
        foreach ($default_tags as $tag) {
            $wpdb->insert(
                $wpdb->prefix . 'mc_bot_chat_terms',
                [
                    'chatid' => $chat_id,
                    'tag' => $tag
                ]
            );
        }
    }

    // Set default options if not set
    if (get_option('mc_bot_default_reply') === false) {
        add_option('mc_bot_default_reply', "I'm sorry, I don't understand the question. Please try rephrasing or contact our support team.");
    }
    if (get_option('mc_bot_header_name') === false) {
        add_option('mc_bot_header_name', 'SuperBot');
    }
    if (get_option('mc_bot_icon_url') === false) {
        add_option('mc_bot_icon_url', '');
    }
    if (get_option('mc_bot_color') === false) {
        add_option('mc_bot_color', '#0073aa');
    }
    if (get_option('mc_bot_custom_css') === false) {
        add_option('mc_bot_custom_css', '');
    }
}
register_activation_hook(__FILE__, 'mc_bot_activate');

/**
 * Add plugin menu to admin panel
 */
function mc_bot_activate_pages_register()
{
    $plugin_slug = "mc_bot_admin";
    add_menu_page('Super Bot', 'Super Bot', 'manage_options', $plugin_slug, 'dashboard_function', 'dashicons-format-chat', 58);
    add_submenu_page($plugin_slug, 'Dashboard', 'Dashboard', 'manage_options', $plugin_slug, 'dashboard_function');
    add_submenu_page($plugin_slug, 'Add/Edit Replies', 'Add/Edit Replies', 'manage_options', 'reply_edit_remove', 'reply_function');
    add_submenu_page($plugin_slug, 'Unreserved Queries', 'Unreserved Queries', 'manage_options', 'unreserved_queries', 'unreserved_query_function');
    add_submenu_page($plugin_slug, 'Settings', 'Settings', 'manage_options', 'chat_global_settings', 'chat_global_settings_function');
}
add_action('admin_menu', 'mc_bot_activate_pages_register');

/**
 * Include Admin Pages
 */
function dashboard_function() { require plugin_dir_path(__FILE__) . 'admin/dashboard.php'; }
function reply_function() { require plugin_dir_path(__FILE__) . 'admin/replies.php'; }
function unreserved_query_function() { require plugin_dir_path(__FILE__) . 'admin/unreserved.php'; }
function chat_global_settings_function() { require plugin_dir_path(__FILE__) . 'settings.php'; }

/**
 * Enable SuperBot chat widget in footer
 */
function mc_bot_chatbot_markup()
{
    $bot_name = get_option('mc_bot_header_name', 'SuperBot');
    $bot_icon = get_option('mc_bot_icon_url', '');
    $bot_color = get_option('mc_bot_color', '#0073aa');
    
    $icon_html = !empty($bot_icon) 
        ? '<img src="' . esc_url($bot_icon) . '" alt="Bot" style="width: 24px; height: 24px; border-radius: 50%;">'
        : '<span class="dashicons dashicons-admin-users"></span>';
    
    ?>
    <style>
        .chatbot-toggler {
            background: <?php echo esc_attr($bot_color); ?> !important;
        }
        .chatbot header {
            background: <?php echo esc_attr($bot_color); ?> !important;
        }
        .chatbot .chat-input span {
            color: <?php echo esc_attr($bot_color); ?> !important;
        }
    </style>
    <button class="chatbot-toggler">
        <span class="dashicons dashicons-format-chat"></span>
        <span class="dashicons dashicons-no"></span>
    </button>
    <div class="chatbot">
        <header>
            <h2><?php echo esc_html($bot_name); ?></h2>
            <span class="close-btn dashicons dashicons-no"></span>
        </header>
        <ul class="chatbox">
            <li class="chat incoming">
                <?php echo $icon_html; ?>
                <p>Hi there 👋<br>How can I help you today?</p>
            </li>
        </ul>
        <div class="chat-input">
            <textarea placeholder="Enter a message..." spellcheck="false" required></textarea>
            <span id="send-btn" class="dashicons dashicons-arrow-up-alt2"></span>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'mc_bot_chatbot_markup');

function mc_bot_admin_uninstall_alert($hook) {
    if ($hook === 'plugins.php') {
        wp_enqueue_script('mc_bot-uninstall-alert', plugins_url('assets/js/uninstall-alert.js', __FILE__), ['jquery'], '2.0.0', true);
    }
}
add_action('admin_enqueue_scripts', 'mc_bot_admin_uninstall_alert');