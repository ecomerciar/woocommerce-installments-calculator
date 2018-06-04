<?php

/*
Plugin Name: Woocommerce Installments calculator
Plugin URI: http://ecomerciar.com
Description: Calculadora para Woocommerce que muestra el interés de las cuotas según el medio de pago
Version: 1.0
Author: Ecomerciar
Author URI: http://ecomerciar.com
License: GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

define('PAYMENT_METHODS', serialize(array('mercadopago', 'todopago')));
define('LOGGER_CONTEXT', serialize(array('source' => 'installments_calculator')));

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

require_once 'calculator-settings.php';
require_once 'installments-calculator.php';
require_once 'hooks.php';
require_once 'modules/mercadopago.php';