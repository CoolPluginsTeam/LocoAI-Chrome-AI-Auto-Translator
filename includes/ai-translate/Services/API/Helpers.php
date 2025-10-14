<?php
/**
 * Class Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Helpers
 *
 * @since 0.2.0
 * @package ai-services
 */

namespace Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API;

use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Enums\Content_Role;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Blob;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Candidates;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Content;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Parts;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\API\Types\Parts\Text_Part;
use Loco_Automatic_Translate_Addon_Pro\AI_Translate\Services\Util\Formatter;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\General\Current_User;
use Felix_Arntz\Loco_Automatic_Translate_Addon_Pro\WP_OOP_Plugin_Lib\Meta\Meta_Repository;
use Generator;
use InvalidArgumentException;

/**
 * Class providing static helper methods as part of the public API.
 *
 * @since 0.2.0
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class Helpers {

	/**
	 * Converts a text string to a Content instance.
	 *
	 * @since 0.2.0
	 *
	 * @param string $text The text.
	 * @param string $role Optional. The role to use for the content. Default 'user'.
	 * @return Content The content instance.
	 */
	public static function text_to_content( string $text, string $role = Content_Role::USER ): Content {
		return Formatter::format_content( $text, $role );
	}

	/**
	 * Converts a Content instance to a text string.
	 *
	 * This method will return the combined text from all consecutive text parts in the content.
	 * Realistically, this should almost always return the text from just one part, as API responses typically do not
	 * contain multiple text parts in a row - but it might be possible.
	 *
	 * @since 0.2.0
	 *
	 * @param Content $content The content instance.
	 * @return string The text, or an empty string if there are no text parts.
	 */
	public static function content_to_text( Content $content ): string {
		$parts = $content->get_parts();

		$text_parts = array();
		foreach ( $parts as $part ) {
			/*
			 * If there is any non-text part present, we want to ensure that no interrupted text content is returned.
			 * Therefore, we break the loop as soon as we encounter a non-text part, unless no text parts have been
			 * found yet, in which case the text may only start with a later part.
			 */
			if ( ! $part instanceof Text_Part ) {
				if ( count( $text_parts ) > 0 ) {
					break;
				}
				continue;
			}

			$text_parts[] = trim( $part->get_text() );
		}

		if ( count( $text_parts ) === 0 ) {
			return '';
		}

		return implode( "\n\n", $text_parts );
	}

	/**
	 * Gets the text from the first Content instance in the given list which contains text.
	 *
	 * @since 0.2.0
	 *
	 * @param Content[] $contents The list of Content instances.
	 * @return string The text, or an empty string if no Content instance has text parts.
	 */
	public static function get_text_from_contents( array $contents ): string {
		foreach ( $contents as $content ) {
			$text = self::content_to_text( $content );
			if ( '' !== $text ) {
				return $text;
			}
		}

		return '';
	}

	/**
	 * Gets the first Content instance in the given list which contains text.
	 *
	 * @since 0.5.0
	 *
	 * @param Content[] $contents The list of Content instances.
	 * @return Content|null The Content instance, or null if no Content instance has text parts.
	 */
	public static function get_text_content_from_contents( array $contents ): ?Content {
		foreach ( $contents as $content ) {
			$text = self::content_to_text( $content );
			if ( '' !== $text ) {
				return $content;
			}
		}

		return null;
	}

	/**
	 * Gets the Content instances for each candidate in the given list.
	 *
	 * @since 0.2.0
	 *
	 * @param Candidates $candidates The list of candidates.
	 * @return Content[] The list of Content instances.
	 */
	public static function get_candidate_contents( Candidates $candidates ): array {
		$contents = array();

		foreach ( $candidates as $candidate ) {
			$content = $candidate->get_content();
			if ( ! $content ) {
				continue;
			}
			$contents[] = $content;
		}

		return $contents;
	}

	/**
	 * Processes a stream of candidates, aggregating the candidates chunks into a single candidates instance.
	 *
	 * This method returns a stream processor instance that can be used to read all chunks from the given candidates
	 * generator and process them with a callback. Alternatively, you can read from the generator yourself and provide
	 * all chunks to the processor manually.
	 *
	 * @since 0.3.0
	 *
	 * @param Generator<Candidates> $generator The generator that yields the chunks of response candidates.
	 * @return Candidates_Stream_Processor The stream processor instance.
	 */
	public static function process_candidates_stream( Generator $generator ): Candidates_Stream_Processor { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		return new Candidates_Stream_Processor( $generator );
	}

	/**
	 * Returns the base64-encoded data URL representation of the given file URL.
	 *
	 * @since 0.5.0
	 *
	 * @param string $file      Absolute path to the file, or its URL.
	 * @param string $mime_type Optional. The MIME type of the file. If provided, the base64-encoded data URL will
	 *                          be prefixed with `data:{mime_type};base64,`. Default empty string.
	 * @return string The base64-encoded file data URL, or empty string on failure.
	 */
	public static function file_to_base64_data_url( string $file, string $mime_type = '' ): string {
		$blob = self::file_to_blob( $file, $mime_type );
		if ( ! $blob ) {
			return '';
		}

		return self::blob_to_base64_data_url( $blob );
	}

	/**
	 * Returns the binary data blob representation of the given file URL.
	 *
	 * @since 0.5.0
	 *
	 * @param string $file      Absolute path to the file, or its URL.
	 * @param string $mime_type Optional. The MIME type of the file. If provided, the automatically detected MIME type
	 *                          will be overwritten. Default empty string.
	 * @return Blob|null The binary data blob, or null on failure.
	 */
	public static function file_to_blob( string $file, string $mime_type = '' ): ?Blob {
		try {
			return Blob::from_file( $file, $mime_type );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Returns the base64-encoded data URL representation of the given binary data blob.
	 *
	 * @since 0.5.0
	 *
	 * @param Blob $blob The binary data blob.
	 * @return string The base64-encoded file data URL, or empty string on failure.
	 */
	public static function blob_to_base64_data_url( Blob $blob ): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$base64    = base64_encode( $blob->get_binary_data() );
		$mime_type = $blob->get_mime_type();
		return "data:$mime_type;base64,$base64";
	}

	/**
	 * Returns the binary data blob representation of the given base64-encoded data URL.
	 *
	 * @since 0.5.0
	 *
	 * @param string $base64_data_url The base64-encoded data URL.
	 * @return Blob|null The binary data blob, or null on failure.
	 */
	public static function base64_data_url_to_blob( string $base64_data_url ): ?Blob {
		if ( ! preg_match( '/^data:([a-z0-9-]+\/[a-z0-9-]+);base64,/', $base64_data_url, $matches ) ) {
			return null;
		}

		$base64 = substr( $base64_data_url, strlen( $matches[0] ) );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$binary_data = base64_decode( $base64 );
		if ( false === $binary_data ) {
			return null;
		}

		return new Blob( $binary_data, $matches[1] );
	}

	/**
	 * Ensures the given base64 data is prefixed correctly to be a data URL.
	 *
	 * @since 0.6.0
	 *
	 * @param string $base64_data Base64-encoded data. If it is already a data URL, it will be returned as is.
	 * @param string $mime_type   MIME type for the data.
	 * @return string The base64 data URL.
	 */
	public static function base64_data_to_base64_data_url( string $base64_data, string $mime_type ): string {
		if ( str_starts_with( $base64_data, 'data:' ) ) {
			return $base64_data;
		}

		return 'data:' . $mime_type . ';base64,' . $base64_data;
	}

	/**
	 * Ensures the given base64 data URL has its prefix removed to be just the base64 data.
	 *
	 * @since 0.6.0
	 *
	 * @param string $base64_data_url Base64 data URL. If it is already without prefix, it will be returned as is.
	 * @return string The base64-encoded data.
	 */
	public static function base64_data_url_to_base64_data( string $base64_data_url ): string {
		if ( ! str_starts_with( $base64_data_url, 'data:' ) ) {
			return $base64_data_url;
		}

		return preg_replace(
			'/^data:[a-z0-9-]+\/[a-z0-9-]+;base64,/',
			'',
			$base64_data_url
		);
	}
}
