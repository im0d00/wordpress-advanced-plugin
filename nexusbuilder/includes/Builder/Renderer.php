<?php
namespace NexusBuilder\Builder;

class Renderer {

    private static ?Renderer $instance = null;
    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        add_filter('the_content', [$this, 'maybe_render_builder_content'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    public function maybe_render_builder_content(string $content): string {
        $post_id = get_the_ID();
        if (!$post_id || !$this->is_builder_page($post_id)) {
            return $content;
        }
        return $this->render_page($post_id);
    }

    public function is_builder_page(int $post_id): bool {
        return (bool) get_post_meta($post_id, '_nexusbuilder_active', true);
    }

    public function render_page(int $post_id): string {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT element_tree, css_cache FROM {$wpdb->prefix}nexusbuilder_data WHERE post_id = %d",
            $post_id
        ));

        if (!$row) return '';

        $tree = json_decode($row->element_tree, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($tree)) return '';

        // Inject scoped CSS into wp_head via late hook
        if (!empty($row->css_cache)) {
            $css = $row->css_cache;
            add_action('wp_head', function() use ($css, $post_id) {
                printf('<style id="nexusbuilder-page-%d">%s</style>', $post_id, wp_strip_all_tags($css));
            }, 99);
        }

        return $this->render_tree($tree);
    }

    private function render_tree(array $nodes, int $depth = 0): string {
        $output = '';
        foreach ($nodes as $node) {
            $output .= $this->render_node($node, $depth);
        }
        return $output;
    }

    private function render_node(array $node, int $depth): string {
        $element = \NexusBuilder\Elements\Registry::instance()->get($node['type'] ?? '');
        if (!$element) return '';

        $settings = $node['settings'] ?? [];
        $children = $node['children'] ?? [];

        // Render children first so parent elements can wrap them
        $children_html = $this->render_tree($children, $depth + 1);

        // Pass children HTML via settings for container elements
        $settings['_children_html'] = $children_html;

        return $element->render_element($settings);
    }

    public function enqueue_frontend_assets(): void {
        if (!is_singular()) return;
        $post_id = get_the_ID();
        if (!$this->is_builder_page($post_id)) return;

        wp_enqueue_style(
            'nexusbuilder-frontend',
            NEXUSBUILDER_ASSETS . 'frontend/nexusbuilder.css',
            [],
            NEXUSBUILDER_VERSION
        );

        // Conditionally enqueue animation JS only if page uses animations
        if (get_post_meta($post_id, '_nexusbuilder_has_animations', true)) {
            wp_enqueue_script(
                'gsap',
                NEXUSBUILDER_ASSETS . 'vendor/gsap.min.js',
                [],
                '3.12.5',
                ['strategy' => 'defer']
            );
            wp_enqueue_script(
                'nexusbuilder-animations',
                NEXUSBUILDER_ASSETS . 'frontend/animations.js',
                ['gsap'],
                NEXUSBUILDER_VERSION,
                ['strategy' => 'defer']
            );
        }
    }
}
