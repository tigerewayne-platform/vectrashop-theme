<?php
/**
 * Homepage sections that need live catalogue data: hero slider, category carousel,
 * product rails and the brand strip. Each is a dynamic block so it can be moved or
 * removed in the Site Editor, and each reads stock and images straight from the catalogue,
 * so the homepage changes as the Syntech sync runs.
 *
 * @package Vectra
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Data helpers
 * ---------------------------------------------------------------------- */

/**
 * Cache key that changes whenever a product is saved, so cached sections never go stale.
 */
function vectra_home_cache_key( string $name, $args = array() ): string {
	return 'vectra_home_' . md5( $name . wp_json_encode( $args ) . get_option( 'vectra_home_v', 1 ) );
}
add_action( 'woocommerce_update_product', function () {
	update_option( 'vectra_home_v', (int) get_option( 'vectra_home_v', 1 ) + 1, false );
} );

/**
 * In-stock, visible products that have an image.
 *
 * @param array $args cats (slugs, children included), limit, max_price, orderby (sales|price|date).
 * @return int[] Product IDs.
 */
function vectra_home_products( array $args ): array {
	$args = wp_parse_args( $args, array( 'cats' => array(), 'limit' => 12, 'max_price' => 0, 'orderby' => 'sales', 'exclude_cats' => array() ) );
	$key  = vectra_home_cache_key( 'products', $args );
	$ids  = get_transient( $key );
	if ( false !== $ids ) {
		return $ids;
	}

	$tax = array(
		'relation' => 'AND',
		array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => array( 'exclude-from-catalog', 'outofstock' ), 'operator' => 'NOT IN' ),
	);
	if ( $args['cats'] ) {
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => (array) $args['cats'], 'include_children' => true );
	}
	if ( $args['exclude_cats'] ) {
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => (array) $args['exclude_cats'], 'operator' => 'NOT IN', 'include_children' => true );
	}
	$meta = array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) );
	if ( $args['max_price'] ) {
		$meta[] = array( 'key' => '_price', 'value' => (float) $args['max_price'], 'compare' => '<=', 'type' => 'NUMERIC' );
	}

	$q = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['limit'],
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	switch ( $args['orderby'] ) {
		case 'price':
			$q['meta_key'] = '_price';
			$q['orderby']  = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
			break;
		case 'date':
			$q['orderby'] = 'date';
			break;
		default:
			$q['meta_key'] = 'total_sales';
			$q['orderby']  = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
	}
	$ids = array_map( 'intval', get_posts( $q ) );
	set_transient( $key, $ids, HOUR_IN_SECONDS );
	return $ids;
}

/**
 * Lowest in-stock price in some categories, for "from R399" lines.
 */
function vectra_home_from_price( array $cats ): string {
	$ids = vectra_home_products( array( 'cats' => $cats, 'limit' => 200, 'orderby' => 'date' ) );
	$min = 0;
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		$v = $p ? (float) $p->get_price() : 0;
		if ( $v > 0 && ( ! $min || $v < $min ) ) {
			$min = $v;
		}
	}
	return $min ? 'R' . number_format( $min, 0, '.', ' ' ) : '';
}

function vectra_home_img( int $product_id, string $size = 'woocommerce_thumbnail', string $loading = 'lazy', ?string $alt = null ): string {
	$id = (int) get_post_thumbnail_id( $product_id );
	return $id ? wp_get_attachment_image( $id, $size, false, array( 'loading' => $loading, 'alt' => null === $alt ? get_the_title( $product_id ) : $alt ) ) : '';
}

