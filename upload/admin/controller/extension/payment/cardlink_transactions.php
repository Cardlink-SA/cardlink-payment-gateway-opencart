<?php
class ControllerExtensionPaymentCardlinkTransactions extends Controller {

    private function addOrderHistory($order_id, $order_status_id, $comment) {
        $this->db->query("
            UPDATE `" . DB_PREFIX . "order`
            SET order_status_id = '" . (int)$order_status_id . "',
                date_modified = NOW()
            WHERE order_id = '" . (int)$order_id . "'
        ");
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "order_history`
            SET order_id        = '" . (int)$order_id . "',
                order_status_id = '" . (int)$order_status_id . "',
                notify          = '0',
                comment         = '" . $this->db->escape($comment) . "',
                date_added      = NOW()
        ");
    }

    private function loadApi() {
        require_once DIR_SYSTEM . 'library/cardlink_xml_api.php';

        $mode     = $this->config->get('payment_cardlink_mode');
        $acquirer = (int)$this->config->get('payment_cardlink_acquirer');

        $partnerMap = [
            0 => CardlinkXmlApi::PARTNER_CARDLINK,
            1 => CardlinkXmlApi::PARTNER_NEXI,
            2 => CardlinkXmlApi::PARTNER_WORLDLINE,
        ];

        $partner     = $partnerMap[$acquirer] ?? CardlinkXmlApi::PARTNER_CARDLINK;
        $environment = ($mode === 'live')
            ? CardlinkXmlApi::ENV_PRODUCTION
            : CardlinkXmlApi::ENV_SANDBOX;

        $merchantId   = $this->config->get('payment_cardlink_merchantid');
        $sharedSecret = $this->config->get('payment_cardlink_merchantpass');

        return new CardlinkXmlApi($merchantId, $sharedSecret, $partner, $environment);
    }

    public function index() {
        $this->load->language('extension/payment/cardlink_transactions');
        $this->load->model('extension/payment/cardlink_transactions');
        $this->document->setTitle($this->language->get('heading_title'));

        $order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
        $page     = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit    = 20;

        $data['transactions'] = $this->model_extension_payment_cardlink_transactions
            ->getTransactions($order_id ?: null, $page, $limit);

        $total = $this->model_extension_payment_cardlink_transactions
            ->getTotalTransactions($order_id ?: null);

        // Attach secondary logs, same-day flag, and remaining amounts to each transaction
        $today = date('Y-m-d');
        foreach ($data['transactions'] as &$tx) {
            $tx['logs']     = $this->model_extension_payment_cardlink_transactions
                ->getTransactionLogs($tx['order_id']);
            $tx['is_today'] = (substr($tx['updated_at'], 0, 10) === $today);

            $captured = $this->model_extension_payment_cardlink_transactions
                ->getSuccessfulCapturedAmount($tx['cardlink_order_id']);
            $refunded = $this->model_extension_payment_cardlink_transactions
                ->getSuccessfulRefundedAmount($tx['cardlink_order_id']);
            $voided   = $this->model_extension_payment_cardlink_transactions
                ->getSuccessfulVoidedCaptureAmount($tx['cardlink_order_id']);

            // Add back refunded and voided captures so the button reflects the true remaining amount
            $tx['remaining_capture'] = max(0, round((float)$tx['amount'] - $captured + $refunded + $voided, 2));
            // For refunds: if no capture logs (direct sale), full amount was captured
            $capturable             = ($captured > 0) ? $captured : (float)$tx['amount'];
            $tx['remaining_refund'] = max(0, round($capturable - $refunded - $voided, 2));

            // Format amounts for display (2 decimal places)
            $tx['amount']           = number_format((float)$tx['amount'], 2, '.', '');
            $tx['remaining_capture'] = number_format($tx['remaining_capture'], 2, '.', '');
            $tx['remaining_refund']  = number_format($tx['remaining_refund'], 2, '.', '');
            foreach ($tx['logs'] as &$log) {
                $log['amount'] = number_format((float)$log['amount'], 2, '.', '');
            }
            unset($log);
        }
        unset($tx);

        $data['filter_order_id'] = $order_id;
        $data['total']           = $total;
        $data['page']            = $page;
        $data['limit']           = $limit;
        $data['pages']           = $limit > 0 ? ceil($total / $limit) : 1;

        $data['user_token'] = $this->session->data['user_token'];
        $data['index_url'] = $this->url->link(
            'extension/payment/cardlink_transactions',
            'user_token=' . $this->session->data['user_token'],
            true
        );

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ],
            [
                'text' => 'Extensions',
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $data['index_url'],
            ],
        ];

