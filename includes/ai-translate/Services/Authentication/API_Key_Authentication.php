<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Authentication\API_Key_Authentication
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Authentication;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\Authentication;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\HTTP\Contracts\Request;

/**
 * Class that represents an API key.
 *
 * @since 0.1.0
 */
final class API_Key_Authentication implements Authentication {

	/**
	 * The API key.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $api_key;

	/**
	 * The HTTP header to use for the API key.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $header_name = 'Authorization';

		/**
	 * The authentication scheme to use for the API key.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $authencation_scheme = 'Bearer';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $api_key The API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Authenticates the given request with the credentials.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request The request instance. Updated in place.
	 */
	public function authenticate( Request $request ): void {
		if ( 'authorization' === strtolower( $this->header_name ) ) {
			$request->add_header( $this->header_name, $this->authencation_scheme . ' ' . $this->api_key );
		} else {
			$request->add_header( $this->header_name, $this->api_key );
		}
	}

	/**
	 * Sets the header name to use to add the credentials to a request.
	 *
	 * @since 0.1.0
	 *
	 * @param string $header_name The header name.
	 */
	public function set_header_name( string $header_name ): void {
		$this->header_name = $header_name;
	}

	public function set_authencation_scheme( string $authencation_scheme ): void {
		$this->authencation_scheme = $authencation_scheme;
	}

	/**
	 * Returns the option definitions needed to store the credentials.
	 *
	 * @since 0.1.0
	 *
	 * @param string $service_slug The service slug.
	 * @return array<string, array<string, mixed>> The option definitions.
	 */
	public static function get_option_definitions( string $service_slug ): array {
		$option_slug = sprintf( 'LocoAutomaticTranslateAddonPro_%s_api_key', $service_slug );

		return array(
			$option_slug => array(
				'type'         => 'string',
				'default'      => '',
				'show_in_rest' => true,
				'autoload'     => true,
			),
		);
	}
}
