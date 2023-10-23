<?php
// Text
$_['heading_title']     = 'Thank you for shopping with %s .... ';
$_['text_title']		= 'Online secure payment with Cardlink checkout'; # checkout payment option
$_['text_checkout']		= 'Checkout';
$_['text_paytype']		= 'Payment method: ';
$_['text_paytype_card']			= 'Credit / debit card';
$_['text_paytype_masterpass']	= 'MasterPass';

$_['text_instalments']		= 'Instalments: ';
$_['text_instalments_nr']	= ' instalments';
$_['text_instalments_no']	= 'No instalments';
$_['text_store_card']	    = 'Store your card?';
$_['text_new_card']	        = 'Pay with new card';

$_['text_to_cardlink']		= '<strong>Please note: </strong>You will be redirected to a secure page to enter your credit card details.';
$_['text_continue']		= 'Continue';
$_['text_wait']			= 'Please wait...';
$_['text_success']		= 'Your transaction was accepted.';
$_['text_returnlink']	= 'Please click <a href="%s">here</a> to continue';
$_['text_redirecting']	= 'Redirecting...';

$_['text_failure']      = '<span style="color: #FF0000">The transaction failed or has been cancelled!</span>';
$_['text_failure_wait'] = '<b><span style="color: #FF0000">Please wait...</span></b><br>If you are not automatically re-directed in 10 seconds, please click <a href="%s">here</a>.';

// Error
$_['error_declined']	 = 'Transaction failed, please try again.';
$_['error_cancelled']	 = 'Your transaction has not been approved not completed. Please try again or use another credit card.';
$_['error_fail_title']	 = 'Sorry';
$_['error_txn_fail']	 = 'The transaction failed. Please try again. %s'; # the %s is used to append DPS error message where possible
$_['error_cardlink_request']	 = 'The GenerateRequest XML sent to the payment gateway was invalid.  Cannot proceed with the transaction.';
$_['error_cardlink_response'] = 'The ProcessResponse XML sent to the payment gateway was invalid and the transaction details could not be decrypted';
$_['error_cardlink_result']	 = 'No encrypted transaction details could be extracted from the URL.';