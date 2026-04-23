<?php
class ControllerExtensionPaymentCardlink extends Controller {
	private $error = [];
	
	public function install() {
		$this->load->model('extension/payment/cardlink');
		$this->model_extension_payment_cardlink->install();
		$this->grantTransactionPermissions();
	}

	public function uninstall() {
		$this->load->model('extension/payment/cardlink');
		$this->model_extension_payment_cardlink->uninstall();
	}

	public function index() {
		$this->load->language('extension/payment/cardlink');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/payment/cardlink');
		$this->model_extension_payment_cardlink->maybeUpgrade();
		$this->grantTransactionPermissions();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_extension_payment_cardlink->editSetting('payment_cardlink', $this->request->post);

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
		$catalog_base = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : str_replace('http://', 'https://', HTTP_CATALOG);
		$data['background_confirmation_url'] = $catalog_base . 'index.php?route=extension/payment/cardlink/callback';
		$data['transactions_url'] = $this->url->link(
			'extension/payment/cardlink_transactions',
			'user_token=' . $this->session->data['user_token'],
			true
		);

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
				'href' => $this->url->link('extension/payment/cardlink', 'user_token=' . $this->session->data['user_token'], true)
			]
		];

		$data['action'] = $this->url->link('extension/payment/cardlink', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$fields = [
			'mode','acquirer','merchantid','merchantpass','title','instalments',
			'trtype','tokenization','iframe','order_status','preauth_order_status',
			'xml_api','url_css','url_success','url_fail','status','sort_order'
		];

		$field_defaults = [
			'payment_cardlink_order_status'        => 2,  // Processing
			'payment_cardlink_preauth_order_status' => 1,  // Pending
		];

		foreach ($fields as $field) {
			$key = 'payment_cardlink_' . $field;
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
			$this->load->view('extension/payment/cardlink', $data)
		);
	}

	private function grantTransactionPermissions() {
		$this->load->model('user/user_group');
		$group_id = $this->user->getGroupId();
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/payment/cardlink_transactions');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/payment/cardlink_transactions');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/cardlink')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['payment_cardlink_title'])) {
			$this->error['title'] = $this->language->get('error_title');
		}

		if (empty($this->request->post['payment_cardlink_merchantid'])) {
			$this->error['merchantid'] = $this->language->get('error_merchantid');
		}

		if (empty($this->request->post['payment_cardlink_merchantpass'])) {
			$this->error['merchantpass'] = $this->language->get('error_merchantpass');
		}

		return !$this->error;
	}
}
