<?php
namespace NexusBuilder\Compatibility;

class WooCommerce {

    public function init(): void {
        add_action('nexusbuilder/elements/register', [$this, 'register_wc_elements']);
        add_filter('nexusbuilder/renderer/render', [$this, 'handle_wc_shortcodes'], 10, 2);
    }

    public function register_wc_elements(\NexusBuilder\Elements\Registry $registry): void {
        $registry->register(new WCElements\ProductCard());
        $registry->register(new WCElements\ProductGrid());
        $registry->register(new WCElements\AddToCart());
        $registry->register(new WCElements\PriceDisplay());
        $registry->register(new WCElements\ProductGallery());
        $registry->register(new WCElements\RelatedProducts());
        $registry->register(new WCElements\CartWidget());
        $registry->register(new WCElements\MiniCart());
        $registry->register(new WCElements\CheckoutForm());
        $registry->register(new WCElements\ProductReviews());
        $registry->register(new WCElements\PriceTable());
        $registry->register(new WCElements\SalesBadge());
    }
}
