<?php
class ControllerExtensionPaymentCardlink extends Controller {

    public function index() {
        $this->load->language('extension/payment/cardlink');
        $this->load->model('extension/payment/cardlink_common');

        $config = [
            'tokenization' => $this->config->get('payment_cardlink_tokenization'),
            'instalments'  => $this->config->get('payment_cardlink_instalments'),
            'iframe'       => $this->config->get('payment_cardlink_iframe') ?? '0'
        ];

        $data['tokenization_enabled'] = $config['tokenization'];
        $data['customer_logged'] = $this->customer->isLogged();
        $data['saved_cards'] = [];

        if ($this->customer->isLogged()) {
            $data['saved_cards'] =
                $this->model_extension_payment_cardlink_common
                    ->getSavedCards($this->customer->getId());
        }

        $data['installment_options'] =
            $this->getInstallmentOptions(
                $config['instalments'],
                $this->cart->getTotal()
            );

        $data['use_iframe'] = $config['iframe'];

        return $this->load->view('extension/payment/cardlink', $data);
    }

    public function confirm() {
        $this->load->language('extension/payment/cardlink');
        $this->load->model('extension/payment/cardlink_common');
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$order_info) {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        $config = [
            'merchantid'   => $this->config->get('payment_cardlink_merchantid'),
            'merchantpass' => $this->config->get('payment_cardlink_merchantpass'),
            'payMethod'    => $this->config->get('payment_cardlink_payMethod'),
            'trType'       => $this->config->get('payment_cardlink_trtype'),
            'cssUrl'       => $this->config->get('payment_cardlink_url_css'),
            'iframe'       => $this->config->get('payment_cardlink_iframe'),
            'tokenization' => $this->config->get('payment_cardlink_tokenization'),
            'var1'         => $this->config->get('payment_cardlink_var1'),
            'var2'         => $this->config->get('payment_cardlink_var2'),
            'var3'         => $this->config->get('payment_cardlink_var3'),
            'var4'         => $this->config->get('payment_cardlink_var4'),
            'var5'         => $this->config->get('payment_cardlink_var5'),
        ];

        $use_token = false;
        $token = '';

        if ($config['tokenization'] == '1' && $this->customer->isLogged()) {
            if (!empty($this->request->post['saved_card'])) {
                $use_token = true;
                $token = $this->request->post['saved_card'];
            }
        }

        $config['store_card'] = !empty($this->request->post['store_card']) ? '1' : '0';

        $installments = null;
        if ( isset($this->request->post['installments']) && (int)$this->request->post['installments'] > 0 ) {
            $installments = (int)$this->request->post['installments'];
        }

        $data = $this->model_extension_payment_cardlink_common->buildRedirectFormData(
            $config,
            $order_id,
            false,
            $use_token,
            $token,
            $installments
        );

        $data['action'] = $this->model_extension_payment_cardlink_common->getEndpoint();
        $data['iframe'] = $config['iframe'];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function callback() {
        if (isset($this->request->get['session_id'])) {
            $restoreSessionId = $this->request->get['session_id'];

            // Close the empty session that OpenCart created (browser didn't send
            // the cookie on the cross-site POST due to SameSite=Lax).
            session_write_close();

            // Re-open the user's original session.
            session_id($restoreSessionId);
            session_start();

            // Re-sync OpenCart's session object with the restored data.
            // OpenCart's Session class stores/reads data as $_SESSION['data'].
            // Without this step, $this->session->data stays empty and on shutdown
            // OpenCart would overwrite the original session file with empty data.
            if (!empty($_SESSION['data']) && is_array($_SESSION['data'])) {
                $this->session->data = $_SESSION['data'];
            }

            // Override the Set-Cookie header so the browser uses the correct session ID.
            if (!headers_sent()) {
                $cookieName = session_name();
                $params     = session_get_cookie_params();
                $isHttps    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                              || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
                setcookie($cookieName, $restoreSessionId, [
                    'expires'  => $params['lifetime'] ? time() + $params['lifetime'] : 0,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $isHttps,
                    'httponly' => $params['httponly'],
                    'samesite' => $isHttps ? 'None' : 'Lax',
                ]);
            }
        }

        $post = $this->request->post;

        $this->load->model('extension/payment/cardlink_common');
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/cardlink');

        $secret = $this->config->get('payment_cardlink_merchantpass');

        if (!$this->model_extension_payment_cardlink_common->verifyCallback($post, $secret)) {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        $strip_ref = explode("REF", $post['orderid']);
        $order_id = intval($strip_ref[0]);

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        // If session was lost (cross-site POST / SameSite=Lax), re-login the customer
        // using the order's email address — same approach as the proven 1.1.x plugin.
        if (!$this->customer->isLogged() && !empty($order_info['customer_id'])) {
            $this->customer->login($order_info['email'], '', true);
        }

        // store token if exists
        if (!empty($post['extToken']) && $this->customer->isLogged()) {
            $this->model_extension_payment_cardlink_common->saveToken(
                $this->customer->getId(),
                $post['extToken'],
                $post['extTokenPanEnd'] ?? '',
                $post['extTokenExp'] ?? ''
            );
        }

        if ($post['status'] === 'CAPTURED' || $post['status'] === 'AUTHORIZED') {
            if ($post['status'] === 'AUTHORIZED') {
                $status_id = (int)$this->config->get('payment_cardlink_preauth_order_status') ?: (int)$this->config->get('payment_cardlink_order_status');
            } else {
                $status_id = (int)$this->config->get('payment_cardlink_order_status');
            }
            $comment = "Cardlink SUCCESS. TxID: " . ($post['txId'] ?? 'N/A') . " | paymentRef: " . ($post['paymentRef'] ?? 'N/A');
            $url = $this->url->link('checkout/success', '', true);
            $status_message = 'success';
            $notify = true;
            $this->cart->clear();
            unset(
                $this->session->data['shipping_method'],
                $this->session->data['shipping_methods'],
                $this->session->data['payment_method'],
                $this->session->data['payment_methods'],
                $this->session->data['coupon'],
                $this->session->data['reward'],
                $this->session->data['voucher'],
                $this->session->data['vouchers']
            );
            try {
                $this->model_extension_payment_cardlink_common->saveTransaction($order_id, $post);
            } catch (Exception $e) {
                // table may not exist yet — log and continue
                error_log('[Cardlink] saveTransaction failed: ' . $e->getMessage());
            }
        } else {
            $status_id = (int)$this->config->get('payment_cardlink_failed_order_status') ?: 10;
            $comment = "Cardlink FAILED/CANCELLED. status: {$post['status']} | message: " . ($post['message'] ?? 'N/A');
            $this->model_extension_payment_cardlink_common->restoreCartFromOrder($order_id);
            $this->session->data['error'] = $this->language->get('error_declined');
            $url = $this->url->link('checkout/checkout', '', true);
            $status_message = 'fail';
            // Don't notify the customer by email — a bank-side decline or a user-initiated
            // cancel shouldn't generate a "your order failed" message.
            $notify = false;
        }

        $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, $notify);

        $html = '<html><body>
            <script>
                try {
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({ action: "cardlink_close_iframe", status: "'.$status_message.'" }, "*");
                    }
                    window.location.href = "'.$url.'";
                } catch(e){
                    window.location.href = "'.$url.'";
                }
            </script>
        </body></html>';

        $this->response->addHeader('Content-Type: text/html');
        $this->response->setOutput($html);
    }

    private function getInstallmentOptions($string, $total) {
        $options = [];
        $pairs = explode(',', $string);
        foreach ($pairs as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) != 2) continue;

            $min = (float)$parts[0];
            $inst = (int)$parts[1];

            if ($total >= $min) {
                $options[] = [
                    'value' => $inst,
                    'text'  => $inst . ' δόσεις'
                ];
            }
        }
        return $options;
    }

    public function deleteToken() {
        $this->load->language('extension/payment/cardlink');
        $this->load->model('extension/payment/cardlink_common');

        $this->response->addHeader('Content-Type: application/json');

        if (!$this->customer->isLogged()) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Not logged']));
            return;
        }

        if (empty($this->request->post['token'])) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Missing token']));
            return;
        }

        $token = $this->request->post['token'];
        $customer_id = $this->customer->getId();

        $deleted = $this->model_extension_payment_cardlink_common
            ->deleteToken($customer_id, $token);

        $this->response->setOutput(json_encode(['success' => $deleted]));
    }
}
