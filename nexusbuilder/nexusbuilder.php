<?php
/**
 * Plugin Name:       NexusBuilder
 * Plugin URI:        https://nexusbuilder.io
 * Description:       The next-generation WordPress page builder.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Your Name
 * License:           GPL-2.0+
 * Text Domain:       nexusbuilder
 */

defined('ABSPATH') || exit;

define('NEXUSBUILDER_VERSION',  '1.0.0');
define('NEXUSBUILDER_FILE',     __FILE__);
define('NEXUSBUILDER_PATH',     plugin_dir_path(__FILE__));
define('NEXUSBUILDER_URL',      plugin_dir_url(__FILE__));
define('NEXUSBUILDER_ASSETS',   NEXUSBUILDER_URL . 'assets/');

require_once NEXUSBUILDER_PATH . 'includes/Core/Autoloader.php';

NexusBuilder\Core\Autoloader::register();

register_activation_hook(__FILE__,   [NexusBuilder\Core\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [NexusBuilder\Core\Plugin::class, 'deactivate']);

add_action('plugins_loaded', function () {
    NexusBuilder\Core\Plugin::instance()->init();
});
