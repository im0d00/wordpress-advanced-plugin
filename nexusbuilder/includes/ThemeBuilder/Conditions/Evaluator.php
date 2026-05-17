<?php
namespace NexusBuilder\ThemeBuilder\Conditions;

class Evaluator {

    private static array $registered = [];

    public static function register(string $key, callable $callback): void {
        self::$registered[$key] = $callback;
    }

    public static function init(): void {
        // Built-in conditions
        self::register('everywhere',      fn() => true);
        self::register('is_front_page',   fn() => is_front_page());
        self::register('is_single',       fn() => is_single());
        self::register('is_page',         fn() => is_page());
        self::register('is_archive',      fn() => is_archive());
        self::register('is_404',          fn() => is_404());
        self::register('is_search',       fn() => is_search());
        self::register('is_logged_in',    fn() => is_user_logged_in());
        self::register('is_admin_user',   fn() => current_user_can('manage_options'));

        self::register('post_type', function(string $post_type) {
            return get_post_type() === $post_type;
        });

        self::register('specific_post', function(int $post_id) {
            return get_the_ID() === $post_id;
        });

        self::register('taxonomy_term', function(int $term_id) {
            return has_term($term_id);
        });

        self::register('user_role', function(string $role) {
            $user = wp_get_current_user();
            return in_array($role, $user->roles ?? [], true);
        });

        // WooCommerce conditions
        if (class_exists('WooCommerce')) {
            self::register('is_wc_shop',       fn() => is_shop());
            self::register('is_wc_product',    fn() => is_product());
            self::register('is_wc_cart',       fn() => is_cart());
            self::register('is_wc_checkout',   fn() => is_checkout());
            self::register('cart_not_empty',   fn() => WC()->cart && !WC()->cart->is_empty());
        }
    }

    /**
     * Evaluate a set of condition groups.
     * Groups are OR-ed; conditions within a group are AND-ed.
     *
     * Example: [[a, b], [c]] means (a AND b) OR (c)
     */
    public static function evaluate(array $condition_groups): bool {
        if (empty($condition_groups)) return true;

        foreach ($condition_groups as $group) {
            if (self::evaluate_group($group)) return true;
        }
        return false;
    }

    private static function evaluate_group(array $conditions): bool {
        foreach ($conditions as $condition) {
            $key    = $condition['type'] ?? '';
            $value  = $condition['value'] ?? null;
            $negate = (bool) ($condition['negate'] ?? false);

            if (!isset(self::$registered[$key])) continue;

            $result = call_user_func(self::$registered[$key], $value);
            if ($negate) $result = !$result;
            if (!$result) return false;
        }
        return true;
    }
}
