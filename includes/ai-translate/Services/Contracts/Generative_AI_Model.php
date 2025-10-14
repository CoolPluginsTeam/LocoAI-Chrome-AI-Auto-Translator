<?php
/**
 * Interface Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts\Generative_AI_Model
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Contracts;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Model_Metadata;

/**
 * Interface for a class representing a generative AI model.
 *
 * @since 0.1.0
 */
interface Generative_AI_Model {

	/**
	 * Gets the model slug.
	 *
	 * @since 0.1.0
	 *
	 * @return string The model slug.
	 */
	public function get_model_slug(): string;

	/**
	 * Gets the model metadata.
	 *
	 * @since 0.7.0
	 *
	 * @return Model_Metadata The model metadata.
	 */
	public function get_model_metadata(): Model_Metadata;
}
