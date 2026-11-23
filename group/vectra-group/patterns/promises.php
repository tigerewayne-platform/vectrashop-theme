<?php
/**
 * Title: Group promises (four USPs)
 * Slug: vectra-group/promises
 * Categories: vectra-group
 */
$vg_cards = array(
	array( 'Your quote holds', 'The price on the quote is the price on the invoice, and the stock is set aside for you while the quote is open. If the rand moves, that is our problem.', '15 reserved in Cape Town for you until Fri 17:00' ),
	array( 'A quote in a minute', 'Paste your list or upload the RFQ. We match every line to live stock and suggest a like-for-like option for anything short. Most suppliers take one to three days.', '6 lines matched · 1 alternative · 58 sec' ),
	array( 'The same model for 12 months', 'Pick a standard laptop and we keep supplying that model for a year. If the manufacturer changes it, you hear from us 60 days before, with the replacement.', 'HP ProBook 450 G10 locked to Nov 2027' ),
	array( 'Paperwork done for you', 'Every order carries your PO number and cost centre. Tax invoices, serial numbers and warranty end dates sit in one place, ready for your asset register and audit.', 'PO 4471 · CC Finance · 15 serials · warranty to 12/2029' ),
);
?>
<!-- wp:group {"className":"vg-section","layout":{"type":"default"}} -->
<div class="wp-block-group vg-section">
<!-- wp:paragraph {"className":"vg-label"} -->
<p class="vg-label">The Vectra standard</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Four commitments we put in writing.</h2>
<!-- /wp:heading -->
<!-- wp:group {"className":"vg-pledges","layout":{"type":"default"}} -->
<div class="wp-block-group vg-pledges">
<?php foreach ( $vg_cards as $c ) : ?>
<!-- wp:group {"className":"vg-pledge","layout":{"type":"default"}} -->
<div class="wp-block-group vg-pledge">
<!-- wp:heading {"level":3,"fontSize":"h2"} -->
<h3 class="wp-block-heading has-h2-font-size"><?php echo esc_html( $c[0] ); ?></h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-muted"} -->
<p class="vg-muted"><?php echo esc_html( $c[1] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"vg-eg"} -->
<p class="vg-eg"><?php echo esc_html( $c[2] ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
