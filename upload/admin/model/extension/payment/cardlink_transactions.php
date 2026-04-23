<?php
class ModelExtensionPaymentCardlinkTransactions extends Model {

    public function getTransactions($order_id = null, $page = 1, $limit = 20) {
        $sql = "SELECT * FROM " . DB_PREFIX . "cardlink_transactions";

        if ($order_id) {
            $sql .= " WHERE order_id = '" . (int)$order_id . "'";
        }

        $sql .= " ORDER BY created_at DESC";

        if ($limit > 0) {
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalTransactions($order_id = null) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cardlink_transactions";

        if ($order_id) {
            $sql .= " WHERE order_id = '" . (int)$order_id . "'";
        }

        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    }

    public function getTransaction($id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cardlink_transactions
            WHERE id = '" . (int)$id . "'
            LIMIT 1
        ");
        return $query->row ?? null;
    }

    public function getTransactionByOrderId($order_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cardlink_transactions
            WHERE order_id = '" . (int)$order_id . "'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        return $query->row ?? null;
    }

    public function updateTransactionStatus($id, $status) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cardlink_transactions
            SET status     = '" . $this->db->escape($status) . "',
                updated_at = '" . date('Y-m-d H:i:s') . "'
            WHERE id = '" . (int)$id . "'
        ");
    }

    public function logAction($order_id, $cardlink_order_id, $action, $amount, $status, $response_json) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cardlink_transaction_log
            SET order_id          = '" . (int)$order_id . "',
                cardlink_order_id = '" . $this->db->escape($cardlink_order_id) . "',
                action            = '" . $this->db->escape($action) . "',
                amount            = '" . (float)$amount . "',
                status            = '" . $this->db->escape($status) . "',
                response          = '" . $this->db->escape($response_json) . "',
                created_at        = '" . date('Y-m-d H:i:s') . "'
        ");
    }

    public function getTransactionLogs($order_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cardlink_transaction_log
            WHERE order_id = '" . (int)$order_id . "'
            ORDER BY created_at DESC
        ");
        return $query->rows;
    }

    public function getSuccessfulCapturedAmount($cardlink_order_id) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM " . DB_PREFIX . "cardlink_transaction_log
            WHERE cardlink_order_id = '" . $this->db->escape($cardlink_order_id) . "'
              AND action IN ('capture', 'partial_capture')
              AND status = 'SUCCESS'
        ");
        return (float)$query->row['total'];
    }

    public function getSuccessfulCaptureEntries($cardlink_order_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cardlink_transaction_log
            WHERE cardlink_order_id = '" . $this->db->escape($cardlink_order_id) . "'
              AND action IN ('capture', 'partial_capture')
              AND status = 'SUCCESS'
            ORDER BY created_at ASC
        ");
        return $query->rows;
    }

    public function getSuccessfulRefundedAmount($cardlink_order_id) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM " . DB_PREFIX . "cardlink_transaction_log
            WHERE cardlink_order_id = '" . $this->db->escape($cardlink_order_id) . "'
              AND action IN ('refund', 'partial_refund')
              AND status = 'SUCCESS'
        ");
        return (float)$query->row['total'];
    }

    public function getSuccessfulVoidedCaptureAmount($cardlink_order_id) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM " . DB_PREFIX . "cardlink_transaction_log
            WHERE cardlink_order_id = '" . $this->db->escape($cardlink_order_id) . "'
              AND action = 'void'
              AND status = 'SUCCESS'
        ");
        return (float)$query->row['total'];
    }
}
