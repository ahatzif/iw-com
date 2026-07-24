import { module } from 'modujs';
import { Datepicker } from 'vanillajs-datepicker';
import { DateRangePicker } from 'vanillajs-datepicker';



const greekLocale = {
    days: ["Κυριακή", "Δευτέρα", "Τρίτη", "Τετάρτη", "Πέμπτη", "Παρασκευή", "Σάββατο"],
    daysShort: ["Κυρ", "Δευ", "Τρι", "Τετ", "Πεμ", "Παρ", "Σαβ"],
    daysMin: ["Κυρ", "Δευ", "Τρι", "Τετ", "Πεμ", "Παρ", "Σαβ"],
    months: ["Ιανουάριος", "Φεβρουάριος", "Μάρτιος", "Απρίλιος", "Μάιος", "Ιούνιος", "Ιούλιος", "Αύγουστος", "Σεπτέμβριος", "Οκτώβριος", "Νοέμβριος", "Δεκέμβριος"],
    monthsShort: ["Ιαν", "Φεβ", "Μαρ", "Απρ", "Μάι", "Ιουν", "Ιουλ", "Αυγ", "Σεπ", "Οκτ", "Νοε", "Δεκ"],
    today: "Σήμερα",
    clear: "Καθαρισμός",
    weekStart: 1,
    format: "d/m/yyyy"
};
Datepicker.locales.el = greekLocale;




export default class extends module {

    options = {
        prevArrow : '<svg class="size-[1.8rem]"><use xlink:href="#icon-calendar-arrow"></use></svg>',
        nextArrow : '<svg class="size-[1.8rem] rotate-180"><use xlink:href="#icon-calendar-arrow"></use></svg>',
        weekStart: 1,
        language: document.documentElement.lang,
        daysShort : true,
        format : 'yyyy-mm-dd',
        updateOnChange: false
    }

    constructor(m) {
        super(m);
        this.events = { click: {  'set-range': 'setRange', 'set-dates' : 'setDates' } };
        this.input = [...this.$( 'input' )];
        this.toggleButton = this.$( 'toggle' )[0];
        this.onToggleBind = this.toggle.bind( this );
        this.hasRange = false;
        this.activeRangeButton = this.el.querySelector( '.active[data-datepicker="set-range"]' );

        // Allow only future dates if data-future-dates-only is present
        if ( this.el.dataset.futureDatesOnly !== undefined ) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            this.options.minDate = today;
        }



        const datasetOptions = this.el.dataset.options;
        if (datasetOptions) {
            try {
                const parsedOptions = JSON.parse(datasetOptions);
                Object.assign(this.options, parsedOptions);
            } catch (e) {
                console.warn('Invalid JSON in data-options:', datasetOptions);
            }
        }


        this.extraOptions = {};
        this.extraOptionsJS = this.$('extra-options');
        if (this.extraOptionsJS.length) {
            try {
                const parsed = JSON.parse(this.extraOptionsJS[0].textContent);
                const { allowDates, soldOutDates, limitedDates, holidays, ...rest} = parsed || {};
                Object.assign(this.options, { allowDates, soldOutDates, limitedDates, holidays, ...rest});

            } catch (e) {
                console.warn('Invalid JSON in extra-options script:', e);
            }
        }
        const toSet = (arr) => new Set(
            arr
                .filter(Boolean)
                .map((d) => {
                    const parts = String(d).split('-');

                    // Support both Y-m-d (2026-02-09) and d-m-Y (09-02-2026)
                    let year, month, day;
                    if (parts[0] && parts[0].length === 4) {
                        // Y-m-d
                        year = Number(parts[0]);
                        month = Number(parts[1]);
                        day = Number(parts[2]);
                    } else {
                        // d-m-Y
                        day = Number(parts[0]);
                        month = Number(parts[1]);
                        year = Number(parts[2]);
                    }

                    const dt = new Date(year, month - 1, day);
                    dt.setHours(0, 0, 0, 0);
                    return dt.getTime();
                })
        );

        // If allowDates is provided (even as an empty array), treat it as an explicit allow-list.
        // Empty allowDates => everything disabled (except limited dates which are allowed).
        if (Array.isArray(this.options.allowDates)) {
            this.allowedDatesSet = toSet(this.options.allowDates);

            // If allowDates has values, constrain the picker to the min/max of the allowed window.
            // This prevents navigating/selecting dates outside the allowed list.
            if (this.options.allowDates.length) {
                // Include limited dates too, since they are treated as implicitly allowed in beforeShowDay
                const unionTs = new Set(this.allowedDatesSet);
                if (this.limitedDatesSet) {
                    for (const ts of this.limitedDatesSet) unionTs.add(ts);
                }

                const arr = Array.from(unionTs);
                const minTs = Math.min(...arr);
                const maxTs = Math.max(...arr);

                const minDt = new Date(minTs);
                minDt.setHours(0, 0, 0, 0);

                const maxDt = new Date(maxTs);
                maxDt.setHours(0, 0, 0, 0);

                // Respect any already-set min/max constraints (e.g. futureDatesOnly sets minDate=today)
                if (this.options.minDate instanceof Date) {
                    const curMin = new Date(this.options.minDate);
                    curMin.setHours(0, 0, 0, 0);
                    this.options.minDate = (curMin.getTime() > minDt.getTime()) ? curMin : minDt;
                } else {
                    this.options.minDate = minDt;
                }

                if (this.options.maxDate instanceof Date) {
                    const curMax = new Date(this.options.maxDate);
                    curMax.setHours(0, 0, 0, 0);
                    this.options.maxDate = (curMax.getTime() < maxDt.getTime()) ? curMax : maxDt;
                } else {
                    this.options.maxDate = maxDt;
                }
            }

            delete this.options.allowDates;
        }

