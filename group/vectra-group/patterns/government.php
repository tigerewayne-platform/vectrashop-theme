<?php
/**
 * Title: Group government and tenders (Zimbabwe)
 * Slug: vectra-group/government
 * Categories: vectra-group
 * Description: Tender-ready section for Zimbabwe government buyers, with the compliance pack. Link each document once uploaded to the Media Library, with its expiry date.
 */
$vg_points = array(
	array( 'Quotes against your tender number', 'Bill of quantities format, line for line with your specification.' ),
	array( 'USD or ZAR pricing', 'Invoiced by the Zimbabwe company for Zimbabwe buyers.' ),
	array( 'Validity to match the tender', 'Not the 4-day hold we give businesses. Agreed per tender.' ),
	array( 'Delivery and installation in Harare', 'Serials and warranty dates on the delivery note for your asset register.' ),
);
$vg_docs = array( 'PRAZ supplier registration', 'ZIMRA tax clearance (ITF263)', 'Certificate of incorporation', 'CR14, directors', 'NSSA compliance', 'HP partner letter' );
?>
<!-- wp:group {"anchor":"government","className":"vg-section vg-gov","layout":{"type":"default"}} -->
<div id="government" class="wp-block-group vg-section vg-gov">
<!-- wp:columns {"verticalAlignment":"top"} -->
<div class="wp-block-columns are-vertically-aligned-top">
<!-- wp:column {"width":"54%"} -->
<div class="wp-block-column" style="flex-basis:54%">
<!-- wp:paragraph {"className":"vg-label"} -->
<p class="vg-label">Government and public sector</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Tender-ready, from Harare.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"vg-muted"} -->
<p class="vg-muted">We supply ministries, departments and parastatals in Zimbabwe. Every quote can be issued against your tender reference, priced in US dollars, with validity matched to the tender. Our compliance documents are a click away, so your evaluation committee isn't waiting on paperwork.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<ul class="vg-gov-points"><?php foreach ( $vg_points as $pt ) : ?><li><b><?php echo esc_html( $pt[0] ); ?></b><span><?php echo esc_html( $pt[1] ); ?></span></li><?php endforeach; ?></ul>
<!-- /wp:html -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"graphite","textColor":"bench"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-bench-color has-graphite-background-color has-text-color has-background wp-element-button" href="/contact/?topic=tender">Request a tender quote</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:html -->
<div class="vg-pack"><div class="vg-pack__head"><span class="vg-label">Compliance pack</span><b>The Vectra Group (Private) Limited</b></div><ul><?php foreach ( $vg_docs as $d ) : ?><li><span><?php echo esc_html( $d ); ?></span><em><?php esc_html_e( 'On request', 'vectra-group' ); ?></em></li><?php endforeach; ?></ul><p class="vg-muted">Each document shows its expiry date. A lapsed certificate comes off the list, so what you download is current.</p></div>
<!-- /wp:html -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
