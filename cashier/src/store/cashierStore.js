import { reactive, watch } from 'vue';
import {
  formatDate,
  isSlotBookable,
  monthTitle,
  money,
  parseYmd,
  slotAvailabilityLabel,
  slotAvailabilityState,
  timeLabel,
  toYmd,
} from '../utils/formatters.js';

const historyStorageKey = 'iwCashierSalesHistory';
const buildingStorageKey = 'iwCashierBuildingId';
const paymentStorageKey = 'iwCashierPaymentMethod';
const printerStorageKey = 'iwCashierPrinterId';
const themeStorageKey = 'iwCashierThemeMode';
const languageStorageKey = 'iwCashierLanguage';
const printJobPollIntervalMs = 1500;
const printJobPollTimeoutMs = 90000;
const calendarWeekdays = ['ΔΕΥ', 'ΤΡΙ', 'ΤΕΤ', 'ΠΕΜ', 'ΠΑΡ', 'ΣΑΒ', 'ΚΥΡ'];
const themePreferenceQuery = window.matchMedia?.('(prefers-color-scheme: dark)') || null;
const memberVerificationTicketCodes = new Set(['4306960']);
const memberVerificationSlotLimit = 2;
const paymentMethodAliases = {
  cashier_cash: 'cash',
  cash: 'cash',
  cashier_pos_manual: 'pos_manual',
  manual: 'pos_manual',
  manual_pos: 'pos_manual',
  pos: 'pos_manual',
  pos_manual: 'pos_manual',
  cashier_pos_nexi: 'pos_nexi',
  nexi: 'pos_nexi',
  pos_nexi: 'pos_nexi',
  cashier_pos_alpha: 'pos_alpha',
  alpha: 'pos_alpha',
  pos_alpha: 'pos_alpha',
};
const paymentMethodFallbackLabels = {
  cash: 'Μετρητά',
  pos_manual: 'Πληρωμή με POS',
  pos_nexi: 'POS Nexi API',
  pos_alpha: 'POS Alpha API',
};
const deliveryMethodLabels = {
  print: 'Εκτύπωση',
  email: 'Email',
  sms: 'SMS',
};
const deliveryMethodKeys = Object.keys(deliveryMethodLabels);

function normalizeDeliveryMethods(value) {
  let methods = value;
  if (typeof methods === 'string') {
    try {
      const decoded = JSON.parse(methods);
      methods = Array.isArray(decoded) ? decoded : methods.split(',');
    } catch {
      methods = methods.split(',');
    }
  }

  const selected = Array.isArray(methods) ? methods : [];
  return deliveryMethodKeys.filter((method) => selected.includes(method));
}

const themeModes = {
  default: {
    label: 'Default',
    subtitle: 'Ακολουθεί τη συσκευή όπου είναι δυνατό.',
  },
  light: {
    label: 'Light',
    subtitle: 'Χρήση φωτεινού περιβάλλοντος.',
  },
  dark: {
    label: 'Dark',
    subtitle: 'Χρήση πιο σκούρου περιβάλλοντος.',
  },
};

const languages = {
  el: {
    label: 'Ελληνικά',
    subtitle: 'Χρήση ελληνικών κειμένων στο cashier.',
  },
  en: {
    label: 'English',
    subtitle: 'Use English cashier text where available.',
  },
};

