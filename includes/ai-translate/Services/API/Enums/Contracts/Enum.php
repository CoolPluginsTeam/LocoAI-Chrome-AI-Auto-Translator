<?php
/**
 * Interface Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums\Contracts\Enum
 *
 * @since 0.2.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums\Contracts;

/**
 * Interface for a class for an enum.
 *
 * @since 0.2.0
 */
interface Enum {

	/**
	 * Checks if the given value is valid for the enum.
	 *
	 * @since 0.2.0
	 *
	 * @param string $value The value to check.
	 * @return bool True if the value is valid, false otherwise.
	 */
	public static function is_valid_value( string $value ): bool;

	/**
	 * Gets the list of valid values for the enum.
	 *
	 * @since 0.2.0
	 *
	 * @return string[] The list of valid values.
	 */
	public static function get_values(): array;
}
