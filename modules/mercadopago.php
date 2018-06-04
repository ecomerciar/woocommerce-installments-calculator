<?php

namespace Ecomerciar\InstallmentsCalculator\Modules;

if (!defined('ABSPATH')) {
    exit;// Exit if accessed directly.
}

class Mercadopago
{
    private $client_id, $client_secret, $access_token;

    public function __construct()
    {

        $credentials = unserialize(get_option('ins_calc_mercadopago_credentials'));
        $installments = unserialize(get_option('ins_calc_mercadopago_installments'));

        $this->client_id = $credentials['username'];
        $this->client_secret = $credentials['password'];
        $this->installments = $installments;
        $this->logger = wc_get_logger();

        $access_token = get_option('ins_calc_mp_at');
        if (!$access_token) {
            $this->set_access_token();
            $access_token = get_option('ins_calc_mp_at');
        }
        $this->access_token = $access_token;

    }

    public function get_client_id()
    {
        return $this->client_id;
    }

    public function get_client_secret()
    {
        return $this->client_secret;
    }

    public function get_access_token()
    {
        return $this->access_token;
    }

    private function get_installment_additional_msg($labels = array())
    {
        $new_arr = array_filter($labels, function ($label) {
            if (strrpos($label, 'CFT_') !== false) {
                return $label;
            }
        });
        return array_shift($new_arr);
    }

    private function calculate_cft($label)
    {
        $cft = explode('|', $label)[0];
        $cft = str_replace('CFT_', '', $cft);
        $cft = str_replace('%', '', $cft);
        $cft = str_replace(',', '.', $cft);
        return (float)$cft;
    }

    private function get_installment_msg($msg)
    {
        $new_msg_before = explode('cuotas de', $msg);
        $new_msg_after = explode('(', $new_msg_before[1]);
        $new_msg = $new_msg_before[0] . 'cuotas de <span class="installment-value">' . $new_msg_after[0] . '</span>(' . $new_msg_after[1];
        return $new_msg;
    }

    public function get_installments($card_id = '', $bank_id = '', $amount = 0)
    {
        if (!$card_id || !$amount) {
            return false;
        }
        if (empty($bank_id)) {
            $response = $this->call_api('GET', '/v1/payment_methods/installments', array('access_token' => $this->get_access_token(), 'payment_method_id' => $card_id, 'amount' => $amount));
        } else {
            $response = $this->call_api('GET', '/v1/payment_methods/installments', array('access_token' => $this->get_access_token(), 'payment_method_id' => $card_id, 'issuer.id' => $bank_id, 'amount' => $amount));
        }
        if (is_wp_error($response)) {
            $this->logger->error('Mercadopago -> WP Error al obtener cuotas: ' . $response->get_error_message(), unserialize(LOGGER_CONTEXT));
            return false;
        }
        if ($response['response']['code'] === 200) {
            $response = json_decode($response['body'], true);
            if (empty($response)) {
                $this->logger->error('Mercadopago -> Cuotas - Error del servidor mensaje: ' . (isset($response['message']) ? $response['message'] : 'Sin mensaje'), unserialize(LOGGER_CONTEXT));
                return false;
            }
            $response = array_shift($response);
            if (isset($response['payer_costs'])) {
                $new_installments = array();
                foreach ($response['payer_costs'] as $installment) {
                    if ($installment['installments'] === 1) {
                        continue;
                    }
                    $new_installment = array();
                    $new_installment['message'] = (isset($installment['recommended_message']) ? $this->get_installment_msg($installment['recommended_message']) : $installment['installments'] . ' cuotas de <span class="installment-value">$' . $installment['installment_amount'] . '</span> ($' . $installment['total_amount'] . ')');
                    $new_installment['CFT_message'] = (isset($installment['labels']) ? $this->get_installment_additional_msg($installment['labels']) : '');
                    $new_installment['CFT'] = ($new_installment['CFT_message'] ? $this->calculate_cft($new_installment['CFT_message']) : '');
                    $new_installments[$installment['installments']] = $new_installment;
                }
            } else {
                $this->logger->error('Mercadopago -> Cuotas - Error del servidor mensaje: ' . (isset($response['message']) ? $response['message'] : 'Sin mensaje'), unserialize(LOGGER_CONTEXT));
                return false;
            }
        } else {
            $this->logger->error('Mercadopago -> Cuotas - Error del servidor codigo:' . (isset($response['response']['code']) ? $response['response']['code'] : 'Sin codigo'), unserialize(LOGGER_CONTEXT));
            $response = json_decode($response['body'], true);
            $this->logger->error('Mercadopago -> Cuotas - Error del servidor mensaje: ' . (isset($response['message']) ? $response['message'] : 'Sin mensaje'), unserialize(LOGGER_CONTEXT));
            return false;
        }

        // Use custom installments instead default ones
        $custom_installments = unserialize(get_option('ins_calc_mercadopago_installments'));

        foreach ($new_installments as $installment_qty => $installment_desc) {
            if ($custom_installments[$installment_qty] && is_numeric($custom_installments[$installment_qty])) {
                $total_amount = $amount + ($amount * ($custom_installments[$installment_qty] / 100));
                $installment_price = ($total_amount / $installment_qty);
                $new_installments[$installment_qty]['message'] = $installment_qty . ' cuotas de $' . number_format($installment_price, 2) . ' ($' . $total_amount . ')';
            }
        }
        return $new_installments;
    }

