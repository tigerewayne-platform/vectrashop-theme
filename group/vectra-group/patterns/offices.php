<?php
/**
 * Title: Group offices (Sandton, Cape Town, Harare)
 * Slug: vectra-group/offices
 * Categories: vectra-group
 * Description: The three offices with live local time and open or closed. Add street addresses and phone numbers in the editor once confirmed.
 */
$vg_offices = array(
	array( 'South Africa', 'Sandton', 'Johannesburg. Gauteng accounts and the Johannesburg warehouse.', 'Africa/Johannesburg' ),
	array( 'South Africa', 'Cape Town', 'Western Cape accounts and the Cape Town warehouse.', 'Africa/Johannesburg' ),
	array( 'Zimbabwe', 'Harare', 'Zimbabwe accounts, quoted and invoiced by our Zimbabwe company.', 'Africa/Harare' ),
);
?>
<!-- wp:group {"align":"full","anchor":"offices","className":"vg-offices-band","backgroundColor":"graphite-frame","textColor":"frame-ink","layout":{"type":"constrained","contentSize":"1120px"}} -->
<div id="offices" class="wp-block-group alignfull vg-offices-band has-frame-ink-color has-graphite-frame-background-color has-text-color has-background">
<!-- wp:paragraph {"className":"vg-eyebrow"} -->
<p class="vg-eyebrow">Our offices</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Three offices. One standard.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-frame-soft"} -->
<p class="vg-frame-soft">Your account, pricing and paperwork are the same whichever office you deal with.</p>
<!-- /wp:paragraph -->
<!-- wp:group {"className":"vg-offices","layout":{"type":"default"}} -->
<div class="wp-block-group vg-offices">
<?php foreach ( $vg_offices as $o ) : ?>
<!-- wp:group {"className":"vg-office","layout":{"type":"default"}} -->
<div class="wp-block-group vg-office">
<!-- wp:paragraph {"className":"vg-office__country"} -->
<p class="vg-office__country"><?php echo esc_html( $o[0] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"vg-office__city"} -->
<h3 class="wp-block-heading vg-office__city"><?php echo esc_html( $o[1] ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-frame-soft"} -->
<p class="vg-frame-soft"><?php echo esc_html( $o[2] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<p class="vg-office__foot"><span class="vg-otime" data-tz="<?php echo esc_attr( $o[3] ); ?>"></span><span class="vg-ostate" data-tz="<?php echo esc_attr( $o[3] ); ?>"></span></p>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
