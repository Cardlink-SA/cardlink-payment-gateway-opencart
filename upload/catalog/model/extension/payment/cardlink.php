<?php
class ModelExtensionPaymentCardlink extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/cardlink');

		$method_data = array();

		$method_data = array(
			'code'       => 'cardlink',
			//'title'      => $this->language->get('text_title'),
			'title'      => $this->config->get('payment_cardlink_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_cardlink_sort_order')
		);

		return $method_data;
	}
}
