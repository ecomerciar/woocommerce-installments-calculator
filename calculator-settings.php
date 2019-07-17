<?php

namespace Ecomerciar\InstallmentsCalculator\Settings;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

function init_settings()
{
	register_setting('ecom_installments_calculator', 'ecom_installments_calculator_options');

	add_settings_section(
		'ecom_installments_calculator',
		'Configuración',
		'',
		'installments_calculator_settings'
	);

	add_settings_field(
		'payment_methods',
		__('Medios de pago activados', 'installments_calculator'),
		__NAMESPACE__ . '\print_payment_methods',
		'installments_calculator_settings',
		'ecom_installments_calculator'
	);

	add_settings_field(
		'payment_methods_credentials',
		__('Configurar credenciales', 'installments_calculator'),
		__NAMESPACE__ . '\print_pm_cfg_credentials',
		'installments_calculator_settings',
		'ecom_installments_calculator'
	);

	add_settings_field(
		'payment_methods_checks',
		__('Configurar manualmente?', 'installments_calculator'),
		__NAMESPACE__ . '\print_pm_cfg_checks',
		'installments_calculator_settings',
		'ecom_installments_calculator'
	);

	add_settings_field(
		'payment_methods_installments',
		__('Configuración manual', 'installments_calculator'),
		__NAMESPACE__ . '\print_pm_cfg_installments',
		'installments_calculator_settings',
		'ecom_installments_calculator'
	);

	add_settings_field(
		'payment_methods_color',
		__('Color principal', 'installments_calculator'),
		__NAMESPACE__ . '\print_pm_cfg_color',
		'installments_calculator_settings',
		'ecom_installments_calculator'
	);

	add_settings_field(
		'payment_methods_info',
		__('Info', 'installments_calculator'),
		__NAMESPACE__ . '\print_pm_cfg_info',
		'installments_calculator_settings',
		'ecom_installments_calculator'
	);

}


function print_payment_methods()
{
	$payment_methods = unserialize(get_option('ins_calc_payment_methods'));
	$mp = $tp = false;
	if ($payment_methods && count($payment_methods) > 0) {
		$mp = in_array('mercadopago', $payment_methods);
		$tp = in_array('todopago', $payment_methods);
	}
	echo '<select name="ins_calc_payment_methods[]" multiple style="width:200px">';
	echo '<option value="mercadopago"' . ($mp ? 'selected' : '') . '>Mercado Pago</option>';
	echo '<option value="todopago"' . ($tp ? 'selected' : '') . ' >Todo Pago</option>';
	echo '</select>';
}

function print_pm_cfg_credentials()
{
	$payment_methods = unserialize(PAYMENT_METHODS);
	$previous_config = array();
	foreach ($payment_methods as $payment_method) {
		$opt = unserialize(get_option('ins_calc_' . $payment_method . '_credentials'));
		if ($opt) {
			$previous_config[$payment_method]['username'] = $opt['username'];
			$previous_config[$payment_method]['password'] = $opt['password'];
		}
	}
	if ($payment_methods && count($payment_methods) > 0) {
		echo '<table class="widefat">';
		foreach ($payment_methods as $payment_method) {
			echo '<tr>';
			echo '<td style="width: 20%"><strong>' . ucfirst($payment_method) . '</strong></td>
			<td style="text-align: left; width: 20%"><input type="text" name="credentials[' . $payment_method . '][username]" value="' . $previous_config[$payment_method]['username'] . '" placeholder="Client ID"></td>
			<td style="text-align: left; width: 20%"><input type="text" name="credentials[' . $payment_method . '][password]" value="' . $previous_config[$payment_method]['password'] . '" placeholder="Client Secret"></td>
			' . '<td style="width: ' . (100 - (count($payment_methods) * 20)) . '%"></td>';
			echo '</tr>';
		}
		echo '</table>';
	}
}

function print_pm_cfg_checks()
{
	$payment_methods = unserialize(PAYMENT_METHODS);
	$previous_config = unserialize(get_option('ins_calc_payment_methods_manual_cfg'));
	if ($payment_methods && count($payment_methods) > 0) {
		echo '<table class="widefat"><thead><tr>';
		foreach ($payment_methods as $payment_method) {
			echo '<th style="text-align: center; width: 20%"><strong>' . ucfirst($payment_method) . '</strong></th>';
		}
		echo '<th style="width: ' . (100 - (count($payment_methods) * 20)) . '%"></th>';
		echo '</tr></thead>';
		echo '<tr>';
		foreach ($payment_methods as $payment_method) {
			if ($previous_config)
				echo '<td style="text-align: center; width: 20%"><input type="checkbox" name="manual_config[]" value="' . $payment_method . '" ' . (in_array($payment_method, $previous_config) ? 'checked="checked"' : '') . '"></td>';
			else
				echo '<td style="text-align: center; width: 20%"><input type="checkbox" name="manual_config[]" value="' . $payment_method . '"></td>';
		}
		echo '</tr>';
		echo '</table>';
	}
}

