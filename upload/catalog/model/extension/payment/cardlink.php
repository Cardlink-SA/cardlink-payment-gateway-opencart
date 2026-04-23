<?php
class ModelExtensionPaymentCardlink extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/cardlink');

        if (!$this->config->get('payment_cardlink_status')) {
            return [];
        }

        $title = $this->config->get('payment_cardlink_title');

        if (!$title) {
            $title = $this->language->get('text_title');
        }

        return [
            'code'       => 'cardlink',
            'title'      => $title,
            'terms'      => '',
            'sort_order' => $this->config->get('payment_cardlink_sort_order')
        ];
    }
}