        $data['xml_api_enabled'] = ($this->config->get('payment_cardlink_xml_api') === '1');

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/payment/cardlink_transactions', $data)
        );
    }

    public function capture() {
        $this->load->language('extension/payment/cardlink_transactions');
        $this->load->model('extension/payment/cardlink_transactions');

        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->hasPermission('modify', 'extension/payment/cardlink')) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_permission')]));
            return;
        }

        if ($this->config->get('payment_cardlink_xml_api') !== '1') {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_xml_api_disabled')]));
            return;
        }

        $transaction_id = (int)($this->request->post['transaction_id'] ?? 0);
        $amount         = $this->request->post['amount'] ?? null;

        $tx = $this->model_extension_payment_cardlink_transactions->getTransaction($transaction_id);
        if (!$tx) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Transaction not found']));
            return;
        }

        if (!in_array($tx['status'], ['AUTHORIZED', 'PARTIALLY_CAPTURED'], true)) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Transaction must be AUTHORIZED or PARTIALLY_CAPTURED to capture']));
            return;
        }

        $already_captured = $this->model_extension_payment_cardlink_transactions
            ->getSuccessfulCapturedAmount($tx['cardlink_order_id']);
        $remaining        = round((float)$tx['amount'] - $already_captured, 2);

        $capture_amount = ($amount !== null && $amount !== '') ? round((float)$amount, 2) : $remaining;
        $action_label   = ($capture_amount < $remaining) ? 'partial_capture' : 'capture';

        if ($capture_amount <= 0 || $capture_amount > $remaining) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Invalid amount. Must be between 0.01 and ' . number_format($remaining, 2)]));
            return;
        }

        try {
            $api      = $this->loadApi();
            $response = $api->capture($tx['cardlink_order_id'], $capture_amount, $tx['currency']);
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']']));
            return;
        }

        $log_status = $response->isSuccess() ? 'SUCCESS' : 'FAILED';
        $this->model_extension_payment_cardlink_transactions->logAction(
            $tx['order_id'],
            $tx['cardlink_order_id'],
            $action_label,
            $capture_amount,
            $log_status,
            json_encode($response->getData())
        );

        if ($response->isSuccess()) {
            $new_captured_total = round($already_captured + $capture_amount, 2);
            $new_status = ($new_captured_total >= round((float)$tx['amount'], 2))
                ? 'CAPTURED'
                : 'PARTIALLY_CAPTURED';
            $this->model_extension_payment_cardlink_transactions->updateTransactionStatus($transaction_id, $new_status);

            $comment = 'Cardlink ' . strtoupper($action_label) . '. Amount: ' . number_format($capture_amount, 2) . ' ' . $tx['currency'];
            $this->addOrderHistory($tx['order_id'], 2, $comment);

            $this->response->setOutput(json_encode([
                'success'    => true,
                'message'    => $this->language->get('text_capture_success'),
                'new_status' => $new_status,
            ]));
        } else {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => $response->getError() ?? $this->language->get('error_api_failed'),
            ]));
        }
    }

    public function voidTx() {
        $this->load->language('extension/payment/cardlink_transactions');
        $this->load->model('extension/payment/cardlink_transactions');

        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->hasPermission('modify', 'extension/payment/cardlink')) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_permission')]));
            return;
        }

        if ($this->config->get('payment_cardlink_xml_api') !== '1') {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_xml_api_disabled')]));
            return;
        }

        $transaction_id = (int)($this->request->post['transaction_id'] ?? 0);

        $tx = $this->model_extension_payment_cardlink_transactions->getTransaction($transaction_id);
        if (!$tx) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Transaction not found']));
            return;
        }

        $isSameDay = (substr($tx['updated_at'], 0, 10) === date('Y-m-d'));

        if ($tx['status'] === 'AUTHORIZED') {
            // Pure preauth, nothing captured → CANCEL for the full authorized amount
            $useRefund   = false;
            $void_amount = (float)$tx['amount'];
        } elseif ($tx['status'] === 'CAPTURED') {
            // Full capture: same-day → CANCEL; previous-day (settled) → REFUND
            $useRefund       = !$isSameDay;
            $captured_amount = $this->model_extension_payment_cardlink_transactions
                ->getSuccessfulCapturedAmount($tx['cardlink_order_id']);
            $void_amount = ($captured_amount > 0) ? $captured_amount : (float)$tx['amount'];
        } elseif ($tx['status'] === 'PARTIALLY_CAPTURED') {

            $capture_entries = $this->model_extension_payment_cardlink_transactions
                ->getSuccessfulCaptureEntries($tx['cardlink_order_id']);

            $captured_total = 0.0;
            foreach ($capture_entries as $e) {
                $captured_total += (float)$e['amount'];
            }
            $remaining_auth = round((float)$tx['amount'] - $captured_total, 2);

            try {
                $api = $this->loadApi();
            } catch (\Throwable $e) {
                $this->response->setOutput(json_encode(['success' => false, 'error' => $e->getMessage()]));
                return;
            }

            $ops_success = [];
            $today       = date('Y-m-d');

            // Cancel or refund each captured amount using its own capture OrderId
            // The remaining uncaptured authorization (if any) is left as-is — Cardlink does not support
            // cancelling the uncaptured portion of a partially captured preauth via the XML API.
            foreach ($capture_entries as $entry) {
                $parsed          = json_decode($entry['response'], true) ?: [];
                $capture_orderid = $parsed['OrderId'] ?? $parsed['orderId'] ?? $tx['cardlink_order_id'];
                $capture_amount  = (float)$entry['amount'];
                $is_same_day     = (substr($entry['created_at'], 0, 10) === $today);
                $action_label    = $is_same_day ? 'void' : 'refund';

                try {
                    $resp = $is_same_day
                        ? $api->cancel($capture_orderid, $capture_amount, $tx['currency'])
                        : $api->refund($capture_orderid, $capture_amount, $tx['currency']);
                } catch (\Throwable $e) {
                    $resp = null;
                }

                $log_status = ($resp && $resp->isSuccess()) ? 'SUCCESS' : 'FAILED';
                $this->model_extension_payment_cardlink_transactions->logAction(
                    $tx['order_id'], $capture_orderid, $action_label,
                    $capture_amount, $log_status,
                    $resp ? json_encode($resp->getData()) : json_encode(['error' => 'Exception'])
                );
                $ops_success[] = ($resp && $resp->isSuccess());
            }

            if (empty($ops_success)) {
                $this->response->setOutput(json_encode(['success' => false, 'error' => 'No operations to perform']));
                return;
            }

            $failed_count = count(array_filter($ops_success, fn($v) => !$v));

            if ($failed_count === 0) {
                $this->model_extension_payment_cardlink_transactions
                    ->updateTransactionStatus($transaction_id, 'CANCELED');
                $comment = 'Cardlink VOID (full reversal). Auth released: ' . number_format($remaining_auth, 2)
                    . ', Captured reversed: ' . number_format($captured_total, 2) . ' ' . $tx['currency'];
                $this->addOrderHistory($tx['order_id'], 16, $comment);
                $this->response->setOutput(json_encode([
                    'success'    => true,
                    'message'    => $this->language->get('text_void_success'),
                    'new_status' => 'CANCELED',
                ]));
            } else {
                $total_ops = count($ops_success);
                $this->response->setOutput(json_encode([
                    'success' => false,
                    'error'   => $failed_count . '/' . $total_ops . ' operations failed. Check transaction log for details.',
                ]));
            }
            return;

        } else {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Transaction cannot be voided in its current status']));
            return;
        }

        try {
            $api = $this->loadApi();
            if ($useRefund) {
                $response     = $api->refund($tx['cardlink_order_id'], $void_amount, $tx['currency']);
                $action_label = 'refund';
            } else {
                $response     = $api->cancel($tx['cardlink_order_id'], $void_amount, $tx['currency']);
                $action_label = 'void';
            }
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']']));
            return;
        }

        $log_status = $response->isSuccess() ? 'SUCCESS' : 'FAILED';
        $this->model_extension_payment_cardlink_transactions->logAction(
            $tx['order_id'],
            $tx['cardlink_order_id'],
            $action_label,
            $void_amount,
            $log_status,
            json_encode($response->getData())
        );

        if ($response->isSuccess()) {
            $new_status = $useRefund ? 'REFUNDED' : 'CANCELED';
            $this->model_extension_payment_cardlink_transactions->updateTransactionStatus($transaction_id, $new_status);

            if ($useRefund) {
                $comment         = 'Cardlink REFUND (via void). Amount: ' . number_format($void_amount, 2) . ' ' . $tx['currency'];
                $order_status_id = 11;
            } else {
                $comment         = 'Cardlink VOID (Reverse). Amount: ' . number_format($void_amount, 2) . ' ' . $tx['currency'];
                $order_status_id = 16;
            }
            $this->addOrderHistory($tx['order_id'], $order_status_id, $comment);

            $this->response->setOutput(json_encode([
                'success'    => true,
                'message'    => $this->language->get($useRefund ? 'text_refund_success' : 'text_void_success'),
                'new_status' => $new_status,
            ]));
        } else {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => $response->getError() ?? $this->language->get('error_api_failed'),
            ]));
        }
    }

    public function refund() {
        $this->load->language('extension/payment/cardlink_transactions');
        $this->load->model('extension/payment/cardlink_transactions');

        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->hasPermission('modify', 'extension/payment/cardlink')) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_permission')]));
            return;
        }

        if ($this->config->get('payment_cardlink_xml_api') !== '1') {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_xml_api_disabled')]));
            return;
        }

        $transaction_id = (int)($this->request->post['transaction_id'] ?? 0);
        $type           = $this->request->post['type'] ?? 'full'; // full | partial
        $amount         = $this->request->post['amount'] ?? null;

        $tx = $this->model_extension_payment_cardlink_transactions->getTransaction($transaction_id);
        if (!$tx) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Transaction not found']));
            return;
        }

        if (!in_array($tx['status'], ['CAPTURED', 'PARTIALLY_REFUNDED'], true)) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Transaction must be CAPTURED or PARTIALLY_REFUNDED to refund']));
            return;
        }

        // Same-day refund is not supported on the initial capture — use Void instead.
        // Skip this check for PARTIALLY_REFUNDED: a prior refund already went through.
        if ($tx['status'] === 'CAPTURED' && substr($tx['updated_at'], 0, 10) === date('Y-m-d')) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_refund_same_day')]));
            return;
        }

        // Determine capturable amount: sum of successful captures, or full tx amount for direct sales
        $captured_amount = $this->model_extension_payment_cardlink_transactions
            ->getSuccessfulCapturedAmount($tx['cardlink_order_id']);
        if ($captured_amount <= 0) {
            $captured_amount = (float)$tx['amount'];
        }

        $already_refunded    = $this->model_extension_payment_cardlink_transactions
            ->getSuccessfulRefundedAmount($tx['cardlink_order_id']);
        $remaining_refundable = round($captured_amount - $already_refunded, 2);

        $refund_amount = ($type === 'partial' && $amount !== null && $amount !== '')
            ? round((float)$amount, 2)
            : $remaining_refundable;

        $action_label = ($refund_amount < $remaining_refundable) ? 'partial_refund' : 'refund';

        if ($refund_amount <= 0 || $refund_amount > $remaining_refundable) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => 'Invalid amount. Must be between 0.01 and ' . number_format($remaining_refundable, 2)]));
            return;
        }

        try {
            $api      = $this->loadApi();
            $response = $api->refund($tx['cardlink_order_id'], $refund_amount, $tx['currency']);
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode(['success' => false, 'error' => $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']']));
            return;
        }

        $log_status = $response->isSuccess() ? 'SUCCESS' : 'FAILED';
        $this->model_extension_payment_cardlink_transactions->logAction(
            $tx['order_id'],
            $tx['cardlink_order_id'],
            $action_label,
            $refund_amount,
            $log_status,
            json_encode($response->getData())
        );

        if ($response->isSuccess()) {
            $new_refunded_total = round($already_refunded + $refund_amount, 2);
            $new_status = ($new_refunded_total >= round($captured_amount, 2))
                ? 'REFUNDED'
                : 'PARTIALLY_REFUNDED';
            $this->model_extension_payment_cardlink_transactions->updateTransactionStatus($transaction_id, $new_status);

            $order_status_id = ($new_status === 'PARTIALLY_REFUNDED') ? 2 : 11; // partial → Processing (2), full → Refunded (11)
            $comment = 'Cardlink ' . strtoupper($action_label) . '. Amount: ' . number_format($refund_amount, 2) . ' ' . $tx['currency'];
            $this->addOrderHistory($tx['order_id'], $order_status_id, $comment);

            $this->response->setOutput(json_encode([
                'success'    => true,
                'message'    => $this->language->get('text_refund_success'),
                'new_status' => $new_status,
            ]));
        } else {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => $response->getError() ?? $this->language->get('error_api_failed'),
            ]));
        }
    }
}
