<?php
/**
 * Interface Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\HTTP\Contracts\With_Stream
 *
 * @since 0.3.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\HTTP\Contracts;

use Generator;

/**
 * Interface for a class that contains a readable stream.
 *
 * @since 0.3.0
 */
interface With_Stream {

	/**
	 * Returns a generator that reads individual chunks of decoded JSON data from the streamed response body.
	 *
	 * @since 0.3.0
	 *
	 * @return Generator The generator for the response stream.
	 */
	public function read_stream(): Generator;
}
