<?php
if (!defined('ABSPATH')) {
    exit;
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Dashboard Chat History List Table Class
 */
class MC_Bot_Dashboard_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct([
            'singular' => 'chat_history',
            'plural'   => 'chat_histories',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'id'    => 'ID',
            'query' => 'User Query',
            'date'  => 'Date',
            'status'=> 'Status'
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'   => ['id', true],
            'date' => ['date', false]
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'query':
            case 'date':
                return $item[$column_name];
            case 'status':
                return $item[$column_name];
            default:
                return '';
        }
    }

    public function column_status($item) {
        global $wpdb;
        $chats_table = $wpdb->prefix . 'mc_bot_chats';
        
        // Check if query exists in chats
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $chats_table WHERE question = %s",
            $item['query']
        ));
        
        if ($exists) {
            return '<span style="color: #00a32a;"><span class="dashicons dashicons-yes-alt"></span> Answered</span>';
        } else {
            return '<span style="color: #d63638;"><span class="dashicons dashicons-warning"></span> Unanswered</span>';
        }
    }

    public function prepare_items() {
        global $wpdb;

        $per_page = $this->get_items_per_page('dashboard_per_page', 15);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $orderby = (!empty($_GET['orderby'])) ? sanitize_sql_orderby($_GET['orderby']) : 'id';
        $order = (!empty($_GET['order'])) ? sanitize_text_field($_GET['order']) : 'DESC';

        $table_name = $wpdb->prefix . 'mc_bot_unreserved_queries';

        // Get total items
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // Get items for current page
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ), ARRAY_A);

        $this->items = $items;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
    }
}

// Get statistics
global $wpdb;
$unreserved_table = $wpdb->prefix . 'mc_bot_unreserved_queries';
$chats_table = $wpdb->prefix . 'mc_bot_chats';

$total_queries = $wpdb->get_var("SELECT COUNT(*) FROM $unreserved_table");
$total_responses = $wpdb->get_var("SELECT COUNT(*) FROM $chats_table");
$unanswered = $wpdb->get_var(
    "SELECT COUNT(*) FROM $unreserved_table 
     WHERE query NOT IN (SELECT question FROM $chats_table)"
);

// Get chart data (last 7 days)
$chart_data = $wpdb->get_results(
    "SELECT date, COUNT(*) as count 
     FROM $unreserved_table 
     GROUP BY date 
     ORDER BY date DESC 
     LIMIT 7",
    ARRAY_A
);

$dates = array_reverse(array_column($chart_data, 'date'));
$counts = array_reverse(array_column($chart_data, 'count'));

// Create list table instance
$dashboard_table = new MC_Bot_Dashboard_List_Table();
$dashboard_table->prepare_items();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Super Bot</title>
    <style>
        .mc-bot-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .mc-bot-stat-card {
            background: #fff;
            padding: 25px;
            border-left: 4px solid #0073aa;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .mc-bot-stat-card.warning {
            border-left-color: #d63638;
        }
        .mc-bot-stat-card.success {
            border-left-color: #00a32a;
        }
        .mc-bot-stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #0073aa;
            margin: 10px 0;
        }
        .mc-bot-stat-card.warning .mc-bot-stat-number {
            color: #d63638;
        }
        .mc-bot-stat-card.success .mc-bot-stat-number {
            color: #00a32a;
        }
        .mc-bot-stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        .mc-bot-chart-container {
            background: #fff;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .mc-bot-chart-container h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }
        #usageChart {
            max-height: 300px;
        }
        .mc-bot-welcome {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
            color: #fff;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .mc-bot-welcome h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .mc-bot-welcome p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <!-- Welcome Banner -->
        <div class="mc-bot-welcome">
            <h2><span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> Welcome to Super Bot Dashboard</h2>
            <p>Monitor your chatbot performance and manage user interactions effectively.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="mc-bot-stats">
            <div class="mc-bot-stat-card">
                <span class="dashicons dashicons-format-chat" style="font-size: 28px; color: #0073aa;"></span>
                <div class="mc-bot-stat-number"><?php echo number_format($total_queries); ?></div>
                <div class="mc-bot-stat-label">Total User Queries</div>
            </div>

            <div class="mc-bot-stat-card success">
                <span class="dashicons dashicons-yes-alt" style="font-size: 28px; color: #00a32a;"></span>
                <div class="mc-bot-stat-number"><?php echo number_format($total_responses); ?></div>
                <div class="mc-bot-stat-label">Bot Responses Available</div>
            </div>

            <div class="mc-bot-stat-card warning">
                <span class="dashicons dashicons-warning" style="font-size: 28px; color: #d63638;"></span>
                <div class="mc-bot-stat-number"><?php echo number_format($unanswered); ?></div>
                <div class="mc-bot-stat-label">Unanswered Queries</div>
            </div>
        </div>

        <!-- Usage Chart -->
        <?php if (!empty($dates)): ?>
        <div class="mc-bot-chart-container">
            <h2><span class="dashicons dashicons-chart-line" style="vertical-align: middle;"></span> Bot Usage (Last 7 Days)</h2>
            <canvas id="usageChart"></canvas>
        </div>
        <?php endif; ?>

        <!-- Recent Queries Table -->
        <h2 style="margin-top: 30px;">Recent User Queries</h2>
        <form method="get">
            <input type="hidden" name="page" value="chatbot_dashboard" />
            <?php $dashboard_table->display(); ?>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($dates)): ?>
        // Create chart
        const ctx = document.getElementById('usageChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'User Queries',
                        data: <?php echo json_encode($counts); ?>,
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#0073aa',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1d2327',
                            padding: 12,
                            cornerRadius: 4
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            },
                            grid: {
                                color: '#f0f0f1'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>