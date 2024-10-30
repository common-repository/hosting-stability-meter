<?php

/**
 * Class HostingStabilityMeterAdmin
 * Admin page of the plugin for WordPress
 * @version       1.0
 * @author        HostingStabilityMeter team (welcome@hostingstabilitymeter.com)
 * @copyright (C) 2020 HostingStabilityMeter team (https://hostingstabilitymeter.com)
 * @license       GPLv2 or later: http://www.gnu.org/licenses/gpl-2.0.html
 * @see           https://hostingstabilitymeter.com/about/wordpress_plugin
 */

class HostingStabilityMeterAdmin {
        const SLUG_NAME = 'hosting-stability-meter';
        const SETTINGS_MAIN_ALLOWSEND = HostingStabilityMeter::SETTINGS_MAIN . '_' . HostingStabilityMeter::NAME_ALLOWSEND;
        const SETTINGS_MAIN_ALLOWMAIL = HostingStabilityMeter::SETTINGS_MAIN . '_' . HostingStabilityMeter::NAME_ALLOWMAIL;
        const SETTINGS_MAIN_MAILADDR = HostingStabilityMeter::SETTINGS_MAIN . '_' . HostingStabilityMeter::NAME_MAILADDR;
        const SETTINGS_MAIN_TH_CPU = HostingStabilityMeter::SETTINGS_MAIN . '_' . HostingStabilityMeter::NAME_TH_CPU;
        const SETTINGS_MAIN_TH_DISK = HostingStabilityMeter::SETTINGS_MAIN . '_' . HostingStabilityMeter::NAME_TH_DISK;
        const SETTINGS_MAIN_TH_DB = HostingStabilityMeter::SETTINGS_MAIN . '_' . HostingStabilityMeter::NAME_TH_DB;
        const SETTINGS_MAIN_INTERVAL_TEST = HostingStabilityMeter::SETTINGS_MAIN . '_' . HostingStabilityMeter::NAME_INTERVAL_TEST;
        const ERROR_MESSAGES_ID = 'hostingstabilitymeter_messages';
        const GRAPH_SQL_INTERVAL = '7 day';
        const GRAPH_SQL_LIMIT = '600';  // ~ 3 tests * 24 hours * 7 days
        const HSM_LINK =  '<a target="_blank" href="https://HostingStabilityMeter.Com">HostingStabilityMeter.Com</a>';
    
        public static $message_hsm;
        public static $message_yes;
        public static $message_no;
        public static $message_os;
        public static $message_ram;
        public static $message_this_server;
        public static $message_settings;
        public static $message_email_address;
        public static $message_bad_param_general;
        public static $message_bad_email_ignored;
        public static $message_bad_allow;
        public static $message_bad_theshold;
        public static $message_bad_interval_test;
        public static $message_settings_saved;
        public static $message_current;
        public static $message_avg;
        public static $message_loss;
        public static $message_excess;
        public static $message_speed_loss_threshold_cpu;
        public static $message_speed_loss_threshold_disk;
        public static $message_speed_loss_threshold_db;
        public static $message_benchmarks_interval_test;
        public static $message_inform_admin_on_threshold;
        public static $message_inform_admin_on_threshold_descr;
        public static $message_send_stats;
        public static $message_send_stats_descr1;
        public static $message_send_stats_descr2;
        public static $message_time;
        public static $message_cores;
        public static $message_core;
        public static $message_threshold;
        public static $message_threshold_reached;
        public static $message_cpu;
        public static $message_disk;
        public static $message_db;
        public static $message_graph_title;
        public static $message_graph_subtitle;
        public static $message_donate;
        public static $message_donate_a;

	private static $initiated = false;
        
        /**
	 * Init hook - sets all hooks, actions, filters, schedules, vars
	 * @static
	 */
	public static function init() {
		if ( ! self::$initiated ) {
			self::$initiated = true;
                        add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		}
        }

