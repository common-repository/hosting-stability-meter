<?php
/*
  Plugin Name: Hosting Stability Meter
  Plugin URI: https://hostingstabilitymeter.com/about/wordpress_plugin
  Description: The plugin measures benchmarks stability of your hosting server in time. Detailed graph is useful to see hosting performance lacks as well as to let you know hosting is good or bad.
  Version: 1.0.1
  Author: HostingStabilityMeter team
  Author URI: https://hostingstabilitymeter.com
  Text Domain: hosting-stability-meter
  Network: true
*/

if ( is_main_site() && is_main_network() ) {
    define( 'HOSTING_STABILITY_METER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

    require_once( HOSTING_STABILITY_METER_PLUGIN_DIR . 'class.hosting-stability-meter-benchmarks.php' );
    require_once( HOSTING_STABILITY_METER_PLUGIN_DIR . 'class.hosting-stability-meter.php' );

    register_activation_hook( __FILE__, array( 'HostingStabilityMeter', 'plugin_activation' ) );
    register_deactivation_hook( __FILE__, array( 'HostingStabilityMeter', 'plugin_deactivation' ) );

    add_action( 'init', array( 'HostingStabilityMeter', 'init' ) );

    if ( is_admin() ) {
        require_once( HOSTING_STABILITY_METER_PLUGIN_DIR . 'class.hosting-stability-meter-admin.php' );
        add_action( 'init', array( 'HostingStabilityMeterAdmin', 'init' ) );
    }
}
