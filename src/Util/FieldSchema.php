<?php
/**
 * Utility for encoding and decoding the field schema WP option.
 *
 * The field schema is stored as a JSON string in the wmr_field_schema option.
 * This utility provides a typed interface for reading and writing it.
 *
 * @package WpMembershipRegistration
 */

namespace WpMembershipRegistration\Util;

/**
 * Encodes and decodes the field schema option value.
 */
class FieldSchema {

	/**
	 * Encode an array of field objects to a JSON string for storage.
	 *
	 * @param array<int, array{label: string, type: string, required: bool}> $fields Field definitions.
	 * @return string JSON-encoded string; '[]' on failure.
	 */
	public static function encode( array $fields ): string {
		$encoded = wp_json_encode( $fields );
		return false !== $encoded ? $encoded : '[]';
	}

	/**
	 * Decode the stored JSON string to an array of field objects.
	 *
	 * @param string $json JSON string from get_option('wmr_field_schema').
	 * @return array<int, array{label: string, type: string, required: bool}>
	 */
	public static function decode( string $json ): array {
		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}
}
