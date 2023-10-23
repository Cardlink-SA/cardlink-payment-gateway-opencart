<?php
// Text
$_['heading_title']     = 'Σας ευχαριστώ για τις αγορές με %s .... ';
$_['text_title']		= 'Online secure payment with Cardlink checkout'; # checkout payment option
$_['text_checkout']		= 'Ταμείο';
$_['text_paytype']		= 'Μέθοδος πληρωμής: ';
$_['text_paytype_card']			= 'Πιστωτική / Χρεωστική Κάρτα';
$_['text_paytype_masterpass']	= 'MasterPass';

$_['text_instalments']		= 'Άτοκες Μηνιαίες Δόσεις: ';
$_['text_instalments_nr']	= ' δόσεις';
$_['text_instalments_no']	= 'Χωρίς Δόσεις';
$_['text_store_card']	    = 'Αποθήκευση κάρτας;';
$_['text_new_card']	        = 'Πληρωμή με νέα κάρτα';

$_['text_to_cardlink']		= '<strong>Προσοχή: </strong>Πατώντας επιβεβαίωση θα μεταφερθείτε σε ασφαλές τραπεζικό σύστημα για την ολοκλήρωση της πληρωμή σας.';
$_['text_continue']		= 'Συνέχεια';
$_['text_wait']			= 'Παρακαλώ περιμένετε...';
$_['text_success']		= 'Σας ενημερώνουμε πως πραγματοποιήθηκε με επιτυχία πληρωμή του παρακάτω ποσού!';
$_['text_returnlink']	= 'Παρακαλώ κάντε κλικ <a href="%s">εδώ</a> για να συνεχίσετε.';
$_['text_redirecting']	= 'Αναπροσανατολισμός...';

$_['text_failure']      = '<span style="color: #FF0000">Υπήρξε λάθος κατά την επεξεργασία της πιστωτικής σας κάρτας. Παρακαλώ δοκιμάστε ξανά.</span>';
$_['text_failure_wait'] = '<b><span style="color: #FF0000">Παρακαλώ περιμένετε...</span></b><br>Αν δεν μεταφερθείτε αυτόματα σε 10 δευτερόλεπτα, παρακαλούμε κάντε κλικ <a href="%s">εδώ</a>.';

// Error
$_['error_declined']	 = 'Υπήρξε λάθος κατά την επεξεργασία της πιστωτικής σας κάρτας. Παρακαλώ δοκιμάστε ξανά.';
$_['error_cancelled']	 = 'Η συναλλαγή σας δεν έχει εγκριθεί  δεν ολοκληρώθηκε. Παρακαλώ προσπαθήστε ξανά ή να χρησιμοποιήσετε μια άλλη πιστωτική κάρτα.';
$_['error_fail_title']	 = 'Λυπάμαι';
$_['error_txn_fail']	 = 'Υπήρξε λάθος κατά την επεξεργασία της πιστωτικής σας κάρτας. %s';
$_['error_cardlink_request']	 = 'The GenerateRequest XML sent to the payment gateway was invalid.  Cannot proceed with the transaction.';
$_['error_cardlink_response'] = 'The ProcessResponse XML sent to the payment gateway was invalid and the transaction details could not be decrypted';
$_['error_cardlink_result']	 = 'No encrypted transaction details could be extracted from the URL.';