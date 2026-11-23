<?php
/**
 * Title: Vectra Group header
 * Slug: vectra-group/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 */
$vg_logo = esc_url( get_stylesheet_directory_uri() . '/assets/logo/vectra-group-inline-reverse.svg' );
$vg_home = esc_url( home_url( '/' ) );
?>
<!-- wp:group {"tagName":"header","className":"vectra-header vg-header","backgroundColor":"graphite-frame","textColor":"frame-ink","layout":{"type":"default"}} -->
<header class="wp-block-group vectra-header vg-header has-frame-ink-color has-graphite-frame-background-color has-text-color has-background">
<!-- wp:group {"className":"vectra-header__main","layout":{"type":"constrained","contentSize":"1120px"}} -->
<div class="wp-block-group vectra-header__main">
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:image {"width":"190px","sizeSlug":"full","linkDestination":"custom","className":"vectra-logo"} -->
<figure class="wp-block-image size-full is-resized vectra-logo"><a href="<?php echo $vg_home; ?>"><img src="<?php echo $vg_logo; ?>" alt="Vectra Group" style="width:190px"/></a></figure>
<!-- /wp:image -->
<!-- wp:navigation {"overlayMenu":"mobile","className":"vg-nav","layout":{"type":"flex","flexWrap":"wrap"},"style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<!-- wp:navigation-link {"label":"Get a quote","url":"/#quote","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Catalogue","url":"/shop/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Managed support","url":"/#support","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Government","url":"/#government","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Offices","url":"/#offices","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Why Vectra","url":"/#why","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"My account","url":"/my-account/","kind":"custom"} /-->
<!-- /wp:navigation -->
<!-- wp:buttons {"className":"vg-header__cta"} -->
<div class="wp-block-buttons vg-header__cta">
<!-- wp:button {"backgroundColor":"copper","textColor":"copper-ink","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size"><a class="wp-block-button__link has-copper-ink-color has-copper-background-color has-text-color has-background wp-element-button" href="/open-an-account/">Open a business account</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</header>
<!-- /wp:group -->
