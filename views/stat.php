<div class="wrap">
 <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

<div style="width: 1000px; border: 0px solid #ccc;">
<center>
    <strong><?php echo self::$message_graph_title; ?></strong>
    <span id="chartRangeFilter_message"></span>
</center>
</div>

<div id="chartRangeFilter_dashboard_div" style="width: 1000px; height: 650px;">
    <div id="chartRangeFilter_chart_cpu_div" style="width: 1000px; height: 200px; border: 1px solid #ccc;"></div>
    <div id="chartRangeFilter_chart_disk_div" style="width: 1000px; height: 200px; border: 1px solid #ccc;"></div>
    <div id="chartRangeFilter_chart_db_div" style="width: 1000px; height: 200px; border: 1px solid #ccc;"></div>
    <div id="chartRangeFilter_control_div" style="width: 1000px; height: 50px; border: 1px solid #ccc;"></div>
</div>

<script type="text/javascript">
  google.charts.load('current', {packages:['corechart', 'table', 'gauge', 'controls'], 'language': '<?php self::echo_current_locale();?>'});
  google.charts.setOnLoadCallback(drawChartRangeFilter);

  function drawChartRangeFilter() {
    var dashboard = new google.visualization.Dashboard(
        document.getElementById('chartRangeFilter_dashboard_div'));

    var control = new google.visualization.ControlWrapper({
      'controlType': 'ChartRangeFilter',
      'containerId': 'chartRangeFilter_control_div',
      'options': {
        'filterColumnIndex': 0,
        'ui': {
          'chartType': 'LineChart',
          'chartOptions': {
            'chartArea': {'width': '90%'},
            'hAxis': {'baselineColor': 'none'},
            'interpolateNulls' : true,
            'colors': ['purple','green','blue']
          },
          'minRangeSize': 7200000   // 2 hours
        },
      },
      'state': {'range': {'start': new Date(Date.now()-43200000), 'end': new Date()}}   // 6 hours
    });

    var chartCPU = new google.visualization.ChartWrapper({
      'chartType': 'LineChart',
      'containerId': 'chartRangeFilter_chart_cpu_div',
      'options': {
        'chartArea': {'height': '70%', 'width': '90%'},
        'hAxis': {'slantedText': false},
        'interpolateNulls' : true,
        'pointSize' : 2,
        'pointsVisible' : true,
        'legend': { position: 'top' },
        'colors': [ 'gray', 'purple'],
        tooltip: {isHtml: true}
      },
      'view': {
        'columns': [0,1,2,3,4]
      }
    });
    var chartDisk = new google.visualization.ChartWrapper({
      'chartType': 'LineChart',
      'containerId': 'chartRangeFilter_chart_disk_div',
      'options': {
        'chartArea': {'height': '70%', 'width': '90%'},
        'hAxis': {'slantedText': false},
        'interpolateNulls' : true,
        'pointSize' : 2,
        'pointsVisible' : true,
        'legend': { position: 'top' },
        'colors': [ 'gray', 'green'],
        tooltip: {isHtml: true}
      },
      'view': {
        'columns': [0,5,6,7,8]
      }
    });

    var chartDb = new google.visualization.ChartWrapper({
      'chartType': 'LineChart',
      'containerId': 'chartRangeFilter_chart_db_div',
      'options': {
        'chartArea': {'height': '70%', 'width': '90%'},
        'hAxis': {'slantedText': false},
        'interpolateNulls' : true,
        'pointSize' : 2,
        'pointsVisible' : true,
        'legend': { position: 'top' },
        'colors': [ 'gray', 'blue'],
        tooltip: {isHtml: true}
      },
      'view': {
        'columns': [0,9,10,11,12]
      }
    });

    var dataTable = new google.visualization.DataTable();
    dataTable.addColumn('datetime', '<?php echo self::$message_time;?>');
    dataTable.addColumn('number', '<?php echo self::$message_cpu . ' ' . self::$message_avg;?>');
    dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
    dataTable.addColumn('number', '<?php echo self::$message_cpu . ' ' . self::$message_current;?>');
    dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
    dataTable.addColumn('number', '<?php echo self::$message_disk . ' ' . self::$message_avg;?>');
    dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
    dataTable.addColumn('number', '<?php echo self::$message_disk . ' ' . self::$message_current;?>');
    dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
    dataTable.addColumn('number', '<?php echo self::$message_db . ' ' . self::$message_avg;?>');
    dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
    dataTable.addColumn('number', '<?php echo self::$message_db . ' ' . self::$message_current;?>');
    dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
    dataTable.addRows([
 <?php self::echo_graph_data(); ?>
    ]);
    dashboard.bind(control, [chartCPU, chartDisk, chartDb]);
    dashboard.draw(dataTable);
    if (dataTable && dataTable.getNumberOfRows() < 9) {
        document.getElementById("chartRangeFilter_message").innerHTML = "<br /><?php echo self::$message_graph_subtitle; ?>";
    }

  }
</script>
<?php
$hardware = HostingStabilityMeterBenchmarks::create_server_info_array();
if ( isset( $hardware ) && (
        isset( $hardware[ 'php' ][ 'os' ] ) ||
        isset( $hardware[ 'mem' ][ 'total' ] ) ||
        isset( $hardware[ 'cpu' ][ 'model_name' ] )
     )
) {
    $hardware_display = array();
    if ( isset( $hardware[ 'php' ][ 'os' ] ) ) {
        $hardware_display_item = $hardware[ 'php' ][ 'os' ];
        $hardware_display_item .= ' ' . $hardware[ 'php' ][ 'uname' ][ 'r' ];
        $hardware_display_item .= ' ' . $hardware[ 'php' ][ 'uname' ][ 'm' ];
        $hardware_display[] = [ self::$message_os, $hardware_display_item ];
    }
    if ( isset( $hardware[ 'cpu' ][ 'model_name' ] ) ) {
        $hardware_display_item = $hardware[ 'cpu' ][ 'model_name' ];
        if ( isset( $hardware[ 'cpu' ][ 'cores' ] ) ) {
            if ( $hardware[ 'cpu' ][ 'cores' ] > 1 ) {
                $hardware_display_item .= ', ' . $hardware[ 'cpu' ][ 'cores' ] . ' ' . self::$message_cores;
            } else {
                $hardware_display_item .= ', ' . $hardware[ 'cpu' ][ 'cores' ] . ' ' . self::$message_core;
            }
        }
        $hardware_display[] = [ self::$message_cpu, ' ' . $hardware_display_item ];
    }
    if ( isset( $hardware[ 'mem' ][ 'total' ] ) ) {
        $hardware_display[] = [ self::$message_ram, ' ' . $hardware[ 'mem' ][ 'total' ] ];
    }
?>
<div style="width: 1000px; border: 0px solid #ccc;">
<center>
    <br />
    <strong><?php echo self::$message_this_server;?>:</strong>
<?php foreach ( $hardware_display as $hardware_display_row ) :?>
    <strong>&nbsp;&nbsp;<?php echo $hardware_display_row[ 0 ]; ?></strong>
    <?php echo $hardware_display_row[ 1 ]; ?>
<?php endforeach;?>
</center>
</div>
<?php
}
?>
<br />
<form action="options.php" method="post">
<?php
 settings_fields( HostingStabilityMeter::SETTINGS_GROUP );
 do_settings_sections( self::SLUG_NAME );
 submit_button();
?>
</form>
</div>
