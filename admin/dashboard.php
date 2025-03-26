<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
  
   <?php 
   //wp_footer(); 
   wp_head(); ?>

</head>

<body>

    <div class="row">
        <div class="col-1"></div>
        <div class="col-10">
            <!-- Chart Container -->
            <canvas id="chatHistoryChart"></canvas>
        </div>
        <div class="col-1"></div>
    </div>


    <br>
    <br>
    <div class="row">
        <h4 class="text-center">Recent Top Chats</h4>
        <br>
        <br>

        <?php
        global $wpdb;
        $history_table_name = $wpdb->prefix . 'mc_bot_chat_history';

        // Fetch recent chats
        $results = $wpdb->get_results("SELECT id,query, gemini_reply, location, date FROM $history_table_name ORDER BY id DESC LIMIT 15");

        if ($results && count($results) > 0) {
            echo '<table class="table custom-table" id="myTable">
            <thead>
                <tr>
                <th scope="col">Enroll No.</th>
                    <th scope="col">Query</th>
                    <th scope="col">Gemini A.I</th>
                    <th scope="col">Location</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>';

            foreach ($results as $row) {
                $location = ($row->location === 'Localhost') ? 'test locally' : $row->location;
                $gemini_reply = substr($row->gemini_reply, 0, 100) . "...";
                echo "<tr>
                <td>{$row->id}</td>
                <td>{$row->query}</td>
                <td>{$gemini_reply}</td>
                <td>{$location}</td>
                <td>{$row->date}</td>
                </tr>";
            }

            echo '</tbody></table>';
        } else {
            echo "<div class='text-center'>No chat history available</div>";
        }
        ?>

    </div>

    

    <script>
        document.addEventListener('DOMContentLoaded', function () {
    if (jQuery('#myTable').length) {
        jQuery('#myTable').DataTable({
                order: [[0, 'desc']]
            });
    }

    // Fetch chat history data from PHP
    <?php
    $chart_data = $wpdb->get_results("SELECT date, COUNT(*) as chat_count FROM $history_table_name GROUP BY date ORDER BY id DESC LIMIT 25");

    $dates = [];
    $counts = [];
    foreach ($chart_data as $data) {
        $dates[] = $data->date;
        $counts[] = $data->chat_count;
    }
    ?>
    const dates = <?php echo json_encode($dates); ?>;
    const counts = <?php echo json_encode($counts); ?>;

    // Render Chart
    const ctx = document.getElementById('chatHistoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Bot Usages Last 7 Days',
                data: counts,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

    </script>
    
   
</body>

</html>