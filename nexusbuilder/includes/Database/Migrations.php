<?php
namespace NexusBuilder\Database;

class Migrations {

    private static array $migrations = [];

    public static function register(string $version, callable $callback): void {
        self::$migrations[$version] = $callback;
    }

    public static function run(): void {
        $installed = get_option('nexusbuilder_db_version', '0.0.0');

        // Register all known migrations
        self::register('1.1.0', function() {
            global $wpdb;
            // Example: add a column added in v1.1.0
            $wpdb->query("ALTER TABLE {$wpdb->prefix}nexusbuilder_data ADD COLUMN IF NOT EXISTS page_type VARCHAR(50) DEFAULT 'page'");
        });

        self::register('1.2.0', function() {
            global $wpdb;
            $wpdb->query("CREATE INDEX IF NOT EXISTS idx_page_type ON {$wpdb->prefix}nexusbuilder_data(page_type)");
        });

        foreach (self::$migrations as $version => $callback) {
            if (version_compare($installed, $version, '<')) {
                call_user_func($callback);
                update_option('nexusbuilder_db_version', $version);
            }
        }
    }
}
