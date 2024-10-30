<?php

/**
 * Class HostingStabilityMeter
 * Base class of the plugin for WordPress
 * @version       1.0
 * @author        HostingStabilityMeter team (welcome@hostingstabilitymeter.com)
 * @copyright (C) 2020 HostingStabilityMeter team (https://hostingstabilitymeter.com)
 * @license       GPLv2 or later: http://www.gnu.org/licenses/gpl-2.0.html
 * @see           https://hostingstabilitymeter.com/about/wordpress_plugin
 */

class HostingStabilityMeter {
        const TEST_DURATION_MAX  = 0.7;         // in seconds
	const TEST_COUNT_MAX     = 1000000;	// big but defined

        const OPTION_NAME_TESTS_MADE = 'hostingstabilitymeter_tests_made';
        const OPTION_HOST_HASH = 'hostingstabilitymeter_host_hash';

        const STAT_TABLE_NAME = 'hostingstabilitymeter_stat';

        const SETTINGS_GROUP = 'hostingstabilitymeter_settings';
        const SETTINGS_MAIN = self::SETTINGS_GROUP . '_main';
        const NAME_ALLOWSEND = 'allow_send';
        const NAME_ALLOWMAIL = 'allow_mail';
        const NAME_MAILADDR = 'mail_addr';
        const TH = 'threshold';
        const NAME_TH_CPU = self::TH . '_' . HostingStabilityMeterBenchmarks::CPU;
        const NAME_TH_DISK = self::TH . '_' . HostingStabilityMeterBenchmarks::DISK;
        const NAME_TH_DB = self::TH . '_' . HostingStabilityMeterBenchmarks::DB;
        const NAME_INTERVAL_TEST = 'interval_test';

        const SETTINGS_DEFAULTS = array(
            self::NAME_ALLOWSEND => 1,
            self::NAME_ALLOWMAIL => 0,
            self::NAME_MAILADDR  => '',
            self::NAME_TH_CPU    => 0,
            self::NAME_TH_DISK   => 0,
            self::NAME_TH_DB     => 0,
	    self::NAME_INTERVAL_TEST => 20
        );
        const THRESHOLD_RANGES = array(
            self::NAME_TH_CPU  => array( 0, 1000 ),
            self::NAME_TH_DISK => array( 0, 1000 ),
            self::NAME_TH_DB   => array( 0, 5000 )
        );
        const INTERVAL_TEST_RANGE = array( 1, 120 );

        const ERROR_CODE_BAD_THRESHOLD = 1;
        const ERROR_CODE_BAD_ALLOW = 2;
        const ERROR_CODE_BAD_EMAIL = 3;
        const ERROR_CODE_BAD_INTERVAL_TEST = 4;

	const CRON_HOOK_TESTS = 'hostingstabilitymeter_cron_hook_tests';
	const CRON_HOOK_SEND = 'hostingstabilitymeter_cron_hook_send';
	const INTERVAL_TESTS = 'hostingstabilitymeter_interval_tests';
	const INTERVAL_SEND = 'hostingstabilitymeter_interval_send';

        const STAT_SQL_INTERVAL = '25 hour';
        const STAT_SQL_LIMIT = '1500';  // every minute for 25 hours
        
        const AGENT_VERSION = '1.0.0';
        
        // group of tests to select one to run each time
        private static $tests_group = array(
            HostingStabilityMeterBenchmarks::CPU  => array( 'setting' => self::NAME_TH_CPU ),
            HostingStabilityMeterBenchmarks::DISK => array( 'setting' => self::NAME_TH_DISK ),
            HostingStabilityMeterBenchmarks::DB   => array( 'setting' => self::NAME_TH_DB )
        );

        private static $initiated = false;
        private static $options = false;
       
        /**
	 * Init hook - sets all hooks, actions, filters, schedules, vars
	 * @static
	 */
	public static function init() {
		if ( ! self::$initiated ) {
			self::$initiated = true;
			add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_interval' ) );
                        add_action( self::CRON_HOOK_SEND, array( __CLASS__, 'send_stats' ) );
			if ( ! wp_next_scheduled( self::CRON_HOOK_SEND ) ) {
				wp_schedule_event( time(), self::INTERVAL_SEND, self::CRON_HOOK_SEND );
			}
                        add_action( self::CRON_HOOK_TESTS, array( __CLASS__, 'cron_exec_tests' ) );
			if ( ! wp_next_scheduled( self::CRON_HOOK_TESTS ) ) {
				wp_schedule_event( time(), self::INTERVAL_TESTS, self::CRON_HOOK_TESTS );
			}
		}
	}

