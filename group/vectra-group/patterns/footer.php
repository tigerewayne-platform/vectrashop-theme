<?php
/**
 * Title: Vectra Group footer
 * Slug: vectra-group/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 */
$vg_logo = esc_url( get_stylesheet_directory_uri() . '/assets/logo/vectra-group-inline-reverse.svg' );
?>
<!-- wp:group {"tagName":"footer","className":"vectra-footer","backgroundColor":"graphite-frame","textColor":"frame-ink","fontSize":"small","layout":{"type":"constrained","contentSize":"1120px"}} -->
<footer class="wp-block-group vectra-footer has-frame-ink-color has-graphite-frame-background-color has-text-color has-background has-small-font-size">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%">
<!-- wp:image {"width":"170px","sizeSlug":"full"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo $vg_logo; ?>" alt="Vectra Group" style="width:170px"/></figure>
<!-- /wp:image -->
<!-- wp:paragraph -->
<p>IT equipment and managed support for SMEs in South Africa and Zimbabwe, and government in Zimbabwe. Buying for home? Visit <a href="https://www.vectrashop.co.za">vectrashop.co.za</a>.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size">Sandton</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Johannesburg, South Africa</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size">Cape Town</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Western Cape, South Africa</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size">Harare</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Zimbabwe</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"fontSize":"body"} -->
<h3 class="wp-block-heading has-body-font-size">Talk to us</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>quotes@vectragroup.co.za<br>Mon to Fri, 08:00 to 17:00</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- /wp:columns -->
<!-- wp:separator {"className":"is-style-wide","backgroundColor":"slate"} -->
<hr class="wp-block-separator has-text-color has-slate-color has-alpha-channel-opacity has-slate-background-color has-background is-style-wide"/>
<!-- /wp:separator -->
<!-- wp:paragraph -->
<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> The Vectra Group. Business prices are shown ex VAT. <a href="/terms-of-trade/">Terms of trade</a> · <a href="/#offices">Offices</a></p>
<!-- /wp:paragraph -->
</footer>
<!-- /wp:group -->
