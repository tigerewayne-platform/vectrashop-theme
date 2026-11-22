<?php
/**
 * Title: Vectra footer
 * Slug: vectra/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 */
$vectra_logo = esc_url( get_theme_file_uri( 'assets/logo/vectra-shop-horizontal-reverse.svg' ) );
?>
<!-- wp:group {"tagName":"footer","className":"vectra-footer","backgroundColor":"graphite-frame","textColor":"frame-ink","fontSize":"small","layout":{"type":"constrained","contentSize":"1280px"}} -->
<footer class="wp-block-group vectra-footer has-frame-ink-color has-graphite-frame-background-color has-text-color has-background has-small-font-size">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
<!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%">
<!-- wp:image {"width":"150px","sizeSlug":"full"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo $vectra_logo; ?>" alt="Vectra Shop" style="width:150px"/></figure>
<!-- /wp:image -->
<!-- wp:paragraph -->
<p>Local stock, straight answers. IT equipment from Cape Town and Johannesburg warehouses, priced incl. VAT.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size">Shop</h3>
<!-- /wp:heading -->
<!-- wp:list {"className":"is-style-default"} -->
<ul class="wp-block-list is-style-default"><!-- wp:list-item --><li><a href="/shop/">All products</a></li><!-- /wp:list-item --><!-- wp:list-item --><li><a href="/shop/?on_sale=1">Deals</a></li><!-- /wp:list-item --><!-- wp:list-item --><li><a href="/product-category/power/">Power &amp; backup</a></li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size">Help</h3>
<!-- /wp:heading -->
<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item --><li><a href="/delivery/">Delivery</a></li><!-- /wp:list-item --><!-- wp:list-item --><li><a href="/returns/">Returns &amp; warranty</a></li><!-- /wp:list-item --><!-- wp:list-item --><li><a href="/contact/">Contact us</a></li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size">Pay securely</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Card via Yoco. Instant EFT via Ozow.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:separator {"className":"is-style-wide","backgroundColor":"slate"} -->
<hr class="wp-block-separator has-text-color has-slate-color has-alpha-channel-opacity has-slate-background-color has-background is-style-wide"/>
<!-- /wp:separator -->
<!-- wp:paragraph -->
<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> The Vectra Group. Prices include 15% VAT.</p>
<!-- /wp:paragraph -->
</footer>
<!-- /wp:group -->
