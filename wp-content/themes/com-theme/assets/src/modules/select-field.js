import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
import infoPopup from "./info-popup.js";


export default class extends module {

    constructor(m) {
        super(m);
        this.events = { click: { 'option': 'onSelect' }, mouseenter: { 'option': 'onMouseEnter' } };
        this.select = this.el.querySelector( 'select' );
        this.isMultiple = this.select.multiple;





        this.el.addEventListener( 'click', this.onClick.bind( this ) );




        this.el.addEventListener( 'form-reset', this.formReset.bind( this ) );

        this.select.addEventListener( 'update', this.onUpdate.bind( this ) );
        this.select.addEventListener( 'click', e => e.stopPropagation() );


        this.input = this.$( 'input' )[0];

        if( this.input ){
            this.input.addEventListener( 'click', e => e.stopPropagation() );
            this.input.addEventListener( 'keyup', this.onKeyup.bind( this ) );
            this.input.addEventListener( 'keydown', e => { if( e.key === 'Enter' ) e.preventDefault(); } );
            this.input.addEventListener( 'input', this.setHeight.bind( this ) );
        }



        Emitter.on('click-outside', this.onBlur.bind( this ) );
        this.onMouseEnter( { currentTarget : this.$( 'option' )[0] });
        this.selected = this.el.querySelector('[data-select-field="option"].selected');

        this.bg= this.$( 'bg' )[0];

        this.pillTemplate = this.$( 'pill' )[0];
        this.placeholder= this.$( 'placeholder' )[0];
        this.pillsCount = 0;

        this.hasClicked = false;


        new ResizeObserver(() => { this.setHeight() }).observe(this.el);

        this.optionClone = this.$( 'option-clone' )[0];
        if( this.optionClone ){
            this.optionClone = this.optionClone.firstElementChild;
        }
    }

    formReset( e ){
        this.hasClicked = false;
        let option = this.el.querySelector('[data-select-field="option"][data-value=""]');
        this.onSelect( { currentTarget: option }, false );
    }

    onUpdate( e ){
        if( e.detail.value ){
            let option = this.el.querySelector('[data-select-field="option"][data-value="' + e.detail.value  + '"]');
            if( option ) option.click();
        }
    }

    init(){

        [...this.el.querySelectorAll('[data-select-field="option"].selected')].forEach( selected => {
            this.onSelect( { currentTarget : selected }, false);
        });


        if( ! this.el.dataset.maxOptions || this.$( 'option' ).length <= parseFloat( this.el.dataset.maxOptions ) ) return;
        this.maxOptions = parseFloat( this.el.dataset.maxOptions );
        this.onResize();
        this.onResizeBind = this.onResize.bind( this );
        window.addEventListener( 'resize', this.onResizeBind );
        this.setHeight();
    }


    onClick( e ){
        e.stopPropagation();

        if( e ) this.hasClicked = true;
        this.el.classList.toggle( 'focus' );
        this.el.classList.toggle( 'z-10' );
        if( this.el.classList.contains( 'focus') && this.input && e.isTrusted){
            this.input.focus();
        }
        this.setHeight();
        Emitter.emit('click-outside', this.el );
    }

    setHeight(){
        this.bg.style.height = this.$( 'selection' )[0].getBoundingClientRect().height + this.$( 'dropdown' )[0].getBoundingClientRect().height +  'px';
    }

    onBlur( e ) {


        if ( e === this.el ) return;
        //if( this.hasClicked ) this.onChange();
        if( this.el.classList.contains( 'focus' ) ){
            this.el.classList.remove( 'focus' );
            this.el.classList.remove( 'z-10' );
            this.bg.style.height = '100%';
            if( this.input) this.input.value = '';
            this.$( 'option' ).forEach( option => option.classList.remove( 'hidden' ) );
        }

        //this.select.querySelector( 'option[value=""]').selected = false;
        let emptyOption = this.select.querySelector( 'option[value=""]');
        if( emptyOption ){
            if( ! this.select.selectedOptions.length ){
                emptyOption.selected = 'selected';
            } else {
                emptyOption.selected = false;
            }
        }

    }

