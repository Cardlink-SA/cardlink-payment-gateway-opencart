<?php
class ModelExtensionPaymentCardlinkIris extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/cardlink_iris');

		$method_data = array();

		$method_data = array(
			'code'       => 'cardlink_iris',
			//'title'      => $this->language->get('text_title'),
			'title'      => $this->config->get('payment_cardlink_iris_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_cardlink_iris_sort_order')
		);

		return $method_data;
	}
}
