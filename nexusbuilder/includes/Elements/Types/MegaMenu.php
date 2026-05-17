<?php
namespace NexusBuilder\Elements\Types;

class MegaMenu extends \NexusBuilder\Elements\Base {

    public function get_name(): string  { return 'mega-menu'; }
    public function get_label(): string { return __('Mega Menu', 'nexusbuilder'); }
    public function get_icon(): string  { return 'ti-menu'; }

    public function register_controls(): void {

        // ── TOP LEVEL ITEMS ──────────────────────────────────────
        $this->start_controls_section('items', [
            'label' => __('Menu Items', 'nexusbuilder'),
            'tab'   => 'content',
        ]);

        $this->add_control('menu_items', [
            'type'  => 'repeater',
            'label' => __('Items', 'nexusbuilder'),
            'item_controls' => [
                ['id' => 'label',    'type' => 'text', 'label' => 'Label'],
                ['id' => 'link',     'type' => 'link', 'label' => 'Link'],
                ['id' => 'is_mega',  'type' => 'switcher', 'label' => 'Enable Mega Dropdown'],
                ['id' => 'icon',     'type' => 'icon', 'label' => 'Icon'],
                ['id' => 'badge',    'type' => 'text', 'label' => 'Badge (e.g. "New")'],
            ],
            'default' => [
                ['_id' => 'm1', 'label' => 'Home', 'link' => ['url'=>'/']],
                ['_id' => 'm2', 'label' => 'Features', 'is_mega' => true],
                ['_id' => 'm3', 'label' => 'Pricing', 'link' => ['url'=>'/pricing']],
            ],
        ]);

        $this->end_controls_section();

        // ── DROP DOWN SETTINGS ───────────────────────────────────
        $this->start_controls_section('dropdown', [
            'label' => __('Dropdown Settings', 'nexusbuilder'),
            'tab'   => 'content',
        ]);

        $this->add_control('trigger', [
            'type'    => 'select',
            'label'   => __('Trigger on', 'nexusbuilder'),
            'options' => ['hover' => 'Hover', 'click' => 'Click'],
            'default' => 'hover',
        ]);

        $this->add_control('animation', [
            'type'    => 'select',
            'label'   => __('Animation', 'nexusbuilder'),
            'options' => ['fade' => 'Fade', 'slide-up' => 'Slide up', 'scale' => 'Scale'],
            'default' => 'slide-up',
        ]);

        $this->add_control('width', [
            'type'    => 'select',
            'label'   => __('Dropdown width', 'nexusbuilder'),
            'options' => ['container' => 'Container width', 'full' => 'Full screen', 'custom' => 'Custom'],
            'default' => 'container',
        ]);

        $this->end_controls_section();
    }

    public function render(): void {
        $s     = $this->get_settings_for_display();
        $items = $s['menu_items'] ?? [];
        $el_id = esc_attr($s['_element_id'] ?? '');
        $trigger = esc_attr($s['trigger'] ?? 'hover');

        echo "<nav class=\"nexus-mega-menu nexus-mega-menu--trigger-{$trigger} nexus-el-{$el_id}\" aria-label=\"Main navigation\">";
        echo '<ul class="nexus-mega-menu__list">';

        foreach ($items as $item) {
            $is_mega = !empty($item['is_mega']);
            $has_dropdown = $is_mega && is_admin(); // In editor, always render dropdown container

            echo '<li class="nexus-mega-menu__item' . ($has_dropdown ? ' nexus-mega-menu__item--has-children' : '') . '">';

            $url = empty($item['link']['url']) ? '#' : esc_url($item['link']['url']);
            echo "<a href=\"{$url}\" class=\"nexus-mega-menu__link\">";
            if (!empty($item['icon'])) {
                echo "<i class=\"{$item['icon']}\" aria-hidden=\"true\"></i> ";
            }
            echo esc_html($item['label'] ?? '');
            if (!empty($item['badge'])) {
                echo "<span class=\"nexus-badge\">" . esc_html($item['badge']) . "</span>";
            }
            if ($is_mega) {
                echo " <i class=\"ti-angle-down nexus-mega-menu__indicator\"></i>";
            }
            echo '</a>';

            // If it's a mega menu item, we render a NexusBuilder inner container
            if ($is_mega) {
                // Determine width class
                $width_class = 'nexus-mega-menu__dropdown--' . esc_attr($s['width'] ?? 'container');

                echo "<div class=\"nexus-mega-menu__dropdown {$width_class}\">";
                // Render the children specific to this mega menu item.
                // In NexusBuilder, this element acts as a parent node.
                // Elements placed inside this menu item are stored in $this->children.

                $node = $this->get_current_node();
                $mega_children = array_filter($node['children'] ?? [], function($c) use ($item) {
                     // In the editor, elements dragged into a mega menu need to be associated
                     // with the specific _id of the repeater item.
                     return ($c['settings']['_mega_parent_id'] ?? '') === $item['_id'];
                });

                if (empty($mega_children) && is_admin()) {
                     echo '<div class="nexus-mega-menu__empty-dropzone" data-mega-parent="' . esc_attr($item['_id']) . '">';
                     echo __('Drag elements here to build mega menu', 'nexusbuilder');
                     echo '</div>';
                }

                \NexusBuilder\Builder\Renderer::instance()->render_children($mega_children);
                echo "</div>";
            }

            echo '</li>';
        }

        echo '</ul>';

        // Mobile toggle button
        echo '<button class="nexus-mega-menu__mobile-toggle" aria-expanded="false" aria-label="Toggle menu">';
        echo '<span class="nexus-mega-menu__hamburger"></span>';
        echo '</button>';

        echo '</nav>';
    }
}
