<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Pinaka_Custom_Reports_Api_Controller {

    protected $namespace = 'pinaka-pos/v1';
    protected $rest_base = 'reports-new';

   
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/sales',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'get_sales_report'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/shift-sales',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'get_shift_sales_report'),
                'permission_callback' => '__return_true',
            )
        );

    }


    public function check_user_role_permission( $request ) {

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return new WP_Error(
                'pinakapos_rest_cannot_view',
                __( 'Unauthorized', 'pinaka-pos' ),
                array( 'status' => 401 )
            );
        }

        return true;
    }

public function get_sales_report( WP_REST_Request $request ) {

    $type = $request->get_param('type');

    $total_sales  = 0;
    $total_orders = 0;
    $chart        = [];

    // ================= DAILY =================
  if ( $type === 'daily' ) {

    // Get current week start (Monday)
    $start_of_week = strtotime('monday this week');
    $end_of_week   = strtotime('sunday this week');

    $current_date = $start_of_week;

    while ( $current_date <= $end_of_week ) {

        $day_start = date('Y-m-d 00:00:00', $current_date);
        $day_end   = date('Y-m-d 23:59:59', $current_date);

        $orders = wc_get_orders([
            'limit'        => -1,
            'status'       => ['wc-completed', 'wc-processing'],
            'date_created' => $day_start . '...' . $day_end,
        ]);

        $day_sales  = 0;
        $day_orders = count($orders);

        foreach ( $orders as $order ) {
            $day_sales += $order->get_total();
        }

        // If this day is today → set totals
        if ( date('Y-m-d', $current_date) === date('Y-m-d') ) {
            $total_sales  = $day_sales;
            $total_orders = $day_orders;
        }

        $chart[] = [
            'label'  => date('D', $current_date), // Mon, Tue, Wed
            'orders' => $day_orders,
            'sales'  => $day_sales
        ];

        $current_date = strtotime('+1 day', $current_date);
    }
}
    // ================= WEEKLY =================
   elseif ( $type === 'weekly' ) {

    $start_of_month = strtotime(date('Y-m-01'));
    $end_of_month   = strtotime(date('Y-m-t'));
    $today          = strtotime(date('Y-m-d'));

    $week = 1;

    while ( $start_of_month <= $end_of_month ) {

        $week_start = date('Y-m-d 00:00:00', $start_of_month);
        $week_end_timestamp = strtotime('+6 days', $start_of_month);

        if ( $week_end_timestamp > $end_of_month ) {
            $week_end_timestamp = $end_of_month;
        }

        $week_end = date('Y-m-d 23:59:59', $week_end_timestamp);

        $orders = wc_get_orders([
            'limit'        => -1,
            'status'       => ['wc-completed', 'wc-processing'],
            'date_created' => $week_start . '...' . $week_end,
        ]);

        $week_sales  = 0;
        $week_orders = count($orders);

        foreach ( $orders as $order ) {
            $week_sales += $order->get_total();
        }

        // If today falls inside this week
        if ( $today >= strtotime($week_start) && $today <= strtotime($week_end) ) {
            $total_sales  = $week_sales;
            $total_orders = $week_orders;
        }

        $chart[] = [
            'label'  => 'Week ' . $week,
            'orders' => $week_orders,
            'sales'  => $week_sales
        ];

        $start_of_month = strtotime('+7 days', $start_of_month);
        $week++;
    }
}
    // ================= MONTHLY =================
   elseif ( $type === 'monthly' ) {

    $current_month = date('m');
    $current_year  = date('Y');

    for ( $i = 1; $i <= 12; $i++ ) {

        $month_start = date($current_year . '-' . str_pad($i,2,'0',STR_PAD_LEFT) . '-01 00:00:00');
        $month_end   = date($current_year . '-' . str_pad($i,2,'0',STR_PAD_LEFT) . '-t 23:59:59');

        $orders = wc_get_orders([
            'limit'        => -1,
            'status'       => ['wc-completed', 'wc-processing'],
            'date_created' => $month_start . '...' . $month_end,
        ]);

        $month_sales  = 0;
        $month_orders = count($orders);

        foreach ( $orders as $order ) {
            $month_sales += $order->get_total();
        }

        if ( $i == intval($current_month) ) {
            $total_sales  = $month_sales;
            $total_orders = $month_orders;
        }

        $chart[] = [
            'label'  => date('M', strtotime($month_start)), // Jan, Feb, Mar
            'orders' => $month_orders,
            'sales'  => $month_sales
        ];
    }
}
    return [
        'total_sales'  => $total_sales,
        'total_orders' => $total_orders,
        'chart'        => $chart,
    ];
}

public function get_shift_sales_report( WP_REST_Request $request ) {

    $date = $request->get_param('date');
    if ( ! $date ) {
        $date = date('Y-m-d');
    }

    // ✅ Get shifts created on that date
    $shifts = get_posts([
        'post_type'      => 'shifts',   // 🔥 THIS IS THE FIX
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'year'  => date('Y', strtotime($date)),
                'month' => date('m', strtotime($date)),
                'day'   => date('d', strtotime($date)),
            ],
        ],
    ]);

    $shift_results = [];
    $total_sales   = 0;
    $total_orders  = 0;

    foreach ( $shifts as $shift ) {

        $shift_id   = $shift->ID;
        $shift_name = $shift->post_title;  // 🔥 This is your shift name

        // Get orders linked to this shift
        $orders = wc_get_orders([
            'limit'  => -1,
            'status' => ['wc-completed'],
            'meta_query' => [
                [
                    'key'   => 'shift_id',   // ⚠️ Make sure this exists in orders
                    'value' => $shift_id,
                ]
            ],
        ]);

        $shift_sales  = 0;
        $shift_orders = count($orders);

        foreach ( $orders as $order ) {
            $shift_sales += $order->get_total();
        }

        $total_sales  += $shift_sales;
        $total_orders += $shift_orders;

        $shift_results[] = [
            'shift_name' => $shift_name,
            'orders'     => $shift_orders,
            'sales'      => $shift_sales,
        ];
    }

    return [
        'date'         => $date,
        'total_sales'  => $total_sales,
        'total_orders' => $total_orders,
        'shifts'       => $shift_results,
    ];
}
}

