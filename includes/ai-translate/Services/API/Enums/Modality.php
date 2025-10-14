<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums\Modality
 *
 * @since 0.7.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums;

/**
 * Class for the modality enum.
 *
 * @since 0.7.0
 */
final class Modality extends Abstract_Enum {

	const TEXT  = 'text';
	const IMAGE = 'image';
	const AUDIO = 'audio';

	/**
	 * Gets all values for the enum.
	 *
	 * @since 0.7.0
	 *
	 * @return string[] The list of all values.
	 */
	protected static function get_all_values(): array {
		return array(
			self::TEXT,
			self::IMAGE,
			self::AUDIO,
		);
	}
}
