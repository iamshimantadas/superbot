<?php

/**
 * Find important words by removing stop words
 */
function extractImportantWords($sentence) {
    $stopWords = [
        'i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you', 'your', 'yours', 'yourself', 'yourselves',
        'he', 'him', 'his', 'himself', 'she', 'her', 'hers', 'herself', 'it', 'its', 'itself', 'they', 'them', 'their',
        'theirs', 'themselves', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'is', 'are',
        'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'having', 'do', 'does', 'did', 'doing', 'a', 'an',
        'the', 'and', 'but', 'if', 'or', 'because', 'as', 'until', 'while', 'of', 'at', 'by', 'for', 'with', 'about',
        'against', 'between', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up',
        'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when',
        'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor',
        'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don', 'should', 'now'
    ];

    $words = preg_split('/\s+/', strtolower($sentence));
    $words = array_map(function ($word) {
        return preg_replace('/[^\w]/', '', $word);
    }, $words);

    $importantWords = array_filter($words, function ($word) use ($stopWords) {
        return !in_array($word, $stopWords) && !empty($word);
    });

    return array_values($importantWords);
}

/**
 * AJAX response handling - Improved search logic
 */
function mc_bot_search_answer() {
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userInput = sanitize_text_field($_POST['userInput']);

        // Check string length
        if (strlen($userInput) > 180) {
            echo "Your query length is too long! Please reduce it.";
            wp_die();
        }

        $response = '';
        $found = false;
        $table_name = $wpdb->prefix . 'mc_bot_chats';
        $terms_table = $wpdb->prefix . 'mc_bot_chat_terms';

        // Step 1: Search exact string in question field
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT answer FROM $table_name WHERE question = %s LIMIT 1",
            strtolower($userInput)
        ));

        if ($result) {
            $response = $result->answer;
            $found = true;
        }

        // Step 2: Search tags in query (exact match in tags)
        if (!$found) {
            $importantWords = extractImportantWords($userInput);
            
            foreach ($importantWords as $word) {
                $result = $wpdb->get_row($wpdb->prepare(
                    "SELECT chatid FROM $terms_table WHERE tag = %s LIMIT 1",
                    $word
                ));

                if ($result) {
                    $chat_result = $wpdb->get_row($wpdb->prepare(
                        "SELECT answer FROM $table_name WHERE id = %d",
                        $result->chatid
                    ));
                    
                    if ($chat_result) {
                        $response = $chat_result->answer;
                        $found = true;
                        break;
                    }
                }
            }
        }

        // Step 3: Search substring LIKE in question
        if (!$found) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT answer FROM $table_name WHERE question LIKE %s LIMIT 1",
                '%' . $wpdb->esc_like(strtolower($userInput)) . '%'
            ));

            if ($result) {
                $response = $result->answer;
                $found = true;
            }
        }

        // Step 4: Search substring LIKE in tags
        if (!$found) {
            $importantWords = extractImportantWords($userInput);
            
            foreach ($importantWords as $word) {
                $result = $wpdb->get_row($wpdb->prepare(
                    "SELECT chatid FROM $terms_table WHERE tag LIKE %s LIMIT 1",
                    '%' . $wpdb->esc_like($word) . '%'
                ));

                if ($result) {
                    $chat_result = $wpdb->get_row($wpdb->prepare(
                        "SELECT answer FROM $table_name WHERE id = %d",
                        $result->chatid
                    ));
                    
                    if ($chat_result) {
                        $response = $chat_result->answer;
                        $found = true;
                        break;
                    }
                }
            }
        }

        // If nothing found, use default reply and log as unreserved
        if (!$found) {
            $response = get_option('mc_bot_default_reply', "I'm sorry, I don't understand the question.");
            
            // Log unreserved query
            $unreserved_table = $wpdb->prefix . "mc_bot_unreserved_queries";
            $wpdb->insert(
                $unreserved_table,
                [
                    "query" => $userInput,
                    "date" => date("Y/m/d"),
                ]
            );
        }

        echo $response;
        wp_die();
    }
}
add_action('wp_ajax_search_answer', 'mc_bot_search_answer');
add_action('wp_ajax_nopriv_search_answer', 'mc_bot_search_answer');

/**
 * Hook - save_query
 */
add_action('wp_ajax_save_query', 'mc_bot_saveQuery');
function mc_bot_saveQuery() {
    global $wpdb;

    $query = sanitize_text_field($_POST['query']);
    $html = wp_kses_post($_POST['editor']);
    $tags = isset($_POST['tags']) && !empty($_POST['tags']) ? explode(',', sanitize_text_field($_POST['tags'])) : [];

    try {
        $table = $wpdb->prefix . "mc_bot_chats";
        $result = $wpdb->insert(
            $table,
            [
                "question" => strtolower($query),
                "answer" => $html,
            ]
        );
        
        if ($result === false) {
            echo "error: Database insert failed";
            exit;
        }
        
        $id = $wpdb->insert_id;

        if (!empty($tags) && !empty($tags[0])) {
            foreach ($tags as $tag) {
                $tag = strtolower(trim($tag));
                if (!empty($tag)) {
                    $wpdb->insert(
                        $wpdb->prefix . "mc_bot_chat_terms",
                        [
                            "chatid" => $id,
                            "tag" => $tag,
                        ]
                    );
                }
            }
        }

        echo "success";
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
    }
    
    exit;
}

