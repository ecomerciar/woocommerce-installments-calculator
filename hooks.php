<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// --- Settings
add_action('admin_init', 'Ecomerciar\InstallmentsCalculator\Settings\init_settings');
add_action('admin_menu', 'Ecomerciar\InstallmentsCalculator\Settings\create_menu_option');

// --- Frontend
add_action('woocommerce_before_add_to_cart_button', 'Ecomerciar\InstallmentsCalculator\InstallmentsCalculator\add_calculator');
add_action('wp_ajax_ic_check_card', 'Ecomerciar\InstallmentsCalculator\InstallmentsCalculator\check_card');
add_action('wp_ajax_nopriv_ic_check_card', 'Ecomerciar\InstallmentsCalculator\InstallmentsCalculator\check_card');
add_action('wp_ajax_ic_check_bank', 'Ecomerciar\InstallmentsCalculator\InstallmentsCalculator\check_bank');
add_action('wp_ajax_nopriv_ic_check_bank', 'Ecomerciar\InstallmentsCalculator\InstallmentsCalculator\check_bank');
