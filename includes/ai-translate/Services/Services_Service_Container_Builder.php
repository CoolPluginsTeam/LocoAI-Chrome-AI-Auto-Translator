<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Services_Service_Container_Builder
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\HTTP\HTTP_With_Streams;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Options\Option_Encrypter;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Util\Data_Encryption;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Admin_Pages\Admin_Menu;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Capabilities\Base_Capability;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Capabilities\Capability_Container;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Capabilities\Capability_Controller;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Capabilities\Capability_Filters;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Capabilities\Meta_Capability;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Current_User;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Plugin_Env;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Service_Container;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Site_Env;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Meta\Meta_Repository;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Options\Option_Container;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Options\Option_Registry;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Options\Option_Repository;

/**
 * Service container builder for the services loader.
 *
 * @since 0.1.0
 */
final class Services_Service_Container_Builder {

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
		$this->build_capability_services();
		$this->build_http_services();
		$this->build_option_services();
		$this->build_entity_services();
		$this->build_admin_services();

		$this->container['api'] = static function ( $cont ) {
			return new Services_API(
				$cont['current_user'],
				$cont['http'],
				$cont['option_container'],
				$cont['option_repository'],
				$cont['option_encrypter']
			);
		};

		return $this;
	}

	/**
	 * Builds the general services for the service container.
	 *
	 * @since 0.1.0
	 */
	private function build_general_services(): void {
		$this->container['current_user'] = static function () {
			return new Current_User();
		};
		$this->container['site_env']     = static function () {
			return new Site_Env();
		};
	}

	/**
	 * Builds the capability services for the service container.
	 *
	 * @since 0.1.0
	 */
	private function build_capability_services(): void {
		$this->container['capability_container'] = static function () {
			$capabilities                        = new Capability_Container();
			$capabilities['ais_manage_services'] = static function () {
				return new Base_Capability(
					'ais_manage_services',
					array( 'manage_options' )
				);
			};
			$capabilities['ais_access_services'] = static function () {
				return new Base_Capability(
					'ais_access_services',
					array( 'edit_posts' )
				);
			};
			$capabilities['ais_access_service']  = static function () {
				return new Meta_Capability(
					'ais_access_service',
					// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
					static function ( int $user_id, string $service_slug ) {
						return array( 'ais_access_services' );
					}
				);
			};
			$capabilities['ais_use_playground']  = static function () {
				return new Meta_Capability(
					'ais_use_playground',
					// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
					static function ( int $user_id ) {
						return array( 'ais_access_services' );
					}
				);
			};
			return $capabilities;
		};

		$this->container['capability_controller'] = static function ( $cont ) {
			return new Capability_Controller( $cont['capability_container'] );
		};
		$this->container['capability_filters']    = static function ( $cont ) {
			return new Capability_Filters( $cont['capability_container'] );
		};
	}

	/**
	 * Builds the HTTP services for the service container.
	 *
	 * @since 0.1.0
	 */
	private function build_http_services(): void {
		$this->container['http'] = static function () {
			// Custom implementation with additional support for streaming responses.
			return new HTTP_With_Streams();
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
		$this->container['option_container']  = static function () {
			return new Option_Container();
		};
		$this->container['option_registry']   = static function () {
			return new Option_Registry( 'ais_services' );
		};
		$this->container['option_encrypter']  = static function () {
			return new Option_Encrypter( new Data_Encryption() );
		};
	}

	/**
	 * Builds the entity services for the service container.
	 *
	 * @since 0.5.0
	 */
	private function build_entity_services(): void {
		$this->container['user_meta_repository'] = static function () {
			return new Meta_Repository( 'user' );
		};
	}

	/**
	 * Builds the admin services for the service container.
	 *
	 * @since 0.1.0
	 */
	private function build_admin_services(): void {
		$this->container['admin_settings_menu']           = static function () {
			return new Admin_Menu( 'options-general.php' );
		};
		$this->container['admin_tools_menu']              = static function () {
			return new Admin_Menu( 'tools.php' );
		};
	}
}
