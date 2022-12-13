<?php
class ControllerExtensionPaymentCardlink extends Controller {
	public function index() {
		$this->load->language('extension/payment/cardlink');

		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_wait'] = $this->language->get('text_wait');
		$data['text_paytype'] = $this->language->get('text_paytype');
		$data['text_paytype_card'] = $this->language->get('text_paytype_card');
		$data['text_paytype_masterpass'] = $this->language->get('text_paytype_masterpass');
		$data['text_instalments'] = $this->language->get('text_instalments');
		$data['text_instalments_nr'] = $this->language->get('text_instalments_nr');
		$data['text_instalments_no'] = $this->language->get('text_instalments_no');
		$data['text_store_card'] = $this->language->get('text_store_card');
		$data['text_new_card'] = $this->language->get('text_new_card');

		$use_iframe = false;
		$html_modal = '';
		if( $this->config->get('payment_cardlink_iframe') ){
			$use_iframe = true;

			$html_modal .= '<div class="cardlink_payment_gateway_woocommerce_modal">';
			$html_modal .= '<div class="cardlink_payment_gateway_woocommerce_modal_wrapper">';
			$html_modal .= '<iframe name="payment_iframe" id="payment_iframe" src="" frameBorder="0"></iframe>';
			$html_modal .= '</div>';
			$html_modal .= '</div>';
		}
		$data['target'] = $use_iframe ? 'payment_iframe' : 'top';
		$data['html_modal'] = $html_modal;
		$data['use_iframe'] = $use_iframe;

		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$payment_cardlink_mode = $this->config->get('payment_cardlink_mode');
		$payment_cardlink_acquirer = $this->config->get('payment_cardlink_acquirer');

		if ($payment_cardlink_mode == 'test'){
			if ($payment_cardlink_acquirer == 0){
				$data['action'] = 'https://ecommerce-test.cardlink.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_acquirer == 1){
				$data['action'] = 'https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_acquirer == 2){
				$data['action'] = 'https://eurocommerce-test.cardlink.gr/vpos/shophandlermpi';
			}
		}else{
			if ($payment_cardlink_acquirer == 0){
				$data['action'] = 'https://ecommerce.cardlink.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_acquirer == 1){
				$data['action'] = 'https://www.alphaecommerce.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_acquirer == 2){
				$data['action'] = 'https://vpos.eurocommerce.gr/vpos/shophandlermpi';
			}
		}

		$TmSecureKey = 'd2ViaXQuYnovbGljZW5zZS50eHQ='; // for extra encryption options
		if(strtoupper($this->language->get('code')) == 'GR' || strtoupper($this->language->get('code')) == 'EL'){
			$cardlink_language = 'el';
		} else {
			$cardlink_language = 'en';
		}

		//set order total to Euro
		//$cardlink_total_eur = $this->currency->format($order_info['total'], 'EUR', '',false);
		$cardlink_total_eur = $order_info['total'];
		$cardlink_total = number_format($cardlink_total_eur, 2, ',', '');	
		
		$paytype_cardlink = '';
		
		if(isset($this->request->post['Paytype']) && $this->request->post['Paytype'] != ''){
			$this->session->data['emppaytype'] = $this->request->post['Paytype'];
		} else {
			$this->session->data['emppaytype'] = '';
		}
		
		$instal_cardlink = '';
		$instal_logic = trim($this->config->get('payment_cardlink_instalments'));
		if(isset($instal_logic) && $instal_logic !=''){
			$split_instal_cardlink = explode(',', $instal_logic);
			$c = count ($split_instal_cardlink);
				
			$instal_cardlink .= '<strong>'.$data['text_instalments'] . '</strong><select name="Installments" id="cardlink_instalments">'."\n";
			$instal_cardlink .= '<option value="0">'.$data['text_instalments_no'].'</option>'."\n";
		
			for($i=0; $i<$c; $i++){
				list($instal_amount, $instal_term) = explode(":", $split_instal_cardlink[$i]);
				if( ($cardlink_total_eur >= $instal_amount) && ($instal_term <= 60) ){
					$instal_cardlink .= '<option value="'.$instal_term.'">'. $instal_term . $data['text_instalments_nr'].'</option>'."\n"; 
				}
			}
			$instal_cardlink .= '</select><br><br>'."\n";
		} 

		if(isset($this->request->post['Installments']) && $this->request->post['Installments'] > 0){
			$this->session->data['empinstal'] = $this->request->post['Installments'];
			$this->session->data['empoffset'] = '0'; 
		} else {
			$this->session->data['empinstal'] = '';
			$this->session->data['empoffset'] = ''; 
		}


//----------start tokens HTML---------

		$html_output        = '';
		$html_cards        = '';
		$icon = '';
		if ( $this->customer->isLogged() && $this->config->get('payment_cardlink_tokenization') ) {
			$tokens = $this->db->query("SELECT * FROM `" . DB_PREFIX . "payment_tokens` WHERE user_id = '" . (int)$this->customer->getId() . "'");

			$html_output .= '<div class="payment-cards__tokens">';
			if ( ! empty( $tokens->rows ) ) {
				foreach ( $tokens->rows as $key => $tok ) {
					if ( $tok['card_type'] == 'mastercard' ) {
						$icon = '<img src="catalog/view/theme/default/image/mastercard.png" alt="mastercard">';
					} elseif ( $tok['card_type'] == 'visa' ) {
						$icon = '<img src="catalog/view/theme/default/image/visa.png" alt="visa">';
					} else {
						$icon = $tok['card_type'];
					}

					$html_cards .= '<div class="payment-cards__field radio">';
					$html_cards .= '<label for="card-' . $key . '" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
								<input type="radio" id="card-' . $key . '" name="cardlink-payment-card" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" value="' . $tok['token'] . '"><span>' .
					         $icon . ' ************' . $tok['pan_end'] . ' ' . $tok['expiry_month'] . '/' . $tok['expiry_year'] .
					         '</span><a href="#" title="Remove card" class="remove" aria-label="Remove card">×</a>' .
					         '</label>';
					$html_cards .= '</div>';
				}
			}

			if ( $html_cards !== "" ) {
				$html_output .= '<div class="payment-cards">';
				$html_output .= $html_cards;
				$html_output .= '<div class="payment-cards__field radio">';
				$html_output .= '<label for="new-card" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input type="radio" id="new-card" name="cardlink-payment-card" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" value="new"><span>'. $data['text_new_card'] .'</span></label>';
				$html_output .= '</div>';
				$html_output .= '</div>';
				$html_output .= '<div class="payment-cards-new-card payment-cards__field checkbox" style="display:none">';
			} else {
				$html_output .= '<div class="payment-cards-new-card payment-cards__field checkbox">';
			}
			$html_output .= '<label for="cardlink-payment-card-store"><input type="checkbox" id="cardlink-payment-card-store" name="cardlink-payment-card-store"><span>'. $data['text_store_card'] .'</span></label>';
			$html_output .= '</div>';
			$html_output .= '</div>';
		}

		$data['html_output'] = $html_output;

//----------end tokens---------

		
		$ref = "REF".substr(md5(uniqid(rand(), true)), 0, 9);
		$orderid = substr($this->session->data['order_id'] . $ref, 0, 50);
		
		
		$trdesc = 'Opencart order';
		$trdesc = mb_substr($trdesc,0,128,'UTF-8');
		
		if(!isset($order_info['payment_iso_code_2']) || $order_info['payment_iso_code_2']==''){
		 	$order_info['payment_iso_code_2']='GR';
		}
		
		$cphone = isset($order_info['telephone']) ? $order_info['telephone'] : '';
		if(isset($order_info['payment_iso_code_2']) && $order_info['payment_iso_code_2']!='GR' && isset($order_info['payment_zone_code']) && !is_numeric($order_info['payment_zone_code'])){
		 	$billing_state =substr($order_info['payment_zone_code'],0,2);
		} else {
		 	$billing_state = '';
		}
		$billing_state = '';

		$confirmUrl = $this->config->get('config_ssl') . 'index.php?route=extension/payment/cardlink/callback/success';
		$cancelUrl = $this->config->get('config_ssl') . 'index.php?route=extension/payment/cardlink/callback/fail';
		
		$digeststring = '2'.trim($this->config->get('payment_cardlink_merchantid')).$cardlink_language.'0'.$orderid.$trdesc.$cardlink_total.'EUR'.$order_info['email'].$cphone.
		$order_info['payment_iso_code_2'].
		$billing_state.
		$order_info['payment_postcode'].
		html_entity_decode($order_info['payment_city']).
		html_entity_decode($order_info['payment_address_1']).
		$this->session->data['emppaytype'].
		$this->config->get('payment_cardlink_trtype').
		$this->session->data['empoffset'].
		$this->session->data['empinstal'].
		trim($this->config->get('payment_cardlink_url_css')).
		trim($confirmUrl).
		trim($cancelUrl).
		trim($this->config->get('payment_cardlink_merchantpass'));

		$digested = base64_encode(hash('sha256', $digeststring,true));
		
		$data['version'] = '2';
		$data['mid'] =  trim($this->config->get('payment_cardlink_merchantid'));
		$data['lang'] = $cardlink_language;
		$data['deviceCategory'] = '0';
		$data['orderid'] = $orderid;
		$data['orderDesc'] = $trdesc;
		$data['orderAmount'] = $cardlink_total;
		$data['currency'] = 'EUR';
		$data['payerEmail'] = $order_info['email'];
		$data['payerPhone'] = $cphone;
		$data['billCountry'] = $order_info['payment_iso_code_2'];
		$data['billState'] = $billing_state;
		$data['billZip'] = $order_info['payment_postcode'];
		$data['billCity'] = html_entity_decode($order_info['payment_city']);
		$data['billAddress'] = html_entity_decode($order_info['payment_address_1']);
		$data['shipCountry'] = '';
		$data['shipState'] = '';
		$data['shipZip'] = '';
		$data['shipCity'] = '';
		$data['shipAddress'] = '';
		$data['payMethod'] = $this->session->data['emppaytype'];
		$data['trType'] = $this->config->get('payment_cardlink_trtype');
		$data['extInstallmentoffset'] = $this->session->data['empoffset'];
		$data['extInstallmentperiod'] = $this->session->data['empinstal'];
		$data['extRecurringfrequency'] = '';
		$data['extRecurringenddate'] = '';
		$data['cssUrl'] = trim($this->config->get('payment_cardlink_url_css'));
		$data['confirmUrl'] = $confirmUrl;
		$data['cancelUrl']  = $cancelUrl;
		$data['extTokenOptions'] = '';
		$data['extToken'] = '';
		$data['var1'] = '';
		$data['var2'] = '';
		$data['var3'] = '';
		$data['var4'] = '';
		$data['var5'] = '';
		$data['digest'] = $digested;
		$data['cardlink_installments'] = $instal_cardlink;
		$data['cardlink_paytype'] = $paytype_cardlink;
		
		$json['mooidval'] = $orderid;
		$json['mopaytypeval'] = $this->session->data['emppaytype'];
		$json['moinstalval'] = $this->session->data['empinstal'];
		$json['mooffsetval'] = $this->session->data['empoffset'];
		$json['modigestval'] = $digested;
		$this->response->setOutput(json_encode($json));

		return $this->load->view('extension/payment/cardlink', $data);
	}
	
