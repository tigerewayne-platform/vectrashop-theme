<?php
/**
 * Title: Vectra header
 * Slug: vectra/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 */
$vectra_logo = esc_url( get_theme_file_uri( 'assets/logo/vectra-shop-inline-reverse.svg' ) );
$vectra_home = esc_url( home_url( '/' ) );
?>
<!-- wp:group {"tagName":"header","className":"vectra-header","backgroundColor":"graphite-frame","textColor":"frame-ink","layout":{"type":"default"}} -->
<header class="wp-block-group vectra-header has-frame-ink-color has-graphite-frame-background-color has-text-color has-background">

<!-- wp:group {"className":"vectra-topstrip","backgroundColor":"graphite","textColor":"frame-ink","fontSize":"small","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group vectra-topstrip has-frame-ink-color has-graphite-background-color has-text-color has-background has-small-font-size">
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Stock in Cape Town &amp; Johannesburg · Pay by card or instant EFT</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vectra-header__main","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group vectra-header__main">
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:image {"width":"220px","sizeSlug":"full","linkDestination":"custom","className":"vectra-logo"} -->
<figure class="wp-block-image size-full is-resized vectra-logo"><a href="<?php echo $vectra_home; ?>"><img src="<?php echo $vectra_logo; ?>" alt="Vectra Shop" style="width:220px"/></a></figure>
<!-- /wp:image -->

<!-- wp:search {"label":"Search products","showLabel":false,"placeholder":"Search products, brands or SKUs","buttonText":"Search","buttonPosition":"button-inside","buttonUseIcon":true,"query":{"post_type":"product"}} /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group">
<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconClass":"wc-block-customer-account__account-icon"} /-->
<!-- wp:woocommerce/mini-cart /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"vectra-catnav","backgroundColor":"card","textColor":"graphite","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group vectra-catnav has-graphite-color has-card-background-color has-text-color has-background">
<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","flexWrap":"nowrap"},"style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<!-- wp:navigation-link {"label":"Components","url":"/product-category/components/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Computers","url":"/product-category/computers/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Peripherals","url":"/product-category/computer-peripherals/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Networking","url":"/product-category/networking-security/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Power &amp; backup","url":"/product-category/power/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Home tech","url":"/product-category/lifestyle-home-tech/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Software","url":"/product-category/software/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Appliances","url":"/product-category/appliances/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Deals","url":"/shop/?on_sale=1","kind":"custom"} /-->
<!-- /wp:navigation -->
</div>
<!-- /wp:group -->

</header>
<!-- /wp:group -->
