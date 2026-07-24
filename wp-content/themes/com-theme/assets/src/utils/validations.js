const Validations = {
    required : ( element ) => {
        if( element.type=== 'checkbox' || element.type=== 'radio' ){
            let checkboxes = element.closest( '[data-module-validate]' ).querySelectorAll( '[type="' + element.type + '"] ');
            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    return true;
                }
            }
            return false;
        } else if( element.type=== 'radio' ){
            return element.checked;
        } else {
            return element.value.trim() !== '';
        }
    },
    email : ( element ) => {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test( String( element.value ).toLowerCase() );
    },
    max_files : ( element, max ) => {
        let maxLength = parseInt( max );
        return element.files.length <= maxLength;
    },
    min_files : ( element, min ) => {
        let minLength = parseInt( min );
        return element.files.length <= minLength;
    },
    min : ( element, length ) => {
        return element.value.length >= parseInt( length );
    },
    max : ( element, length ) => {
        return element.value.length <= parseInt( length );
    },
    number : ( element ) => {
        return /^.*[0-9].*$/.test(element.value);
    },
    lowercase : ( element ) => {
        return /^.*[a-z].*$/.test(element.value);
    },
    uppercase : ( element ) => {
        return /^.*[a-z].*$/.test(element.value);
    },
    special : ( element ) => {
        return /^.*[!@#$%^&*].*$/.test(element.value);
    },
    url : ( element ) => {
        if( element.value === '' ) return true;
        let url;
        try { url = new URL(element.value); } catch (_) { return false;}
        return url.protocol === "http:" || url.protocol === "https:";
    },
    password : ( element ) => {
        return /(?=^.{12,}$)(?=.*\d)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/.test( element.value )
    },
    match : ( element, match ) => {
        let elMatch = element.closest( 'form' ).querySelector( `[name="${match}"]` );
        return elMatch && elMatch.value === element.value ;
    },
    autocomplete : ( element ) => {
        let name = element.getAttribute( 'name' );
        let autocompleteEl = element.closest( 'form' ).querySelector( `[name="${name}_autocomplete"]` );
        return autocompleteEl.value !== '';
    },
    tax_number : ( element ) => {
        const afm = element.value.trim();
        if (afm.length !== 9) return false;
        if (!/^\d+$/.test(afm)) return false;
        if (afm === '000000000') return false;

        const body = afm.slice(0, -1);
        const chars = body.split('');
        let i = 0;
        const sum = chars.reduce((s, v) => {
            return s + (parseInt(v, 10) << (8 - i++));
        }, 0);

        const calc = sum % 11;
        const checkDigit = parseInt(afm[8], 10);

        return (calc % 10) === checkDigit;
    },
    has_class : ( element, className ) => {
        return element.classList.contains( className ) || element.closest( '.field' ).classList.contains( className );
    },
    phone : ( element ) => {
        const value = element.value.trim();
        if (value === '') return true;
        return /^(?:\+|00)?\d{6,15}$/.test(value);
    }

};

const ValidationMessages = typeof window.ValidationMessages  !== "undefined" ? window.ValidationMessages : {
    'required'  : () => FORM_MESSAGES && FORM_MESSAGES.fieldRequired || 'The field is required',
    'email'     : () => FORM_MESSAGES && FORM_MESSAGES.emailRequired || 'Email is not valid',
    'max_files' : (el, max) => ( FORM_MESSAGES && FORM_MESSAGES.maxFiles || 'Max files ' ) + max,
    'min_files' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.minFiles || 'Min files is' ) + min,
    'uppercase' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.uppercase || 'A lowercase character is required' ),
    'lowercase' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.lowercase || 'An uppercase character is required' ),
    'autocomplete' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.autocomplete || 'Please select one' ),
    'tax_number' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.tax_number || 'Invalid Tax Number' ),
    'match' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.match || 'Fields do not match' ),
    'special' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.special || 'A special character is required' ),
    'min' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.min || 'Min characters {x}' ).replace( '{x}', min),
    'max' : (el, max) => ( FORM_MESSAGES && FORM_MESSAGES.max || 'Max characters {x}' ).replace( '{x}', max ),
    'phone' : () => ( FORM_MESSAGES && FORM_MESSAGES.phone || 'Invalid phone number' ),
    'has_class' : ( element, className ) => FORM_MESSAGES && FORM_MESSAGES[ 'has_class:'+  className ] || 'The field is invalid',
};

export { Validations, ValidationMessages };
