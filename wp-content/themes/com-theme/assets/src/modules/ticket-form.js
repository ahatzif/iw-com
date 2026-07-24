import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);


        this.$( 'category-quantity' ).forEach( div => div.addEventListener( 'input', this.categoryQuantityChange.bind( this ), { passive: true }  ) );
        this.el.addEventListener( 'submit', this.onSubmit.bind( this ) );
        this.el.addEventListener( 'input', this.updateVisitorField.bind( this ) );
        this.el.addEventListener( 'change', this.updateVisitorField.bind( this ) );
        this.el.addEventListener( 'click', this.trashHandler.bind( this ) );
        Emitter.on( 'form-validation-success', this.onFormSuccess.bind(this));


        this.state = {
            quantities: { regular: 0, reduced: 0, free: 0 },
            visitorsByCategory: { regular: [], reduced: [], free: [] },
        };

        this.categories = this.$( 'category' );
        this.categoriesData = [];
        this.categories.forEach( cat => {
            this.categoriesData[ cat.dataset.key ] = {
                el : cat,
                key: cat.dataset.key,
                label: cat.dataset.label,
                price: parseInt( cat.dataset.price ),
                maxTickets : parseInt( cat.dataset.maxTickets ),
                minTickets : parseInt( cat.dataset.minTickets ),
                quantity : 0,
                visitors : []
            };
        });

        // Base (default) max tickets coming from markup. Availability updates may temporarily lower this.
        this.baseTotalMaxTickets = this.toNumber(this.el.dataset.totalMaxTickets);
        this.totalMaxTickets = this.baseTotalMaxTickets;

        // Base (default) min tickets coming from markup. Used for global minimum tickets constraint.
        this.baseTotalMinTickets = this.toNumber(this.el.dataset.totalMinTickets);
        this.totalMinTickets = this.baseTotalMinTickets;
        // Translatable error message template (use "{min}" placeholder)
        this.minTicketsErrorTemplate = this.el.dataset.minTicketsError || 'Πρέπει να επιλέξετε τουλάχιστον {min} εισιτήρια.';
        this.inited = false;
    }

    init(){
        if( ! this.inited) {
            this.inited = true;
        }  else {
            return;
        }
        this.applyInitialMinTickets();
    }

    getTotalTicketsSelected( excludeKey = null ){
        let total = 0;
        Object.entries(this.categoriesData).forEach( ([key, cat]) => {
            if( excludeKey !== null && key === excludeKey ) return;
            total += this.clampInt(cat?.quantity ?? 0, 0, cat?.maxTickets ?? 0);
        });
        return total;
    }

    getRemainingTickets( excludeKey = null ){
        const limit = this.toNumber(this.totalMaxTickets);
        if( !Number.isFinite(limit) || limit <= 0 ) return Infinity;
        const used = this.getTotalTicketsSelected(excludeKey);
        return Math.max(0, limit - used);
    }

    getEffectiveTotalMinTickets(){
        const min = this.toNumber(this.totalMinTickets);
        const max = this.toNumber(this.totalMaxTickets);

        if( !Number.isFinite(min) || min <= 0 ) return 0;

        // If availability (max) is lower than min, relax min to max
        if( Number.isFinite(max) && max > 0 && max < min ){
            return max;
        }
        return min;
    }

    normalizeTotalMaxTicketsValue( value ){
        // Incoming can be a string like '7', '1', or 'null' (literal)
        if( value === null || value === undefined ) return null;
        const s = String(value).trim();
        if( s === '' ) return null;
        if( s.toLowerCase() === 'null' ) return null;
        const n = this.toNumber(s);
        return Number.isFinite(n) && n > 0 ? n : null;
    }

    setTotalMaxTicketsFromAvailability( value ){
        const incoming = this.normalizeTotalMaxTicketsValue(value);

        // Rule:
        // - If incoming is a number and smaller than base -> lower the totalMaxTickets.
        // - If incoming is larger than base OR is null -> restore base.
        if( incoming !== null && incoming < this.baseTotalMaxTickets ){
            this.totalMaxTickets = incoming;
        } else {
            this.totalMaxTickets = this.baseTotalMaxTickets;
        }

        // After changing the global max, ensure selected tickets do not exceed it.
        this.enforceGlobalTotalMax();
        this.applyInitialMinTickets();
    }

    enforceGlobalTotalMax(){
        const limit = this.toNumber(this.totalMaxTickets);
        if( !Number.isFinite(limit) || limit <= 0 ) return;

        let total = this.getTotalTicketsSelected();
        if( total <= limit ) return;

        let overflow = total - limit;

        // Reduce tickets from the end (last categories) until we fit the limit.
        const entries = Object.entries(this.categoriesData);
        for( let i = entries.length - 1; i >= 0 && overflow > 0; i-- ){
            const [key, cat] = entries[i];
            const currentQty = this.clampInt(cat?.quantity ?? 0, 0, cat?.maxTickets ?? 0);
            if( currentQty <= 0 ) continue;

            const reduceBy = Math.min(currentQty, overflow);
            const newQty = currentQty - reduceBy;

            cat.quantity = newQty;
            this.updateVisitorsArray(cat); // will slice visitors to quantity

            // Sync the quantity input
            const qtyInput = this.el.querySelector('[data-ticket-form="category-quantity"][data-key="' + key + '"]');
            if( qtyInput ) qtyInput.value = String(cat.quantity);

            overflow -= reduceBy;
        }

        // Re-render everything after clamping
        Object.entries(this.categoriesData).forEach(([key, cat]) => {
            this.renderCategoryTotal(key, cat);
        });
        this.renderVisitors();
        this.renderTotals();
    }

    applyInitialMinTickets(){
        // Apply per-category minimum tickets on initial load.
        // If a global max exists (availability), mins are clamped in order to fit.
        const limit = this.toNumber(this.totalMaxTickets);
        let remaining = (!Number.isFinite(limit) || limit <= 0) ? Infinity : limit;

        Object.entries(this.categoriesData).forEach(([key, cat]) => {
            const min = this.clampInt(cat?.minTickets ?? 0, 0, cat?.maxTickets ?? 0);
            const allowed = Math.min(min, remaining);

            cat.quantity = this.clampInt(allowed, 0, cat?.maxTickets ?? 0);

            // Sync quantity input
            const qtyInput = this.el.querySelector('[data-ticket-form="category-quantity"][data-key="' + key + '"]');
            if( qtyInput ) qtyInput.value = String(cat.quantity);

            // Ensure visitors array matches quantity
            this.updateVisitorsArray(cat);
            cat.visitors.forEach((v, i) => {
                if( v && v.price === undefined ) v.price = 0;
                // Lock visitors that belong to minTickets
                v.locked = (cat.minTickets > 0 && i < cat.minTickets);
            });

            remaining = remaining === Infinity ? Infinity : Math.max(0, remaining - cat.quantity);
        });

        // Rendering visitors may auto-select `category-id` and set visitor.price
        this.renderVisitors();

        // Update per-category totals and overall totals
        Object.entries(this.categoriesData).forEach(([key, cat]) => {
            this.renderCategoryTotal(key, cat);
        });
        this.renderTotals();
    }

    categoryQuantityChange( e ){
        let el = e.currentTarget;
        let key = el.dataset.key;
        let cat = this.categoriesData[ key ];
        // Enforce both per-category max and global total max
        const desired = this.toNumber(el.value);
        const remaining = this.getRemainingTickets(key);
        const allowedForThisCategory = Math.min(
            cat.maxTickets,
            Number.isFinite(remaining) ? remaining : cat.maxTickets
        );

        cat.quantity = this.clampInt(desired, 0, allowedForThisCategory);

        // Keep the UI input in sync if we had to clamp it
        if( String(el.value) !== String(cat.quantity) ){
            el.value = String(cat.quantity);
        }

        this.updateVisitorsArray( cat );
        cat.visitors.forEach(v => { if( v && v.price === undefined ) v.price = 0; });

        // Rendering visitors may auto-select `category-id` and set visitor.price
        this.renderVisitors();

        // Now totals can be computed correctly
        this.renderCategoryTotal( key, cat );
        this.renderTotals();

    }

    renderCategoryTotal( key, cat  ){
        const total = this.getCategoryCost( cat );
        this.$( 'category-cost' ).forEach( catTotal => {
            if( catTotal.dataset.key === key ){
                catTotal.textContent = this.printPrice( total );
            }
        });
    }

    getVisitorPriceFromField( el ){
        // Try native <option data-price>
        try {
            const value = el.value;
            if( el && el.tagName === 'SELECT' ){
                const opt = el.querySelector(`option[value="${CSS.escape(String(value))}"]`);
                const p = opt?.dataset?.price;
                if( p !== undefined ) return this.toNumber(p);
            }
        } catch(e){}

        // Fallback: custom select UI options like [data-select-field="option"][data-price]
        const wrap = el?.closest?.('[data-module-select-field]');
        const value = el?.value;
        if( wrap && value !== undefined ){
            const optEl = wrap.querySelector(`[data-select-field="option"][data-value="${String(value).replace(/"/g,'\\"')}"]`);
            const p = optEl?.dataset?.price;
            if( p !== undefined ) return this.toNumber(p);
        }

        return 0;
    }

    getCategoryCost( cat ){
        if( !cat || !Array.isArray(cat.visitors) ) return 0;
        return cat.visitors.reduce( (sum, v) => sum + this.toNumber(v?.price), 0 );
    }

    updateVisitorsArray( cat) {
        let desired = cat.quantity;
        let current = cat.visitors;
        if ( cat.quantity === cat.visitors.length ) return;
        if (current.length < desired) {
            cat.visitors = current.concat( Array.from({ length: desired - current.length }, () => {
                return { first: "", last: "", 'category-id': "", price: 0 };
            }) );
        } else {
            cat.visitors = current.slice(0, desired);
        }
    }

    renderVisitors() {
        Object.entries(this.categoriesData).forEach( ([key, cat] ) => {
            let visitorsContainerEl = cat.el.querySelector( '[data-visitors]' );
            visitorsContainerEl.innerHTML = '';
            cat.visitors.forEach( ( visitor, index ) => {
                let visitorEl = this.getTemplate( `visitor-template-${key}`  );
                if( visitor?.locked ){
                    visitorEl.classList.add('is-min-ticket');
                }
                let indexEl = visitorEl.querySelector( '[data-index]' );
                if( indexEl ) indexEl.textContent = index + 1;

                // Print per-ticket price
                const priceEl = visitorEl.querySelector('[data-ticket-price]');
                if( priceEl ){
                    const p = this.toNumber(visitor?.price);
                    priceEl.textContent = p > 0 ? this.printPrice(p) : '';
                }

                visitorEl.querySelectorAll( '[data-visitor-field]' ).forEach( field => {
                    field.dataset.index = index;
                    field.dataset.cat = key;
                    const name = field.getAttribute('name');
                    const value = (visitor && visitor[name] !== undefined) ? visitor[name] : '';

                    if (field.tagName === 'SELECT' ) {
                        let effectiveValue = value;

                        // Auto-select when there is only one valid option (excluding the empty placeholder)
                        if( (effectiveValue === '' || effectiveValue === null || effectiveValue === undefined) ){
                            const realOptions = Array.from(field.querySelectorAll('option')).filter(o => String(o.value) !== '');

                            if( realOptions.length === 1 ){
                                effectiveValue = realOptions[0].value;
                                // Persist to state so subsequent renders keep it
                                visitor[name] = effectiveValue;
                            }
                        }

                        field.value = effectiveValue;

                        if( effectiveValue !== '' ){
                            const wrap = field.closest( '[data-module-select-field]' );
                            if( wrap ){
                                let selected = wrap.querySelector(`[data-select-field="option"].selected` );
                                if( selected ) selected.classList.remove( 'selected' );
                                let newSelected = wrap.querySelector(`[data-select-field="option"][data-value="${effectiveValue}"]`);
                                if( newSelected ) newSelected.classList.add( 'selected' );
                            }

                            // Keep stored price in sync with selected category-id
                            const price = this.getVisitorPriceFromField( field );
                            visitor.price = price;

                            const priceEl = visitorEl.querySelector('[data-ticket-price]');
                            if( priceEl ) priceEl.textContent = price > 0 ? this.printPrice(price) : '';
                        }
                    } else {
                        field.value = value;
                    }

                });
                let trashButton = visitorEl.querySelector( '[trash-button-confirm]' );
                if( trashButton ){
                    trashButton.dataset.index = index;
                    trashButton.dataset.cat = key;
                }
                visitorsContainerEl.appendChild( visitorEl );
            });
            visitorsContainerEl.classList.toggle( 'hidden', ! cat.visitors.length  );
            this.call( 'update', visitorsContainerEl, 'app' );
        });


    }

    trashHandler( e ){
        const trashButton = e.target.closest( '[trash-button-confirm]' );
        if( !trashButton ) return;

        const catKey = trashButton.dataset.cat;
        const idx = parseInt(trashButton.dataset.index, 10);
        const cat = this.categoriesData?.[catKey];
        if( !cat || Number.isNaN(idx) ) return;

        // Prevent deletion of locked visitors (enforced minimum tickets)
        if( cat.visitors?.[idx]?.locked ){
            return;
        }

        // Remove visitor row
        if( Array.isArray(cat.visitors) && idx >= 0 && idx < cat.visitors.length ){
            cat.visitors.splice(idx, 1);
        }

        // Keep quantity in sync (1 visitor == 1 ticket)
        cat.quantity = this.clampInt((cat.visitors?.length ?? 0), 0, cat.maxTickets);

        // Update the quantity input (so UI + state stay aligned)
        const qtyInput = this.el.querySelector('[data-ticket-form="category-quantity"][data-key="' + catKey + '"]');
        if( qtyInput ) qtyInput.value = String(cat.quantity);

        // Safety: ensure we never exceed the global max after removals/edits
        const limit = this.toNumber(this.totalMaxTickets);
        if( Number.isFinite(limit) && limit > 0 ){
            const total = this.getTotalTicketsSelected();
            if( total > limit ){
                // If somehow above limit, clamp this category down to fit
                const remaining = this.getRemainingTickets(catKey);
                const allowed = Math.min(cat.maxTickets, remaining + cat.quantity);
                cat.quantity = this.clampInt(cat.quantity, 0, allowed);
                if( qtyInput ) qtyInput.value = String(cat.quantity);
                this.updateVisitorsArray(cat);
            }
        }

        this.renderCategoryTotal(catKey, cat);
        this.renderVisitors();
        this.renderTotals();
    }

    updateVisitorField( e ){
        let el = e.target;
        if( el.dataset.visitorField !== undefined ){
            const cat = el.getAttribute("data-cat");
            const idx = parseInt(el.getAttribute("data-index"), 10);
            const field = el.getAttribute("name");
            if( !this.categoriesData?.[cat] || Number.isNaN(idx) ) return;
            if( !this.categoriesData[cat].visitors?.[idx] ) return;

            this.categoriesData[cat].visitors[idx][field] = el.value;

            // Prices now depend on the visitor `category-id` selection.
            if( field === 'category-id' ){
                const price = this.getVisitorPriceFromField( el );
                this.categoriesData[cat].visitors[idx].price = price;

                const row = el.closest('[data-visitors]')?.querySelector(`[data-index]`)?.closest('.space-y-10');
                const priceEl = el.closest('.space-y-10')?.querySelector('[data-ticket-price]');
                if( priceEl ) priceEl.textContent = price > 0 ? this.printPrice(price) : '';

                this.renderCategoryTotal( cat, this.categoriesData[cat] );
                this.renderTotals();
            }
        }
    }


    getTotals() {
        let tickets = 0;
        let cost = 0;
        Object.entries(this.categoriesData).forEach( ([key, cat] ) => {
            const qty = this.clampInt(cat.quantity, 0, cat.maxTickets);
            tickets += qty;
            cost += this.getCategoryCost( cat );
        });
        return { tickets, cost };
    }

    renderTotals() {

        const { tickets, cost } = this.getTotals();

        let categoryTotals = this.$( 'category-totals' );
        if( categoryTotals.length ){
            categoryTotals = categoryTotals[0];
            categoryTotals.innerHTML = '';
            Object.entries(this.categoriesData).forEach( ([key, cat] ) => {
                if( cat.quantity > 0) {
                    let categoryTotal = this.getTemplate( 'category-total' );
                    categoryTotal.querySelector( '[data-label]' ).textContent = cat.label;
                    categoryTotal.querySelector( '[data-quantity]' ).textContent = cat.quantity;
                    let categoryCost = this.getCategoryCost(cat);
                    categoryTotal.querySelector( '[data-price]' ).textContent = this.printPrice( this.getCategoryCost(cat) );
                    categoryTotals.appendChild( categoryTotal );
                }

            });
        }


        this.$( 'total-cost' ).forEach( el => el.textContent = this.printPrice(cost) );
        this.$( 'total-tickets' ).forEach( el => {
            el.textContent = `${tickets} ${ tickets === 1 ? el.dataset.singularLabel  : el.dataset.pluralLabel  }`
        });



        this.el.classList.toggle( 'has-tickets-selected', tickets > 0 );
        this.el.classList.toggle( 'has-multiple-tickets', tickets > 1 );
        this.el.classList.toggle( 'submit-enabled', tickets > 0  );
        this.el.classList.toggle( 'below-min-tickets', ! this.canSubmit() );
        if( this.canSubmit() ){
            this.el.classList.remove('form-error');
            this.formError = this.$( 'form-error' )[0];
            if( this.formError ){
                this.formError.innerHTML = '';
            }
        }
    }

    canSubmit(){
        const { tickets, cost } = this.getTotals();
        const effectiveMin = this.getEffectiveTotalMinTickets();
        return tickets >= effectiveMin && tickets > 0;
    }

    onFormSuccess( el ){
        if( el !== this.el ) return;


        const { tickets } = this.getTotals();
        const effectiveMin = this.getEffectiveTotalMinTickets();
        if( tickets < effectiveMin ){
            this.el.classList.add('form-error');
            this.formError = this.$( 'form-error' )[0];
            if( this.formError ){
                this.formError.innerHTML = String(this.minTicketsErrorTemplate).replace('{min}', String(effectiveMin));
            }
            return;
        }
        const formData = new FormData();
        const visitors = [];
        Object.entries(this.categoriesData).forEach( ([key, cat] ) => {
            cat.visitors.forEach( ( visitor, index ) => {
                visitors.push( visitor );
            });
        });

        this.formError = this.$( 'form-error' )[0];

        this.el.classList.remove( 'generic-error', 'form-error' );
        this.el.classList.add( 'loading');

        formData.append( 'action', this.el.dataset.action );
        formData.append( 'nonce', this.el.dataset.nonce );
        formData.append( 'tickets-for-id', this.el.dataset.ticketsForId );
        formData.append( 'day', this.el.querySelector( '[name="ticket-day"]').value );
        formData.append( 'time', this.el.querySelector( '[name="ticket-time"]').value );
        formData.append( 'visitors', JSON.stringify( visitors ) );


        axios.post(THEME_OBJ.ajaxURL, formData ).then( response => {

            let success = response.data.success;
            let data = response.data.data;
            if( success ) {
                if( data.redirect_url ){
                    window.location.href = data.redirect_url;
                }
            } else {
                this.el.classList.remove( 'loading' );
                this.el.classList.add( 'form-error' );
                this.formError.innerHTML = data.message;
                this.call( 'update', this.formError, 'app' );
            }
        }).catch( e => {
            this.el.classList.remove( 'loading' );
            this.el.classList.add( 'generic-error' );
        } );

    }

    onSubmit( e ){
        e.preventDefault();
    }



    printPrice(amount) {
        return `${amount.toFixed( 2 )} €`;
    }

    clampInt(value, min, max) {
        const n = Number.isFinite(value) ? value : parseInt(String(value), 10);
        if (!Number.isFinite(n)) return min;
        return Math.max(min, Math.min(max, n));
    }
    toNumber( value ){
        const n = parseFloat(String(value).replace(',', '.'));
        return Number.isFinite(n) ? n : 0;
    }
    getTemplate( name ){
        let template = this.$( name );
        return template.length ? template[0].content.firstElementChild.cloneNode( true ) : 0;
    }


    onUpdateAvailability( slotAvailability ){
        console.info('onUpdateAvailability')
        // Expecting something like slotAvailability.totalMaxTickets as a string: '7', '1' or 'null'
        this.setTotalMaxTicketsFromAvailability(slotAvailability ?? null);
    }
}
