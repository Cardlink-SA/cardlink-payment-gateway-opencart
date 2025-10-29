<?php
class ControllerExtensionPaymentCardlinkIris extends Controller {
	public function index() {
		$this->load->language('extension/payment/cardlink_iris');

		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_wait'] = $this->language->get('text_wait');

		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$payment_cardlink_iris_mode = $this->config->get('payment_cardlink_iris_mode');
		$payment_cardlink_iris_acquirer = $this->config->get('payment_cardlink_iris_acquirer');

		if ($payment_cardlink_iris_mode == 'test'){
			if ($payment_cardlink_iris_acquirer == 0){
				$data['action'] = 'https://ecommerce-test.cardlink.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_iris_acquirer == 1){
				$data['action'] = 'https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_iris_acquirer == 2){
				$data['action'] = 'https://eurocommerce-test.cardlink.gr/vpos/shophandlermpi';
			}
		}else{
			if ($payment_cardlink_iris_acquirer == 0){
				$data['action'] = 'https://ecommerce.cardlink.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_iris_acquirer == 1){
				$data['action'] = 'https://www.alphaecommerce.gr/vpos/shophandlermpi';
			}else if($payment_cardlink_iris_acquirer == 2){
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
		
		$ref = "REF".substr(md5(uniqid(rand(), true)), 0, 9);
		$order_id = substr($this->config->get('order_id') . $ref, 0, 50);
		
		if($payment_cardlink_iris_acquirer == 1){
			$trdesc = $this->get_rf_code( $order_id, $order_info['total'] );
		}else{
			$trdesc = 'Opencart order';
			$trdesc = mb_substr($trdesc,0,128,'UTF-8');
		}
		
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

		$confirmUrl = $this->url->link('extension/payment/cardlink_iris/callback/success'. session_id(), '', true);
		$cancelUrl = $this->url->link('extension/payment/cardlink_iris/callback/fail'. session_id(), '', true);
		
		$digeststring = '2'.trim($this->config->get('payment_cardlink_iris_merchantid')).$cardlink_language.'0'.$order_id.$trdesc.$cardlink_total.'EUR'.$order_info['email'].$cphone.
		$order_info['payment_iso_code_2'].
		$billing_state.
		$order_info['payment_postcode'].
		html_entity_decode($order_info['payment_city']).
		html_entity_decode($order_info['payment_address_1']).
		'IRIS'.
		trim($confirmUrl).
		trim($cancelUrl).
		trim($this->config->get('payment_cardlink_iris_merchantpass'));

		$digested = base64_encode(hash('sha256', $digeststring,true));
		
		$data['version'] = '2';
		$data['mid'] =  trim($this->config->get('payment_cardlink_iris_merchantid'));
		$data['lang'] = $cardlink_language;
		$data['deviceCategory'] = '0';
		$data['orderid'] = $order_id;
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
		$data['payMethod'] = 'IRIS';
		$data['confirmUrl'] = $confirmUrl;
		$data['cancelUrl']  = $cancelUrl;
		$data['digest'] = $digested;
		/* 
		$json['mooidval'] = $order_id;
		$json['mopaytypeval'] = 'IRIS';
		$json['modigestval'] = $digested;
		$this->response->setOutput(json_encode($json));
 		*/
		return $this->load->view('extension/payment/cardlink_iris', $data);
	}

	public function get_rf_code( $order_id, $order_total ) {
/* 		$rf_payment_code = get_post_meta( $order_id, 'rf_payment_code', true );
		if ( $rf_payment_code !== '' ) {
		   return $rf_payment_code;
		} */

		/* calculate payment check code */
		$paymentSum = 0;
		if ( $order_total > 0 ) {
		   $ordertotal = str_replace( [ ',' ], '.', (string) $order_total );
		   $ordertotal = number_format( $ordertotal, 2, '', '' );
		   $ordertotal = strrev( $ordertotal );
		   $factor     = [ 1, 7, 3 ];
		   $idx        = 0;
		   for ( $i = 0; $i < strlen( $ordertotal ); $i ++ ) {
			  $idx        = $idx <= 2 ? $idx : 0;
			  $paymentSum += $ordertotal[ $i ] * $factor[ $idx ];
			  $idx ++;
		   }
		}
		$randomNumber 	 = $this->generateRandomString( 13, time() );
		$paymentCode  	 = $paymentSum ? ( $paymentSum % 8 ) : '8';
		$systemCode   	 = '12';
		$tempCode     	 = $this->config->get('payment_cardlink_iris_rf_payment_code') . $paymentCode . $systemCode . $randomNumber . '271500';		
		$mod97        	 = bcmod( $tempCode, '97' );
		$cd           	 = 98 - (int) $mod97;
		$cd              = str_pad( (string) $cd, 2, '0', STR_PAD_LEFT );
		$rf_payment_code = 'RF' . $cd . $this->config->get('payment_cardlink_iris_rf_payment_code') . $paymentCode . $systemCode . $randomNumber;
		
		return $rf_payment_code;
	}

	public function generateRandomString( $length = 22, $order_id = 0 ) {
		return str_pad( $order_id, $length, '0', STR_PAD_LEFT );
	}
	
	public function callback() {
		$this->load->language('extension/payment/cardlink_iris');

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
			$emp_shared = trim($this->config->get('payment_cardlink_iris_merchantpass'));
	  
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
				//$data['continue'] = $this->url->link('checkout/cart');

				if (isset($this->request->get['session_id'])) {
					session_write_close();
					session_id($this->request->get['session_id']);
					session_start();
				}
				$this->session->data['error'] = $this->language->get('error_declined');
				$this->response->redirect($this->url->link('checkout/cart', '', true));
				return;

				$this->response->setOutput($this->load->view('extension/payment/cardlink_iris_failure', $data));

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
			$xlsbonusdigest = '';
			if( array_key_exists( 'xlsbonusdigest', $_POST ) ){
				isset($_POST['xlsbonusdigest']) ? $xlsbonusdigest = $_POST['xlsbonusdigest'] : $xlsbonusdigest = '';
			}
			$emp_shared = trim($this->config->get('payment_cardlink_iris_merchantpass'));

			$extTokenExpYear  = substr( $emp_extTokenExp, 0, 4 );
			$extTokenExpMonth = substr( $emp_extTokenExp, 4, 2 );
/* 
			$emp_form_data = '';
			foreach ( $_POST as $k => $v ) {
				if ( ! in_array( $k, array( 'digest' ) ) ) {
					$emp_form_data .= $v;
				}
			}
			$emp_form_data .= $emp_shared;
			$emp_digested = base64_encode(hash('sha256', $emp_form_data,true));
 */


			$emp_form_data = '';
			$emp_form_data_bonus = '';
			foreach ( $_POST as $k => $v ) {
				if ( ! in_array( $k, array( '_charset_', 'digest', 'submitButton', 'xlsbonusadjamt', 'xlsbonustxid', 'xlsbonusstatus', 'xlsbonusdetails', 'xlsbonusdigest' ) ) ) {
					$emp_form_data .= $v;
				}
				if ( in_array( $k, array( 'xlsbonusadjamt', 'xlsbonustxid', 'xlsbonusstatus', 'xlsbonusdetails' ) ) ) {
					$emp_form_data_bonus .= $v;
				}
			}		

			$emp_form_data 			.= $emp_shared;
			$emp_digested 			= base64_encode(hash('sha256', $emp_form_data,true));
			$emp_form_data_bonus 	.= $emp_shared;
			$emp_digested_bonus 	= base64_encode(hash('sha256', $emp_form_data_bonus,true));

			$failed = true;
			if ( $emp_digest == $emp_digested ){
				$failed = false;
			}
			if( $xlsbonusdigest != '' ){
				if ( $xlsbonusdigest == $emp_digested_bonus ){
					$failed = false;
				}else{
					$failed = true;
				}
			}


			//if(($emp_digested == $emp_digest) && ($emp_status=='AUTHORIZED' || $emp_status=='CAPTURED')){
			if( !$failed && ($emp_status=='AUTHORIZED' || $emp_status=='CAPTURED') ){
		
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
				$message .= 'CARDLINK ECOMMERCE' . "\n";

				$order_status_id = $this->config->get('payment_cardlink_iris_order_status');

				$this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $message, true);
			
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
				$this->response->setOutput($this->load->view('extension/payment/cardlink_iris_response', $data));

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
		$order_obj = $this->model_checkout_order->getOrder($order_id); // use the desired $order_Id here
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
		$data_string .= trim($this->config->get('payment_cardlink_iris_merchantpass'));
		
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