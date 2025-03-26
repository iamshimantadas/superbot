<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replies</title>

    <?php wp_head(); ?>
    <script>
        const REDIRECT_PAGE = "<?php echo admin_url('admin.php?page=reply_edit_remove'); ?>";
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    </script>
</head>

<body>

    <br>

    <div class="row">
        <div class="col-3">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                Add Query
            </button>
        </div>
        <div class="col-3"></div>
        <div class="col-3">

            <!-- Bootstrap Import CSV Button -->
            <button id="import-csv-btn" class="btn btn-primary">Import CSV</button>

            <!-- File Input (Hidden) -->
            <input type="file" id="csv-file-input" style="display: none;" accept=".csv" />

        </div>
        <div class="col-3">
            <button type="button" id="export-csv-btn" class="btn btn-warning">
                Export Chats
            </button>
        </div>
    </div>


    <!-- Modal of add query -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <br>
        <br>
        <br>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Bot's Query & Reply</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form>
                        <div class="mb-3">
                            <label for="query" class="form-label">Query</label>
                            <input type="text" class="form-control" name="query" id="query"
                                placeholder="enter bot's query" required>
                        </div>
                        <div class="mb-3">
                            <label for="query_tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" name="query_tags" id="query_tags"
                                placeholder="enter tags related query and separate by comma">
                            <div id="tags-container"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editor1" class="form-label">Enter bot's reply</label>
                            <textarea name="editor1" id="editor1" rows="10" cols="80" required>
                        </textarea>
                        </div>
                        <button type="button" id="save_query_btn" class="btn btn-primary">Submit</button>
                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <br>
    <br>


    <!-- start of query/reply -->
    <div class="row">
        <div class="col-12">

            <table class="table" id="myTable">
                <thead>
                    <tr>
                        <th scope="col">Chat ID</th>
                        <th scope="col">Query</th>
                        <th scope="col">Reply</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $record = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mc_bot_chats ORDER BY id DESC"));

                    foreach ($record as $data) {
                        ?>
                        <tr>
                            <td><?php echo $data->id; ?></td>
                            <td><?php echo $data->question; ?></td>
                            <td><?php echo sanitize_text_field(substr($data->answer, 0, 50)) . "..."; ?></td>
                            <td>
                                <i class="bi bi-pencil-square" onclick="updateForm(<?php echo $data->id; ?>)"></i>
                                <i class="bi bi-trash3-fill"
                                    onclick="deleteForm(<?php echo $data->id; ?>, '<?php echo $data->question; ?>')"></i>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>
    </div>
    <!-- end of query/reply -->


    <!-- Update Chat Modal -->
    <div class="modal fade" id="updateChatModal" tabindex="-1" aria-labelledby="updateChatModalLabel"
        aria-hidden="true">
        <br>
        <br>
        <br>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateChatModalLabel">Update Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateChatForm">
                        <input type="hidden" id="chat-id">
                        <div class="mb-3">
                            <label for="chat-question" class="form-label">Query</label>
                            <input type="text" class="form-control" id="chat-question" required>
                        </div>
                        <div class="mb-3">
                            <label for="chat-answer" class="form-label">Bot's reply</label>
                            <textarea class="form-control" id="editor2" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="chat-tags" class="form-label">Tags (comma-separated)</label>
                            <input type="text" class="form-control" id="chat-tags">
                            <div id="update-tags-container" class="mt-2"></div>
                        </div>
                        <button type="button" id="update_query_btn" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>



</body>

</html>