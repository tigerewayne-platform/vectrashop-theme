<?php
/**
 * Optional AI reading of RFQs with the Claude API.
 *
 * Claude only turns the buyer's document or text into clean request lines (quantity + what they asked for).
 * It never sees prices and never picks products: matching happens in matcher.php against our catalogue.
 *
 * Raw HTTP via wp_remote_post because a WordPress plugin on shared hosting has no Composer.
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

function vectra_b2b_ai_enabled(): bool {
	return '' !== (string) vectra_b2b_opt( 'ai_key' );
}

/**
 * Read an RFQ into lines.
 *
 * @param string      $text     Pasted text (may be empty when a file is given).
 * @param string|null $pdf_path Path to an uploaded PDF, or null.
 * @return array|WP_Error List of { line, qty, text } in the same shape as vectra_b2b_parse_lines().
 */
function vectra_b2b_ai_read( string $text, ?string $pdf_path = null ) {
	$content = array();
	if ( $pdf_path && is_readable( $pdf_path ) ) {
		$bytes = file_get_contents( $pdf_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( strlen( $bytes ) > 20 * MB_IN_BYTES ) {
			return new WP_Error( 'too_big', __( 'That PDF is over 20 MB. Paste the item list instead.', 'vectra-b2b' ) );
		}
		$content[] = array(
			'type'   => 'document',
			'source' => array(
				'type'       => 'base64',
				'media_type' => 'application/pdf',
				'data'       => base64_encode( $bytes ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			),
		);
	}
	$content[] = array(
		'type' => 'text',
		'text' => "Read this request for quotation from a South African business buying IT equipment. List every item they want to buy.\n"
			. "For each item give the quantity (1 if not stated) and a short description that keeps every spec they gave (brand, model, CPU, RAM, storage, size, ports, colour).\n"
			. "Leave out delivery terms, company details, signatures and boilerplate. Do not suggest products or prices.\n\n"
			. ( '' !== trim( $text ) ? "Request text:\n" . $text : 'The request is in the attached document.' ),
	);

	$schema = array(
		'type'                 => 'object',
		'properties'           => array(
			'items' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'qty'         => array( 'type' => 'integer' ),
						'description' => array( 'type' => 'string' ),
						'as_written'  => array( 'type' => 'string', 'description' => 'The line as the buyer wrote it, shortened if long.' ),
					),
					'required'             => array( 'qty', 'description', 'as_written' ),
					'additionalProperties' => false,
				),
			),
		),
		'required'             => array( 'items' ),
		'additionalProperties' => false,
	);

	$body = array(
		'model'         => vectra_b2b_opt( 'ai_model' ),
		'max_tokens'    => 8000,
		'output_config' => array(
			'effort' => 'low',
			'format' => array( 'type' => 'json_schema', 'schema' => $schema ),
		),
		// If the model declines, let the API retry on a fallback model instead of failing the quote.
		'fallbacks'     => 'default',
		'messages'      => array( array( 'role' => 'user', 'content' => $content ) ),
	);

	$res = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
		'timeout' => 90,
		'headers' => array(
			'content-type'      => 'application/json',
			'x-api-key'         => vectra_b2b_opt( 'ai_key' ),
			'anthropic-version' => '2023-06-01',
			'anthropic-beta'    => 'server-side-fallback-2026-07-01',
		),
		'body'    => wp_json_encode( $body ),
	) );

	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = wp_remote_retrieve_response_code( $res );
	$json = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== $code || ! is_array( $json ) ) {
		$msg = $json['error']['message'] ?? ( 'HTTP ' . $code );
		return new WP_Error( 'ai_http', 'Claude API: ' . $msg );
	}
	if ( 'end_turn' !== ( $json['stop_reason'] ?? '' ) ) {
		return new WP_Error( 'ai_stop', 'Claude API stopped early: ' . ( $json['stop_reason'] ?? 'unknown' ) );
	}
	$text_out = '';
	foreach ( (array) $json['content'] as $block ) {
		if ( 'text' === ( $block['type'] ?? '' ) ) {
			$text_out .= $block['text'];
		}
	}
	$data = json_decode( $text_out, true );
	if ( ! is_array( $data ) || ! isset( $data['items'] ) ) {
		return new WP_Error( 'ai_json', 'Claude API returned something that was not a list of items.' );
	}
	$lines = array();
	foreach ( $data['items'] as $it ) {
		$desc = trim( (string) ( $it['description'] ?? '' ) );
		if ( '' === $desc ) {
			continue;
		}
		$lines[] = array(
			'line' => sanitize_text_field( (string) ( $it['as_written'] ?? $desc ) ),
			'qty'  => max( 1, min( 9999, (int) ( $it['qty'] ?? 1 ) ) ),
			'text' => sanitize_text_field( $desc ),
		);
	}
	return $lines;
}
