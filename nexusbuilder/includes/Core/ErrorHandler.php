<?php
namespace NexusBuilder\Core;

class ErrorHandler {

    public static function init(): void {
        if (defined('NEXUSBUILDER_DEBUG') && NEXUSBUILDER_DEBUG) {
            add_action('nexusbuilder/renderer/error', [self::class, 'log_render_error'], 10, 3);
        }

        add_filter('nexusbuilder/renderer/element_html', [self::class, 'catch_element_errors'], 10, 3);
    }

    public static function catch_element_errors(string $html, $element, array $settings): string {
        try {
            return $html ?: '';
        } catch (\Throwable $e) {
            do_action('nexusbuilder/renderer/error', $e, $element, $settings);
            return defined('NEXUSBUILDER_DEBUG') && NEXUSBUILDER_DEBUG
                ? '<div style="border:2px solid red;padding:8px;color:red">Element render error: ' . esc_html($e->getMessage()) . '</div>'
                : '';
        }
    }

    public static function log_render_error(\Throwable $e, $element, array $settings): void {
        $type = method_exists($element, 'get_name') ? $element->get_name() : 'unknown';
        error_log(sprintf(
            'NexusBuilder render error [%s]: %s in %s on line %d',
            $type,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }
}
