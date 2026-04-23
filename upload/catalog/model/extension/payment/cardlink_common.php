<?php
class ModelExtensionPaymentCardlinkCommon extends Model {

    public function buildRedirectFormData($config, $order_id, $isIris = false, $useToken = false, $token = '', $installments = '') {

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $ref = 'REF' . substr(md5(uniqid('', true)), 0, 9);
        $orderid_ref = substr($order_id . $ref, 0, 50);

        $lang_code = strtoupper($this->language->get('code'));
        $lang = ($lang_code === 'EL' || $lang_code === 'GR') ? 'el' : 'en';

        if (empty($order_info['payment_iso_code_2'])) {
            $order_info['payment_iso_code_2'] = 'GR';
        }

        $session_id = session_id();
        $route_base = 'extension/payment/' . ($isIris ? 'cardlink_iris' : 'cardlink') . '/callback';

        $callbackUrl = $this->url->link($route_base, 'session_id=' . $session_id, true);
        // Force HTTPS — OpenCart may generate http:// if HTTPS_CATALOG is not set in config.php,
        // causing a 301 redirect that drops the POST body from Cardlink's background confirmation.
        if (strncmp($callbackUrl, 'http://', 7) === 0) {
            $callbackUrl = 'https://' . substr($callbackUrl, 7);
        }
        $confirmUrl = $callbackUrl;
        $cancelUrl  = $callbackUrl;

        $data = [
            'version'        => '2',
            'mid'            => $config['merchantid'],
            'lang'           => $lang,
            'deviceCategory' => '0',

            'orderid'        => $orderid_ref,
            'orderDesc'      => 'Order #' . $order_id,
            'orderAmount'    => $this->formatAmount($order_info['total']),
            'currency'       => 'EUR',

            'payerEmail'     => $order_info['email'],
            'payerPhone'     => $order_info['telephone'] ?? '',

            'billCountry'    => $order_info['payment_iso_code_2'],
            'billState'      => '',
            'billZip'        => $order_info['payment_postcode'],
            'billCity'       => $order_info['payment_city'],
            'billAddress'    => $order_info['payment_address_1'],

            'shipCountry'    => $order_info['shipping_iso_code_2'],
            'shipZip'        => $order_info['shipping_postcode'],
            'shipCity'       => $order_info['shipping_city'],
            'shipAddress'    => $order_info['shipping_address_1'],

            'payMethod'      => $config['payMethod'],
            'trType'         => $config['trType'],

            'cssUrl'         => trim($config['cssUrl']),

            'confirmUrl'     => $confirmUrl,
            'cancelUrl'      => $cancelUrl,

            'var1' => $config['var1'] ?? '',
            'var2' => $config['var2'] ?? '',
            'var3' => $config['var3'] ?? '',
            'var4' => $config['var4'] ?? '',
            'var5' => $config['var5'] ?? '',

            'use_iframe' => $config['iframe']
        ];

        // tokenization
        if (!empty($config['tokenization']) && $config['tokenization'] == '1') {

            // 1) saved card payment
            if ($useToken && !empty($token)) {
                $data['extToken'] = $token;
                $data['extTokenOptions'] = '110'; // AUTO payment with token
            }
            // 2) store new card
            else if (!empty($config['store_card']) && $config['store_card'] == '1') {
                $data['extTokenOptions'] = '100'; // show payment page with prefilled card data
            }
        }

        // installments
        if ($installments !== null && $installments > 0) {
            $data['extInstallmentoffset'] = '0';
            $data['extInstallmentperiod'] = (string)$installments;
        }

        $data['digest'] = $this->calculateRequestDigest($data, $config['merchantpass']);

        return $data;
    }