function print_pm_cfg_installments()
{
	$payment_methods = unserialize(PAYMENT_METHODS);
	if ($payment_methods && count($payment_methods) > 0) {
		echo '<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th style="text-align:center"><strong>Payment method</strong></th>
						<th style="text-align:center"><strong>3 Installments</strong></th>
						<th style="text-align:center"><strong>6 Installments</strong></th>
						<th style="text-align:center"><strong>9 Installments</strong></th>
						<th style="text-align:center"><strong>12 Installments</strong></th>
						<th style="text-align:center"><strong>18 Installments</strong></th>
						<th style="text-align:center"><strong>24 Installments</strong></th>
					</tr>
				</thead>';
		foreach ($payment_methods as $payment_method) {
			echo '<tr>';
			echo '<td style="text-align: center"><label><strong>' . ucfirst($payment_method) . '</strong></label></td>';
			$previous_config = unserialize(get_option('ins_calc_' . $payment_method . '_installments'));
			echo '<td style="text-align: center"><input type="text" style="width: 82%;" class="input-text regular-input" value="' . ($previous_config[3] ? $previous_config[3] : '') . '" placeholder="Value in % (ex. 5)" name="' . $payment_method . '_installments[3]"></td>';
			echo '<td style="text-align: center"><input type="text" style="width: 82%;" class="input-text regular-input" value="' . ($previous_config[6] ? $previous_config[6] : '') . '" placeholder="Value in % (ex. 5)" name="' . $payment_method . '_installments[6]"></td>';
			echo '<td style="text-align: center"><input type="text" style="width: 82%;" class="input-text regular-input" value="' . ($previous_config[9] ? $previous_config[9] : '') . '" placeholder="Value in % (ex. 5)" name="' . $payment_method . '_installments[9]"></td>';
			echo '<td style="text-align: center"><input type="text" style="width: 82%;" class="input-text regular-input" value="' . ($previous_config[12] ? $previous_config[12] : '') . '" placeholder="Value in % (ex. 5)" name="' . $payment_method . '_installments[12]"></td>';
			echo '<td style="text-align: center"><input type="text" style="width: 82%;" class="input-text regular-input" value="' . ($previous_config[18] ? $previous_config[18] : '') . '" placeholder="Value in % (ex. 5)" name="' . $payment_method . '_installments[18]"></td>';
			echo '<td style="text-align: center"><input type="text" style="width: 82%;" class="input-text regular-input" value="' . ($previous_config[24] ? $previous_config[24] : '') . '" placeholder="Value in % (ex. 5)" name="' . $payment_method . '_installments[24]"></td>';
			echo '</tr>';
		}
		echo '</table>';
	}
}

function print_pm_cfg_color()
{
	$payment_methods = unserialize(PAYMENT_METHODS);
	$previous_config = get_option('ins_calc_payment_methods_color', '');
	echo '<input type="text" name="color_config" placeholder="#FFFFFF" value="' . $previous_config . '">';
}

function print_pm_cfg_info()
{
	echo 'Agregá la calculadora donde quieras usando el shortcode [ecomerciar_woocommerce_installments_calculator]';
}

function create_menu_option()
{
	add_options_page(
		'Calculadora de Financiación',
		'Calculadora de Financiación',
		'manage_options',
		'installments_calculator_settings',
		__NAMESPACE__ . '\settings_page_content'
	);
}

function settings_page_content()
{

	if (!current_user_can('manage_options')) {
		return;
	}

	// Save select
	if (isset($_POST['ins_calc_payment_methods']) && count($_POST['ins_calc_payment_methods'])) {
		update_option('ins_calc_payment_methods', serialize($_POST['ins_calc_payment_methods']));
	}

	// Save checkboxes
	if (isset($_POST['manual_config']) && count($_POST['manual_config'])) {
		update_option('ins_calc_payment_methods_manual_cfg', serialize($_POST['manual_config']));
	}

	// Save Color
	if (isset($_POST['color_config']) && count($_POST['color_config'])) {
		update_option('ins_calc_payment_methods_color', $_POST['color_config']);
	}

	// Save credentials and installments
	$payment_methods = unserialize(PAYMENT_METHODS);
	if (isset($_POST['credentials']) && count($_POST['credentials'])) {
		foreach ($payment_methods as $payment_method) {
			update_option('ins_calc_' . $payment_method . '_credentials', serialize($_POST['credentials'][$payment_method]));
			update_option('ins_calc_' . $payment_method . '_installments', serialize($_POST[$payment_method . '_installments']));
		}
	}

	?>
	<div class="wrap">
		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
		<form action="options-general.php?page=installments_calculator_settings" method="post">
			<?php
		settings_fields('installments_calculator_settings');
		do_settings_sections('installments_calculator_settings');
		submit_button('Guardar');
		?>
		</form>
	</div>
	<?php

}