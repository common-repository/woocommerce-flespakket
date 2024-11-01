<?php
/*
Plugin Name: WooCommerce Flespakket
Plugin URI: http://www.flespakket.nl
Description: Export your WooCommerce orders to Flespakket (www.flespakket.nl) and print labels directly from the WooCommerce admin
Author: Richard Perdaan
Version: 1.4.4
Text Domain: wcflespakket

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if ( !class_exists( 'WooCommerce_Flespakket_Export' ) ) {
	class WooCommerce_Flespakket_Export {
	
		/**
		 * Construct.
		 */
		 		
		public function __construct() {
	
			// Load textdomain
			add_action( 'plugins_loaded', array( &$this, 'languages' ), 0 );
	
			// Load custom styles & scripts
			add_action( 'admin_enqueue_scripts', array( &$this, 'add_admin_styles_scripts' ) );
			
			$this->includes();
			register_activation_hook( __FILE__, array( 'WC_Flespakket_Settings', 'default_settings' ) );

			// Load plugin classes
			add_action( 'init', array( &$this, 'load_hooks' ) );
		}
	
		/**
		 * Load the main plugin classes and functions
		 */
		public function includes() {
			include_once( 'includes/wcflespakket-settings.php' );
			include_once( 'includes/wcflespakket-export.php' );
			include_once( 'includes/wcflespakket-writepanel.php' );
			include_once( 'includes/wcflespakket-nlpostcode-fields.php' );
		}
		
		public function load_hooks() {
			$this->settings = new WC_Flespakket_Settings();
			$this->export = new WC_Flespakket_Export();
			$this->writepanel = new WC_Flespakket_Writepanel();
			$this->nlpostcodefields = new WC_NLPostcode_Fields();
		}
	
		/**
		 * Load translations.
		 */
		public function languages() {
			load_plugin_textdomain( 'wcflespakket', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	
		/**
		 * Load admin styles & scripts.
		 */
		function add_admin_styles_scripts(){
		 	global $post_type;
			if( $post_type == 'shop_order' ) {
				wp_enqueue_script( 'thickbox' );
				wp_enqueue_style( 'thickbox' );
				wp_enqueue_script( 'wcflespakket-export', plugin_dir_url(__FILE__) . 'js/wcflespakket-script.js', array( 'jquery', 'thickbox' ) );

				if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
					// Old versions
					wp_register_style( 'wcflespakket-admin-styles', plugins_url( '/css/wcflespakket-admin-styles.css', __FILE__ ), array(), '', 'all' );
				} else {
					// WC 2.1+, MP6 style with larger buttons
					wp_register_style( 'wcflespakket-admin-styles', plugins_url( '/css/wcflespakket-admin-styles-wc21.css', __FILE__ ), array(), '', 'all' );
				}				

				wp_enqueue_style( 'wcflespakket-admin-styles' );  
			}
		}
	
	}
}

/**
 * WooCommerce fallback notice.
 *
 * @return string Fallack notice.
 */
function wcflespakket_fallback_notice() {
	$message = '<div class="error">';
		$message .= '<p>' . sprintf( __( 'WooCommerce Flespakket export depends on <a href="%s">WooCommerce</a> to work!' , 'wcflespakket' ), 'http://wordpress.org/extend/plugins/woocommerce/' ) . '</p>';
	$message .= '</div>';

	echo $message;
}

/**
 * Check if WooCommerce is active.
 *
 * Ref: http://wcdocs.woothemes.com/codex/extending/create-a-plugin/.
 */
$blog_plugins = get_option( 'active_plugins', array() );
$site_plugins = get_site_option( 'active_sitewide_plugins', array() ); // Multisite

if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
	$wcflespakketexport = new WooCommerce_Flespakket_Export();
} else {
	add_action( 'admin_notices', 'wcflespakket_fallback_notice' );
}