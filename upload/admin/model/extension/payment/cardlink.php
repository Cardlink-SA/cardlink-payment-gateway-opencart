<?php
class ModelExtensionPaymentCardlink extends Model {

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payment_tokens` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `token` varchar(255) NOT NULL,
                `user_id` int(11) NOT NULL,
                `card_type` varchar(50) DEFAULT NULL,
                `pan_end` varchar(4) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ");

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cardlink_transaction_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `cardlink_order_id` varchar(100) NOT NULL,
                `action` varchar(50) NOT NULL,
                `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
                `status` varchar(50) NOT NULL,
                `response` text DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payment_tokens`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cardlink_transactions`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cardlink_transaction_log`");
    }

    public function getSetting($code) {
        $query = $this->db->query("
            SELECT * 
            FROM " . DB_PREFIX . "setting 
            WHERE `code` = '" . $this->db->escape($code) . "'
        ");

        $data = [];
        foreach ($query->rows as $result) {
            $data[$result['key']] = $result['value'];
        }

        return $data;
    }

    public function maybeUpgrade() {
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cardlink_transaction_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `cardlink_order_id` varchar(100) NOT NULL,
                `action` varchar(50) NOT NULL,
                `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
                `status` varchar(50) NOT NULL,
                `response` text DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function editSetting($code, $data) {
        $this->db->query("
            DELETE FROM " . DB_PREFIX . "setting 
            WHERE `code` = '" . $this->db->escape($code) . "'
        ");

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $this->db->query("
                INSERT INTO " . DB_PREFIX . "setting 
                SET 
                    `code` = '" . $this->db->escape($code) . "',
                    `key` = '" . $this->db->escape($key) . "',
                    `value` = '" . $this->db->escape($value) . "',
                    `serialized` = '0'
            ");
        }
    }

}
