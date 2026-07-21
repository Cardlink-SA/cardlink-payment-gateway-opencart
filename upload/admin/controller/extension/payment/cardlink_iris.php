<?php
class ControllerExtensionPaymentCardlinkIris extends Controller {
	private $error = [];

	public function index() {
		$this->load->language('extension/payment/cardlink_iris');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/payment/cardlink_iris');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_extension_payment_cardlink_iris->editSetting('payment_cardlink_iris', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect(
				$this->url->link(
					'marketplace/extension',
					'user_token=' . $this->session->data['user_token'] . '&type=payment',
					true
				)
			);
		}

		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['error_title']   = $this->error['title'] ?? '';
		$data['error_merchantid'] = $this->error['merchantid'] ?? '';
		$data['error_merchantpass'] = $this->error['merchantpass'] ?? '';
		$data['logo'] = 'view/image/payment/cardlink_iris.png';

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			],
			[
				'text' => 'Extensions',
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/payment/cardlink_iris', 'user_token=' . $this->session->data['user_token'], true)
			]
		];

		$data['action'] = $this->url->link('extension/payment/cardlink_iris', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$fields = [
			'mode','acquirer','merchantid','merchantpass','title',
			'order_status','failed_order_status','url_success','url_fail','status','sort_order'
		];

		$field_defaults = [
			'payment_cardlink_iris_order_status'        => 2,  // Processing
			'payment_cardlink_iris_failed_order_status' => 10, // Voided (legacy default)
		];

		foreach ($fields as $field) {
			$key = 'payment_cardlink_iris_' . $field;
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} else {
				$value = $this->config->get($key);
				$data[$key] = ($value !== null && $value !== '') ? $value : ($field_defaults[$key] ?? '');
			}
		}

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput(
			$this->load->view('extension/payment/cardlink_iris', $data)
		);
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/cardlink_iris')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['payment_cardlink_iris_title'])) {
			$this->error['title'] = $this->language->get('error_title');
		}

		if (empty($this->request->post['payment_cardlink_iris_merchantid'])) {
			$this->error['merchantid'] = $this->language->get('error_merchantid');
		}

		if (empty($this->request->post['payment_cardlink_iris_merchantpass'])) {
			$this->error['merchantpass'] = $this->language->get('error_merchantpass');
		}

		return !$this->error;
	}
}
