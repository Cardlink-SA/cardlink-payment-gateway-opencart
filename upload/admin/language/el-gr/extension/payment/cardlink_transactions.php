<?php
$_['heading_title']       = 'Cardlink Συναλλαγές';

$_['text_home']           = 'Αρχική';
$_['text_list']           = 'Λίστα Συναλλαγών';
$_['text_filter']         = 'Φιλτράρισμα κατά Order ID';
$_['text_filter_btn']     = 'Αναζήτηση';
$_['text_reset']          = 'Επαναφορά';
$_['text_no_results']     = 'Δεν βρέθηκαν συναλλαγές.';
$_['text_logs']           = 'Ιστορικό Δευτερογενών Συναλλαγών';
$_['text_no_logs']        = 'Δεν υπάρχουν καταγεγραμμένες ενέργειες.';

// Table columns
$_['column_order_id']      = 'ID Παραγγελίας';
$_['column_cardlink_id']   = 'Cardlink Order ID';
$_['column_tx_id']         = 'ID Συναλλαγής';
$_['column_payment_ref']   = 'Αναφορά Πληρωμής';
$_['column_status']        = 'Κατάσταση';
$_['column_amount']        = 'Ποσό';
$_['column_pay_method']    = 'Μέθοδος';
$_['column_date']          = 'Ημερομηνία';
$_['column_actions']       = 'Ενέργειες';

// Log table columns
$_['column_action']        = 'Ενέργεια';
$_['column_log_amount']    = 'Ποσό';
$_['column_log_status']    = 'Αποτέλεσμα';
$_['column_log_date']      = 'Ημερομηνία';

// Buttons
$_['button_capture']        = 'Capture';
$_['button_partial_capture']= 'Μερικό Capture';
$_['button_void']           = 'Void / Ακύρωση';
$_['button_full_refund']    = 'Πλήρης Επιστροφή';
$_['button_partial_refund'] = 'Μερική Επιστροφή';
$_['button_confirm']        = 'Επιβεβαίωση';
$_['button_cancel']         = 'Άκυρο';

// Amount input
$_['text_enter_amount']     = 'Εισαγωγή ποσού';

// Success messages
$_['text_capture_success']  = 'Η συναλλαγή καταχωρήθηκε επιτυχώς.';
$_['text_void_success']     = 'Η ακύρωση ολοκληρώθηκε επιτυχώς.';
$_['text_refund_success']   = 'Η επιστροφή χρημάτων ολοκληρώθηκε επιτυχώς.';

// Errors
$_['error_permission']      = 'Προειδοποίηση: Δεν έχετε δικαίωμα τροποποίησης ρυθμίσεων πληρωμής!';
$_['error_api_failed']      = 'Το αίτημα API απέτυχε. Παρακαλώ δοκιμάστε ξανά.';
$_['error_xml_api_disabled']= 'Οι Λειτουργίες XML API είναι απενεργοποιημένες. Ενεργοποιήστε τες από τις ρυθμίσεις πληρωμής Cardlink.';
$_['error_void_next_day']   = 'Το Void είναι διαθέσιμο μόνο την ίδια ημέρα της συναλλαγής. Χρησιμοποιήστε Επιστροφή για ακυρώσεις επόμενης ημέρας.';
$_['error_refund_same_day'] = 'Η επιστροφή χρημάτων ίδιας ημέρας δεν υποστηρίζεται. Χρησιμοποιήστε Void για ακύρωση συναλλαγής ίδιας ημέρας.';
