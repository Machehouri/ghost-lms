<?php
/**
 * Plugin Name: Ghost LMS
 * Plugin URI:  https://example.com/ghost-lms
 * Description: A WordPress LMS plugin for course management, progress tracking, and WooCommerce enrollments.
 * Version:     1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Author:      Ghost LMS
 * Text Domain: ghost-lms
 * Domain Path: /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('GHOST_LMS_PLUGIN_FILE')) {
    define('GHOST_LMS_PLUGIN_FILE', __FILE__);
}

if (! defined('GHOST_LMS_VERSION')) {
    define('GHOST_LMS_VERSION', '1.0.0');
}

if (! defined('GHOST_LMS_PLUGIN_DIR')) {
    define('GHOST_LMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (! defined('GHOST_LMS_PLUGIN_URL')) {
    define('GHOST_LMS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

function ghost_lms_requirements_met(): bool
{
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        return false;
    }

    if (version_compare(get_bloginfo('version'), '6.4', '<')) {
        return false;
    }

    if (! class_exists('WooCommerce') && ! function_exists('woocommerce_version')) {
        return false;
    }

    return true;
}

function ghost_lms_activation_notice(): void
{
    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Ghost LMS requires PHP 8.1+, WordPress 6.4+, and WooCommerce to be active.', 'ghost-lms' ) . '</p></div>';
}

function ghost_lms_activate_plugin(): void
{
    if (! ghost_lms_requirements_met()) {
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', 'ghost_lms_activation_notice');
        return;
    }

    GhostLMS\Core\Activator::activate();
}

function ghost_lms_deactivate_plugin(): void
{
    GhostLMS\Core\Deactivator::deactivate();
}

if (! file_exists(GHOST_LMS_PLUGIN_DIR . 'vendor/autoload.php')) {
    add_action(
        'admin_notices',
        static function (): void {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Ghost LMS is missing Composer dependencies. Run composer install before activating the plugin.', 'ghost-lms' ) . '</p></div>';
        }
    );
    return;
}

require_once GHOST_LMS_PLUGIN_DIR . 'vendor/autoload.php';

register_activation_hook(__FILE__, 'ghost_lms_activate_plugin');
register_deactivation_hook(__FILE__, 'ghost_lms_deactivate_plugin');

add_action(
    'plugins_loaded',
    static function (): void {
        $plugin = new GhostLMS\Core\Plugin();
        $plugin->register();
    },
    20
);
