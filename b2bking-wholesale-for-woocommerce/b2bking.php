<?php
/*
/**
 * Plugin Name:       B2BKing Core
 * Plugin URI:        https://codecanyon.net/item/b2bking-the-ultimate-woocommerce-b2b-plugin/26689576
 * Description:       B2BKing is the complete solution for turning WooCommerce into an enterprise-level B2B e-commerce platform. Core Plugin.
 * Version:           4.7.10
 * Author:            WebWizards
 * Author URI:        webwizards.dev
 * Text Domain:       b2bking
 * Domain Path:       /languages
 * WC requires at least: 5.0.0
 * WC tested up to: 9.1.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'B2BKINGCORE_DIR', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'B2BKINGCORE_VERSION' ) ) {
	define(	'B2BKINGCORE_VERSION', 'v4.7.10');
}


function b2bkingcore_activate() {
	require_once B2BKINGCORE_DIR . 'includes/class-b2bking-activator.php';
	B2bkingcore_Activator::activate();
}
register_activation_hook( __FILE__, 'b2bkingcore_activate' );

require B2BKINGCORE_DIR . 'includes/class-b2bking.php';

// Load plugin language
add_action( 'init', 'b2bkingcore_load_language');
function b2bkingcore_load_language() {
   load_plugin_textdomain( 'b2bking', FALSE, basename( dirname( __FILE__ ) ) . '/languages');
}

// Begins execution of the plugin.
function b2bkingcore_run() {

	require_once ( B2BKINGCORE_DIR . 'includes/class-b2bking-global-helper.php' );

	if (!function_exists('b2bking')){
		function b2bking() {
		    return B2bking_Globalhelpercore::init();
		}
	}

	
	$plugin = new B2bkingcore();
}

if (!defined('B2BKING_DIR') && get_option('b2bking_main_active', 'no') === 'no'){

	b2bkingcore_run();

} else {
	add_action('plugins_loaded', function(){

		// important for correct plugin loading order
		if (class_exists('B2bking')){
			update_option('b2bking_main_active', 'yes');

			add_action( 'admin_notices', 'b2bking_activate_notification' );

		} else {
			update_option('b2bking_main_active', 'no');
		}
	});
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


function b2bking_activate_notification() {

	if ( defined( 'WC_PLUGIN_FILE' ) && defined('B2BKINGCORE_DIR') ) {
		$license = get_option('b2bking_license_key_setting', '');
		if (empty($license)){

			// if B2BKING PRO EXISTS
			if ( defined( 'B2BKING_VERSION' ) || defined ('B2BKING_DIR')) {
				if (defined('B2BKING_VERSION')){
					$version_b2bking_pro = substr(B2BKING_VERSION, 1);
				} else {
					$version_b2bking_pro = '1.0'; // so old that we cannot get it, use 1.0 as placeholder
				}

				if ( version_compare($version_b2bking_pro, '4.8.0') == -1) { // lower than 4.8.00

					// if over 4.4, please activate
					if ( version_compare($version_b2bking_pro, '4.4.0') >= 0) { 
						?>
						<br>
						<div class="b2bking_dismiss_review_notice notice notice-info is-dismissible b2bking_main_notice">
							<?php
							$iconurl = plugins_url('includes/assets/images/b2bking-icon-gray2.svg', __FILE__);
							?>
							<div class="b2bking_notice_left_screen">
								<img src="<?php echo esc_attr($iconurl);?>" class="b2bking_notice_icon">
							</div>
							<div class="b2bking_notice_right_screen">
								<h3><?php esc_html_e('Welcome to B2BKing Pro!','b2bking');?></h3>
								<p><?php esc_html_e('Please activate your license to get important plugin updates and premium support.','b2bking');?></p>
								<a href="<?php echo esc_attr(admin_url('admin.php?page=b2bking&tab=activate'));?>"><button type="button" class="button-primary b2bking_notice_button"><?php esc_html_e('Activate License','b2bking');?></button></a>
								<br><br>
							</div>
						</div>
						<?php
					} else {
						// if under 4.4 please update plugin, very old and no activation yet
						?>
						<br>
						<div class="b2bking_dismiss_review_notice notice notice-info is-dismissible b2bking_main_notice">
							<?php
							$iconurl = plugins_url('includes/assets/images/b2bking-icon-gray2.svg', __FILE__);
							?>
							<div class="b2bking_notice_left_screen">
								<img src="<?php echo esc_attr($iconurl);?>" class="b2bking_notice_icon">
							</div>
							<div class="b2bking_notice_right_screen">
								<h4><?php esc_html_e('B2BKing Pro update notice!','b2bking');?></h4>
								<p><?php esc_html_e('You are running an old version of B2BKing Pro. Please update to get access to the latest features and improvements.','b2bking');?></p>
								<a target="_blank" href="https://woocommerce-b2b-plugin.com/docs/how-to-update-b2bking-to-the-latest-version/"><button type="button" class="button-primary b2bking_notice_button"><?php esc_html_e('How to Update','b2bking');?></button></a>
								<br><br>
							</div>
						</div>
						<?php
					}

					?>
					<style type="text/css">
						img.b2bking_notice_icon {
						    width: 30px;
						    background: #f6f6f6;
						    padding: 9px 14px 14px 12px;
						    border-radius: 23px;
						    border: 1px solid #eaeaea;
						}
						.toplevel_page_b2bking img.b2bking_notice_icon, .b2bking_page_b2bking_tools img.b2bking_notice_icon, .b2bking_page_b2bking_dashboard img.b2bking_notice_icon, .b2bking_page_b2bking_reports img.b2bking_notice_icon {
						    width: 56px !important;
						}
						.b2bking_dismiss_review_notice {
						    display: flex;
						}
						.b2bking_notice_left_screen {
						    display: flex;
						    align-items: center;
						    padding: 0px 21px 0px 5px;
						}
						.b2bking_notice_button {
						    margin-top: 7px !important;
						    margin-right: 10px !important;
						}
						.b2bking_main_notice {
						    border-left-color: #e2ac4b;
						}
						button.button-secondary.b2bking_notice_button {
						    background: white !important;
						    border: 1px solid #ccc;
						    color: #333 !important;
						}
						button.button-secondary:hover.b2bking_notice_button {
						    border: 1px solid #a3a3a3;
						}
						button.button-primary.b2bking_notice_button, button.button-primary:focus.b2bking_notice_button, button.button-primary:target.b2bking_notice_button {
						    background: #e2ac4b;
						    border-color: #e2ac4b;
						}
						button.button-primary:hover.b2bking_notice_button {
						    background: #d18b0e;
						    border-color: #d18b0e;
						}
						.b2bking_notice_right_screen h3, .b2bking_notice_right_screen h4{
							margin-top: 16px;
						}
					</style>
					<?php

				}
			}
		}
	}
}