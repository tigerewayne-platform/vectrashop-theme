<?php
/**
 * Title: Group how an order works
 * Slug: vectra-group/how-it-works
 * Categories: vectra-group
 */
$vg_steps = array(
	array( 'Send the list', 'Paste it, upload it, or email it to quotes@vectragroup.co.za.', 'about 1 minute' ),
	array( 'Check the quote', 'Lines, stock by city and alternatives. Change quantities, then share it with whoever signs off.', 'valid 4 working days' ),
	array( 'Approve with a PO', 'Add your PO and cost centre. Approved accounts pay in 30 days. New customers pay by EFT.', 'stock held while you approve' ),
	array( 'Delivered and logged', 'A delivery date, not a range. Serials and warranty dates land in your account the day it ships.', 'CPT and JHB next working day' ),
);
?>
<!-- wp:group {"className":"vg-section","layout":{"type":"default"}} -->
<div class="wp-block-group vg-section">
<!-- wp:paragraph {"className":"vg-label"} -->
<p class="vg-label">How an order works</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">From your list to your desks.</h2>
<!-- /wp:heading -->
<!-- wp:group {"className":"vg-steps","layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group vg-steps">
<?php foreach ( $vg_steps as $i => $s ) : ?>
<!-- wp:group {"className":"vg-step","layout":{"type":"default"}} -->
<div class="wp-block-group vg-step">
<!-- wp:paragraph {"className":"vg-step__n"} -->
<p class="vg-step__n"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size"><?php echo esc_html( $s[0] ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-muted"} -->
<p class="vg-muted"><?php echo esc_html( $s[1] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-step__t"} -->
<p class="vg-step__t"><?php echo esc_html( $s[2] ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
