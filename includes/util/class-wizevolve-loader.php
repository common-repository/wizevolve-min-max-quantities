<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WizEvolve_Loader')) {

    class WizEvolve_Loader {

        public $plugin_path;
        public $plugin_name;
        public $plugin_slug;
        public $plugin_basename;

        public $minimum_php_version;
        public $minimum_wp_version;
        public $minimum_wc_version;

        public function __construct(string $plugin_name, string $plugin_slug, string $plugin_file, string $minimum_php_version, string $minimum_wp_version, string $minimum_wc_version, callable $success_function) {

            $this->plugin_name = $plugin_name;
            $this->plugin_slug = $plugin_slug;
            $this->plugin_path = plugin_dir_path($plugin_file);
            $this->plugin_basename = plugin_basename($plugin_file);
            $this->minimum_php_version = $minimum_php_version;
            $this->minimum_wp_version = $minimum_wp_version;
            $this->minimum_wc_version = $minimum_wc_version;

            $compatible = $this->is_compatible();

            if(!$compatible) $this->deactivate_plugin();

            $success_function();
        }

        public function is_compatible(): bool
        {
            $compatible = true;

            // Check PHP Version
            if (version_compare(PHP_VERSION, $this->minimum_php_version, '<')) {
                add_action('admin_notices', [$this, 'php_version_notice']);
                $compatible = false;
            }

            // Check WP Version
            if (version_compare(get_bloginfo('version'), $this->minimum_wp_version, '<')) {
                add_action('admin_notices', [$this, 'wp_version_notice']);
                $compatible = false;
            }

            // Check WooCommerce is activated
            if (!class_exists('WooCommerce')) {
                add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
                $compatible = false;
            }

            // Check WooCommerce Version
            if (!defined('WC_VERSION') || version_compare(WC_VERSION, $this->minimum_wc_version, '<')) {
                add_action('admin_notices', [$this, 'wc_version_notice']);
                $compatible = false;
            }

            return $compatible;
        }

        public function php_version_notice(): void
        {
            $message = sprintf(
                /* translators: 1: Plugin name. 2: Minimum PHP version. */
                esc_html__('%1$s requires PHP version %2$s or newer.', 'wizevolve-min-max-quantities'),
                $this->plugin_name,
                $this->minimum_php_version
            );
            echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
        }

        public function wp_version_notice(): void
        {
            $message = sprintf(
            /* translators: 1: Plugin name. 2: Minimum WordPress version. */
                esc_html__('%1$s requires WordPress version %2$s or newer.', 'wizevolve-min-max-quantities'),
                $this->plugin_name,
                $this->minimum_wp_version
            );
            echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
        }

        public function woocommerce_missing_notice(): void
        {
            $message = sprintf(
            /* translators: 1: Plugin name. */
                esc_html__('%1$s requires the WooCommerce plugin to be installed and active.', 'wizevolve-min-max-quantities'),
                $this->plugin_name
            );
            echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
        }

        public function wc_version_notice(): void
        {
            $message = sprintf(
            /* translators: 1: Plugin name. 2: Minimum WooCommerce version. */
                esc_html__('%1$s requires WooCommerce version %2$s or newer.', 'wizevolve-min-max-quantities'),
                $this->plugin_name,
                $this->minimum_wc_version
            );
            echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
        }

        public function deactivate_plugin(): void
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            deactivate_plugins( $this->plugin_basename );

            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    }
}
