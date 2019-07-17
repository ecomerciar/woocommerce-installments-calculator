<?php

namespace Ecomerciar\InstallmentsCalculator\InstallmentsCalculator;

use Ecomerciar\InstallmentsCalculator\Modules\Mercadopago;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function add_calculator()
{
    global $product;
    $payment_methods = unserialize(get_option('ins_calc_payment_methods'));
    if (empty($payment_methods)) {
        return false;
    }
    $payment_methods_titles = get_titles($payment_methods);
    $output = '<div id="installments-calculator">';
    foreach ($payment_methods as $payment_method) {
        if ($payment_method === 'mercadopago') {
            $module = new Mercadopago;
        }
        $output .= '<h3> Calculá tus cuotas de ' . $payment_methods_titles[$payment_method] . '</h3>';
        $output .= '<div>';
        $output .= print_cards_select($module, $payment_method, $product);
        $output .= print_banks_select($payment_method);
        $output .= print_installments_select($payment_method);
        $output .= '<h5 id="installments_message"></h5>';
        $output .= '</div>';
    }
    $output .= '</div>';

    $main_color = get_option('ins_calc_payment_methods_color', '');
    $output .= '<style>';
    $output .= 'h3.ui-state-active{border:1px solid ' . $main_color . ';background:' . $main_color . '}';
    $output .= '</style>';

    add_calculator_files();

    return $output;
}

function add_calculator_files()
{
    global $post;
    wp_enqueue_script('calculator.js', plugin_dir_url(__FILE__) . 'js/calculator.js', array('jquery', 'jquery-ui-core', 'jquery-ui-accordion'), '', true);
    wp_enqueue_style('calculator.css', plugin_dir_url(__FILE__) . 'css/calculator.css', array(), "1.4.3");
    wp_localize_script('calculator.js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'product_id' => $post->ID));
}

function print_cards_select($module, $payment_method = '', $product)
{
    $output = '';
    $output .= '<select id="' . $payment_method . '_installments_calculator_cards">';
    $output .= '<option value="nothing">Tarjeta</option>';
    foreach ($module->get_cards() as $card) {
        $price = $product->get_price();
        if ($price >= $card['min_amount'] && $price <= $card['max_amount']) {
            $output .= '<option value="' . $card['id'] . '">' . $card['name'] . '</option>';
        }
    }
    $output .= '</select>';
    return $output;
}

function print_banks_select($payment_method)
{
    $output = '';
    $output .= '<select id="' . $payment_method . '_installments_calculator_banks">';
    $output .= '<option value="nothing">Banco</option>';
    $output .= '</select>';
    return $output;
}

function print_installments_select($payment_method)
{
    $output = '';
    $output .= '<select id="' . $payment_method . '_installments_calculator_installments">';
    $output .= '<option value="nothing">Cuotas</option>';
    $output .= '</select>';
    return $output;
}

function get_titles($payment_methods = array())
{
    $titles = array();
    foreach ($payment_methods as $payment_method) {
        if ($payment_method === 'mercadopago') {
            $titles['mercadopago'] = 'Mercado Pago';
        }
        if ($payment_method === 'todopago') {
            $titles['todopago'] = 'Todo Pago';
        }
    }
    return $titles;
}

function check_card()
{
    $card_selected = strip_tags($_POST['card_selected']);
    $payment_method = strip_tags($_POST['payment_method']);
    $product = wc_get_product($_POST['product_id']);
    //if (!$product) wp_send_json_error(array('msg' => 'Couldn\'t retreive product'));
    if (!$product) wp_send_json_error(array('msg' => print_r($_POST, true)));
    $price = $product->get_price();
    if ($payment_method === 'mercadopago') {
        $module = new Mercadopago;
    }
    $banks = $module->get_banks($card_selected);
    if ($banks) {
        wp_send_json_success(array('banks' => $banks));
    } else {
        $installments = $module->get_installments($card_selected, '', $price);
        if ($installments) {
            wp_send_json_success(array('installments' => $installments));
        } else {
            wp_send_json_error(array('msg' => 'Error obteniendo los bancos de ' . $payment_method));
        }
    }
}

function check_bank()
{
    $product = wc_get_product($_POST['product_id']);
    $card_selected = strip_tags($_POST['card_selected']);
    $bank_selected = strip_tags($_POST['bank_selected']);
    $payment_method = strip_tags($_POST['payment_method']);
    $price = $product->get_price();
    if ($payment_method === 'mercadopago') {
        $module = new Mercadopago;
    }
    $installments = $module->get_installments($card_selected, $bank_selected, $price);
    if ($installments) {
        wp_send_json_success(array('installments' => $installments));
    } else {
        wp_send_json_error(array('msg' => 'Error obteniendo las cuotas de ' . $payment_method));
    }
}