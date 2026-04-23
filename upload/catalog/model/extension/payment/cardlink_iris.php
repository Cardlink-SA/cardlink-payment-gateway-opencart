<?php
class ModelExtensionPaymentCardlinkIris extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/cardlink_iris');

        if (!$this->config->get('payment_cardlink_iris_status')) {
            return [];
        }

        $title = $this->config->get('payment_cardlink_iris_title');

        if (!$title) {
            $title = $this->language->get('text_title');
        }

        return [
            'code'       => 'cardlink_iris',
            'title'      => $title,
            'terms'      => '',
            'sort_order' => $this->config->get('payment_cardlink_iris_sort_order')
        ];
    }
}