        if (Array.isArray(this.options.soldOutDates) && this.options.soldOutDates.length) {
            this.soldOutDatesSet = toSet(this.options.soldOutDates);
            delete this.options.soldOutDates;
        }

        if (Array.isArray(this.options.limitedDates) && this.options.limitedDates.length) {
            this.limitedDatesSet = toSet(this.options.limitedDates);
            delete this.options.limitedDates;
        }

        // Holidays (array of objects: [{date: 'YYYY-MM-DD', label: '...'}])
        this.holidayDatesSet = null;
        this.holidayLabelByTs = null;

        if (Array.isArray(this.options.holidays) && this.options.holidays.length) {
            const dates = [];
            const labelByTs = new Map();

            for (const h of this.options.holidays) {
                if (!h || !h.date) continue;
                dates.push(h.date);

                // Build timestamp -> label map (midnight)
                const parts = String(h.date).split('-');
                if (parts.length === 3 && parts[0].length === 4) {
                    const year = Number(parts[0]);
                    const month = Number(parts[1]);
                    const day = Number(parts[2]);
                    const dt = new Date(year, month - 1, day);
                    dt.setHours(0, 0, 0, 0);
                    labelByTs.set(dt.getTime(), String(h.label || ''));
                }
            }

            this.holidayDatesSet = toSet(dates);
            this.holidayLabelByTs = labelByTs;
            delete this.options.holidays;
        }
        // One beforeShowDay to rule them all
        if (this.allowedDatesSet || this.soldOutDatesSet || this.limitedDatesSet || this.holidayDatesSet) {
            this.options.beforeShowDay = (date) => {
                const d = new Date(date);
                d.setHours(0, 0, 0, 0);
                const ts = d.getTime();

                // base enabled
                let enabled = true;

                const isLimited = this.limitedDatesSet ? this.limitedDatesSet.has(ts) : false;

                // allow list (treat limited as implicitly allowed)
                if (this.allowedDatesSet) {
                    enabled = this.allowedDatesSet.has(ts) || isLimited;
                }

                // sold out overrides everything
                const isSoldOut = this.soldOutDatesSet ? this.soldOutDatesSet.has(ts) : false;
                if (isSoldOut) enabled = false;

                const classes = [];
                if (isSoldOut) classes.push('sold-out');
                if (isLimited) classes.push('limited');

                const isHoliday = this.holidayDatesSet ? this.holidayDatesSet.has(ts) : false;
                let tooltip;
                if (isHoliday) {
                    classes.push('holiday');

                    if (enabled) {
                        classes.push('holiday-enabled');
                    }

                    tooltip = this.holidayLabelByTs && this.holidayLabelByTs.has(ts)
                        ? this.holidayLabelByTs.get(ts)
                        : 'Αργία';
                }

                // vanillajs-datepicker supports returning `tooltip` from beforeShowDay
                return { enabled, classes: classes.join(' '), tooltip };
            };
        }

        // Inline mode (always visible calendar)
        this.isInline = this.options.inline === true;
        if ( this.isInline ) {
            this.options.autohide = false;
            this.options.showOnFocus = false;
        }

        this.isClearing = false;
        this.isDateRange = this.input.length > 1;
        if( this.isDateRange ) {
            this.options.inputs = this.input;
            this.datepicker = new DateRangePicker( this.el, this.options );
        } else {
            this.datepicker = new Datepicker( this.input[0], this.options );
        }
        this.dropdowns = this.el.querySelectorAll( '.datepicker-dropdown' );

        this.dropdownEl = this.dropdowns.length ? this.dropdowns[this.dropdowns.length - 1] : null;
        this.el.addEventListener('changeDate', this.onChangeDate.bind( this ) );
        this.el.addEventListener('click', this.onClick.bind( this ) );
        if( this.toggleButton ){
            this.toggleButton.addEventListener( 'click', this.onToggleBind );
        }
        this.setDatesButton();
        this.el.addEventListener('hide', () => this.onHide.bind( this ) );

