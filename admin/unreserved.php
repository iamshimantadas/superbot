<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unreserved Queries</title>

    <?php wp_head(); ?>

</head>

<body>

    <div class="row">
        <div class="col-1"></div>
        <div class="col-10">


            <div class="row">
                <br>
                <br>


                <?php
                global $wpdb;
                $history_table_name = $wpdb->prefix . 'mc_bot_chat_history';
                $chats_table_name = $wpdb->prefix . 'mc_bot_chats';

                // Fetch recent chats
                $results = $wpdb->get_results("SELECT query, gemini_reply FROM $history_table_name WHERE gemini_reply <> ''
                AND query NOT IN (
                        SELECT question FROM $chats_table_name
                    )
                ORDER BY id DESC");

                if ($results && count($results) > 0) {
                    echo '<table class="table" id="myTable">
                    <thead>
                        <tr>
                            <th scope="col">Query</th>
                            <th scope="col">Gemini A.I Reply</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>';

                    foreach ($results as $row) {
                        $gemini_reply = substr($row->gemini_reply, 0, 100);
                        $url = admin_url('admin.php?page=reply_edit_remove&showModal=true&query=' . $row->query);

                        echo "<tr>
                        <td>{$row->query}</td>
                        <td>{$gemini_reply}</td>
                        <td>
                            <a href='{$url}'>
                                <button type='button' class='btn btn-warning'>Add</button>
                            </a>
                        </td>
                        </tr>";
                    }

                    echo '</tbody></table>';
                } else {
                    echo "<br/><br/> <div class='text-center'>No chats available</div>";
                }
                ?>


            </div>



        </div>
        <div class="col-1"></div>
    </div>

    <?php wp_footer(); ?>

    <script>
        jQuery(document).ready(function () {
            jQuery('#myTable').DataTable();
        });
    </script>

</body>

</html>