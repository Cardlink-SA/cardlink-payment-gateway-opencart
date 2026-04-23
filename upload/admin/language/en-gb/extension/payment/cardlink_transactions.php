<?php
$_['heading_title']       = 'Cardlink Transactions';

$_['text_home']           = 'Home';
$_['text_list']           = 'Transaction List';
$_['text_filter']         = 'Filter by Order ID';
$_['text_filter_btn']     = 'Search';
$_['text_reset']          = 'Reset';
$_['text_no_results']     = 'No transactions found.';
$_['text_logs']           = 'Secondary Transaction History';
$_['text_no_logs']        = 'No secondary actions recorded.';

// Table columns
$_['column_order_id']      = 'Order ID';
$_['column_cardlink_id']   = 'Cardlink Order ID';
$_['column_tx_id']         = 'Transaction ID';
$_['column_payment_ref']   = 'Payment Ref';
$_['column_status']        = 'Status';
$_['column_amount']        = 'Amount';
$_['column_pay_method']    = 'Method';
$_['column_date']          = 'Date';
$_['column_actions']       = 'Actions';

// Log table columns
$_['column_action']        = 'Action';
$_['column_log_amount']    = 'Amount';
$_['column_log_status']    = 'Result';
$_['column_log_date']      = 'Date';

// Buttons
$_['button_capture']        = 'Capture';
$_['button_partial_capture']= 'Partial Capture';
$_['button_void']           = 'Void / Reverse';
$_['button_full_refund']    = 'Full Refund';
$_['button_partial_refund'] = 'Partial Refund';
$_['button_confirm']        = 'Confirm';
$_['button_cancel']         = 'Cancel';

// Amount input
$_['text_enter_amount']     = 'Enter amount';

// Success messages
$_['text_capture_success']  = 'Transaction captured successfully.';
$_['text_void_success']     = 'Transaction voided successfully.';
$_['text_refund_success']   = 'Refund processed successfully.';

// Errors
$_['error_permission']      = 'Warning: You do not have permission to modify payment settings!';
$_['error_api_failed']      = 'API request failed. Please try again.';
$_['error_xml_api_disabled']= 'XML API Operations are disabled. Enable them in the Cardlink payment settings.';
$_['error_void_next_day']   = 'Void is only available on the same day as the transaction. Use Refund for next-day cancellations.';
$_['error_refund_same_day'] = 'Same-day refund is not supported. Use Void to cancel a transaction on the same day.';
