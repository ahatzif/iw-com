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
        return element.value.trim() === '' || re.test( String( element.value ).toLowerCase() );
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
    url : ( element ) => {
        if( element.value === '' ) return true;
        let url;
        try { url = new URL(element.value); } catch (_) { return false;}
        return url.protocol === "http:" || url.protocol === "https:";
    },
    password : ( element ) => {
        return /(?=^.{12,}$)(?=.*\d)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/.test( element.value )
    },
};

const ValidationMessages = typeof window.ValidationMessages  !== "undefined" ? window.ValidationMessages : {
    'required'  : () => FORM_MESSAGES && FORM_MESSAGES.fieldRequired || 'The field is required',
    'email'     : () => FORM_MESSAGES && FORM_MESSAGES.emailRequired || 'Email is not valid',
    'max_files' : (el, max) => ( FORM_MESSAGES && FORM_MESSAGES.maxFiles || 'Max files ' ) + max,
    'min_files' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.minFiles || 'Min files is' ) + min,
    'min' : (el, min) => ( FORM_MESSAGES && FORM_MESSAGES.min || 'Min characters {x}' ).replace( '{x}', min),
    'max' : (el, max) => ( FORM_MESSAGES && FORM_MESSAGES.max || 'Max characters {x}' ).replace( '{x}', max ),
};

export { Validations, ValidationMessages };