    private function calculateRequestDigest(array $d, string $merchantpass): string {

        $digeststring =
            $d['version'] .
            $d['mid'] .
            $d['lang'] .
            $d['deviceCategory'] .
            $d['orderid'] .
            $d['orderDesc'] .
            $d['orderAmount'] .
            $d['currency'] .
            $d['payerEmail'] .
            $d['payerPhone'] .
            $d['billCountry'] .
            $d['billState'] .
            $d['billZip'] .
            html_entity_decode($d['billCity']) .
            html_entity_decode($d['billAddress']) .
            $d['shipCountry'] .
            $d['shipZip'] .
            html_entity_decode($d['shipCity']) .
            html_entity_decode($d['shipAddress']) .
            $d['payMethod'] .
            $d['trType'];

        // installment fields (ONE OR THE OTHER)
        if (isset($d['extInstallmentoffset']) && isset($d['extInstallmentperiod'])) {
            $digeststring .= $d['extInstallmentoffset'];
            $digeststring .= $d['extInstallmentperiod'];
        }

        $digeststring .= trim($d['cssUrl']) .
            trim($d['confirmUrl']) .
            trim($d['cancelUrl']);


        // add extTokenOptions if exists
        if (!empty($d['extTokenOptions'])) {
            $digeststring .= $d['extTokenOptions'];
        }

        // add extToken if exists
        if (!empty($d['extToken'])) {
            $digeststring .= $d['extToken'];
        }

        $digeststring .= trim($merchantpass);

        return base64_encode(hash('sha256', $digeststring, true));
    }


    public function verifyCallback(array $post, string $merchantpass): bool {
        if (empty($post['digest'])) {
            return false;
        }

        $calculated = $this->calculateCallbackDigest($post, $merchantpass);
        return hash_equals($post['digest'], $calculated);
    }

