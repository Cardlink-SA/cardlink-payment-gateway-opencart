<?php
// Heading
$_['heading_title']      = 'Cardlink Iris';

// Text
$_['text_payment']       = 'Πληρωμή';
$_['text_success']       = 'Επιτυχία: Έχετε επεξεργαστεί τη πληρωμή με Cardlink';
$_['text_test']			 = 'Περιβάλλον δοκιμών';
$_['text_live']      	 = 'Περιβάλλον live';
$_['text_yes']			 = 'Ναι';
$_['text_no']			 = 'Όχι';
$_['text_authorize']     = 'Με Προέγκριση';
$_['text_capture']       = 'Αγορά';
$_['text_redirection']   = 'Ανακατεύθυνση';
$_['text_iframe']        = 'iframe';
$_['text_iframe_note']   = '<br />Οι πελάτες θα παραμείνουν στον ιστότοπο για να ολοκληρώσουν τις πληρωμές χωρίς να ανακατευθύνονται στη σελίδα πληρωμών ηλεκτρονικού εμπορίου της Cardlink.<br />Πρέπει να έχετε εγκαταστήσει ένα έγκυρο πιστοποιητικό SSL στον τομέα σας.';
$_['text_enable']        = 'Ενεργοποίηση';
$_['text_disable']       = 'Απενεργοποίηση';
$_['text_tokenization_note']   = '<br />Εάν επιλεγεί, ο χρήστης θα έχει τη δυνατότητα αποθήκευσης στοιχείων πιστωτικής κάρτας για μελλοντικές αγορές. Πρέπει πρώτα να επικοινωνήσετε με την Cardlink.';
$_['text_allowu']      	 = 'Allow 3D Secure U Status';
$_['text_rejectu']       = 'Reject 3D Secure U Status (see manual)';
$_['text_url_gateway']   = '<br />Gateway URL - Για τη χρήση της δοκιμής:<br>https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi<br>Live mode use: https://www.alphaecommerce.gr/vpos/shophandlermpi';
$_['text_url_success']   = '<br />Success URL - παράδειγμα:<br>http://www.yourdomain.gr/index.php?route=extension/payment/cardlink_iris/callback/success';
$_['text_url_fail']   = '<br />Fail / Cancel URL - παράδειγμα:<br>http://www.yourdomain.gr/index.php?route=extension/payment/cardlink_iris/callback/fail';
$_['text_url_css']   = '<br />Προαιρετικός Css URL - παράδειγμα:<br>https://www.yourdomain.gr/alpha.css';
$_['text_instalments'] 	 = '<br />Παράδειγμα: 150:3,600:6 <br /> Παραγγελία συνολικά 150 -> επιτρέπονται 3 δόσεις, σύνολο παραγγελίας 600 -> επιτρέπονται 3 και 6 δόσεις. <br /> Μέγιστος αριθμός δόσεων 60. Εξαρτάται από τη σύμβαση του πελάτη με την τράπεζα. <br /> Αφήστε το κενό για να απενεργοποιήσετε δόσεις.';
$_['text_cardlink_iris']     = '<a onclick="window.open(\'http://www.cardlink.gr\');"><img src="view/image/payment/cardlink.png" alt="Cardlink" title="Cardlink" /></a>';
$_['text_order_status_note'] = '<br />Κατασταση παραγγελίας μετά απο επιτυχή πληρωμή.';

// Entry
$_['entry_total']               = 'Σύνολο:';
$_['help_total']                = 'Το συνολικό χρηματικό ποσό που θα πρέπει να φτάσει η παραγγελία για να ενεργοποιηθεί αυτός ο τρόπος πληρωμής.';
$_['entry_merchantid']			= 'MID επιχείρησης:';
$_['entry_merchantpass']		= 'Shared Secret Key:';
$_['entry_rf_payment_code']		= 'IRIS customer code:';
$_['entry_title']       		= 'Τίτλος:';
$_['entry_url_gateway']			= 'Gateway URL:';
$_['entry_url_success']			= 'Success URL:';
$_['entry_url_fail']			= 'Fail / Cancel URL:';
$_['entry_url_css']				= 'Css URL:';
$_['entry_trtype']				= 'Τύπος συναλλαγής:';
$_['entry_iframe']				= 'URL σελίδας επιστροφής:';
$_['entry_tokenization']	    = 'Αποθήκευση των στοιχείων της κάρτας:';
$_['entry_paytype']				= 'Allow MasterPass:';
$_['entry_security']			= '3D Security:';
$_['entry_testurl']				= 'Cardlink test URL:';
$_['entry_instalments']			= 'Δόσεις:';
$_['entry_processed_status']	= 'Κατάσταση Επεξεργάστηκε (Processed):';
$_['entry_failed_status']		= 'Κατάσταση Απέτυχε (Failed):';
$_['entry_geo_zone']			= 'Γεωγραφική Ζώνη:';
$_['entry_status']				= 'Κατάσταση:';
$_['entry_sort_order']			= 'Σειρά Ταξινόμησης:';
$_['entry_test']				= 'Περιβάλλον:';
$_['entry_acquirer']			= 'Οργανισμός αποδοχής πληρωμών:';
$_['entry_order_status']		= 'Κατάσταση Παραγγελίας:';

// Error
$_['error_permission']    = 'Ειδοποίηση: Δεν έχετε άδεια να επεξεργαστείτε τη πληρωμή Cardlink!';
$_['error_merchantid']    = 'Απαιτείται το Merchant ID';
$_['error_merchantpass']  = 'Απαιτείται το Shared Secret';
$_['error_rf_payment_code']  = 'Απαιτείται το IRIS customer code';
$_['error_title']         = 'Απαιτείται ο τίτλος';
$_['error_url_gateway']   = 'Απαιτείται το Gateway URL';
$_['error_url_success']   = 'Απαιτείται το Success URL';
$_['error_url_fail']  	  = 'Απαιτείται το Fail URL';