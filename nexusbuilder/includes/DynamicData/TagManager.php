<?php
namespace NexusBuilder\DynamicData;

class TagManager {

    private static array $tags = [];

    public static function init(): void {
        // Built-in dynamic tags
        self::register('post_title',       fn() => get_the_title());
        self::register('post_excerpt',     fn() => get_the_excerpt());
        self::register('post_date',        fn() => get_the_date());
        self::register('post_author',      fn() => get_the_author());
        self::register('post_url',         fn() => get_the_permalink());
        self::register('post_thumbnail',   fn() => get_the_post_thumbnail_url(null, 'full'));
        self::register('site_name',        fn() => get_bloginfo('name'));
        self::register('site_tagline',     fn() => get_bloginfo('description'));
        self::register('site_url',         fn() => home_url());
        self::register('current_year',     fn() => date('Y'));
        self::register('user_name',        fn() => wp_get_current_user()->display_name ?? '');
        self::register('user_email',       fn() => wp_get_current_user()->user_email ?? '');

        // ACF fields (if ACF active)
        if (function_exists('get_field')) {
            self::register('acf', function(string $field_name) {
                return get_field($field_name) ?? '';
            });
        }

        // WooCommerce tags
        if (class_exists('WooCommerce')) {
            self::register('product_price',  fn() => function_exists('wc_get_product') && ($p = wc_get_product()) ? $p->get_price_html() : '');
            self::register('product_sku',    fn() => function_exists('wc_get_product') && ($p = wc_get_product()) ? $p->get_sku() : '');
            self::register('cart_count',     fn() => WC()->cart ? WC()->cart->get_cart_contents_count() : 0);
            self::register('cart_total',     fn() => WC()->cart ? WC()->cart->get_cart_total() : '');
        }

        do_action('nexusbuilder/dynamic_tags/register', static::class);
    }

    public static function register(string $key, callable $resolver): void {
        self::$tags[$key] = $resolver;
    }

    /**
     * Replace {{dynamic_tag:field_name}} placeholders in text.
     */
    public static function process(string $text): string {
        return preg_replace_callback(
            '/\{\{([a-z0-9_]+)(?::([^}]+))?\}\}/i',
            function(array $matches): string {
                $tag   = $matches[1];
                $param = $matches[2] ?? '';

                if (!isset(self::$tags[$tag])) return $matches[0];

                $value = $param
                    ? call_user_func(self::$tags[$tag], $param)
                    : call_user_func(self::$tags[$tag]);

                return is_string($value) ? wp_kses_post($value) : (string) $value;
            },
            $text
        );
    }
}