    private function calculateCallbackDigest(array $data, string $merchantpass): string {

        $fields = [
            'version',
            'mid',
            'orderid',
            'status',
            'orderAmount',
            'currency',
            'paymentTotal',
            'message',
            'riskScore',
            'payMethod',
            'txId',
            'paymentRef',
            'extToken',
            'extTokenPanEnd',
            'extTokenExp'
        ];

        $buffer = '';
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $buffer .= $data[$field];
            }
        }

        $buffer .= $merchantpass;
        return base64_encode(hash('sha256', $buffer, true));
    }


    public function getEndpoint() {

        $mode = $this->config->get('payment_cardlink_mode');
        $acquirer = (int)$this->config->get('payment_cardlink_acquirer');

        if ($mode === 'test') {
            return [
                0 => 'https://ecommerce-test.cardlink.gr/vpos/shophandlermpi',
                1 => 'https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi',
                2 => 'https://eurocommerce-test.cardlink.gr/vpos/shophandlermpi',
            ][$acquirer] ?? '';
        }

        return [
            0 => 'https://ecommerce.cardlink.gr/vpos/shophandlermpi',
            1 => 'https://www.alphaecommerce.gr/vpos/shophandlermpi',
            2 => 'https://vpos.eurocommerce.gr/vpos/shophandlermpi',
        ][$acquirer] ?? '';
    }

    private function formatAmount($amount): int {
        return $amount;
        //return (int)round($amount * 100);
    }

    public function restoreCartFromOrder($order_id) {

        $this->load->model('checkout/order');
        $this->load->model('catalog/product');

        // Clear existing cart
        $this->cart->clear();

        // Restore products
        $products = $this->model_checkout_order->getOrderProducts($order_id);

        foreach ($products as $product) {

            $options = [];

            $option_query = $this->db->query("
                SELECT * FROM " . DB_PREFIX . "order_option
                WHERE order_id = '" . (int)$order_id . "'
                AND order_product_id = '" . (int)$product['order_product_id'] . "'
            ");

            foreach ($option_query->rows as $option) {
                $options[$option['product_option_id']] = $option['product_option_value_id'];
            }

            $this->cart->add(
                $product['product_id'],
                $product['quantity'],
                $options
            );
        }

        // Restore vouchers if any
        $voucher_query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "order_voucher
            WHERE order_id = '" . (int)$order_id . "'
        ");

        if ($voucher_query->num_rows) {
            foreach ($voucher_query->rows as $voucher) {
                $this->session->data['vouchers'][] = [
                    'description'      => $voucher['description'],
                    'code'             => $voucher['code'],
                    'to_name'          => $voucher['to_name'],
                    'to_email'         => $voucher['to_email'],
                    'from_name'        => $voucher['from_name'],
                    'from_email'       => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message'          => $voucher['message'],
                    'amount'           => $voucher['amount']
                ];
            }
        }
    }

    public function getSavedCards($customer_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "payment_tokens
            WHERE user_id = '" . (int)$customer_id . "'
        ");
        return $query->rows;
    }

    public function saveToken($customer_id, $token, $pan_end, $card_type) {
        $existing = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "payment_tokens
            WHERE user_id = '" . (int)$customer_id . "'
              AND pan_end = '" . $this->db->escape($pan_end) . "'
              AND card_type = '" . $this->db->escape($card_type) . "'
        ");

        if ($existing->num_rows) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "payment_tokens
                SET token = '" . $this->db->escape($token) . "'
                WHERE id = '" . (int)$existing->row['id'] . "'
            ");
        } else {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "payment_tokens
                SET user_id = '" . (int)$customer_id . "',
                    token = '" . $this->db->escape($token) . "',
                    pan_end = '" . $this->db->escape($pan_end) . "',
                    card_type = '" . $this->db->escape($card_type) . "'
            ");
        }
    }

    public function deleteToken($customer_id, $token): bool {

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "payment_tokens
            WHERE user_id = '" . (int)$customer_id . "'
            AND token = '" . $this->db->escape($token) . "'
        ");

        return $this->db->countAffected() > 0;
    }

    private function ensureTablesExist() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cardlink_transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `cardlink_order_id` varchar(100) NOT NULL,
                `tx_id` varchar(100) DEFAULT NULL,
                `payment_ref` varchar(100) DEFAULT NULL,
                `status` varchar(50) NOT NULL,
                `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
                `currency` varchar(10) NOT NULL DEFAULT 'EUR',
                `pay_method` varchar(50) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
    }

    public function saveTransaction($order_id, $post) {
        $this->ensureTablesExist();
        $cardlink_order_id = $this->db->escape($post['orderid'] ?? '');
        $tx_id             = $this->db->escape($post['txId'] ?? '');
        $payment_ref       = $this->db->escape($post['paymentRef'] ?? '');
        $status            = $this->db->escape($post['status'] ?? '');
        $amount            = (float)($post['orderAmount'] ?? 0);
        $currency          = $this->db->escape($post['currency'] ?? 'EUR');
        $pay_method        = $this->db->escape($post['payMethod'] ?? '');
        $now               = date('Y-m-d H:i:s');

        $existing = $this->db->query("
            SELECT id FROM " . DB_PREFIX . "cardlink_transactions
            WHERE order_id = '" . (int)$order_id . "'
            LIMIT 1
        ");

        if ($existing->num_rows) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cardlink_transactions
                SET cardlink_order_id = '" . $cardlink_order_id . "',
                    tx_id             = '" . $tx_id . "',
                    payment_ref       = '" . $payment_ref . "',
                    status            = '" . $status . "',
                    amount            = '" . $amount . "',
                    currency          = '" . $currency . "',
                    pay_method        = '" . $pay_method . "',
                    updated_at        = '" . $now . "'
                WHERE order_id = '" . (int)$order_id . "'
            ");
        } else {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cardlink_transactions
                SET order_id          = '" . (int)$order_id . "',
                    cardlink_order_id = '" . $cardlink_order_id . "',
                    tx_id             = '" . $tx_id . "',
                    payment_ref       = '" . $payment_ref . "',
                    status            = '" . $status . "',
                    amount            = '" . $amount . "',
                    currency          = '" . $currency . "',
                    pay_method        = '" . $pay_method . "',
                    created_at        = '" . $now . "',
                    updated_at        = '" . $now . "'
            ");
        }
    }

}
