<?php
/**
 * Title: Group who we work with
 * Slug: vectra-group/who
 * Categories: vectra-group
 */
$vg_who = array(
	array( 'Small and medium businesses', 'From a five-person office to a few hundred staff, in South Africa and Zimbabwe. New-starter kits, refresh cycles and the urgent replacement, without a procurement department.' ),
	array( 'Government in Zimbabwe', 'Ministries, departments and parastatals, quoted against your tender in USD, with the compliance pack ready.' ),
	array( 'IT service providers', 'Order on your client\'s behalf, with the paperwork addressed to them. Ask about partner pricing.' ),
);
?>
<!-- wp:group {"className":"vg-section","layout":{"type":"default"}} -->
<div class="wp-block-group vg-section">
<!-- wp:paragraph {"className":"vg-label"} -->
<p class="vg-label">Who we work with</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">SMEs in two countries, and government in Zimbabwe.</h2>
<!-- /wp:heading -->
<!-- wp:group {"className":"vg-grid","layout":{"type":"grid","minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group vg-grid">
<?php foreach ( $vg_who as $w ) : ?>
<!-- wp:group {"className":"vg-card","layout":{"type":"default"}} -->
<div class="wp-block-group vg-card">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size"><?php echo esc_html( $w[0] ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-muted"} -->
<p class="vg-muted"><?php echo esc_html( $w[1] ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
