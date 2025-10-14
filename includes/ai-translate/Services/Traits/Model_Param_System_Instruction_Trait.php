<?php
/**
 * Trait Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Traits\Model_Param_System_Instruction_Trait
 *
 * @since 0.7.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Traits;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Content;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Util\Formatter;
use InvalidArgumentException;

/**
 * Trait for a model that uses a system instruction.
 *
 * @since 0.7.0
 */
trait Model_Param_System_Instruction_Trait {

	/**
	 * The system instruction.
	 *
	 * @since 0.7.0
	 * @var Content|null
	 */
	private $system_instruction;

	/**
	 * Gets the system instruction.
	 *
	 * @since 0.7.0
	 *
	 * @return Content|null The system instruction, or null if not set.
	 */
	final protected function get_system_instruction(): ?Content {
		return $this->system_instruction;
	}

	/**
	 * Sets the system instruction.
	 *
	 * @since 0.7.0
	 *
	 * @param Content $system_instruction The system instruction.
	 */
	final protected function set_system_instruction( Content $system_instruction ): void {
		$this->system_instruction = $system_instruction;
	}

	/**
	 * Sets the system instruction if provided in the `systemInstruction` model parameter.
	 *
	 * @since 0.7.0
	 *
	 * @param array<string, mixed> $model_params The model parameters.
	 *
	 * @throws InvalidArgumentException Thrown if the `systemInstruction` model parameter is invalid.
	 */
	protected function set_system_instruction_from_model_params( array $model_params ): void {
		if ( ! isset( $model_params['systemInstruction'] ) ) {
			return;
		}

		try {
			$model_params['systemInstruction'] = Formatter::format_system_instruction( $model_params['systemInstruction'] );
		} catch ( InvalidArgumentException $e ) {
			throw new InvalidArgumentException(
				sprintf(
					'Invalid systemInstruction model parameter: %s',
					htmlspecialchars( $e->getMessage() ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				)
			);
		}

		$this->set_system_instruction( $model_params['systemInstruction'] );
	}
}
