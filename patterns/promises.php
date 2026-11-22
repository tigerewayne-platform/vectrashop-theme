<?php
/**
 * Title: Things we won't do
 * Slug: vectra/promises
 * Categories: vectra
 * Description: The five public promises from the brand system. Used by the page with the slug "promises".
 */
$vectra_items = array(
	array( 'No "in stock" unless it is on a shelf.', 'Every product shows how many are in Cape Town and Johannesburg today. If a warehouse is empty, we say so, and when the next shipment lands.' ),
	array( 'No fake "was" prices.', 'A crossed-out price means we sold it at that price in the last 30 days. We never invent a higher price to make a discount look bigger.' ),
	array( 'No countdown timers or made-up scarcity.', 'If it says 2 left, there are 2 left. Nothing on this site is designed to rush you.' ),
	array( 'No surprises at checkout.', 'Prices include VAT. Delivery is shown before you pay. There are no card fees or handling fees added at the end.' ),
	array( 'No upsell we wouldn\'t buy ourselves.', 'If a cheaper product does the job, we\'ll tell you. Ask us anything and you\'ll get a straight answer.' ),
);
?>
<!-- wp:group {"className":"vectra-promises","layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group vectra-promises">
<!-- wp:paragraph {"className":"vectra-promises__lede"} -->
<p class="vectra-promises__lede">No surprises. Just tech. Most tech stores sell the same products. We decided the difference would be how straight we are with you. These are the rules we hold ourselves to.</p>
<!-- /wp:paragraph -->
<?php foreach ( $vectra_items as $item ) : ?>
<!-- wp:group {"className":"vectra-promise","layout":{"type":"default"}} -->
<div class="wp-block-group vectra-promise">
<!-- wp:heading {"level":2,"fontSize":"h2"} -->
<h2 class="wp-block-heading has-h-2-font-size"><?php echo esc_html( $item[0] ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html( $item[1] ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
<!-- wp:paragraph {"className":"vectra-promises__foot","textColor":"slate"} -->
<p class="vectra-promises__foot has-slate-color has-text-color">If we break one of these, tell us. It matters more to us than the sale.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
