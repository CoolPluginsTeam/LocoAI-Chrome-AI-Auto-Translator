<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Plugin_Service_Container_Builder
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Chatbot\Chatbot;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Current_User;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Input;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Network_Env;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Network_Runner;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Plugin_Env;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Service_Container;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Site_Env;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Options\Option_Container;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Options\Option_Registry;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Options\Option_Repository;

/**
 * Plugin service container builder.
 *
 * @since 0.1.0
 */
final class Plugin_Service_Container_Builder {

	/**
	 * Service container.
	 *
	 * @since 0.1.0
	 * @var Service_Container
	 */
	private $container;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->container = new Service_Container();
	}

	/**
	 * Gets the service container.
	 *
	 * @since 0.1.0
	 *
	 * @return Service_Container Service container for the plugin.
	 */
	public function get(): Service_Container {
		return $this->container;
	}

	/**
	 * Builds the plugin environment service for the service container.
	 *
	 * @since 0.1.0
	 *
	 * @param string $main_file Absolute path to the plugin main file.
	 * @return self The builder instance, for chaining.
	 */
	public function build_env( string $main_file ): self {
		$this->container['plugin_env'] = function () use ( $main_file ) {
			return new Plugin_Env( $main_file, ATLT_PRO_VERSION );
		};

		return $this;
	}

	/**
	 * Builds the services for the service container.
	 *
	 * @since 0.1.0
	 *
	 * @return self The builder instance, for chaining.
	 */
	public function build_services(): self {
		$this->build_general_services();
		$this->build_option_services();

		return $this;
	}

	/**
	 * Builds the general services for the service container.
	 *
	 * @since 0.1.0
	 */
	private function build_general_services(): void {
		$this->container['input']            = static function () {
			return new Input();
		};
		$this->container['current_user']     = static function () {
			return new Current_User();
		};
		$this->container['site_env']         = static function () {
			return new Site_Env();
		};
		$this->container['network_env']      = static function () {
			return new Network_Env();
		};
		$this->container['network_runner']   = static function ( $cont ) {
			return new Network_Runner( $cont['network_env'] );
		};
	}
	/**
	 * Builds the option services for the service container.
	 *
	 * @since 0.1.0
	 */
	private function build_option_services(): void {
		$this->container['option_repository'] = static function () {
			return new Option_Repository();
		};
		$this->container['option_container']  = function () {
			$options = new Option_Container();
			return $options;
		};
		$this->container['option_registry']   = static function () {
			return new Option_Registry( 'ai_services' );
		};
	}
}
