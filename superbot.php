<?php
/*
 * Plugin Name: Super Bot
 * Plugin URI: https://wordpress.org/plugins/superbot/
 * Description: An AI-powered assistant for your WordPress site, driven by Google Gemini API, providing 24x7 smart responses to visitor queries!
 * Version: 1.0.0
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
    // Enqueue Styles
    wp_enqueue_style('mc_bot-material-icons-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0', [], null);
    wp_enqueue_style('mc_bot-material-icons-rounded', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0', [], null);
    wp_enqueue_style('mc_bot-bootstrap-icons-css', "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css", [], '1.0.0');
    wp_enqueue_style('mc_bot-bootstrap-css', plugins_url('assets/css/bootstrap.min.css', __FILE__), [], '1.0.0');
    wp_enqueue_style('mc_bot-custom-css', plugins_url('assets/css/custom.css', __FILE__), [], '1.0.0');
    wp_enqueue_style('mc_bot-datatables-css', plugins_url('assets/DataTables/datatables.min.css', __FILE__), [], '1.0.0');
    wp_enqueue_style('mc_bot-summernote-css', plugins_url('assets/summernote/summernote.css', __FILE__), [], '1.0.0');
    wp_enqueue_style('mc_bot-style-css', plugins_url('assets/css/style.css', __FILE__), [], '1.0.0');

    // Enqueue Scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('mc_bot-bootstrap-bundle-js', plugins_url('assets/js/bootstrap.bundle.min.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_script('mc_bot-chart-js', plugins_url('assets/js/chart.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_script('mc_bot-datatables-js', plugins_url('assets/DataTables/datatables.min.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_script('mc_bot-summernote-js', plugins_url('assets/summernote/summernote.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_script('mc_bot-sweetalert-js', plugins_url('assets/js/sweetalert.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_script('mc_bot-script-js', plugins_url('assets/js/script.js', __FILE__), ['jquery'], '1.0.0', true);

    // Pass AJAX URL to scripts
    wp_localize_script('mc_bot-script-js', 'mc_bot_ajax', [ 'ajax_url' => admin_url('admin-ajax.php') ]);
}
add_action('wp_enqueue_scripts', 'mc_bot_enqueue_assets');

/**
 * Plugin Activation Hook - Creates necessary tables
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

        'mc_bot_chat_history' => "(
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            query VARCHAR(200) NOT NULL,
            gemini_reply LONGTEXT NOT NULL,
            ip_address VARCHAR(50) NULL,
            location VARCHAR(200) NULL,
            date VARCHAR(50) NOT NULL,
            PRIMARY KEY (id)
        )",

        'mc_bot_chat_global_settings' => "(
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            gemini_key VARCHAR(200) NULL,
            contact_us_link VARCHAR(200) NULL,
            business_name VARCHAR(200) NULL,
            business_description VARCHAR(250) NULL,
            restriction VARCHAR(200) NULL,
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
}
register_activation_hook(__FILE__, 'mc_bot_activate');

/**
 * Add plugin menu to admin panel
 */
function mc_bot_activate_pages_register()
{
    $plugin_slug = "mc_bot_admin";
    add_menu_page('Super Bot', 'Super Bot', 'edit', $plugin_slug, null, plugins_url('icon.png', __FILE__), 58);
    add_submenu_page($plugin_slug, 'Dashboard', 'Dashboard', 'manage_options', 'chatbot_dashboard', 'dashboard_function');
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
    ?>
    <button class="chatbot-toggler">
        <span class="material-symbols-rounded">mode_comment</span>
        <span class="material-symbols-outlined">close</span>
    </button>
    <div class="chatbot">
        <header>
            <h2>SuperBot</h2>
            <span class="close-btn material-symbols-outlined">close</span>
        </header>
        <ul class="chatbox">
            <li class="chat incoming">
                <span class="material-symbols-outlined">smart_toy</span>
                <p>Hi there 👋<br>How can I help you today?</p>
            </li>
        </ul>
        <div class="chat-input">
            <textarea placeholder="Enter a message..." spellcheck="false" required></textarea>
            <span id="send-btn" class="material-symbols-rounded">send</span>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'mc_bot_chatbot_markup');


function mc_bot_admin_uninstall_alert($hook) {
    if ($hook === 'plugins.php') {
        wp_enqueue_script('mc_bot-uninstall-alert', plugins_url('assets/js/uninstall-alert.js', __FILE__), ['jquery'], '1.0.0', true);
    }
}
add_action('admin_enqueue_scripts', 'mc_bot_admin_uninstall_alert');

