<?php
namespace NexusBuilder\Core;

final class Plugin {

    private static ?Plugin $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        $this->load_textdomain();
        $this->register_modules();
    }

    private function load_textdomain(): void {
        load_plugin_textdomain(
            'nexusbuilder',
            false,
            dirname(plugin_basename(NEXUSBUILDER_FILE)) . '/languages/'
        );
    }

    private function register_modules(): void {
        // Order matters — registry before elements, elements before builder
        \NexusBuilder\Elements\Registry::instance()->init();
        \NexusBuilder\Controls\Registry::instance()->init();
        \NexusBuilder\Builder\Editor::instance()->init();
        \NexusBuilder\Builder\Renderer::instance()->init();
        \NexusBuilder\ThemeBuilder\ThemeBuilder::instance()->init();
        \NexusBuilder\API\Router::instance()->init();
        \NexusBuilder\AI\Client::instance()->init();

        if (class_exists('WooCommerce')) {
            \NexusBuilder\Compatibility\WooCommerce::instance()->init();
        }

        do_action('nexusbuilder/init', $this);
    }

    public static function activate(): void {
        \NexusBuilder\Database\Installer::run();
        \NexusBuilder\Database\Installer::set_default_global_styles();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
