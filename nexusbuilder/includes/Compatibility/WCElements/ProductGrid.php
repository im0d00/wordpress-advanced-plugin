<?php
namespace NexusBuilder\Compatibility\WCElements;

class ProductGrid extends \NexusBuilder\Elements\Base {

    public function get_name(): string  { return 'wc-product-grid'; }
    public function get_label(): string { return __('Product Grid', 'nexusbuilder'); }
    public function get_icon(): string  { return 'ti-shopping-bag'; }
    public function get_categories(): array { return ['woocommerce']; }

    public function register_controls(): void {
        $this->start_controls_section('query', ['label' => 'Query', 'tab' => 'content']);

        $this->add_control('posts_per_page', ['type' => 'number', 'label' => 'Products per page', 'default' => 12]);
        $this->add_control('orderby', [
            'type' => 'select', 'label' => 'Order by', 'default' => 'date',
            'options' => ['date' => 'Newest', 'price' => 'Price', 'popularity' => 'Popularity', 'rating' => 'Rating', 'rand' => 'Random'],
        ]);
        $this->add_control('category', ['type' => 'select', 'label' => 'Category', 'options' => $this->get_category_options()]);
        $this->add_control('on_sale', ['type' => 'switcher', 'label' => 'Show only sale items', 'default' => false]);
        $this->add_control('featured', ['type' => 'switcher', 'label' => 'Show only featured', 'default' => false]);

        $this->end_controls_section();
        $this->start_controls_section('layout', ['label' => 'Layout', 'tab' => 'content']);

        $this->add_responsive_control('columns', [
            'type' => 'slider', 'label' => 'Columns',
            'default' => ['desktop' => 3, 'tablet' => 2, 'mobile' => 1],
            'min' => 1, 'max' => 6,
        ]);
        $this->add_control('show_rating',    ['type' => 'switcher', 'label' => 'Show rating',    'default' => true]);
        $this->add_control('show_price',     ['type' => 'switcher', 'label' => 'Show price',     'default' => true]);
        $this->add_control('show_add_cart',  ['type' => 'switcher', 'label' => 'Show add to cart', 'default' => true]);
        $this->add_control('show_wishlist',  ['type' => 'switcher', 'label' => 'Show wishlist',  'default' => false]);
        $this->add_control('card_style', [
            'type' => 'select', 'label' => 'Card style', 'default' => 'standard',
            'options' => ['standard' => 'Standard', 'compact' => 'Compact', 'overlay' => 'Overlay', 'list' => 'List view'],
        ]);

        $this->end_controls_section();
    }

    public function render(): void {
        $s = $this->get_settings_for_display();
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => absint($s['posts_per_page'] ?? 12),
            'orderby'        => sanitize_key($s['orderby'] ?? 'date'),
            'tax_query'      => [],
        ];

        if (!empty($s['category'])) {
            $args['tax_query'][] = ['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => (int)$s['category']];
        }
        if (!empty($s['on_sale'])) {
            $args['post__in'] = wc_get_product_ids_on_sale();
        }
        if (!empty($s['featured'])) {
            $args['tax_query'][] = ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured'];
        }

        $query    = new \WP_Query($args);
        $columns  = absint($s['columns']['desktop'] ?? 3);
        $style    = sanitize_key($s['card_style'] ?? 'standard');

        echo "<div class=\"nexus-wc-grid nexus-wc-grid--{$style} nexus-wc-grid--cols-{$columns}\">";

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                global $product;
                $this->render_product_card($product, $s, $style);
            }
            wp_reset_postdata();
        } else {
            echo '<p class="nexus-no-products">' . esc_html__('No products found.', 'nexusbuilder') . '</p>';
        }

        echo '</div>';
    }

    private function render_product_card(\WC_Product $product, array $s, string $style): void {
        $img_id   = $product->get_image_id();
        $img_url  = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail') : wc_placeholder_img_src();
        $sale     = $product->is_on_sale();

        echo "<div class=\"nexus-product-card nexus-product-card--{$style}\">";
        echo '<div class="nexus-product-card__image-wrap">';
        echo '<a href="' . esc_url(get_permalink()) . '">';
        echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
        echo '</a>';
        if ($sale) {
            echo '<span class="nexus-sale-badge">' . esc_html__('Sale', 'nexusbuilder') . '</span>';
        }
        echo '</div>';

        echo '<div class="nexus-product-card__body">';
        echo '<h3 class="nexus-product-card__title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';

        if ($s['show_rating'] ?? true) {
            echo '<div class="nexus-product-card__rating">';
            wc_get_template('single-product/rating.php');
            echo '</div>';
        }

        if ($s['show_price'] ?? true) {
            echo '<div class="nexus-product-card__price">' . wp_kses_post($product->get_price_html()) . '</div>';
        }

        if ($s['show_add_cart'] ?? true) {
            echo '<div class="nexus-product-card__atc">';
            woocommerce_template_loop_add_to_cart(['quantity' => 1]);
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    private function get_category_options(): array {
        $options = ['' => __('All categories', 'nexusbuilder')];
        $terms   = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->term_id] = $term->name;
            }
        }
        return $options;
    }
}
