<?php

// Find important words
function extractImportantWords($sentence) {
    // Define a list of common stop words
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

    // Convert the sentence to lowercase and split into words
    $words = preg_split('/\s+/', strtolower($sentence));

    // Remove any non-word characters
    $words = array_map(function ($word) {
        return preg_replace('/[^\w]/', '', $word);
    }, $words);

    // Filter out stop words
    $importantWords = array_filter($words, function ($word) use ($stopWords) {
        return !in_array($word, $stopWords) && !empty($word);
    });

    // Return the important words
    return array_values($importantWords);
}

// AJAX response handling
function mc_bot_search_answer() {
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userInput = sanitize_text_field($_POST['userInput']);

        // Check string length first
        if (strlen($userInput) > 180) {
            echo $response = "Your query length is very big! reduce it!";
            exit;
        }

        $response = "I'm sorry, I don't understand the question.";
        $table_name = $wpdb->prefix . 'mc_bot_chats';

        // Prepare the SQL query
        $sql = $wpdb->prepare(
            "SELECT answer FROM $table_name WHERE question REGEXP %s LIMIT 1",
            $userInput
        );

        // Execute the query
        $result = $wpdb->get_row($sql);

        if ($result) {
            $response = $result->answer;

            /** STORING THE USER'S QUERY */
            try {
                $ip = getenv('REMOTE_ADDR');

                // Check if the IP is localhost
                if ($ip === '127.0.0.1' || $ip === '::1') {
                    $location = "Localhost";
                } else {
                    $url = "https://freeipapi.com/api/json/$ip";
                    $data = file_get_contents($url);
                    $data = json_decode($data, true);

                    if (is_array($data) && isset($data['countryName'])) {
                        $countryName = $data['countryName'];
                        $cityName = $data['cityName'];
                        $regionName = $data['regionName'];
                        $zipCode = $data['zipCode'];

                        // Create the response string
                        $location = "Country: $countryName, City: $cityName, Region: $regionName, Zip Code: $zipCode";
                    } else {
                        $location = NULL;
                    }
                }
            } catch (Exception $e) {
                $location = NULL;
                $ip = NULL;
                error_log($e->getMessage());
            }
            /** END OF USER'S QUERY */

            $table = $wpdb->prefix . "mc_bot_chat_history";
            $wpdb->insert(
                $table,
                array(
                    "query" => $userInput,
                    "date" => date("Y/m/d"),
                    "ip_address" => $ip,
                    "location" => $location,
                )
            );
        } else {
            $table_name = $wpdb->prefix . 'mc_bot_chats';
            $importantWords = extractImportantWords($userInput);
            $i = 0;
            $count = 0;
            
            while ($i < sizeof($importantWords)) {
                $sql = $wpdb->prepare(
                    "SELECT answer FROM $table_name WHERE question = %s LIMIT 1",
                    $importantWords[$i]
                );
                $result = $wpdb->get_row($sql);
                if ($result) {
                    $count++;
                    $response = $result->answer;
                }
                $i++;
            }

            if ($count == 0) {
                $table_name = $wpdb->prefix . 'mc_bot_chat_terms';
                $importantWords = extractImportantWords($userInput);
                $i = 0;
                while ($i < sizeof($importantWords)) {
                    $sql = $wpdb->prepare(
                        "SELECT chatid FROM $table_name WHERE tag='%s'",
                        $importantWords[$i]
                    );
                    $result = $wpdb->get_row($sql);
                    if ($result) {
                        $chat_id = $result->chatid;
                    }
                    $i++;
                }

                // Getting answer
                if ($chat_id) {
                    $count = 0;
                    $table_name = $wpdb->prefix . 'mc_bot_chats';
                    $result = $wpdb->get_row($wpdb->prepare("SELECT answer FROM $table_name WHERE id = %d", $chat_id));
                    if ($result) {
                        $response = $result->answer;
                    }

                    try {
                        $ip = getenv('REMOTE_ADDR');
                        if ($ip === '127.0.0.1' || $ip === '::1') {
                            $location = "Localhost";
                        } else {
                            $url = "https://freeipapi.com/api/json/$ip";
                            $data = file_get_contents($url);
                            $data = json_decode($data, true);

                            if (is_array($data) && isset($data['countryName'])) {
                                $countryName = $data['countryName'];
                                $cityName = $data['cityName'];
                                $regionName = $data['regionName'];
                                $zipCode = $data['zipCode'];

                                // Create the response string
                                $location = "Country: $countryName, City: $cityName, Region: $regionName, Zip Code: $zipCode";
                            } else {
                                $location = NULL;
                            }
                        }
                    } catch (Exception $e) {
                        $location = NULL;
                        $ip = NULL;
                        error_log($e->getMessage());
                    }

                    $table = $wpdb->prefix . "mc_bot_chat_history";
                    $wpdb->insert(
                        $table,
                        array(
                            "query" => $userInput,
                            "date" => date("Y/m/d"),
                            "ip_address" => $ip,
                            "location" => $location,
                        )
                    );
                } else {
                    /** NO CHAT TERMS FOUND */
                    $count = 0;

                    try {
                        $table = $wpdb->prefix . "mc_bot_chat_global_settings";
                        $arr = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
                
                        if (empty($arr)) {
                            return "Settings not found."; // Handle case where settings are missing
                        }
                
                        $apiKey = $arr[0]->gemini_key;
                        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
                        $business_name = $arr[0]->business_name;
                        $business_description = $arr[0]->business_description;
                        $common = "you should reply as for $business_name, $business_description";
                        $restrictions = $arr[0]->restriction;
                        $contact_page = $arr[0]->contact_us_link;
                
                        $data = json_encode([
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => "$userInput, $common, $restrictions"
                                        ]
                                    ]
                                ]
                            ]
                        ]);
                
                        $ch = curl_init($apiUrl . '?key=' . $apiKey);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                
                        $response = curl_exec($ch);
                        curl_close($ch);
                
                        $responseArray = json_decode($response, true);
                
                        if (is_array($responseArray) && isset($responseArray['error'])) {
                            // Handle API error
                            if ($contact_page) {
                                $response = "Feel free to <b><a href='" . esc_url($contact_page) . "' target='_blank'>contact us</a></b>! We will contact you shortly.";
                            } else {
                                $response = "We can't understand your query! Please correct your query!";
                            }
                        } elseif (isset($responseArray['candidates'][0]['content']['parts'][0]['text'])) {
                            $response = $responseArray['candidates'][0]['content']['parts'][0]['text'];
                            $response = preg_replace('/\*\*|\*/', '', $response);
                
                            try {
                                $ip = $_SERVER['REMOTE_ADDR']; // Use $_SERVER
                                $location = null;
                
                                if ($ip === '127.0.0.1' || $ip === '::1') {
                                    $location = "Localhost";
                                } else {
                                    $url = "https://freeipapi.com/api/json/$ip";
                                    $data = file_get_contents($url);
                                    $data = json_decode($data, true);
                
                                    if (is_array($data) && isset($data['countryName'])) {
                                        $location = "Country: " . $data['countryName'] . ", City: " . $data['cityName'] . ", Region: " . $data['regionName'] . ", Zip Code: " . $data['zipCode'];
                                    }
                                }
                
                                $table = $wpdb->prefix . "mc_bot_chat_history";
                                $wpdb->insert(
                                    $table,
                                    [
                                        "query" => $userInput,
                                        "date" => date("Y/m/d"),
                                        "gemini_reply" => $response,
                                        "ip_address" => $ip,
                                        "location" => $location,
                                    ]
                                );
                            } catch (Exception $e) {
                                error_log($e->getMessage());
                            }
                        } else {
                            $response = "An unexpected error occurred.";
                        }
                
                        echo $response;
                    } catch (Exception $e) {
                        return "An error occurred: " . $e->getMessage();
                    }
                }
            }
        }

        echo $response;
        wp_die();
    }
}
add_action('wp_ajax_search_answer', 'mc_bot_search_answer');
add_action('wp_ajax_nopriv_search_answer', 'mc_bot_search_answer');