    public function get_banks($card_id = '')
    {
        if (!$card_id) {
            return false;
        }
        $response = $this->call_api('GET', '/v1/payment_methods/card_issuers', array('access_token' => $this->get_access_token(), 'payment_method_id' => $card_id));
        if (is_wp_error($response)) {
            $this->logger->error('Mercadopago -> WP Error al obtener bancos: ' . $response->get_error_message(), unserialize(LOGGER_CONTEXT));
            return false;
        }
        if ($response['response']['code'] === 200) {
            $response = json_decode($response['body'], true);
            $new_banks = array();
            foreach ($response as $bank) {
                $new_bank = array();
                $new_bank['id'] = $bank['id'];
                $new_bank['name'] = $bank['name'];
                $new_bank['image'] = $bank['secure_thumbnail'];
                $new_banks[] = $new_bank;
            }
            return $new_banks;
        } else {
            $this->logger->error('Mercadopago -> Bancos - Error del servidor codigo:' . (isset($response['response']['code']) ? $response['response']['code'] : 'Sin codigo'), unserialize(LOGGER_CONTEXT));
            $response = json_decode($response['body'], true);
            $this->logger->error('Mercadopago -> Bancos - Error del servidor mensaje: ' . (isset($response['message']) ? $response['message'] : 'Sin mensaje'), unserialize(LOGGER_CONTEXT));
            return false;
        }
    }

    public function get_cards()
    {
        $response = $this->call_api('GET', '/v1/payment_methods', array('access_token' => $this->get_access_token()));
        if (is_wp_error($response)) {
            $this->logger->error('Mercadopago -> WP Error al obtener metodos de pago: ' . $response->get_error_message(), unserialize(LOGGER_CONTEXT));
            return false;
        }
        if ($response['response']['code'] === 200) {
            $response = json_decode($response['body'], true);
            $new_payment_methods = array();
            foreach ($response as $payment_method) {
                if ($payment_method['status'] === 'active') {
                    $new_payment_method = array();
                    $new_payment_method['id'] = $payment_method['id'];
                    $new_payment_method['name'] = $payment_method['name'];
                    $new_payment_method['image'] = $payment_method['secure_thumbnail'];
                    $new_payment_method['min_amount'] = $payment_method['min_allowed_amount'];
                    $new_payment_method['max_amount'] = $payment_method['max_allowed_amount'];
                    $new_payment_methods[] = $new_payment_method;
                }
            }
            return $new_payment_methods;
        } else {
            $this->logger->error('Mercadopago -> Metodos de pago - Error del servidor codigo:' . (isset($response['response']['code']) ? $response['response']['code'] : 'Sin codigo'), unserialize(LOGGER_CONTEXT));
            $response = json_decode($response['body'], true);
            $this->logger->error('Mercadopago -> Metodos de pago - Error del servidor mensaje: ' . (isset($response['message']) ? $response['message'] : 'Sin mensaje'), unserialize(LOGGER_CONTEXT));
            return false;
        }
    }

