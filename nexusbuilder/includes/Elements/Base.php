<?php
namespace NexusBuilder\Elements;

abstract class Base {

    private array $settings      = [];
    private array $raw_settings  = [];
    private array $controls      = [];
    private array $control_groups = [];

    // ── Abstract interface ──────────────────────────────────────────────

    abstract public function get_name(): string;
    abstract public function get_label(): string;
    abstract public function register_controls(): void;
    abstract public function render(): void;

    // ── Optional overrides ──────────────────────────────────────────────

    public function get_icon(): string        { return 'ti-box'; }
    public function get_categories(): array   { return ['general']; }
    public function get_keywords(): array     { return []; }
    public function get_version(): string     { return '1.0.0'; }
    public function is_dynamic(): bool        { return false; }

    // ── Control registration helpers ────────────────────────────────────

    public function start_controls_section(string $id, array $args): void {
        $args['id'] = $id;
        $this->control_groups[] = array_merge(['controls' => []], $args);
    }

    public function end_controls_section(): void {
        // Closing marker — controls added after this go to next group
    }

    public function add_control(string $id, array $args): void {
        $args['id'] = $id;
        $args['responsive'] = $args['responsive'] ?? false;
        $args['states']     = $args['states']     ?? false;

        $last = array_key_last($this->control_groups);
        if ($last !== null) {
            $this->control_groups[$last]['controls'][] = $args;
        }
        $this->controls[$id] = $args;
    }

    public function add_responsive_control(string $id, array $args): void {
        $args['responsive'] = true;
        $this->add_control($id, $args);
    }

    public function add_group_control(string $type, array $args): void {
        $group = \NexusBuilder\Controls\Groups\Registry::get($type);
        if ($group) {
            foreach ($group->get_controls($args) as $cid => $control) {
                $this->add_control($cid, $control);
            }
        }
    }

    // ── Settings access ─────────────────────────────────────────────────

    public function set_settings(array $settings, string $device = 'desktop'): void {
        $this->raw_settings = $settings;
        $this->settings     = $this->parse_settings($settings, $device);
    }

    private function parse_settings(array $raw, string $device): array {
        $parsed = [];
        foreach ($this->controls as $id => $control) {
            if (!empty($control['responsive']) && isset($raw[$id])) {
                $value = $raw[$id][$device]
                    ?? $raw[$id]['tablet']
                    ?? $raw[$id]['desktop']
                    ?? ($control['default'] ?? null);
            } else {
                $value = $raw[$id] ?? $control['default'] ?? null;
            }
            $parsed[$id] = $value;
        }
        return $parsed;
    }

    public function get_settings_for_display(string $key = ''): mixed {
        return $key ? ($this->settings[$key] ?? null) : $this->settings;
    }

    // ── Frontend render helpers ─────────────────────────────────────────

    final public function render_element(array $settings, string $device = 'desktop'): string {
        $this->register_controls();
        $this->set_settings($settings, $device);

        ob_start();
        $this->render();
        return ob_get_clean();
    }

    protected function print_inline_css(array $rules, string $selector): void {
        if (empty($rules)) return;
        $css_parts = [];
        foreach ($rules as $property => $value) {
            if ($value !== null && $value !== '') {
                $css_parts[] = esc_attr($property) . ':' . esc_attr($value);
            }
        }
        if ($css_parts) {
            echo 'style="' . implode(';', $css_parts) . '"';
        }
    }

    // ── Schema for JS editor ────────────────────────────────────────────

    public function get_editor_schema(): array {
        $this->register_controls();
        return [
            'name'        => $this->get_name(),
            'label'       => $this->get_label(),
            'icon'        => $this->get_icon(),
            'categories'  => $this->get_categories(),
            'keywords'    => $this->get_keywords(),
            'version'     => $this->get_version(),
            'is_dynamic'  => $this->is_dynamic(),
            'groups'      => $this->control_groups,
        ];
    }
}
