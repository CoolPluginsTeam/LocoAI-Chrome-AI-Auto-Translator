<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Deepl\Deepl_AI_Text_Generation_Model
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Deepl;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums\Content_Role;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Candidate;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Candidates;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Content;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Model_Metadata;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Parts;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Parts\Text_Part;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Base\Abstract_AI_Model;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\Generative_AI_API_Client;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\With_API_Client;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\With_Function_Calling;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\With_Text_Generation;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Exception\Generative_AI_Exception;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Traits\Model_Param_Text_Generation_Config_Trait;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Traits\With_API_Client_Trait;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Traits\With_Text_Generation_Trait;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Util\Transformer;
use Generator;
use InvalidArgumentException;

/**
 * Class representing a Deepl text generation AI model.
 *
 * @since 0.1.0
 * @since 0.5.0 Renamed from `Deepl_AI_Model`.
 */
	class Deepl_AI_Text_Generation_Model extends Abstract_AI_Model implements With_API_Client, With_Text_Generation, With_Function_Calling {
		use With_API_Client_Trait;
		use With_Text_Generation_Trait;
		use Model_Param_Text_Generation_Config_Trait;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Generative_AI_API_Client $api_client      The AI API client instance.
	 * @param Model_Metadata           $metadata        The model metadata.
	 * @param array<string, mixed>     $model_params    Optional. Additional model parameters. See
	 *                                                  {@see Deepl_AI_Service::get_model()} for the list of available
	 *                                                  parameters. Default empty array.
	 * @param array<string, mixed>     $request_options Optional. The request options. Default empty array.
	 *
	 * @throws InvalidArgumentException Thrown if the model parameters are invalid.
	 */
	public function __construct( Generative_AI_API_Client $api_client, Model_Metadata $metadata, array $model_params = array(), array $request_options = array() ) {
		$this->set_api_client( $api_client );
		$this->set_model_metadata( $metadata );

		$this->set_text_generation_config_from_model_params( $model_params );

		$this->set_request_options( $request_options );
	}

	/**
	 * Sends a request to generate text content.
	 *
	 * @since 0.1.0
	 *
	 * @param Content[]            $contents        Prompts for the content to generate.
	 * @param array<string, mixed> $request_options The request options.
	 * @return Candidates The response candidates with generated text content - usually just one.
	 *
	 * @throws Generative_AI_Exception Thrown if the request fails or the response is invalid.
	 */
	protected function send_generate_text_request( array $contents, array $request_options ): Candidates {

		$api    = $this->get_api_client();
		$params = $this->prepare_generate_text_params( $contents );

		$request  = $api->create_post_request(
			'translate',
			$params,
			array_merge(
				$this->get_request_options(),
				$request_options
			)
		);

		$response = $api->make_request( $request );

		return $api->process_response_data(
			$response,
			function ( $response_data ) {
				return $this->get_response_candidates( $response_data );
			}
		);
	}

	/**
	 * Sends a request to generate text content, streaming the response.
	 *
	 * @since 0.3.0
	 *
	 * @param Content[]            $contents        Prompts for the content to generate.
	 * @param array<string, mixed> $request_options The request options.
	 * @return Generator<Candidates> Generator that yields the chunks of response candidates with generated text
	 *                               content - usually just one candidate.
	 *
	 * @throws Generative_AI_Exception Thrown if the request fails or the response is invalid.
	 */
	protected function send_stream_generate_text_request( array $contents, array $request_options ): Generator {
		$api    = $this->get_api_client();
		$params = $this->prepare_generate_text_params( $contents );

		$model = $this->get_model_slug();
		if ( ! str_contains( $model, '/' ) ) {
			$model = 'models/' . $model;
		}

		$request  = $api->create_post_request(
			"{$model}:streamGenerateContent",
			$params,
			array_merge(
				$this->get_request_options(),
				$request_options,
				array( 'stream' => true )
			)
		);

		$response = $api->make_request( $request );

		return $api->process_response_stream(
			$response,
			function ( $response_data, $prev_chunk_candidates ) {
				return $this->get_response_candidates( $response_data, $prev_chunk_candidates );
			}
		);
	}

	/**
	 * Prepares the API request parameters for generating text content.
	 *
	 * @since 0.3.0
	 *
	 * @param Content[] $contents The contents to generate text for.
	 * @return array<string, mixed> The parameters for generating text content.
	 */
	private function prepare_generate_text_params( array $contents ): array {
		$transformers = $this->get_content_transformers();

		$params = Transformer::transform_content( $contents[0], $transformers );

		if(isset($params['parts'])){
			$params = json_decode($params['parts'][0]['text'], true);
		}

		return array_filter( $params );
	}

	/**
	 * Extracts the candidates with content from the response.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $response_data The response data.
	 * @param ?Candidates          $prev_chunk_candidates The candidates from the previous chunk in case of a streaming
	 *                                                    response, or null.
	 * @return Candidates The candidates with content parts.
	 *
	 * @throws Generative_AI_Exception Thrown if the response does not have any candidates with content.
	 */
	private function get_response_candidates( array $response_data, ?Candidates $prev_chunk_candidates = null ): Candidates {
		if ( ! isset( $response_data['translations'] ) ) {
			throw $this->get_api_client()->create_missing_response_key_exception( 'translations' );
		}

		if ( null === $prev_chunk_candidates ) {
			$other_data = $response_data;
			unset( $other_data['translations'] );

			$candidates = new Candidates();

			$translate_strings=array();
			$translation_data=array();

			foreach ( $response_data['translations'] as $index => $translation_data ) {
				$translate_strings[$index]=$translation_data['text'];

				if(!isset($translation_data['confidence'])){
					$translation_data['confidence']=0;
				}

				if(!isset($translation_data['detected_source_language'])){
					$translation_data['detected_source_language']='';
				}
			}

			$translation_data['text']=json_encode($translate_strings, JSON_FORCE_OBJECT);

			$candidates->add_candidate(
				new Candidate(
					$this->prepare_translation_content( $translation_data ),
					$other_data
				)
			);

			return $candidates;
		}

		// Subsequent chunk of a streaming response.
		$candidates_data = $this->merge_translation_chunk(
			$prev_chunk_candidates->to_array(),
			$response_data
		);

		return Candidates::from_array( $candidates_data );
	}

	/**
	 * Merges a streaming response chunk with the previous candidates data.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, mixed> $candidates_data The candidates data from the previous chunk.
	 * @param array<string, mixed> $chunk_data      The response chunk data.
	 * @return array<string, mixed> The merged candidates data.
	 *
	 * @throws Generative_AI_Exception Thrown if the response is invalid.
	 */
	private function merge_translation_chunk( array $candidates_data, array $chunk_data ): array {
		if ( ! isset( $chunk_data['translations'] ) ) {
			throw $this->get_api_client()->create_missing_response_key_exception( 'translations' );
		}

		$other_data = $chunk_data;
		unset( $other_data['translations'] );

		foreach ( $chunk_data['translations'] as $index => $candidate_data ) {
			$candidates_data[ $index ] = array_merge( $candidates_data[ $index ], $candidate_data, $other_data );
		}

		return $candidates_data;
	}

	/**
	 * Transforms a given choice from the API response into a Content instance.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, mixed> $choice_data The API response candidate data.
	 * @param int                  $index       The index of the choice in the response.
	 * @return Content The Content instance.
	 *
	 * @throws Generative_AI_Exception Thrown if the response is invalid.
	 */
	private function prepare_translation_content( array $translation_data ): Content {
		return new Content(
			Content_Role::MODEL,
			$this->prepare_translation_content_parts( $translation_data )
		);
	}

	private function prepare_translation_content_parts( array $translation_data ): Parts {
		$parts[] = array(
			'text' => $translation_data['text'],
			'detected_source_language' => $translation_data['detected_source_language'] ?? '',
			'confidence' => $translation_data['confidence'] ?? 0,
		);

		return Parts::from_array($parts);
	}

	/**
	 * Gets the content transformers.
	 *
	 * @since 0.2.0
	 * @since 0.7.0 Changed to non-static.
	 *
	 * @return array<string, callable> The content transformers.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function get_content_transformers(): array {
		return array(
			'role'  => static function ( Content $content ) {
				return $content->get_role();
			},
			'parts' => static function ( Content $content ) {
				$parts = array();
				foreach ( $content->get_parts() as $part ) {
					if ( $part instanceof Text_Part ) {
						$parts[] = array( 'text' => $part->get_text() );
					}
				}
				return $parts;
			},
		);
	}
}