	public function callback() {
		$this->load->language('extension/payment/cardlink');

		$data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

		if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
			$data['base'] = $this->config->get('config_url');
		} else {
			$data['base'] = $this->config->get('config_ssl');
		}

		$data['language'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');
		$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
		$data['text_failure'] = $this->language->get('text_failure');
		$data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/checkout', '', 'SSL'));

		$order_id = 0;

		//fail
		if(preg_match("/fail/i", $_SERVER['REQUEST_URI'])) {
		
			isset($_POST['mid']) ? $emp_mid = $_POST['mid'] : $emp_mid = '';
			isset($_POST['orderid']) ? $emp_orderid = $_POST['orderid'] : $emp_orderid = '';
			isset($_POST['status']) ? $emp_status = $_POST['status'] : $emp_status = '';
			isset($_POST['orderAmount']) ? $emp_orderAmount = $_POST['orderAmount'] : $emp_orderAmount = '';
			isset($_POST['currency']) ? $emp_currency = stripslashes($_POST['currency']) : $emp_currency = '';
			isset($_POST['paymentTotal']) ? $emp_paymentTotal = $_POST['paymentTotal'] : $emp_paymentTotal = '';
			isset($_POST['riskScore']) ? $emp_riskScore = $_POST['riskScore'] : $emp_riskScore = '';
			isset($_POST['txId']) ? $emp_txId = stripslashes($_POST['txId']) : $emp_txId = '';
			isset($_POST['paymentRef']) ? $emp_paymentRef = $_POST['paymentRef'] : $emp_paymentRef = '';
			isset($_POST['digest']) ? $emp_digest = $_POST['digest'] : $emp_digest = '';
			$emp_shared = trim($this->config->get('payment_cardlink_merchantpass'));
	  
	  		if(isset($emp_mid) && $emp_mid !='') {
		
				if (isset($emp_orderid)) {
					$strip_ref = explode("REF",$emp_orderid);
					$emp_orderid = $strip_ref[0];
					$order_id = intval($emp_orderid);
				} else {
					$order_id = 0;
				}
				
				$this->load->model('checkout/order');				
				$order_info = $this->model_checkout_order->getOrder($order_id);
				
				if(!$this->customer->isLogged() && $order_info['customer_id']!=0){
					$email_query = $this->db->query("SELECT email FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
					if ($email_query->num_rows){
						$this->customer->login($email_query->row['email'], '', true);
					}
				}
				
				/*
				$this->session->data['error'] = $this->language->get('error_declined');
				$this->redirect((isset($this->session->data['guest'])) ? $this->url->link('checkout/cart', '', 'SSL') : $this->url->link('checkout/cart', '', 'SSL')); 
				*/
				$data['continue'] = $this->url->link('checkout/cart');

				$this->response->setOutput($this->load->view('extension/payment/cardlink_failure', $data));

			}
	  	}//end fail
	  
		//success
		elseif(preg_match("/success/i", $_SERVER['REQUEST_URI'])) {
			
			isset($_POST['version']) ? $emp_version = $_POST['version'] : $emp_version = '';
			isset($_POST['mid']) ? $emp_mid = $_POST['mid'] : $emp_mid = '';
			isset($_POST['orderid']) ? $emp_orderid = $_POST['orderid'] : $emp_orderid = '';
			isset($_POST['status']) ? $emp_status = $_POST['status'] : $emp_status = '';
			isset($_POST['orderAmount']) ? $emp_orderAmount = $_POST['orderAmount'] : $emp_orderAmount = '';
			isset($_POST['currency']) ? $emp_currency = stripslashes($_POST['currency']) : $emp_currency = '';
			isset($_POST['paymentTotal']) ? $emp_paymentTotal = $_POST['paymentTotal'] : $emp_paymentTotal = '';
			isset($_POST['message']) ? $emp_message = $_POST['message'] : $emp_message = '';
			isset($_POST['riskScore']) ? $emp_riskScore = $_POST['riskScore'] : $emp_riskScore = '';
			isset($_POST['payMethod']) ? $emp_payMethod = $_POST['payMethod'] : $emp_payMethod = '';
			isset($_POST['txId']) ? $emp_txId = stripslashes($_POST['txId']) : $emp_txId = '';
			isset($_POST['paymentRef']) ? $emp_paymentRef = $_POST['paymentRef'] : $emp_paymentRef = '';
			isset($_POST['extToken']) ? $emp_extToken = $_POST['extToken'] : $emp_extToken = '';
			isset($_POST['extTokenPanEnd']) ? $emp_extTokenPanEnd = $_POST['extTokenPanEnd'] : $emp_extTokenPanEnd = '';
			isset($_POST['extTokenExp']) ? $emp_extTokenExp = $_POST['extTokenExp'] : $emp_extTokenExp = '';
			isset($_POST['digest']) ? $emp_digest = $_POST['digest'] : $emp_digest = '';
			$emp_shared = trim($this->config->get('payment_cardlink_merchantpass'));

			$extTokenExpYear  = substr( $emp_extTokenExp, 0, 4 );
			$extTokenExpMonth = substr( $emp_extTokenExp, 4, 2 );

			$emp_form_data = '';
			foreach ( $_POST as $k => $v ) {
				if ( ! in_array( $k, array( 'digest' ) ) ) {
					$emp_form_data .= $v;
				}
			}
			$emp_form_data .= $emp_shared;

			$emp_digested = base64_encode(hash('sha256', $emp_form_data,true));

			if(($emp_digested == $emp_digest) && ($emp_status=='AUTHORIZED' || $emp_status=='CAPTURED')){
		
				if (isset($emp_orderid)) {
					$strip_ref = explode("REF",$emp_orderid);
					$emp_orderid = $strip_ref[0];
					$order_id = intval($emp_orderid);
				} else {
					$order_id = 0;
				}
				
				$this->load->model('checkout/order');				
				$order_info = $this->model_checkout_order->getOrder($order_id);
				
				$message = '';
				if (isset($this->request->post['status'])) {
					$message .= 'Status: ' . $this->request->post['status'] . "\n";
				}
				if (isset($this->request->post['txId'])) {
					$message .= 'TxID: ' . $this->request->post['txId'] . "\n";
				}
				if (isset($this->request->post['paymentTotal'])) {
					$message .= 'Amount: ' . number_format($this->request->post['paymentTotal'], 2, ',', ' ') . "\n";
				}
				if (isset($this->request->post['currency'])) {
					$message .= 'Currency: ' . $this->request->post['currency'] . "\n";
				}
				if (isset($this->request->post['payMethod'])) {
					$message .= 'PayMethod: ' . $this->request->post['payMethod'] . "\n";
				}
				if (isset($this->request->post['paymentRef'])) {
					$message .= 'PaymentRef: ' . $this->request->post['paymentRef'] . "\n";
				}
				$message .= 'E.C. Electronic Commerce' . "\n";
				
				$this->model_checkout_order->addOrderHistory($order_id, 15, $message, true);
			
				if(!$this->customer->isLogged() && $order_info['customer_id']!=0){
					$email_query = $this->db->query("SELECT email FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
					if ($email_query->num_rows){
						$this->customer->login($email_query->row['email'], '', true);
						if ($this->customer->getId()) {
							$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE customer_id = '" . (int)$this->customer->getId() . "'");
						} 
					}
				}
			
				//$this->response->redirect($this->url->link('checkout/success'));
			
				$data['continue'] = $this->url->link('checkout/success');
				$data['text_title'] = $this->language->get('text_success');
				$data['text_response'] = preg_replace('/\\n/m','<br />',$message);
				$data['text_link'] = sprintf($this->language->get('text_returnlink'), $this->url->link('checkout/success', '', true));
				$this->response->setOutput($this->load->view('extension/payment/cardlink_response', $data));

				//save card to DB ( INSERT INTO dogs(id, name, gender) VALUES (1, 'AXEL'); )
				//----------if card not exist
				$tokens = $this->db->query("SELECT * FROM `" . DB_PREFIX . "payment_tokens` WHERE user_id = '" . (int)$this->customer->getId() . "'");
				$card_exist  = false;
				foreach ( $tokens->rows as $key => $tok ) {
					if ( $tok['card_type'] == $emp_payMethod && $tok['pan_end'] == $emp_extTokenPanEnd && $tok['expiry_year'] == $extTokenExpYear && $tok['expiry_month'] == $extTokenExpMonth ) {
						$card_exist = true;
					}
				}

				if( $emp_extToken && !$card_exist ){
					$save_card_query = $this->db->query(" INSERT INTO " . DB_PREFIX . "payment_tokens SET token = '" . $emp_extToken . "',  user_id = '" . (int)$this->customer->getId() . "', card_type = '" . $emp_payMethod . "', pan_end = '" . (int)$emp_extTokenPanEnd . "', expiry_year = '" . (int)$extTokenExpYear . "', expiry_month = '" . (int)$extTokenExpMonth . "' ");
				}

			}
		}//end success	 

		//-------------update redirect meta false
		if( $order_id ){
			$this->db->query("UPDATE " . DB_PREFIX . "order SET redirected = '" . 0 . "' WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("UPDATE " . DB_PREFIX . "order SET redirect_url = '" . $data['continue'] . "' WHERE order_id = '" . (int)$order_id . "'");
		}

	}//end callback


	public function getOrderStatus() {
		$json = array( 
			'status' => true,
			'response' => null
		);

		$order_id = $this->request->post['order_id'];

		/* $this->load->model('checkout/order'); // call this only if this model is not yet instantiated!
		$order_obj = $this->model_checkout_order->getOrder($order_id); // use the desired $orderId here
		$order_status = $order_obj['order_status']; */

		//--------------------create redirect meta true
		$this->db->query("UPDATE " . DB_PREFIX . "order SET redirected = '" . 1 . "' WHERE order_id = '" . (int)$order_id . "'");
		

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function checkOrderStatus() {
		$json = array( 
			'redirected' => true,
			'redirect_url' => null,
		);

		$order_id = $this->request->post['order_id'];
		
		//---------select query redirect meta value
		$query = $this->db->query("SELECT redirected FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		$json['redirected'] = $query->row["redirected"] == 1 ? true : false;

		$query = $this->db->query("SELECT redirect_url FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		$json['redirect_url'] = $query->row["redirect_url"];
		

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	public function calculateDigest() {
		$json = array( 
			'digest' => null,
		);

		$data = [];
		$data_fields = $this->request->post['data_fields'];
		$ext = $this->request->post['ext'];
		$token = $this->request->post['token'];

		unset($data_fields['extTokenOptions']);
		unset($data_fields['extToken']);
		unset($data_fields['digest']);
		$data_string = '';
		foreach ($data_fields as $key => $value) {
			$data_string .= $value;
		}
		$data_string .= $ext;
		$data_string .= $token;
		$data_string .= trim($this->config->get('payment_cardlink_merchantpass'));
		
		$json['digest'] = base64_encode(hash('sha256', $data_string,true));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function deletePaymentCard() {
		$json = array( 
			'status' => null,
		);

		$selected_card_value = $this->request->post['selected_card_value'];

		$this->db->query("DELETE FROM `" . DB_PREFIX . "payment_tokens` WHERE token = '" . (int)$selected_card_value . "'");

		//$json['cards_html'] = $selected_card_value;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}




}//end class