<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Deepl\Deepl_AI_Service
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Deepl;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums\AI_Capability;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Model_Metadata;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Service_Metadata;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Base\Abstract_AI_Service;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\Authentication;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\Generative_AI_Model;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\With_API_Client;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Exception\Generative_AI_Exception;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\HTTP\HTTP_With_Streams;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Traits\With_API_Client_Trait;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Util\AI_Capabilities;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\HTTP\Contracts\Request_Handler;

/**
 * Class for the Deepl AI service.
 *
 * @since 0.1.0
 * @since 0.7.0 Now extends `Abstract_AI_Service`.
 */
class Deepl_AI_Service extends Abstract_AI_Service implements With_API_Client {
	use With_API_Client_Trait;

	const DEFAULT_API_BASE_URL = 'https://api.deepl.com';
	const DEFAULT_API_VERSION  = 'v2';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Service_Metadata $metadata        The service metadata.
	 * @param Authentication   $authentication  The authentication credentials.
	 * @param Request_Handler  $request_handler Optional. The request handler instance to use for requests. Default is a
	 *                                          new HTTP_With_Streams instance.
	 */
	public function __construct( Service_Metadata $metadata, Authentication $authentication, ?Request_Handler $request_handler = null ) {
		$this->set_service_metadata( $metadata );
		$this->set_api_client(
			new Deepl_AI_API_Client(
				self::DEFAULT_API_BASE_URL,
				self::DEFAULT_API_VERSION,
				'DeepL',
				$request_handler ?? new HTTP_With_Streams(),
				$authentication
			)
		);
	}

	/**
	 * Lists the available generative model slugs and their metadata.
	 *
	 * @since 0.1.0
	 * @since 0.5.0 Return type changed to a map of model data shapes.
	 * @since 0.7.0 Return type changed to a map of model metadata objects.
	 *
	 * @param array<string, mixed> $request_options Optional. The request options. Default empty array.
	 * @return array<string, Model_Metadata> Metadata for each model, mapped by model slug.
	 *
	 * @throws Generative_AI_Exception Thrown if the request fails or the response is invalid.
	 */
	public function list_models( array $request_options = array() ): array {
		$api = $this->get_api_client();

		$request       = $api->create_get_request( 'usage', array(), $request_options );

		$response_data = $api->make_request( $request )->get_data();

		if ( ! isset( $response_data['character_count'] ) || ! $response_data['character_limit'] ) {
			throw $api->create_missing_response_key_exception( 'character_limit' );
		}

		return array('languages'=>Model_Metadata::from_array(array('slug'=>'languages','name'=>'Languages','capabilities'=>array(AI_Capability::TEXT_GENERATION))));
	}

	/**
	 * Creates a new model instance for the provided model metadata and parameters.
	 *
	 * @since 0.7.0
	 *
	 * @param Model_Metadata       $model_metadata  The model metadata.
	 * @param array<string, mixed> $model_params    Model parameters. See {@see Generative_AI_Service::get_model()} for
	 *                                              a list of available parameters.
	 * @param array<string, mixed> $request_options The request options.
	 * @return Generative_AI_Model The new model instance.
	 */
	protected function create_model_instance( Model_Metadata $model_metadata, array $model_params, array $request_options ): Generative_AI_Model {
		$model_class = AI_Capabilities::get_model_class_for_capabilities(
			array(
				Deepl_AI_Text_Generation_Model::class,
			),
			$model_metadata->get_capabilities()
		);

		return new $model_class(
			$this->get_api_client(),
			$model_metadata,
			$model_params,
			$request_options
		);
	}

	/**
	 * Sorts model slugs by preference.
	 *
	 * @since 0.1.0
	 *
	 * @param string[] $model_slugs The model slugs to sort.
	 * @return string[] The model slugs, sorted by preference.
	 */
	protected function sort_models_by_preference( array $model_slugs ): array {
		return $model_slugs;
	}
}
