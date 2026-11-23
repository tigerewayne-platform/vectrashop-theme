<?php
/**
 * RFQ matching: turn a request line ("15 x laptops, i5, 16GB") into a catalogue product.
 *
 * The AI (ai.php) can do the reading; matching to products, prices and stock is always done here,
 * against our own catalogue, so a quote can never contain a product we do not sell.
 *
 * @package VectraB2B
 */

defined( 'ABSPATH' ) || exit;

/**
 * Words that mean the same thing in a request and in a product title.
 */
function vectra_b2b_synonyms(): array {
	return array(
		array( 'laptop', 'laptops', 'notebook', 'notebooks' ),
		array( 'desktop', 'desktops', 'pc', 'tower', 'workstation' ),
		array( 'dock', 'docks', 'docking' ),
		array( 'headset', 'headsets', 'headphone', 'headphones' ),
		array( 'monitor', 'monitors', 'display', 'screen', 'screens' ),
		array( 'printer', 'printers', 'mfp', 'laserjet' ),
		array( 'ups', 'inverter' ),
		array( 'wifi', 'wi-fi', 'access', 'unifi', 'mesh' ),
		array( 'keyboard', 'keyboards' ),
		array( 'mouse', 'mice' ),
		array( 'webcam', 'webcams', 'camera' ),
		array( 'switch', 'switches' ),
		array( 'router', 'routers' ),
		array( 'ssd', 'solid' ),
		array( 'colour', 'color' ),
		array( 'tablet', 'tablets', 'ipad' ),
		array( 'projector', 'projectors' ),
	);
}

function vectra_b2b_stopwords(): array {
	return array( 'x', 'for', 'the', 'a', 'an', 'and', 'with', 'of', 'to', 'new', 'staff', 'units', 'unit', 'pcs', 'qty', 'please', 'need', 'we', 'our', 'office', 'or', 'similar', 'equivalent', 'in', 'on', 'each', 'per', 'usb', 'inch', 'station', 'stations', 'point', 'points', 'type', 'mic', 'network', 'floor', 'a4' );
}

/**
 * Split pasted text into request lines with a quantity. Pure function.
 *
 * @return array<int, array{line:string, qty:int, text:string}>
 */
function vectra_b2b_parse_lines( string $text ): array {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n|;/', $text ) as $raw ) {
		$line = trim( preg_replace( '/^\s*(?:[-*•·]|\d+[.)])\s+/u', '', $raw ) );
		if ( '' === $line || mb_strlen( $line ) < 3 ) {
			continue;
		}
		$qty  = 0;
		$rest = $line;
		$patterns = array(
			'/^(\d{1,4})\s*(?:x|×|units?|pcs|no\.?)?\s+(.+)$/iu',             // 15 x laptops / 15 laptops
			'/^(?:qty|quantity)[:\s]*(\d{1,4})[\s,:-]+(.+)$/iu',            // Qty: 15 laptops
			'/^(.+?)[\s,:-]+(?:x|×|qty[:\s]*)\s*(\d{1,4})\s*(?:units?|pcs)?$/iu', // laptops x 15
			'/^(.+?)\s*\((\d{1,4})\)$/u',                                   // laptops (15)
		);
		foreach ( $patterns as $i => $p ) {
			if ( preg_match( $p, $line, $m ) ) {
				if ( $i < 2 ) {
					$qty  = (int) $m[1];
					$rest = $m[2];
				} else {
					$qty  = (int) $m[2];
					$rest = $m[1];
				}
				break;
			}
		}
		$out[] = array(
			'line' => $line,
			'qty'  => max( 1, $qty ),
			'text' => trim( $rest ),
		);
	}
	return $out;
}

/**
 * Normalise text into match tokens. "16 GB" -> "16gb", 24" -> "24". Pure function.
 */
function vectra_b2b_tokens( string $text ): array {
	$t = mb_strtolower( $text );
	$t = preg_replace( '/(\d+)\s*(gb|tb|mb|w|kva|va|hz|mp|ghz)\b/u', '$1$2', $t );
	$t = preg_replace( '/(\d+(?:\.\d+)?)\s*(?:"|”|inch(?:es)?|in\b)/u', '$1 inch', $t );
	$t = str_replace( array( 'wi-fi', 'usb-c', 'type-c' ), array( 'wifi', 'usbc', 'usbc' ), $t );
	$t = preg_replace( '/[^a-z0-9.\- ]+/u', ' ', $t );
	$words = preg_split( '/[\s\-]+/', $t, -1, PREG_SPLIT_NO_EMPTY );
	$stop  = vectra_b2b_stopwords();
	$words = array_values( array_unique( array_filter( $words, static fn( $w ) => ! in_array( $w, $stop, true ) && strlen( $w ) > 1 ) ) );
	return $words;
}

/**
 * Score how well a product title answers a request. 0..1. Pure function.
 * Spec tokens (anything with a digit, e.g. i5, 16gb, 24) count double.
 */
