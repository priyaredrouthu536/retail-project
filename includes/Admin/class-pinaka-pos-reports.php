<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Pinaka_Reports {

    public function register_menu() {
        add_submenu_page(
            'pinaka-pos-dashboard',
            __('Reports','pinaka-pos'),
            __('Reports','pinaka-pos'),
            'manage_options',
            'reports',
            [$this,'render_page_reports']
        );
    }

    public function render_page_reports() {

        /* ===== Orders & Revenue Range ===== */
        $range = $_GET['range'] ?? 'daily';
        if(!in_array($range,['daily','weekly','monthly'])) {
            $range = 'daily';
        }

        $data = $this->get_reports_data($range);

        /* ===== Shift Date Range ===== */
        $shift_range = $_GET['shift_range'] ?? 'daily';
        if(!in_array($shift_range,['daily','weekly','monthly'])){
            $shift_range = 'daily';
        }

        $shift_data = $this->get_shift_chart_data($shift_range);

        ?>

        <div class="wrap">
            <h1>Reports Overview</h1>

            <!-- =========================
                 ORDERS & REVENUE GRAPH
            ========================== -->
            <div style="background:#f5f3ff;padding:25px;margin-top:15px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">

                    <div style="display:flex;align-items:center;font-size:22px;font-weight:600;color:#334155;">
                        📊 <span style="margin-left:10px;">Total Orders & Revenue</span>
                    </div>

                    <div style="display:inline-flex;background:#e5e7eb;border-radius:12px;overflow:hidden;border:1px solid #d1d5db;">
                        <?php
                        $ranges = ['daily'=>'Day','weekly'=>'Week','monthly'=>'Month'];
                        foreach($ranges as $key=>$label){
                            $active = ($range==$key)
                                ? 'background:#3b82f6;color:#fff;'
                                : 'color:#374151;';
                            echo '<a href="?page=reports&range='.$key.'"
                                    style="padding:10px 22px;text-decoration:none;font-weight:500;'.$active.'">'.$label.'</a>';
                        }
                        ?>
                    </div>

                </div>


                <div style="background:#ffffff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.06);height:360px;">
                    <canvas id="comboChart"></canvas>
                </div>

            </div>

            <!-- =========================
            SHIFT ANALYTICS GRAPH
            ========================== -->
            <div style="background:#f5f3ff;padding:25px;margin-top:25px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">

                    <div style="display:flex;align-items:center;font-size:22px;font-weight:600;color:#334155;">
                        📅 <span style="margin-left:10px;">User Sales Analytics</span>
                    </div>

                    <div style="display:inline-flex;background:#e5e7eb;border-radius:12px;overflow:hidden;border:1px solid #d1d5db;">
                        <?php
                        $shift_ranges = ['daily'=>'Day','weekly'=>'Week','monthly'=>'Month'];
                        foreach($shift_ranges as $key=>$label){
                            $active = ($shift_range==$key)
                                ? 'background:#3b82f6;color:#fff;'
                                : 'color:#374151;';
                            echo '<a href="?page=reports&range='.$range.'&shift_range='.$key.'"
                                    style="padding:10px 22px;text-decoration:none;font-weight:500;'.$active.'">'.$label.'</a>';
                        }
                        ?>
                    </div>


                </div>

                <div style="
                    background:#ffffff;
                    padding:14px 18px;
                    border-radius:10px;
                    margin-bottom:12px;
                    display:inline-block;
                    font-size:16px;
                    font-weight:600;
                    box-shadow:0 1px 3px rgba(0,0,0,0.06);
                ">
                    🧾 Total Shift Orders:
                    <span style="color:#3b82f6;">
                        <?php echo intval($shift_data['total_orders']); ?>
                    </span>
                </div>

                <div style="
                    background:#ffffff;
                    padding:14px 18px;
                    border-radius:10px;
                    margin-bottom:12px;
                    margin-left:10px;
                    display:inline-block;
                    font-size:16px;
                    font-weight:600;
                    box-shadow:0 1px 3px rgba(0,0,0,0.06);
                ">
                    💰 Total Shift Revenue:
                    <span style="color:#16a34a;">
                        ₹<?php echo number_format($shift_data['total_revenue'], 2); ?>
                    </span>
                </div>


                <div style="background:#ffffff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.06);height:320px;">
                    <canvas id="shiftChart"></canvas>
                </div>

            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function(){

                /* ===== Orders & Revenue Chart ===== */
                new Chart(document.getElementById('comboChart'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($data['labels']); ?>,
                        datasets: [
                            {
                                label: 'Orders',
                                data: <?php echo json_encode($data['orders']); ?>,
                                backgroundColor: '#f8c8dc',
                                borderColor: '#f4a6c1',
                                borderWidth: 1,
                                borderRadius: 8,
                                barThickness: 28
                            },
                            {
                                label: 'Revenue',
                                data: <?php echo json_encode($data['sales']); ?>,
                                backgroundColor: '#b0d3fa',
                                borderColor: '#9ec5f3',
                                borderWidth: 1,
                                borderRadius: 8,
                                barThickness: 28,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options:{
                        responsive:true,
                        maintainAspectRatio:false,
                        plugins:{
                            legend:{ position:'top' },
                            tooltip:{
                                callbacks:{
                                    label:function(ctx){
                                        if(ctx.dataset.label === 'Revenue'){
                                            return '₹' + ctx.raw.toLocaleString();
                                        }
                                        return ctx.raw + ' orders';
                                    }
                                }
                            }
                        },
                        scales:{
                            y:{
                                beginAtZero:true,
                                grid:{ color:'rgba(0,0,0,0.05)' },
                                title:{ display:true, text:'Orders'}
                            },
                            y1:{
                                beginAtZero:true,
                                position:'right',
                                grid:{ drawOnChartArea:false },
                                title:{ display:true, text:'Revenue'},
                                ticks:{
                                    callback:function(value){
                                        if(value>=1000000) return value/1000000+'M';
                                        if(value>=1000) return value/1000+'K';
                                        return value;
                                    }
                                }
                            }
                        }
                    }
                });

                /* ===== Shift Chart ===== */
                const shiftLabels = <?php echo json_encode($shift_data['labels']); ?>;
                const shiftSales  = <?php echo json_encode($shift_data['sales']); ?>;

                const shiftUsers = <?php echo json_encode($shift_data['users']); ?>;
                const shiftOrders = <?php echo json_encode($shift_data['orders_arr']); ?>;
                const shiftStatuses = <?php echo json_encode($shift_data['statuses']); ?>;
                const shiftClosingTimes = <?php echo json_encode($shift_data['closing_times']); ?>;
                const shiftCounts = <?php echo json_encode($shift_data['shift_counts']); ?>;
                const currentRange = "<?php echo $shift_range; ?>";


                new Chart(document.getElementById('shiftChart'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($shift_data['labels']); ?>,
                        datasets: [
                            {
                                label: 'Orders',
                                data: <?php echo json_encode($shift_data['orders_arr']); ?>,
                                backgroundColor: '#f8c8dc',
                                borderColor: '#f4a6c1',
                                borderWidth: 1,
                                borderRadius: 8,
                                barThickness: 20
                            },
                            {
                                label: 'Revenue',
                                data: <?php echo json_encode($shift_data['sales']); ?>,
                                backgroundColor: '#b0d3fa',
                                borderColor: '#9ec5f3',
                                borderWidth: 1,
                                borderRadius: 8,
                                barThickness: 20,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options:{
                        responsive:true,
                        maintainAspectRatio:false,
                        plugins:{
                            legend:{ display:false },
                            tooltip:{
                                callbacks:{
                                    title:function(ctx){
                                        return shiftUsers[ctx[0].dataIndex];
                                    },
                                    label: function(ctx){
                                        let i = ctx.dataIndex;

                                        if(ctx.dataset.label === 'Revenue'){
                                            return 'Revenue: ₹' + ctx.raw.toLocaleString();
                                        }

                                        // always show orders & shifts
                                        let lines = [
                                            'Orders: ' + shiftOrders[i],
                                            'Shifts: ' + shiftCounts[i]
                                        ];

                                        // show extra info ONLY for daily view
                                        if(currentRange === 'daily'){
                                            lines.push('Status: ' + shiftStatuses[i]);
                                            lines.push('Closing Time: ' + shiftClosingTimes[i]);
                                        }

                                        return lines;
                                    }

                                }
                            },

                        },
                        scales:{
                            y:{
                                beginAtZero:true,
                                title:{ display:true, text:'Orders'}
                            },
                            y1:{
                                beginAtZero:true,
                                position:'right',
                                grid:{ drawOnChartArea:false },
                                title:{ display:true, text:'Revenue'},
                                ticks:{
                                    callback:function(value){
                                        if(value>=1000000) return value/1000000+'M';
                                        if(value>=1000) return value/1000+'K';
                                        return value;
                                    }
                                }
                            }
                        }
                    }
                });


                // const picker = document.getElementById('shiftDatePicker');
                // if (picker) {
                //     picker.addEventListener('change', function () {
                //         document.getElementById('shiftFilterForm').submit();
                //     });
                // }

            });
        </script>

         <?php
    }

    /* ===============================
       ORDERS & SALES DATA
    =============================== */

    private function get_reports_data($range){

        $labels = [];
        $orders = [];
        $sales  = [];

        switch($range){

            case 'daily':
                $start = strtotime('monday this week');
                for($i=0;$i<7;$i++){
                    $day = date('Y-m-d', strtotime("+$i day",$start));
                    $row = $this->get_orders_sales($day,$day);
                    $labels[] = date('D',strtotime($day));
                    $orders[] = $row['orders'];
                    $sales[]  = $row['sales'];
                }
            break;

            case 'weekly':
                $year=date('Y'); $month=date('m'); $days=date('t');
                $week=1; $start_day=1;
                while($start_day<=$days){
                    $start="$year-$month-$start_day";
                    $end_day=min($start_day+6,$days);
                    $end="$year-$month-$end_day";
                    $row=$this->get_orders_sales($start,$end);
                    $labels[]="Week ".$week;
                    $orders[]=$row['orders'];
                    $sales[]=$row['sales'];
                    $start_day+=7; $week++;
                }
            break;

            case 'monthly':
                $year=date('Y');
                for($m=1;$m<=12;$m++){
                    $start=date("$year-$m-01");
                    $end=date("Y-m-t",strtotime($start));
                    $row=$this->get_orders_sales($start,$end);
                    $labels[]=date('M',strtotime($start));
                    $orders[]=$row['orders'];
                    $sales[]=$row['sales'];
                }
            break;
        }

        return compact('labels','orders','sales');
    }

    private function get_orders_sales($start,$end){
        $orders = wc_get_orders([
            'status'=>['wc-completed','wc-processing'],
            'date_created'=>$start.'...'.$end,
            'limit'=>-1
        ]);

        $total=0; $count=0;
        foreach($orders as $order){
            $total += $order->get_total();
            $count++;
        }

        return ['orders'=>$count,'sales'=>$total];
    }

    /* ===============================
       SHIFT DATA
    =============================== */

    private function get_shift_chart_data($range){

        $user_totals = [];

        $total_orders = 0;
        $total_revenue = 0;

        $labels = [];
        $orders_arr = [];
        $sales = [];
        $users = [];
        $statuses = [];
        $closing_times = [];
        $shift_counts = [];

        $is_daily_view = ($range === 'daily');

        // determine date range
        switch($range){
            case 'daily':
                $start = date('Y-m-d 00:00:00');
                $end   = date('Y-m-d 23:59:59');
                break;

            case 'weekly':
                $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $end   = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                break;

            case 'monthly':
                $start = date('Y-m-01 00:00:00');
                $end   = date('Y-m-t 23:59:59');
                break;
        }

        $posts = get_posts([
            'post_type'      => 'shifts',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'date_query'     => [[
                'after'  => $start,
                'before' => $end,
                'inclusive' => true,
            ]]
        ]);

        foreach($posts as $shift){

            $shift_id = $shift->ID;

            $user = get_userdata($shift->post_author);
            $username = $user ? $user->display_name : 'Unknown';

            $status = get_post_meta($shift_id, '_shift_status', true);
            $close_time = get_post_meta($shift_id, '_shift_end_time', true);

            $orders = wc_get_orders([
                'limit' => -1,
                'return' => 'ids',
                'meta_query' => [[
                    'key'   => 'shift_id',
                    'value' => (string)$shift_id,
                ]],
            ]);

            $order_count = count($orders);
            $shift_sales = 0;

            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $shift_sales += (float)$order->get_total();
                }
            }

            $total_orders += $order_count;
            $total_revenue += $shift_sales;

            // ✅ DAILY → each shift is one bar
            if($is_daily_view){

                $labels[] = $username;
                $users[]  = $username;
                $orders_arr[] = $order_count;
                $sales[] = $shift_sales;
                $shift_counts[] = 1;

                $statuses[] = ucfirst($status ?: 'Unknown');

                $closing_times[] = $close_time
                    ? date('d M Y, h:i A', strtotime($close_time))
                    : '-';

            } else {

                // ✅ WEEKLY / MONTHLY → group by user
                if(!isset($user_totals[$username])){
                    $user_totals[$username] = [
                        'orders' => 0,
                        'sales'  => 0,
                        'shift_count' => 0,
                        'last_close' => '',
                        'current_status' => 'Unknown'
                    ];
                }

                $user_totals[$username]['orders'] += $order_count;
                $user_totals[$username]['sales']  += $shift_sales;
                $user_totals[$username]['shift_count']++;

                $user_totals[$username]['current_status'] =
                    ucfirst($status ?: 'Unknown');

                if($close_time){
                    $user_totals[$username]['last_close'] =
                        date('d M Y, h:i A', strtotime($close_time));
                }
            }
        }

        // build grouped data for week/month
        if(!$is_daily_view){
            foreach($user_totals as $user => $data){
                $labels[] = $user;
                $users[]  = $user;
                $orders_arr[] = $data['orders'];
                $sales[]  = $data['sales'];
                $shift_counts[] = $data['shift_count'];
                $statuses[] = $data['current_status'];
                $closing_times[] = $data['last_close'] ?: '-';
            }
        }

        return compact(
            'labels',
            'sales',
            'users',
            'orders_arr',
            'shift_counts',
            'statuses',
            'closing_times',
            'total_orders',
            'total_revenue'
        );
    }

}

new Pinaka_Reports();
