<?php
/**
 * Heimdall
 *
 * @package     Heimdall
 * @author      Rmanaf <me@rmanaf.com>
 * @copyright   2018-2023 WP Heimdall
 * @license     No License (No Permission)
 *
 * @wordpress-plugin
 * Plugin Name: Heimdall
 * Plugin URI: https://wp-heimdall.com
 * Description: This plugin is for tracking your client activities.
 * Version: 1.4.0
 * Network: true
 * Requires at least: 5.8
 * Requires PHP:      5.6
 * Author: Rmanaf
 * Author URI: https://rmanaf.com
 * License: No License (No Permission)
 * License URI: https://wp-heimdall.com/license
 * Text Domain: heimdall
 * Domain Path: /languages
 */

use Heimdall\Communicator;
use Heimdall\Core;
use Heimdall\Dashboard;
use Heimdall\Database;
use Heimdall\LicenseManager;
use Heimdall\Options;

defined( 'ABSPATH' ) || die;

define( 'HEIMDALL_FILE', __FILE__ );
define( 'HEIMDALL_DIR', plugin_dir_path( __FILE__ ) );
define( 'HEIMDALL_URL', plugin_dir_url( __FILE__ ) );
define( 'HEIMDALL_VER', '1.4.0' );

require_once __DIR__ . '/includes/class-database.php';
require_once __DIR__ . '/includes/class-addon.php';
require_once __DIR__ . '/includes/class-core.php';
require_once __DIR__ . '/includes/class-helpers.php';
require_once __DIR__ . '/includes/class-cryptor.php';
require_once __DIR__ . '/includes/class-options.php';
require_once __DIR__ . '/includes/class-dashboard.php';
require_once __DIR__ . '/includes/class-communicator.php';
require_once __DIR__ . '/includes/class-license-manager.php';

Database::init();
Options::init();
Dashboard::init();
Core::init();
Communicator::init();
LicenseManager::init();
