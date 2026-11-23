<?php
/**
 * Title: Group why Vectra (trust facts)
 * Slug: vectra-group/why-vectra
 * Categories: vectra-group
 * Description: Facts procurement asks for. Replace the B-BBEE card once the level is confirmed.
 */
$vg_facts = array(
	array( 'Partner status', 'HP Partner', 'Genuine stock with full local manufacturer warranty.' ),
	array( 'Warehouses', 'Cape Town and Johannesburg', 'Stock counts shown per city on every quote.' ),
	array( 'Registered in', 'South Africa and Zimbabwe', 'Company and VAT numbers on every invoice.' ),
	array( 'B-BBEE', 'Level to confirm', 'Certificate downloadable here once confirmed.' ),
);
?>
<!-- wp:group {"anchor":"why","className":"vg-section","layout":{"type":"default"}} -->
<div id="why" class="wp-block-group vg-section">
<!-- wp:paragraph {"className":"vg-label"} -->
<p class="vg-label">Why Vectra</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">The details procurement will ask for.</h2>
<!-- /wp:heading -->
<!-- wp:group {"className":"vg-facts-row","layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group vg-facts-row">
<?php foreach ( $vg_facts as $f ) : ?>
<!-- wp:group {"className":"vg-tfact","layout":{"type":"default"}} -->
<div class="wp-block-group vg-tfact">
<!-- wp:paragraph {"className":"vg-label"} -->
<p class="vg-label"><?php echo esc_html( $f[0] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-fact"} -->
<p class="vg-fact"><?php echo esc_html( $f[1] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-muted"} -->
<p class="vg-muted"><?php echo esc_html( $f[2] ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