        /**
         * Inits admin page data.
	 * @static
         */
	public static function admin_init() {
                register_setting( HostingStabilityMeter::SETTINGS_GROUP, HostingStabilityMeter::SETTINGS_MAIN, array( __CLASS__, 'validate_settings_section_main' ) );
	}

        /**
         * Adds a submenu page under a custom post type parent.
	 * @static
         */
	public static function admin_menu() {
                self::$message_hsm = __( 'Hosting Stability Meter', 'hosting-stability-meter' );
		$page = add_submenu_page(
                        'tools.php',
                        self::$message_hsm,
                        self::$message_hsm,
                        'administrator',
                        self::SLUG_NAME,
                        array( __CLASS__, 'display_page' ) );
                add_action( 'admin_print_scripts-' . $page, array( __CLASS__, 'admin_scripts' ) );
	}
        
        /**
         * Adds scripts to plugin's admin page.
	 * @static
         */
	public static function admin_scripts() {
                wp_enqueue_script( self::SLUG_NAME . '-charts', 'https://www.gstatic.com/charts/loader.js' );
	}
        
        /**
         * Admin callback function - Displays description of 'main' plugin parameters section
         */
        function echo_settings_section_main( $args ){
            echo '';
        }

        /**
         * Admin callback function - Displays inputs of 'th_cpu' plugin parameter
         */
        function echo_settings_section_main_th_cpu() {
            $options = HostingStabilityMeter::get_settings();
            $value = $options[ HostingStabilityMeter::NAME_TH_CPU ];

            echo "<input type='text' id='hostingstabilitymeter_th_cpu' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_TH_CPU . "]' value='" . $value . "' size=4 maxlength=4 > %";
        }
        
        /**
         * Admin callback function - Displays inputs of 'th_disk' plugin parameter
         */
        function echo_settings_section_main_th_disk() {
            $options = HostingStabilityMeter::get_settings();
            $value = $options[ HostingStabilityMeter::NAME_TH_DISK ];

            echo "<input type='text' id='hostingstabilitymeter_th_disk' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_TH_DISK . "]' value='" . $value . "' size=4 maxlength=4 > %";
        }
        
        /**
         * Admin callback function - Displays inputs of 'th_db' plugin parameter
         */
        function echo_settings_section_main_th_db() {
            $options = HostingStabilityMeter::get_settings();
            $value = $options[ HostingStabilityMeter::NAME_TH_DB ];

            echo "<input type='text' id='hostingstabilitymeter_th_db' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_TH_DB . "]' value='" . $value . "' size=4 maxlength=4 > %";
        }
        
        /**
         * Admin callback function - Displays inputs of 'interval_test' plugin parameter
         */
        function echo_settings_section_main_interval_test() {
            $options = HostingStabilityMeter::get_settings();
            $value = $options[ HostingStabilityMeter::NAME_INTERVAL_TEST ];

            echo "<input type='text' id='hostingstabilitymeter_interval_test' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_INTERVAL_TEST . "]' value='" . $value . "' size=3 maxlength=3 > ";
        }

        /**
         * Admin callback function - Displays inputs of 'allowsend' plugin parameter
         */
        function echo_settings_section_main_allow_send() {
            $options = HostingStabilityMeter::get_settings();
            $value = $options[ HostingStabilityMeter::NAME_ALLOWSEND ];

            echo "<input type='radio' id='hostingstabilitymeter_allowsend1' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_ALLOWSEND . "]' value='1' " . ( $value == '1' ? 'checked' : '' ) . " /><label for='hostingstabilitymeter_allowsend1'>" . self::$message_yes . "</label>";
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            echo "<input type='radio' id='hostingstabilitymeter_allowsend0' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_ALLOWSEND . "]' value='0' " . ( $value == '0' ? 'checked' : '' ) . " /><label for='hostingstabilitymeter_allowsend0'>" . self::$message_no . "</label>";
            self::echo_field_description( self::$message_send_stats_descr1
                    . '<br />' . sprintf( self::$message_send_stats_descr2, self::HSM_LINK)
                    . '<br /><br />' . self::$message_donate . ' : ' . self::$message_donate_a
            );
        }

        /**
         * Admin callback function - Displays inputs of 'allowmail' plugin parameter
         */
        function echo_settings_section_main_allow_mail() {
            $options = HostingStabilityMeter::get_settings();
            $value = $options[ HostingStabilityMeter::NAME_ALLOWMAIL ];

            echo "<input type='radio' id='hostingstabilitymeter_allowmail1' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_ALLOWMAIL . "]' value='1' " . ( $value == '1' ? 'checked' : '' ) . " /><label for='hostingstabilitymeter_allowmail1'>" . self::$message_yes . "</label>";
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            echo "<input type='radio' id='hostingstabilitymeter_allowmail0' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_ALLOWMAIL . "]' value='0' " . ( $value == '0' ? 'checked' : '' ) . " /><label for='hostingstabilitymeter_allowmail0'>" . self::$message_no . "</label>";
            self::echo_field_description( self::$message_inform_admin_on_threshold_descr );
        }

        /**
         * Admin callback function - Displays inputs of 'mailaddr' plugin parameter
         */
        function echo_settings_section_main_mail_addr() {
            $options = HostingStabilityMeter::get_settings();
            $value = $options[ HostingStabilityMeter::NAME_MAILADDR ];

            echo "<input type='email' id='hostingstabilitymeter_mailaddr' name='" . HostingStabilityMeter::SETTINGS_MAIN . "[" . HostingStabilityMeter::NAME_MAILADDR . "]' value='" . $value . "' size=30 maxlength=50>";
            self::echo_field_description( self::$message_email_address );
        }

        /**
         * Add descriptions for field
         */
        function echo_field_description( $descr = '' ) {
            echo "<div style='font-size: 90%; color: #666 !important'>$descr</div>";
        }
        
        /**
         * Admin callback function - Validates the 'main' plugin parameters section
         */
        function validate_settings_section_main( $input ) {
            if ( is_array( $input ) ) {
                $error_code = HostingStabilityMeter::sanitize_settings( $input );
                if ( $error_code !== FALSE ) {
                    self::$message_bad_param_general = __( 'Someting wrong with parameter', 'hosting-stability-meter' );
                    self::$message_bad_email_ignored = __( 'Bad e-mail address ignored', 'hosting-stability-meter' );
                    self::$message_bad_allow = __( 'Bad "allow" parameter ignored, set to default', 'hosting-stability-meter' );
                    self::$message_bad_theshold = __( 'Bad theshold parameter ignored, set to zero', 'hosting-stability-meter' );
                    self::$message_bad_interval_test = __( 'Bad interval parameter ignored, set to default', 'hosting-stability-meter' );
                    $error_text = self::$message_bad_param_general;
                    switch ( $error_code ) {
                        case HostingStabilityMeter::ERROR_CODE_BAD_EMAIL :
                            $error_text = self::$message_bad_email_ignored;
                            break;
                        case HostingStabilityMeter::ERROR_CODE_BAD_ALLOW :
                            $error_text = self::$message_bad_allow;
                            break;
                        case HostingStabilityMeter::ERROR_CODE_BAD_THRESHOLD :
                            $error_text = self::$message_bad_theshold;
                            break;
                        case HostingStabilityMeter::ERROR_CODE_BAD_INTERVAL_TEST :
                            $error_text = self::$message_bad_interval_test;
                            break;
                    }
                    add_settings_error( self::ERROR_MESSAGES_ID, self::SLUG_NAME, $error_text, 'error' );
                }
            } else {
                $input = HostingStabilityMeter::SETTINGS_DEFAULTS;
            }
            self::$message_settings_saved = __( 'Settings saved', 'hosting-stability-meter' );
            add_settings_error( self::ERROR_MESSAGES_ID, self::SLUG_NAME, self::$message_settings_saved, 'updated' );
            return $input;
        }

        /**
         * Displays corrent WP locale
         */
        function echo_current_locale() {
            $loc = get_locale();
            if ( isset( $loc ) ) {
                $loc = substr($loc, 0, 2);
            } else {
                $loc = 'en';
            }
            echo $loc;
        }
        
        /**
         * Display tests statistics in Google Charts format.
         */
        function echo_graph_data() {
            global $wpdb;
            
            $data_rows = $wpdb->get_results(
                    'SELECT DATE_FORMAT(dtime, "%Y %m %d %H %i %s") as time,'
                    . ' DATE_FORMAT(dtime, "%Y-%m-%d %H:%i:%s") as time_read,'
                    . ' test_name, speed_this, speed_avg_previous,'
                    . ' threshold_value, threshold_delta, threshold_over'
                    . ' FROM ( SELECT dtime, test_name, speed_this,'
                    . ' speed_avg_previous, threshold_value, threshold_delta, threshold_over'
                    . ' FROM ' . $wpdb->prefix . HostingStabilityMeter::STAT_TABLE_NAME
                    . ' WHERE dtime > now() - interval ' . self::GRAPH_SQL_INTERVAL
                    . ' order by dtime desc limit ' . self::GRAPH_SQL_LIMIT
                    . ') as s order by dtime;'
            );
            $graph_data_string = '';
            $time_format = get_option( 'date_format' ) . '  ' . get_option( 'time_format' );
            $data_graph_tooltip_template_start = "'<table>";
            $data_graph_tooltip_template_1td = "<tr><td colspan=2>%s</td></tr>";
            $data_graph_tooltip_template_2td = "<tr><td>%s</td><td>%s</td></tr>";
            $data_graph_tooltip_template_end = "</table>'";
            foreach ( $data_rows as $data_row ) {
                if ( $graph_data_string != '' ) {
                    $graph_data_string .= ",\n ";
                }
                if ( ! isset( $data_row->time ) || ! isset( $data_row->test_name ) || ! isset( $data_row->speed_this ) ) continue;
                $data_time = explode( ' ', $data_row->time );
                if ( count( $data_time ) < 6 ) continue;
                unset( $data_graph_template );
                switch ( $data_row->test_name ) {
                    case 'cpu' :
                        $data_graph_template = '[new Date(%d,%d,%d,%d,%d,%d), %s, %s, %s, %s, null, null, null, null, null, null, null, null]';
                        break;
                    case 'disk' :
                        $data_graph_template = '[new Date(%d,%d,%d,%d,%d,%d), null, null, null, null, %s, %s, %s, %s, null, null, null, null]';
                        break;
                    case 'db' :
                        $data_graph_template = '[new Date(%d,%d,%d,%d,%d,%d), null, null, null, null, null, null, null, null, %s, %s, %s, %s]';
                        break;
                }
                if ( ! isset( $data_graph_template )) continue;
                
                $datetime_obj = date_create( $data_row->time_read );
                if ( is_object( $datetime_obj ) ) {
                    $data_graph_datetime = date_i18n( $time_format, $datetime_obj->getTimestamp() );
                } else {
                    $data_graph_datetime = $data_row->time_read;
                }
                $data_graph_tooltip_current = $data_graph_tooltip_template_start
                        . sprintf( $data_graph_tooltip_template_1td, $data_graph_datetime )
                        . sprintf( $data_graph_tooltip_template_2td, self::$message_current, sprintf( '%.2f', $data_row->speed_this ) )
                        . sprintf( $data_graph_tooltip_template_2td, self::$message_avg, sprintf( '%.2f', $data_row->speed_avg_previous ) );
                if ($data_row->threshold_delta > 0) {
                    $data_graph_tooltip_current .= sprintf( $data_graph_tooltip_template_2td, self::$message_loss, sprintf( '%.2f%%', $data_row->threshold_delta ) );
                    $data_graph_tooltip_current .= sprintf( $data_graph_tooltip_template_2td, self::$message_threshold, sprintf( '%.2f%%', $data_row->threshold_value ) );
                    if ( $data_row->threshold_over ) {
                        $data_graph_tooltip_current .= sprintf( $data_graph_tooltip_template_1td, '<span style="color:red;">' . self::$message_threshold_reached . '</span>' );
                    }
                } else {
                    $data_graph_tooltip_current .= sprintf( $data_graph_tooltip_template_2td, self::$message_excess, sprintf( '%.2f%%', abs( $data_row->threshold_delta ) ) );
                }
                $data_graph_tooltip_current .= $data_graph_tooltip_template_end;

                $data_graph_tooltip_avg = $data_graph_tooltip_template_start
                        . sprintf( $data_graph_tooltip_template_1td, $data_graph_datetime )
                        . sprintf( $data_graph_tooltip_template_2td, self::$message_avg, sprintf( '%.2f', $data_row->speed_avg_previous ) );
                $data_graph_tooltip_avg .= $data_graph_tooltip_template_end;
                
                $graph_data_string .= sprintf(
                    $data_graph_template,    
                    $data_time[0], $data_time[1] - 1, $data_time[2], $data_time[3], $data_time[4], $data_time[5],
                    $data_row->speed_avg_previous,
                    $data_graph_tooltip_avg,
                    $data_row->speed_this,
                    $data_graph_tooltip_current
                );
            }
            echo $graph_data_string;
        }

        /**
         * Display callback for the submenu page.
         */
        function display_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            self::$message_yes = __( 'Yes' );
            self::$message_no = __( 'No' );
            self::$message_os = __( 'OS', 'hosting-stability-meter' );
            self::$message_ram = __( 'RAM', 'hosting-stability-meter' );
            self::$message_this_server = __( 'This server', 'hosting-stability-meter' );
            self::$message_settings = __( 'Settings', 'hosting-stability-meter' );
            self::$message_email_address = __( 'E-mail address to send if differs from admin`s one. Leave empty if not needed.', 'hosting-stability-meter' );
            self::$message_current = __( 'Current', 'hosting-stability-meter' );
            self::$message_avg = __( 'Average', 'hosting-stability-meter' );
            self::$message_loss = __( 'Loss', 'hosting-stability-meter' );
            self::$message_excess = __( 'Excess', 'hosting-stability-meter' );
            self::$message_speed_loss_threshold_cpu = __( 'CPU speed loss threshold', 'hosting-stability-meter' );
            self::$message_speed_loss_threshold_disk = __( 'Disk speed loss threshold', 'hosting-stability-meter' );
            self::$message_speed_loss_threshold_db = __( 'Database speed loss threshold', 'hosting-stability-meter' );
            self::$message_benchmarks_interval_test = __( 'Interval between benchmark calls in minutes', 'hosting-stability-meter' );
            self::$message_inform_admin_on_threshold = __( 'Inform admin on threshold reached', 'hosting-stability-meter' );
            self::$message_inform_admin_on_threshold_descr = __( 'Send e-mail to site admin if benchmark threshold reached.', 'hosting-stability-meter' );
            self::$message_send_stats = __( 'Send statistics to the HostingStabilityMeter.com once a day', 'hosting-stability-meter' );
            self::$message_send_stats_descr1 = __( 'Your benchmarks results, hardware information, IP address and hostname will be used for aggregation only.', 'hosting-stability-meter' );
            self::$message_send_stats_descr2 = __( '%s will never publish or give somebody this information.', 'hosting-stability-meter' );
            self::$message_time = __( 'Time', 'hosting-stability-meter' );
            self::$message_cores = __( 'cores', 'hosting-stability-meter' );
            self::$message_core = __( 'core', 'hosting-stability-meter' );
            self::$message_threshold = __( 'Threshold', 'hosting-stability-meter' );
            self::$message_threshold_reached = __( 'Threshold reached!', 'hosting-stability-meter' );
            self::$message_cpu = __( 'CPU', 'hosting-stability-meter' );
            self::$message_disk = __( 'Disk', 'hosting-stability-meter' );
            self::$message_db = __( 'Database', 'hosting-stability-meter' );
            self::$message_graph_title = __( 'Benchmarks speed, cycles/sec', 'hosting-stability-meter' );
            self::$message_graph_subtitle = __( 'To view beautiful graphs, wait 2-3 hours (until each benchmark is run several times)', 'hosting-stability-meter' );
            self::$message_donate =  'Donate link';
            self::$message_donate_a =  '<a target="_blank" href="https://www.patreon.com/HostingStabilityMeter">https://www.patreon.com/HostingStabilityMeter</a>';

            add_settings_section( HostingStabilityMeter::SETTINGS_MAIN, self::$message_settings, array( __CLASS__, 'echo_settings_section_main' ), self::SLUG_NAME );

            add_settings_field(
                    self::SETTINGS_MAIN_TH_CPU,
                    self::$message_speed_loss_threshold_cpu, 
                    array( __CLASS__, 'echo_settings_section_main_th_cpu' ),
                    self::SLUG_NAME, 
                    HostingStabilityMeter::SETTINGS_MAIN, 
                    [ 'label_for' => HostingStabilityMeter::NAME_TH_CPU ]
            );
            add_settings_field(
                    self::SETTINGS_MAIN_TH_DISK,
                    self::$message_speed_loss_threshold_disk,
                    array( __CLASS__, 'echo_settings_section_main_th_disk' ),
                    self::SLUG_NAME, 
                    HostingStabilityMeter::SETTINGS_MAIN, 
                    [ 'label_for' => HostingStabilityMeter::NAME_TH_DISK ]
            );
            add_settings_field(
                    self::SETTINGS_MAIN_TH_DB,
                    self::$message_speed_loss_threshold_db,
                    array( __CLASS__, 'echo_settings_section_main_th_db' ),
                    self::SLUG_NAME, 
                    HostingStabilityMeter::SETTINGS_MAIN, 
                    [ 'label_for' => HostingStabilityMeter::NAME_TH_DB ]
            );

            add_settings_field(
                    self::SETTINGS_MAIN_INTERVAL_TEST,
                    self::$message_benchmarks_interval_test,
                    array( __CLASS__, 'echo_settings_section_main_interval_test' ),
                    self::SLUG_NAME, 
                    HostingStabilityMeter::SETTINGS_MAIN, 
                    [ 'label_for' => HostingStabilityMeter::NAME_INTERVAL_TEST ]
            );

            add_settings_field(
                    self::SETTINGS_MAIN_ALLOWMAIL, 
                    self::$message_inform_admin_on_threshold,
                    array( __CLASS__, 'echo_settings_section_main_allow_mail' ),
                    self::SLUG_NAME, 
                    HostingStabilityMeter::SETTINGS_MAIN, 
                    [ 'label_for' => HostingStabilityMeter::NAME_ALLOWMAIL ]
            );
            add_settings_field(
                    self::SETTINGS_MAIN_MAILADDR,
                    '',
                    array( __CLASS__, 'echo_settings_section_main_mail_addr' ),
                    self::SLUG_NAME, 
                    HostingStabilityMeter::SETTINGS_MAIN, 
                    [ 'label_for' => HostingStabilityMeter::NAME_MAILADDR ]
            );

            add_settings_field(
                    self::SETTINGS_MAIN_ALLOWSEND, 
                    self::$message_send_stats,
                    array( __CLASS__, 'echo_settings_section_main_allow_send' ),
                    self::SLUG_NAME, 
                    HostingStabilityMeter::SETTINGS_MAIN, 
                    [ 'label_for' => HostingStabilityMeter::NAME_ALLOWSEND ]
            );

            settings_errors( self::ERROR_MESSAGES_ID );
            
            include( HOSTING_STABILITY_METER_PLUGIN_DIR . 'views/stat.php' );
        }
}
