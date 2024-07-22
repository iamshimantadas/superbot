<?php
/*
 * Plugin Name:       SuperBot
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       This is a bot assistant, which also powered by Google Gemini API. It can reply against queries 24x7 to website visitors.
 * Version:           1.1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Shimanta Das
 * Author URI:        https://microcodes.in/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       superbot
 */

if (!defined('ABSPATH')) {
    die("You are restricted to access this page!");
}

// enqueue css and js scripts
function superbot_enqueue_assets()
{
    // Enqueue Google Fonts
    wp_enqueue_style('superbot-material-icons-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0', [], null);
    wp_enqueue_style('superbot-material-icons-rounded', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0', [], null);

    // Enqueue plugin styles and scripts
    wp_enqueue_style('superbot-style', plugins_url('assets/css/style.css', __FILE__), [], '1.0.0');
    wp_enqueue_script('superbot-jquery', plugins_url('assets/js/jquery.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_script('superbot-script', plugins_url('assets/js/script.js', __FILE__), ['jquery'], '1.0.0', true);

    // Pass the AJAX URL to the script
    wp_localize_script(
        'superbot-script',
        'superbot_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );
}
add_action('wp_enqueue_scripts', 'superbot_enqueue_assets');

// activation hook
function superbot_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chats';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            question VARCHAR(250) NOT NULL,
            answer LONGTEXT NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'superbot_activate');

function superbot_chatbot_markup()
{
    ?>
    <!-- floating bot icon -->
    <button class="chatbot-toggler">
        <span class="material-symbols-rounded">mode_comment</span>
        <span class="material-symbols-outlined">close</span>
    </button>
    <div class="chatbot">
        <header>
            <h2> SuperBot </h2>
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

add_action('wp_footer', 'superbot_chatbot_markup');


// ajax response handelling
function superbot_search_answer() {
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userInput = sanitize_text_field($_POST['userInput']);
        $response = "I'm sorry, I don't understand the question.";

        $table_name = $wpdb->prefix . 'chats';
        $results = $wpdb->get_results("SELECT question, answer FROM $table_name");

        if (!empty($results)) {
            foreach ($results as $row) {
                // Normalize the user input and database question
                $normalizedUserInput = preg_replace('/[^\w\s]/', '', strtolower($userInput));
                $normalizedQuestion = preg_replace('/[^\w\s]/', '', strtolower($row->question));

                if (strpos($normalizedUserInput, $normalizedQuestion) !== false) {
                    $response = $row->answer;
                    break;
                }
            }
        }

        echo $response;
        wp_die(); // This is required to terminate immediately and return a proper response
    }
}
add_action('wp_ajax_search_answer', 'superbot_search_answer');
add_action('wp_ajax_nopriv_search_answer', 'superbot_search_answer');
?>