/**
 * Hook - get_reply
 */
add_action('wp_ajax_get_reply', 'mc_bot_getQuery');
function mc_bot_getQuery() {
    global $wpdb;

    $id = intval($_POST['id']);
    $table = $wpdb->prefix . "mc_bot_chats";

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

    if ($result) {
        $tags_table = $wpdb->prefix . "mc_bot_chat_terms";
        $tags = $wpdb->get_results($wpdb->prepare("SELECT tag FROM $tags_table WHERE chatid = %d", $id), ARRAY_A);
        $tags = array_column($tags, 'tag');

        echo json_encode([
            'question' => $result->question,
            'answer' => $result->answer,
            'tags' => $tags
        ]);
    } else {
        echo json_encode(['error' => 'No record found']);
    }

    exit;
}

/**
 * Hook - update_query
 */
add_action('wp_ajax_update_query', 'mc_bot_updateQuery');
function mc_bot_updateQuery() {
    global $wpdb;

    $query = sanitize_text_field($_POST['query']);
    $html = wp_kses_post($_POST['editor']);
    $chatid = intval($_POST['chatid']);
    $tags = isset($_POST['tags']) && !empty($_POST['tags']) ? explode(',', sanitize_text_field($_POST['tags'])) : [];

    try {
        $table = $wpdb->prefix . "mc_bot_chats";
        $wpdb->update(
            $table,
            [
                "question" => strtolower($query),
                "answer" => $html,
            ],
            ['id' => $chatid]
        );

        // Delete old tags
        $terms_table = $wpdb->prefix . "mc_bot_chat_terms";
        $wpdb->delete($terms_table, ['chatid' => $chatid]);

        // Insert new tags
        if (!empty($tags) && !empty($tags[0])) {
            foreach ($tags as $tag) {
                $tag = strtolower(trim($tag));
                if (!empty($tag)) {
                    $wpdb->insert(
                        $terms_table,
                        [
                            "chatid" => $chatid,
                            "tag" => $tag,
                        ]
                    );
                }
            }
        }

        echo "success";
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
    }

    exit;
}

/**
 * Hook - delete_query
 */
add_action('wp_ajax_delete_query', 'mc_bot_deleteQuery');
function mc_bot_deleteQuery() {
    global $wpdb;
    $id = intval($_POST['chatid']);

    try {
        $table = $wpdb->prefix . "mc_bot_chats";
        $wpdb->delete($table, ['id' => $id]);

        $table = $wpdb->prefix . "mc_bot_chat_terms";
        $wpdb->delete($table, ['chatid' => $id]);

        echo "success";
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    exit;
}

/**
 * Hook - save_settings
 */
add_action('wp_ajax_save_settings', 'mc_bot_chatSettings');
function mc_bot_chatSettings() {
    $default_reply = wp_kses_post($_POST['default_reply']);
    $header_name = sanitize_text_field($_POST['header_name']);
    $icon_url = esc_url_raw($_POST['icon_url']);
    $bot_color = sanitize_hex_color($_POST['bot_color']);
    $custom_css = wp_strip_all_tags($_POST['custom_css']);

    update_option('mc_bot_default_reply', $default_reply);
    update_option('mc_bot_header_name', $header_name);
    update_option('mc_bot_icon_url', $icon_url);
    update_option('mc_bot_color', $bot_color);
    update_option('mc_bot_custom_css', $custom_css);

    echo "success";
    exit;
}

/**
 * Hook - view_settings
 */
add_action('wp_ajax_view_settings', 'mc_bot_viewSettings');
function mc_bot_viewSettings() {
    $settings = [
        'default_reply' => get_option('mc_bot_default_reply', ''),
        'header_name' => get_option('mc_bot_header_name', 'SuperBot'),
        'icon_url' => get_option('mc_bot_icon_url', ''),
        'bot_color' => get_option('mc_bot_color', '#0073aa'),
        'custom_css' => get_option('mc_bot_custom_css', '')
    ];
    
    echo json_encode($settings);
    exit;
}

/**
 * Hook - export_csv
 */
add_action('wp_ajax_export_csv', 'exportCSV');
function exportCSV() {
    global $wpdb;
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=chatbot_data.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['id', 'question', 'answer']);
    
    $table = $wpdb->prefix . "mc_bot_chats";
    $rows = $wpdb->get_results("SELECT id, question, answer FROM $table", ARRAY_A);
    
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Hook - import_csv
 */
add_action('wp_ajax_import_csv', 'importCSV');
function importCSV() {
    global $wpdb;
    $table = $wpdb->prefix . "mc_bot_chats";

    if (isset($_FILES['file'])) {
        $file = $_FILES['file']['tmp_name'];

        if (($handle = fopen($file, 'r')) !== false) {
            fgetcsv($handle); // Skip header

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $wpdb->insert(
                    $table,
                    [
                        'question' => $data[1],
                        'answer' => $data[2],
                    ],
                    ['%s', '%s']
                );
            }

            fclose($handle);
            wp_send_json_success('CSV imported successfully!');
        } else {
            wp_send_json_error('Failed to open the file.');
        }
    } else {
        wp_send_json_error('No file uploaded.');
    }

    exit();
}