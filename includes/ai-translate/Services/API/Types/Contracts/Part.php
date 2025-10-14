<?php
/**
 * Interface Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Contracts\Part
 *
 * @since 0.1.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Contracts;

use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Contracts\Arrayable;

/**
 * Interface for a class representing a part of content for a generative model.
 *
 * @since 0.1.0
 */
interface Part extends Arrayable {

	/**
	 * Sets data for the part.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $data The part data.
	 */
	public function set_data( array $data ): void;
}
