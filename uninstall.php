<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$tables = ['mc_bot_chats', 'mc_bot_chat_terms', 'mc_bot_chat_history', 'mc_bot_chat_global_settings'];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
}
