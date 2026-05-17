<?php
namespace NexusBuilder\Elements\Types;

use NexusBuilder\Elements\Base;
use NexusBuilder\Controls\Manager as CM;

class Heading extends Base {

    public function get_name(): string  { return 'heading'; }
    public function get_label(): string { return __('Heading', 'nexusbuilder'); }
    public function get_icon(): string  { return 'ti-heading'; }
    public function get_categories(): array { return ['typography', 'basic']; }

    public function register_controls(): void {

        // ── CONTENT TAB ──────────────────────────────────────────
        $this->start_controls_section('content', [
            'label' => __('Content', 'nexusbuilder'),
            'tab'   => CM::TAB_CONTENT,
        ]);

        $this->add_control('text', [
            'type'        => CM::TEXTAREA,
            'label'       => __('Heading text', 'nexusbuilder'),
            'default'     => __('Add your heading here', 'nexusbuilder'),
            'dynamic'     => true,  // Supports dynamic tags
            'placeholder' => __('Enter heading...', 'nexusbuilder'),
        ]);

        $this->add_control('tag', [
            'type'    => CM::SELECT,
            'label'   => __('HTML tag', 'nexusbuilder'),
            'default' => 'h2',
            'options' => [
                'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3',
                'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
                'div' => 'div', 'span' => 'span', 'p' => 'p',
            ],
        ]);

        $this->add_control('link', [
            'type'  => CM::LINK,
            'label' => __('Link', 'nexusbuilder'),
        ]);

        $this->end_controls_section();

        // ── STYLE TAB ────────────────────────────────────────────
        $this->start_controls_section('style', [
            'label' => __('Style', 'nexusbuilder'),
            'tab'   => CM::TAB_STYLE,
        ]);

        $this->add_group_control('typography', [
            'name'     => 'heading_typography',
            'selector' => '{{WRAPPER}} .nexus-heading',
        ]);

        $this->add_responsive_control('text_align', [
            'type'    => CM::CHOOSE,
            'label'   => __('Alignment', 'nexusbuilder'),
            'options' => [
                'left'   => ['icon' => 'ti-align-left'],
                'center' => ['icon' => 'ti-align-center'],
                'right'  => ['icon' => 'ti-align-right'],
            ],
            'default' => 'left',
            'css_map' => ['text-align' => '{{VALUE}}'],
        ]);

        $this->add_control('text_color', [
            'type'    => CM::COLOR,
            'label'   => __('Color', 'nexusbuilder'),
            'css_map' => ['color' => '{{VALUE}}'],
            'states'  => ['normal', 'hover'],
        ]);

        $this->add_control('text_shadow', [
            'type'    => CM::SHADOW,
            'label'   => __('Text shadow', 'nexusbuilder'),
            'shadow_type' => 'text',
        ]);

        $this->end_controls_section();
    }

    public function render(): void {
        $settings = $this->get_settings_for_display();
        $tag      = in_array($settings['tag'], ['h1','h2','h3','h4','h5','h6','div','span','p'])
                    ? $settings['tag'] : 'h2';

        $text = wp_kses_post($settings['text'] ?? '');

        if (!empty($settings['link']['url'])) {
            $text = sprintf(
                '<a href="%s"%s>%s</a>',
                esc_url($settings['link']['url']),
                !empty($settings['link']['target']) ? ' target="_blank" rel="noopener noreferrer"' : '',
                $text
            );
        }

        printf('<%1$s class="nexus-heading nexus-el-%2$s">%3$s</%1$s>',
            esc_attr($tag),
            esc_attr($settings['_element_id'] ?? ''),
            $text
        );
    }
}