function vectra_home_arrows( string $label ): string {
	return '<div class="vectra-rail__nav">'
		. '<button type="button" class="vectra-rail__btn" data-dir="-1" aria-label="' . esc_attr( sprintf( __( 'Previous %s', 'vectra' ), $label ) ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>'
		. '<button type="button" class="vectra-rail__btn" data-dir="1" aria-label="' . esc_attr( sprintf( __( 'Next %s', 'vectra' ), $label ) ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>'
		. '</div>';
}

/* -------------------------------------------------------------------------
 * Hero slider
 * ---------------------------------------------------------------------- */

/**
 * Slides. Copy follows the brand voice; images come from in-stock products in each slide's categories.
 * Change campaigns here (or filter 'vectra_hero_slides' from a plugin).
 */
function vectra_hero_slides(): array {
	return apply_filters( 'vectra_hero_slides', array(
		array(
			'eyebrow' => __( 'Load-shedding ready', 'vectra' ),
			'title'   => __( 'Keep the Wi-Fi on when the lights go off', 'vectra' ),
			'text'    => __( 'UPS units, DC mini UPSs and power stations from %from%. Stock shown for Cape Town and Johannesburg before you buy.', 'vectra' ),
			'cta'     => __( 'Shop power & backup', 'vectra' ),
			'url'     => '/product-category/power/',
			'cats'    => array( 'ups-inverters', 'portable-power-stations', 'power-banks' ),
		),
		array(
			'eyebrow' => __( 'Graphics cards', 'vectra' ),
			'title'   => __( 'Upgrade the card, keep the rig', 'vectra' ),
			'text'    => __( 'Current GeForce and Radeon cards from ASUS, ASRock and MSI, from %from%. Real stock, not supplier back-orders.', 'vectra' ),
			'cta'     => __( 'Shop graphics cards', 'vectra' ),
			'url'     => '/product-category/components/graphics-cards/',
			'cats'    => array( 'graphics-cards' ),
		),
		array(
			'eyebrow' => __( 'Wi-Fi and mesh', 'vectra' ),
			'title'   => __( 'Fix the dead zones for good', 'vectra' ),
			'text'    => __( 'Wi-Fi 7 routers, mesh kits and range extenders from %from%. Works with your fibre box.', 'vectra' ),
			'cta'     => __( 'Shop networking', 'vectra' ),
			'url'     => '/product-category/networking-security/',
			'cats'    => array( 'routers-mesh', 'range-extenders-access-points' ),
		),
	) );
}

function vectra_render_hero_slider(): string {
	$slides = vectra_hero_slides();
	$out    = '';
	foreach ( array_values( $slides ) as $i => $s ) {
		$ids   = vectra_home_products( array( 'cats' => $s['cats'], 'limit' => 3, 'orderby' => 'price' ) );
		$stack = '';
		foreach ( $ids as $n => $pid ) {
			$stack .= '<a class="vectra-hero__shot is-' . ( $n + 1 ) . '" href="' . esc_url( get_permalink( $pid ) ) . '" tabindex="-1">' . vectra_home_img( $pid, 'woocommerce_single', 0 === $i ? 'eager' : 'lazy' ) . '</a>';
		}
		$from = vectra_home_from_price( $s['cats'] );
		$text = str_replace( '%from%', $from ? $from : __( 'local stock', 'vectra' ), $s['text'] );
		$out .= sprintf(
			'<div class="vectra-hero__slide%1$s" role="group" aria-roledescription="slide" aria-label="%2$s"%3$s>
				<div class="vectra-hero__copy">
					<p class="vectra-eyebrow">%4$s</p>
					<h2 class="vectra-hero__title">%5$s</h2>
					<p class="vectra-hero__text">%6$s</p>
					<a class="wp-element-button vectra-hero__cta" href="%7$s">%8$s</a>
				</div>
				<div class="vectra-hero__stack" aria-hidden="true">%9$s</div>
			</div>',
			0 === $i ? ' is-active' : '',
			esc_attr( sprintf( '%d of %d', $i + 1, count( $slides ) ) ),
			0 === $i ? '' : ' aria-hidden="true"',
			esc_html( $s['eyebrow'] ),
			esc_html( $s['title'] ),
			esc_html( $text ),
			esc_url( $s['url'] ),
			esc_html( $s['cta'] ),
			$stack
		);
	}
	$dots = '';
	foreach ( array_keys( $slides ) as $i ) {
		$dots .= '<button type="button" class="vectra-hero__dot' . ( 0 === $i ? ' is-active' : '' ) . '" data-slide="' . $i . '" aria-label="' . esc_attr( sprintf( __( 'Show slide %d', 'vectra' ), $i + 1 ) ) . '"></button>';
	}
	return '<section class="vectra-hero vectra-hero--slider" data-vectra-hero aria-roledescription="carousel" aria-label="' . esc_attr__( 'Featured', 'vectra' ) . '">'
		. '<div class="vectra-hero__inner">' . $out . '</div>'
		. '<div class="vectra-hero__controls">' . $dots . '</div>'
		. '</section>';
}

/* -------------------------------------------------------------------------
 * Category carousel
 * ---------------------------------------------------------------------- */

function vectra_render_category_carousel(): string {
	$key   = vectra_home_cache_key( 'cats' );
	$cards = get_transient( $key );
	if ( false === $cards ) {
		$cards = array();
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
		$subs  = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent__not_in' => array( 0 ) ) );
		// Top-level first, then the busiest sub-categories, so the carousel has enough to move.
		usort( $subs, function ( $a, $b ) {
			return $b->count <=> $a->count;
		} );
		foreach ( array_merge( (array) $terms, array_slice( (array) $subs, 0, 14 ) ) as $t ) {
			$ids = vectra_home_products( array( 'cats' => array( $t->slug ), 'limit' => 500, 'orderby' => 'price' ) );
			if ( ! $ids ) {
				continue;
			}
			$thumb = (int) get_term_meta( $t->term_id, 'thumbnail_id', true );
			$cards[] = array(
				'name'  => html_entity_decode( $t->name ),
				'url'   => get_term_link( $t ),
				'count' => count( $ids ),
				'img'   => $thumb ? wp_get_attachment_image( $thumb, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'alt' => '' ) ) : vectra_home_img( $ids[0], 'woocommerce_thumbnail', 'lazy', '' ),
			);
		}
		set_transient( $key, $cards, HOUR_IN_SECONDS );
	}
	if ( ! $cards ) {
		return '';
	}
	$items = '';
	foreach ( $cards as $c ) {
		$items .= sprintf(
			'<li class="vectra-cat"><a href="%1$s"><span class="vectra-cat__img">%2$s</span><span class="vectra-cat__name">%3$s</span><span class="vectra-cat__count">%4$s</span></a></li>',
			esc_url( $c['url'] ),
			$c['img'],
			esc_html( $c['name'] ),
			esc_html( sprintf( _n( '%d in stock', '%d in stock', $c['count'], 'vectra' ), $c['count'] ) )
		);
	}
	return '<section class="vectra-rail vectra-rail--cats" data-vectra-carousel>'
		. '<div class="vectra-rail__head"><h2>' . esc_html__( 'Shop by category', 'vectra' ) . '</h2>' . vectra_home_arrows( __( 'categories', 'vectra' ) ) . '</div>'
		. '<ul class="vectra-rail__track" role="list">' . $items . '</ul></section>';
}

/* -------------------------------------------------------------------------
 * Product rail
 * ---------------------------------------------------------------------- */

function vectra_product_card( int $pid ): string {
	$p = wc_get_product( $pid );
	if ( ! $p ) {
		return '';
	}
	$brands = taxonomy_exists( 'product_brand' ) ? get_the_terms( $pid, 'product_brand' ) : array();
	$brand  = ( $brands && ! is_wp_error( $brands ) ) ? '<span class="vectra-brand-label">' . esc_html( $brands[0]->name ) . '</span>' : '';
	$url    = get_permalink( $pid );

	if ( $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() ) {
		$btn = sprintf(
			'<a href="%1$s" data-quantity="1" data-product_id="%2$d" data-product_sku="%3$s" class="wp-element-button add_to_cart_button ajax_add_to_cart vectra-card__btn" aria-label="%4$s" rel="nofollow">%5$s</a>',
			esc_url( $p->add_to_cart_url() ),
			$pid,
			esc_attr( $p->get_sku() ),
			esc_attr( sprintf( __( 'Add %s to cart', 'vectra' ), $p->get_name() ) ),
			esc_html__( 'Add to cart', 'vectra' )
		);
	} else {
		$btn = '<a href="' . esc_url( $url ) . '" class="wp-element-button vectra-card__btn">' . esc_html__( 'View', 'vectra' ) . '</a>';
	}

	$badge = $p->is_on_sale() ? '<span class="vectra-card__badge">' . esc_html__( 'Sale', 'vectra' ) . '</span>' : '';

	return sprintf(
		'<li class="vectra-card vectra-rail__item">%1$s<a class="vectra-card__img" href="%2$s" tabindex="-1" aria-hidden="true">%3$s</a>%4$s<h3 class="vectra-card__title"><a href="%2$s">%5$s</a></h3><div class="vectra-card__price">%6$s</div>%7$s%8$s</li>',
		$badge,
		esc_url( $url ),
		vectra_home_img( $pid ),
		$brand,
		esc_html( $p->get_name() ),
		$p->get_price_html(),
		vectra_stock_html( $pid ),
		$btn
	);
}

function vectra_render_product_rail( array $a ): string {
	$a   = wp_parse_args( $a, array( 'title' => '', 'cats' => '', 'limit' => 12, 'maxPrice' => 0, 'orderby' => 'sales', 'link' => '', 'linkLabel' => '', 'excludeCats' => '' ) );
	$ids = vectra_home_products( array(
		'cats'         => array_filter( array_map( 'trim', explode( ',', $a['cats'] ) ) ),
		'exclude_cats' => array_filter( array_map( 'trim', explode( ',', $a['excludeCats'] ) ) ),
		'limit'        => (int) $a['limit'],
		'max_price'    => (float) $a['maxPrice'],
		'orderby'      => $a['orderby'],
	) );
	if ( count( $ids ) < 4 ) {
		return ''; // A rail with two products looks broken; hide it until stock arrives.
	}
	$items = implode( '', array_map( 'vectra_product_card', $ids ) );
	$more  = $a['link'] ? '<a class="vectra-rail__more" href="' . esc_url( $a['link'] ) . '">' . esc_html( $a['linkLabel'] ? $a['linkLabel'] : __( 'See all', 'vectra' ) ) . '</a>' : '';
	return '<section class="vectra-rail vectra-rail--products" data-vectra-carousel>'
		. '<div class="vectra-rail__head"><h2>' . esc_html( $a['title'] ) . '</h2>' . $more . vectra_home_arrows( $a['title'] ) . '</div>'
		. '<ul class="vectra-rail__track" role="list">' . $items . '</ul></section>';
}

/* -------------------------------------------------------------------------
 * Brand strip
 * ---------------------------------------------------------------------- */

function vectra_render_brand_strip(): string {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return '';
	}
	$terms = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 24 ) );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	$list = '';
	foreach ( $terms as $t ) {
		$list .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
	}
	// The list is printed twice so the scroll loops without a gap; the copy is hidden from screen readers.
	return '<section class="vectra-brands" aria-label="' . esc_attr__( 'Brands we stock', 'vectra' ) . '">'
		. '<p class="vectra-eyebrow">' . esc_html__( 'Brands we stock', 'vectra' ) . '</p>'
		. '<div class="vectra-brands__viewport"><ul class="vectra-brands__track" role="list">' . $list . '</ul><ul class="vectra-brands__track" aria-hidden="true">' . str_replace( '<a ', '<a tabindex="-1" ', $list ) . '</ul></div>'
		. '</section>';
}