function vectra_b2b_score( array $tokens, string $title ): float {
	if ( ! $tokens ) {
		return 0.0;
	}
	$title_tokens = vectra_b2b_tokens( $title );
	$hay          = ' ' . implode( ' ', $title_tokens ) . ' ';
	$groups       = vectra_b2b_synonyms();
	$total        = 0;
	$hit          = 0;
	$noun_hit     = false;
	$type_asked   = false; // the request names a kind of product (laptop, dock, monitor...)
	$type_hit     = false;
	foreach ( $tokens as $tok ) {
		$is_spec = (bool) preg_match( '/\d/', $tok );
		$weight  = $is_spec ? 2 : 1;
		$total  += $weight;
		$alts    = array( $tok );
		$is_type = false;
		foreach ( $groups as $g ) {
			if ( in_array( $tok, $g, true ) ) {
				$alts    = $g;
				$is_type = true;
				break;
			}
		}
		$type_asked = $type_asked || $is_type;
		foreach ( $alts as $a ) {
			if ( str_contains( $hay, ' ' . $a . ' ' ) || ( $is_spec && str_contains( $hay, $a ) ) ) {
				$hit += $weight;
				if ( ! $is_spec ) {
					$noun_hit = true;
				}
				if ( $is_type ) {
					$type_hit = true;
				}
				break;
			}
		}
	}
	// A title that only shares numbers with the request is not a match,
	// and a request for a laptop is never answered with a RAM stick.
	if ( ! $noun_hit || ( $type_asked && ! $type_hit ) ) {
		return 0.0;
	}
	return $hit / $total;
}

/**
 * Stock per warehouse, from the catalogue sync meta, falling back to WooCommerce's own count.
 */
function vectra_b2b_stock( WC_Product $p ): array {
	$cpt = $p->get_meta( '_vectra_stock_cpt', true );
	$jhb = $p->get_meta( '_vectra_stock_jhb', true );
	if ( '' === $cpt && '' === $jhb ) {
		// Not stock-managed but in stock: count is unknown, so treat it as plenty (999) rather than none.
		$q = $p->managing_stock() ? (int) $p->get_stock_quantity() : ( $p->is_in_stock() ? 999 : 0 );
		return array( 'cpt' => max( 0, $q ), 'jhb' => 0, 'eta' => (string) $p->get_meta( '_vectra_eta', true ) );
	}
	return array( 'cpt' => max( 0, (int) $cpt ), 'jhb' => max( 0, (int) $jhb ), 'eta' => (string) $p->get_meta( '_vectra_eta', true ) );
}

/**
 * Stock still free after other open quotes' reservations.
 */
function vectra_b2b_available( WC_Product $p, int $exclude_quote = 0 ): array {
	$s   = vectra_b2b_stock( $p );
	$res = vectra_b2b_reserved_for( $p->get_id(), $exclude_quote );
	return array(
		'cpt' => max( 0, $s['cpt'] - $res['cpt'] ),
		'jhb' => max( 0, $s['jhb'] - $res['jhb'] ),
		'eta' => $s['eta'],
	);
}

/**
 * Split a quantity across warehouses, Cape Town first. Pure function.
 */
function vectra_b2b_allocate( int $qty, array $avail ): array {
	$cpt = min( $qty, $avail['cpt'] );
	$jhb = min( $qty - $cpt, $avail['jhb'] );
	return array( 'cpt' => $cpt, 'jhb' => $jhb, 'short' => $qty - $cpt - $jhb );
}

/**
 * Pick the best product for one request line.
 *
 * Among products that match about as well as the best one, prefer one with enough free stock,
 * then the cheaper one: the cheapest product that does the job, as the brand promises.
 *
 * @return array|null { product_id, score, alt, reserve{cpt,jhb,short} }
 */
function vectra_b2b_match_line( string $text, int $qty ): ?array {
	$tokens = vectra_b2b_tokens( $text );
	if ( ! $tokens ) {
		return null;
	}
	$words = array_filter( $tokens, static fn( $t ) => ! preg_match( '/\d/', $t ) );
	$ids   = array();
	foreach ( array_slice( array_values( $words ), 0, 4 ) as $w ) {
		$search = array( $w );
		foreach ( vectra_b2b_synonyms() as $g ) {
			if ( in_array( $w, $g, true ) ) {
				$search = array_slice( $g, 0, 3 );
				break;
			}
		}
		foreach ( $search as $s ) {
			$ids = array_merge( $ids, wc_get_products( array( 's' => $s, 'status' => 'publish', 'limit' => 60, 'return' => 'ids', 'type' => array( 'simple', 'variation' ) ) ) );
		}
	}
	$ids = array_unique( $ids );
	if ( ! $ids ) {
		return null;
	}
	$scored = array();
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p || '' === $p->get_price() ) {
			continue;
		}
		$score = vectra_b2b_score( $tokens, $p->get_name() );
		if ( $score >= 0.3 ) {
			$avail    = vectra_b2b_available( $p );
			$scored[] = array(
				'p'     => $p,
				'score' => $score,
				'free'  => $avail['cpt'] + $avail['jhb'],
				'avail' => $avail,
				'price' => (float) wc_get_price_excluding_tax( $p ),
			);
		}
	}
	if ( ! $scored ) {
		return null;
	}
	usort( $scored, static fn( $a, $b ) => $b['score'] <=> $a['score'] ?: $a['price'] <=> $b['price'] );
	$best  = $scored[0];
	$close = array_filter( $scored, static fn( $c ) => $c['score'] >= $best['score'] - 0.12 );
	$enough = array_filter( $close, static fn( $c ) => $c['free'] >= $qty );
	if ( $enough ) {
		usort( $enough, static fn( $a, $b ) => $a['price'] <=> $b['price'] );
		$pick = reset( $enough );
	} else {
		usort( $close, static fn( $a, $b ) => $b['free'] <=> $a['free'] );
		$pick = reset( $close );
	}
	// Flag it as an alternative when the closest match was passed over for stock.
	$alt = $pick['p']->get_id() !== $best['p']->get_id() && $best['free'] < $qty;
	return array(
		'product_id' => $pick['p']->get_id(),
		'score'      => round( $pick['score'], 2 ),
		'alt'        => $alt,
		'reserve'    => vectra_b2b_allocate( $qty, $pick['avail'] ),
	);
}
