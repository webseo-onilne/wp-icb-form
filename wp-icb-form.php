<?php
/*
Plugin Name: WP ICB_form
Plugin URI: https://webseo.co.za
Description: ICB_form
Author: Web SEO Online (PTY) LTD
Author URI: https://webseo.co.za
Version: 0.0.1

  Copyright: © 2018 Web SEO Online (PTY) LTD (email : supprt@webseo.co.za)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


/**
 * No direct access
 */
if ( ! defined( 'ABSPATH' ) ) {
    die( 'No access' );
};


/**
 * Check if gravityforms is active
 */
if ( in_array( 'gravityforms/gravityforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
  
	if ( ! class_exists( 'WP_ICB_form' ) ) {
		
		/**
		 * Localisation
		 **/
		load_plugin_textdomain( 'WP_ICB_form', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

		class WP_ICB_form {

			public function __construct() {
				// Scripts to include
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

				// Frontend DB Fetch with ajax call
				add_action( 'wp_ajax_aa_ajax_fetch_from_db', array( $this, 'aa_ajax_fetch_from_db' ) );
				add_action( 'wp_ajax_nopriv_aa_ajax_fetch_from_db', array( $this, 'aa_ajax_fetch_from_db' ) );
				
				// Dynamic feild choices
				add_filter( 'gform_pre_render_3', array( $this, 'populate_checkbox' ) );
				add_filter( 'gform_pre_validation_3', array( $this, 'populate_checkbox' ) );
				add_filter( 'gform_pre_submission_filter_3', array( $this, 'populate_checkbox' ) );
				add_filter( 'gform_admin_pre_render_3', array( $this, 'populate_checkbox' ) );
			}


			/**
			 * Add scripts used on the front end
			 *
			 * @return void
			 */
			public function frontend_scripts () {
				global $post; 
				// JS      
				wp_enqueue_script( 'aquaaid_scripts', plugin_dir_url( __FILE__ ) . 'assets/js/icb-form.js', array('jquery') );
				// Create variables
				wp_localize_script( 'aquaaid_scripts', 'aquaaid', array(
					'ajax_url' => admin_url( 'admin-ajax.php' )    
				));
			}


			/**
			 * Frontend Select relevant data as per ajax request
			 *
			 * @return void
			 */
			public function aa_ajax_fetch_from_db() {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					global $wpdb;
					$aa_userInput = $_POST['aa_userInput'];
					try {
						$query = $wpdb->get_results(
							$wpdb->prepare("SELECT location_extrafields FROM wp_fdgjkh435_map_locations WHERE location_title LIKE '{$aa_userInput}'")
						);
						echo json_encode( $query ? $query : $wpdb->last_error );
					} 
					catch( Exception $e ) {
						echo json_encode( array( 'exception response:' => $e->getMessage() ) );
					}
				}
				wp_die();
			}


			/**
			 * Dynamically populate location choices
			 *
			 * @return string
			 */
			function populate_checkbox( $form ) {
				global $wpdb;

				// Get all provinces from database
				$provinces = $wpdb->get_results( "SELECT location_state FROM wp_fdgjkh435_map_locations GROUP BY location_state" );
			
				// Exmaination Venues Group ID
				$exam_venues = '134';
			
				// For each form field
				foreach( $form['fields'] as &$field )  {
			
					// For each province
					foreach ( $provinces as $province ) {
			
						// Skipt if there is no province set
						if ( ! $province->location_state || $province->location_state === '' ) {
							continue;
						}
			
						// Check if the province is in the field label
						if ( strpos( $field->label, $province->location_state ) !== false ) {
							$input_id = 1;
							$choices = array(null);
							$inputs = array(null);
			
							// Skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
							if ( $input_id % 10 == 0 ) {
								$input_id++;
							}
			
							// Get all the locations for the province
							$locations = $wpdb->get_results( "SELECT location_title, location_group_map FROM wp_fdgjkh435_map_locations WHERE location_state = '$province->location_state' " );
			
							// Add each location to the choices list
							foreach ( $locations as $key => $location ) {
			
								// Dont add Examination Venues to choices list
								if ( strpos( $location->location_group_map, $exam_venues ) === false ) {
									$choices[$key] = array( 'text' => $location->location_title, 'value' => $location->location_title );
									$inputs[$key] = array( 'label' => $location->location_title, 'id' => "{$field_id}.{$input_id}" );
								}
			
								// Increment the field ID
								$input_id++;
							}
			
							// Sort choices
							sort( $choices );
			
							// Remove NULL values and set choices and inputs
							$field->choices = array_filter( $choices );
							$field->inputs = array_filter( $inputs );
						}
					}
			
				}
			 
				return $form;
			}

		}

		// finally instantiate our plugin class and add it to the set of globals
		$GLOBALS['WP_ICB_form'] = new WP_ICB_form();
	}
}