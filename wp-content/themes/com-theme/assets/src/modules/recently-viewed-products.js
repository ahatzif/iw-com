import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'scroll-to': 'scroll', } };
    }
    init() {
    // Παίρνουμε το product ID από το data attribute στο στοιχείο που έχει data-module="recently-viewed"
    const id = parseInt(this.el.getAttribute('data-product-id'), 10);
    if (!id) return; // Αν δεν υπάρχει έγκυρο ID, σταματάμε

    const NAME = 'iw_my_recently_viewed'; // Όνομα του custom cookie
    const MAX  = 10;                       // Μέγιστος αριθμός προϊόντων που κρατάμε στο cookie

    // --------- Helper: Διάβασε το cookie ---------
    const readCookie = () => {
      // Βρίσκουμε το cookie με regex
      const m = document.cookie.match(
        new RegExp('(?:^|; )' + NAME.replace(/([.$?*|{}()\[\]\\/+^])/g, '\\$1') + '=([^;]*)')
      );
      if (!m) return []; // Αν δεν βρεθεί cookie, επιστρέφουμε κενό array
      try {
        return JSON.parse(decodeURIComponent(m[1])); // Decode & parse JSON
      } catch {
        return []; // Αν κάτι πάει στραβά, επιστρέφουμε κενό array
      }
    };

    // --------- Helper: Γράψε το cookie ---------
    const writeCookie = (arr) => {
      const val = encodeURIComponent(JSON.stringify(arr)); // Μετατροπή σε JSON string και encode
      const d   = new Date();
      d.setTime(d.getTime() + 7 * 24 * 60 * 60 * 1000); // Cookie λήγει σε 7 μέρες
      const secure = location.protocol === 'https:' ? '; Secure' : ''; // Secure flag μόνο σε HTTPS
      // Δημιουργία cookie string
      document.cookie = `${NAME}=${val}; expires=${d.toUTCString()}; path=/; SameSite=Lax${secure}`;
    };

    // --------- Λογική: Πρόσθεσε το τρέχον προϊόν στο cookie ---------
    const addCurrentProduct = () => {
      let arr = readCookie();              // Διάβασε τα τρέχοντα προϊόντα
      if (!Array.isArray(arr)) arr = [];   // Αν δεν είναι array, ξεκίνα νέο
      if (!arr.includes(id)) arr.unshift(id); // Πρόσθεσε το ID στην αρχή, αν δεν υπάρχει
      arr = arr.slice(0, MAX);             // Κράτα μόνο τα 10 πιο πρόσφατα
      writeCookie(arr);                    // Γράψε πίσω το cookie
    };

    // --------- Έλεγχος consent ---------
    const tryRun = () => {
      // Αν υπάρχει Cookiebot και ο χρήστης έχει δώσει consent για Preferences
      if (window.Cookiebot && Cookiebot.consent && Cookiebot.consent.preferences) {
        addCurrentProduct();
      }
    };

    // 1) Εκτέλεση άμεσα αν ο χρήστης έχει ήδη δώσει consent
    tryRun();

    // 2) Αν ο χρήστης δώσει consent τώρα (μετά το page load), ξανατρέχουμε
    window.addEventListener('CookiebotOnAccept', () => {
      if (Cookiebot.consent.preferences) addCurrentProduct();
    });
  }
    destroy(){}
}
