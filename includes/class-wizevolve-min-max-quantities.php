<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !class_exists( 'WizEvolve_Min_Max_Quantities' ) ) {
    class WizEvolve_Min_Max_Quantities
    {
        protected static  $instance = null ;
        public static function get_instance() : WizEvolve_Min_Max_Quantities
        {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function __construct()
        {
            require_once 'util/class-wizevolve-options.php';
            $options = new WizEvolve_Options(
                WizEvolve_Min_Max_Quantities_Loader::PLUGIN_FUNCTION,
                WizEvolve_Min_Max_Quantities_Loader::PLUGIN_NAME,
                WizEvolve_Min_Max_Quantities_Loader::PLUGIN_SLUG,
                esc_html__( 'Welcome to the settings page for the WooCommerce Min Max Quantity Extension. This plugin allows you to establish global minimum and maximum order quantities for your WooCommerce store products. For product-specific rules, visit the individual product pages. Use the settings below to adjust global quantities and manage any default messages shown to customers.', 'wizevolve-min-max-quantities' ),
                esc_html__( 'Set minimum and maximum order quantities for your WooCommerce store products.', 'wizevolve-min-max-quantities' ),
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/minmax.png',
                esc_html__( 'Min-Max Quantities', 'wizevolve-min-max-quantities' )
            );
            $options->start_section( esc_html__( 'Global Settings', 'wizevolve-min-max-quantities' ), esc_html__( 'Devine a minimum or maximum number of product which will be used for every product. The product specific rules will overwrite these settings.', 'wizevolve-min-max-quantities' ) );
            $options->add_number_option(
                'wizevolve-min-max-global-min',
                esc_html__( 'Global minimum quantity', 'wizevolve-min-max-quantities' ),
                esc_html__( 'Set a global minimum quantity every product should have.', 'wizevolve-min-max-quantities' ),
                '',
                true
            );
            $options->add_number_option(
                'wizevolve-min-max-global-max',
                esc_html__( 'Global maximum quantity', 'wizevolve-min-max-quantities' ),
                esc_html__( 'Set a global minimum quantity every product should have.', 'wizevolve-min-max-quantities' ),
                '',
                true
            );
            $options->start_section( esc_html__( 'Cart Content', 'wizevolve-min-max-quantities' ), esc_html__( 'Devine a minimum or maximum number of product a user should have in his cart before he can go to the checkout.', 'wizevolve-min-max-quantities' ) );
            $options->add_number_option(
                'wizevolve-min-max-cart-min',
                esc_html__( 'Minimum cart quantity', 'wizevolve-min-max-quantities' ),
                esc_html__( 'Use this to set a minimum amount of products the cart should contain to allow checkout', 'wizevolve-min-max-quantities' ),
                '',
                true
            );
            $options->add_number_option(
                'wizevolve-min-max-cart-max',
                esc_html__( 'Maximum cart quantity', 'wizevolve-min-max-quantities' ),
                esc_html__( 'Use this to set a maximum amount of products the cart can contain to allow checkout', 'wizevolve-min-max-quantities' ),
                '',
                true
            );
            $options->start_section( esc_html__( 'Notification settings', 'wizevolve-min-max-quantities' ), esc_html__( 'Manage what actions will cause notifications to the users.', 'wizevolve-min-max-quantities' ) );
            $options->add_select_option(
                'wizevolve-min-max-notification-stock',
                esc_html__( 'Stock notification', 'wizevolve-min-max-quantities' ),
                [
                'on'  => esc_html__( 'Show notifications', 'wizevolve-min-max-quantities' ),
                'off' => esc_html__( 'Hide notifications', 'wizevolve-min-max-quantities' ),
            ],
                esc_html__( 'Use this to show the user a message when the minimum order amount will exceed the current stock of the product.', 'wizevolve-min-max-quantities' ),
                'on',
                true
            );
            $options->start_section( esc_html__( 'Quantity selector setting', 'wizevolve-min-max-quantities' ), esc_html__( 'Manage how the quantity selectors will behave on the product detail pages and the cart page.', 'wizevolve-min-max-quantities' ) );
            $options->add_select_option(
                'wizevolve-min-max-restrict-quantity-selector',
                esc_html__( 'Apply rules to quantity selector', 'wizevolve-min-max-quantities' ),
                [
                'on'  => esc_html__( 'Apply rules', 'wizevolve-min-max-quantities' ),
                'off' => esc_html__( 'Unlock quantity selector', 'wizevolve-min-max-quantities' ),
            ],
                esc_html__( 'Choose weather or not the user should be able to enter quantities which will not be able to be purchased.', 'wizevolve-min-max-quantities' ),
                'on',
                true
            );
            $options->add_select_option(
                'wizevolve-min-max-restrict-quantity-selector-stock',
                esc_html__( 'Max quantity selector based on stock', 'wizevolve-min-max-quantities' ),
                [
                'on'  => esc_html__( 'Limit based on stock', 'wizevolve-min-max-quantities' ),
                'off' => esc_html__( 'Ignore stock', 'wizevolve-min-max-quantities' ),
            ],
                esc_html__( 'Change the upper bound of the quantity selector based on the maximum available stock for the product. <br /><strong>Note</strong>: only works if "Apply rules to quantity selector" is on and backorders are not allowed on the product.', 'wizevolve-min-max-quantities' ),
                'off',
                true
            );
            // Add min max fields to the product
            add_action( 'woocommerce_product_options_inventory_product_data', [ $this, 'register_product_meta_fields' ] );
            add_action(
                'woocommerce_product_after_variable_attributes',
                [ $this, 'register_variable_product_meta_fields' ],
                10,
                3
            );
            // Process the fields on save
            add_action( 'woocommerce_process_product_meta', [ $this, 'process_product_meta_fields_save' ] );
            add_action(
                'woocommerce_save_product_variation',
                [ $this, 'process_variable_product_meta_fields_save' ],
                10,
                2
            );
            // Check add to cart quantities
            add_filter(
                'woocommerce_add_to_cart_validation',
                [ $this, 'add_to_cart_validation' ],
                10,
                5
            );
            // Validate cart content
            add_action( 'woocommerce_check_cart_items', [ $this, 'check_cart_items' ] );
            // Add min, max and stock to variable ajax call
            add_filter( 'woocommerce_available_variation', [ $this, 'variation_data' ] );
        }
        
        function variation_data( $data ) : array
        {
            [ $min_quantity, $max_quantity ] = $this->get_quantities_product( wp_get_post_parent_id( $data['variation_id'] ), $data['variation_id'] );
            $min_quantity = apply_filters( 'wizevolve_min_max_quantities_variation_data_min_quantity', $min_quantity, $data );
            $max_quantity = apply_filters( 'wizevolve_min_max_quantities_variation_data_max_quantity', $max_quantity, $data );
            $data['_min_quantity'] = $min_quantity;
            $data['_max_quantity'] = $max_quantity;
            $product = wc_get_product( $data['variation_id'] );
            $product_stock = apply_filters( 'wizevolve_min_max_quantities_variation_data_stock', $product->get_stock_quantity(), $data );
            $data['_stock_quantity'] = $product_stock;
            return $data;
        }
        
        function check_cart_items() : void
        {
            // Check individual items
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product_id = $cart_item['product_id'];
                $quantity = $cart_item['quantity'];
                $variation_id = $cart_item['variation_id'];
                // Skip bundled items
                if ( isset( $cart_item['bundled_by'] ) ) {
                    continue;
                }
                if ( !apply_filters( 'wizevolve_min_max_quantities_check_cart_item', true, $cart_item ) ) {
                    continue;
                }
                [ $min_quantity, $max_quantity ] = $this->get_quantities_product( $product_id, $variation_id );
                $min_quantity = apply_filters( 'wizevolve_min_max_quantities_cart_validation_min_quantity', $min_quantity, $cart_item );
                $max_quantity = apply_filters( 'wizevolve_min_max_quantities_cart_validation_max_quantity', $max_quantity, $cart_item );
                if ( !empty($min_quantity) && $quantity < $min_quantity ) {
                    wc_add_notice( sprintf( esc_html__( 'The minimum allowed quantity for "%s" is %s. Please adjust your cart.', 'wizevolve-min-max-quantities' ), $cart_item['data']->get_title(), $min_quantity ), 'error' );
                }
                if ( !empty($max_quantity) && $quantity > $max_quantity ) {
                    wc_add_notice( sprintf( esc_html__( 'The maximum allowed quantity for "%s" is %s. Please adjust your cart.', 'wizevolve-min-max-quantities' ), $cart_item['data']->get_title(), $max_quantity ), 'error' );
                }
            }
        }
        
        function add_to_cart_validation(
            $passed,
            $product_id,
            $quantity,
            $variation_id = '',
            $variations = ''
        ) : bool
        {
            [ $min_quantity, $max_quantity ] = $this->get_quantities_product( $product_id, $variation_id );
            $min_quantity = apply_filters(
                'wizevolve_min_max_quantities_add_to_cart_quantity',
                $min_quantity,
                $product_id,
                $variation_id,
                $quantity
            );
            $max_quantity = apply_filters(
                'wizevolve_min_max_quantities_add_to_cart_quantity',
                $max_quantity,
                $product_id,
                $variation_id,
                $quantity
            );
            
            if ( !empty($min_quantity) && $quantity < $min_quantity ) {
                wc_add_notice( sprintf( esc_html__( 'You must add a minimum of %s products to your cart.', 'wizevolve-min-max-quantities' ), $min_quantity ), 'error' );
                $passed = false;
            }
            
            
            if ( !empty($max_quantity) && $quantity > $max_quantity ) {
                wc_add_notice( sprintf( esc_html__( 'You can add a maximum of %s products to your cart.', 'wizevolve-min-max-quantities' ), $max_quantity ), 'error' );
                $passed = false;
            }
            
            return apply_filters(
                'wizevolve_min_max_quantities_add_to_cart_validation',
                $passed,
                $product_id,
                $variation_id,
                $quantity,
                $min_quantity,
                $max_quantity
            );
        }
        
        function register_product_meta_fields() : void
        {
            echo  '<div class="options_group">' ;
            woocommerce_wp_text_input( array(
                'id'                => '_min_quantity',
                'label'             => esc_html__( 'Minimum Quantity', 'wizevolve-min-max-quantities' ),
                'placeholder'       => '',
                'desc_tip'          => 'true',
                'description'       => esc_html__( 'Enter the minimum quantity for this product.', 'wizevolve-min-max-quantities' ),
                'type'              => 'number',
                'custom_attributes' => array(
                'step' => 'any',
                'min'  => '0',
            ),
            ) );
            woocommerce_wp_text_input( array(
                'id'                => '_max_quantity',
                'label'             => esc_html__( 'Maximum Quantity', 'wizevolve-min-max-quantities' ),
                'placeholder'       => '',
                'desc_tip'          => 'true',
                'description'       => esc_html__( 'Enter the maximum quantity for this product.', 'wizevolve-min-max-quantities' ),
                'type'              => 'number',
                'custom_attributes' => array(
                'step' => 'any',
                'min'  => '0',
            ),
            ) );
            echo  '</div>' ;
        }
        
        function process_product_meta_fields_save( $post_id ) : void
        {
            // Sanitize and validate min_quantity
            $min_quantity = filter_input( INPUT_POST, '_min_quantity', FILTER_SANITIZE_NUMBER_INT );
            
            if ( filter_var( $min_quantity, FILTER_VALIDATE_INT ) || $min_quantity === '' ) {
                $min_quantity = esc_attr( $min_quantity );
                update_post_meta( $post_id, '_min_quantity', $min_quantity );
            }
            
            // Sanitize and validate max_quantity
            $max_quantity = filter_input( INPUT_POST, '_max_quantity', FILTER_SANITIZE_NUMBER_INT );
            
            if ( filter_var( $max_quantity, FILTER_VALIDATE_INT ) || $max_quantity === '' ) {
                $max_quantity = esc_attr( $max_quantity );
                update_post_meta( $post_id, '_max_quantity', $max_quantity );
            }
        
        }
        
        function register_variable_product_meta_fields( $loop, $variation_data, $variation ) : void
        {
            woocommerce_wp_text_input( array(
                'id'    => '_variable_min_quantity[' . $loop . ']',
                'label' => esc_html__( 'Minimum Quantity', 'wizevolve-min-max-quantities' ),
                'value' => get_post_meta( $variation->ID, '_variable_min_quantity', true ),
            ) );
            woocommerce_wp_text_input( array(
                'id'    => '_variable_max_quantity[' . $loop . ']',
                'label' => esc_html__( 'Maximum Quantity', 'wizevolve-min-max-quantities' ),
                'value' => get_post_meta( $variation->ID, '_variable_max_quantity', true ),
            ) );
        }
        
        function process_variable_product_meta_fields_save( $variation_id, $i ) : void
        {
            // Get the entire POST arrays
            $variable_min_quantities = filter_input(
                INPUT_POST,
                '_variable_min_quantity',
                FILTER_DEFAULT,
                FILTER_REQUIRE_ARRAY
            );
            $variable_max_quantities = filter_input(
                INPUT_POST,
                '_variable_max_quantity',
                FILTER_DEFAULT,
                FILTER_REQUIRE_ARRAY
            );
            // Sanitize and validate variable_min_quantity
            $variable_min_quantity = ( isset( $variable_min_quantities[$i] ) ? filter_var( $variable_min_quantities[$i], FILTER_SANITIZE_NUMBER_INT ) : '' );
            
            if ( filter_var( $variable_min_quantity, FILTER_VALIDATE_INT ) || $variable_min_quantity === '' ) {
                $variable_min_quantity = esc_attr( $variable_min_quantity );
                update_post_meta( $variation_id, '_variable_min_quantity', $variable_min_quantity );
            }
            
            // Sanitize and validate variable_max_quantity
            $variable_max_quantity = ( isset( $variable_max_quantities[$i] ) ? filter_var( $variable_max_quantities[$i], FILTER_SANITIZE_NUMBER_INT ) : '' );
            
            if ( filter_var( $variable_max_quantity, FILTER_VALIDATE_INT ) || $variable_max_quantity === '' ) {
                $variable_max_quantity = esc_attr( $variable_max_quantity );
                update_post_meta( $variation_id, '_variable_max_quantity', $variable_max_quantity );
            }
        
        }
        
        private function get_quantities_product( $product_id, mixed $variation_id = '' ) : array
        {
            // Get variation rules
            
            if ( $variation_id ) {
                $min_quantity_var = get_post_meta( $variation_id, '_variable_min_quantity', true );
                $max_quantity_var = get_post_meta( $variation_id, '_variable_max_quantity', true );
            }
            
            // Get product rules
            $min_quantity_prod = get_post_meta( $product_id, '_min_quantity', true );
            $max_quantity_prod = get_post_meta( $product_id, '_max_quantity', true );
            // Use product rules if no variation rules
            $min_quantity = ( !empty($min_quantity_var) ? $min_quantity_var : $min_quantity_prod );
            $max_quantity = ( !empty($max_quantity_var) ? $max_quantity_var : $max_quantity_prod );
            $min_quantity = apply_filters(
                'wizevolve_min_max_quantities_get_min_quantity',
                $min_quantity,
                $product_id,
                $variation_id
            );
            $max_quantity = apply_filters(
                'wizevolve_min_max_quantities_get_max_quantity',
                $max_quantity,
                $product_id,
                $variation_id
            );
            return [ $min_quantity, $max_quantity ];
        }
    
    }
}