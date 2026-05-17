<?php
namespace NexusBuilder\API\Endpoints;

use WP_REST_Request;
use WP_REST_Response;

class Pages {

    const BASE = '/wp-json/nexusbuilder/v1';

    public function register(): void {
        register_rest_route('nexusbuilder/v1', '/pages/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_page'],
                'permission_callback' => [$this, 'can_edit'],
                'args'                => ['id' => ['validate_callback' => 'is_numeric']],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'save_page'],
                'permission_callback' => [$this, 'can_edit'],
            ],
        ]);

        register_rest_route('nexusbuilder/v1', '/pages/(?P<id>\d+)/revisions', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_revisions'],
            'permission_callback' => [$this, 'can_edit'],
        ]);
    }

    public function get_page(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $post_id = (int) $request->get_param('id');

        if (!get_post($post_id)) {
            return new WP_REST_Response(['error' => 'Post not found'], 404);
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nexusbuilder_data WHERE post_id = %d",
            $post_id
        ));

        return new WP_REST_Response([
            'post_id'      => $post_id,
            'element_tree' => $row ? json_decode($row->element_tree, true) : [],
            'version'      => $row->version ?? null,
            'updated_at'   => $row->updated_at ?? null,
        ], 200);
    }

    public function save_page(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $post_id      = (int) $request->get_param('id');
        $element_tree = $request->get_param('element_tree');
        $label        = sanitize_text_field($request->get_param('label') ?? '');

        if (!get_post($post_id) || !is_array($element_tree)) {
            return new WP_REST_Response(['error' => 'Invalid data'], 400);
        }

        // Sanitize element tree recursively
        $clean_tree = $this->sanitize_element_tree($element_tree);
        $tree_json  = wp_json_encode($clean_tree);

        // Generate CSS from tree
        $css = \NexusBuilder\Builder\CSSGenerator::generate($clean_tree, $post_id);

        // Save revision before overwriting
        $this->save_revision($post_id, $label);

        // Upsert main data row
        $wpdb->replace(
            "{$wpdb->prefix}nexusbuilder_data",
            [
                'post_id'      => $post_id,
                'element_tree' => $tree_json,
                'css_cache'    => $css,
                'version'      => NEXUSBUILDER_VERSION,
            ],
            ['%d', '%s', '%s', '%s']
        );

        // Mark post as builder-active
        update_post_meta($post_id, '_nexusbuilder_active', 1);

        // Flag if animations present
        $has_animations = $this->tree_has_animations($clean_tree);
        update_post_meta($post_id, '_nexusbuilder_has_animations', (int) $has_animations);

        // Invalidate page cache
        do_action('nexusbuilder/page/saved', $post_id, $clean_tree);

        return new WP_REST_Response([
            'success'    => true,
            'post_id'    => $post_id,
            'css_length' => strlen($css),
        ], 200);
    }

    private function save_revision(int $post_id, string $label): void {
        global $wpdb;

        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT element_tree FROM {$wpdb->prefix}nexusbuilder_data WHERE post_id = %d",
            $post_id
        ));

        if (!$current) return;

        $wpdb->insert(
            "{$wpdb->prefix}nexusbuilder_revisions",
            [
                'post_id'    => $post_id,
                'user_id'    => get_current_user_id(),
                'diff'       => $current, // Store full snapshot (or implement diff later)
                'label'      => $label,
            ],
            ['%d', '%d', '%s', '%s']
        );

        // Keep max 50 revisions per post
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}nexusbuilder_revisions 
             WHERE post_id = %d 
             AND id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM {$wpdb->prefix}nexusbuilder_revisions 
                     WHERE post_id = %d ORDER BY created_at DESC LIMIT 50
                 ) AS keep
             )",
            $post_id, $post_id
        ));
    }

    private function sanitize_element_tree(array $tree): array {
        array_walk_recursive($tree, function(&$value) {
            if (is_string($value)) {
                // Allow safe HTML in text elements, strip PHP
                $value = wp_kses_post(strip_tags($value, '<p><a><strong><em><ul><ol><li><br><span><h1><h2><h3><h4><h5><h6>'));
            }
        });
        return $tree;
    }

    private function tree_has_animations(array $tree): bool {
        foreach ($tree as $node) {
            if (!empty($node['settings']['animation']['type'])) return true;
            if (!empty($node['children']) && $this->tree_has_animations($node['children'])) return true;
        }
        return false;
    }

    public function can_edit(WP_REST_Request $request): bool {
        $post_id = (int) $request->get_param('id');
        return current_user_can('edit_post', $post_id);
    }

    public function get_revisions(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $post_id   = (int) $request->get_param('id');
        $revisions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, label, created_at 
             FROM {$wpdb->prefix}nexusbuilder_revisions 
             WHERE post_id = %d ORDER BY created_at DESC LIMIT 50",
            $post_id
        ));

        return new WP_REST_Response($revisions, 200);
    }
}
