<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop tables
$tables = [
    'mc_bot_chats', 
    'mc_bot_chat_terms', 
    'mc_bot_unreserved_queries'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
}

// Delete options
delete_option('mc_bot_default_reply');
delete_option('mc_bot_header_name');
delete_option('mc_bot_icon_url');
delete_option('mc_bot_color');
delete_option('mc_bot_custom_css');

// Clean up user meta for screen options
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'mc_bot_%'");
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '%mc_bot%per_page'");