/* -------------------------------------------------------------------------
 * Blocks and assets
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	wp_register_script( 'vectra-home-editor', get_theme_file_uri( 'assets/js/home-blocks.js' ), array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components' ), VECTRA_VERSION, true );

	$common = array( 'api_version' => 3, 'category' => 'woocommerce', 'editor_script' => 'vectra-home-editor', 'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ) );

	register_block_type( 'vectra/hero-slider', $common + array(
		'title'           => __( 'Vectra hero slider', 'vectra' ),
		'render_callback' => 'vectra_render_hero_slider',
	) );
	register_block_type( 'vectra/category-carousel', $common + array(
		'title'           => __( 'Vectra category carousel', 'vectra' ),
		'render_callback' => 'vectra_render_category_carousel',
	) );
	register_block_type( 'vectra/brand-strip', $common + array(
		'title'           => __( 'Vectra brand strip', 'vectra' ),
		'render_callback' => 'vectra_render_brand_strip',
	) );
	register_block_type( 'vectra/product-rail', $common + array(
		'title'           => __( 'Vectra product rail', 'vectra' ),
		'attributes'      => array(
			'title'       => array( 'type' => 'string', 'default' => 'Popular right now' ),
			'cats'        => array( 'type' => 'string', 'default' => '' ),
			'excludeCats' => array( 'type' => 'string', 'default' => '' ),
			'limit'       => array( 'type' => 'number', 'default' => 12 ),
			'maxPrice'    => array( 'type' => 'number', 'default' => 0 ),
			'orderby'     => array( 'type' => 'string', 'default' => 'sales' ),
			'link'        => array( 'type' => 'string', 'default' => '' ),
			'linkLabel'   => array( 'type' => 'string', 'default' => '' ),
		),
		'render_callback' => 'vectra_render_product_rail',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$path = get_theme_file_path( 'assets/js/vectra-ui.js' );
	wp_enqueue_script( 'vectra-ui', get_theme_file_uri( 'assets/js/vectra-ui.js' ), array(), VECTRA_VERSION . '.' . filemtime( $path ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	if ( is_front_page() && function_exists( 'WC' ) ) {
		wp_enqueue_script( 'wc-add-to-cart' ); // Ajax add-to-cart for the rail buttons.
	}
} );