    private function set_access_token()
    {
        $response = $this->call_api('POST', '/oauth/token', array('grant_type' => 'client_credentials', 'client_id' => $this->get_client_id(), 'client_secret' => $this->get_client_secret()));
        if (is_wp_error($response)) {
            $this->logger->error('Mercadopago -> WP Error al obtener access token: ' . $response->get_error_message(), unserialize(LOGGER_CONTEXT));
            return false;
        }
        if ($response['response']['code'] === 200) {
            $response = json_decode($response['body'], true);
            if (isset($response['access_token'])) {
                update_option('ins_calc_mp_at', $response['access_token']);
                $this->access_token = $response['access_token'];
            } else {
                $this->logger->error('Mercadopago -> Error del servidor al obtener access token: ' . (isset($response['message']) ? $response['message'] : 'Sin mensaje'), unserialize(LOGGER_CONTEXT));
                return false;
            }
        } else {
            $this->logger->error('Mercadopago -> Access token - Error del servidor codigo:' . (isset($response['response']['code']) ? $response['response']['code'] : 'Sin codigo'), unserialize(LOGGER_CONTEXT));
            $response = json_decode($response['body'], true);
            $this->logger->error('Mercadopago -> Access token - Error del servidor mensaje: ' . (isset($response['message']) ? $response['message'] : 'Sin mensaje'), unserialize(LOGGER_CONTEXT));
            return false;
        }
    }

    public function call_api($method = '', $endpoint = '', $params = array(), $headers = array())
    {
        if ($method && $endpoint) {
            $url = 'https://api.mercadopago.com' . $endpoint;
            if ($method === 'GET') {
                $response = wp_remote_get($url . '?' . http_build_query($params));
            } else {
                $args = array(
                    'headers' => $headers,
                    'body' => json_encode($params)
                );
                if (empty($headers)) {
                    $args['headers'] = array(
                        'Content-Type' => 'application/json'
                    );
                }
                $response = wp_remote_post($url, $args);
            }

            // Token call, return it to prevent endless execution
            if ($endpoint === '/oauth/token') {
                return $response;
            }

            if (is_wp_error($response)) {
                $this->logger->error('Mercadopago -> WP Error antes de reintentar al llamar a la API: ' . $response->get_error_message(), unserialize(LOGGER_CONTEXT));
                return false;
            }

            // Bad call - Maybe invalid Access token?
            if ($response['response']['code'] === 401) {
                $this->set_access_token();
                if (isset($params['access_token'])) {
                    $params['access_token'] = $this->get_access_token();
                }
            } else {
            // Good call - return data
                return $response;
            }

            // New Access token, retry call
            if ($method === 'GET') {
                $response = wp_remote_get($url . '?' . http_build_query($params));
            } else {
                $args = array(
                    'headers' => $headers,
                    'body' => json_encode($params)
                );
                if (empty($headers)) {
                    $args['headers'] = array(
                        'Content-Type' => 'application/json'
                    );
                }
                $response = wp_remote_post($url, $args);
            }
            if (is_wp_error($response)) {
                $this->logger->error('Mercadopago -> WP Error luego de reintentar al llamar a la API: ' . $response->get_error_message(), unserialize(LOGGER_CONTEXT));
                return false;
            } else {
                return $response;
            }
        }
    }
}