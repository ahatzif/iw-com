<?php
/**
 * Provision the English WPML content and String Translation entries for COM.
 *
 * Run with:
 * wp --path=/var/www/html/com eval-file /var/www/html/com/tools/wpml-enable-english.php
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "Run this file through WP-CLI.\n" );
}

if ( ! function_exists( 'icl_register_string' ) || ! function_exists( 'icl_add_string_translation' ) ) {
    WP_CLI::error( 'WPML String Translation is not active.' );
}

$phase = getenv( 'COM_EN_PHASE' ) ?: 'all';

$ui = [
    '%1$d από %2$d' => '%1$d of %2$d',
    '200 χρόνια' => '200 years',
    '200 ΧΡΟΝΙΑ ΑΠΟ ΤΗΝ ΕΞΟΔΟ' => '200 YEARS SINCE THE EXODUS',
    'Α.Φ.Μ.' => 'Tax ID',
    'Αγορά' => 'Purchase',
    'Αγορά #%s' => 'Purchase #%s',
    'ΑΓΟΡΑ ΕΙΣΙΤΗΡΙΟΥ' => 'BUY TICKETS',
    'Αγορά εισιτηρίου' => 'Buy tickets',
    'Αγορά εισιτηρίων' => 'Buy tickets',
    'Αγορά εισιτηρίων →' => 'Buy tickets →',
    'ΑΓΟΡΑΣ' => 'PURCHASE',
    'Αγορές' => 'Purchases',
    'Ακύρωση' => 'Cancel',
    'Άλλα Μουσεία του τόπου μας' => 'More museums in our region',
    'Αλλαγή κωδικού' => 'Change password',
    'Ανακαλύψτε τα μουσεία' => 'Discover the museums',
    'Ανακαλύψτε τα μουσεία ↓' => 'Discover the museums ↓',
    'Αξία εισιτηρίων' => 'Ticket value',
    'από' => 'from',
    'Απόδειξη' => 'Receipt',
    'Αποθήκευση αλλαγών' => 'Save changes',
    'Αποθήκευση διεύθυνσης' => 'Save address',
    'Απόκρυψη κωδικού' => 'Hide password',
    'απομένει 1 εισιτήριο' => '1 ticket remaining',
    'απομένουν %s εισιτήρια' => '%s tickets remaining',
    'ΑΠΟΣΤΟΛΗ' => 'SUBMIT',
    'Αποστολή' => 'Submit',
    'Αποσύνδεση' => 'Log out',
    'Απρίλιος' => 'April',
    'Αριθμός αγοράς' => 'Purchase number',
    'Αριθμός παραγγελίας' => 'Order number',
    'Αρχική' => 'Home',
    'Αύγουστος' => 'August',
    'Αύξηση %s' => 'Increase %s',
    'Αφαίρεση' => 'Remove',
    'Αφαίρεση %s από το καλάθι' => 'Remove %s from cart',
    'Αφήστε τα πεδία κενά αν δεν θέλετε να αλλάξετε τον κωδικό σας.' => 'Leave these fields blank if you do not want to change your password.',
    'Βήματα αγοράς' => 'Purchase steps',
    'Βήματα checkout' => 'Checkout steps',
    'Βρείτε τα ενεργά εισιτήρια, τα QR και το ιστορικό των επισκέψεών σας.' => 'Find your active tickets, QR codes and visit history.',
    'Βρείτε το εισιτήριό σας' => 'Find your ticket',
    'Για τη συγκεκριμένη χώρα τα στοιχεία τιμολογίου συμπληρώνονται χειροκίνητα.' => 'For this country, invoice details must be entered manually.',
    'Για την προστασία των στοιχείων σας, πληκτρολογήστε το email που χρησιμοποιήσατε κατά την αγορά.' => 'To protect your details, enter the email address used for the purchase.',
    'Δ.Ο.Υ.' => 'Tax office',
    'ΔΕΙΤΕ ΕΠΙΣΗΣ' => 'SEE ALSO',
    'Δείτε στο χάρτη' => 'View on map',
    'Δείτε τα μουσεία' => 'View the museums',
    'Δείτε την κατάσταση και τις λεπτομέρειες όλων των αγορών σας.' => 'View the status and details of all your purchases.',
    'Δεκέμβριος' => 'December',
    'Δεν έχει οριστεί ακόμη.' => 'Not set yet.',
    'Δεν έχετε κάνει ακόμα κάποια αγορά' => 'You have not made any purchases yet',
    'Δεν έχουν εκδοθεί ακόμα εισιτήρια' => 'No tickets have been issued yet',
    'Δεν ήταν δυνατή η διαγραφή του λογαριασμού.' => 'Your account could not be deleted.',
    'Δεν ήταν δυνατή η προσθήκη στο καλάθι. Παρακαλούμε δοκιμάστε ξανά.' => 'Could not add to cart. Please try again.',
    'Δεν ήταν δυνατή η φόρτωση αυτών των εισιτηρίων.' => 'These tickets could not be loaded.',
    'Δεν υπάρχει διαθέσιμος τρόπος πληρωμής.' => 'No payment method is available.',
    'Δεν υπάρχουν διαθέσιμα εισιτήρια' => 'No tickets are available',
    'Δεν υπάρχουν διαθέσιμες ημερομηνίες.' => 'No dates are available.',
    'Δεν υπάρχουν διαθέσιμες ώρες για αυτή την ημερομηνία.' => 'No times are available for this date.',
    'ΔΕΝ ΧΑΘΗΚΕ ΤΙΠΟΤΑ — ΕΚΤΟΣ ΑΠΟ ΑΥΤΗ ΤΗ ΣΕΛΙΔΑ.' => 'NOTHING IS LOST — EXCEPT THIS PAGE.',
    'Δες εδώ →' => 'View here →',
    'Διαγραφή' => 'Delete',
    'Διαγραφή λογαριασμού' => 'Delete account',
    'διαθέσιμη' => 'available',
    'ΔΙΑΘΕΣΙΜΟΣ ΑΡΙΘΜΟΣ' => 'AVAILABLE',
    'Διευθύνσεις' => 'Addresses',
    'Διεύθυνση' => 'Address',
    'Διεύθυνση αποστολής' => 'Shipping address',
    'Διεύθυνση χρέωσης' => 'Billing address',
    'Διεύθυνση email' => 'Email address',
    'Δραστηριότητα επιχείρησης' => 'Business activity',
    'Εγγραφή' => 'Register',
    'Εγγραφή μέσω τρίτου παρόχου' => 'Register with a third-party provider',
    'Είδος παραστατικού' => 'Document type',
    'Εικόνα μουσείου' => 'Museum image',
    'εισιτήρια' => 'tickets',
    'Εισιτήρια' => 'Tickets',
    'Εισιτήρια μουσείου' => 'Museum tickets',
    'ΕΙΣΙΤΗΡΙΟ' => 'TICKET',
    'εισιτήριο' => 'ticket',
    'Εισιτήριο' => 'Ticket',
    'Εισιτήριο %d' => 'Ticket %d',
    'ΕΙΣΙΤΗΡΙΩΝ' => 'TICKETS',
    'Είσοδος στο μουσείο' => 'Museum admission',
    'Είστε βέβαιος ότι θέλετε να διαγράψετε τον λογαριασμό σας;' => 'Are you sure you want to delete your account?',
    'Έλεγξε το email σου και ακολούθησε τις οδηγίες για να ολοκληρώσεις την εγγραφή σου ' => 'Check your email and follow the instructions to complete your registration ',
    'Ελέγξτε ή ενημερώστε τα στοιχεία που θα χρησιμοποιηθούν για την έκδοση του παραστατικού σας.' => 'Review or update the details used to issue your receipt or invoice.',
    'Ελέγξτε τα εισιτήριά σας πριν προχωρήσετε στην ολοκλήρωση της αγοράς.' => 'Review your tickets before completing your purchase.',
    'Ελέγξτε τα προσωπικά σας στοιχεία και μεταβείτε γρήγορα στις αγορές ή στα εισιτήριά σας.' => 'Review your personal details and quickly access your purchases or tickets.',
    'Ελλάδα' => 'Greece',
    'Εμφάνιση κωδικού' => 'Show password',
    'Εναλλαγή γλώσσας' => 'Language switcher',
    'Ένας τόπος όπου η ιστορία παραμένει ζωντανή.' => 'A place where history remains alive.',
    'Ενεργά εισιτήρια' => 'Active tickets',
    'Ενεργά Εισιτήρια' => 'Active Tickets',
    'Ενεργοποίηση' => 'Activate',
    'Ενημερώστε τα βασικά στοιχεία του λογαριασμού ή αλλάξτε τον κωδικό πρόσβασής σας.' => 'Update your account details or change your password.',
    'εξαντλήθηκαν' => 'sold out',
    'Επαλήθευση ΑΦΜ' => 'Verify tax ID',
    'Επαλήθευση ΑΦΜ μέσω ΑΑΔΕ' => 'Verify tax ID through AADE',
    'Επαλήθευση VAT μέσω VIES' => 'Verify VAT number through VIES',
    'Επανάληψη πληρωμής' => 'Retry payment',
    'Επαναφορά συνθηματικού' => 'Reset password',
    'Επεξεργασία' => 'Edit',
    'Επιβεβαίωση νέου κωδικού' => 'Confirm new password',
    'Επιβεβαίωση email' => 'Confirm email',
    'Επιλέξτε' => 'Select',
    'Επιλέξτε εισιτήρια' => 'Select tickets',
    'Επιλέξτε κατηγορία εισιτηρίου.' => 'Select a ticket category.',
    'Επιλέξτε περιφέρεια' => 'Select region',
    'Επιλέξτε το μουσείο, την ημερομηνία και τον τύπο εισιτηρίου που σας ενδιαφέρει.' => 'Select the museum, date and ticket type you are interested in.',
    'Επιλέξτε τον τρόπο πληρωμής και ολοκληρώστε με ασφάλεια την παραγγελία σας.' => 'Choose a payment method and securely complete your order.',
    'Επιλέξτε ώρα επίσκεψης' => 'Select a visiting time',
    'ΕΠΙΛΟΓΗ' => 'SELECT',
    'Επιλογή διαφάνειας' => 'Slide selection',
    'ΕΠΙΛΟΓΗ ΕΙΣΙΤΗΡΙΩΝ' => 'SELECT TICKETS',
    "Επισκεφθείτε\nτα μουσεία μας" => "Visit\nour museums",
    'Επίσκεψη σε όλα τα μουσεία' => 'Visit all museums',
    'Επισκόπηση' => 'Overview',
    'Επιστροφή' => 'Back',
    'Επιστροφή στην αρχική' => 'Return home',
    'Επιτυχής Εγγραφή! ' => 'Registration successful! ',
    'Επόμενα' => 'Next',
    'Επόμενη' => 'Next',
    'Επόμενο' => 'Next',
    'Επόμενος μήνας' => 'Next month',
    'Επωνυμία επιχείρησης' => 'Company name',
    'Επώνυμο' => 'Last name',
    'Εταιρεία' => 'Company',
    'Ευχαριστούμε' => 'Thank you',
    'Ευχαριστούμε. Η παραγγελία σας έχει παραληφθεί.' => 'Thank you. Your order has been received.',
    'Έχετε %d ενεργά εισιτήρια' => 'You have %d active tickets',
    'Έχετε 1 ενεργό εισιτήριο' => 'You have 1 active ticket',
    'Έχετε διαθέσιμο το QR κάθε εισιτηρίου, καθώς και επιλογές Wallet ή PDF.' => 'The QR code for each ticket is available, along with Wallet and PDF options.',
    'ΕΧΕΤΕ ΕΠΙΛΕΞΕΙ' => 'YOU HAVE SELECTED',
    'Έχετε κάνει %d αγορές' => 'You have made %d purchases',
    'Έχετε κάνει 1 αγορά' => 'You have made 1 purchase',
    'Έχετε λογαριασμό;' => 'Already have an account?',
    'Η αγορά ολοκληρώθηκε' => 'Purchase completed',
    'Η αλλαγή κωδικού δεν ολοκληρώθηκε. Παρακαλούμε ζητήστε νέο σύνδεσμο επαναφοράς.' => 'The password change could not be completed. Please request a new reset link.',
    'Ή δείτε τα μουσεία' => 'Or view the museums',
    'Η ενέργεια αυτή είναι μη αναστρέψιμη. Όλα τα προσωπικά σας δεδομένα, το ιστορικό σας και η πρόσβασή σας στις υπηρεσίες μας θα διαγραφούν οριστικά.' => 'This action cannot be undone. Your personal data, history and access to our services will be permanently deleted.',
    'Η ενεργοποίηση δεν ολοκληρώθηκε. Παρακαλούμε ελέγξτε τον σύνδεσμο του email.' => 'Activation could not be completed. Please check the link in your email.',
    'Η Έξοδος' => 'The Exodus',
    'Η ηλεκτρονική διάθεση εισιτηρίων δεν έχει ενεργοποιηθεί ακόμη για το συγκεκριμένο μουσείο.' => 'Online ticket sales have not yet been enabled for this museum.',
    'Η ιστορία συνεχίζεται' => 'History continues',
    'Η κράτηση έληξε' => 'The reservation has expired',
    'Η κράτησή σας έληξε' => 'Your reservation has expired',
    'Η παραγγελία σας #%s καταχωρήθηκε με επιτυχία. Παρακάτω θα βρείτε όλες τις λεπτομέρειες της αγοράς σας.' => 'Your order #%s was placed successfully. You can find all purchase details below.',
    'Η παραγγελία σας καταχωρείται' => 'Your order is being placed',
    'Η παραγγελία σας καταχωρήθηκε' => 'Your order has been placed',
    'Η πληρωμή δεν ολοκληρώθηκε' => 'Payment was not completed',
    'Η ΣΕΛΙΔΑ ΔΕΝ ΒΡΕΘΗΚΕ' => 'PAGE NOT FOUND',
    'Η σελίδα έφυγε βόλτα προς τη λιμνοθάλασσα. Εσείς μπορείτε να γυρίσετε στην αρχική και να συνεχίσετε την εξερεύνηση.' => 'This page wandered off towards the lagoon. Return home and continue exploring.',
    'Η σελίδα που αναζητάτε δεν υπάρχει — η ιστορία του Μεσολογγίου όμως είναι παντού γύρω μας.' => 'The page you are looking for does not exist — but the history of Messolonghi is all around us.',
    'Η σελίδα; Κανείς δεν ξέρει.' => 'The page? Nobody knows.',
    'Η συναλλαγή δεν ολοκληρώθηκε. Μπορείτε να δοκιμάσετε ξανά την πληρωμή.' => 'The transaction was not completed. You can try the payment again.',
    'ΗΜ/ΝΙΑ:' => 'DATE:',
    'Ημερομηνία' => 'Date',
    'ΗΜΕΡΟΜΗΝΙΑΣ' => 'DATE',
    'Ι.Π. ΜΕΣΟΛΟΓΓΙΟΥ' => 'SACRED CITY OF MESSOLONGHI',
    'Ιανουάριος' => 'January',
    'Ιούλιος' => 'July',
    'Ιούνιος' => 'June',
    'Ιστορικό' => 'History',
    'Καθαρισμός επαληθευμένων στοιχείων' => 'Clear verified details',
    'Καλάθι, %s' => 'Cart, %s',
    'Κάποιες διαδρομές χάνονται. Η μνήμη, ποτέ.' => 'Some paths are lost. Memory never is.',
    'Κατηγορία' => 'Category',
    'Κατηγορία:' => 'Category:',
    'Κάτι πήγε στραβά. Παρακαλούμε δοκιμάστε ξανά.' => 'Something went wrong. Please try again.',
    'Κλείσιμο' => 'Close',
    'Κλείσιμο καλαθιού' => 'Close cart',
    'Κλείσιμο παραθύρου' => 'Close dialog',
    'Κουπόνι: %s' => 'Coupon: %s',
    'Κύρια πλοήγηση' => 'Main navigation',
    'Κωδικός' => 'Password',
    'Λεπτομέρειες αγοράς' => 'Purchase details',
    'Λήψη εισιτηρίου σε PDF' => 'Download ticket as PDF',
    'ΛΙΜΝΟΘΑΛΑΣΣΑ ΜΕΣΟΛΟΓΓΙΟΥ' => 'MESSOLONGHI LAGOON',
    'Λογαριασμός' => 'Account',
    'Λογαριασμός χρήστη' => 'User account',
    'Μάιος' => 'May',
    'Μάρτιος' => 'March',
    'Μείωση %s' => 'Decrease %s',
    'Μενού λογαριασμού' => 'Account menu',
    'Μεσολόγγι 200 χρόνια' => 'Messolonghi 200 years',
    'ΜΕΣΟΛΟΓΓΙ, ΑΙΤΩΛΙΚΟ' => 'MESSOLONGHI, AITOLIKO',
    'Μετάβαση στη διαφάνεια %d' => 'Go to slide %d',
    'ΜΗ ΔΙΑΘΕΣΙΜΕΣ' => 'UNAVAILABLE',
    'μη διαθέσιμη' => 'unavailable',
    'ΜΙΚΡΗ ΠΑΡΑΚΑΜΨΗ · ΜΕΓΑΛΗ ΙΣΤΟΡΙΑ' => 'A SMALL DETOUR · A GREAT HISTORY',
    'Μπορείτε να επιλέξετε έως %s εισιτήρια.' => 'You can select up to %s tickets.',
    'Νέα αγορά εισιτηρίων' => 'Buy new tickets',
    'Νέος κωδικός' => 'New password',
    'Νοέμβριος' => 'November',
    'Ο λογαριασμός μου' => 'My account',
    'Οι αγορές μου' => 'My purchases',
    'Οι διευθύνσεις αυτές χρησιμοποιούνται αυτόματα κατά την ολοκλήρωση της αγοράς.' => 'These addresses are used automatically during checkout.',
    'Οι επισκέψεις μου' => 'My visits',
    'Οκτώβριος' => 'October',
    'ΟΛΑ' => 'ALL',
    'Όλα όσα αφορούν τις αγορές και τις επισκέψεις σας, συγκεντρωμένα σε ένα σημείο.' => 'Everything about your purchases and visits, all in one place.',
    'Ολοκληρώνουμε' => 'Finalising',
    'ΟΛΟΚΛΗΡΩΣΗ' => 'CHECKOUT',
    'ΟΛΟΚΛΗΡΩΣΗ ΑΓΟΡΑΣ' => 'CHECKOUT',
    'Ολοκλήρωση αγοράς' => 'Checkout',
    'Ολοκληρώστε' => 'Complete',
    'Όνομ/μο:' => 'Name:',
    'Όνομα' => 'First name',
    'Ονοματεπώνυμο' => 'Full name',
    'Όροφος, διαμέρισμα κ.λπ.' => 'Apartment, suite, etc.',
    'Παρακαλούμε αποδεχτείτε την πολιτική απορρήτου' => 'Please accept the privacy policy',
    'Παρακαλούμε αποδεχτείτε τους όρους χρήσης' => 'Please accept the terms of use',
    'Παρακαλούμε μην κλείσετε ή ανανεώσετε τη σελίδα.' => 'Please do not close or refresh this page.',
    'ΠΑΡΑΚΑΛΩ ΠΕΡΙΜΕΝΕΤΕ' => 'PLEASE WAIT',
    'Παρουσιάστηκε ένα σφάλμα. Παρακαλούμε δοκιμάστε ξανά.' => 'An error occurred. Please try again.',
    'Περιγραφή μουσείου' => 'Museum description',
    'ΠΕΡΙΕΧΟΜΕΝΑ' => 'CONTENTS',
    'Περιλαμβάνει' => 'Includes',
    'περιορισμένη διαθεσιμότητα' => 'limited availability',
    'ΠΕΡΙΟΡΙΣΜΕΝΟΣ ΑΡΙΘΜΟΣ' => 'LIMITED AVAILABILITY',
    'ΠΕΡΙΣΣΟΤΕΡΑ' => 'MORE',
    'Περισσότερα →' => 'More →',
    'Περισσότερα ↓' => 'More ↓',
    'ΠΕΡΙΣΣΟΤΕΡΑ ΑΡΘΡΑ' => 'MORE ARTICLES',
    'Περιφέρεια' => 'Region',
    'Πιστωτική / χρεωστική κάρτα' => 'Credit / debit card',
    'Πίσω στα εισιτήρια' => 'Back to tickets',
    'Πίσω στον σωστό δρόμο' => 'Back on the right path',
    'Πληροφορίες μουσείου' => 'Museum information',
    'ΠΛΗΡΩΜΗΣ' => 'PAYMENT',
    'Πόλη' => 'City',
    'Πολιτική απορρήτου' => 'Privacy Policy',
    'Ποσότητα: %d' => 'Quantity: %d',
    'Πρέπει να επιλέξετε τουλάχιστον %s εισιτήρια.' => 'You must select at least %s tickets.',
    'Πρέπει να συνδεθείτε για να ολοκληρώσετε την αγορά.' => 'You must sign in to complete your purchase.',
    'ΠΡΟΒΟΛΗ' => 'VIEW',
    'Προβολή παραγγελίας' => 'View order',
    'Προβολή της αγοράς σας' => 'View your purchase',
    'Προετοιμάζουμε την ασφαλή μετάβασή σας στο περιβάλλον πληρωμών της Cardlink.' => 'We are preparing your secure transfer to Cardlink.',
    'Προετοιμάζουμε την πληρωμή' => 'Preparing payment',
    'Προηγούμενα' => 'Previous',
    'Προηγούμενα εισιτήρια' => 'Previous tickets',
    'Προηγούμενη' => 'Previous',
    'Προηγούμενος μήνας' => 'Previous month',
    'Πρόσβαση στην παραγγελία' => 'Access order',
    'Προσθήκη στο καλάθι' => 'Add to cart',
    'Προσθήκη στο Apple Wallet' => 'Add to Apple Wallet',
    'Προσθήκη στο Google Wallet' => 'Add to Google Wallet',
    'Προσθήκη…' => 'Adding…',
    'Προσωπικά στοιχεία' => 'Personal details',
    'Προσωπικός χώρος' => 'Your account',
    'Προφίλ και ασφάλεια' => 'Profile and security',
    'Σας μεταφέρουμε στο ασφαλές περιβάλλον πληρωμών της Cardlink για να ολοκληρώσετε την πληρωμή σας.' => 'You are being transferred to Cardlink to complete your payment securely.',
    'Σας μεταφέρουμε στο ασφαλές περιβάλλον πληρωμών της Cardlink.' => 'You are being transferred to Cardlink securely.',
    'Σε αναμονή πληρωμής' => 'Pending payment',
    'Σελίδες λογαριασμού' => 'Account pages',
    'Σελιδοποίηση αγορών' => 'Purchase pagination',
    'Σελιδοποίηση εισιτηρίων' => 'Ticket pagination',
    'Σεπτέμβριος' => 'September',
    'Σημείωση' => 'Note',
    'ΣΗΜΕΡΑ' => 'TODAY',
    'ΣΤΟΙΧΕΙΑ' => 'DETAILS',
    'Στοιχεία αγοράς' => 'Purchase details',
    'Στοιχεία αποστολής' => 'Shipping details',
    'ΣΤΟΙΧΕΙΑ ΠΑΡΑΓΓΕΛΙΑΣ' => 'ORDER DETAILS',
    'Στοιχεία τιμολογίου' => 'Invoice details',
    'Στοιχεία χρέωσης' => 'Billing details',
    'Συμπληρώστε ένα έγκυρο email' => 'Enter a valid email address',
    'Συμπληρώστε έναν έγκυρο αριθμό τηλεφώνου.' => 'Enter a valid phone number.',
    'Συμπληρώστε τα στοιχεία όπως θέλετε να εμφανίζονται στις αγορές σας.' => 'Enter your details as you want them to appear on your purchases.',
    'Συμπληρώστε τα στοιχεία που θα χρησιμοποιηθούν για την έκδοση του παραστατικού σας.' => 'Enter the details to be used on your receipt or invoice.',
    'Συνδεθείτε εδώ' => 'Sign in here',
    'Σύνδεση' => 'Sign in',
    'Σύνδεση ή δημιουργία λογαριασμού' => 'Sign in or create an account',
    'ΣΥΝΕΧΕΙΑ ΑΓΟΡΩΝ' => 'CONTINUE SHOPPING',
    'Συνέχεια στην ασφαλή πληρωμή' => 'Continue to secure payment',
    'Συνέχεια στην Cardlink' => 'Continue to Cardlink',
    'Συνεχίστε την επιλογή εισιτηρίων και ανακαλύψτε τα μουσεία μας.' => 'Continue selecting tickets and discover our museums.',
    'ΣΥΝΟΛΟ' => 'TOTAL',
    'Σύνολο' => 'Total',
    'Σύνολο αγορών' => 'Total purchases',
    'Σύνοψη αγοράς' => 'Purchase summary',
    'Σύνοψη εισιτηρίου' => 'Ticket summary',
    'Σύνοψη λογαριασμού' => 'Account summary',
    'ΣΦΑΛΜΑ 404' => 'ERROR 404',
    'Τα εισιτήρια αποδεσμεύτηκαν. Για νέα διαθεσιμότητα χρειάζεται να επιλέξετε ξανά ημερομηνία και ώρα.' => 'The tickets have been released. Select a date and time again to check current availability.',
    'Τα εισιτήρια αποδεσμεύτηκαν. Ξεκινήστε νέα αγορά για να ελέγξετε ξανά τη διαθεσιμότητα.' => 'The tickets have been released. Start a new purchase to check availability again.',
    'Τα εισιτήριά μου' => 'My tickets',
    'Τα εισιτήριά σας' => 'Your tickets',
    'ΤΑ ΜΟΥΣΕΙΑ ΜΑΣ' => 'OUR MUSEUMS',
    'Τα μουσεία που μπορείτε να επισκεφθείτε' => 'Museums you can visit',
    'Τα προσωπικά σας δεδομένα χρησιμοποιούνται για την επεξεργασία της παραγγελίας και σύμφωνα με την %s.' => 'Your personal data is used to process your order in accordance with our %s.',
    'Ταχυδρομικός κώδικας' => 'Postcode',
    'Τηλέφωνο' => 'Phone',
    'Τηλέφωνο:' => 'Phone:',
    'την αγορά σας' => 'your purchase',
    'ΤΙ ΘΑ ΔΕΙΤΕ' => 'WHAT YOU WILL SEE',
    'Τιμή εισιτηρίου:' => 'Ticket price:',
    'Τιμή:' => 'Price:',
    'Τιμολόγιο' => 'Invoice',
    'ΤΙΤΛΟΣ' => 'TITLE',
    'Το ΑΦΜ επαληθεύεται από την ΑΑΔΕ και τα εταιρικά στοιχεία συμπληρώνονται αυτόματα.' => 'The tax ID is verified through AADE and available company details are filled in automatically.',
    'ΤΟ ΚΑΛΑΘΙ ΜΟΥ' => 'MY CART',
    'Το καλάθι σας' => 'Your cart',
    'Το καλάθι σας είναι άδειο' => 'Your cart is empty',
    'Το Μεσολόγγι δεν είναι μια πόλη που απλώς επισκέπτεται κανείς· είναι μια εμπειρία που ζει, μια ταυτότητα που μοιράζεται και μια μνήμη που εμπνέει.' => 'Messolonghi is not simply a city to visit; it is an experience to live, an identity to share and a memory that inspires.',
    'Το Μεσολόγγι είναι από εδώ' => 'Messolonghi is this way',
    'Το πεδίο είναι υποχρεωτικό' => 'This field is required',
    'Το πεδίο πρέπει να περιέχει τουλάχιστον {x} χαρακτήρες' => 'This field must contain at least {x} characters',
    'Η διεύθυνση δεν είναι έγκυρη' => 'The URL is not valid',
    'Το πεδίο δεν περιέχει κεφαλαίο χαρακτήρα' => 'The field must contain an uppercase letter',
    'Το πεδίο δεν περιέχει πεζό χαρακτήρα' => 'The field must contain a lowercase letter',
    'Το πεδίο δεν περιέχει αριθμό' => 'The field must contain a number',
    'Το πεδίο δεν περιέχει ειδικό χαρακτήρο' => 'The field must contain a special character',
    'Οι κωδικοί δεν ταιριάζουν.' => 'The passwords do not match.',
    'Το email δεν αντιστοιχεί στην παραγγελία. Ελέγξτε το και δοκιμάστε ξανά.' => 'The email does not match the order. Check it and try again.',
    'Το VAT number επαληθεύεται από το VIES και τα διαθέσιμα εταιρικά στοιχεία συμπληρώνονται αυτόματα.' => 'The VAT number is verified through VIES and available company details are filled in automatically.',
    'Τοποθεσία:' => 'Location:',
    'Τρέχων κωδικός' => 'Current password',
    'ΤΡΟΠΟΣ' => 'METHOD',
    'Τρόπος πληρωμής' => 'Payment method',
    'υποχρεωτικό' => 'required',
    'Φεβρουάριος' => 'February',
    'Φωτογραφίες μουσείων' => 'Museum photos',
    'ΧΜ… ΛΑΘΟΣ ΣΤΕΝΟ' => 'HMM… WRONG TURN',
    'Χμ… μάλλον πήρατε λάθος δρόμο.' => 'Hmm… it looks like you took a wrong turn.',
    'ΧΡΕΩΣΗΣ' => 'BILLING',
    'Χρόνος κράτησης:' => 'Reservation time:',
    'Χώρα / Περιοχή' => 'Country / Region',
    'ΩΡΑ:' => 'TIME:',
    'Ωράριο:' => 'Opening hours:',
    'ΩΡΑΣ' => 'TIME',
    'QR εισιτηρίου' => 'Ticket QR code',
    "ΜΟΥ\nΣΕΙΑ" => "MU\nSEUMS",
    '%d εισιτήριο' => '%d ticket',
    '%d εισιτήρια' => '%d tickets',
    'ΕΙΣΟΔΟΣ' => 'WELCOME',
    'Έχετε λογαριασμό ή είστε μέλος;' => 'Do you have an account or are you a member?',
    'Δεν έχετε λογαριασμό;' => 'Do not have an account?',
    'Συνεχίστε ως επισκέπτης ή δημιουργήστε έναν λογαριασμό για να αποκτήσετε πρόσβαση σε αποκλειστικά προνόμια.' => 'Continue as a guest or create an account to access exclusive benefits.',
    'Συνέχεια ως Επισκέπτης' => 'Continue as Guest',
    'ΣΥΝΔΕΣΗ' => 'SIGN IN',
    'Συμπληρώστε τα στοιχεία για να συνδεθείτε στον λογαριασμό σας.' => 'Enter your details to sign in to your account.',
    'ΚΩΔΙΚΟΣ' => 'PASSWORD',
    'Υπενθύμιση κωδικού' => 'Forgot password?',
    'ΣΥΝΔΕΣΗ ΜΕ' => 'SIGN IN WITH',
    'ΔΕΝ ΕΧΕΤΕ ΛΟΓΑΡΙΑΣΜΟ;' => 'DO NOT HAVE AN ACCOUNT?',
    'ΕΓΓΡΑΦΗ' => 'REGISTER',
    'ΞΕΧΑΣΑΤΕ ΤΟΝ ΚΩΔΙΚΟ ΣΑΣ;' => 'FORGOT YOUR PASSWORD?',
    'Εισάγετε τη διεύθυνση ηλεκτρονικού ταχυδρομείου που συνδέεται με τον λογαριασμό σας. Θα σας αποσταλεί ένας σύνδεσμος επαναφοράς κωδικού.' => 'Enter the email address linked to your account. We will send you a password reset link.',
    'ΕΛΕΓΞΤΕ ΤΟ EMAIL ΣΑΣ' => 'CHECK YOUR EMAIL',
    'Στείλαμε οδηγίες επαναφοράς κωδικού στο' => 'We sent password reset instructions to',
    'Επιστροφή στη σύνδεση' => 'Back to sign in',
    'ΔΗΜΙΟΥΡΓΙΑ ΝΕΟΥ ΚΩΔΙΚΟΥ' => 'CREATE A NEW PASSWORD',
    'Συμπληρώστε τον νέο κωδικό που θέλετε να χρησιμοποιείτε για τον λογαριασμό σας.' => 'Enter the new password you want to use for your account.',
    'ΝΕΟΣ ΚΩΔΙΚΟΣ' => 'NEW PASSWORD',
    'ΕΠΙΒΕΒΑΙΩΣΗ ΝΕΟΥ ΚΩΔΙΚΟΥ' => 'CONFIRM NEW PASSWORD',
    'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες, ένα πεζό γράμμα και έναν ειδικό χαρακτήρα.' => 'The password must contain at least 8 characters, one lowercase letter and one special character.',
    'Ο ΚΩΔΙΚΟΣ ΑΛΛΑΞΕ' => 'PASSWORD CHANGED',
    'Μπορείτε τώρα να συνδεθείτε χρησιμοποιώντας τον νέο σας κωδικό.' => 'You can now sign in with your new password.',
    'ΕΝΕΡΓΟΠΟΙΗΣΗ ΛΟΓΑΡΙΑΣΜΟΥ' => 'ACCOUNT ACTIVATION',
    'Ο κωδικός ενεργοποίησης έχει συμπληρωθεί αυτόματα. Πατήστε «Ενεργοποίηση» για να ολοκληρώσετε την εγγραφή σας.' => 'The activation code has been filled in automatically. Select “Activate” to complete your registration.',
    'ΚΩΔΙΚΟΣ ΕΝΕΡΓΟΠΟΙΗΣΗΣ' => 'ACTIVATION CODE',
    'Ο ΛΟΓΑΡΙΑΣΜΟΣ ΕΝΕΡΓΟΠΟΙΗΘΗΚΕ' => 'ACCOUNT ACTIVATED',
    'Μπορείτε τώρα να συνδεθείτε στον λογαριασμό σας.' => 'You can now sign in to your account.',
    'ΣΥΜΠΛΗΡΩΣΤΕ ΤΑ ΣΤΟΙΧΕΙΑ ΣΑΣ' => 'ENTER YOUR DETAILS',
    'ΟΝΟΜΑ' => 'FIRST NAME',
    'ΕΠΩΝΥΜΟ' => 'LAST NAME',
    'ΚΙΝΗΤΟ ΤΗΛΕΦΩΝΟ' => 'MOBILE PHONE',
    'ΕΠΙΒΕΒΑΙΩΣΗ ΚΩΔΙΚΟΥ' => 'CONFIRM PASSWORD',
    'Εγγραφή στο newsletter' => 'Subscribe to the newsletter',
    'Συμφωνώ με τους' => 'I agree to the',
    'Όρους Χρήσης' => 'Terms of Use',
    'και την' => 'and the',
    'Πολιτική Απορρήτου' => 'Privacy Policy',
    'Έχω ήδη λογαριασμό' => 'I already have an account',
    'ΕΓΓΡΑΦΗ ΜΕΣΩ' => 'REGISTER WITH',
    'Ο λογαριασμός σας δημιουργήθηκε. Ελέγξτε το email σας για οδηγίες ενεργοποίησης του λογαριασμού σας.' => 'Your account has been created. Check your email for activation instructions.',
    'ΔΕΥ' => 'MON',
    'ΤΡΙ' => 'TUE',
    'ΤΕΤ' => 'WED',
    'ΠΕΜ' => 'THU',
    'ΠΑΡ' => 'FRI',
    'ΣΑΒ' => 'SAT',
    'ΚΥΡ' => 'SUN',
];

$content = [
    'Αρχική' => 'Home',
    'Πολιτική απορρήτου' => 'Privacy Policy',
    'Όροι χρήσης' => 'Terms of Use',
    'Πολιτική cookies' => 'Cookie Policy',
    'Εισιτήρια' => 'Tickets',
    'Αγορά εισιτηρίου' => 'Buy Tickets',
    'Ο λογαριασμός μου' => 'My Account',
    'Καλάθι' => 'Cart',
    'Ολοκλήρωση αγοράς' => 'Checkout',
    'Κατάστημα' => 'Shop',
    'Εγγραφή' => 'Register',
    'Σύνδεση' => 'Sign In',
    'Ανάκτηση κωδικού' => 'Password Recovery',
    'Επαναφορά κωδικού' => 'Reset Password',
    'Ενεργοποίηση λογαριασμού' => 'Account Activation',
    'Οι συλλογές μου' => 'My Collections',
    'Τα αγαπημένα μου' => 'My Favourites',
    'Επεξεργασία προφίλ' => 'Edit Profile',
    'Προφίλ χρήστη' => 'User Profile',
    '/agora-eisitiriou/' => '/en/buy-tickets/',
    '/eisitiria/' => '/en/tickets/',
    '/politiki-aporritou/' => '/en/privacy-policy/',
    '/oroi-xrisis/' => '/en/terms-of-use/',
    '/politiki-cookies/' => '/en/cookie-policy/',
    'Το Μεσολόγγι δεν είναι μια πόλη που απλώς επισκέπτεται κανείς· είναι μια εμπειρία που ζει, μια ταυτότητα που μοιράζεται και μια μνήμη που εμπνέει.' => 'Messolonghi is not simply a city to visit; it is an experience to live, an identity to share and a memory that inspires.',
    'Δημοτική Πινακοθήκη (Μουσείο Ιστορίας και Τέχνης)' => 'Municipal Art Gallery (Museum of History and Art)',
    'Μουσείο Οικογένειας Τρικούπη' => 'Trikoupis Family Museum',
    'Πατρογονικό Σπίτι Κωστή Παλαμά' => 'Kostis Palamas Ancestral Home',
    'Μουσείο Αλιείας Αιτωλικού' => 'Aitoliko Fisheries Museum',
    'Κέντρο Χαρακτικών Τεχνών – Μουσείο «Βάσω Κατράκη»' => 'Vasso Katraki Museum – Centre for the Engraving Arts',
    'Ξενοκράτειο Αρχαιολογικό Μουσείο' => 'Xenokrateion Archaeological Museum',
    'Κέντρο Λόγου και Τέχνης – Μουσείο «Διέξοδος»' => 'Diexodos Centre for Literature and Art',
    'Μουσείο Άλατος' => 'Salt Museum',
    'Μουσείο Λόρδου Βύρωνα' => 'Lord Byron Museum',
    'Λαογραφικό Μουσείο Αιτωλικού' => 'Aitoliko Folklore Museum',
    'ΤΑ ΜΟΥΣΕΙΑ ΜΑΣ' => 'OUR MUSEUMS',
    "Επισκεφθείτε\r\nτα μουσεία μας" => "Visit\r\nour museums",
    'Αγορά εισιτηρίων →' => 'Buy tickets →',
    'Ανακαλύψτε τα μουσεία ↓' => 'Discover the museums ↓',
    'ΤΑ ΜΟΥΣΕΙΑ' => 'THE MUSEUMS',
    'Δέκα μουσεία αφηγούνται την ιστορία, την τέχνη, την καθημερινή ζωή και το φυσικό τοπίο του Μεσολογγίου και του Αιτωλικού.' => 'Ten museums tell the story of the history, art, everyday life and natural landscape of Messolonghi and Aitoliko.',
    'ΜΕΣΟΛΟΓΓΙ, ΑΙΤΩΛΙΚΟ' => 'MESSOLONGHI, AITOLIKO',
    'Επίσκεψη σε όλα τα μουσεία' => 'Visit all museums',
    'Με ένα ενιαίο εισιτήριο επισκεφθείτε όλα τα μουσεία του Μεσολογγίου και του Αιτωλικού.' => 'Visit all the museums of Messolonghi and Aitoliko with a single ticket.',
    'Περισσότερα' => 'More',
    'Περισσότερα →' => 'More →',
    '200 ΧΡΟΝΙΑ ΑΠΟ ΤΗΝ ΕΞΟΔΟ' => '200 YEARS SINCE THE EXODUS',
    '1826–2026. Η πόλη που έγραψε ιστορία.' => '1826–2026. The city that made history.',
    'Μάθετε περισσότερα για την ιστορία της πόλης του Μεσολογγίου.' => 'Learn more about the history of Messolonghi.',
    'Εξερευνήστε την →' => 'Explore →',
    'ΜΗ ΧΑΣΕΤΕ' => 'DO NOT MISS',
    'Εμπειρίες που αξίζουν' => 'Experiences worth discovering',
    'Δες εδώ →' => 'View here →',
    'ΠΕΡΙΕΧΟΜΕΝΑ' => 'CONTENTS',
    '1. Υπεύθυνος επεξεργασίας' => '1. Data controller',
    '2. Δεδομένα που συλλέγουμε' => '2. Data we collect',
    '3. Σκοποί και νομικές βάσεις' => '3. Purposes and legal bases',
    '4. Αποδέκτες δεδομένων' => '4. Data recipients',
    '5. Χρόνος διατήρησης' => '5. Retention period',
    '6. Cookies' => '6. Cookies',
    '7. Τα δικαιώματά σας' => '7. Your rights',
    '8. Ασφάλεια' => '8. Security',
    '9. Ενημερώσεις πολιτικής' => '9. Policy updates',
    '10. Επικοινωνία' => '10. Contact',
    'Η παρούσα πολιτική εξηγεί πώς συλλέγονται, χρησιμοποιούνται και προστατεύονται τα προσωπικά δεδομένα κατά την επίσκεψη στον ιστότοπο, την αγορά εισιτηρίων και την επικοινωνία με τα μουσεία του Μεσολογγίου.' => 'This policy explains how personal data is collected, used and protected when you visit the website, purchase tickets or contact the museums of Messolonghi.',
    'Για κάθε ζήτημα που αφορά την επεξεργασία προσωπικών δεδομένων μπορείτε να χρησιμοποιήσετε τα στοιχεία επικοινωνίας που αναφέρονται στο τέλος της σελίδας.' => 'For any matter concerning personal data processing, please use the contact details at the end of this page.',
    '2.1 Δεδομένα που μας παρέχετε' => '2.1 Data you provide',
    'Στοιχεία ταυτοποίησης και επικοινωνίας, όπως ονοματεπώνυμο και email.' => 'Identification and contact details, such as your name and email address.',
    'Στοιχεία παραγγελίας, εισιτηρίων και συναλλαγής.' => 'Order, ticket and transaction details.',
    'Περιεχόμενο αιτημάτων που αποστέλλετε μέσω email ή φόρμας επικοινωνίας.' => 'The content of requests you submit by email or through a contact form.',
    '2.2 Δεδομένα που συλλέγονται αυτόματα' => '2.2 Data collected automatically',
    'Κατά την πλοήγηση μπορεί να καταγράφονται τεχνικές πληροφορίες, όπως διεύθυνση IP, τύπος συσκευής και προγράμματος περιήγησης, ημερομηνία πρόσβασης και βασικά δεδομένα χρήσης.' => 'Technical information may be recorded while you browse, including your IP address, device and browser type, access date and basic usage data.',
    'Τα δεδομένα χρησιμοποιούνται μόνο όταν υπάρχει κατάλληλη νομική βάση και για συγκεκριμένους σκοπούς.' => 'Data is used only for specific purposes and where an appropriate legal basis exists.',
    'Πρόσβαση στα δεδομένα έχουν μόνο εξουσιοδοτημένα πρόσωπα και συνεργάτες που είναι απαραίτητοι για τη λειτουργία των υπηρεσιών, όπως πάροχοι φιλοξενίας, τεχνικής υποστήριξης και πληρωμών. Οι συνεργάτες δεσμεύονται από κατάλληλες υποχρεώσεις εμπιστευτικότητας και προστασίας δεδομένων.' => 'Data may be accessed only by authorised personnel and partners required to operate the services, such as hosting, technical support and payment providers. These partners are bound by appropriate confidentiality and data-protection obligations.',
    'Τα προσωπικά δεδομένα διατηρούνται μόνο για όσο απαιτείται από τον σκοπό συλλογής τους και από τις σχετικές νομικές, φορολογικές ή λογιστικές υποχρεώσεις. Μετά το πέρας της περιόδου διατήρησης διαγράφονται ή ανωνυμοποιούνται με ασφαλή τρόπο.' => 'Personal data is retained only for as long as required for the purpose for which it was collected and by applicable legal, tax or accounting obligations. It is then securely deleted or anonymised.',
    'Ο ιστότοπος μπορεί να χρησιμοποιεί απολύτως απαραίτητα cookies για τη βασική λειτουργία του και, εφόσον δοθεί συγκατάθεση, προαιρετικά cookies για στατιστικούς ή άλλους σκοπούς.' => 'The website may use strictly necessary cookies for its basic operation and, with your consent, optional cookies for analytics or other purposes.',
    'Μπορείτε να αλλάξετε τις προτιμήσεις σας για τα προαιρετικά cookies ανά πάσα στιγμή μέσω του διαθέσιμου εργαλείου διαχείρισης συγκατάθεσης.' => 'You can change your optional cookie preferences at any time using the consent-management tool.',
    'Ανάλογα με την περίπτωση και την ισχύουσα νομοθεσία, μπορείτε να ζητήσετε:' => 'Depending on the circumstances and applicable law, you may request:',
    'πρόσβαση στα προσωπικά δεδομένα που σας αφορούν,' => 'access to your personal data,',
    'διόρθωση ανακριβών ή ελλιπών δεδομένων,' => 'correction of inaccurate or incomplete data,',
    'διαγραφή ή περιορισμό της επεξεργασίας,' => 'erasure or restriction of processing,',
    'φορητότητα των δεδομένων σας,' => 'portability of your data,',
    'εναντίωση σε συγκεκριμένη επεξεργασία ή ανάκληση της συγκατάθεσής σας.' => 'objection to specific processing or withdrawal of your consent.',
    'Έχετε επίσης δικαίωμα να απευθυνθείτε στην αρμόδια εποπτική αρχή, αν θεωρείτε ότι η επεξεργασία παραβιάζει την ισχύουσα νομοθεσία.' => 'You also have the right to contact the competent supervisory authority if you believe that processing infringes applicable law.',
    'Εφαρμόζονται κατάλληλα τεχνικά και οργανωτικά μέτρα για την προστασία των δεδομένων από απώλεια, μη εξουσιοδοτημένη πρόσβαση, αλλοίωση ή γνωστοποίηση.' => 'Appropriate technical and organisational measures are used to protect data against loss, unauthorised access, alteration or disclosure.',
    'Η πολιτική μπορεί να ενημερώνεται ώστε να αντανακλά αλλαγές στις υπηρεσίες ή στο κανονιστικό πλαίσιο. Η νεότερη έκδοση δημοσιεύεται στην παρούσα σελίδα.' => 'This policy may be updated to reflect changes to the services or regulatory framework. The latest version is published on this page.',
    'Για την άσκηση δικαιώματος ή για οποιαδήποτε ερώτηση σχετικά με την πολιτική απορρήτου, επικοινωνήστε στο privacy@messolonghi-museums.gr.' => 'To exercise a right or ask a question about this privacy policy, contact privacy@messolonghi-museums.gr.',
    'Για την άσκηση δικαιώματος ή για οποιαδήποτε ερώτηση σχετικά με την πολιτική απορρήτου, επικοινωνήστε στο privacy@messolonghi-museums.gr' => 'To exercise a right or ask a question about this privacy policy, contact privacy@messolonghi-museums.gr',
    'Για την άσκηση δικαιώματος ή για οποιαδήποτε ερώτηση σχετικά με την πολιτική απορρήτου, επικοινωνήστε στο ' => 'To exercise a right or ask a question about this privacy policy, contact ',
    'Σκοπός' => 'Purpose',
    'Ενδεικτικά δεδομένα' => 'Example data',
    'Νομική βάση' => 'Legal basis',
    'Έκδοση και διαχείριση εισιτηρίου' => 'Ticket issue and management',
    'Στοιχεία επικοινωνίας και παραγγελίας' => 'Contact and order details',
    'Εκτέλεση σύμβασης' => 'Performance of a contract',
    'Απάντηση σε αίτημα' => 'Responding to a request',
    'Στοιχεία επικοινωνίας και μήνυμα' => 'Contact details and message',
    'Έννομο συμφέρον ή συγκατάθεση' => 'Legitimate interest or consent',
    'Ασφάλεια ιστοτόπου' => 'Website security',
    'Τεχνικά αρχεία καταγραφής' => 'Technical logs',
    'Έννομο συμφέρον' => 'Legitimate interest',
    '1. Πεδίο εφαρμογής' => '1. Scope',
    '2. Χρήση των υπηρεσιών' => '2. Use of services',
    '3. Ηλεκτρονικά εισιτήρια' => '3. Electronic tickets',
    '4. Τιμές και πληρωμές' => '4. Prices and payments',
    '5. Κανόνες επίσκεψης' => '5. Visiting rules',
    '6. Πνευματικά δικαιώματα' => '6. Intellectual property',
    '7. Περιορισμός ευθύνης' => '7. Limitation of liability',
    '8. Αλλαγές στους όρους' => '8. Changes to these terms',
    '9. Επικοινωνία' => '9. Contact',
    'Οι παρόντες όροι διέπουν την πρόσβαση και τη χρήση του ιστοτόπου, την έκδοση ηλεκτρονικών εισιτηρίων και τις υπηρεσίες που παρέχονται για την επίσκεψη στα μουσεία του Μεσολογγίου.' => 'These terms govern access to and use of the website, the issue of electronic tickets and the services provided for visits to the museums of Messolonghi.',
    'Με τη χρήση του ιστοτόπου δηλώνετε ότι έχετε διαβάσει και αποδέχεστε τους παρόντες όρους. Αν δεν συμφωνείτε με κάποιον από αυτούς, παρακαλείστε να μη χρησιμοποιήσετε τις σχετικές υπηρεσίες.' => 'By using the website, you confirm that you have read and accept these terms. If you disagree with any of them, please do not use the relevant services.',
    '2.1 Υποχρεώσεις επισκέπτη' => '2.1 Visitor obligations',
    'Ο επισκέπτης οφείλει να παρέχει ακριβή και πλήρη στοιχεία όπου αυτά ζητούνται και να χρησιμοποιεί τον ιστότοπο αποκλειστικά για νόμιμους σκοπούς.' => 'Visitors must provide accurate and complete information where requested and use the website only for lawful purposes.',
    'Δεν επιτρέπεται η απόπειρα μη εξουσιοδοτημένης πρόσβασης σε συστήματα ή δεδομένα.' => 'Attempts to gain unauthorised access to systems or data are prohibited.',
    'Δεν επιτρέπεται η χρήση αυτοματοποιημένων μέσων που επηρεάζουν τη λειτουργία του ιστοτόπου.' => 'Automated means that interfere with the operation of the website may not be used.',
    'Τα στοιχεία επικοινωνίας που δηλώνονται κατά την αγορά πρέπει να είναι έγκυρα.' => 'Contact details provided during purchase must be valid.',
    '2.2 Διαθεσιμότητα' => '2.2 Availability',
    'Καταβάλλεται κάθε εύλογη προσπάθεια ώστε οι ψηφιακές υπηρεσίες να παραμένουν διαθέσιμες. Ενδέχεται, ωστόσο, να διακόπτονται προσωρινά για συντήρηση, αναβάθμιση ή λόγους ανωτέρας βίας.' => 'Every reasonable effort is made to keep digital services available. They may, however, be temporarily interrupted for maintenance, upgrades or events beyond our control.',
    '3.1 Έκδοση και ισχύς' => '3.1 Issue and validity',
    'Το εισιτήριο ισχύει αποκλειστικά για το μουσείο, την ημερομηνία και τη ζώνη ώρας που αναγράφονται σε αυτό. Το QR code είναι μοναδικό και δεν πρέπει να κοινοποιείται.' => 'A ticket is valid only for the museum, date and time slot shown on it. Its QR code is unique and must not be shared.',
    'Επιλέξτε μουσείο, ημερομηνία και διαθέσιμη ώρα.' => 'Select a museum, date and available time.',
    'Επιλέξτε τη σωστή κατηγορία εισιτηρίου.' => 'Select the correct ticket category.',
    'Ολοκληρώστε την πληρωμή και κρατήστε το email επιβεβαίωσης.' => 'Complete payment and keep the confirmation email.',
    'Το εισιτήριο μπορεί να παρουσιαστεί από κινητή συσκευή ή εκτυπωμένο. Για μειωμένη ή δωρεάν είσοδο ενδέχεται να ζητηθεί έγκυρο δικαιολογητικό.' => 'Tickets may be presented on a mobile device or in print. Valid supporting documentation may be requested for reduced or free admission.',
    'Οι διαθέσιμες κατηγορίες και οι αντίστοιχες τιμές εμφανίζονται πριν από την ολοκλήρωση της παραγγελίας. Η συναλλαγή ολοκληρώνεται μετά την επιτυχή έγκρισή της από τον πάροχο πληρωμών.' => 'Available categories and prices are shown before checkout. The transaction is completed once it has been approved by the payment provider.',
    'Κατά την παραμονή στους χώρους των μουσείων οι επισκέπτες ακολουθούν τις υποδείξεις του προσωπικού και τη σχετική σήμανση.' => 'While on museum premises, visitors must follow staff instructions and posted signage.',
    'Δεν επιτρέπεται η επαφή με τα εκθέματα, εκτός αν υπάρχει ρητή σχετική ένδειξη.' => 'Exhibits must not be touched unless expressly permitted.',
    'Η φωτογράφιση ή βιντεοσκόπηση μπορεί να περιορίζεται σε συγκεκριμένους χώρους.' => 'Photography or filming may be restricted in certain areas.',
    'Τσάντες μεγάλου μεγέθους ενδέχεται να φυλάσσονται σε υποδεικνυόμενο χώρο.' => 'Large bags may need to be left in a designated area.',
    'Το περιεχόμενο του ιστοτόπου, συμπεριλαμβανομένων κειμένων, φωτογραφιών, γραφικών και σημάτων, προστατεύεται από την ισχύουσα νομοθεσία. Η αναπαραγωγή ή εμπορική αξιοποίησή του χωρίς προηγούμενη γραπτή άδεια απαγορεύεται.' => 'Website content, including text, photographs, graphics and marks, is protected by applicable law. Reproduction or commercial use without prior written permission is prohibited.',
    'Οι πληροφορίες παρέχονται με στόχο να είναι ακριβείς και ενημερωμένες. Δεν παρέχεται εγγύηση ότι ο ιστότοπος θα λειτουργεί αδιάλειπτα ή χωρίς τεχνικά σφάλματα.' => 'Information is provided with the aim of being accurate and current. The website is not guaranteed to operate continuously or without technical errors.',
    'Οι όροι μπορούν να αναθεωρούνται όταν αλλάζουν οι υπηρεσίες ή το ισχύον κανονιστικό πλαίσιο. Η ενημερωμένη έκδοση δημοσιεύεται στην παρούσα σελίδα με την αντίστοιχη ημερομηνία ισχύος.' => 'These terms may be revised when the services or applicable regulatory framework change. The updated version and its effective date will be published on this page.',
    'Για ερωτήματα σχετικά με τους όρους χρήσης ή τα εισιτήρια, επικοινωνήστε μαζί μας στο info@messolonghi-museums.gr.' => 'For questions about these terms or tickets, contact us at info@messolonghi-museums.gr.',
    'Για ερωτήματα σχετικά με τους όρους χρήσης ή τα εισιτήρια, επικοινωνήστε μαζί μας στο info@messolonghi-museums.gr' => 'For questions about these terms or tickets, contact us at info@messolonghi-museums.gr',
    'Για ερωτήματα σχετικά με τους όρους χρήσης ή τα εισιτήρια, επικοινωνήστε μαζί μας στο ' => 'For questions about these terms or tickets, contact us at ',
    'Κατηγορία' => 'Category',
    'Τιμή' => 'Price',
    'Δικαιολογητικό' => 'Supporting document',
    'Γενική είσοδος' => 'General admission',
    'Δεν απαιτείται' => 'Not required',
    'Μειωμένη είσοδος' => 'Reduced admission',
    'Απαιτείται' => 'Required',
    'Δωρεάν είσοδος' => 'Free admission',
];

$museum_data = [
    77 => [
        'title' => 'Municipal Art Gallery (Museum of History and Art)',
        'slug' => 'municipal-art-gallery-museum-history-art',
        'excerpt' => 'The Municipal Art Gallery is housed in the former town hall and presents the history, art and records of the Exodus of Messolonghi.',
        'content' => "The Municipal Art Gallery, on Markos Botsaris central square, occupies a neoclassical building constructed in 1932 during the mayoralty of Christos Evangelatos and formerly used as the town hall.\n\nThe museum houses a rich collection of original paintings and copies depicting scenes from the Exodus of Messolonghi, portraits of Philhellenes and Greek military leaders, original 1837 engravings by the English artist Friedel, weapons from 1826, coins and medals.\n\nThe ground floor presents exhibits dedicated to Lord Byron, including personal objects, manuscripts and artworks connected with his presence in Greece and Messolonghi. Highlights include the original poem Childe Harold’s Pilgrimage, copies of important paintings and the model for the poet’s statue.\n\nOn the first floor, visitors encounter works inspired by the War of Independence, portraits of military leaders, a copy of Delacroix’s Greece on the Ruins of Missolonghi and records reflecting the identity and history of the Sacred City.",
        'meta' => [
            'place_label' => 'MESSOLONGHI',
            'address' => 'Municipal Museum of History and Art, Mpotsari Square, Messolonghi, Greece',
            'opening_hours' => 'Daily 09:00–14:00 and 15:00–19:00, except Monday.',
            'features_0_title' => 'The Exodus through art',
            'features_0_text' => 'Paintings, copies and artworks inspired by the siege and the Exodus.',
            'features_1_title' => 'Philhellenes and military leaders',
            'features_1_text' => 'Portraits, engravings, weapons, coins and historical records.',
            'features_2_title' => 'Tribute to Lord Byron',
            'features_2_text' => 'Objects, manuscripts and artworks connected with Byron and Messolonghi.',
        ],
    ],
    80 => [
        'title' => 'Trikoupis Family Museum',
        'slug' => 'trikoupis-family-museum',
        'excerpt' => 'The two-storey Trikoupis family mansion presents keepsakes, personal objects and aspects of nineteenth-century urban life.',
        'content' => "The museum occupies a two-storey stone mansion built in the 1850s in the old Agios Spyridon neighbourhood, after the original building was damaged. Among those who lived here were Spyridon Trikoupis and his son Charilaos Trikoupis.\n\nThe museum includes Trikoupis family keepsakes, personal objects and photographs. Rooms on the first floor recreate a nineteenth-century urban home, including bedrooms, a dining room and offices decorated with family portraits, furniture and objects.",
        'meta' => [
            'place_label' => 'MESSOLONGHI',
            'address' => 'Trikoupis Family Museum, Charilaou Trikoupi Street, Messolonghi, Greece',
            'opening_hours' => 'Daily 09:00–15:00 and 15:00–19:00, except Monday.',
            'features_0_title' => 'The Trikoupis family',
            'features_0_text' => 'Keepsakes, personal objects and photographs from a family with a decisive role in Greek history.',
            'features_1_title' => 'A nineteenth-century urban home',
            'features_1_text' => 'Recreated rooms, furniture, portraits and period objects.',
            'features_2_title' => 'Charilaos Trikoupis',
            'features_2_text' => 'Digital copies of documents, photographs and engravings illuminating his work.',
        ],
    ],
    83 => [
        'title' => 'Kostis Palamas Ancestral Home',
        'slug' => 'kostis-palamas-ancestral-home',
        'excerpt' => 'The Palamas family’s ancestral home tells the story of the family and its connection with the national poet Kostis Palamas.',
        'content' => "The museum is housed in the Palamas family’s stone mansion, opposite the Trikoupis residence. Panagiotis Palamas, forefather of the family and a distinguished teacher, was born here in 1722. Greece’s national poet Kostis Palamas also lived in this house for many years.\n\nThe museum displays objects relating to his life and work, printed and photographic material, personal possessions and household items.",
        'meta' => [
            'place_label' => 'MESSOLONGHI',
            'address' => '10 Spondi Street, Messolonghi, Greece',
            'opening_hours' => 'Daily 09:00–15:00 and 15:00–19:00, except Monday.',
            'features_0_title' => 'The Palamas family mansion',
            'features_0_text' => 'A stone-built place of memory in the heart of Messolonghi.',
            'features_1_title' => 'Life and work',
            'features_1_text' => 'Printed material, photographs and personal objects connected with Kostis Palamas.',
            'features_2_title' => 'The city of poets',
            'features_2_text' => 'A stop connecting literary history with the city’s everyday life.',
        ],
    ],
    86 => [
        'title' => 'Aitoliko Fisheries Museum',
        'slug' => 'aitoliko-fisheries-museum',
        'excerpt' => 'The Aitoliko Fisheries Museum presents traditional fishing, fishermen’s tools and local life’s relationship with the lagoon.',
        'content' => "The Aitoliko Fisheries Museum is dedicated to traditional fishing and the close relationship between local residents and the Messolonghi–Aitoliko lagoon.\n\nExhibits, photographs and audiovisual material present the history of fishing activity, traditional tools and techniques, and the region’s rich wetland. The museum highlights the area’s cultural heritage and the importance of protecting its natural environment.",
        'meta' => [
            'place_label' => 'AITOLIKO',
            'address' => 'Aitoliko Fisheries Museum, Konstantinou Laskari Street, Aitoliko, Greece',
            'features_0_title' => 'Traditional fishing',
            'features_0_text' => 'Tools, techniques and stories of Aitoliko’s fishermen.',
            'features_1_title' => 'The lagoon',
            'features_1_text' => 'The relationship between water, the wetland and everyday life.',
            'features_2_title' => 'Audiovisual material',
            'features_2_text' => 'Photographs and records that bring fishing activity to life.',
        ],
    ],
    89 => [
        'title' => 'Vasso Katraki Museum – Centre for the Engraving Arts',
        'slug' => 'vasso-katraki-museum',
        'excerpt' => 'In Aitoliko, the Vasso Katraki Museum presents a permanent collection of engravings, plates, sketches and the major printmaker’s studio.',
        'content' => "The Vasso Katraki Museum – Centre for the Engraving Arts opened on the island of Aitoliko in the summer of 2006. Located on the town’s eastern side, it is the only museum in Greece and Europe devoted exclusively to the art of printmaking.\n\nThe museum presents a permanent collection of the Greek artist Vasso Katraki’s wood and stone engravings from across her career. The artist bequeathed the plates and preparatory sketches of her works to her birthplace. Her studio, library and a rich photographic archive are also housed here.",
        'meta' => [
            'place_label' => 'AITOLIKO',
            'address' => 'Vasso Katraki Museum, Andrea Syngrou Street, Aitoliko, Greece',
            'opening_hours' => 'Weekdays and weekends 09:00–15:00.',
            'features_0_title' => 'The art of printmaking',
            'features_0_text' => 'A unique museum experience devoted to engraving.',
            'features_1_title' => 'Vasso Katraki',
            'features_1_text' => 'Works, plates, sketches and material from her artistic career.',
            'features_2_title' => 'Studio and library',
            'features_2_text' => 'The artist’s world through personal and professional records.',
        ],
    ],
    127 => [
        'title' => 'Visit All Museums',
        'slug' => 'visit-all-museums',
        'excerpt' => 'One ticket for all the museums of Messolonghi and Aitoliko.',
        'content' => "With one ticket, you can plan a complete cultural route through Messolonghi and Aitoliko, visiting places devoted to history, art, poetry, folklore, archaeology and the natural environment.\n\nThe visit includes the Municipal Art Gallery (Museum of History and Art), Kostis Palamas Ancestral Home, Trikoupis Family Museum, Xenokrateion Archaeological Museum, Diexodos Centre for Literature and Art, Salt Museum, Lord Byron Museum, Vasso Katraki Museum, Aitoliko Fisheries Museum and Aitoliko Folklore Museum.\n\nIt is an easy way to discover the region’s identity through different stories: the Exodus and Philhellenism, leading families and poets, engraving, archaeology, fishing, salt pans and everyday life around the lagoon.",
        'meta' => [ 'place_label' => 'MESSOLONGHI, AITOLIKO' ],
    ],
    143 => [
        'title' => 'Xenokrateion Archaeological Museum',
        'slug' => 'xenokrateion-archaeological-museum',
        'excerpt' => 'The Xenokrateion occupies a historic neoclassical building and presents more than 1,200 archaeological objects from Aetolia-Acarnania.',
        'content' => "The Xenokrateion Archaeological Museum is housed in a historic neoclassical building constructed between 1885 and 1889 as a municipal girls’ school, with a donation from Konstantinos Xenokratis, a national benefactor from Eastern Thrace.\n\nIts permanent exhibition presents more than 1,200 objects spanning the prehistoric to late Roman periods and originating throughout Aetolia-Acarnania. Video projections and multimedia applications complement the experience, connecting the historic building with contemporary museum storytelling.",
        'meta' => [
            'place_label' => 'MESSOLONGHI',
            'address' => 'Xenokrateion Archaeological Museum, Asimaki Fotila Street, Messolonghi, Greece',
            'opening_hours' => 'Daily 08:30–15:30, except Tuesday.',
        ],
    ],
    146 => [
        'title' => 'Diexodos Centre for Literature and Art',
        'slug' => 'diexodos-centre-literature-art',
        'excerpt' => 'Diexodos occupies the ancestral home of Athanasios Razi-Kotsikas and hosts historical relics, art and records of the Greek Revolution.',
        'content' => "Since 1999, the Diexodos Centre for Literature and Art has operated in the Sacred City of Messolonghi. It is housed in the ancestral home of Athanasios Razi-Kotsikas, commander of the Messolonghi Guard during the heroic Exodus of 1826.\n\nThe pre-revolutionary mansion was transformed into a cultural centre at the heart of the place that inspired Dionysios Solomos’s Hymn to Liberty. Its permanent collection includes relics of the 1821 Revolution, personal objects of Athanasios Razi-Kotsikas, weapons, jewellery, costumes, period newspapers, Philhellenic objects and engravings of Lord Byron. The gallery hosts human-centred works by Greek and international artists from 1875 to the present.",
        'meta' => [
            'place_label' => 'MESSOLONGHI',
            'address' => 'Diexodos Centre for Literature and Art, Athanasiou Razi-Kotsika Street, Messolonghi, Greece',
            'opening_hours' => 'Friday, Saturday and Sunday 11:00–13:30. Group visits by appointment.',
            'features_0_title' => 'Historical relics',
            'features_0_text' => 'Objects from the Revolution and personal records of leading figures in the struggle.',
            'features_1_title' => 'The Razi-Kotsikas home',
            'features_1_text' => 'A pre-revolutionary mansion with a powerful connection to memory.',
            'features_2_title' => 'Gallery and culture',
            'features_2_text' => 'Artworks, literature and cultural events in the historic heart of the city.',
        ],
    ],
    149 => [
        'title' => 'Salt Museum',
        'slug' => 'salt-museum',
        'excerpt' => 'The Salt Museum tells the story of salt and its relationship with salt pans, the economy, the environment, art and everyday life.',
        'content' => "The Salt Museum of Messolonghi occupies a historic saltworks building constructed in 1946 to house salt workers. Once known as the “Chamber”, the building was restored and transformed into a thematic museum devoted to one of the most important commodities in human history.\n\nVisitors discover the history of salt, its different types, colours and forms, its cultivation and production in the Messolonghi salt pans, and its influence on the economy, environment, art and religion. The museum also presents a unique collection of 1,800 salt cellars. Its quality and originality have received major awards in Greece and abroad.",
        'meta' => [
            'place_label' => 'MESSOLONGHI',
            'address' => 'Salt Museum, Messolonghi, Greece',
            'opening_hours' => 'Wednesday to Sunday, 10:00–17:00, throughout the year.',
            'features_0_title' => 'The history of salt',
            'features_0_text' => 'From the birth of the planet to its modern uses.',
            'features_1_title' => 'Messolonghi salt pans',
            'features_1_text' => 'The production process and the world of the salt workers.',
            'features_2_title' => 'Salt-cellar collection',
            'features_2_text' => 'An impressive collection of 1,800 objects devoted to an everyday commodity.',
        ],
    ],
    152 => [
        'title' => 'Lord Byron Museum',
        'slug' => 'lord-byron-museum',
        'excerpt' => 'The Lord Byron and Philhellenism Museum presents the life, work and memory of the poet whose name is closely linked with Messolonghi.',
        'content' => "The Messolonghi Byron Society was founded in 1991 and created Greece’s only International Research Centre for Lord Byron and Philhellenism. The centre is especially significant because it is based in Sacred Messolonghi, where Lord Byron died on 19 April 1824.\n\nThe permanent exhibition is devoted to Byron’s life and work, his early years, travels in Greece, connection with the European Philhellenic movement and memory through art. Documents, reproductions, historical material and stories connect Romanticism, Philhellenism and the history of the Exodus.",
        'meta' => [
            'place_label' => 'MESSOLONGHI',
            'address' => 'Lord Byron Museum, Messolonghi, Greece',
            'features_0_title' => 'Lord Byron and Messolonghi',
            'features_0_text' => 'The poet’s life, actions and final chapter in the Sacred City.',
            'features_1_title' => 'Philhellenism',
            'features_1_text' => 'The European dimension of the struggle and Byron’s influence.',
            'features_2_title' => 'Research and education',
            'features_2_text' => 'A library, records and programmes that keep Byron’s memory alive.',
        ],
    ],
    161 => [
        'title' => 'Aitoliko Folklore Museum',
        'slug' => 'aitoliko-folklore-museum',
        'excerpt' => 'The Aitoliko Folklore Museum occupies a restored stone building and presents objects from everyday life, fishing and local tradition.',
        'content' => "The Aitoliko Folklore Museum occupies a restored stone building at the entrance to Aitoliko that once served as an olive press.\n\nIts three rooms contain exhibits from everyday life in Aitoliko over the previous two centuries, including household objects, furniture, a loom and fishermen’s tools. A specially designed area presents elements of lagoon life, including the façade of a traditional pelada stilt house, a small pile-built house and a gaita boat, along with local costumes.",
        'meta' => [
            'place_label' => 'AITOLIKO',
            'address' => 'Aitoliko Folklore Museum, Konstantinou Laskari Street, Aitoliko, Greece',
            'opening_hours' => 'Visits by appointment.',
            'features_0_title' => 'Everyday life',
            'features_0_text' => 'Household objects, furniture and tools from Aitoliko’s history.',
            'features_1_title' => 'The lagoon and pelades',
            'features_1_text' => 'Displays and elements of the region’s distinctive fishing tradition.',
            'features_2_title' => 'Local costumes',
            'features_2_text' => 'Clothing that connects folklore with social memory.',
        ],
    ],
];

$page_data = [
    3 => [ 'title' => 'Privacy Policy', 'slug' => 'privacy-policy' ],
    5 => [ 'title' => 'Shop', 'slug' => 'shop' ],
    6 => [ 'title' => 'Cart', 'slug' => 'cart' ],
    7 => [ 'title' => 'Checkout', 'slug' => 'checkout' ],
    8 => [ 'title' => 'My Account', 'slug' => 'my-account' ],
    10 => [ 'title' => 'Register', 'slug' => 'register' ],
    11 => [ 'title' => 'Sign In', 'slug' => 'sign-in' ],
    12 => [ 'title' => 'Password Recovery', 'slug' => 'password-recovery' ],
    13 => [ 'title' => 'Reset Password', 'slug' => 'reset-password' ],
    14 => [ 'title' => 'Account Activation', 'slug' => 'account-activation' ],
    15 => [ 'title' => 'My Collections', 'slug' => 'my-collections' ],
    16 => [ 'title' => 'My Favourites', 'slug' => 'my-favourites' ],
    17 => [ 'title' => 'Edit Profile', 'slug' => 'edit-profile' ],
    18 => [ 'title' => 'User Profile', 'slug' => 'user-profile' ],
    20 => [ 'title' => 'Home', 'slug' => 'home' ],
    24 => [ 'title' => 'Terms of Use', 'slug' => 'terms-of-use' ],
    25 => [ 'title' => 'Cookie Policy', 'slug' => 'cookie-policy' ],
    32 => [ 'title' => 'Tickets', 'slug' => 'tickets' ],
    33 => [ 'title' => 'Buy Tickets', 'slug' => 'buy-tickets' ],
];

function com_en_register_translation( string $context, string $name, string $original, string $translation ): void {
    $string_id = icl_register_string( $context, $name, $original, false, 'el' );
    if ( $string_id ) {
        $status = defined( 'ICL_STRING_TRANSLATION_COMPLETE' ) ? ICL_STRING_TRANSLATION_COMPLETE : 10;
        icl_add_string_translation( $string_id, 'en', $translation, $status );
    }
}

function com_en_translate_value( $value, array $translations, array $id_map = [] ) {
    if ( is_string( $value ) ) {
        return $translations[ $value ] ?? strtr( $value, $translations );
    }
    if ( is_int( $value ) && isset( $id_map[ $value ] ) ) {
        return $id_map[ $value ];
    }
    if ( is_array( $value ) ) {
        foreach ( $value as $key => $item ) {
            $value[ $key ] = com_en_translate_value( $item, $translations, $id_map );
        }
    }
    return $value;
}

function com_en_translate_blocks( string $post_content, array $translations, array $id_map = [] ): string {
    if ( ! has_blocks( $post_content ) ) {
        return strtr( $post_content, $translations );
    }

    $translate_block = static function ( array $block ) use ( &$translate_block, $translations, $id_map ): array {
        $block['attrs'] = com_en_translate_value( $block['attrs'] ?? [], $translations, $id_map );
        if ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = array_map( $translate_block, $block['innerBlocks'] );
        }
        if ( ! empty( $block['innerContent'] ) ) {
            $block['innerContent'] = array_map(
                static fn ( $chunk ) => is_string( $chunk ) ? strtr( $chunk, $translations ) : $chunk,
                $block['innerContent']
            );
        }
        if ( isset( $block['innerHTML'] ) ) {
            $block['innerHTML'] = strtr( $block['innerHTML'], $translations );
        }
        return $block;
    };

    return serialize_blocks( array_map( $translate_block, parse_blocks( $post_content ) ) );
}

function com_en_source_language_details( int $post_id, string $post_type ): array {
    $details = apply_filters(
        'wpml_element_language_details',
        null,
        [ 'element_id' => $post_id, 'element_type' => 'post_' . $post_type ]
    );

    if ( ! is_object( $details ) || empty( $details->trid ) ) {
        do_action(
            'wpml_set_element_language_details',
            [
                'element_id' => $post_id,
                'element_type' => 'post_' . $post_type,
                'trid' => false,
                'language_code' => 'el',
                'source_language_code' => null,
            ]
        );
        $details = apply_filters(
            'wpml_element_language_details',
            null,
            [ 'element_id' => $post_id, 'element_type' => 'post_' . $post_type ]
        );
    }

    return [
        'trid' => (int) $details->trid,
        'element_type' => 'post_' . $post_type,
    ];
}

function com_en_upsert_post_translation( int $source_id, array $data, array $translations, array $id_map = [] ): int {
    $source = get_post( $source_id );
    if ( ! $source ) {
        return 0;
    }

    $language = com_en_source_language_details( $source_id, $source->post_type );
    global $wpdb;
    $translations_table = $wpdb->prefix . 'icl_translations';
    $translated_id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT element_id
             FROM {$translations_table}
             WHERE trid = %d
               AND element_type = %s
               AND language_code = 'en'
             LIMIT 1",
            $language['trid'],
            $language['element_type']
        )
    );

    $postarr = [
        'ID' => $translated_id,
        'post_type' => $source->post_type,
        'post_status' => $source->post_status,
        'post_author' => $source->post_author,
        'post_title' => $data['title'],
        'post_name' => $data['slug'],
        'post_excerpt' => $data['excerpt'] ?? strtr( $source->post_excerpt, $translations ),
        'post_content' => $data['content'] ?? com_en_translate_blocks( $source->post_content, $translations, $id_map ),
        'post_parent' => 0,
        'menu_order' => $source->menu_order,
        'comment_status' => $source->comment_status,
        'ping_status' => $source->ping_status,
    ];

    $translated_id = wp_insert_post( wp_slash( $postarr ), true );
    if ( is_wp_error( $translated_id ) ) {
        WP_CLI::warning( $translated_id->get_error_message() );
        return 0;
    }

    do_action(
        'wpml_set_element_language_details',
        [
            'element_id' => $translated_id,
            'element_type' => $language['element_type'],
            'trid' => $language['trid'],
            'language_code' => 'en',
            'source_language_code' => 'el',
        ]
    );

    $skip_meta = [ '_edit_lock', '_edit_last', '_wp_old_slug' ];
    foreach ( get_post_meta( $source_id ) as $meta_key => $values ) {
        if ( in_array( $meta_key, $skip_meta, true ) ) {
            continue;
        }
        delete_post_meta( $translated_id, $meta_key );
        foreach ( $values as $raw_value ) {
            $value = maybe_unserialize( $raw_value );
            $value = com_en_translate_value( $value, $translations, $id_map );
            add_post_meta( $translated_id, $meta_key, $value );
        }
    }

    if ( has_post_thumbnail( $source_id ) ) {
        set_post_thumbnail( $translated_id, get_post_thumbnail_id( $source_id ) );
    }

    foreach ( $data['meta'] ?? [] as $meta_key => $meta_value ) {
        update_post_meta( $translated_id, $meta_key, $meta_value );
    }

    return (int) $translated_id;
}

$settings = [
    'footer_info_text' => 'Messolonghi is not simply a city to visit; it is an experience to live, an identity to share and a memory that inspires.',
    'footer_copyright_text' => '© Copyright Messolonghi. All rights reserved.',
    'footer_credit_text' => 'created by INTERWEAVE',
];
if ( in_array( $phase, [ 'all', 'strings' ], true ) ) {
    foreach ( $ui as $original => $translation ) {
        com_en_register_translation( 'com-theme', md5( $original ), $original, $translation );
    }

    foreach ( $settings as $name => $translation ) {
        $original = (string) get_field( $name, 'option' );
        if ( $original !== '' ) {
            com_en_register_translation( 'COM Theme Settings', $name, $original, $translation );
        }
    }

    foreach ( [ 'main', 'copyright', 'footer' ] as $location ) {
        foreach ( com_theme_get_menu_items( $location ) as $item ) {
            $translated_title = $ui[ $item->title ] ?? ( $content[ $item->title ] ?? $item->title );
            com_en_register_translation( 'COM Theme Menus', $location . '-item-' . $item->ID, $item->title, $translated_title );
        }
    }

    $site_identity = [
        'blogname' => 'Municipality of the Sacred City of Messolonghi / Tickets',
        'blogdescription' => 'Museums and tickets in Messolonghi and Aitoliko',
    ];
    foreach ( $site_identity as $option_name => $translation ) {
        $original = (string) get_option( $option_name );
        if ( $original !== '' ) {
            com_en_register_translation( 'COM Site Identity', $option_name, $original, $translation );
        }
    }

    foreach ( $museum_data as $museum_id => $museum ) {
        foreach ( $museum['meta'] ?? [] as $field_name => $translation ) {
            $original = get_post_meta( $museum_id, $field_name, true );
            if ( is_string( $original ) && $original !== '' && $original !== $translation ) {
                com_en_register_translation(
                    'COM Museum Fields',
                    'museum-' . $museum_id . '-' . $field_name,
                    $original,
                    (string) $translation
                );
            }
        }
    }

    global $wpdb;
    $obsolete_ids = $wpdb->get_col(
        "SELECT id FROM {$wpdb->prefix}icl_strings
         WHERE context = 'com-theme'
           AND name REGEXP '^theme-[a-f0-9]{32}$'"
    );
    if ( $obsolete_ids ) {
        $id_list = implode( ',', array_map( 'absint', $obsolete_ids ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id IN ({$id_list})" );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}icl_strings WHERE id IN ({$id_list})" );
    }
}

$page_id_map = [];
if ( in_array( $phase, [ 'all', 'pages' ], true ) ) {
    foreach ( $page_data as $source_id => $data ) {
        $page_id_map[ $source_id ] = com_en_upsert_post_translation( $source_id, $data, $content );
        if ( function_exists( 'gc_collect_cycles' ) ) {
            gc_collect_cycles();
        }
    }
}

$museum_id_map = [];
if ( in_array( $phase, [ 'all', 'museums' ], true ) ) {
    foreach ( $museum_data as $source_id => $data ) {
        $museum_id_map[ $source_id ] = com_en_upsert_post_translation( $source_id, $data, $content );
        if ( function_exists( 'gc_collect_cycles' ) ) {
            gc_collect_cycles();
        }
    }
}

if ( $phase === 'final' ) {
    foreach ( array_keys( $page_data ) as $source_id ) {
        $page_id_map[ $source_id ] = (int) apply_filters( 'wpml_object_id', $source_id, 'page', false, 'en' );
    }
    foreach ( array_keys( $museum_data ) as $source_id ) {
        $museum_id_map[ $source_id ] = (int) apply_filters( 'wpml_object_id', $source_id, 'museum', false, 'en' );
    }
}

// Re-run translations that contain relationships now that every EN museum ID exists.
if ( in_array( $phase, [ 'all', 'final' ], true ) ) {
foreach ( $museum_data as $source_id => $data ) {
    $translated_id = $museum_id_map[ $source_id ] ?? 0;
    if ( ! $translated_id ) {
        continue;
    }
    $related = get_post_meta( $source_id, 'related_museums', true );
    if ( is_array( $related ) ) {
        update_post_meta(
            $translated_id,
            'related_museums',
            array_values( array_filter( array_map( static fn ( $id ) => $museum_id_map[ (int) $id ] ?? 0, $related ) ) )
        );
    }
}
}

// Rebuild the home translation with translated museum relationships and URLs.
if ( in_array( $phase, [ 'all', 'final' ], true ) && ! empty( $page_id_map[20] ) ) {
    $home_source = get_post( 20 );
    wp_update_post(
        wp_slash(
            [
                'ID' => $page_id_map[20],
                'post_content' => com_en_translate_blocks( $home_source->post_content, $content, $museum_id_map ),
            ]
        )
    );
}

// Point the translated WooCommerce and COM page settings to their EN pages.
$page_option_map = [
    'woocommerce_shop_page_id' => 5,
    'woocommerce_cart_page_id' => 6,
    'woocommerce_checkout_page_id' => 7,
    'woocommerce_myaccount_page_id' => 8,
];
if ( in_array( $phase, [ 'all', 'final' ], true ) ) {
foreach ( $page_option_map as $option_name => $source_id ) {
    if ( ! empty( $page_id_map[ $source_id ] ) ) {
        com_en_register_translation( 'admin_texts_' . $option_name, $option_name, (string) $source_id, (string) $page_id_map[ $source_id ] );
    }
}
}

if ( in_array( $phase, [ 'all', 'final' ], true ) ) {
    do_action( 'wpml_st_refresh_translation_files' );
    clean_post_cache( 20 );
    flush_rewrite_rules();
}

WP_CLI::success(
    sprintf(
        'English enabled: %d UI strings, %d pages and %d museums.',
        count( $ui ),
        count( array_filter( $page_id_map ) ),
        count( array_filter( $museum_id_map ) )
    )
);