    onChange(e){
        this.select.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
    }


    onSelect( e, triggerChange = true ) {
        if( this.input ){
            this.input.value = '';
        }
        if( ! this.isMultiple ) {
            if( e.currentTarget && e.currentTarget.dataset.text){
                this.$( 'placeholder' )[0].textContent = e.currentTarget.dataset.text;
            }
            if( this.selected ){
                this.selected.classList.remove( 'selected' );
            }
            this.selected = this.hovered = e.currentTarget;
            if( this.selected ) {
                this.selected.classList.add( 'selected' );
                this.selected.classList.remove( 'hovered' );
                this.select.value = this.selected.dataset.value;
            }

            this.onBlur();
        } else {
            this.selected = this.hovered = e.currentTarget;
            this.selected.classList.add( '!hidden' );
            this.createPill( e.currentTarget );
            if(this.input) this.input.value = '';
            this.select.querySelector( 'option[value="' + this.selected.dataset.value + '"]').selected = true ;
            this.filterResults();
            this.setHeight();
        }
        if( triggerChange ){
            this.onChange();
        }
    }

    createPill( option ){
        let pill = this.pillTemplate.cloneNode( true );
        pill.classList.remove( 'hidden' );
        pill.querySelector( '[data-pill-text]' ).textContent = option.dataset.text;
        pill.querySelector( '[data-pill-remove]' ).addEventListener( 'click', e => {
            e.stopPropagation();
            pill.remove();
            option.classList.remove( '!hidden' );
            this.pillsCount--;
            this.placeholder.classList.toggle( 'hidden', this.pillsCount );
            this.select.querySelector( 'option[value="' + option.dataset.value + '"]').selected = '';

            this.setHeight();
        });

        this.$( 'pills' )[0].append( pill );
        this.pillsCount++;
        this.placeholder.classList.add( 'hidden' );
    }

    onMouseEnter( e ){


        if( this.hovered ){
            this.hovered.classList.remove( 'hovered' );
        }
        this.hovered = e.currentTarget;
        if( this.hovered ){
            this.hovered.classList.add( 'hovered' );
            Emitter.emit('scroll-to', { container : this.$( 'options' )[0] , el : this.hovered  });
        }


    }

    onResize(){
        let clone = this.el.querySelector( '[data-select-field="option"]:not(.selected)' ).cloneNode( true );
        clone.style.position = 'absolute';
        clone.style.top = '-1000px';
        clone.classList.remove( 'hidden', '!hidden' );
        document.body.append( clone );
        this.$( 'options' )[0].style.maxHeight = this.maxOptions * clone.getBoundingClientRect().height + 'px';
        clone.remove();
        this.setHeight();
    }

    onKeyup( e ){
        if( e.key === 'Escape') this.onBlur();
        else if( e.key === 'Enter') {
            e.preventDefault();
            this.hovered.click();
        }
        else if ( e.key === 'ArrowDown' ){
            let nextSibling = this.hovered.nextSibling;
            while( nextSibling && nextSibling.nodeType !== 1)  nextSibling = nextSibling.nextSibling;
            this.onMouseEnter( { currentTarget : nextSibling || this.$( 'option' )[0] })
        }
        else if ( e.key === 'ArrowUp' ){
            let previousSibling = this.hovered.previousSibling;
            while( previousSibling && previousSibling.nodeType !== 1) previousSibling = previousSibling.previousSibling;
            this.onMouseEnter( { currentTarget : previousSibling || this.$( 'option' )[ this.$( 'option' ).length - 1 ] })
        } else {
            this.filterResults();
        }
        this.setHeight();
    }

    filterResults(){
        let foundOne = false;


        this.$('option').forEach(option => {
            let optionText = this.removeAccents(option.dataset.text.toUpperCase());
            let inputValue = this.removeAccents(this.input.value.toUpperCase());
            let matches = optionText.includes(inputValue);
            option.classList.toggle('hidden', !matches);
            if (matches && !foundOne) {
                foundOne = true;
                this.onMouseEnter({ currentTarget: option });
            }
        });
    }

