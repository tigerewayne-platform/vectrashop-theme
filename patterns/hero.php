<?php
/**
 * Title: Hero: power and backup
 * Slug: vectra/hero
 * Categories: vectra
 * Description: Graphite hero panel with the Split V mark. Swap the copy per campaign.
 */
$vectra_mark = esc_url( get_theme_file_uri( 'assets/logo/vectra-mark-reverse-transparent.svg' ) );
?>
<!-- wp:group {"className":"vectra-hero","backgroundColor":"graphite-frame","textColor":"frame-ink","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group vectra-hero has-frame-ink-color has-graphite-frame-background-color has-text-color has-background">
<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center","width":"60%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%">
<!-- wp:paragraph {"className":"vectra-eyebrow","textColor":"copper","fontSize":"small"} -->
<p class="vectra-eyebrow has-copper-color has-text-color has-small-font-size">LOAD-SHEDDING READY</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"fontSize":"hero"} -->
<h1 class="wp-block-heading has-hero-font-size">Keep the Wi-Fi on when the lights go off</h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>UPS units, inverters and power stations, shipped from Cape Town or Johannesburg. See exactly what's in stock before you buy.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/product-category/power/">Shop power &amp; backup</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">
<!-- wp:image {"sizeSlug":"full","className":"vectra-hero__mark"} -->
<figure class="wp-block-image size-full vectra-hero__mark"><img src="<?php echo $vectra_mark; ?>" alt=""/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
