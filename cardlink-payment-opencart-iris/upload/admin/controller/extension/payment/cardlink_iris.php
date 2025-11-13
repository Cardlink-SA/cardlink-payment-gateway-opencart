<?php
class ControllerExtensionPaymentCardlinkIris extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/cardlink_iris');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		//OC3 + payment_
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			
			$this->model_setting_setting->editSetting('payment_cardlink_iris', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			//OC3 extension -> marketplace, token -> user_token, SSL -> true, type=payment, variables + payment_, remove .tpl
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_test'] = $this->language->get('text_test');
		$data['text_live'] = $this->language->get('text_live');
		
		$data['text_authorize'] = $this->language->get('text_authorize');
		$data['text_capture'] = $this->language->get('text_capture');
		
		$data['text_allowu'] = $this->language->get('text_allowu');
		$data['text_rejectu'] = $this->language->get('text_rejectu');
		
		$data['text_url_gateway'] = $this->language->get('text_url_gateway');
		$data['text_url_success'] = $this->language->get('text_url_success');
		$data['text_url_fail'] = $this->language->get('text_url_fail');
		$data['text_url_css'] = $this->language->get('text_url_css');
		
		
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_successful'] = $this->language->get('text_successful');
		$data['text_declined'] = $this->language->get('text_declined');
		$data['text_off'] = $this->language->get('text_off');
		$data['text_instalments'] = $this->language->get('text_instalments');
		$data['text_url_gateway'] = $this->language->get('text_url_gateway');

		$data['entry_total'] = $this->language->get('entry_total');
		$data['help_total'] = $this->language->get('help_total');
		$data['entry_merchantid'] = $this->language->get('entry_merchantid');
		$data['entry_merchantpass'] = $this->language->get('entry_merchantpass');
		$data['entry_url_gateway'] = $this->language->get('entry_url_gateway');	
		$data['entry_url_success'] = $this->language->get('entry_url_success');
		$data['entry_url_fail'] = $this->language->get('entry_url_fail');
		$data['entry_url_css'] = $this->language->get('entry_url_css');
		$data['entry_trtype'] = $this->language->get('entry_trtype');
		
		$data['entry_testurl'] = $this->language->get('entry_testurl');
		$data['entry_test'] = $this->language->get('entry_test');	
		$data['entry_processed_status'] = $this->language->get('entry_processed_status');		
		$data['entry_failed_status'] = $this->language->get('entry_failed_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_instalments'] = $this->language->get('entry_instalments');
		$data['entry_order_status'] = $this->language->get('entry_order_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchantid'])) {
			$data['error_merchantid'] = $this->error['merchantid'];
		} else {
			$data['error_merchantid'] = '';
		}
		
		if (isset($this->error['merchantpass'])) {
			$data['error_merchantpass'] = $this->error['merchantpass'];
		} else {
			$data['error_merchantpass'] = '';
		}
		
		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = '';
		}
		
		if (isset($this->error['url_gateway'])) {
			$data['error_url_gateway'] = $this->error['url_gateway'];
		} else {
			$data['error_url_gateway'] = '';
		}	
		
		if (isset($this->error['url_success'])) {
			$data['error_url_success'] = $this->error['url_success'];
		} else {
			$data['error_url_success'] = '';
		}
		
		if (isset($this->error['url_fail'])) {
			$data['error_url_fail'] = $this->error['url_fail'];
		} else {
			$data['error_url_fail'] = '';
		}	

		//OC3 correct breadcrumbs
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/payment/cardlink_iris', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/cardlink_iris', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

		if (isset($this->request->post['payment_cardlink_iris_total'])) {
			$data['payment_cardlink_iris_total'] = $this->request->post['payment_cardlink_iris_total'];
		} else {
			$data['payment_cardlink_iris_total'] = $this->config->get('payment_cardlink_iris_total'); 
		}
		
		if (isset($this->request->post['payment_cardlink_iris_merchantid'])) {
			$data['payment_cardlink_iris_merchantid'] = $this->request->post['payment_cardlink_iris_merchantid'];
		} else {
			$data['payment_cardlink_iris_merchantid'] = $this->config->get('payment_cardlink_iris_merchantid');
		}

		if (isset($this->request->post['payment_cardlink_iris_merchantpass'])) {
			$data['payment_cardlink_iris_merchantpass'] = $this->request->post['payment_cardlink_iris_merchantpass'];
		} else {
			$data['payment_cardlink_iris_merchantpass'] = $this->config->get('payment_cardlink_iris_merchantpass');
		}
		
		if (isset($this->request->post['payment_cardlink_iris_title'])) {
			$data['payment_cardlink_iris_title'] = $this->request->post['payment_cardlink_iris_title'];
		} else {
			$data['payment_cardlink_iris_title'] = $this->config->get('payment_cardlink_iris_title');
		}
		
		if (isset($this->request->post['payment_cardlink_iris_trtype'])) {
			$data['payment_cardlink_iris_trtype'] = $this->request->post['payment_cardlink_iris_trtype'];
		} else {
			$data['payment_cardlink_iris_trtype'] = $this->config->get('payment_cardlink_iris_trtype');
		}
		
		if (isset($this->request->post['payment_cardlink_iris_tokenization'])) {
			$data['payment_cardlink_iris_tokenization'] = $this->request->post['payment_cardlink_iris_tokenization'];
		} else {
			$data['payment_cardlink_iris_tokenization'] = $this->config->get('payment_cardlink_iris_tokenization');
		}
		
		if (isset($this->request->post['payment_cardlink_iris_iframe'])) {
			$data['payment_cardlink_iris_iframe'] = $this->request->post['payment_cardlink_iris_iframe'];
		} else {
			$data['payment_cardlink_iris_iframe'] = $this->config->get('payment_cardlink_iris_iframe');
		}
	
		if (isset($this->request->post['payment_cardlink_iris_instalments'])) {
			$data['payment_cardlink_iris_instalments'] = $this->request->post['payment_cardlink_iris_instalments'];
		} else {
			$data['payment_cardlink_iris_instalments'] = $this->config->get('payment_cardlink_iris_instalments');
		}		
	
		if (isset($this->request->post['payment_cardlink_iris_acquirer'])) {
			$data['payment_cardlink_iris_acquirer'] = $this->request->post['payment_cardlink_iris_acquirer'];
		} else {
			$data['payment_cardlink_iris_acquirer'] = $this->config->get('payment_cardlink_iris_acquirer');
		}		

		if (isset($this->request->post['payment_cardlink_iris_mode'])) {
			$data['payment_cardlink_iris_mode'] = $this->request->post['payment_cardlink_iris_mode'];
		} else {
			$data['payment_cardlink_iris_mode'] = $this->config->get('payment_cardlink_iris_mode');
		}		

		if ($data['payment_cardlink_iris_mode'] == 'test'){
			if ($data['payment_cardlink_iris_acquirer'] == 0){
				$data['payment_cardlink_iris_url_gateway'] = 'https://ecommerce-test.cardlink.gr/vpos/shophandlermpi';
			}else if($data['payment_cardlink_iris_acquirer'] == 1){
				$data['payment_cardlink_iris_url_gateway'] = 'https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi';
			}else if($data['payment_cardlink_iris_acquirer'] == 2){
				$data['payment_cardlink_iris_url_gateway'] = 'https://eurocommerce-test.cardlink.gr/vpos/shophandlermpi';
			}
		}else{
			if ($data['payment_cardlink_iris_acquirer'] == 0){
				$data['payment_cardlink_iris_url_gateway'] = 'https://ecommerce.cardlink.gr/vpos/shophandlermpi';
			}else if($data['payment_cardlink_iris_acquirer'] == 1){
				$data['payment_cardlink_iris_url_gateway'] = 'https://www.alphaecommerce.gr/vpos/shophandlermpi';
			}else if($data['payment_cardlink_iris_acquirer'] == 2){
				$data['payment_cardlink_iris_url_gateway'] = 'https://vpos.eurocommerce.gr/vpos/shophandlermpi';
			}
		}
		
		$data['payment_cardlink_iris_url_success'] = HTTP_CATALOG . 'index.php?route=extension/payment/cardlink_iris/callback/success';
		$data['payment_cardlink_iris_url_fail'] 	= HTTP_CATALOG . 'index.php?route=extension/payment/cardlink_iris/callback/fail';

		/* if (isset($this->request->post['payment_cardlink_iris_url_success'])) {
			$data['payment_cardlink_iris_url_success'] = $this->request->post['payment_cardlink_iris_url_success'];
		} else {
			$data['payment_cardlink_iris_url_success'] = $this->config->get('payment_cardlink_iris_url_success');
		}
		
		if (isset($this->request->post['payment_cardlink_iris_url_fail'])) {
			$data['payment_cardlink_iris_url_fail'] = $this->request->post['payment_cardlink_iris_url_fail'];
		} else {
			$data['payment_cardlink_iris_url_fail'] = $this->config->get('payment_cardlink_iris_url_fail');
		} */

		if (isset($this->request->post['payment_cardlink_iris_url_css'])) {
			$data['payment_cardlink_iris_url_css'] = $this->request->post['payment_cardlink_iris_url_css'];
		} else {
			$data['payment_cardlink_iris_url_css'] = $this->config->get('payment_cardlink_iris_url_css');
		}

		/* 
		if (isset($this->request->post['payment_cardlink_iris_processed_status_id'])) {
			$data['payment_cardlink_iris_processed_status_id'] = $this->request->post['payment_cardlink_iris_processed_status_id'];
		} else {
			$data['payment_cardlink_iris_processed_status_id'] = $this->config->get('payment_cardlink_iris_processed_status_id');
		}
		if (!$data['payment_cardlink_iris_processed_status_id']) {
		 	$data['payment_cardlink_iris_processed_status_id'] = 15;  # "Processed"
		 }

		if (isset($this->request->post['payment_cardlink_iris_failed_status_id'])) {
			$data['payment_cardlink_iris_failed_status_id'] = $this->request->post['payment_cardlink_iris_failed_status_id'];
		} else {
			$data['payment_cardlink_iris_failed_status_id'] = $this->config->get('payment_cardlink_iris_failed_status_id');
		}
		if (!$data['payment_cardlink_iris_failed_status_id']) {
		 	$data['payment_cardlink_iris_failed_status_id'] = 10;  # "Failed"
		 }
		*/

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_cardlink_iris_order_status'])) {
			$data['payment_cardlink_iris_order_status'] = $this->request->post['payment_cardlink_iris_order_status'];
		} else {
			$data['payment_cardlink_iris_order_status'] = $this->config->get('payment_cardlink_iris_order_status');
		}

		if (isset($this->request->post['payment_cardlink_iris_geo_zone_id'])) {
			$data['payment_cardlink_iris_geo_zone_id'] = $this->request->post['payment_cardlink_iris_geo_zone_id'];
		} else {
			$data['payment_cardlink_iris_geo_zone_id'] = $this->config->get('payment_cardlink_iris_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_cardlink_iris_status'])) {
			$data['payment_cardlink_iris_status'] = $this->request->post['payment_cardlink_iris_status'];
		} else {
			$data['payment_cardlink_iris_status'] = $this->config->get('payment_cardlink_iris_status');
		}

		if (isset($this->request->post['payment_cardlink_iris_sort_order'])) {
			$data['payment_cardlink_iris_sort_order'] = $this->request->post['payment_cardlink_iris_sort_order'];
		} else {
			$data['payment_cardlink_iris_sort_order'] = $this->config->get('payment_cardlink_iris_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/cardlink_iris', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/cardlink_iris')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_cardlink_iris_merchantid']) {
			$this->error['merchantid'] = $this->language->get('error_merchantid');
		}
		
		if (!$this->request->post['payment_cardlink_iris_merchantpass']) {
			$this->error['merchantpass'] = $this->language->get('error_merchantpass');
		}
		
		if (!$this->request->post['payment_cardlink_iris_title']) {
			$this->error['title'] = $this->language->get('error_title');
		}
		
		if (!$this->request->post['payment_cardlink_iris_url_success']) {
			$this->error['url_success'] = $this->language->get('error_url_success');
		}
		
		if (!$this->request->post['payment_cardlink_iris_url_fail']) {
			$this->error['url_fail'] = $this->language->get('error_url_fail');
		}	
		
		return !$this->error;
	}
}