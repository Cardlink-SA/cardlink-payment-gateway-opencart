<?php
class ControllerExtensionPaymentCardlinkIris extends Controller {

    public function index() {
        $this->load->language('extension/payment/cardlink_iris');
        $this->load->model('extension/payment/cardlink_common');

        $order_id = $this->session->data['order_id'] ?? 0;
        if (!$order_id) {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        $config = [
            'merchantid'   => $this->config->get('payment_cardlink_iris_merchantid'),
            'merchantpass' => $this->config->get('payment_cardlink_iris_merchantpass'),
            'payMethod'    => 'IRIS',
            'trType'       => $this->config->get('payment_cardlink_iris_trType'),
            'iframe'       => $this->config->get('payment_cardlink_iris_iframe'),
            'var1'         => $this->config->get('payment_cardlink_iris_var1'),
            'var2'         => $this->config->get('payment_cardlink_iris_var2'),
            'var3'         => $this->config->get('payment_cardlink_iris_var3'),
            'var4'         => $this->config->get('payment_cardlink_iris_var4'),
            'var5'         => $this->config->get('payment_cardlink_iris_var5'),
            'cssUrl'       => $this->config->get('payment_cardlink_url_css'),
        ];

        // buildRedirectFormData για IRIS (3ο arg true)
        $data = $this->model_extension_payment_cardlink_common
            ->buildRedirectFormData($config, $order_id, true);

        $data['action'] = $this->model_extension_payment_cardlink_common->getEndpoint();
        $data['payment_title'] = $this->config->get('payment_cardlink_iris_title');

        return $this->load->view('extension/payment/cardlink_iris', $data);
    }

    public function confirm() {
        $this->load->language('extension/payment/cardlink_iris');
        $this->load->model('extension/payment/cardlink_common');
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'] ?? 0;
        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        $config = [
            'merchantid'   => $this->config->get('payment_cardlink_iris_merchantid'),
            'merchantpass' => $this->config->get('payment_cardlink_iris_merchantpass'),
            'payMethod'    => 'IRIS',
            'trType'       => $this->config->get('payment_cardlink_iris_trType'),
            'iframe'       => $this->config->get('payment_cardlink_iris_iframe'),
            'cssUrl'       => $this->config->get('payment_cardlink_url_css'),
            'var1'         => $this->config->get('payment_cardlink_iris_var1'),
            'var2'         => $this->config->get('payment_cardlink_iris_var2'),
            'var3'         => $this->config->get('payment_cardlink_iris_var3'),
            'var4'         => $this->config->get('payment_cardlink_iris_var4'),
            'var5'         => $this->config->get('payment_cardlink_iris_var5'),
        ];

        $data = $this->model_extension_payment_cardlink_common
            ->buildRedirectFormData($config, $order_id, true);

        $data['action'] = $this->model_extension_payment_cardlink_common->getEndpoint();
        $data['iframe'] = $config['iframe'];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function callback() {
        // Restore session
        if (isset($this->request->get['session_id'])) {
            $restoreSessionId = $this->request->get['session_id'];
            session_write_close();
            session_id($restoreSessionId);
            session_start();

            if (!empty($_SESSION['data']) && is_array($_SESSION['data'])) {
                $this->session->data = $_SESSION['data'];
            }

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
        $this->load->language('extension/payment/cardlink_iris');

        $secret = $this->config->get('payment_cardlink_iris_merchantpass');

        // verify digest
        if (!$this->model_extension_payment_cardlink_common->verifyCallback($post, $secret)) {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        // extract order_id
        $strip_ref = explode("REF", $post['orderid']);
        $order_id = intval($strip_ref[0]);

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        // If session was lost (cross-site POST / SameSite=Lax), re-login the customer.
        if (!$this->customer->isLogged() && !empty($order_info['customer_id'])) {
            $this->customer->login($order_info['email'], '', true);
        }

        $status = $post['status'] ?? '';

        if ($status === 'CAPTURED' || $status === 'AUTHORIZED') {
            $status_id = (int)$this->config->get('payment_cardlink_iris_order_status');
            $comment = "IRIS SUCCESS. TxID: " . ($post['txId'] ?? 'N/A') . " | paymentRef: " . ($post['paymentRef'] ?? 'N/A');
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
                error_log('[Cardlink IRIS] saveTransaction failed: ' . $e->getMessage());
            }
        } else {
            error_log('[Cardlink IRIS] non-success callback status: ' . ($status ?: 'N/A') . ' | order_id: ' . $order_id);

            $this->model_extension_payment_cardlink_common->restoreCartFromOrder($order_id);
            $this->session->data['error'] = $this->language->get('error_declined');
            $url = $this->url->link('checkout/checkout', '', true);
            $status_message = 'fail';

            if (in_array(strtoupper($status), ['CANCELED', 'CANCELLED'], true)) {
                // The customer clicked "Cancel" at the bank page — this is not a failed
                // payment, just an abandoned attempt. Leave the order's status untouched
                // (no history entry, no email) so they can go back and retry payment or
                // choose a different method on the same order.
                $status_id = null;
                $comment = "IRIS CANCELLED by customer. status: {$status}";
                $notify = false;
            } else {
                // Genuine decline/error from the bank — record it, but still don't email
                // the customer (a declined attempt isn't a final outcome for the order).
                $status_id = (int)$this->config->get('payment_cardlink_iris_failed_order_status') ?: 10;
                $comment = "IRIS FAILED. status: {$status} | message: " . ($post['message'] ?? 'N/A');
                $notify = false;
            }
        }

        if ($status_id !== null) {
            $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, $notify);
        }

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
}