function readSaleHistory() {
  try {
    const parsed = JSON.parse(window.localStorage.getItem(historyStorageKey) || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch (error) {
    return [];
  }
}

function getStoredValue(key, fallback = '') {
  try {
    return window.localStorage.getItem(key) || fallback;
  } catch (error) {
    return fallback;
  }
}

function setStoredValue(key, value) {
  try {
    window.localStorage.setItem(key, value);
  } catch (error) {
    // Local storage can be unavailable in strict privacy contexts.
  }
}

function normalizeSearchText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('el-GR');
}

function normalizeTicketCode(value) {
  return String(value ?? '').trim();
}

function normalizePaymentMethodId(value) {
  const normalized = String(value || '')
    .trim()
    .toLocaleLowerCase('el-GR')
    .replace(/[\s-]+/g, '_');

  return paymentMethodAliases[normalized] || normalized;
}

function normalizePrinterId(value) {
  const printerId = String(value || '').trim();
  return printerId || 'auto';
}

function normalizeEmail(value) {
  return String(value || '').trim();
}

function parseEmailList(value) {
  const seen = new Set();
  return String(value || '')
    .split(/[\s,;\u037e]+/u)
    .map(normalizeEmail)
    .filter(Boolean)
    .filter((email) => {
      const key = email.toLocaleLowerCase('el-GR');
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
}

function isValidEmail(value) {
  const email = normalizeEmail(value);
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function areValidEmails(value) {
  const emails = parseEmailList(value);
  return Boolean(emails.length) && emails.every(isValidEmail);
}

function normalizePhone(value) {
  return String(value || '').trim();
}

function isValidPhone(value) {
  const phone = normalizePhone(value);
  const digits = phone.replace(/\D/g, '');
  return digits.length >= 10 && /^[\d\s()+.-]+$/.test(phone);
}

function ticketCodeValues(item = {}) {
  const subject = item && typeof item === 'object' ? item : {};
  return [
    subject.key,
    subject.value,
    subject.id,
    subject.term_id,
    subject.category_id,
    subject.code,
    subject.slug,
  ].map(normalizeTicketCode).filter(Boolean);
}

function hasMemberVerificationTicketCode(item = {}) {
  return ticketCodeValues(item).some((value) => memberVerificationTicketCodes.has(value));
}

function resolveMemberCardPayload(value = '') {
  const rawText = String(value || '').trim();
  let parsed = null;

  try {
    parsed = JSON.parse(rawText);
  } catch (error) {
    parsed = null;
  }

  if (parsed && typeof parsed === 'object' && parsed.member_card_id) {
    return String(parsed.member_card_id).trim();
  }

  const compactMatch = rawText.match(/^m:(.+)$/i);
  return compactMatch ? compactMatch[1].trim() : '';
}

function isRestNonceError(payload = {}, response = null) {
  const code = String(payload?.code || '');
  const message = String(payload?.message || payload?.error || '').toLocaleLowerCase('el-GR');

  return code === 'rest_cookie_invalid_nonce'
    || (
      Number(response?.status || 0) === 403
      && message.includes('cookie')
      && (message.includes('failed') || message.includes('απέτυχε'))
    );
}

function normalizeApiErrorMessage(payload = {}, response = null) {
  if (isRestNonceError(payload, response)) {
    return 'Η συνεδρία έληξε. Ανανέωσε τη σελίδα ή συνδέσου ξανά.';
  }

  return payload?.message || payload?.error || 'Η ενέργεια δεν ολοκληρώθηκε.';
}

function firstOfMonth(value) {
  const date = parseYmd(value) || new Date();
  return toYmd(new Date(date.getFullYear(), date.getMonth(), 1));
}

function addMonths(value, amount) {
  const date = parseYmd(value) || new Date();
  return toYmd(new Date(date.getFullYear(), date.getMonth() + amount, 1));
}

function monthNumber(value) {
  const date = parseYmd(value);
  return date ? date.getFullYear() * 12 + date.getMonth() : 0;
}

function normalizeThemeMode(mode) {
  return Object.prototype.hasOwnProperty.call(themeModes, mode) ? mode : 'default';
}

function normalizeLanguage(language) {
  return Object.prototype.hasOwnProperty.call(languages, language) ? language : 'el';
}

function resolveInitialBuilding(config) {
  const buildings = Array.isArray(config.buildings) ? config.buildings : [];
  const storedBuildingId = getStoredValue(buildingStorageKey, '');

  if (storedBuildingId === 'all') {
    return null;
  }

  if (storedBuildingId) {
    return buildings.find((building) => String(building.id) === storedBuildingId) || config.selectedBuilding || null;
  }

  return config.selectedBuilding || null;
}

export function createCashierStore(config) {
  let deferredInstallPrompt = null;
  let restNonceRefreshPromise = null;

  const store = reactive({
    config,
    ticketFilters: [],
    selectedTicketType: 'all',
    ticketSearch: '',
    allTicketResults: [],
    ticketsLoaded: false,
    ticketsLoading: false,
    paymentMethods: [],
    settingsPaymentMethod: getStoredValue(paymentStorageKey, ''),
    selectedPrinterId: normalizePrinterId(getStoredValue(printerStorageKey, 'auto')),
    selectedPaymentMethod: '',
    selectedTicket: null,
    selectedTicketListItem: null,
    selectedTicketData: null,
    ticketDetailLoading: false,
    selectedDate: '',
    selectedTime: '',
    visitors: [],
    visitorCounter: 0,
    selectedBuilding: resolveInitialBuilding(config),
    themeMode: normalizeThemeMode(getStoredValue(themeStorageKey, 'default')),
    language: normalizeLanguage(getStoredValue(languageStorageKey, document.documentElement.lang || 'el')),
    fullscreenStatus: 'Εγκατάσταση ή fullscreen σε αυτή τη συσκευή.',
    appVersionStatus: '',
    loading: false,
    statusMessage: '',
    statusType: 'neutral',
    validationFocusTarget: {
      uid: 0,
      field: '',
      token: 0,
    },
    result: null,
    resultContext: {
      source: '',
      title: '',
    },
    historyDetailLoading: false,
    printingAllTickets: false,
    printingTicketKey: '',
    printSheet: {
      open: false,
      title: '',
      subtitle: '',
      status: 'idle',
      pending: false,
      summary: '',
      messages: [],
      payload: null,
      error: '',
    },
    printJobStatusTimer: 0,
    printJobStatusRequestId: 0,
    posReference: '',
    terminalId: '',
    deliveryMethods: ['print'],
    customerEmail: '',
    customerPhone: '',
    newsletterOptIn: false,
    membershipInviteOptIn: false,
    saleHistory: readSaleHistory(),
    searchTimer: 0,
    ticketSearchRequestId: 0,
    ticketDetailRequestId: 0,
    passwordCloseTimer: 0,
    accountCloseTimer: 0,
    calendarHintTimer: 0,
    currentUser: {
      ...(config.currentUser || {}),
    },
    selectSheet: {
      open: false,
      kind: '',
      title: '',
      icon: 'pin',
      search: '',
      searchable: false,
      searchPlaceholder: '',
      visitorIndex: -1,
      categoryKey: '',
      calendarMonth: '',
      calendarHintDate: '',
      options: [],
    },
    passwordSheet: {
      open: false,
      newPassword: '',
      confirmPassword: '',
      message: '',
      messageType: 'neutral',
      pending: false,
    },
    accountSheet: {
      open: false,
      firstName: '',
      lastName: '',
      email: '',
      message: '',
      messageType: 'neutral',
      pending: false,
    },
    memberVerificationSheet: {
      open: false,
      mode: 'search',
      visitorUid: 0,
      query: '',
      message: '',
      messageType: 'neutral',
      pending: false,
      matches: [],
      result: null,
      cameraPending: false,
      cameraError: '',
    },

    get buildings() {
      return Array.isArray(this.config.buildings) ? this.config.buildings : [];
    },
    get selectedBuildingTitle() {
      return this.selectedBuilding?.title || 'Όλα';
    },
    get selectedBuildingAddress() {
      return this.selectedBuilding?.address || 'Όλα τα μουσεία';
    },
    get selectedTicketTitle() {
      return this.selectedTicketListItem?.display_title || this.selectedTicketData?.title || 'Επιλέξτε εισιτήριο';
    },
    get operatorSummary() {
      return this.accountDisplayName();
    },
    get selectedTicketCardItem() {
      const listItem = this.selectedTicketListItem || this.selectedTicketData?.list_item || this.findTicketListItem();
      if (listItem) {
        return listItem;
      }

      if (!this.selectedTicketData) {
        return null;
      }

      return {
        id: this.selectedTicketData.id || this.selectedTicket,
        ticket_id: this.selectedTicketData.id || this.selectedTicket,
        source_id: this.selectedTicketData.source_id || this.selectedTicketData.id || this.selectedTicket,
        display_title: this.selectedTicketData.source_title || this.selectedTicketData.title || this.selectedTicketTitle,
        title: this.selectedTicketData.title || this.selectedTicketTitle,
        post_type: this.selectedTicketData.post_type || '',
        building_title: this.selectedBuilding?.title || '',
      };
    },
    get ticketData() {
      return this.selectedTicketData?.ticket_data || {};
    },
    get schedule() {
      return this.ticketData.schedule || {};
    },
    get availableDates() {
      return Array.isArray(this.schedule.allowDates) ? [...this.schedule.allowDates].sort() : [];
    },
    get availableDateSet() {
      return new Set(this.availableDates);
    },
    get calendarWeekdays() {
      return calendarWeekdays;
    },
    get calendarMonthTitle() {
      return monthTitle(this.selectSheet.calendarMonth);
    },
    get calendarCanGoPrev() {
      if (!this.availableDates.length || !this.selectSheet.calendarMonth) return false;
      return monthNumber(this.selectSheet.calendarMonth) > monthNumber(firstOfMonth(this.availableDates[0]));
    },
    get calendarCanGoNext() {
      if (!this.availableDates.length || !this.selectSheet.calendarMonth) return false;
      return monthNumber(this.selectSheet.calendarMonth) < monthNumber(firstOfMonth(this.availableDates[this.availableDates.length - 1]));
    },
    get calendarDays() {
      const monthDate = parseYmd(this.selectSheet.calendarMonth);
      if (!monthDate) return [];

      const year = monthDate.getFullYear();
      const month = monthDate.getMonth();
      const firstDate = new Date(year, month, 1);
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const leadingEmptyCells = (firstDate.getDay() + 6) % 7;
      const totalCells = Math.ceil((leadingEmptyCells + daysInMonth) / 7) * 7;

      return Array.from({ length: totalCells }, (_, index) => {
        const dayNumber = index - leadingEmptyCells + 1;
        if (dayNumber < 1 || dayNumber > daysInMonth) {
          return {
            key: `empty-${index}`,
            empty: true,
          };
        }

        const date = toYmd(new Date(year, month, dayNumber));
        const availabilityState = this.dateAvailabilityState(date);
        const holidayLabel = this.dateHolidayLabel(date);
        const disabled = !this.availableDateSet.has(date) || availabilityState === 'sold-out';

        return {
          key: date,
          date,
          label: String(dayNumber),
          empty: false,
          selected: date === this.selectedDate,
          disabled,
          availabilityState,
          availabilityLabel: this.dateAvailabilityLabel(date),
          holidayLabel,
          hintVisible: Boolean(holidayLabel && this.selectSheet.calendarHintDate === date),
        };
      });
    },
    get selectedDateSlots() {
      return this.slotsForDate(this.selectedDate);
    },
    get availableTimes() {
      return this.selectedDateSlots.filter((slot) => isSlotBookable(slot));
    },
    get selectedTimeSlot() {
      return this.selectedDateSlots.find((slot) => slot.time === this.selectedTime) || null;
    },
    get selectedDateLabel() {
      return this.selectedDate ? formatDate(this.selectedDate) : 'Επιλέξτε πρώτα εισιτήριο';
    },
    get selectedTimeLabel() {
      if (!this.selectedDate) return 'Επιλέξτε πρώτα ημερομηνία';
      return this.selectedTime
        ? timeLabel(this.selectedTimeSlot)
        : 'Μη διαθέσιμο';
    },
    get selectedTimeAvailabilityLabel() {
      return 'Ώρα επίσκεψης';
    },
    get ticketCategories() {
      const categories = this.ticketData.ticket_categories || [];
      return categories
        .map((category) => {
          const subcategories = Array.isArray(category.subcategories) ? category.subcategories : [];
          return {
            ...category,
            subcategories: subcategories.filter((sub) => sub && sub.value && sub.price !== undefined),
          };
        })
        .filter((category) => category.subcategories.length);
    },
    get ticketCategoryGroups() {
      return this.ticketCategories.map((category, categoryIndex) => {
        const key = this.ticketCategoryKey(category, categoryIndex);
        const visitors = this.visitors
          .map((visitor, index) => ({
            visitor,
            index,
          }))
          .filter((entry) => this.visitorCategoryKey(entry.visitor) === key)
          .map((entry, localIndex) => ({
            ...entry,
            localIndex,
          }));

        return {
          key,
          category,
          label: category.label || `Κατηγορία ${categoryIndex + 1}`,
          description: category.description || '',
          priceLabel: this.ticketCategoryPriceLabel(category),
          min: this.ticketCategoryMin(category),
          max: this.ticketCategoryMax(category),
          count: visitors.length,
          total: visitors.reduce((sum, entry) => {
            const type = this.categoryByValue(entry.visitor.categoryId);
            return sum + Number(type?.sub?.price || 0);
          }, 0),
          visitors,
          showType: category.subcategories.length > 1,
          canAdd: this.canAddTicketCategory(key),
          canRemove: this.canDecreaseTicketCategory(key),
        };
      });
    },
    get defaultCategoryValue() {
      return this.ticketCategories[0]?.subcategories?.[0]?.value || '';
    },
    get totalPrice() {
      return this.visitors.reduce((sum, visitor) => {
        const type = this.categoryByValue(visitor.categoryId);
        return sum + Number(type?.sub?.price || 0);
      }, 0);
    },
    get enabledPaymentMethods() {
      return this.paymentMethods.filter((method) => method.enabled);
    },
    get selectedPaymentObject() {
      return this.paymentMethods.find((method) => method.id === this.selectedPaymentMethod) || null;
    },
    get settingsPaymentObject() {
      return this.paymentMethods.find((method) => method.id === this.settingsPaymentMethod) || null;
    },
    get selectedPaymentIsPos() {
      return this.selectedPaymentObject?.type === 'pos';
    },
    get selectedPaymentRequiresTerminal() {
      return Boolean(this.selectedPaymentObject?.requires_terminal_id);
    },
    get selectedPaymentSummary() {
      if (!this.enabledPaymentMethods.length) return 'Δεν υπάρχουν ενεργοί τρόποι πληρωμής.';
      const payment = this.settingsPaymentObject;
      if (!payment) return 'Δεν έχει οριστεί προεπιλογή.';
      if (payment.api_integration) return `${payment.label || payment.id} · API POS`;
      return `${payment.label || payment.id} · ${payment.type === 'cash' ? 'Ταμείο' : 'Manual POS'}`;
    },
    get zebraPrintConfig() {
      return this.config.zebraPrint || this.config.zebra_print || {};
    },
    get printerTargets() {
      const targets = Array.isArray(this.zebraPrintConfig.targets) ? this.zebraPrintConfig.targets : [];
      return [
        {
          id: 'auto',
          label: 'Αυτόματα',
          subtitle: 'Επιλογή από το κτίριο του εισιτηρίου.',
        },
        ...targets.map((target) => ({
          id: normalizePrinterId(target.id),
          label: target.label || target.id || 'Εκτυπωτής',
          subtitle: target.subtitle || '',
        })),
      ];
    },
    get selectedPrinterTarget() {
      return this.printerTargets.find((target) => target.id === normalizePrinterId(this.selectedPrinterId)) || this.printerTargets[0];
    },
    get selectedPrinterSummary() {
      const target = this.selectedPrinterTarget;
      if (!target) return 'Αυτόματη επιλογή εκτυπωτή.';
      return target.subtitle ? `${target.label} · ${target.subtitle}` : target.label;
    },
    get themeSummary() {
      return themeModes[this.themeMode]?.label || themeModes.default.label;
    },
    get languageSummary() {
      return languages[this.language]?.label || languages.el.label;
    },
    get appVersionSummary() {
      return this.appVersionStatus || `Έκδοση ${this.config.appVersionLabel || this.config.appVersion || ''}`;
    },
    get validVisitors() {
      return this.visitors.length > 0 && this.visitors.every((visitor) => {
        const type = this.categoryByValue(visitor.categoryId);
        if (!type) return false;
        if (!this.visitorRequiresManualName(visitor)) return true;
        return String(visitor.first || '').trim() && String(visitor.last || '').trim();
      });
    },
    get visitorValidationViolation() {
      return this.firstVisitorValidationTarget()?.message || '';
    },
    get memberVerificationViolation() {
      const index = this.firstMissingMemberVerificationIndex();
      if (index < 0) return '';

      const visitor = this.visitors[index];
      return `Χρειάζεται ενεργή συνδρομή για ${this.visitorCategoryLabel(visitor)}.`;
    },
    get memberVerificationLimitViolation() {
      const counts = new Map();

      for (const visitor of this.visitors) {
        if (!this.visitorRequiresMemberVerification(visitor) || !this.isMemberVerificationActive(visitor?.memberVerification)) {
          continue;
        }

        const cardId = this.visitorMemberCardId(visitor);
        if (!cardId) continue;

        const nextCount = (counts.get(cardId) || 0) + 1;
        if (nextCount > memberVerificationSlotLimit) {
          return 'Το μέλος μπορεί να έχει μόνο έναν συνοδό για το ίδιο slot.';
        }
        counts.set(cardId, nextCount);
      }

      return '';
    },
    get ticketLimitViolation() {
      const globalMin = this.globalMinTickets();
      const globalMax = this.globalMaxTickets();

      if (globalMin > 0 && this.visitors.length < globalMin) {
        return `Πρέπει να επιλέξετε τουλάχιστον ${this.ticketCountLabel(globalMin, 'lower')}.`;
      }

      if (globalMax > 0 && this.visitors.length > globalMax) {
        return `Μπορείτε να επιλέξετε έως ${this.ticketCountLabel(globalMax, 'lower')}.`;
      }

      for (let categoryIndex = 0; categoryIndex < this.ticketCategories.length; categoryIndex += 1) {
        const category = this.ticketCategories[categoryIndex];
        const key = this.ticketCategoryKey(category, categoryIndex);
        const count = this.ticketCategoryCount(key);
        const min = this.ticketCategoryMin(category);
        const max = this.ticketCategoryMax(category);

        if (min > 0 && count < min) {
          return this.ticketCategoryLimitMessage(category, categoryIndex, 'min');
        }

        if (max > 0 && count > max) {
          return this.ticketCategoryLimitMessage(category, categoryIndex, 'max');
        }
      }

      return '';
    },
    get satisfiesTicketLimits() {
      return !this.ticketLimitViolation;
    },
    get paymentValidationViolation() {
      if (!this.visitors.length) return '';
      if (!this.selectedPaymentObject) return 'Επίλεξε τρόπο πληρωμής.';
      if (this.selectedPaymentRequiresTerminal && !String(this.terminalId || '').trim()) {
        return 'Συμπλήρωσε Terminal ID για το POS.';
      }
      return '';
    },
    get selectedDeliveryMethods() {
      const methods = Array.isArray(this.deliveryMethods) ? this.deliveryMethods : [];
      return deliveryMethodKeys.filter((method) => methods.includes(method));
    },
    get deliveryPrintEnabled() {
      return this.selectedDeliveryMethods.includes('print');
    },
    get deliveryEmailEnabled() {
      return this.selectedDeliveryMethods.includes('email');
    },
    get deliverySmsEnabled() {
      return this.selectedDeliveryMethods.includes('sms');
    },
    get deliveryPrintReady() {
      return this.deliveryPrintEnabled;
    },
    get deliveryEmailReady() {
      return this.deliveryEmailEnabled && this.customerEmailList.length > 0 && areValidEmails(this.customerEmail);
    },
    get deliverySmsReady() {
      return this.deliverySmsEnabled && isValidPhone(this.customerPhone);
    },
    get deliveryRequiresEmail() {
      return this.deliveryEmailEnabled || this.newsletterOptIn || this.membershipInviteOptIn;
    },
    get deliveryRequiresPhone() {
      return this.deliverySmsEnabled;
    },
    get customerEmailList() {
      return parseEmailList(this.customerEmail);
    },
    get deliveryEmailViolation() {
      if (!this.deliveryRequiresEmail) return '';
      if (!this.customerEmailList.length) return 'Συμπλήρωσε email παραλήπτη.';
      if (!areValidEmails(this.customerEmail)) return 'Έλεγξε τα email παραλήπτη.';
      return '';
    },
    get deliveryPhoneViolation() {
      if (!this.deliveryRequiresPhone) return '';
      if (!isValidPhone(this.customerPhone)) return 'Συμπλήρωσε έγκυρο κινητό για SMS.';
      return '';
    },
    get deliverySummary() {
      const labels = this.selectedDeliveryMethods.map((method) => deliveryMethodLabels[method]).filter(Boolean);
      if (this.newsletterOptIn) {
        labels.push('Newsletter');
      }
      if (this.membershipInviteOptIn) {
        labels.push('Member');
      }
      return labels.length ? labels.join(' + ') : 'Δεν έχει επιλεγεί παράδοση';
    },
    get deliveryValidationViolation() {
      if (!this.visitors.length) return '';
      if (!this.selectedDeliveryMethods.length) {
        return 'Επίλεξε τρόπο παράδοσης εισιτηρίων.';
      }
      return this.deliveryEmailViolation || this.deliveryPhoneViolation;
    },
    get canSubmit() {
      return Boolean(
        this.selectedTicket
        && this.selectedDate
        && this.selectedTime
        && this.validVisitors
        && this.satisfiesTicketLimits
        && !this.memberVerificationViolation
        && !this.memberVerificationLimitViolation
        && !this.deliveryValidationViolation
        && this.selectedPaymentObject
        && (!this.selectedPaymentRequiresTerminal || String(this.terminalId || '').trim())
        && !this.ticketDetailLoading
        && !this.loading
      );
    },
    get renderedTicketFilters() {
      const availableTypes = new Set(
        this.allTicketResults
          .map((item) => String(item?.type || '').trim())
          .filter(Boolean),
      );
      const filters = (this.ticketFilters.length ? this.ticketFilters : [{ id: 'all', label: 'Όλα' }])
        .filter((filter) => filter?.id === 'all' || availableTypes.has(String(filter?.id || '')));
      const contentFilters = filters.filter((filter) => filter?.id !== 'all');

      return contentFilters.length > 1 ? filters : [];
    },
    get ticketResults() {
      return this.filteredTicketResults();
    },
    get resultTickets() {
      return Array.isArray(this.result?.tickets) ? this.result.tickets : [];
    },
    get resultTicketCount() {
      return Number(this.result?.tickets_count || 0) || this.resultTickets.length;
    },
    get resultTicketCountLabel() {
      return this.ticketCountLabel(this.resultTicketCount, 'title');
    },
    get resultOrderNumber() {
      return this.result?.order_id ? `#${this.result.order_id}` : '';
    },
    get resultTotal() {
      return Number(this.result?.total_price || 0);
    },
    get resultTitle() {
      return (
        this.result?.title
        || this.resultContext?.title
        || this.currentSaleTitle()
        || 'Εισιτήριο'
      );
    },
    get resultIsHistory() {
      return this.resultContext?.source === 'history';
    },
    get filteredSelectOptions() {
      const needle = this.selectSheet.search.toLocaleLowerCase('el-GR');
      return this.selectSheet.options.filter((item) => {
        const haystack = `${item.label} ${item.subtitle || ''}`.toLocaleLowerCase('el-GR');
        return !needle || haystack.includes(needle);
      });
    },

    endpoint(name) {
      return this.config.endpoints ? this.config.endpoints[name] : '';
    },
    async apiFetch(url, options = {}) {
      const { retryNonce = true, ...fetchOptions } = options;
      const headers = new Headers(fetchOptions.headers || {});
      headers.set('X-WP-Nonce', this.config.restNonce || '');
      if (fetchOptions.body && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
      }

      const response = await fetch(url, { credentials: 'same-origin', ...fetchOptions, headers });
      const contentType = response.headers.get('content-type') || '';
      const payload = contentType.includes('application/json') ? await response.json() : {};
      if (!response.ok) {
        if (retryNonce !== false && isRestNonceError(payload, response) && await this.refreshRestNonce()) {
          return this.apiFetch(url, { ...options, retryNonce: false });
        }

        const message = normalizeApiErrorMessage(payload, response);
        throw new Error(message);
      }
      return payload;
    },
    async refreshRestNonce() {
      if (restNonceRefreshPromise) {
        return restNonceRefreshPromise;
      }

      restNonceRefreshPromise = (async () => {
        try {
          const cashierUrl = new URL(window.location.href);
          cashierUrl.hash = '';
          cashierUrl.searchParams.set('_cashier_nonce_refresh', String(Date.now()));

          const response = await fetch(cashierUrl.toString(), {
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
              Accept: 'text/html',
            },
          });

          if (!response.ok) return false;

          const html = await response.text();
          const document = new DOMParser().parseFromString(html, 'text/html');
          const configText = document.getElementById('iw-cashier-config')?.textContent || '';
          if (!configText) return false;

          const freshConfig = JSON.parse(configText);
          if (!freshConfig?.restNonce) return false;

          Object.assign(this.config, freshConfig);
          return true;
        } catch (error) {
          return false;
        } finally {
          restNonceRefreshPromise = null;
        }
      })();

      return restNonceRefreshPromise;
    },
    buildEndpointUrl(endpoint, params = {}) {
      const url = new URL(endpoint, window.location.href);
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          url.searchParams.set(key, value);
        }
      });
      return url.toString();
    },
    setStatus(message = '', type = 'neutral') {
      this.statusMessage = message;
      this.statusType = type;
    },
    clearValidationFocusTarget() {
      this.visitors.forEach((visitor) => {
        delete visitor.validationField;
      });
      this.validationFocusTarget = {
        uid: 0,
        field: '',
        token: Number(this.validationFocusTarget?.token || 0),
      };
    },
    markValidationFocusTarget(visitorOrUid, field = '') {
      const visitor = typeof visitorOrUid === 'object'
        ? visitorOrUid
        : this.visitors.find((item) => Number(item?.uid || 0) === Number(visitorOrUid || 0));
      const uid = Number(visitor?.uid || 0);
      const normalizedField = String(field || '');

      if (visitor) {
        visitor.validationField = normalizedField;
      }

      this.validationFocusTarget = {
        uid,
        field: normalizedField,
        token: Number(this.validationFocusTarget?.token || 0) + 1,
      };
    },
    visitorValidationFocusField(visitor) {
      if (visitor?.validationField) {
        return String(visitor.validationField);
      }
      const uid = Number(visitor?.uid || 0);
      const target = this.validationFocusTarget || {};
      return uid && Number(target.uid || 0) === uid ? String(target.field || '') : '';
    },
    visitorHasValidationFocus(visitor, field) {
      return this.visitorValidationFocusField(visitor) === String(field || '');
    },
    firstVisitorValidationTarget() {
      if (!this.visitors.length) return null;

      const missingCategory = this.visitors.find((visitor) => !this.categoryByValue(visitor.categoryId));
      if (missingCategory) {
        return {
          visitor: missingCategory,
          field: 'category',
          message: 'Επίλεξε τύπο εισιτηρίου.',
        };
      }

      const missingName = this.visitors.find((visitor) => (
        this.visitorRequiresManualName(visitor)
        && (!String(visitor.first || '').trim() || !String(visitor.last || '').trim())
      ));

      if (!missingName) return null;

      return {
        visitor: missingName,
        field: String(missingName.first || '').trim() ? 'last' : 'first',
        message: `Συμπλήρωσε όνομα και επώνυμο για ${this.visitorCategoryLabel(missingName)}.`,
      };
    },
    currentSaleTitle() {
      return this.selectedTicketListItem?.display_title
        || this.selectedTicketData?.title
        || this.selectedTicketTitle
        || 'Εισιτήριο';
    },
    ticketImageUrl(item = {}) {
      return String(
        item?.thumb
        || item?.thumbnail
        || item?.thumbnail_url
        || item?.image?.thumb
        || item?.image?.url
        || ''
      );
    },
    ticketCountLabel(count = 0, casing = 'title') {
      const value = Number(count || 0);
      const isSingular = value === 1;
      if (casing === 'lower') {
        return `${value} ${isSingular ? 'εισιτήριο' : 'εισιτήρια'}`;
      }
      return `${value} ${isSingular ? 'Εισιτήριο' : 'Εισιτήρια'}`;
    },
    historyItemTicketCountLabel(item = {}) {
      return this.ticketCountLabel(item.count || item.result?.tickets_count || 0, 'lower');
    },
    paymentMethodByValue(value = '') {
      const normalized = normalizePaymentMethodId(value);
      if (!normalized) return null;

      return this.paymentMethods.find((method) => {
        const candidates = [
          method.id,
          method.gateway_id,
          method.provider,
        ].map(normalizePaymentMethodId).filter(Boolean);

        return candidates.includes(normalized);
      }) || null;
    },
    paymentMethodLabel(value = '', fallback = '') {
      const normalized = normalizePaymentMethodId(value);
      if (!normalized) return fallback;

      const method = this.paymentMethodByValue(value);
      if (method) {
        const methodId = normalizePaymentMethodId(method.id);
        return paymentMethodFallbackLabels[methodId] || method.label || fallback;
      }

      return paymentMethodFallbackLabels[normalized] || fallback;
    },
    historyItemPaymentLabel(item = {}) {
      const provider = item.result?.payment?.provider || item.payment || '';
      const providerLabel = this.paymentMethodLabel(provider);
      if (providerLabel) return providerLabel;

      const label = item.paymentLabel || item.result?.payment?.label || '';
      return this.paymentMethodLabel(label, label);
    },
    historyItemThumb(item = {}) {
      const directThumb = this.ticketImageUrl(item) || this.ticketImageUrl(item.result);
      if (directThumb) return directThumb;

      const itemTitle = normalizeSearchText(item.title || item.result?.title || '');
      if (!itemTitle) return '';

      const matchedTicket = this.allTicketResults.find((ticket) => {
        const titles = [
          ticket.display_title,
          ticket.title,
          ticket.source_title,
        ].map(normalizeSearchText).filter(Boolean);

        return titles.includes(itemTitle)
          || titles.some((title) => title.includes(itemTitle) || itemTitle.includes(title));
      });

      return this.ticketImageUrl(matchedTicket);
    },
    historyOrderId(item = {}) {
      return String(item.orderId || item.order_id || item.result?.order_id || '').trim();
    },
    findSaleHistoryItem(orderId) {
      const normalizedOrderId = String(orderId || '').trim();
      if (!normalizedOrderId) return null;
      return this.saleHistory.find((item) => this.historyOrderId(item) === normalizedOrderId) || null;
    },
    normalizeOrderResult(result = {}, context = {}) {
      const tickets = Array.isArray(result.tickets) ? result.tickets : [];
      const count = Number(result.tickets_count || context.count || tickets.length || 0);
      const rawTotal = result.total_price ?? context.total ?? 0;
      const total = Number(rawTotal);
      const firstTicket = tickets[0] || {};
      const payment = result.payment && typeof result.payment === 'object'
        ? result.payment
        : (context.payment ? { provider: context.payment } : null);
      const delivery = result.delivery && typeof result.delivery === 'object' ? result.delivery : {};
      const deliveryMethods = normalizeDeliveryMethods(
        delivery.methods || result.delivery_methods || context.delivery_methods || context.deliveryMethods
      );
      const deliveryEmails = parseEmailList(
        delivery.emails || result.customer_emails || context.customer_emails || delivery.email || result.customer_email || context.customer_email
      );
      const deliveryNewsletter = Boolean(
        delivery.newsletter_opt_in
        || result.newsletter_opt_in
        || context.newsletter_opt_in
      );
      const deliveryMembershipInvite = Boolean(
        delivery.membership_invite_opt_in
        || result.membership_invite_opt_in
        || context.membership_invite_opt_in
      );
      const deliveryPhone = String(delivery.phone || result.customer_phone || context.customer_phone || '').trim();

      return {
        status: result.status || 'issued',
        order_id: result.order_id || context.orderId || context.order_id || '',
        order_item_id: result.order_item_id || '',
        created_at: result.created_at || context.createdAt || context.created_at || '',
        ticket_id: result.ticket_id || context.ticket_id || firstTicket.post_id || '',
        source_id: result.source_id || context.source_id || '',
        title: result.title || context.title || '',
        tickets_count: count,
        total_price: Number.isFinite(total) ? total : 0,
        currency: result.currency || 'EUR',
        payment,
        building_id: result.building_id || context.building_id || context.buildingId || '',
        building_title: result.building_title || context.building_title || context.buildingTitle || '',
        building_address: result.building_address || context.building_address || context.buildingAddress || '',
        location: result.location || context.location || '',
        thumb: this.ticketImageUrl(result) || this.ticketImageUrl(context),
        image: result.image || context.image || null,
        delivery: {
          methods: deliveryMethods,
          emails: deliveryEmails,
          email: deliveryEmails[0] || '',
          phone: deliveryPhone,
          newsletter_opt_in: deliveryNewsletter,
          membership_invite_opt_in: deliveryMembershipInvite,
        },
        delivery_methods: deliveryMethods,
        customer_emails: deliveryEmails,
        customer_email: deliveryEmails[0] || '',
        customer_phone: deliveryPhone,
        newsletter_opt_in: deliveryNewsletter,
        membership_invite_opt_in: deliveryMembershipInvite,
        tickets,
      };
    },
    async fetchOrderResult(orderId) {
      const endpoint = this.endpoint('order');
      if (!endpoint || !orderId) {
        throw new Error('Δεν βρέθηκε endpoint παραγγελίας.');
      }
      return this.apiFetch(`${endpoint}${orderId}`);
    },
    resolvedThemeMode() {
      if (this.themeMode === 'light' || this.themeMode === 'dark') {
        return this.themeMode;
      }

      return themePreferenceQuery?.matches ? 'dark' : 'light';
    },
    applyThemeMode() {
      document.documentElement.dataset.cashierThemeMode = this.themeMode;
      document.documentElement.dataset.cashierTheme = this.resolvedThemeMode();
    },
    setThemeMode(mode, options = {}) {
      this.themeMode = normalizeThemeMode(mode);
      this.applyThemeMode();

      if (options.persist !== false) {
        setStoredValue(themeStorageKey, this.themeMode);
      }
    },
    setLanguage(language, options = {}) {
      this.language = normalizeLanguage(language);
      document.documentElement.lang = this.language;

      if (options.persist !== false) {
        setStoredValue(languageStorageKey, this.language);
      }
    },
    persistSelectedBuilding() {
      setStoredValue(buildingStorageKey, this.selectedBuilding?.id ? String(this.selectedBuilding.id) : 'all');
    },
    persistSelectedPaymentMethod() {
      if (this.settingsPaymentMethod) {
        setStoredValue(paymentStorageKey, this.settingsPaymentMethod);
      }
    },
    persistSelectedPrinterId() {
      setStoredValue(printerStorageKey, normalizePrinterId(this.selectedPrinterId));
    },
    isStandaloneApp() {
      return Boolean(
        window.matchMedia?.('(display-mode: standalone)')?.matches
        || window.matchMedia?.('(display-mode: fullscreen)')?.matches
        || window.navigator.standalone
      );
    },
    updateFullscreenStatus(message = '') {
      if (message) {
        this.fullscreenStatus = message;
        return;
      }

      if (this.isStandaloneApp()) {
        this.fullscreenStatus = 'Το app τρέχει ήδη ως εγκατεστημένη εφαρμογή.';
      } else if (document.fullscreenElement) {
        this.fullscreenStatus = 'Το fullscreen mode είναι ενεργό.';
      } else if (deferredInstallPrompt) {
        this.fullscreenStatus = 'Έτοιμο για εγκατάσταση σε αυτή τη συσκευή.';
      } else {
        this.fullscreenStatus = 'Εγκατάσταση ή fullscreen σε αυτή τη συσκευή.';
      }
    },
    setInstallPrompt(event) {
      deferredInstallPrompt = event;
      this.updateFullscreenStatus();
    },
    handleAppInstalled() {
      deferredInstallPrompt = null;
      this.updateFullscreenStatus('Το app εγκαταστάθηκε.');
    },
    async handleFullscreenAction() {
      if (this.isStandaloneApp()) {
        this.updateFullscreenStatus('Το app τρέχει ήδη ως εγκατεστημένη εφαρμογή.');
        return;
      }

      if (deferredInstallPrompt) {
        deferredInstallPrompt.prompt();
        const choice = await deferredInstallPrompt.userChoice.catch(() => null);
        deferredInstallPrompt = null;
        this.updateFullscreenStatus(choice?.outcome === 'accepted' ? 'Το app εγκαταστάθηκε.' : 'Η εγκατάσταση παραλείφθηκε.');
        return;
      }

      if (document.fullscreenElement) {
        await document.exitFullscreen?.();
        this.updateFullscreenStatus('Έξοδος από fullscreen mode.');
        return;
      }

      if (document.documentElement.requestFullscreen) {
        try {
          await document.documentElement.requestFullscreen({ navigationUI: 'hide' });
          this.updateFullscreenStatus('Το fullscreen mode είναι ενεργό.');
          return;
        } catch (error) {
          // iOS Safari exposes no reliable requestFullscreen path for page UI.
        }
      }

      this.updateFullscreenStatus('Χρησιμοποιήστε το μενού του browser για Add to Home Screen.');
    },
    async handleAppUpdateAction() {
      if (!('serviceWorker' in navigator)) {
        this.appVersionStatus = 'Service worker μη διαθέσιμο.';
        return;
      }

      this.appVersionStatus = 'Έλεγχος για ενημέρωση...';
      try {
        const registrations = await navigator.serviceWorker.getRegistrations();
        await Promise.all(registrations.map((registration) => registration.update()));
        this.appVersionStatus = `Έκδοση ${this.config.appVersionLabel || this.config.appVersion || ''} · ενημερωμένη`;
      } catch (error) {
        this.appVersionStatus = 'Ο έλεγχος ενημέρωσης δεν ολοκληρώθηκε.';
      }
    },
    slotsForDate(date) {
      const slots = (this.schedule.timesByDate && this.schedule.timesByDate[date]) || [];
      return Array.isArray(slots) ? slots.filter((slot) => slot && slot.time) : [];
    },
    slotOptionAvailabilityState(slot) {
      const state = slotAvailabilityState(slot);
      if (state === 'neutral' && isSlotBookable(slot)) {
        return 'available';
      }
      return state;
    },
    slotOptionAvailabilityLabel(slot) {
      const label = slotAvailabilityLabel(slot);
      if (label) return label;
      if (!slot) return '';
      return isSlotBookable(slot) ? 'διαθέσιμο' : 'εξαντλημένο';
    },
    dateAvailabilityState(date) {
      if (!this.availableDateSet.has(date)) return 'unavailable';

      const soldOutDates = Array.isArray(this.schedule.soldOutDates) ? this.schedule.soldOutDates : [];
      if (soldOutDates.includes(date)) return 'sold-out';

      const limitedDates = Array.isArray(this.schedule.limitedDates) ? this.schedule.limitedDates : [];
      if (limitedDates.includes(date)) return 'low';

      const slots = this.slotsForDate(date);
      if (!slots.length) return 'available';

      const bookableSlots = slots.filter((slot) => isSlotBookable(slot));
      if (!bookableSlots.length) return 'sold-out';
      if (bookableSlots.some((slot) => slotAvailabilityState(slot) === 'available')) return 'available';
      if (bookableSlots.some((slot) => slotAvailabilityState(slot) === 'neutral')) return 'available';
      if (bookableSlots.every((slot) => slotAvailabilityState(slot) === 'low')) return 'low';
      return 'available';
    },
    dateAvailabilityLabel(date) {
      const state = this.dateAvailabilityState(date);
      if (state === 'sold-out') return 'εξαντλήθηκαν';
      if (state === 'low') return 'περιορισμένη διαθεσιμότητα';
      if (state === 'available') return 'διαθέσιμο';
      return 'μη διαθέσιμο';
    },
    dateHolidayLabel(date) {
      const holidays = Array.isArray(this.schedule.holidays) ? this.schedule.holidays : [];
      const holiday = holidays.find((item) => item && String(item.date || '') === String(date));
      if (holiday) {
        return String(holiday.label || 'Αργία');
      }

      const closedExceptions = this.schedule.closedExceptions || {};
      if (closedExceptions && typeof closedExceptions === 'object' && closedExceptions[date]) {
        return String(closedExceptions[date]);
      }

      return '';
    },
    showCalendarHint(date) {
      const label = this.dateHolidayLabel(date);
      if (!label) return;

      window.clearTimeout(this.calendarHintTimer);
      this.selectSheet.calendarHintDate = this.selectSheet.calendarHintDate === date ? '' : date;

      if (this.selectSheet.calendarHintDate) {
        this.calendarHintTimer = window.setTimeout(() => {
          if (this.selectSheet.calendarHintDate === date) {
            this.selectSheet.calendarHintDate = '';
          }
        }, 2200);
      }
    },
    changeCalendarMonth(delta) {
      if (delta < 0 && !this.calendarCanGoPrev) return;
      if (delta > 0 && !this.calendarCanGoNext) return;
      this.selectSheet.calendarMonth = addMonths(this.selectSheet.calendarMonth, delta);
    },
    filteredTicketResults() {
      const selectedType = this.selectedTicketType || 'all';
      const selectedBuildingId = Number(this.selectedBuilding?.id || 0);
      const needle = normalizeSearchText(this.ticketSearch);

      return this.allTicketResults.filter((item) => {
        if (selectedType !== 'all' && item.type !== selectedType) return false;
        if (selectedBuildingId > 0 && Number(item.building_id || 0) !== selectedBuildingId) return false;

        if (!needle) return true;
        const haystack = normalizeSearchText([
          item.display_title,
          item.title,
          item.source_title,
          item.type_label,
          item.building_title,
          item.date_label,
          item.price_label,
        ].filter(Boolean).join(' '));

        return haystack.includes(needle);
      });
    },
    setPasswordMessage(message = '', type = 'neutral') {
      this.passwordSheet.message = message;
      this.passwordSheet.messageType = type;
    },
    accountDisplayName() {
      return [
        this.currentUser.firstName,
        this.currentUser.lastName,
      ].filter(Boolean).join(' ').trim()
        || this.currentUser.name
        || this.currentUser.email
        || 'Operator';
    },
    setAccountMessage(message = '', type = 'neutral') {
      this.accountSheet.message = message;
      this.accountSheet.messageType = type;
    },
    openAccountSheet() {
      window.clearTimeout(this.accountCloseTimer);
      this.accountSheet.firstName = this.currentUser.firstName || '';
      this.accountSheet.lastName = this.currentUser.lastName || '';
      this.accountSheet.email = this.currentUser.email || '';
      this.accountSheet.pending = false;
      this.setAccountMessage();
      this.accountSheet.open = true;
    },
    closeAccountSheet() {
      if (this.accountSheet.pending) return;
      this.accountSheet.open = false;
    },
    updateCurrentUserFromAccountSheet() {
      const firstName = String(this.accountSheet.firstName || '').trim();
      const lastName = String(this.accountSheet.lastName || '').trim();
      const email = String(this.accountSheet.email || '').trim();

      this.currentUser.firstName = firstName;
      this.currentUser.lastName = lastName;
      this.currentUser.email = email;
      this.currentUser.name = [firstName, lastName].filter(Boolean).join(' ').trim() || email || this.currentUser.name || '';
      this.config.currentUser = { ...(this.config.currentUser || {}), ...this.currentUser };
    },
    async submitAccountChange() {
      this.setAccountMessage();

      if (!String(this.accountSheet.firstName || '').trim() || !String(this.accountSheet.lastName || '').trim()) {
        this.setAccountMessage('Συμπληρώστε όνομα και επώνυμο.', 'error');
        return;
      }

      if (!String(this.accountSheet.email || '').trim()) {
        this.setAccountMessage('Συμπληρώστε email.', 'error');
        return;
      }

      this.accountSheet.pending = true;

      try {
        const formData = new FormData();
        formData.set('action', 'iw-auth-edit-account');
        formData.set('user_fields[first_name]', String(this.accountSheet.firstName || '').trim());
        formData.set('user_fields[last_name]', String(this.accountSheet.lastName || '').trim());
        formData.set('user_fields[user_email]', String(this.accountSheet.email || '').trim());

        const payload = await this.postAjaxForm(formData);

        this.updateCurrentUserFromAccountSheet();
        this.setAccountMessage(payload.message || 'Τα στοιχεία ενημερώθηκαν.', 'success');
        window.clearTimeout(this.accountCloseTimer);
        this.accountCloseTimer = window.setTimeout(() => this.closeAccountSheet(), 800);
      } catch (error) {
        this.setAccountMessage(error.message || 'Η ενημέρωση στοιχείων δεν ολοκληρώθηκε.', 'error');
      } finally {
        this.accountSheet.pending = false;
      }
    },
    openPasswordSheet() {
      window.clearTimeout(this.passwordCloseTimer);
      this.passwordSheet.newPassword = '';
      this.passwordSheet.confirmPassword = '';
      this.passwordSheet.pending = false;
      this.setPasswordMessage();
      this.passwordSheet.open = true;
    },
    closePasswordSheet() {
      if (this.passwordSheet.pending) return;
      this.passwordSheet.open = false;
    },
    isValidPassword(value) {
      return /(?=^.{8,}$)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[a-z]).*$/.test(String(value || ''));
    },
    async postAjaxForm(formData) {
      const response = await fetch(this.config.ajaxUrl || window.location.href, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
        },
        body: formData,
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || payload?.success === false) {
        throw new Error(payload?.data?.message || payload?.message || 'Η ενέργεια δεν ολοκληρώθηκε.');
      }
      return payload?.data || payload || {};
    },
    async submitPasswordChange() {
      const password = String(this.passwordSheet.newPassword || '');
      const confirmation = String(this.passwordSheet.confirmPassword || '');

      this.setPasswordMessage();

      if (password !== confirmation) {
        this.setPasswordMessage('Οι κωδικοί δεν ταιριάζουν.', 'error');
        return;
      }

      if (!this.isValidPassword(password)) {
        this.setPasswordMessage('Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες, ένα πεζό γράμμα και ένα ειδικό χαρακτήρα.', 'error');
        return;
      }

      this.passwordSheet.pending = true;

      try {
        const formData = new FormData();
        formData.set('action', 'iw-auth-change-password');
        formData.set('security', this.config.passwordNonce || '');
        formData.set('user_fields[new_user_pass]', password);
        formData.set('user_fields[new_user_pass_confirmation]', confirmation);

        const payload = await this.postAjaxForm(formData);
        this.passwordSheet.newPassword = '';
        this.passwordSheet.confirmPassword = '';
        this.setPasswordMessage(payload.message || 'Ο κωδικός άλλαξε.', 'success');
        window.clearTimeout(this.passwordCloseTimer);
        this.passwordCloseTimer = window.setTimeout(() => this.closePasswordSheet(), 800);
      } catch (error) {
        this.setPasswordMessage(error.message || 'Η αλλαγή κωδικού δεν ολοκληρώθηκε.', 'error');
      } finally {
        this.passwordSheet.pending = false;
      }
    },
    ticketCategoryKey(category, fallbackIndex = 0) {
      return String(category?.key || `category-${fallbackIndex}`);
    },
    limitNumber(value, fallback = 0) {
      if (value === null || value === undefined || value === '' || value === 'null') {
        return fallback;
      }
      const number = Number(value);
      return Number.isFinite(number) ? Math.max(0, Math.floor(number)) : fallback;
    },
    globalMinTickets() {
      return Math.max(1, this.limitNumber(this.ticketData.min_tickets, 1));
    },
    globalMaxTickets() {
      return this.limitNumber(this.ticketData.max_tickets, 0);
    },
    ticketCategoryMin(category) {
      return this.limitNumber(category?.min_tickets, 0);
    },
    ticketCategoryMax(category) {
      return this.limitNumber(category?.max_tickets, 0);
    },
    ticketCategoryLimitMessage(category, fallbackIndex = 0, kind = 'min') {
      const label = category?.label || `Κατηγορία ${fallbackIndex + 1}`;
      if (kind === 'max') {
        return `Για την κατηγορία "${label}" μπορείτε να επιλέξετε έως ${this.ticketCountLabel(this.ticketCategoryMax(category), 'lower')}.`;
      }
      return `Για την κατηγορία "${label}" πρέπει να επιλέξετε τουλάχιστον ${this.ticketCountLabel(this.ticketCategoryMin(category), 'lower')}.`;
    },
    ticketCategoryByKey(categoryKey) {
      const normalizedKey = String(categoryKey || '');
      let match = null;
      this.ticketCategories.some((category, index) => {
        if (this.ticketCategoryKey(category, index) !== normalizedKey) return false;
        match = category;
        return true;
      });
      return match;
    },
    defaultCategoryValueFor(categoryKey = '') {
      const category = categoryKey ? this.ticketCategoryByKey(categoryKey) : this.ticketCategories[0];
      return category?.subcategories?.[0]?.value || '';
    },
    ticketCategoryPriceLabel(category) {
      const prices = (category?.subcategories || [])
        .map((sub) => Number(sub.price))
        .filter((price) => Number.isFinite(price));
      const rawMin = category?.min_price ?? category?.price;
      const rawMax = category?.max_price ?? category?.price;
      const categoryMin = rawMin === '' || rawMin === null || rawMin === undefined ? NaN : Number(rawMin);
      const categoryMax = rawMax === '' || rawMax === null || rawMax === undefined ? NaN : Number(rawMax);
      const min = Number.isFinite(categoryMin) ? categoryMin : Math.min(...prices);
      const max = Number.isFinite(categoryMax) ? categoryMax : Math.max(...prices);

      if (!Number.isFinite(min) || min <= 0) return '';
      return Number.isFinite(max) && max > min ? `Από ${money(min)}` : money(min);
    },
    ticketCategoryCount(categoryKey) {
      const category = this.ticketCategoryByKey(categoryKey);
      if (!category) return 0;
      const key = String(categoryKey || this.ticketCategoryKey(category));
      return this.visitors.filter((visitor) => this.visitorCategoryKey(visitor) === key).length;
    },
    canAddTicketCategory(categoryKey) {
      const category = this.ticketCategoryByKey(categoryKey);
      if (!category) return false;
      const globalMax = this.globalMaxTickets();
      if (globalMax && this.visitors.length >= globalMax) return false;
      const max = this.ticketCategoryMax(category);
      return !max || this.ticketCategoryCount(categoryKey) < max;
    },
    canDecreaseTicketCategory(categoryKey) {
      const category = this.ticketCategoryByKey(categoryKey);
      if (!category) return false;
      return this.ticketCategoryCount(categoryKey) > this.ticketCategoryMin(category);
    },
    increaseTicketCategory(categoryKey) {
      const category = this.ticketCategoryByKey(categoryKey);
      if (!category) return;
      const key = String(categoryKey || this.ticketCategoryKey(category, this.ticketCategories.indexOf(category)));
      if (!this.canAddTicketCategory(categoryKey)) {
        const globalMax = this.globalMaxTickets();
        const max = this.ticketCategoryMax(category);
        const message = globalMax && this.visitors.length >= globalMax
          ? `Μπορείτε να επιλέξετε έως ${this.ticketCountLabel(globalMax, 'lower')}.`
          : this.ticketCategoryLimitMessage(category, this.ticketCategories.indexOf(category), max > 0 ? 'max' : 'min');
        this.setStatus(message, 'error');
        return;
      }

      this.addVisitor(key);
      const newVisitorIndex = this.visitors.length - 1;
      const shouldChooseProtectedSubcategory = category.subcategories.length > 1
        && category.subcategories.some((sub) => this.ticketSubcategoryRequiresMemberVerification(sub));

      if (shouldChooseProtectedSubcategory) {
        this.openVisitorCategory(newVisitorIndex, key);
        this.setStatus('Επίλεξε τον τύπο εισιτηρίου για τον έλεγχο συνδρομής.', 'neutral');
        return;
      }

      this.setStatus('', 'neutral');
    },
    decreaseTicketCategory(categoryKey) {
      const category = this.ticketCategoryByKey(categoryKey);
      if (!category) return;
      if (!this.canDecreaseTicketCategory(categoryKey)) {
        this.setStatus(this.ticketCategoryLimitMessage(category, this.ticketCategories.indexOf(category), 'min'), 'error');
        return;
      }
      const key = String(categoryKey || this.ticketCategoryKey(category));

      for (let index = this.visitors.length - 1; index >= 0; index -= 1) {
        if (this.visitorCategoryKey(this.visitors[index]) === key) {
          if (this.removeVisitor(index)) {
            this.setStatus('', 'neutral');
          }
          return;
        }
      }
    },
    categoryByValue(value) {
      for (let categoryIndex = 0; categoryIndex < this.ticketCategories.length; categoryIndex += 1) {
        const category = this.ticketCategories[categoryIndex];
        for (const sub of category.subcategories) {
          if (sub.value === value) {
            return { category, sub, key: this.ticketCategoryKey(category, categoryIndex) };
          }
        }
      }
      return null;
    },
    visitorCategoryKey(visitor) {
      if (!visitor) return '';
      const directKey = String(visitor.categoryKey || '');
      if (directKey) return directKey;
      return this.categoryByValue(visitor.categoryId)?.key || '';
    },
    visitorCategoryEntry(visitor) {
      const selected = this.categoryByValue(visitor?.categoryId);
      if (selected) return selected;
      const category = this.ticketCategoryByKey(visitor?.categoryKey);
      return category ? { category, sub: null, key: this.ticketCategoryKey(category) } : null;
    },
    visitorCategoryLabel(visitor) {
      const type = this.categoryByValue(visitor.categoryId);
      return type ? `${type.sub.label || type.sub.value} · ${money(type.sub.price)}` : 'Επιλογή Κατηγορίας';
    },
    visitorCategorySubtitle(visitor) {
      return this.visitorCategoryEntry(visitor)?.category?.label || '';
    },
    visitorRequiresName(visitor) {
      return Boolean(this.visitorCategoryEntry(visitor)?.category?.require_full_name);
    },
    visitorRequiresManualName(visitor) {
      if (this.visitorRequiresMemberVerification(visitor)) {
        return this.visitorIsMemberCompanion(visitor);
      }

      return this.visitorRequiresName(visitor);
    },
    ticketCategoryRequiresMemberVerification(category = {}) {
      return hasMemberVerificationTicketCode(category);
    },
    ticketSubcategoryRequiresMemberVerification(subcategory = {}) {
      return hasMemberVerificationTicketCode(subcategory);
    },
    visitorRequiresMemberVerification(visitor) {
      const entry = this.visitorCategoryEntry(visitor);
      if (!entry) return false;
      return this.ticketCategoryRequiresMemberVerification(entry.category)
        || this.ticketSubcategoryRequiresMemberVerification(entry.sub);
    },
    isMemberVerificationActive(verification = null) {
      return Boolean(
        verification?.valid
        && verification?.user
        && verification?.subscription?.valid !== false
      );
    },
    visitorHasActiveMemberVerification(visitor) {
      return !this.visitorRequiresMemberVerification(visitor)
        || this.isMemberVerificationActive(visitor?.memberVerification);
    },
    visitorMemberCardId(visitor) {
      return String(visitor?.memberCardId || visitor?.memberVerification?.user?.card_id || '').trim();
    },
    visitorIsMemberCompanion(visitor) {
      return Boolean(
        visitor
        && this.visitorRequiresMemberVerification(visitor)
        && this.isMemberVerificationActive(visitor?.memberVerification)
        && visitor.memberVerificationRole === 'companion'
      );
    },
    memberVerificationUsageCount(cardId, excludedVisitorUid = 0) {
      const normalizedCardId = String(cardId || '').trim();
      if (!normalizedCardId) return 0;

      return this.visitors.filter((visitor) => (
        Number(visitor.uid) !== Number(excludedVisitorUid || 0)
        && this.visitorRequiresMemberVerification(visitor)
        && this.isMemberVerificationActive(visitor?.memberVerification)
        && this.visitorMemberCardId(visitor) === normalizedCardId
      )).length;
    },
    memberCardHasPrimaryUsage(cardId, excludedVisitorUid = 0) {
      const normalizedCardId = String(cardId || '').trim();
      if (!normalizedCardId) return false;

      return this.visitors.some((visitor) => (
        Number(visitor.uid) !== Number(excludedVisitorUid || 0)
        && this.visitorRequiresMemberVerification(visitor)
        && this.isMemberVerificationActive(visitor?.memberVerification)
        && this.visitorMemberCardId(visitor) === normalizedCardId
        && this.visitorMemberVerificationRole(visitor) === 'member'
      ));
    },
    linkedCompanionVisitors(cardId, primaryVisitorUid = 0) {
      const normalizedCardId = String(cardId || '').trim();
      if (!normalizedCardId) return [];

      return this.visitors.filter((visitor) => (
        Number(visitor.uid) !== Number(primaryVisitorUid || 0)
        && this.visitorRequiresMemberVerification(visitor)
        && this.isMemberVerificationActive(visitor?.memberVerification)
        && this.visitorMemberCardId(visitor) === normalizedCardId
        && (visitor.memberVerificationRole === 'companion' || this.visitorMemberVerificationRole(visitor) === 'companion')
      ));
    },
    syncLinkedMemberCompanions(previousCardId, verification = {}, nextCardId = '', primaryVisitorUid = 0) {
      const normalizedPreviousCardId = String(previousCardId || '').trim();
      const normalizedNextCardId = String(nextCardId || '').trim();
      if (!normalizedPreviousCardId || !normalizedNextCardId) return;

      this.linkedCompanionVisitors(normalizedPreviousCardId, primaryVisitorUid).forEach((visitor) => {
        visitor.memberVerification = verification;
        visitor.memberCardId = normalizedNextCardId;
        visitor.memberVerificationRole = 'companion';
      });
    },
    canAddMemberCompanion(visitor) {
      if (
        !this.visitorRequiresMemberVerification(visitor)
        || !this.isMemberVerificationActive(visitor?.memberVerification)
        || this.visitorMemberVerificationRole(visitor) !== 'member'
      ) {
        return false;
      }

      const cardId = this.visitorMemberCardId(visitor);
      if (!cardId || this.linkedCompanionVisitors(cardId, visitor.uid).length) {
        return false;
      }

      return this.canAddTicketCategory(this.visitorCategoryKey(visitor));
    },
    addMemberCompanion(index) {
      const primary = this.visitors[index];
      if (!primary || !this.canAddMemberCompanion(primary)) {
        this.setStatus('Δεν μπορεί να προστεθεί άλλος συνοδός για αυτό το μέλος.', 'error');
        return false;
      }

      const companion = {
        uid: ++this.visitorCounter,
        categoryKey: primary.categoryKey,
        categoryId: primary.categoryId,
        first: '',
        last: '',
        memberCardId: this.visitorMemberCardId(primary),
        memberVerificationRole: 'companion',
        memberVerification: primary.memberVerification,
      };

      this.visitors.splice(index + 1, 0, companion);
      this.clearValidationFocusTarget();
      this.setStatus('', 'neutral');
      return true;
    },
    visitorMemberVerificationRole(visitor) {
      const cardId = this.visitorMemberCardId(visitor);
      if (!cardId || !this.visitorRequiresMemberVerification(visitor)) return '';

      if (visitor.memberVerificationRole === 'companion') {
        return 'companion';
      }

      let seenSameMember = false;
      for (const currentVisitor of this.visitors) {
        if (
          this.visitorRequiresMemberVerification(currentVisitor)
          && this.isMemberVerificationActive(currentVisitor?.memberVerification)
          && this.visitorMemberCardId(currentVisitor) === cardId
        ) {
          if (Number(currentVisitor.uid) === Number(visitor.uid)) {
            return seenSameMember ? 'companion' : 'member';
          }
          seenSameMember = true;
        }
      }

      return visitor.memberVerificationRole || 'member';
    },
    firstMissingMemberVerificationIndex() {
      return this.visitors.findIndex((visitor) => (
        this.visitorRequiresMemberVerification(visitor)
        && !this.visitorHasActiveMemberVerification(visitor)
      ));
    },
    memberVerificationVisitorIndex() {
      const uid = Number(this.memberVerificationSheet.visitorUid || 0);
      if (!uid) return -1;
      return this.visitors.findIndex((visitor) => Number(visitor.uid) === uid);
    },
    memberVerificationVisitor() {
      const index = this.memberVerificationVisitorIndex();
      return index >= 0 ? this.visitors[index] : null;
    },
    visitorMemberVerificationTitle(visitor) {
      if (!this.visitorRequiresMemberVerification(visitor)) return '';
      if (this.isMemberVerificationActive(visitor?.memberVerification)) {
        return visitor.memberVerification?.user?.name || 'Ενεργή συνδρομή';
      }
      return 'Απαιτείται έλεγχος συνδρομής';
    },
    visitorMemberVerificationSubtitle(visitor) {
      if (!this.visitorRequiresMemberVerification(visitor)) return '';
      if (this.isMemberVerificationActive(visitor?.memberVerification)) {
        const subscription = visitor.memberVerification?.subscription || {};
        const roleLabel = this.visitorMemberVerificationRole(visitor) === 'companion' ? 'Συνοδός μέλους' : 'Μέλος';
        return subscription.name ? `${roleLabel} - ${subscription.name}` : roleLabel;
      }
      return 'Αναζήτηση μέλους ή σάρωση κάρτας';
    },
    resetMemberVerificationSheet() {
      this.memberVerificationSheet = {
        open: false,
        mode: 'search',
        visitorUid: 0,
        query: '',
        message: '',
        messageType: 'neutral',
        pending: false,
        matches: [],
        result: null,
        cameraPending: false,
        cameraError: '',
      };
    },
    openMemberVerification(index) {
      const visitor = this.visitors[index];
      if (!visitor || !this.visitorRequiresMemberVerification(visitor)) return;

      this.memberVerificationSheet = {
        open: true,
        mode: 'search',
        visitorUid: visitor.uid,
        query: '',
        message: 'Αναζήτησε μέλος με ενεργή συνδρομή ή σκάναρε την κάρτα του.',
        messageType: 'neutral',
        pending: false,
        matches: [],
        result: null,
        cameraPending: false,
        cameraError: '',
      };
    },
    closeMemberVerification() {
      this.memberVerificationSheet.open = false;
      this.memberVerificationSheet.mode = 'search';
      this.memberVerificationSheet.cameraPending = false;
      this.memberVerificationSheet.cameraError = '';
    },
    setMemberVerificationMode(mode) {
      this.memberVerificationSheet.mode = mode === 'scan' ? 'scan' : 'search';
      this.memberVerificationSheet.cameraError = '';
      this.memberVerificationSheet.message = this.memberVerificationSheet.mode === 'scan'
        ? 'Σκάναρε την κάρτα μέλους.'
        : 'Αναζήτησε μέλος με ενεργή συνδρομή.';
      this.memberVerificationSheet.messageType = 'neutral';
    },
    memberVerificationContextParams() {
      const building = this.selectedBuildingPayload();
      return {
        building_id: building.building_id || '',
        gate: building.building_name || '',
      };
    },
    setMemberVerificationMessage(message = '', type = 'neutral') {
      this.memberVerificationSheet.message = message;
      this.memberVerificationSheet.messageType = type;
    },
    async searchMemberForVerification() {
      const query = String(this.memberVerificationSheet.query || '').trim();
      if (query.length < 3) {
        this.setMemberVerificationMessage('Πληκτρολόγησε τουλάχιστον 3 χαρακτήρες.', 'error');
        return;
      }

      const endpoint = this.endpoint('searchMemberCard');
      if (!endpoint) {
        this.setMemberVerificationMessage('Δεν βρέθηκε endpoint αναζήτησης μέλους.', 'error');
        return;
      }

      this.memberVerificationSheet.pending = true;
      this.memberVerificationSheet.matches = [];
      this.memberVerificationSheet.result = null;
      this.setMemberVerificationMessage('Αναζήτηση μέλους...', 'neutral');

      try {
        const url = this.buildEndpointUrl(endpoint, {
          q: query,
          ...this.memberVerificationContextParams(),
        });
        const payload = await this.apiFetch(url);

        if (payload?.requires_selection && Array.isArray(payload.matches)) {
          this.memberVerificationSheet.matches = payload.matches.filter((match) => match?.card_id);
          if (!this.memberVerificationSheet.matches.length) {
            this.setMemberVerificationMessage(payload?.error || 'Δεν βρέθηκε μέλος.', 'error');
            return;
          }
          this.setMemberVerificationMessage(`Βρέθηκαν ${this.memberVerificationSheet.matches.length} μέλη. Επίλεξε ένα.`, 'success');
          return;
        }

        if (payload?.valid === false && !payload?.user) {
          this.setMemberVerificationMessage(payload?.error || 'Δεν βρέθηκε μέλος.', 'error');
          return;
        }

        this.applyMemberVerification(payload, query);
      } catch (error) {
        this.setMemberVerificationMessage(error.message || 'Η αναζήτηση απέτυχε.', 'error');
      } finally {
        this.memberVerificationSheet.pending = false;
      }
    },
    async verifyMemberCardForVisitor(cardId, cachedVerification = null) {
      const normalizedCardId = String(cardId || '').trim();
      if (!normalizedCardId && !cachedVerification) return;

      this.memberVerificationSheet.result = null;

      if (cachedVerification) {
        this.applyMemberVerification(cachedVerification, normalizedCardId);
        return;
      }

      const endpoint = this.endpoint('verifyMemberCard');
      if (!endpoint) {
        this.setMemberVerificationMessage('Δεν βρέθηκε endpoint ελέγχου κάρτας μέλους.', 'error');
        return;
      }

      this.memberVerificationSheet.pending = true;
      this.setMemberVerificationMessage('Έλεγχος συνδρομής...', 'neutral');

      try {
        const url = this.buildEndpointUrl(endpoint, {
          'member-card-id': normalizedCardId,
          ...this.memberVerificationContextParams(),
        });
        const payload = await this.apiFetch(url);
        this.applyMemberVerification(payload, normalizedCardId);
      } catch (error) {
        this.setMemberVerificationMessage(error.message || 'Ο έλεγχος συνδρομής απέτυχε.', 'error');
      } finally {
        this.memberVerificationSheet.pending = false;
      }
    },
    async verifyMemberScanPayload(payload) {
      const memberCardId = resolveMemberCardPayload(payload);
      if (!memberCardId) {
        this.memberVerificationSheet.cameraError = 'Το QR δεν περιέχει κάρτα μέλους.';
        this.setMemberVerificationMessage('Το QR δεν περιέχει κάρτα μέλους.', 'error');
        return;
      }

      await this.verifyMemberCardForVisitor(memberCardId);
    },
    applyMemberVerification(verification = {}, cardId = '') {
      if (!this.isMemberVerificationActive(verification)) {
        this.memberVerificationSheet.result = null;
        const reason = verification?.subscription?.reason
          || verification?.subscription_reason
          || verification?.error
          || 'Δεν βρέθηκε ενεργή συνδρομή για αυτό το μέλος.';
        this.setMemberVerificationMessage(reason, 'error');
        return;
      }

      const index = this.memberVerificationVisitorIndex();
      const visitor = index >= 0 ? this.visitors[index] : null;
      if (!visitor) {
        this.memberVerificationSheet.result = null;
        this.setMemberVerificationMessage('Δεν βρέθηκε το εισιτήριο που ελέγχεται.', 'error');
        return;
      }
      const previousMemberCardId = this.visitorMemberCardId(visitor);

      const memberCardId = String(verification?.user?.card_id || cardId || '').trim();
      if (!memberCardId) {
        this.memberVerificationSheet.result = null;
        this.setMemberVerificationMessage('Δεν βρέθηκε αριθμός κάρτας μέλους.', 'error');
        return;
      }

      if (this.memberCardHasPrimaryUsage(memberCardId, visitor.uid)) {
        this.memberVerificationSheet.result = null;
        this.setMemberVerificationMessage('Το μέλος έχει ήδη προστεθεί. Χρησιμοποίησε την Προσθήκη συνοδού.', 'error');
        return;
      }

      const existingUsageCount = this.memberVerificationUsageCount(memberCardId, visitor.uid);
      if (existingUsageCount >= memberVerificationSlotLimit) {
        this.memberVerificationSheet.result = null;
        this.setMemberVerificationMessage('Το μέλος έχει ήδη εισιτήριο και έναν συνοδό για αυτό το slot.', 'error');
        return;
      }

      this.memberVerificationSheet.result = verification;
      visitor.memberVerification = verification;
      visitor.memberCardId = memberCardId;
      visitor.memberVerificationRole = 'member';
      this.clearValidationFocusTarget();

      this.syncLinkedMemberCompanions(previousMemberCardId || memberCardId, verification, memberCardId, visitor.uid);

      if (!String(visitor.first || '').trim() && !String(visitor.last || '').trim()) {
        const parts = String(verification?.user?.name || '').trim().split(/\s+/).filter(Boolean);
        visitor.first = parts.shift() || '';
        visitor.last = parts.join(' ');
      }

      this.setStatus('Η ενεργή συνδρομή επιβεβαιώθηκε.', 'success');
      this.closeMemberVerification();
    },
    syncVisitorMemberRequirement(index) {
      const visitor = this.visitors[index];
      if (!visitor) return;

      if (!this.visitorRequiresMemberVerification(visitor)) {
        delete visitor.memberVerification;
        delete visitor.memberCardId;
        delete visitor.memberVerificationRole;
        return;
      }

      if (!this.isMemberVerificationActive(visitor.memberVerification)) {
        this.openMemberVerification(index);
      }
    },
    addVisitor(categoryKey = '', options = {}) {
      const category = categoryKey ? this.ticketCategoryByKey(categoryKey) : this.ticketCategories[0];
      if (!category) return false;
      const key = categoryKey ? String(categoryKey) : this.ticketCategoryKey(category, this.ticketCategories.indexOf(category));
      if (!options.bypassLimits && !this.canAddTicketCategory(key)) return false;
      const subcategories = Array.isArray(category.subcategories) ? category.subcategories : [];
      const categoryId = subcategories.length === 1 ? subcategories[0].value : '';
      this.visitors.push({
        uid: ++this.visitorCounter,
        categoryKey: key,
        categoryId,
        first: '',
        last: '',
        memberCardId: '',
        memberVerificationRole: '',
        memberVerification: null,
      });
      if (options.skipMemberPrompt !== true) {
        this.syncVisitorMemberRequirement(this.visitors.length - 1);
      }
      return true;
    },
    applyInitialCategoryMinimums() {
      let remaining = this.globalMaxTickets();
      if (remaining <= 0) {
        remaining = Infinity;
      }

      this.ticketCategories.forEach((category, categoryIndex) => {
        const min = this.ticketCategoryMin(category);
        if (min <= 0 || remaining <= 0) return;

        const max = this.ticketCategoryMax(category);
        const target = Math.min(min, max > 0 ? max : min, remaining);
        const key = this.ticketCategoryKey(category, categoryIndex);

        for (let index = 0; index < target; index += 1) {
          this.addVisitor(key, { bypassLimits: true, skipMemberPrompt: true });
        }

        if (remaining !== Infinity) {
          remaining = Math.max(0, remaining - target);
        }
      });
    },
    clearSelectedTicket() {
      this.ticketDetailRequestId += 1;
      this.selectedTicket = null;
      this.selectedTicketListItem = null;
      this.selectedTicketData = null;
      this.ticketDetailLoading = false;
      this.selectedDate = '';
      this.selectedTime = '';
      this.visitors = [];
      this.result = null;
      this.resultContext = { source: '', title: '' };
      this.selectedPaymentMethod = '';
      this.posReference = '';
      this.terminalId = '';
      this.resetDeliveryOptions();
      this.resetMemberVerificationSheet();
      this.clearValidationFocusTarget();
    },
    prepareTicketSelection(ticketId, item = null) {
      if (!ticketId) return false;
      this.ticketDetailRequestId += 1;
      this.selectedTicket = ticketId;
      this.selectedTicketListItem = item || this.findTicketListItem(ticketId);
      this.selectedTicketData = null;
      this.ticketDetailLoading = false;
      this.selectedDate = '';
      this.selectedTime = '';
      this.visitors = [];
      this.result = null;
      this.resultContext = { source: '', title: '' };
      this.selectedPaymentMethod = '';
      this.posReference = '';
      this.terminalId = '';
      this.resetDeliveryOptions();
      this.resetMemberVerificationSheet();
      this.clearValidationFocusTarget();
      this.setStatus('', 'neutral');
      return true;
    },
    visitorRemovalUids(index) {
      const visitor = this.visitors[index];
      const removalUids = new Set();
      if (!visitor) return removalUids;

      removalUids.add(Number(visitor.uid));

      if (
        this.visitorRequiresMemberVerification(visitor)
        && this.isMemberVerificationActive(visitor?.memberVerification)
        && this.visitorMemberVerificationRole(visitor) === 'member'
      ) {
        this.linkedCompanionVisitors(this.visitorMemberCardId(visitor), visitor.uid).forEach((companion) => {
          removalUids.add(Number(companion.uid));
        });
      }

      return removalUids;
    },
    canRemoveVisitor(index) {
      const visitor = this.visitors[index];
      if (!visitor) return false;

      const removalUids = this.visitorRemovalUids(index);
      const affectedCounts = new Map();

      this.visitors.forEach((currentVisitor) => {
        if (!removalUids.has(Number(currentVisitor.uid))) return;
        const key = this.visitorCategoryKey(currentVisitor);
        if (!key || !this.ticketCategoryByKey(key)) return;
        affectedCounts.set(key, (affectedCounts.get(key) || 0) + 1);
      });

      if (!affectedCounts.size) return true;

      for (const [key, removalCount] of affectedCounts.entries()) {
        const category = this.ticketCategoryByKey(key);
        if (this.ticketCategoryCount(key) - removalCount < this.ticketCategoryMin(category)) {
          return false;
        }
      }

      return true;
    },
    removeVisitor(index, options = {}) {
      const visitor = this.visitors[index];
      if (!visitor) return false;
      if (!options.bypassLimits && !this.canRemoveVisitor(index)) {
        const category = this.ticketCategoryByKey(this.visitorCategoryKey(visitor));
        this.setStatus(this.ticketCategoryLimitMessage(category, this.ticketCategories.indexOf(category), 'min'), 'error');
        return false;
      }

      const removalUids = this.visitorRemovalUids(index);
      for (let currentIndex = this.visitors.length - 1; currentIndex >= 0; currentIndex -= 1) {
        if (removalUids.has(Number(this.visitors[currentIndex]?.uid))) {
          this.visitors.splice(currentIndex, 1);
        }
      }

      if (removalUids.has(Number(this.memberVerificationSheet.visitorUid || 0))) {
        this.closeMemberVerification();
      }
      if (removalUids.has(Number(this.validationFocusTarget?.uid || 0))) {
        this.clearValidationFocusTarget();
      }
      return true;
    },
    openSelect(kind) {
      let options = [];
      let title = 'Επιλογή';
      let icon = 'pin';
      let searchable = false;
      let searchPlaceholder = 'Αναζήτηση';
      let visitorIndex = -1;

      if (kind === 'building') {
        title = 'Επιλογή μουσείου';
        icon = 'pin';
        searchable = true;
        searchPlaceholder = 'Αναζήτηση μουσείου';
        options = [
          {
            value: 'all',
            label: 'Όλα',
            subtitle: 'Όλα τα μουσεία',
            selected: !this.selectedBuilding?.id,
          },
          ...this.buildings.map((building) => ({
            value: String(building.id),
            label: building.title || '',
            subtitle: building.address || '',
            selected: Number(building.id) === Number(this.selectedBuilding?.id || 0),
          })),
        ];
      } else if (kind === 'date') {
        if (!this.availableDates.length) return;
        title = 'Επιλογή ημερομηνίας';
        icon = 'calendar';
        options = [];
      } else if (kind === 'time') {
        if (!this.selectedDateSlots.length) return;
        title = 'Επιλογή ώρας';
        icon = 'clock';
        options = this.selectedDateSlots.map((slot) => ({
          value: slot.time,
          label: timeLabel(slot),
          subtitle: this.slotOptionAvailabilityLabel(slot),
          selected: slot.time === this.selectedTime,
          disabled: !isSlotBookable(slot),
          availabilityState: this.slotOptionAvailabilityState(slot),
        }));
      } else if (kind === 'payment') {
        if (!this.enabledPaymentMethods.length) return;
        title = 'POS mode';
        icon = 'card';
        options = this.enabledPaymentMethods.map((method) => ({
          value: method.id,
          label: method.label || method.id,
          subtitle: method.api_integration ? 'API POS' : (method.type === 'cash' ? 'Ταμείο' : 'Manual POS'),
          selected: method.id === this.settingsPaymentMethod,
        }));
      } else if (kind === 'printer') {
        title = 'Εκτυπωτής';
        icon = 'printer';
        options = this.printerTargets.map((target) => ({
          value: target.id,
          label: target.label,
          subtitle: target.subtitle || '',
          selected: target.id === normalizePrinterId(this.selectedPrinterId),
        }));
      } else if (kind === 'theme') {
        title = 'Theme mode';
        icon = 'theme';
        options = Object.entries(themeModes).map(([value, option]) => ({
          value,
          label: option.label,
          subtitle: option.subtitle,
          selected: value === this.themeMode,
        }));
      } else if (kind === 'language') {
        title = 'Γλώσσα';
        icon = 'language';
        options = Object.entries(languages).map(([value, option]) => ({
          value,
          label: option.label,
          subtitle: option.subtitle,
          selected: value === this.language,
        }));
      }

      this.selectSheet = {
        open: true,
        kind,
        title,
        icon,
        search: '',
        searchable,
        searchPlaceholder,
        visitorIndex,
        categoryKey: '',
        calendarMonth: kind === 'date' ? firstOfMonth(this.selectedDate || this.availableDates[0]) : '',
        calendarHintDate: '',
        options,
      };
    },
    openVisitorCategory(index, categoryKey = '') {
      const visitor = this.visitors[index];
      if (!visitor) return;

      const options = [];
      const categories = categoryKey
        ? [this.ticketCategoryByKey(categoryKey)].filter(Boolean)
        : this.ticketCategories;

      categories.forEach((category) => {
        category.subcategories.forEach((sub) => {
          options.push({
            value: sub.value,
            label: `${sub.label || sub.value} · ${money(sub.price)}`,
            subtitle: category.label || '',
            selected: visitor.categoryId === sub.value,
          });
        });
      });

      this.selectSheet = {
        open: true,
        kind: 'visitor-category',
        title: 'Τύπος εισιτηρίου',
        icon: 'ticket',
        search: '',
        searchable: options.length > 6,
        searchPlaceholder: 'Αναζήτηση τύπου',
        visitorIndex: index,
        categoryKey: categories.length === 1 ? this.ticketCategoryKey(categories[0]) : '',
        calendarMonth: '',
        calendarHintDate: '',
        options,
      };
    },
    chooseSelectOption(value) {
      let memberVerificationIndex = -1;

      if (this.selectSheet.kind === 'building') {
        const nextBuilding = value === 'all'
          ? null
          : this.buildings.find((building) => String(building.id) === String(value)) || this.selectedBuilding;
        const hasChanged = String(nextBuilding?.id || '') !== String(this.selectedBuilding?.id || '');
        this.selectedBuilding = nextBuilding;
        if (hasChanged) {
          this.persistSelectedBuilding();
          this.clearSelectedTicket();
        }
      } else if (this.selectSheet.kind === 'date') {
        this.selectedDate = value;
        if (this.selectedDateSlots.length) {
          if (!this.availableTimes.some((slot) => slot.time === this.selectedTime)) {
            this.selectedTime = this.availableTimes[0]?.time || '';
          }
          this.openSelect('time');
          return;
        }
      } else if (this.selectSheet.kind === 'time') {
        this.selectedTime = value;
      } else if (this.selectSheet.kind === 'visitor-category') {
        const visitor = this.visitors[this.selectSheet.visitorIndex];
        if (visitor) {
          visitor.categoryId = value;
          visitor.categoryKey = this.selectSheet.categoryKey || this.categoryByValue(value)?.key || visitor.categoryKey || '';
          this.clearValidationFocusTarget();
          memberVerificationIndex = this.selectSheet.visitorIndex;
        }
      } else if (this.selectSheet.kind === 'payment') {
        this.settingsPaymentMethod = value;
        this.persistSelectedPaymentMethod();
      } else if (this.selectSheet.kind === 'printer') {
        this.selectedPrinterId = normalizePrinterId(value);
        this.persistSelectedPrinterId();
      } else if (this.selectSheet.kind === 'theme') {
        this.setThemeMode(value);
      } else if (this.selectSheet.kind === 'language') {
        this.setLanguage(value);
      }
      this.closeSelect();

      if (memberVerificationIndex >= 0) {
        this.syncVisitorMemberRequirement(memberVerificationIndex);
      }
    },
    closeSelect() {
      window.clearTimeout(this.calendarHintTimer);
      this.selectSheet.open = false;
      this.selectSheet.calendarHintDate = '';
    },
    async loadConfig() {
      try {
        const payload = await this.apiFetch(this.endpoint('config'));
        this.ticketFilters = payload.ticket_filters || [];
        this.paymentMethods = payload.payment_methods || [];
        if (payload.zebra_print) {
          this.config.zebraPrint = payload.zebra_print;
        }
      } catch (error) {
        this.setStatus(error.message, 'error');
      }
    },
    debouncedSearchTickets() {
      window.clearTimeout(this.searchTimer);
    },
    selectTicketFilter(filterId) {
      this.selectedTicketType = filterId || 'all';
    },
    findTicketListItem(ticketId = this.selectedTicket, sourceId = this.selectedTicketData?.source_id) {
      const selectedTicketId = Number(ticketId || this.selectedTicketData?.id || 0);
      const selectedSourceId = Number(sourceId || this.selectedTicketData?.source_id || ticketId || 0);
      if (!selectedTicketId && !selectedSourceId) return null;

      return this.allTicketResults.find((item) => {
        const itemTicketId = Number(item.ticket_id || item.id || 0);
        const itemSourceId = Number(item.source_id || 0);
        return itemTicketId === selectedTicketId || (selectedSourceId > 0 && itemSourceId === selectedSourceId);
      }) || null;
    },
    async searchTickets(force = false) {
      if (!this.endpoint('tickets')) return;
      if (this.ticketsLoaded && !force) return;
      const requestId = ++this.ticketSearchRequestId;
      this.ticketsLoading = true;
      try {
        const url = new URL(this.endpoint('tickets'), window.location.origin);
        url.searchParams.set('limit', '200');
        const payload = await this.apiFetch(url.toString());
        if (requestId === this.ticketSearchRequestId) {
          this.allTicketResults = payload.items || [];
          this.ticketsLoaded = true;
        }
      } catch (error) {
        if (requestId === this.ticketSearchRequestId) {
          this.setStatus(error.message, 'error');
        }
      } finally {
        if (requestId === this.ticketSearchRequestId) {
          this.ticketsLoading = false;
        }
      }
    },
    async loadTicket(ticketId, item = null) {
      if (!ticketId) return false;
      const requestId = ++this.ticketDetailRequestId;
      this.selectedTicket = Number(item?.ticket_id || ticketId);
      const listItem = item || this.findTicketListItem(ticketId);
      if (listItem) {
        this.selectedTicketListItem = listItem;
      }
      this.ticketDetailLoading = true;
      this.setStatus('Φόρτωση πληροφοριών εισιτηρίου...', 'neutral');
      try {
        const payload = await this.apiFetch(`${this.endpoint('ticket')}${ticketId}`);
        if (requestId !== this.ticketDetailRequestId) return false;
        const payloadListItem = listItem || payload.list_item || this.findTicketListItem(payload.id, payload.source_id);
        this.selectedTicket = Number(payload.id || payloadListItem?.ticket_id || ticketId);
        if (payloadListItem) {
          this.selectedTicketListItem = payloadListItem;
        }
        this.selectedTicketData = payload;
        this.selectedDate = this.availableDates[0] || '';
        this.selectedTime = this.availableTimes[0]?.time || '';
        this.visitors = [];
        this.applyInitialCategoryMinimums();
        this.result = null;
        this.resultContext = { source: '', title: '' };
        this.selectedPaymentMethod = '';
        this.posReference = '';
        this.terminalId = '';
        this.resetDeliveryOptions();
        this.setStatus('', 'neutral');
        return true;
      } catch (error) {
        if (requestId === this.ticketDetailRequestId) {
          this.setStatus(error.message, 'error');
        }
        return false;
      } finally {
        if (requestId === this.ticketDetailRequestId) {
          this.ticketDetailLoading = false;
        }
      }
    },
    selectedBuildingPayload() {
      const itemBuildingId = Number(this.selectedTicketListItem?.building_id || 0);
      return {
        building_id: this.selectedBuilding?.id || itemBuildingId || 0,
        building_name: this.selectedBuilding?.title || this.selectedTicketListItem?.building_title || '',
      };
    },
    resetDeliveryOptions() {
      this.deliveryMethods = ['print'];
      this.customerEmail = '';
      this.customerPhone = '';
      this.newsletterOptIn = false;
      this.membershipInviteOptIn = false;
    },
    isDeliveryMethodSelected(method) {
      return this.selectedDeliveryMethods.includes(method);
    },
    setDeliveryMethodEnabled(method, enabled = true) {
      if (!deliveryMethodKeys.includes(method) || this.loading) return;
      const selected = this.selectedDeliveryMethods;
      const hasMethod = selected.includes(method);

      if (enabled && !hasMethod) {
        this.deliveryMethods = [...selected, method];
      } else if (!enabled && hasMethod) {
        this.deliveryMethods = selected.filter((item) => item !== method);
      }

      if (!this.deliveryEmailEnabled) {
        this.newsletterOptIn = false;
        this.membershipInviteOptIn = false;
      }
    },
    toggleDeliveryMethod(method) {
      if (!deliveryMethodKeys.includes(method) || this.loading) return;
      this.setDeliveryMethodEnabled(method, !this.isDeliveryMethodSelected(method));
    },
    saleSelectionValidationViolation() {
      if (!this.selectedTicket) return 'Επίλεξε εισιτήριο.';
      if (!this.selectedDate) return 'Επίλεξε ημερομηνία επίσκεψης.';
      if (!this.selectedTime) return 'Επίλεξε ώρα επίσκεψης.';
      return '';
    },
    async issueTickets() {
      this.clearValidationFocusTarget();

      const selectionViolation = this.saleSelectionValidationViolation();
      if (selectionViolation) {
        this.setStatus(selectionViolation, 'error');
        return;
      }

      const limitViolation = this.ticketLimitViolation;
      if (limitViolation) {
        this.setStatus(limitViolation, 'error');
        return;
      }

      const visitorValidationTarget = this.firstVisitorValidationTarget();
      if (visitorValidationTarget) {
        this.setStatus(visitorValidationTarget.message, 'error');
        this.markValidationFocusTarget(visitorValidationTarget.visitor, visitorValidationTarget.field);
        return;
      }

      const memberViolation = this.memberVerificationViolation;
      if (memberViolation) {
        this.setStatus(memberViolation, 'error');
        const missingIndex = this.firstMissingMemberVerificationIndex();
        if (missingIndex >= 0) {
          this.markValidationFocusTarget(this.visitors[missingIndex], 'member');
          this.openMemberVerification(missingIndex);
        }
        return;
      }

      const memberLimitViolation = this.memberVerificationLimitViolation;
      if (memberLimitViolation) {
        this.setStatus(memberLimitViolation, 'error');
        return;
      }

      const paymentViolation = this.paymentValidationViolation;
      if (paymentViolation) {
        this.setStatus(paymentViolation, 'error');
        return;
      }

      const deliveryViolation = this.deliveryValidationViolation;
      if (deliveryViolation) {
        this.setStatus(deliveryViolation, 'error');
        return;
      }

      if (!this.canSubmit) return;
      this.loading = true;
      this.setStatus('', 'neutral');
      this.result = null;
      this.resultContext = { source: 'sale', title: this.currentSaleTitle() };
      const customerEmails = this.deliveryRequiresEmail ? this.customerEmailList : [];
      const customerEmail = customerEmails[0] || '';
      const customerPhone = this.deliveryRequiresPhone ? normalizePhone(this.customerPhone) : '';

      const payload = {
        ticket_id: this.selectedTicket,
        day: this.selectedDate,
        time: this.selectedTime,
        visitors: this.visitors.map((visitor) => ({
          first: visitor.first || '',
          last: visitor.last || '',
          'category-id': visitor.categoryId,
          member_card_id: visitor.memberCardId || '',
          member_role: this.visitorMemberVerificationRole(visitor),
          member_user_id: visitor.memberVerification?.user?.id || '',
          member_subscription_id: visitor.memberVerification?.subscription?.id || '',
        })),
        payment_method: this.selectedPaymentMethod,
        pos_reference: this.selectedPaymentIsPos ? this.posReference || '' : '',
        terminal_id: this.selectedPaymentIsPos ? this.terminalId || '' : '',
        delivery_methods: this.selectedDeliveryMethods,
        customer_email: customerEmail,
        customer_emails: customerEmails,
        customer_phone: customerPhone,
        subscribe_newsletter: this.newsletterOptIn ? 1 : 0,
        send_membership_email: this.membershipInviteOptIn ? 1 : 0,
        ...this.selectedBuildingPayload(),
      };

      try {
        const result = await this.apiFetch(this.endpoint('issue'), {
          method: 'POST',
          body: JSON.stringify(payload),
        });
        this.result = this.normalizeOrderResult(result, {
          title: this.resultContext.title,
          ticket_id: this.selectedTicket,
          source_id: this.selectedTicketListItem?.source_id || '',
          building_id: this.selectedBuilding?.id || this.selectedTicketListItem?.building_id || '',
          building_title: this.selectedBuilding?.title || this.selectedTicketListItem?.building_title || '',
          building_address: this.selectedBuilding?.address || '',
        });
        this.setStatus('Η έκδοση ολοκληρώθηκε.', 'success');
        this.saveSaleHistory(this.result);
        if (this.result?.delivery_methods?.includes('print')) {
          const issuedOrderId = this.result.order_id;
          window.setTimeout(() => {
            if (this.result?.order_id === issuedOrderId) {
              this.printOrderWithZebra();
            }
          }, 0);
        }
        return this.result;
      } catch (error) {
        this.setStatus(error.message, 'error');
        return null;
      } finally {
        this.loading = false;
      }
    },
    saveSaleHistory(result) {
      const ticketItem = this.selectedTicketCardItem || {};
      const ticketThumb = this.ticketImageUrl(ticketItem);
      const ticketImage = ticketItem.image || null;
      const normalized = this.normalizeOrderResult(result, {
        title: this.resultContext?.title || this.currentSaleTitle(),
        payment: this.selectedPaymentMethod,
        thumb: ticketThumb,
        image: ticketImage,
        ticket_id: this.selectedTicket,
        source_id: ticketItem.source_id || '',
        building_id: this.selectedBuilding?.id || ticketItem.building_id || '',
        building_title: this.selectedBuilding?.title || ticketItem.building_title || '',
        building_address: this.selectedBuilding?.address || '',
      });
      const paymentValue = normalized.payment?.provider || this.selectedPaymentMethod;
      const item = {
        orderId: normalized.order_id || '',
        title: normalized.title || this.currentSaleTitle(),
        total: normalized.total_price || 0,
        count: normalized.tickets_count || 0,
        payment: paymentValue,
        paymentLabel: this.paymentMethodLabel(paymentValue, normalized.payment?.label || paymentValue),
        thumb: normalized.thumb || ticketThumb,
        image: normalized.image || ticketImage,
        ticketId: normalized.ticket_id || '',
        sourceId: normalized.source_id || '',
        buildingId: normalized.building_id || '',
        buildingTitle: normalized.building_title || '',
        buildingAddress: normalized.building_address || '',
        createdAt: new Date().toISOString(),
        result: normalized,
      };
      this.saleHistory = [item, ...readSaleHistory()].slice(0, 30);
      try {
        window.localStorage.setItem(historyStorageKey, JSON.stringify(this.saleHistory));
      } catch (error) {}
      return item;
    },
    async openHistoryOrder(orderId) {
      const normalizedOrderId = String(orderId || '').trim();
      if (!normalizedOrderId) {
        this.closeHistoryResult();
        return false;
      }

      const item = this.findSaleHistoryItem(normalizedOrderId) || { orderId: normalizedOrderId };
      await this.openHistoryResult(item);
      return true;
    },
    async openHistoryResult(item = {}) {
      const orderId = this.historyOrderId(item);
      this.historyDetailLoading = true;
      this.result = null;
      this.resultContext = {
        source: 'history',
        title: item.title || 'Εισιτήριο',
      };
      this.setStatus('Φόρτωση παραγγελίας...', 'neutral');

      let payload = item.result && typeof item.result === 'object' ? item.result : null;

      try {
        const hasTickets = payload && Array.isArray(payload.tickets) && payload.tickets.length;
        const hasTicketPrices = hasTickets && payload.tickets.every((ticket) => (
          ticket?.unit_price !== undefined
          || ticket?.price !== undefined
        ));
        const hasEventContext = payload && (
          payload.location
          || payload.building_title
          || payload.buildingTitle
        );
        const hasOrderMeta = payload && (
          payload.created_at
          || payload.createdAt
        );
        const hasDeliveryContext = payload && (
          payload.delivery
          || payload.delivery_methods
          || payload.customer_emails
          || payload.customer_email
        );

        if (orderId && (!hasTickets || !hasTicketPrices || !hasEventContext || !hasOrderMeta || !hasDeliveryContext)) {
          payload = await this.fetchOrderResult(orderId);
        }

        const normalized = this.normalizeOrderResult(payload || {}, { ...item, orderId });
        this.resultContext = {
          source: 'history',
          title: normalized.title || item.title || 'Εισιτήριο',
        };
        this.result = normalized;
        this.setStatus('', 'neutral');
      } catch (error) {
        this.result = this.normalizeOrderResult(payload || {}, { ...item, orderId });
        this.setStatus(error.message || 'Η παραγγελία δεν φορτώθηκε.', 'error');
      } finally {
        this.historyDetailLoading = false;
      }
    },
    closeHistoryResult() {
      this.historyDetailLoading = false;
      this.result = null;
      this.resultContext = { source: '', title: '' };
      this.setStatus('', 'neutral');
    },
    clearPrintJobStatusPolling() {
      if (this.printJobStatusTimer) {
        window.clearTimeout(this.printJobStatusTimer);
        this.printJobStatusTimer = 0;
      }
      this.printJobStatusRequestId += 1;
    },
    resetPrintSheet() {
      this.clearPrintJobStatusPolling();
      this.printSheet = {
        open: false,
        title: '',
        subtitle: '',
        status: 'idle',
        pending: false,
        summary: '',
        messages: [],
        payload: null,
        error: '',
      };
    },
    openPrintSheet({ title = 'Εκτύπωση', subtitle = '' } = {}) {
      this.clearPrintJobStatusPolling();
      this.printSheet = {
        open: true,
        title,
        subtitle,
        status: 'pending',
        pending: true,
        summary: 'Προετοιμασία εκτύπωσης...',
        messages: [],
        payload: null,
        error: '',
      };
    },
    closePrintSheet() {
      if (this.printSheet.pending) return;
      this.clearPrintJobStatusPolling();
      this.printSheet.open = false;
    },
    addPrintMessage(message = '', type = 'neutral', detail = '', key = '') {
      const text = String(message || '').trim();
      if (!text) return;
      const messageKey = String(key || '').trim();
      this.printSheet.messages = [{
        id: `${Date.now()}-${this.printSheet.messages.length}`,
        key: messageKey,
        text,
        type,
        detail: String(detail || '').trim(),
      }];
    },
    completePrintSheet(status = 'success', summary = '', payload = null, error = '') {
      this.printSheet.status = status;
      this.printSheet.pending = false;
      this.printSheet.summary = summary;
      this.printSheet.payload = payload;
      this.printSheet.error = String(error || '').trim();
    },
    printPayloadSummary(payload = {}) {
      const count = Number(payload.count || payload.job?.count || 0);
      const printed = Number(payload.printed || 0);

      if (payload.queued) {
        return 'Η εκτύπωση μπήκε στην ουρά εκτύπωσης.';
      }

      if (printed || count) {
        return `Ο εκτυπωτής δέχτηκε ${this.ticketCountLabel(printed || count, 'lower')}.`;
      }

      return 'Η εκτύπωση στάλθηκε στον εκτυπωτή.';
    },
    printJobId(payload = {}) {
      return Number(payload?.job?.id || payload?.job_id || 0) || 0;
    },
    zebraPrintJobStatusUrl(jobId = 0) {
      const endpoint = this.endpoint('zebraJob');
      const id = Number(jobId || 0);
      if (!endpoint || !id) return '';
      return new URL(`${endpoint}${id}`, window.location.href).toString();
    },
    formatPrintJobTime(value = 0) {
      const seconds = Number(value || 0);
      if (!seconds) return '';
      try {
        return new Intl.DateTimeFormat('el-GR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
        }).format(new Date(seconds * 1000));
      } catch (error) {
        return '';
      }
    },
    mergePrintJobPayload(job = {}, payload = {}) {
      return {
        ...payload,
        queued: true,
        printer_id: job.printer_id || payload.printer_id || '',
        job: {
          ...(payload.job || {}),
          ...job,
        },
      };
    },
    applyPrintJobStatus(job = {}, originalPayload = {}) {
      const jobId = Number(job.id || 0);
      const status = String(job.status || '').toLowerCase();
      const payload = this.mergePrintJobPayload(job, originalPayload);
      const when = this.formatPrintJobTime(job.completed_at);

      this.printSheet.payload = payload;

      if (status === 'processing') {
        const detail = job.agent_id ? `Σταθμός ${job.agent_id}` : '';
        this.addPrintMessage('Ο agent παρέλαβε την εργασία.', 'success', detail, `print-job-${jobId}-processing`);
        this.printSheet.summary = 'Η εκτύπωση επεξεργάζεται από τον agent.';
        return;
      }

      if (status === 'done') {
        this.addPrintMessage(
          'Η εκτύπωση ολοκληρώθηκε.',
          'success',
          when ? `Ολοκληρώθηκε στις ${when}` : '',
          `print-job-${jobId}-done`
        );
        this.completePrintSheet(
          'success',
          when ? `Η εκτύπωση ολοκληρώθηκε στις ${when}.` : 'Η εκτύπωση ολοκληρώθηκε.',
          payload
        );
        this.setStatus('Η εκτύπωση ολοκληρώθηκε.', 'success');
        return;
      }

      if (status === 'failed') {
        const message = String(job.message || '').trim() || 'Η εκτύπωση απέτυχε.';
        this.addPrintMessage(message, 'error', when ? `Ολοκληρώθηκε στις ${when}` : '', `print-job-${jobId}-failed`);
        this.completePrintSheet('error', message, payload, message);
        this.setStatus(message, 'error');
      }
    },
    startPrintJobStatusPolling(payload = {}) {
      const jobId = this.printJobId(payload);
      const url = this.zebraPrintJobStatusUrl(jobId);
      if (!jobId || !url) return;

      this.clearPrintJobStatusPolling();
      const requestId = this.printJobStatusRequestId;
      const startedAt = Date.now();

      const poll = async () => {
        if (this.printJobStatusRequestId !== requestId || !this.printSheet.open) return;

        try {
          const response = await this.apiFetch(url);
          if (this.printJobStatusRequestId !== requestId || !this.printSheet.open) return;

          const job = response?.job || {};
          this.applyPrintJobStatus(job, payload);

          const status = String(job.status || '').toLowerCase();
          if (status === 'done' || status === 'failed') {
            this.printJobStatusTimer = 0;
            return;
          }
        } catch (error) {
          if (Date.now() - startedAt > printJobPollTimeoutMs) {
            this.addPrintMessage(
              'Δεν πήραμε νεότερη ενημέρωση από την ουρά εκτύπωσης.',
              'neutral',
              '',
              `print-job-${jobId}-timeout`
            );
            this.printJobStatusTimer = 0;
            return;
          }
        }

        if (this.printJobStatusRequestId === requestId && this.printSheet.open) {
          this.printJobStatusTimer = window.setTimeout(poll, printJobPollIntervalMs);
        }
      };

      this.printJobStatusTimer = window.setTimeout(poll, printJobPollIntervalMs);
    },
    printTicket(url) {
      const win = window.open(url, '_blank', 'noopener');
      if (win) win.focus();
    },
    zebraPrintUrl(orderId, ticketUuid = '') {
      const endpoint = this.endpoint('order');
      if (!orderId || !endpoint) return '';

      const url = new URL(`${endpoint}${orderId}/zebra-print`, window.location.href);
      if (ticketUuid) {
        url.searchParams.set('ticket_uuid', ticketUuid);
      }
      const printerId = normalizePrinterId(this.selectedPrinterId);
      if (printerId && printerId !== 'auto') {
        url.searchParams.set('printer_id', printerId);
      }
      return url.toString();
    },
    async printOrderWithZebra() {
      const orderId = this.result?.order_id || '';
      const countLabel = this.ticketCountLabel(this.resultTicketCount || this.resultTickets.length || 0, 'lower');
      this.openPrintSheet({
        title: 'Εκτύπωση παραγγελίας',
        subtitle: this.resultOrderNumber ? `${this.resultOrderNumber} · ${countLabel}` : countLabel,
      });
      this.addPrintMessage('Έλεγχος στοιχείων παραγγελίας.');

      const url = this.zebraPrintUrl(orderId);
      if (!url) {
        const message = 'Δεν βρέθηκε παραγγελία για εκτύπωση.';
        this.addPrintMessage(message, 'error');
        this.completePrintSheet('error', message, null, message);
        this.setStatus('Δεν βρέθηκε παραγγελία για εκτύπωση.', 'error');
        return null;
      }

      if (this.printingAllTickets) {
        const message = 'Υπάρχει ήδη εκτύπωση σε εξέλιξη.';
        this.addPrintMessage(message, 'error');
        this.completePrintSheet('error', message, null, message);
        return null;
      }

      this.printingAllTickets = true;
      this.setStatus('', 'neutral');
      this.addPrintMessage('Αποστολή αιτήματος εκτύπωσης στο σύστημα.');

      try {
        const payload = await this.apiFetch(url, { method: 'POST', body: JSON.stringify({}) });
        const summary = this.printPayloadSummary(payload);
        if (payload.queued) {
          this.addPrintMessage('Η εργασία δημιουργήθηκε στην ουρά εκτύπωσης.', 'success');
        } else {
          this.addPrintMessage('Ο εκτυπωτής απάντησε επιτυχώς.', 'success');
        }
        this.completePrintSheet('success', summary, payload);
        if (payload.queued) {
          this.startPrintJobStatusPolling(payload);
        }
        this.setStatus(payload.queued ? 'Η εκτύπωση μπήκε στην ουρά.' : 'Η εκτύπωση στάλθηκε στον εκτυπωτή.', 'success');
        return payload;
      } catch (error) {
        const message = error.message || 'Η εκτύπωση απέτυχε.';
        this.addPrintMessage(message, 'error');
        this.completePrintSheet('error', message, null, message);
        this.setStatus(message, 'error');
        return null;
      } finally {
        this.printingAllTickets = false;
      }
    },
    async printTicketWithZebra(ticket = {}) {
      const orderId = this.result?.order_id || '';
      const ticketUuid = String(ticket?.ticket_uuid || '').trim();
      const ticketKey = ticketUuid || String(ticket?.id || ticket?.barcode_hash || '');
      const ticketName = String(ticket?.attendee_name || ticket?.attendee || '').trim();
      this.openPrintSheet({
        title: 'Εκτύπωση εισιτηρίου',
        subtitle: ticketName || ticketUuid || 'Μεμονωμένο εισιτήριο',
      });
      this.addPrintMessage('Έλεγχος στοιχείων εισιτηρίου.');

      const url = this.zebraPrintUrl(orderId, ticketUuid);
      if (!url || !ticketUuid) {
        const message = 'Δεν βρέθηκε εισιτήριο για εκτύπωση.';
        this.addPrintMessage(message, 'error');
        this.completePrintSheet('error', message, null, message);
        this.setStatus(message, 'error');
        return null;
      }

      if (this.printingTicketKey) {
        const message = 'Υπάρχει ήδη εκτύπωση εισιτηρίου σε εξέλιξη.';
        this.addPrintMessage(message, 'error');
        this.completePrintSheet('error', message, null, message);
        return null;
      }

      this.printingTicketKey = ticketKey;
      this.setStatus('', 'neutral');
      this.addPrintMessage('Αποστολή αιτήματος εκτύπωσης στο σύστημα.');

      try {
        const payload = await this.apiFetch(url, { method: 'POST', body: JSON.stringify({}) });
        const summary = this.printPayloadSummary(payload);
        if (payload.queued) {
          this.addPrintMessage('Η εργασία δημιουργήθηκε στην ουρά εκτύπωσης.', 'success');
        } else {
          this.addPrintMessage('Ο εκτυπωτής απάντησε επιτυχώς.', 'success');
        }
        this.completePrintSheet('success', summary, payload);
        if (payload.queued) {
          this.startPrintJobStatusPolling(payload);
        }
        this.setStatus(payload.queued ? 'Η εκτύπωση μπήκε στην ουρά.' : 'Η εκτύπωση στάλθηκε στον εκτυπωτή.', 'success');
        return payload;
      } catch (error) {
        const message = error.message || 'Η εκτύπωση απέτυχε.';
        this.addPrintMessage(message, 'error');
        this.completePrintSheet('error', message, null, message);
        this.setStatus(message, 'error');
        return null;
      } finally {
        this.printingTicketKey = '';
      }
    },
    isTicketPrinting(ticket = {}) {
      const ticketUuid = String(ticket?.ticket_uuid || '').trim();
      const ticketKey = ticketUuid || String(ticket?.id || ticket?.barcode_hash || '');
      return Boolean(ticketKey && this.printingTicketKey === ticketKey);
    },
    startNewSale() {
      this.result = null;
      this.resultContext = { source: '', title: '' };
      this.visitors = [];
      this.applyInitialCategoryMinimums();
      this.selectedPaymentMethod = '';
      this.posReference = '';
      this.terminalId = '';
      this.resetDeliveryOptions();
      this.setStatus('', 'neutral');
    },
    async registerServiceWorker() {
      if (!('serviceWorker' in navigator) || !this.config.serviceWorkerUrl) return;
      try {
        await navigator.serviceWorker.register(this.config.serviceWorkerUrl);
      } catch (error) {
        // PWA install remains optional.
      }
    },
    bootSettings() {
      this.setThemeMode(this.themeMode, { persist: false });
      this.setLanguage(this.language, { persist: false });
      this.updateFullscreenStatus();
    },
    handleThemePreferenceChange() {
      if (this.themeMode === 'default') {
        this.applyThemeMode();
      }
    },
  });

  watch(
    () => store.availableTimes,
    () => {
      if (!store.availableTimes.some((slot) => slot.time === store.selectedTime)) {
        store.selectedTime = store.availableTimes[0]?.time || '';
      }
    }
  );

  watch(
    () => store.enabledPaymentMethods,
    (methods) => {
      if (store.settingsPaymentMethod && !methods.some((method) => method.id === store.settingsPaymentMethod)) {
        store.settingsPaymentMethod = '';
      }
    },
    { immediate: true }
  );

  watch(
    () => store.selectedPaymentMethod,
    () => {
      if (!store.selectedPaymentIsPos) {
        store.posReference = '';
        store.terminalId = '';
      }
    }
  );

  return store;
}