    removeAccents(str) {
        const accentMap = {
            'Ά': 'Α', 'Έ': 'Ε', 'Ή': 'Η', 'Ί': 'Ι', 'Ό': 'Ο', 'Ύ': 'Υ', 'Ώ': 'Ω',
            'ά': 'α', 'έ': 'ε', 'ή': 'η', 'ί': 'ι', 'ό': 'ο', 'ύ': 'υ', 'ώ': 'ω',
            'Ϊ': 'Ι', 'Ϋ': 'Υ', 'ϊ': 'ι', 'ϋ': 'υ', 'ΐ': 'ι', 'ΰ': 'υ'
        };
        return str.replace(/[\u0386\u0388-\u038A\u038C\u038E\u038F\u03AC-\u03CE\u03AA\u03AB\u03CA\u03CB\u0390\u03B0]/g, match => accentMap[match] || match);
    }



    setValue(valueOrValues, triggerChange = true) {
        if (this.isMultiple) {
            const valuesArray = Array.isArray(valueOrValues) ? valueOrValues : [valueOrValues];
            this.clearAllSelections();
            valuesArray.forEach(val => {
                const customOption = this.el.querySelector(`[data-select-field="option"][data-value="${val}"]`);
                if (customOption) {
                    this.onSelect({ currentTarget: customOption }, false );
                }
            });
            if (triggerChange) {
                this.onChange();
            }

        } else {
            const finalValue = Array.isArray(valueOrValues) ? valueOrValues[0] : valueOrValues;
            const customOption = this.el.querySelector(`[data-select-field="option"][data-value="${finalValue}"]`);
            if (customOption) {
                this.onSelect({ currentTarget: customOption }, triggerChange);
            }
        }
    }


    clearAllSelections() {
        [...this.select.options].forEach(opt => {
            opt.selected = false;
        });
        const pillsContainer = this.$('pills')[0];
        if (pillsContainer) {
            pillsContainer.innerHTML = '';
        }
        this.placeholder?.classList.remove('hidden');
        this.$('option').forEach(option => option.classList.remove('!hidden'));
        this.pillsCount = 0;
    }



    updateOptions(optionsObj = {}, valueOrValues = null, triggerChange = true) {



        let optionsContainer = this.$('options')[0];
        const optionsContainerScrollContent = optionsContainer.querySelector( '.scroll-content'  );
        if( optionsContainerScrollContent ) optionsContainer = optionsContainerScrollContent;
        if (optionsContainer) {
            optionsContainer.innerHTML = '';
        }
        this.select.innerHTML = '';




        Object.entries(optionsObj).forEach(([value, label]) => {
            const nativeOpt = document.createElement('option');
            nativeOpt.value = value;
            nativeOpt.textContent = label;
            this.select.append(nativeOpt);
            // Custom option node (same structure as existing ones)
            const customOpt = this.optionClone.cloneNode( true );
            customOpt.dataset.value = value;
            customOpt.dataset.text = label;
            customOpt.querySelector( '[data-label]' ).textContent = label;
            optionsContainer.append(customOpt);
            // Bind interactions
            customOpt.addEventListener('click', this.onSelect.bind(this));
            customOpt.addEventListener('mouseenter', this.onMouseEnter.bind(this));
        });


        if (this.isMultiple) {
            this.clearAllSelections();
        } else {
            this.selected = null;
            if (this.placeholder) {
                this.placeholder.textContent = this.placeholder.dataset.placeholderDefaultText || '';
            }
        }


        if (this.maxOptions) {
            this.onResize();
        } else {
            this.setHeight();
        }


        if (valueOrValues !== null) {
            this.setValue(valueOrValues, triggerChange);
        }

        if (triggerChange && valueOrValues === null) {
            this.onChange();
        }
    }




    destroy(){
        window.removeEventListener( 'resize', this.onResizeBind );
    }


    expandChildren( e ){
        e.stopPropagation();
        let parentId = e.target.id;
    }

}
