<?php

/*
Plugin Name: WizEvolve Min Max Quantities
Description: Set the minimum and maximum allowed quantities of a product
Version: 1.3.1
Author: WizEvolve
Author URI: https://www.wizevolve.com/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wizevolve-min-max-quantities
*/
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'plugins_loaded', function () {
    
    if ( !function_exists( 'wizevolve_wmmq_fs' ) ) {
        // Create a helper function for easy SDK access.
        function wizevolve_wmmq_fs()
        {
            global  $wizevolve_wmmq_fs ;
            
            if ( !isset( $wizevolve_wmmq_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $wizevolve_wmmq_fs = fs_dynamic_init( array(
                    'id'             => '13335',
                    'slug'           => 'wizevolve-min-max-quantities',
                    'premium_slug'   => 'wizevolve-min-max-quantities-pro',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_153077b7d69f624ff48ca9312640e',
                    'is_premium'     => false,
                    'premium_suffix' => 'Pro',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                    'slug'    => 'wizevolve-min-max-quantities',
                    'contact' => false,
                    'support' => false,
                    'parent'  => array(
                    'slug' => 'wizevolve',
                ),
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $wizevolve_wmmq_fs;
        }
        
        // Init Freemius.
        wizevolve_wmmq_fs();
        // Signal that SDK was initiated.
        do_action( 'wizevolve_wmmq_fs_loaded' );
    }
    
    include_once 'includes/util/class-wizevolve-loader.php';
    class WizEvolve_Min_Max_Quantities_Loader
    {
        const  PLUGIN_NAME = 'WizEvolve Min Max Quantities' ;
        const  PLUGIN_SLUG = 'wizevolve-min-max-quantities' ;
        const  PLUGIN_FUNCTION = 'wizevolve_wmmq_fs' ;
        public static function start() : void
        {
            load_plugin_textdomain( WizEvolve_Min_Max_Quantities_Loader::PLUGIN_SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
            new WizEvolve_Loader(
                self::PLUGIN_NAME,
                self::PLUGIN_SLUG,
                __FILE__,
                '8.0',
                '5.6',
                '3.5',
                function () {
                include_once 'includes/class-wizevolve-min-max-quantities.php';
                WizEvolve_Min_Max_Quantities::get_instance();
            }
            );
        }
    
    }
    WizEvolve_Min_Max_Quantities_Loader::start();
} );