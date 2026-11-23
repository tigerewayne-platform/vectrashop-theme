<?php
/**
 * Title: Group who we work with
 * Slug: vectra-group/who
 * Categories: vectra-group
 */
$vg_who = array(
	array( 'Offices of 10 to 200 people', 'New-starter kits, refresh cycles and the odd urgent replacement, without a procurement department.' ),
	array( 'Schools', 'Staff laptops, lab refreshes and classroom displays, quoted the way governing bodies need to see them.' ),
	array( 'IT service providers', 'Order on your client\'s behalf, with the paperwork addressed to them. Talk to us about partner pricing.' ),
);
?>
<!-- wp:group {"className":"vg-section","layout":{"type":"default"}} -->
<div class="wp-block-group vg-section">
<!-- wp:paragraph {"className":"vg-label"} -->
<p class="vg-label">Who we work with</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Built for the person who runs IT on the side.</h2>
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
