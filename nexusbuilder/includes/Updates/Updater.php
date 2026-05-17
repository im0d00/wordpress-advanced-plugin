<?php
namespace NexusBuilder\Updates;

class Updater {

    private string $update_url = 'https://api.nexusbuilder.io/v1/update-check';
    private string $plugin_slug;
    private string $plugin_file;

    public function __construct() {
        $this->plugin_slug = 'nexusbuilder/nexusbuilder.php';
        $this->plugin_file = NEXUSBUILDER_FILE;
    }

    public function init(): void {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_action('upgrader_process_complete', [$this, 'after_update'], 10, 2);
        add_filter('plugin_action_links_' . plugin_basename($this->plugin_file), [$this, 'add_action_links']);
    }

    public function check_for_update(object $transient): object {
        if (empty($transient->checked)) return $transient;

        $license = get_option('nexusbuilder_license_key', '');
        if (!$license) return $transient;

        $cached = get_transient('nexusbuilder_update_data');

        if (!$cached) {
            $response = wp_remote_post($this->update_url, [
                'body' => [
                    'license'         => $license,
                    'current_version' => NEXUSBUILDER_VERSION,
                    'site'            => home_url(),
                    'php_version'     => PHP_VERSION,
                    'wp_version'      => get_bloginfo('version'),
                ],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) return $transient;
            $cached = json_decode(wp_remote_retrieve_body($response), true);
            set_transient('nexusbuilder_update_data', $cached, 12 * HOUR_IN_SECONDS);
        }

        if (
            !empty($cached['new_version']) &&
            version_compare($cached['new_version'], NEXUSBUILDER_VERSION, '>')
        ) {
            $transient->response[$this->plugin_slug] = (object) [
                'slug'        => 'nexusbuilder',
                'plugin'      => $this->plugin_slug,
                'new_version' => $cached['new_version'],
                'url'         => 'https://nexusbuilder.io',
                'package'     => $cached['download_url'] ?? '',
                'icons'       => ['1x' => NEXUSBUILDER_ASSETS . 'admin/icon-128.png'],
            ];
        }

        return $transient;
    }

    public function after_update(object $upgrader, array $hook_extra): void {
        if (
            isset($hook_extra['plugin']) &&
            $hook_extra['plugin'] === $this->plugin_slug
        ) {
            delete_transient('nexusbuilder_update_data');
            delete_transient('nexusbuilder_license_tier');

            // Run any new DB migrations
            \NexusBuilder\Database\Migrations::run();

            do_action('nexusbuilder/updated', NEXUSBUILDER_VERSION);
        }
    }

    public function add_action_links(array $links): array {
        $settings_link = '<a href="' . admin_url('admin.php?page=nexusbuilder-settings') . '">' . __('Settings', 'nexusbuilder') . '</a>';
        $license_link  = '<a href="' . admin_url('admin.php?page=nexusbuilder-license') . '" style="color:#d63638">' . __('License', 'nexusbuilder') . '</a>';
        return array_merge([$settings_link, $license_link], $links);
    }
}