/**
 * Hook - save_query helps to save query, response and tags, inside page=reply_edit_remove
 */
add_action('wp_ajax_save_query', 'mc_bot_saveQuery');
add_action('wp_ajax_nopriv_save_query', 'mc_bot_saveQuery');
function mc_bot_saveQuery() {
    global $wpdb;

    $query = sanitize_text_field($_POST['query']);
    $html = $_POST['editor'];
    $html = str_replace('\\', '', $html);
    $tags = explode(',', sanitize_text_field($_POST['tags']));

    try {
        $table = $wpdb->prefix . "mc_bot_chats";
        $wpdb->insert(
            $table,
            array(
                "question" => $query,
                "answer" => $html,
            )
        );
        // Getting inserted record last id
        $id = $wpdb->insert_id;

        if ($_POST['tags']) {
            $i = 0;
            while ($i < sizeof($tags)) {
                $tag = trim($tags[$i]);
                $tag = str_replace('\\', '', $tag);
                $table = $wpdb->prefix . "mc_bot_chat_terms";
                $wpdb->insert(
                    $table,
                    array(
                        "chatid" => $id,
                        "tag" => $tag,
                    )
                );
                $i++;
            }
        }

        $res = true;
    } catch (Exception $e) {
        $res = false;
        echo $e->getMessage();
    }

    if ($res) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
}

