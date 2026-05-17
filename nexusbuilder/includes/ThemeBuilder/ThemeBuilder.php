<?php
namespace NexusBuilder\ThemeBuilder;

class ThemeBuilder {

    private static ?ThemeBuilder $instance = null;
    public static function instance(): self { return self::$instance ??= new self(); }

    public function init(): void {
        Conditions\Evaluator::init();

        add_action('template_redirect', [$this, 'maybe_override_template'], 1);
        add_filter('nexusbuilder/render/section',  [$this, 'inject_header'], 10, 2);
        add_filter('nexusbuilder/render/section',  [$this, 'inject_footer'], 10, 2);
    }

    public function maybe_override_template(): void {
        $header = $this->find_matching_template('header');
        $footer = $this->find_matching_template('footer');

        if ($header) {
            add_action('get_header', function() use ($header) {
                $this->render_theme_template($header);
                // Prevent WordPress from loading its own header
            }, 0);
        }

        if ($footer) {
            add_action('get_footer', function() use ($footer) {
                $this->render_theme_template($footer);
            }, 0);
        }
    }

    private function find_matching_template(string $type): ?array {
        global $wpdb;

        $templates = $wpdb->get_results($wpdb->prepare(
            "SELECT id, element_tree, conditions, priority
             FROM {$wpdb->prefix}nexusbuilder_theme_templates
             WHERE type = %s AND active = 1
             ORDER BY priority DESC",
            $type
        ));

        foreach ($templates as $template) {
            $conditions = json_decode($template->conditions, true);
            if (Conditions\Evaluator::evaluate($conditions)) {
                return (array) $template;
            }
        }

        return null;
    }

    private function render_theme_template(array $template): void {
        $tree = json_decode($template['element_tree'], true);
        echo \NexusBuilder\Builder\Renderer::instance()->render_tree($tree ?? []);
    }
}
