<?php 
/**
 * The admin-discounts functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

if (!defined('WPINC')) { die; }

class Pinaka_Merchant_Dashboard {

    public function __construct() {
        // Keep default WP widgets so default dashboard appears on top.
        // We will add our custom dashboard widget below (priority 1000).
        add_action('wp_dashboard_setup', [$this, 'add_pinaka_dashboard_widget'], 1000);

        // Only remove welcome panel whitespace (optional); do not remove default dashboard widgets.
        add_action('wp_dashboard_setup', [$this, 'remove_welcome_panel'], 999);

        // Load CSS/JS only on dashboard
        add_action('admin_enqueue_scripts', [$this, 'enqueue_tailwind']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_chart_scripts']);
    }

    /**
     * Add custom dashboard widget (placed after default widgets).
     */
    public function add_pinaka_dashboard_widget() {
        wp_add_dashboard_widget(
            'pinaka_custom_dashboard',              // Widget slug
            'Pinaka Custom Dashboard',              // Title
            [$this, 'render_dashboard_widget']      // Display callback
        );

        // Make widget span both columns: hook into admin_print_scripts-index.php
        add_action('admin_print_scripts-index.php', [$this, 'make_widget_fullwidth']);
    }

    /**
     * Keep default widgets intact, only remove welcome panel.
     */
    public function remove_welcome_panel() {
        remove_action('welcome_panel', 'wp_welcome_panel');
    }
    

    /**
     * Small helper to make the widget use full width of dashboard area (both columns)
     */
    public function make_widget_fullwidth() {
        echo '<style>
            /* force our widget to full width */
            #pinaka_custom_dashboard .inside { padding: 0 !important; margin: 0 !important; }
            #dashboard-widgets #postbox-container-1, #dashboard-widgets #postbox-container-2 { width: auto; }
            #dashboard-widgets .postbox#pinaka_custom_dashboard { width: 100% !important; max-width: 1000% !important; box-sizing: border-box; }
            /* push our widget visually below default widgets */
            #pinaka_custom_dashboard { order: 9999; }
            </style>';
             echo '<style>

    /* Remove padding to allow true full-size rendering */
    #pinaka_custom_dashboard .inside { 
        padding: 0 !important; 
        margin: 0 !important; 
    }

    /* Force dashboard containers to not restrict width */
    #dashboard-widgets, 
    #dashboard-widgets-wrap, 
    .metabox-holder {
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Force single-column stretching */
    #postbox-container-1, 
    #postbox-container-2, 
    #postbox-container-3, 
    #postbox-container-4 {
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
    }

    /* Make OUR widget fully expandable */
    .postbox#pinaka_custom_dashboard {
        width: 100% !important;
        max-width: none !important;   /* no max width limit */
        box-sizing: border-box !important;
        position: relative !important;
    }

    /* Allow widget content to exceed container size */
    #pinaka_custom_dashboard .inside {
        overflow: visible !important;
    }

    /* Push our widget below WooCommerce widgets */
    #pinaka_custom_dashboard { 
        order: 9999 !important;
    }

    </style>';
}
    
    /**
     * Enqueue tailwind on dashboard page only
     */
    public function enqueue_tailwind($hook) {
        if ($hook !== 'index.php') return; // only load on dashboard
        wp_enqueue_style(
            'tailwindcss',
            'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
            [],
            null
        );
    }

    /**
     * Enqueue Chart.js for dashboard
     */
    public function enqueue_chart_scripts($hook) {
        if ($hook !== 'index.php') return;
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            [],
            '3.9.1',
            true
        );
    }

    /**
     * Dashboard widget render callback (keeps your existing markup & logic).
     * This function is the dashboard widget content — identical markup/data as before.
     */
    public function render_dashboard_widget() {
        // The original render_dashboard logic but as a widget callback
        // Get current page check - ensure we are on dashboard
        global $pagenow;
        if ($pagenow !== 'index.php') return;

        // Get current period from URL parameter or default to 'day'
        $current_period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'day';
        if (!in_array($current_period, ['day', 'week', 'month'])) {
            $current_period = 'day';
        }

        $restaurant_id = null;

        // Core data loading with period filter
        $revenue_summary = $this->merchant_get_revenue_summary_local($restaurant_id, $current_period);
        $alerts          = $this->get_inventory_alerts_local();
        $top_products    = $this->merchant_get_top_products_local($restaurant_id, $current_period);
        $top_categories  = $this->merchant_get_top_categories_local($restaurant_id, $current_period);
        
        // Get chart data from WooCommerce orders with period filter
        $chart_data = $this->get_chart_data_from_orders($current_period);

        echo '<div class="p-8 bg-gray-50 min-h-screen">';

        // Keep the exact CSS and HTML you had (unchanged).
        echo '<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .dashboard {
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .dashboard-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .filter-container {
            display: flex;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e1e4e8;
        }
        
        .filter-option {
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #555;
            border-right: 1px solid #e1e4e8;
            text-decoration: none;
            display: block;
        }
        
        .filter-option:last-child {
            border-right: none;
        }
        
        .filter-option:hover {
            background-color: #f8f9fa;
            text-decoration: none;
        }
        
        .filter-option.active {
            background-color: #3498db;
            color: white;
            text-decoration: none;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #bae6fd;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .card-content {
            color: #7f8c8d;
        }
        
        .chart-placeholder {
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #95a5a6;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #3498db;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-container {
                margin-top: 15px;
                align-self: flex-end;
            }
            
            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }
    </style>';

        echo'<div class="dashboard">
            <div class="dashboard-header">
                <h1 class="dashboard-title">📊Dashboard</h1>
                <div class="filter-container">';

        // Generate filter links with active state
        $periods = ['day' => 'Day', 'week' => 'Week', 'month' => 'Month'];
        foreach ($periods as $period => $label) {
            $active_class = ($current_period === $period) ? 'active' : '';
            $url = add_query_arg('period', $period);
            echo '<a href="' . esc_url($url) . '" class="filter-option ' . $active_class . '">' . $label . '</a>';
        }

        echo '</div>';
        echo '</div>';

        // Revenue Cards with period-specific data
        echo '<style>
            .dashboard-row {
                display: flex;
                gap: 20px;
            }
            .dashboard-card {
                flex: 1;
            }
        </style>';

        // Cards
        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8  ">';
        echo $this->card('💰Total Revenue', '₹' . number_format($revenue_summary['total_revenue'], 2), '', 'from-blue-50 to-blue-100', 'border-blue-200');
        echo $this->card('🛒Total Orders', $revenue_summary['today_orders'], '', 'from-green-50 to-green-100', 'border-green-200');
        echo '</div>';

        // BAR CHARTS SECTION
        echo '<div class="bg-blue-50 shadow rounded-xl p-6 mb-8 border-l-4 border-blue-300">';
        echo '<div class="flex justify-between items-center mb-6">';
        echo '<h2 class="text-xl font-semibold text-gray-700">📈Store Analytics' ;
        echo '</div>';

        // Bar Charts Grid - Side by Side
        echo '<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">';

        // Net Sales Chart
        echo '<div class="bg-white rounded-lg p-4 shadow-sm">';
        echo '<h3 class="text-lg font-semibold mb-4 text-gray-700">Net sales</h3>';
        echo '<div class="chart-container" style="height: 200px;">';
        echo '<canvas id="netSalesChart"></canvas>';
        echo '</div>';
        echo '</div>';

        // Orders Chart
        echo '<div class="bg-white rounded-lg p-4 shadow-sm">';
        echo '<h3 class="text-lg font-semibold mb-4 text-gray-700">Orders</h3>';
        echo '<div class="chart-container" style="height: 200px;">';
        echo '<canvas id="ordersChart"></canvas>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // End charts grid
        echo '</div>'; // End analytics section

        // Two columns for Top Products and Top Categories
        echo '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">';
        echo $this->render_list_section('🔥 Top Products Sold  ', $top_products, 'pcs', 'from-purple-50 to-purple-100', 'border-purple-200');
        echo $this->render_list_section('🏷️ Top Categories Sold  ' , $top_categories, 'pcs', 'from-pink-50 to-pink-100', 'border-pink-200');
        echo '</div>';

        // Inventory Alerts
        echo $this->render_inventory_alerts($alerts);

        echo '</div>'; // .dashboard wrapper

        // Output the chart initialization script with dynamic WooCommerce data
        $this->output_chart_script($chart_data, $current_period);

        echo '</div>'; // container
    }

    /* ------------------------------------------------------------------
     * BUSINESS LOGIC METHODS
     * ------------------------------------------------------------------ */

    private function merchant_get_revenue_summary_local($restaurant_id, $period = 'day') {
        $date_range = $this->get_date_range_for_period($period);
        
        // Calculate current period sales and orders
        $current = $this->pinaka_calculate_sales($date_range['current_start'], $date_range['current_end'], null, null);
        
        // Calculate total orders (only completed orders as per your existing logic)
        $total_orders = $this->get_total_orders_count();
        
        return [
            'total_revenue' => $current['gross_sales'] ?? 0,
            'today_orders'  => $current['orders'] ?? 0,
            'total_orders'  => $total_orders,
        ];
    }

    private function merchant_get_top_products_local($restaurant_id, $period = 'day') {
        $date_range = $this->get_date_range_for_period($period);
        
        $orders = wc_get_orders([
            'date_created' => $date_range['current_start'] . '...' . $date_range['current_end'],
            'status'       => array_keys(wc_get_order_statuses()),
            'limit'        => -1,
        ]);

        $counts = [];

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $name = $item->get_name();

                if (!isset($counts[$name])) { 
                    $counts[$name] = 0; 
                }

                $counts[$name] += $item->get_quantity();
            }
        }

        // Sort all products by quantity sold in descending order
        arsort($counts);
        
        // Take only top 5 products
        $top_products = array_slice($counts, 0, 5, true);

        $result = [];
        foreach ($top_products as $name => $quantity) {
            $result[] = ['name' => $name, 'items_sold' => $quantity];
        }

        return $result;
    }

    private function merchant_get_top_categories_local($restaurant_id, $period = 'day') {
        $date_range = $this->get_date_range_for_period($period);
        
        $orders = wc_get_orders([
            'date_created' => $date_range['current_start'] . '...' . $date_range['current_end'],
            'status'       => array_keys(wc_get_order_statuses()),
            'limit'        => -1,
        ]);

        $counts = [];

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);

                foreach ($categories as $cat) {
                    if (!isset($counts[$cat])) {
                        $counts[$cat] = 0;
                    }
                    $counts[$cat] += $item->get_quantity();
                }
            }
        }

        // Sort all categories by quantity sold in descending order
        arsort($counts);
        
        // Take only top 5 categories
        $top_categories = array_slice($counts, 0, 5, true);

        $result = [];
        foreach ($top_categories as $name => $qty) {
            $result[] = ['name' => $name, 'items_sold' => $qty];
        }

        return $result;
    }

    private function pinaka_calculate_sales($start_date, $end_date, $zone_id = null, $order_type = null) {
        $args = [
            'status' => ['wc-completed', 'wc-processing'],
            'date_created' => $start_date . '...' . $end_date,
            'limit' => -1,
        ];
        $orders = wc_get_orders($args);
        $gross = 0; 
        $count = 0;
        
        foreach ($orders as $order) {
            if ($zone_id && (int)$order->get_meta('_zone_id') !== (int)$zone_id) continue;
            $gross += $order->get_total();
            $count++;
        }
        
        return ['orders' => $count, 'gross_sales' => $gross];
    }

    private function get_total_orders_count() {
        $orders = wc_get_orders([
            'status' => ['wc-completed'],
            'limit' => -1,
        ]);
        return count($orders);
    }

    /**
     * Get date ranges based on period
     */
    private function get_date_range_for_period($period) {
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'week':
                $current_start = date('Y-m-d', strtotime('monday this week'));
                $current_end = $today;
                $previous_start = date('Y-m-d', strtotime('monday last week'));
                $previous_end = date('Y-m-d', strtotime('sunday last week'));
                break;
                
            case 'month':
                $current_start = date('Y-m-01');
                $current_end = $today;
                $previous_start = date('Y-m-01', strtotime('-1 month'));
                $previous_end = date('Y-m-t', strtotime('-1 month'));
                break;
                
            case 'day':
            default:
                $current_start = $today;
                $current_end = $today;
                $previous_start = date('Y-m-d', strtotime('-1 day'));
                $previous_end = date('Y-m-d', strtotime('-1 day'));
                break;
        }
        
        return [
            'current_start' => $current_start,
            'current_end' => $current_end,
            'previous_start' => $previous_start,
            'previous_end' => $previous_end
        ];
    }

    /**
     * Get chart data from WooCommerce orders with period filter
     */
    private function get_chart_data_from_orders($period = 'day') {
        $date_range = $this->get_date_range_for_period($period);
        
        // Get orders for current period
        $current_orders = wc_get_orders([
            'status' => ['wc-completed', 'wc-processing'],
            'date_created' => $date_range['current_start'] . '...' . $date_range['current_end'],
            'limit' => -1,
        ]);

        // Get orders for previous period
        $previous_orders = wc_get_orders([
            'status' => ['wc-completed', 'wc-processing'],
            'date_created' => $date_range['previous_start'] . '...' . $date_range['previous_end'],
            'limit' => -1,
        ]);

        // Calculate net sales and orders
        $current_sales = 0;
        $current_order_count = count($current_orders);
        foreach ($current_orders as $order) {
            $current_sales += $order->get_total();
        }

        $previous_sales = 0;
        $previous_order_count = count($previous_orders);
        foreach ($previous_orders as $order) {
            $previous_sales += $order->get_total();
        }

        // Get data based on period
        switch ($period) {
            case 'week':
                $labels = [];
                $sales_data = [];
                $orders_data = [];
                
                // Last 4 calendar weeks (can cross month boundaries)
                for ($i = 3; $i >= 0; $i--) {
                    $week_start = date('Y-m-d', strtotime('monday this week -' . $i . ' weeks'));
                    $week_end = date('Y-m-d', strtotime('sunday this week -' . $i . ' weeks'));
                    
                    $week_orders = wc_get_orders([
                        'status' => ['wc-completed', 'wc-processing'],
                        'date_created' => $week_start . '...' . $week_end,
                        'limit' => -1,
                    ]);

                    $week_sales = 0;
                    $week_order_count = 0;
                    foreach ($week_orders as $order) {
                        $week_sales += $order->get_total();
                        $week_order_count++;
                    }

                    // Format label to show actual dates, even if they cross months
                    $start_month = date('M', strtotime($week_start));
                    $end_month = date('M', strtotime($week_end));
                    
                    if ($start_month === $end_month) {
                        $labels[] = date('M j', strtotime($week_start)) . ' - ' . date('j', strtotime($week_end));
                    } else {
                        $labels[] = date('M j', strtotime($week_start)) . ' - ' . date('M j', strtotime($week_end));
                    }
                    
                    $sales_data[] = $week_sales;
                    $orders_data[] = $week_order_count;
                }
                break;
                
            case 'month':
                $labels = [];
                $sales_data = [];
                $orders_data = [];
                
                // Last 6 months
                for ($i = 5; $i >= 0; $i--) {
                    $month_start = date('Y-m-01', strtotime("-$i months"));
                    $month_end = date('Y-m-t', strtotime("-$i months"));
                    
                    $month_orders = wc_get_orders([
                        'status' => ['wc-completed', 'wc-processing'],
                        'date_created' => $month_start . '...' . $month_end,
                        'limit' => -1,
                    ]);

                    $month_sales = 0;
                    $month_order_count = 0;
                    foreach ($month_orders as $order) {
                        $month_sales += $order->get_total();
                        $month_order_count++;
                    }

                    $labels[] = date('M', strtotime($month_start));
                    $sales_data[] = $month_sales;
                    $orders_data[] = $month_order_count;
                }
                break;
                
            case 'day':
            default:
                $labels = [];
                $sales_data = [];
                $orders_data = [];
                
                // Last 7 days
                for ($i = 6; $i >= 0; $i--) {
                    $day = date('Y-m-d', strtotime("-$i days"));
                    $day_orders = wc_get_orders([
                        'status' => ['wc-completed', 'wc-processing'],
                        'date_created' => $day . '...' . $day,
                        'limit' => -1,
                    ]);

                    $day_sales = 0;
                    $day_order_count = 0;
                    foreach ($day_orders as $order) {
                        $day_sales += $order->get_total();
                        $day_order_count++;
                    }

                    $labels[] = date('D', strtotime($day));
                    $sales_data[] = $day_sales;
                    $orders_data[] = $day_order_count;
                }
                break;
        }

        // Get period comparison data
        $current_period_data = $this->get_period_data($date_range['current_start'], $date_range['current_end']);
        $previous_period_data = $this->get_period_data($date_range['previous_start'], $date_range['previous_end']);
        
        // Get yearly data
        $current_year = $this->get_period_data(date('Y-01-01'), date('Y-m-d'));
        $previous_year = $this->get_period_data(
            date('Y-01-01', strtotime('-1 year')),
            date('Y-m-d', strtotime('-1 year'))
        );

        return [
            'net_sales' => [
                'current' => $current_sales,
                'previous' => $previous_sales
            ],
            'orders' => [
                'current' => $current_order_count,
                'previous' => $previous_order_count
            ],
            'labels' => $labels,
            'sales_data' => $sales_data,
            'orders_data' => $orders_data,
            'current_period' => $current_period_data,
            'previous_period' => $previous_period_data,
            'current_year' => $current_year,
            'previous_year' => $previous_year,
            'period' => $period
        ];
    }

    /**
     * Get data for a specific period
     */
    private function get_period_data($start_date, $end_date) {
        $orders = wc_get_orders([
            'status' => ['wc-completed', 'wc-processing'],
            'date_created' => $start_date . '...' . $end_date,
            'limit' => -1,
        ]);

        $revenue = 0;
        foreach ($orders as $order) {
            $revenue += $order->get_total();
        }

        return [
            'revenue' => $revenue,
            'orders' => count($orders)
        ];
    }

    /**
     * Output JavaScript to initialize charts with dynamic WooCommerce data
     */
    private function output_chart_script($chart_data, $current_period) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts with current period data
            function initializeCharts() {
                if (typeof Chart !== 'undefined') {
                    try {
                        // Net Sales Chart
                        const netSalesElem = document.getElementById('netSalesChart');
                        if (netSalesElem) {
                            const netSalesCtx = netSalesElem.getContext('2d');
                            window.netSalesChart = new Chart(netSalesCtx, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode($chart_data['labels']); ?>,
                                    datasets: [{
                                        label: 'Net Sales',
                                        data: <?php echo json_encode($chart_data['sales_data']); ?>,
                                        backgroundColor: '#b0d3faff',
                                        borderColor: '#9ec5f3ff',
                                        borderWidth: 1,
                                        borderRadius: 6,
                                        barPercentage: 0.7,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: { callbacks: {
                                            label: function(context) { return '₹' + context.parsed.y.toFixed(2); }
                                        }}
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { callback: function(value) { return '₹' + value.toFixed(2); } }
                                        }
                                    }
                                }
                            });
                        }

                        // Orders Chart
                        const ordersElem = document.getElementById('ordersChart');
                        if (ordersElem) {
                            const ordersCtx = ordersElem.getContext('2d');
                            window.ordersChart = new Chart(ordersCtx, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode($chart_data['labels']); ?>,
                                    datasets: [{
                                        label: 'Orders',
                                        data: <?php echo json_encode($chart_data['orders_data']); ?>,
                                        backgroundColor: '#d0f4deff',
                                        borderColor: '#d0f4deff',
                                        borderWidth: 1,
                                        borderRadius: 6,
                                        barPercentage: 0.7,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: { y: { beginAtZero: true } }
                                }
                            });
                        }

                    } catch (error) {
                        console.error('Error initializing charts:', error);
                    }
                } else {
                    setTimeout(initializeCharts, 100);
                }
            }
            
            // Load Chart.js if not already loaded
            if (typeof Chart === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
                script.onload = initializeCharts;
                document.head.appendChild(script);
            } else {
                setTimeout(initializeCharts, 500);
            }
        });
        </script>
        <?php
    }

    /* ------------------------------------------------------------------
     * UI COMPONENT METHODS
     * ------------------------------------------------------------------ */

    private function card($title, $value, $description, $gradient = 'from-blue-50 to-blue-100', $border = 'border-blue-200') {
        return '
        <div class="bg-gradient-to-br ' . $gradient . ' rounded-xl shadow-sm p-6 border-l-4 ' . $border . '">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">' . $title . '</h3>
            <p class="text-2xl font-bold text-gray-900">' . $value . '</p>
            <p class="text-sm text-gray-500 mt-2">' . $description . '</p>
        </div>';
    }

    private function render_list_section($title, $items, $unit, $gradient = 'from-purple-50 to-purple-100', $border = 'border-purple-200') {
        $html = '<div class="bg-gradient-to-br ' . $gradient . ' rounded-xl shadow-sm p-6 border-l-4 ' . $border . '">';
        $html .= '<h3 class="text-lg font-semibold text-gray-700 mb-4">' . $title . '</h3>';
        
        if (!empty($items) && is_array($items)) {
            $html .= '<div class="space-y-3">';
            foreach ($items as $item) {
                $html .= '<div class="flex justify-between items-center py-2 border-b border-opacity-30">';
                $html .= '<span class="text-gray-700">' . esc_html($item['name']) . '</span>';
                $html .= '<span class="font-semibold text-gray-600">' . $item['items_sold'] . ' ' . $unit . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<p class="text-gray-500 text-center py-4">No data available</p>';
        }
        
        $html .= '</div>';
        return $html;
    }

    private function get_inventory_alerts_local() {
        // Get ALL products without any meta query restrictions
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            // Remove meta_query to get ALL products
        ];

        $query = new WP_Query($args);
        $alerts = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            
            if (!$product) {
                continue;
            }

            $product_id = $product->get_id();
            $product_name = $product->get_name();
            $manages_stock = $product->get_manage_stock();
            $stock_quantity = $product->get_stock_quantity();
            $stock_status = $product->get_stock_status();
            
            // Handle variable products
            if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();
                $has_low_stock = false;
                $has_out_of_stock = false;
                $low_stock_count = 0;
                $out_of_stock_count = 0;
                
                foreach ($variations as $variation) {
                    $variation_product = wc_get_product($variation['variation_id']);
                    if ($variation_product) {
                        $var_manages_stock = $variation_product->get_manage_stock();
                        $var_stock_quantity = $variation_product->get_stock_quantity();
                        $var_stock_status = $variation_product->get_stock_status();
                        
                        // Check for out of stock first (based on WooCommerce stock status)
                        if ($var_stock_status === 'outofstock') {
                            $has_out_of_stock = true;
                            $out_of_stock_count++;
                        } 
                        // Then check for low stock only if not out of stock and manages stock
                        elseif ($var_manages_stock && $var_stock_quantity !== null && $var_stock_quantity <= 5) {
                            $has_low_stock = true;
                            $low_stock_count++;
                        }
                    }
                }
                
                // Add alerts for variable products with problematic variations
                if ($has_out_of_stock) {
                    $alerts[] = [
                        'id'        => $product_id,
                        'name'      => $product_name . ' (Variable - ' . $out_of_stock_count . ' variations out of stock)',
                        'quantity'  => 0,
                        'status'    => 'Out of Stock'
                    ];
                }
                
                if ($has_low_stock && !$has_out_of_stock) {
                    $alerts[] = [
                        'id'        => $product_id,
                        'name'      => $product_name . ' (Variable - ' . $low_stock_count . ' variations low stock)',
                        'quantity'  => 1,
                        'status'    => 'Low Stock'
                    ];
                }
                
            } else {
                // Simple products and other types
                
                // Check stock status first - if out of stock, show as out of stock
                if ($stock_status === 'outofstock') {
                    $alerts[] = [
                        'id'        => $product_id,
                        'name'      => $product_name,
                        'quantity'  => 0,
                        'status'    => 'Out of Stock'
                    ];
                } 
                // If in stock but manages stock and quantity is low, show as low stock
                elseif ($manages_stock && $stock_quantity !== null && $stock_quantity <= 5) {
                    $alerts[] = [
                        'id'        => $product_id,
                        'name'      => $product_name,
                        'quantity'  => $stock_quantity,
                        'status'    => 'Low Stock'
                    ];
                }
                // Skip products that are in stock with sufficient quantity
                else {
                    continue;
                }
            }
        }

        return $alerts;
    }

    private function render_inventory_alerts($alerts) {
        echo '<style>
            .pinaka-status-dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; margin-right: 6px; }
            .status-instock { background-color: #86efac; }
            .status-lowstock { background-color: #fde047; }
            .status-outstock { background-color: #fca5a5; }
            .pinaka-status-label { display: flex; align-items: center; gap: 6px; font-weight: 500; }
            .pinaka-inventory-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
            .pinaka-inventory-table th, .pinaka-inventory-table td { padding: 10px 12px; border-bottom: 1px solid #dbeafe; text-align: left; }
            .pinaka-inventory-table th { background: #bfdbfe; font-weight: 600; color: #1e40af; }
            .pinaka-category-row { background-color: #dbeafe; font-weight: 600; }
            .pinaka-products-list { color: #666; font-size: 0.9em; margin-top: 4px; }
        </style>';

        echo "<div class='bg-gradient-to-br from-blue-50 to-blue-100 shadow rounded-xl p-6 mb-8 border-l-4 border-blue-300'>";
        echo '<h2 class="text-xl font-semibold mb-4 text-gray-700">🚨 Inventory Alerts</h2>';
        
        if (!empty($alerts) && is_array($alerts)) {

            // Filter only low stock and out of stock products - CORRECTED STATUS CHECK
            $filtered_alerts = array_filter($alerts, function($item) {
                $status = $item['status'] ?? '';
                $quantity = $item['quantity'] ?? 0;
                if ($status === 'outofstock' || $status === 'Out of Stock') { return true; }
                if ($status === 'lowstock' || $status === 'Low Stock') { return true; }
                if (($status === 'instock' || $status === 'In Stock') && $quantity > 0 && $quantity <= 5) { return true; }
                return false;
            });

            if (empty($filtered_alerts)) {
                echo '<p class="text-gray-500">No inventory alerts at this time.</p>';
                echo '</div>';
                return;
            }

            // Group products by category and status
            $categories_data = [];
            
            foreach ($filtered_alerts as $item) {
                $product_categories = [];
                if (isset($item['id'])) {
                    $terms = wp_get_post_terms($item['id'], 'product_cat');
                    foreach ($terms as $term) { $product_categories[] = $term->name; }
                }
                if (empty($product_categories)) { $product_categories[] = 'Uncategorized'; }
                
                $status = $item['status'] ?? '';
                $quantity = $item['quantity'] ?? 0;
                
                $is_out_of_stock = false;
                $is_low_stock = false;
                
                if ($status === 'outofstock' || $status === 'Out of Stock' || $quantity <= 0) {
                    $is_out_of_stock = true;
                } elseif ($status === 'lowstock' || $status === 'Low Stock' || ($quantity > 0 && $quantity <= 5)) {
                    $is_low_stock = true;
                }
                
                foreach ($product_categories as $category) {
                    if (!isset($categories_data[$category])) {
                        $categories_data[$category] = [ 'low_stock' => [], 'out_of_stock' => [] ];
                    }
                    if ($is_low_stock) {
                        $categories_data[$category]['low_stock'][] = [ 'name' => $item['name'] ?? 'Unknown Product', 'quantity' => $quantity ];
                    } elseif ($is_out_of_stock) {
                        $categories_data[$category]['out_of_stock'][] = [ 'name' => $item['name'] ?? 'Unknown Product', 'quantity' => 0 ];
                    }
                }
            }

            echo '<table class="pinaka-inventory-table">';
            echo '<tr><th>Category</th><th>Low Stock Products</th><th>Out of Stock Products</th><th>Total Alerts</th></tr>';

            foreach ($categories_data as $category_name => $category_data) {
                $low_stock_count = count($category_data['low_stock']);
                $out_of_stock_count = count($category_data['out_of_stock']);
                $total_alerts = $low_stock_count + $out_of_stock_count;
                
                if ($total_alerts === 0) {
                    continue; // Skip categories with no alerts
                }

                $low_stock_products = [];
                $out_of_stock_products = [];
                
                foreach ($category_data['low_stock'] as $product) {
                    $low_stock_products[] = $product['name'] . ' (' . $product['quantity'] . ')';
                }
                
                foreach ($category_data['out_of_stock'] as $product) {
                    $out_of_stock_products[] = $product['name'] . ' (0)';
                }

                echo '<tr>';
                echo '<td class="pinaka-category-row">' . esc_html($category_name) . '</td>';
                echo '<td>';
                if (!empty($low_stock_products)) {
                    echo '<span class="pinaka-status-label"><span class="pinaka-status-dot status-lowstock"></span>Low Stock</span>';
                    echo '<div class="pinaka-products-list">' . implode(', ', $low_stock_products) . '</div>';
                } else {
                    echo '<span class="text-gray-400">-</span>';
                }
                echo '</td>';
                echo '<td>';
                if (!empty($out_of_stock_products)) {
                    echo '<span class="pinaka-status-label"><span class="pinaka-status-dot status-outstock"></span>Out of Stock</span>';
                    echo '<div class="pinaka-products-list">' . implode(', ', $out_of_stock_products) . '</div>';
                } else {
                    echo '<span class="text-gray-400">-</span>';
                }
                echo '</td>';
                echo '<td><span class="font-semibold text-blue-600">' . $total_alerts . '</span></td>';
                echo '</tr>';
            }

            echo '</table>';
        } else {
            echo '<p class="text-gray-500">No inventory alerts at this time.</p>';
        }
        
        echo '</div>';
    }
}

// Instantiate the class
$pinaka_merchant_dashboard = new Pinaka_Merchant_Dashboard();