/**
 * Hook - get_reply helps to fetch summernote editor data and query and tags data in update form, inside page=reply_edit_remove
 */
add_action('wp_ajax_get_reply', 'mc_bot_getQuery');
add_action('wp_ajax_nopriv_get_reply', 'mc_bot_getQuery');
function mc_bot_getQuery() {
    global $wpdb;

    $id = sanitize_text_field($_POST['id']);
    $table = $wpdb->prefix . "mc_bot_chats";

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

    if ($result) {
        $tags_table = $wpdb->prefix . "mc_bot_chat_terms";
        $tags = $wpdb->get_results($wpdb->prepare("SELECT tag FROM $tags_table WHERE chatid = %d", $id), ARRAY_A);
        $tags = array_column($tags, 'tag');

        echo json_encode(
            array(
                'question' => $result->question,
                'answer' => $result->answer,
                'tags' => $tags
            )
        );
    } else {
        echo json_encode(array('error' => 'No record found'));
    }

    exit;
}

/**
 * Hook - update_query helps to update query, inside page=reply_edit_remove
 */
add_action('wp_ajax_update_query', 'mc_bot_updateQuery');
add_action('wp_ajax_nopriv_update_query', 'mc_bot_updateQuery');
function mc_bot_updateQuery() {
    global $wpdb;

    $query = sanitize_text_field($_POST['query']);
    $html = $_POST['editor'];
    $html = str_replace('\\', '', $html);
    $chatid = sanitize_text_field($_POST['chatid']);
    $tags = explode(',', sanitize_text_field($_POST['tags']));

    try {
        $table = $wpdb->prefix . "mc_bot_chats";
        $wpdb->update(
            $table,
            array(
                "question" => $query,
                "answer" => $html,
            ),
            array('id' => $chatid),
        );
        // Getting inserted record last id
        $id = $chatid;

        /**
         * Chat id can't be deleted, but chat terms should be deleted!
         * When deletion 3 conditions - update new tags, remove existing tags, insert new tags
         */
        if ($_POST['tags']) {
            // Tags update 
            $table = $wpdb->prefix . "mc_bot_chat_terms";
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE chatid = %d",
                $chatid
            );
            $num_records = $wpdb->get_var($query);
            if ($num_records != 0) {
                // Deleting old ids of chat terms -> tags
                $i = 0;
                while ($i < sizeof($tags)) {
                    $table = $wpdb->prefix . "mc_bot_chat_terms";
                    $wpdb->delete($table, array('chatid' => $id));
                    $i++;
                }
            }

            // Inserting new ids of chat terms -> tags
            $i = 0;
            while ($i < sizeof($tags)) {
                $tag = trim($tags[$i]);
                $tag = str_replace('\\', '', $tag);
                $table = $wpdb->prefix . "mc_bot_chat_terms";
                $wpdb->insert(
                    $table,
                    array(
                        "chatid" => $id,
                        "tag" => $tag,
                    )
                );
                $i++;
            }
        } else {
            $table = $wpdb->prefix . "mc_bot_chat_terms";
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE chatid = %d",
                $chatid
            );

            // If records -> means no records and if at least one then delete operation start!
            $num_records = $wpdb->get_var($query);

            if ($num_records != 0) {
                // Deleting old ids of chat terms -> tags
                $i = 0;
                while ($i < sizeof($tags)) {
                    $table = $wpdb->prefix . "mc_bot_chat_terms";
                    $wpdb->delete($table, array('chatid' => $id));
                    $i++;
                }
            }
        }

        $res = true;
    } catch (Exception $e) {
        $res = false;
        echo $e->getMessage();
    }

    if ($res) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
}

