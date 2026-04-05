<?php
/**
 * Plugin Name: LeadFlow Pro
 * Plugin URI:  https://leadflowpro.com
 * Description: The All-in-One Autonomous Lead Generation & CRM for WordPress.
 * Version:     1.0.0
 * Author:      LeadFlow Team
 * Author URI:  https://leadflowpro.com
 * Text Domain: leadflow-pro
 * Domain Path: /languages
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LEADFLOW_PRO_VERSION', '1.0.0' );
define( 'LEADFLOW_PRO_PATH', plugin_dir_path( __FILE__ ) );
define( 'LEADFLOW_PRO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation logic.
 */
function activate_leadflow_pro() {
	require_once LEADFLOW_PRO_PATH . 'includes/class-leadflow-activator.php';
	LeadFlow_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_leadflow_pro' );

/**
 * Core initialization.
 */
require_once LEADFLOW_PRO_PATH . 'includes/class-leadflow-core.php';

function run_leadflow_pro() {
	$plugin = new LeadFlow_Core();
	$plugin->run();
}
run_leadflow_pro();
