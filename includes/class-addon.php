<?php
/**
 * @package     Heimdall
 * @author      Rmanaf <me@rmanaf.com>
 * @copyright   2018-2023 WP Heimdall
 * @license     No License (No Permission)
 */

namespace Heimdall;

defined( 'HEIMDALL_VER' ) || die;

/**
 * @since 1.4.0
 */
abstract class Addon {

	/**
	 * @since 1.4.0
	 */
	abstract public static function setup();

	/**
	 * @since 1.4.0
	 */
	public static function get_slug() {
		return Helpers::addon_class_to_file( self::get_class_name() );
	}

	/**
	 * @since 1.4.0
	 */
	public static function get_url( $name = '' ) {
		return Helpers::get_addon_url( $name, self::get_class_name() );
	}

	/**
	 * Returns the name of the class.
	 *
	 * @since 1.4.0
	 */
	public static function get_class_name() {
		$class = get_called_class();
		return substr( strrchr( $class, '\\' ), 1 );
	}

	/**
	 * @since 1.4.0
	 */
	public static function load_libs( $libs ) {
		foreach ( $libs as $lib ) {
			require_once Helpers::get_addon_dir( $lib, self::get_class_name() );
		}
	}

	/**
	 * Registers the script if $src provided (does NOT overwrite), and enqueues it.
	 *
	 * @param string   $handle Name of the script. Should be unique.
	 * @param string   $name Full name of the script.
	 * @param boolean  $in_footer Optional. Whether to enqueue the script before instead of in the . Default 'false'.
	 * @param string[] $deps Optional. An array of registered script handles this script depends on. Default empty array.
	 *
	 * @return void
	 *
	 * @since 1.4.0
	 */
	public static function enqueue_script( $handle, $name, $in_footer = false, $deps = array() ) {
		wp_enqueue_script( $handle, self::get_url( $name ), $deps, HEIMDALL_VER, $in_footer );
	}

}
