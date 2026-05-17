<?php
namespace NexusBuilder\Elements\Types;

class PostQueryLoop extends \NexusBuilder\Elements\Base {

    public function get_name(): string  { return 'query-loop'; }
    public function get_label(): string { return __('Post Query Loop', 'nexusbuilder'); }
    public function get_icon(): string  { return 'ti-repeat'; }
    public function is_dynamic(): bool  { return true; }

    public function register_controls(): void {
        $this->start_controls_section('query', ['label' => 'Query', 'tab' => 'content']);
        $this->add_control('post_type', [
            'type' => 'select', 'label' => 'Post type', 'default' => 'post',
            'options' => $this->get_post_types(),
        ]);
        $this->add_control('posts_per_page', ['type' => 'number', 'label' => 'Items', 'default' => 6]);
        $this->add_control('orderby', [
            'type' => 'select', 'label' => 'Order by', 'default' => 'date',
            'options' => ['date' => 'Date', 'title' => 'Title', 'rand' => 'Random', 'menu_order' => 'Custom order', 'modified' => 'Last modified'],
        ]);
        $this->add_control('order', [
            'type' => 'choose', 'label' => 'Order',
            'options' => ['DESC' => ['label' => 'Desc'], 'ASC' => ['label' => 'Asc']],
            'default' => 'DESC',
        ]);
        $this->add_control('include_ids', ['type' => 'text', 'label' => 'Include IDs (comma separated)']);
        $this->add_control('exclude_ids', ['type' => 'text', 'label' => 'Exclude IDs (comma separated)']);
        $this->add_control('offset', ['type' => 'number', 'label' => 'Offset', 'default' => 0]);
        $this->end_controls_section();

        $this->start_controls_section('item_template', ['label' => 'Item template', 'tab' => 'content']);
        $this->add_control('show_thumbnail', ['type' => 'switcher', 'label' => 'Show thumbnail', 'default' => true]);
        $this->add_control('show_title',     ['type' => 'switcher', 'label' => 'Show title',     'default' => true]);
        $this->add_control('show_excerpt',   ['type' => 'switcher', 'label' => 'Show excerpt',   'default' => true]);
        $this->add_control('show_date',      ['type' => 'switcher', 'label' => 'Show date',      'default' => true]);
        $this->add_control('show_author',    ['type' => 'switcher', 'label' => 'Show author',    'default' => false]);
        $this->add_control('excerpt_length', ['type' => 'number',   'label' => 'Excerpt length', 'default' => 20]);
        $this->add_control('read_more_text', ['type' => 'text',     'label' => 'Read more text', 'default' => 'Read more']);
        $this->end_controls_section();
    }

    public function render(): void {
        $s    = $this->get_settings_for_display();
        $args = [
            'post_type'      => sanitize_key($s['post_type'] ?? 'post'),
            'posts_per_page' => absint($s['posts_per_page'] ?? 6),
            'orderby'        => sanitize_key($s['orderby'] ?? 'date'),
            'order'          => in_array($s['order'] ?? 'DESC', ['ASC','DESC']) ? $s['order'] : 'DESC',
            'offset'         => absint($s['offset'] ?? 0),
            'post_status'    => 'publish',
        ];

        if (!empty($s['include_ids'])) {
            $args['post__in'] = array_map('absint', explode(',', $s['include_ids']));
        }
        if (!empty($s['exclude_ids'])) {
            $args['post__not_in'] = array_map('absint', explode(',', $s['exclude_ids']));
        }

        $query = new \WP_Query($args);

        echo '<div class="nexus-query-loop">';
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_item($s);
            }
            wp_reset_postdata();
        }
        echo '</div>';
    }

    private function render_item(array $s): void {
        $exc_length = absint($s['excerpt_length'] ?? 20);
        echo '<article class="nexus-loop-item">';
        if (!empty($s['show_thumbnail']) && has_post_thumbnail()) {
            echo '<a href="' . esc_url(get_permalink()) . '" class="nexus-loop-item__thumb">';
            the_post_thumbnail('medium_large', ['loading' => 'lazy']);
            echo '</a>';
        }
        echo '<div class="nexus-loop-item__body">';
        if (!empty($s['show_date'])) {
            echo '<time class="nexus-loop-item__date">' . esc_html(get_the_date()) . '</time>';
        }
        if (!empty($s['show_title'])) {
            echo '<h3 class="nexus-loop-item__title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
        }
        if (!empty($s['show_author'])) {
            echo '<span class="nexus-loop-item__author">' . esc_html(get_the_author()) . '</span>';
        }
        if (!empty($s['show_excerpt'])) {
            echo '<p class="nexus-loop-item__excerpt">' . esc_html(wp_trim_words(get_the_excerpt(), $exc_length)) . '</p>';
        }
        $rm = esc_html($s['read_more_text'] ?? 'Read more');
        echo '<a href="' . esc_url(get_permalink()) . '" class="nexus-loop-item__more">' . $rm . '</a>';
        echo '</div></article>';
    }

    private function get_post_types(): array {
        $types = get_post_types(['public' => true], 'objects');
        $opts  = [];
        foreach ($types as $type) {
            $opts[$type->name] = $type->labels->name;
        }
        return $opts;
    }
}
