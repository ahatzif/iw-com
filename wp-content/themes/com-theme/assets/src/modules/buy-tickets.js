import { module } from 'modujs';
require( 'dom-slider' );


export default class extends module {
    constructor(m) {
        super(m);

        this.delegatedEvents = {
            click: {
                '[data-buy-tickets="time-slot"]': 'timeSlotClick',
                '[data-buy-tickets="toggle-step"]': 'toggleStep',
            },
        };

        this.delegateEvents(this.el, this.delegatedEvents);


        this.activeTimeSlot = null;
        this.ticketDateInput = this.$( 'ticket-date-input' )[0];
        this.ticketDatePreview =  this.$( 'ticket-date-preview' )[0];
        this.ticketTimePreview =  this.$( 'ticket-time-preview' )[0];
        this.ticketTimeInput = this.$( 'ticket-time-input' )[0] || null;
        this.timeStep = this.$( 'time-step' )[0];
        this.ticketsStep = this.$( 'tickets-step' )[0];
        this.dateStep = this.$( 'date-step' )[0];
        this.header = document.querySelector('header');

        this.ticketDateInput.addEventListener( 'changeDate', this.onChangeDate.bind( this ) );

        this.schedule = {};
        this.scheduleJS = this.$( 'schedule' );
        if( this.scheduleJS.length ){
            this.schedule = JSON.parse( this.scheduleJS[0].textContent );
        }

        this.timesByDate = (this.schedule && this.schedule.timesByDate && typeof this.schedule.timesByDate === 'object')
            ? this.schedule.timesByDate
            : {};

        this.hasTimesByDate = Object.keys(this.timesByDate).length > 0;

        this.el.classList.toggle( 'times-by-date', this.hasTimesByDate );
        this.timeSlotsContainer = this.$('time-slots')[0] || null;
        this.timeSlotTemplate = this.$('time-slot-template')[0] || null;
        this.renderTimeSlotsForSelectedDate();

        this.ticketTimeInput.value = this.ticketTimeInput.value.trim();
        this.prifilledTime = this.ticketTimeInput.value;
        if ( this.ticketDateInput.value !== '' ) { // Opens time step / tickets step.
            this.onChangeDate();
        }
    }



    tryAutoselectTimeSlot() {
        if( ! this.prifilledTime ) return;
        let wanted = this.prifilledTime;
        if (/^\d{2}-\d{2}$/.test(wanted)) {
            wanted = wanted.replace('-', ':');
        }

        let slot = this.timeSlotsContainer.querySelector(`[data-buy-tickets="time-slot"][data-time="${wanted}"]`);
        if( slot ){
            this.timeSlotClick({ currentTarget: slot });
        }

        this.prifilledTime = false;
    }


    onChangeDate(e){
        this.resetTimeSlots();
        const [y, m, d] = this.ticketDateInput.value.split('-');
        this.ticketDatePreview.textContent = `${d}/${m}/${y}`;
        this.renderTimeSlotsForSelectedDate();
        this.el.classList.add( 'date-selected' );
        //this.dateStep.classList.remove( 'active' );
        const hasSlotsForDate = this.getSlotsForSelectedDate().length > 0;
        let hasTimeStep = this.hasTimesByDate && hasSlotsForDate;
        let next = hasTimeStep ? this.timeStep : this.ticketsStep;
        if( hasTimeStep ){
            this.ticketsStep.classList.remove( 'active' );
        }
        next.classList.add( 'active' )
        setTimeout(() => {
            let el = next.querySelector( '.counter-item' );
            if( e ){
                this.call('scrollTo', { target: el, options: { offset: -window.innerHeight/2 + el.offsetHeight   } }, 'Scroll');
            }
            this.tryAutoselectTimeSlot();

        },100);
    }

    resetTimeSlots(){
        this.el.classList.remove( 'time-selected' );
        if (this.activeTimeSlot) {
            this.activeTimeSlot.classList.remove('active');
            this.activeTimeSlot = null;
        }
        if (this.ticketTimeInput) {
            this.ticketTimeInput.value = '';
        }
    }
    getSlotsForSelectedDate() {
        const dateYmd = this.ticketDateInput?.value;
        if (!dateYmd) return [];
        const slots = this.timesByDate?.[dateYmd];
        return Array.isArray(slots) ? slots : [];
    }

