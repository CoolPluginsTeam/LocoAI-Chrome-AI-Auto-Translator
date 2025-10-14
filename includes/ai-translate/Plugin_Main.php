<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Plugin_Main
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Google\Google_AI_Service;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Google\Google_AI_Text_Generation_Model;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Deepl\Deepl_AI_Service;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Deepl\Deepl_AI_Text_Generation_Model;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\OpenAI\OpenAI_AI_Service;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\OpenAI\OpenAI_AI_Text_Generation_Model;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums\Service_Type;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Service_Registration_Context;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Services_API;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Services_API_Instance;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Services_Loader;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Util\AI_Capabilities;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Contracts\With_Hooks;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Service_Container;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Options\Option_Hook_Registrar;

/**
 * Plugin main class.
 *
 * @since 0.1.0
 */
class Plugin_Main implements With_Hooks {

	/**
	 * Plugin service container.
	 *
	 * @since 0.1.0
	 * @var Service_Container
	 */
	private $container;

	/**
	 * Services loader.
	 *
	 * @since 0.1.0
	 * @var Services_Loader
	 */
	private $services_loader;

	/**
	 * Services API instance.
	 *
	 * @since 0.1.0
	 * @var Services_API
	 */
	private $services_api;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $main_file Absolute path to the plugin main file.
	 */
	public function __construct( string $main_file ) {
		// Instantiate the services loader, which separately initializes all functionality related to the AI services.
		$this->services_loader = new Services_Loader( $main_file );

		// Then retrieve the canonical AI services instance, which is created by the services loader.
		$this->services_api = Services_API_Instance::get();

		// Last but not least, set up the container for the main plugin functionality.
		$this->container = $this->set_up_container( $main_file );

		$this->register_default_services();
	}

	/**
	 * Adds relevant WordPress hooks.
	 *
	 * @since 0.1.0
	 */
	public function add_hooks(): void {
		$this->services_loader->add_hooks();
		$this->add_cleanup_hooks();
		$this->add_service_hooks();
	}

	/**
	 * Adds cleanup hooks related to plugin deactivation.
	 *
	 * @since 0.1.0
	 */
	private function add_cleanup_hooks(): void {
		// This function is only available in WordPress 6.4+.
		if ( ! function_exists( 'wp_set_options_autoload' ) ) {
			return;
		}

		// Disable autoloading of plugin options on deactivation.
		register_deactivation_hook(
			$this->container['plugin_env']->main_file(),
			function ( $network_wide ) {
				// For network-wide deactivation, this cleanup cannot be reliably implemented.
				if ( $network_wide ) {
					return;
				}

				$autoloaded_options = $this->get_autoloaded_options();
				if ( ! $autoloaded_options ) {
					return;
				}

				wp_set_options_autoload(
					$autoloaded_options,
					false
				);
			}
		);

		// Reinstate original autoload settings on (re-)activation.
		register_activation_hook(
			$this->container['plugin_env']->main_file(),
			function ( $network_wide ) {
				// See deactivation hook for network-wide cleanup limitations.
				if ( $network_wide ) {
					return;
				}

				$autoloaded_options = $this->get_autoloaded_options();
				if ( ! $autoloaded_options ) {
					return;
				}

				wp_set_options_autoload(
					$autoloaded_options,
					true
				);
			}
		);
	}

	/**
	 * Adds general service hooks on 'init' to initialize the plugin.
	 *
	 * @since 0.1.0
	 */
	private function add_service_hooks(): void {
		// Register options.
		$this->load_options();
	}

	/**
	 * Loads the plugin options.
	 *
	 * @since 0.7.0
	 */
	private function load_options(): void {
		$option_registrar = new Option_Hook_Registrar( $this->container['option_registry'] );
		$option_registrar->add_register_callback(
			function ( $registry ) {
				foreach ( $this->container['option_container']->get_keys() as $key ) {
					$option = $this->container['option_container']->get( $key );
					$registry->register(
						$option->get_key(),
						$option->get_registration_args()
					);
				}
			}
		);
	}

	/**
	 * Gets the plugin option names that are autoloaded.
	 *
	 * @since 0.1.0
	 *
	 * @return string[] List of autoloaded plugin options.
	 */
	private function get_autoloaded_options(): array {
		$autoloaded_options = array();

		foreach ( $this->container['option_container']->get_keys() as $key ) {
			// Trigger option instantiation so that the autoload config is populated.
			$this->container['option_container']->get( $key );

			$autoload = $this->container['option_repository']->get_autoload_config( $key );

			if ( true === $autoload ) {
				$autoloaded_options[] = $key;
			}
		}

		return $autoloaded_options;
	}

	/**
	 * Sets up the plugin container.
	 *
	 * @since 0.1.0
	 *
	 * @param string $main_file Absolute path to the plugin main file.
	 * @return Service_Container Plugin container.
	 */
	private function set_up_container( string $main_file ): Service_Container {
		$builder = new Plugin_Service_Container_Builder();

		return $builder->build_env( $main_file )
			->build_services()
			->get();
	}

	/**
	 * Registers the default AI services.
	 *
	 * @since 0.1.0
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	private function register_default_services(): void {
		$this->services_api->register_service(
			'google',
			static function ( Service_Registration_Context $context ) {
				return new Google_AI_Service(
					$context->get_metadata(),
					$context->get_authentication(),
					$context->get_request_handler()
				);
			},
			array(
				'name'            => 'Google (Gemini, Imagen)',
				'credentials_url' => 'https://aistudio.google.com/app/apikey',
				'type'            => Service_Type::CLOUD,
				'capabilities'    => AI_Capabilities::get_model_classes_capabilities(
					array(
						Google_AI_Text_Generation_Model::class,
					)
				),
				'allow_override'  => false,
			)
		);
		$this->services_api->register_service(
			'openai',
			static function ( Service_Registration_Context $context ) {
				return new OpenAI_AI_Service(
					$context->get_metadata(),
					$context->get_authentication(),
					$context->get_request_handler()
				);
			},
			array(
				'name'            => 'OpenAI (GPT, Dall-E)',
				'credentials_url' => 'https://platform.openai.com/api-keys',
				'type'            => Service_Type::CLOUD,
				'capabilities'    => AI_Capabilities::get_model_classes_capabilities(
					array(
						OpenAI_AI_Text_Generation_Model::class
					)
				),
				'allow_override'  => false,
			)
		);
		$this->services_api->register_service(
			'deepl',
			static function ( Service_Registration_Context $context ) {
				return new Deepl_AI_Service(
					$context->get_metadata(),
					$context->get_authentication(),
					$context->get_request_handler()
				);
			},
			array(
				'name'            => 'DeepL',
				'credentials_url' => 'https://www.deepl.com/docs-api/translating-text/',
				'type'            => Service_Type::CLOUD,
				'capabilities'    => AI_Capabilities::get_model_classes_capabilities(
					array(
						Deepl_AI_Text_Generation_Model::class
					)
				),
				'allow_override'  => false,
			)
		);
	}
}