/**
 * Hook - delete_query helps to delete query form, inside page=reply_edit_remove
 */
add_action('wp_ajax_delete_query', 'mc_bot_deleteQuery');
add_action('wp_ajax_nopriv_delete_query', 'mc_bot_deleteQuery');
function mc_bot_deleteQuery() {
    global $wpdb;
    $id = sanitize_text_field($_POST['chatid']);

    try {
        $table = $wpdb->prefix . "mc_bot_chats";
        $wpdb->delete($table, array('id' => $id));

        $table = $wpdb->prefix . "mc_bot_chat_terms";
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE chatid = %d",
            $id
        );
        $count = $wpdb->get_var($query);
        if ($count != 0) {
            $i = 0;
            while ($i < $count) {
                $table = $wpdb->prefix . "mc_bot_chat_terms";
                $wpdb->delete($table, array('chatid' => $id));
                $i++;
            }
        }

        echo "success";
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    exit;
}

/**
 * Hook - save_settings helps to save chat global settings into db.
 */
add_action('wp_ajax_save_settings', 'mc_bot_chatSettings');
add_action('wp_ajax_nopriv_save_settings', 'mc_bot_chatSettings');
function mc_bot_chatSettings() {
    global $wpdb;

    try {
        $key = sanitize_text_field($_POST['key']);
        $contact = str_replace('\\', '', $_POST['contact']);
        $name = str_replace('\\', '', $_POST['business_name']);
        $description = str_replace('\\', '', $_POST['description']);
        $restriction = str_replace('\\', '', $_POST['restriction']);

        $table = $wpdb->prefix . "mc_bot_chat_global_settings";
        $wpdb->insert(
            $table,
            array(
                "gemini_key" => $key,
                "contact_us_link" => $contact,
                "business_name" => $name,
                "business_description" => $description,
                "restriction" => $restriction,
            )
        );
        echo "success";
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    exit;
}

// Hook - view_settings helps to display settings value inside settings form.
add_action('wp_ajax_view_settings', 'mc_bot_viewSettings');
add_action('wp_ajax_nopriv_view_settings', 'mc_bot_viewSettings');
function mc_bot_viewSettings() {
    global $wpdb;
    $table = $wpdb->prefix . "mc_bot_chat_global_settings";
    $arr = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
    echo json_encode($arr, true);
    exit;
}

// Hook - export_csv helps to export table data in csv format
add_action('wp_ajax_export_csv', 'exportCSV');
add_action('wp_ajax_nopriv_export_csv', 'exportCSV');
function exportCSV() {
    global $wpdb;
    // Set headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data.csv');
    $output = fopen('php://output', 'w');

    // Output column headers
    fputcsv($output, array('id', 'question', 'answer'));
    $table = $wpdb->prefix . "mc_bot_chats";
    $rows = $wpdb->get_results("SELECT id, question, answer FROM $table", ARRAY_A);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);

    exit();
}

add_action('wp_ajax_import_csv', 'importCSV');
add_action('wp_ajax_nopriv_import_csv', 'importCSV');
function importCSV() {
    global $wpdb;
    $table = $wpdb->prefix . "mc_bot_chats";

    // Check if a file was uploaded
    if (isset($_FILES['file'])) {
        $file = $_FILES['file']['tmp_name'];

        // Open the uploaded CSV file
        if (($handle = fopen($file, 'r')) !== false) {
            global $wpdb;

            // Skip the first line (header row)
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $wpdb->insert(
                    $table,
                    array(
                        'question' => $data[1], // Assuming 'question' is in the 2nd column
                        'answer' => $data[2],   // Assuming 'answer' is in the 3rd column
                    ),
                    array('%s', '%s')
                );
            }

            // Close the file handle
            fclose($handle);

            // Return success response
            wp_send_json_success('CSV imported successfully!');
        } else {
            wp_send_json_error('Failed to open the file.');
        }
    } else {
        wp_send_json_error('No file uploaded.');
    }

    exit();
}