    renderTimeSlotsForSelectedDate() {
        if (!this.hasTimesByDate) return;
        if (!this.timeSlotsContainer || !this.timeSlotTemplate) return;
        const slots = this.getSlotsForSelectedDate();

        this.timeSlotsContainer.innerHTML = '';
        if (!slots.length) return;

        for (const slot of slots) {
            const btn = this.timeSlotTemplate.content.firstElementChild.cloneNode(true);
            const status = slot?.status || 'available';
            btn.classList.add(status);
            if (status === 'sold-out') {
                btn.disabled = true;
                btn.title = this.el.dataset.unavailableLabel || '';
            } else {
                btn.disabled = false;
                btn.removeAttribute('title');
            }
            // Label: "09:00 – 09:50" or "09:00"
            const labelText = slot?.end ? `${slot.time} – ${slot.end}` : `${slot.time}`;
            // Store for click handler
            btn.dataset.text = labelText;
            btn.dataset.time = slot?.time || '';
            if (slot?.end) btn.dataset.end = slot.end;
            btn.dataset.status = status;

            const labelEl = btn.querySelector('[data-label]');
            labelEl.textContent = labelText;
            const availEl = btn.querySelector('[data-availability]');

            const hasAvailability = (slot?.availability !== undefined && slot?.availability !== null);
            const availability = hasAvailability ? Number(slot.availability) : null;
            btn.dataset.availability = availability;

            const remainingTpl    = availEl.dataset.remainingText;
            const oneRemainingTpl = availEl.dataset.oneRemainingText;
            const soldOutText     = availEl.dataset.soldOutText;



            // Show availability line only when we have a number.
            if (availability === null || Number.isNaN(availability)) {
                availEl.classList.add('hidden');
                availEl.textContent = '';
            } else {
                availEl.classList.remove('hidden');

                if (availability <= 0 || status === 'sold-out') {
                    // Do not show "απομένουν 0"; show sold-out text.
                    availEl.textContent = soldOutText;
                } else if (availability === 1) {
                    availEl.textContent = oneRemainingTpl.replace('%s', '1');
                } else {
                    availEl.textContent = remainingTpl.replace('%s', String(availability));
                }
            }

            this.timeSlotsContainer.appendChild(btn);
        }
    }


    timeSlotClick( e ){
        if (e.currentTarget.disabled) return;
        if( this.activeTimeSlot ) {
            this.activeTimeSlot.classList.remove( 'active' );
        }
        this.activeTimeSlot = e.currentTarget;
        this.activeTimeSlot.classList.add( 'active' );
        this.el.classList.add( 'time-selected' );
        this.ticketTimePreview.textContent = this.activeTimeSlot.dataset.text;
        if (this.ticketTimeInput) {
            this.ticketTimeInput.value = this.activeTimeSlot.dataset.time || '';
        }

        this.ticketsStep.classList.add( 'active' );
        this.call( 'onUpdateAvailability', this.activeTimeSlot.dataset.availability ,'TicketForm');
        if( e.isTrusted ){
            setTimeout(() => {
                let el  = this.ticketsStep.querySelector( '.counter-item' );
                this.call('scrollTo', { target: el, options: { offset: -window.innerHeight/2 + el.offsetHeight  } }, 'Scroll');
            },100);
        }
    }

    toggleStep( e ){
        e.currentTarget.closest( '[data-step]' ).classList.toggle( 'active' );
    }

    /**
     * Simple event delegation helper.
     * Example:
     *  this.delegateEvents(this.el, {
     *    click: {
     *      '[data-x="btn"]': 'onBtnClick',
     *      '.something': (e, el) => {}
     *    }
     *  });
     */
    delegateEvents(root, eventsMap = {}) {
        if (!root || !eventsMap || typeof eventsMap !== 'object') return;

        Object.entries(eventsMap).forEach(([eventName, selectorsMap]) => {
            if (!selectorsMap || typeof selectorsMap !== 'object') return;

            const isPassiveEvent = ['touchstart', 'touchmove', 'wheel'].includes(eventName);
            const listenerOptions = isPassiveEvent ? { passive: true } : false;

            root.addEventListener(eventName, (e) => {
                // preserve insertion order (first match wins)
                for (const [selector, handler] of Object.entries(selectorsMap)) {
                    const matchedEl = e.target?.closest?.(selector);
                    if (!matchedEl || !root.contains(matchedEl)) continue;

                    const delegatedEvent = { currentTarget: matchedEl, isTrusted: e.isTrusted, originalEvent: e };

                    if (typeof handler === 'function') {
                        handler.call(this, delegatedEvent, matchedEl);
                        return;
                    }

                    if (typeof handler === 'string' && typeof this[handler] === 'function') {
                        this[handler](delegatedEvent);
                        return;
                    }

                    // If handler is invalid, just stop at the first matched selector to avoid double-firing.
                    return;
                }
            }, { passive: true } );
        });
    }
}