        this.addFooter();
    }

    onHide() { this.el.classList.remove('focus'); }

    toggle(){
        this.el.classList.toggle( 'focus' );
        if( this.isDateRange ){
            this.datepicker.datepickers[1].toggle();
        } else {
            if (this.datepicker.show) this.datepicker.show()
        }
    }

    hide(){
        if (this.isDateRange) {
            this.datepicker.datepickers[1].hide();
        } else {
            this.datepicker.hide();
        }
        this.el.classList.remove( 'focus' );
    }

    setDatesButton() {
        this.setDatesBtn = this.$( 'set-dates' )[0];
        if( this.dropdownEl && this.setDatesBtn ){
            this.dropdownEl.querySelector( '.datepicker-footer' ).appendChild( this.setDatesBtn );
        }

    }



    onClick(e) {
        const dayEl = e.target.closest('.day');
        if (!dayEl) return;

        this.clearActive(e);

        // If user clicks a holiday day that is DISABLED, show its label.
        // Some posts may have exceptions that open on holidays, so don't show the hint for enabled days.
        if (dayEl.classList.contains('holiday') && dayEl.classList.contains('disabled')) {
            const ts = dayEl.dataset && dayEl.dataset.date ? Number(dayEl.dataset.date) : null;
            const label = (ts && this.holidayLabelByTs && this.holidayLabelByTs.has(ts))
                ? this.holidayLabelByTs.get(ts)
                : 'Αργία';

            this.showHint(dayEl, label);
        }
    }

    showHint(anchorEl, message) {
        if (!anchorEl || !message) return;

        // Toggle behavior: if hint already exists on this day, remove it.
        const existingHint = anchorEl.querySelector('.datepicker-hint');
        if (existingHint) {
            existingHint.remove();
            return;
        }

        // Create hint
        const hintEl = document.createElement('div');
        hintEl.className = 'datepicker-hint rounded-[1rem] bg-white px-20 py-10 text-H8 absolute bottom-full left-1/2 -translate-x-1/2 text-dark whitespace-nowrap';
        hintEl.textContent = message;

        anchorEl.appendChild(hintEl);

        // Auto-remove after 2 seconds
        setTimeout(() => {
            if (hintEl && hintEl.parentNode) {
                hintEl.remove();
            }
        }, 2000);
    }

    onChangeDate(e) {
        if( this.isClearing ) {
            this.isClearing = false;
            return;
        }

        if (this.datepicker instanceof DateRangePicker) {
            const range = this.datepicker.dates;
            if (this.hasRange) {
                const newStartDate = e.detail.date;
                this.hasRange = false;
                this.datepicker.setDates(newStartDate,newStartDate);
                return;
            }
            this.hasRange = range[0] !== range[1];
        } else {
            // Single date selected
            if ( ! this.isInline ) this.hide();
        }
    }

    refresh(){
        if (this.datepicker instanceof DateRangePicker) {
            const fromDate = this.input[0].value ? new Date(this.input[0].value) : null;
            const toDate = this.input[1].value ? new Date(this.input[1].value) : null;
            this.datepicker.setDates(fromDate, toDate, { render: true });

            if (fromDate && toDate) {
                this.datepicker.setDates(fromDate, toDate, { render: true });
            } else if (fromDate) {
                this.datepicker.setDates(fromDate, fromDate, { render: true });
            } else {
                this.datepicker.setDates({ clear: true });
            }
        } else if (this.datepicker instanceof Datepicker) {
            const dateValue = this.input[0].value ? new Date(this.input[0].value) : null;

            if (dateValue) {
                this.datepicker.setDate(dateValue, { render: true });
            } else {
                this.datepicker.setDate(null, { render: true });
            }
        }
    }







    clearActive( e ){
        if( this.activeRangeButton ) {
            this.activeRangeButton.classList.remove( 'active' );
            this.activeRangeButton = null;
        }
    }

    clearDates(){
        this.isClearing = true;
        this.datepicker.setDates( { clear : true } ); // no arguments = clear
        this.hasRange = false;
    }

    setRange( ev ){
        let el = ev.currentTarget;

        if( this.activeRangeButton !== el ) {
            this.clearActive( ev );
            el.classList.add( 'active' );
            this.activeRangeButton = el;
        } else {
            this.clearActive( ev );
        }

        this.clearDates( ev );

        this.hide();

    }

    setDates(){
        this.hide();
    }


    addFooter() {
        this.footer = this.$( 'footer' );
        if( this.footer.length ){
            this.footer = this.footer[0];
            const picker = this.el.querySelector('.datepicker-picker');
            if ( ! picker ) return;
            let footer = picker.querySelector('.datepicker-footer');
            if ( ! footer ) {
                footer = document.createElement('div');
                footer.className = 'datepicker-footer';
                picker.appendChild(footer);
            }
            this.footer.classList.remove( 'hidden' );
            footer.appendChild(this.footer );

        }


    }


}