	/**
	 * Activation hook - creates tables
	 * @static
	 */
	public static function plugin_activation() {
	    global $wpdb;

            $options = self::get_settings();
            $table_name = $wpdb->prefix . self::STAT_TABLE_NAME;
	    if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
	        $sql = "CREATE TABLE " . $table_name . " (
	          dtime timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
                  test_name set('cpu','disk','db') NOT NULL,
                  count int(10) UNSIGNED NOT NULL,
                  seconds float UNSIGNED NOT NULL,
                  speed_this float UNSIGNED NOT NULL,
                  speed_avg_previous float UNSIGNED NOT NULL,
                  threshold_value tinyint(3) UNSIGNED NOT NULL,
                  threshold_delta float NOT NULL,
                  threshold_over tinyint(1) NOT NULL DEFAULT '0',
	          UNIQUE KEY id (dtime),
                  KEY test_name (test_name)
	        ) DEFAULT CHARSET=utf8;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	    }

            // Make first benchmarks call
            foreach ( self::$tests_group as $test_name => $test ) {
		self::call_benchmark( $test_name, $options );
            }
            self::send_stats();
	}

	/**
	 * Deactivation hook - clears schedule
	 * @static
	 */
	public static function plugin_deactivation( ) {
		$timestamp = wp_next_scheduled( self::CRON_HOOK_TESTS );
		wp_unschedule_event( $timestamp, self::CRON_HOOK_TESTS );
		wp_clear_scheduled_hook( self::CRON_HOOK_TESTS );

                $timestamp = wp_next_scheduled( self::CRON_HOOK_SEND );
		wp_unschedule_event( $timestamp, self::CRON_HOOK_SEND );
		wp_clear_scheduled_hook( self::CRON_HOOK_SEND );
	}

	/**
	 * Adds custom cron interval
	 * @static
	 */
	public static function add_cron_interval( $schedules ) {
		$options = self::get_settings();
		$schedules[ self::INTERVAL_TESTS ] = array(
			'interval' => 60 * $options[ self::NAME_INTERVAL_TEST ],
			'display'  => self::INTERVAL_TESTS
		);
		$schedules[ self::INTERVAL_SEND ] = array(
			'interval' => 86400,
			'display'  => self::INTERVAL_SEND
		);
		return $schedules;
	}

	/**
	 * Makes cron jobs: tests and results storing
	 * @static
	 */
	public static function cron_exec_tests() {
		global $wpdb;
                
                $options = self::get_settings();
                $table_name = $wpdb->prefix . self::STAT_TABLE_NAME;
                
                // We run one random test from the rest of the group each time
                foreach ( self::$tests_group as $test_name => $test ) {
                    $tests_group[] = $test_name;
                }
                $tests_made = get_option( self::OPTION_NAME_TESTS_MADE ); // called tests
                if ( ! isset($tests_made) ) {
                    $tests_made = array();
                }
                foreach ( $tests_made as $key => $test_name ) {
                    if ( ! in_array( $test_name, $tests_group ) ) {
                        unset( $tests_made[ $key ] );  // remove tests not in group if any
                    }
                }
                if ( count( $tests_made ) < count( $tests_group )) {
                    foreach ( $tests_group as $key => $test_name ) {
                        if ( in_array( $test_name, $tests_made ) ) {
                            unset( $tests_group[ $key ] );  // remove called tests
                        }
                    }
                } else {
                    $tests_made = array();
                }
                $test_name = $tests_group[ array_rand( $tests_group ) ];
                
                $tests_made[] = $test_name;
                if ( count( $tests_made ) == count( self::$tests_group ) ) {
                    $tests_made = array();  // clear made tests list if it's full
                    $wpdb->query( "DELETE FROM `$table_name` WHERE dtime < NOW() - INTERVAL 8 DAY;" );   // then clear old records
                }
                update_option( self::OPTION_NAME_TESTS_MADE,  $tests_made, true);

                $test_result = self::call_benchmark( $test_name, $options );
                if ( $test_result !== false ) {
                    $allow_mail = $options[ self::NAME_ALLOWMAIL ];
                    $email_to = filter_var( empty( $options[ self::NAME_MAILADDR ] ) ? get_option( 'admin_email' ) : $options[ self::NAME_MAILADDR ], FILTER_VALIDATE_EMAIL );
                    if ( $email_to === FALSE ) {
                        $allow_mail = 0;
                    }
                    if ( $allow_mail && $test_result[ 'threshold_over' ] == 1 ) {
                        //load_plugin_textdomain( 'hosting-stability-meter' );
                        $message_attention = __( 'Attention, please!', 'hosting-stability-meter' );
                        $message_hsm_detected_threshold = __( 'Hosting Stability Meter detected benchmark threshold reached on your site.', 'hosting-stability-meter' );
                        $message_site = __( 'Site', 'hosting-stability-meter' );
                        $message_benchmark = __( 'Benchmark', 'hosting-stability-meter' );
                        $message_current_speed = __( 'Current speed', 'hosting-stability-meter' );
                        $message_units_per_sec = __( 'cycles/sec', 'hosting-stability-meter' );
                        $message_avg_speed_24h = __( 'Avg. speed in 24 hours', 'hosting-stability-meter' );
                        $message_loss_of_speed = __( 'Loss of speed', 'hosting-stability-meter' );
                        $message_threshold = __( 'Threshold', 'hosting-stability-meter' );
                        $message_current_time = __( 'Current time', 'hosting-stability-meter' );
                        $message_hsm_benchmark = __( 'Hosting Stability Meter benchmark', 'hosting-stability-meter' );
                        $message_speed_loss = __( 'speed loss', 'hosting-stability-meter' );
                        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
                        $template = file_get_contents( HOSTING_STABILITY_METER_PLUGIN_DIR . 'views/mail.html' );
                        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                        switch ( $test_name ) {
                            case HostingStabilityMeterBenchmarks::CPU :
                                $test_title = __( 'CPU', 'hosting-stability-meter' );
                                break;
                            case HostingStabilityMeterBenchmarks::DISK :
                                $test_title = __( 'Disk', 'hosting-stability-meter' );
                                break;
                            case HostingStabilityMeterBenchmarks::DB :
                                $test_title = __( 'Database', 'hosting-stability-meter' );
                                break;
                        }
                        $time_format = get_option( 'date_format' ) . '  ' . get_option( 'time_format' );
                        $message  = sprintf(
                            $template,
                            $message_attention,
                            $message_hsm_detected_threshold,
                            $message_site, $site_name,
                            $message_benchmark, $test_title,
                            $message_current_speed, sprintf( '%.3f', $test_result[ 'speed_this' ] ), $message_units_per_sec,
                            $message_avg_speed_24h, sprintf( '%.3f', $test_result[ 'speed_avg_previous' ] ), $message_units_per_sec,
                            $message_loss_of_speed, sprintf( '%.1f', $test_result[ 'threshold_delta' ] ),
                            $message_threshold, $test_result[ 'threshold_value' ],
                            $message_current_time, date_i18n( $time_format )
                        );
                        @wp_mail(
                                $email_to,
                                sprintf(
                                        '%s - %s: %s %s %s%%', 
                                        $site_name, 
                                        $message_hsm_benchmark,
                                        $test_title,
                                        $message_speed_loss,
                                        sprintf( '%.1f', $test_result[ 'threshold_delta' ] )
                                ),
                                $message,
                                $headers
                        );
                    }
                }
	}

        /**
         * Inner function - Calls benchmark
         * @return  Array|false Array in success or false
         */
        function call_benchmark( $test_name, $options ) {
		global $wpdb;

                $table_name = $wpdb->prefix . self::STAT_TABLE_NAME;
                $test_result = HostingStabilityMeterBenchmarks::benchmark(
                    $test_name,
                    self::TEST_DURATION_MAX,
                    self::TEST_COUNT_MAX,
                    $test_name === HostingStabilityMeterBenchmarks::DB ? array( 'prefix' => $wpdb->prefix, 'query_object' => $wpdb, 'query_method' => 'query' ) : NULL
		);
                if ( $test_result !== false && $test_result[ 0 ] > 0 && $test_result[ 1 ] > 0 ) {
                    $this_speed_value = $test_result[ 0 ] / $test_result[ 1 ];  // count/duration
                    
                    $avg_speed_value = $wpdb->get_var(
                        "SELECT AVG(speed_this) FROM `$table_name` " .
                        "WHERE test_name = '$test_name' AND dtime > NOW() - INTERVAL 24 HOUR;"
                    );
                    if ( ! isset( $avg_speed_value ) || $avg_speed_value == 0 ) {
                        $avg_speed_value = $this_speed_value;
                    }

                    $threshold_value = $options[ self::$tests_group[ $test_name ][ 'setting' ] ];
                    if ( ! isset( $threshold_value )) $threshold_value = 0;
                    if ( $avg_speed_value > 0 ) {
                        $threshold_delta = ( $avg_speed_value - $this_speed_value ) * 100 / $avg_speed_value;
                    } else {
                        $threshold_delta = 0;
                    }

                    $this_result = array(
                            'test_name' => $test_name,
                            'count' => $test_result[ 0 ],
                            'seconds' => $test_result[ 1 ],
                            'speed_this' => $this_speed_value,
                            'speed_avg_previous' => $avg_speed_value,
                            'threshold_value' => $threshold_value,
                            'threshold_delta' => $threshold_delta,
                            'threshold_over' => ( $threshold_value > 0 && $threshold_delta >= $threshold_value ) ? 1 : 0
                    );
                    $wpdb->insert( $table_name, $this_result );
                    
                    return $this_result;
                } else {
                    return false;
                }
	}

        /**
         * Inner function - Gets current Hosting Stability Meter settings
         * @return  mixed[] Array of options
         */
        function get_settings() {
            if ( self::$options === false ) {
                self::$options = get_option( self::SETTINGS_MAIN );
            }
            if( is_array( self::$options ) ) {
                self::sanitize_settings( self::$options );
            } else {
                self::$options = array();
            }
            return array_merge( self::SETTINGS_DEFAULTS, self::$options );
        }

        /**
         * Inner function - Sanitizes current Hosting Stability Meter settings
         * @return  int|false Error code or FALSE in no error
         */
        function sanitize_settings( &$input = array() ) {
                $result = FALSE;
                foreach ( $input as $k => $v ) {
                    if ( !array_key_exists( $k, self::SETTINGS_DEFAULTS ) ) {
                        unset( $input[ $k ] );
                    }
                }
                foreach ( self::THRESHOLD_RANGES as $k => $v ) {
                    if ( isset( $input[ $k ] ) ) {
                        if ( $input[ $k ] < $v[ 0 ] || $input[ $k ] > $v[ 1 ] ) {
                            $input[ $k ] = 0;
                            $result = self::ERROR_CODE_BAD_THRESHOLD;
                        }
                    } else {
                        $input[ $k ] = self::SETTINGS_DEFAULTS[ $k ];
                    }
                }
                if ( isset( $input[ self::NAME_INTERVAL_TEST ] ) ) {
		    $input[ self::NAME_INTERVAL_TEST ] = intval($input[ self::NAME_INTERVAL_TEST ]);
                    if ( $input[ self::NAME_INTERVAL_TEST ] < self::INTERVAL_TEST_RANGE[ 0 ] || $input[ self::NAME_INTERVAL_TEST ] > self::INTERVAL_TEST_RANGE[ 1 ] ) {
                        $input[ self::NAME_INTERVAL_TEST ] = self::SETTINGS_DEFAULTS[ self::NAME_INTERVAL_TEST ];
                        $result = self::ERROR_CODE_BAD_INTERVAL_TEST;
		    }
                } else {
                    $input[ self::NAME_INTERVAL_TEST ] = self::SETTINGS_DEFAULTS[ self::NAME_INTERVAL_TEST ];
                }
                if ( isset( $input[ self::NAME_ALLOWSEND ] ) ) {
                    if ( $input[ self::NAME_ALLOWSEND ] != 0 && $input[ self::NAME_ALLOWSEND ] != 1 )  {
                        $input[ self::NAME_ALLOWSEND ] = self::SETTINGS_DEFAULTS[ self::NAME_ALLOWSEND ];
                        $result = self::ERROR_CODE_BAD_ALLOW;
                    }
                } else {
                    $input[ self::NAME_ALLOWSEND ] = self::SETTINGS_DEFAULTS[ self::NAME_ALLOWSEND ];
                }
                if ( isset( $input[ self::NAME_ALLOWMAIL ] ) ) {
                    if ( $input[ self::NAME_ALLOWMAIL ] != 0 && $input[ self::NAME_ALLOWMAIL ] != 1 ) {
                        $input[ self::NAME_ALLOWMAIL ] = self::SETTINGS_DEFAULTS[ self::NAME_ALLOWMAIL ];
                        $result = self::ERROR_CODE_BAD_ALLOW;
                    }
                } else {
                    $input[ self::NAME_ALLOWMAIL ] = self::SETTINGS_DEFAULTS[ self::NAME_ALLOWMAIL ];
                }
                if ( isset( $input[ self::NAME_MAILADDR ] ) && ! empty( $input[ self::NAME_MAILADDR ] ) ) {
                    if ( ! is_email( $input[ self::NAME_MAILADDR ] ) ) {
                        $input[ self::NAME_MAILADDR ] = self::SETTINGS_DEFAULTS[ self::NAME_MAILADDR ];
                        $result = self::ERROR_CODE_BAD_EMAIL;
                    }
                } else {
                    $input[ self::NAME_MAILADDR ] = self::SETTINGS_DEFAULTS[ self::NAME_MAILADDR ];
                }
                
                return $result;
        }
        
        /**
         * Inner function - Current Hosting Stability Meter host hash
         * @return  string MD5 hash
         */
        function get_host_hash() {
            $host_hash = get_option( self::OPTION_HOST_HASH );
            if ( ! ( is_string( $host_hash ) && strlen( $host_hash ) == 32 ) ) {
                $host_hash =  md5( $_SERVER[ 'SERVER_NAME' ] . strftime( '%Y-%m-%d %H:%M:%S' ) );
                update_option( self::OPTION_HOST_HASH, $host_hash, true );
            }
            return $host_hash;
        }

        /**
         * Inner function - Sends stats to the host
         * @return  true|false Success or not
         */
        function send_stats() {
            global $wpdb;
            $options = self::get_settings();
            if ( ! $options[ self::NAME_ALLOWSEND ] ) {
                return true;
            }
            $host_hash = self::get_host_hash();
            $data_rows = $wpdb->get_results(
                    'SELECT DATE_FORMAT(dtime, "%Y-%m-%d %H:%i:%s"), UNIX_TIMESTAMP(dtime), test_name, count, seconds'
                    . ' FROM ' . $wpdb->prefix . self::STAT_TABLE_NAME
                    . ' WHERE dtime > now() - interval ' . self::STAT_SQL_INTERVAL
                    . ' ORDER BY dtime LIMIT ' . self::STAT_SQL_LIMIT,
                    'ARRAY_N'
            );
            
            // Store values not frequent than 10 minutes
            $data_rows_selected = array();
            foreach ( $data_rows as $row ) {
                if (
                    isset( $row[ 0 ] ) && isset( $row[ 1 ] ) && isset( $row[ 2 ] ) && isset( $row[ 3 ] ) && isset( $row[ 4 ] )
                ) {
                    $datetime_obj = date_create( $row[ 0 ] );
                    if ( is_object( $datetime_obj ) ) {
                        $datetime_str = $datetime_obj->format( 'Y m d H i' );
                        $datetime_array = explode( ' ', $datetime_str );
                        $key = sprintf(
                            '%s-%s-%s %s:%02d',
                            $datetime_array[ 0 ],
                            $datetime_array[ 1 ],
                            $datetime_array[ 2 ],
                            $datetime_array[ 3 ],
                            10 * intval( intval( $datetime_array[ 4 ] ) / 10 )
                        );
                        if ( !isset( $data_rows_selected[ $key ] ) ) {
                            $data_rows_selected[ $key ] = array(
                                $row[ 1 ],
                                $row[ 2 ],
                                $row[ 3 ],
                                $row[ 4 ]
                            );
                        }
                    }
                }
            }
            unset( $data_rows );

            $data_rows_2_send = array();
            foreach ( $data_rows_selected as $key => $row ) {
                $stats_record_array = HostingStabilityMeterBenchmarks::create_stats_record_array( array(
                    'timestamp' => intval( $row[ 0 ] ),
                    'test_name' => $row[ 1 ],
                    'test_count' => intval( $row[ 2 ] ),
                    'duration_seconds' => floatval( $row[ 3 ] )
                ) );
                if ($stats_record_array !== false) $data_rows_2_send[] = $stats_record_array;
            }
            unset( $data_rows_selected );

            if ( empty( $data_rows_2_send ) ) return false;

            $post_array = HostingStabilityMeterBenchmarks::create_post_array( array(
                    'hostname' => $_SERVER[ 'SERVER_NAME' ],
                    'ip' => $_SERVER[ 'SERVER_ADDR' ],
                    'hosthash' => $host_hash,
                    'agent' => 'wordpress-' . self::AGENT_VERSION,
                    'stats' => wp_json_encode( $data_rows_2_send ),
                    'hostinfo' => wp_json_encode( HostingStabilityMeterBenchmarks::create_server_info_array() )
                    ) );
            
            if ( $post_array === false ) return false;
            
            $response = wp_remote_post( HostingStabilityMeterBenchmarks::STATS_URL, array(
                'method' => 'POST',
                'timeout' => 5,
                'redirection' => 2,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' =>  $post_array,
                'cookies' => array()
                )
            );
    
            if ( is_wp_error( $response ) ) {
                $r = $response->get_error_message();
                return false;
            } else {
                $r = $response;
                return true;
            }        
        }
}
