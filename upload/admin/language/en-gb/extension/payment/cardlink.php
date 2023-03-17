<?php
// Heading
$_['heading_title']      = 'Cardlink';

// Text
$_['text_payment']       = 'Payment';
$_['text_success']       = 'Cardlink settings modified successfully';
$_['text_test']			 = 'Test mode';
$_['text_live']      	 = 'Live mode';
$_['text_yes']			 = 'Yes';
$_['text_no']			 = 'No';
$_['text_authorize']     = 'Pre Authorize';
$_['text_capture']       = 'Purchase';
$_['text_redirection']   = 'Redirection';
$_['text_iframe']        = 'iframe';
$_['text_iframe_note']   = '<br />Customers will stay in website to complete payments or redirecting to Cardlink eCommerce payment page.<br />You must have a valid SSL certificate installed on your domain.';
$_['text_enable']        = 'Enable';
$_['text_disable']       = 'Disable';
$_['text_tokenization_note']   = '<br />If checked the user will have the ability to store credit card details for future purchases. You must contact Cardlink first.';
$_['text_allowu']      	 = 'Allow 3D Secure U Status';
$_['text_rejectu']       = 'Reject 3D Secure U Status (see manual)';
$_['text_url_gateway']   = '<br />Gateway URL - For testing use:<br>https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi<br>Live mode use: https://www.alphaecommerce.gr/vpos/shophandlermpi';
$_['text_url_success']   = '<br />Success URL - Example:<br>http://www.yourdomain.gr/index.php?route=extension/payment/cardlink/callback/success';
$_['text_url_fail']   = '<br />Fail / Cancel URL - Example:<br>http://www.yourdomain.gr/index.php?route=extension/payment/cardlink/callback/fail';
$_['text_url_css']   = '<br />Optional Css URL - Example:<br>https://www.yourdomain.gr/alpha.css';
$_['text_instalments'] 	 = '<br />Example: 150:3,600:6<br />Order total 150 -> allow 3 instalments, order total 600 -> allow 3 and 6 instalments <br /> The plugin can support the maximum number of 60 installments. The exact number depends on the customer’s contract with the bank provider. <br /> Leave empty to deactivate instalments';
$_['text_cardlink']     = '<a onclick="window.open(\'http://www.cardlink.gr\');"><img src="view/image/payment/cardlink.png" alt="Cardlink" title="Cardlink" /></a>';
$_['text_order_status_note'] = '<br />Order status after success payment';

// Entry
$_['entry_total']               = 'Total:';
$_['help_total']                = 'The checkout total the order must reach before this payment method becomes active.';
$_['entry_merchantid']			= 'Merchant ID:';
$_['entry_merchantpass']		= 'Shared Secret Key:';
$_['entry_title']       		= 'Payment Title:';
$_['entry_url_gateway']			= 'Gateway URL:';
$_['entry_url_success']			= 'Success URL:';
$_['entry_url_fail']			= 'Fail / Cancel URL:';
$_['entry_url_css']				= 'Css URL:';
$_['entry_trtype']				= 'Transaction Type:';
$_['entry_iframe']				= 'Cardlink eCommerce Payment Page:';
$_['entry_tokenization']	    = 'Tokenization:';
$_['entry_paytype']				= 'Allow MasterPass:';
$_['entry_security']			= '3D Security:';
$_['entry_testurl']				= 'Cardlink test URL:';
$_['entry_instalments']			= 'Instalments:';
$_['entry_processed_status']	= 'Processed Status:';
$_['entry_failed_status']		= 'Failed Status:';
$_['entry_geo_zone']			= 'Geo Zone:';
$_['entry_status']				= 'Status:';
$_['entry_sort_order']			= 'Sort Order:';
$_['entry_test']				= 'Environment:';
$_['entry_acquirer']			= 'Acquirer:';
$_['entry_order_status']		= 'Order Status:';

// Error
$_['error_permission']    = 'Warning: You do not have permission to modify payment Cardlink!';
$_['error_merchantid']    = 'Merchant ID Required';
$_['error_merchantpass']  = 'Shared Secret Required';
$_['error_title']         = 'Payment Title Required';
$_['error_url_gateway']   = 'Gateway URL Required';
$_['error_url_success']   = 'Success URL Required';
$_['error_url_fail']  	  = 'Fail URL Required';