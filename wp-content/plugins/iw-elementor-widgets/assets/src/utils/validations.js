const Validations = {
    required : ( element ) => {
        return element.type=== 'checkbox' ? element.checked : element.value.trim() !== '';
    },
    email : ( element ) => {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test( String( element.value ).toLowerCase() );
    },
    password : ( element ) => {
        return /(?=^.{12,}$)(?=.*\d)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/.test( element.value )
    },
    min : ( element, length ) => {
        return element.value.length >= parseInt( length );
    },
    uppercase : ( element ) => {
        return /^.*[A-Z].*$/.test(element.value);
    },
    lowercase : ( element ) => {
        return /^.*[a-z].*$/.test(element.value);
    },
    number : ( element ) => {
        return /^.*[0-9].*$/.test(element.value);
    },
    special : ( element ) => {
        return /^.*[!@#$%^&*].*$/.test(element.value);
    },
    url : ( element ) => {
        if( element.value === '' ) return true;
        let url;
        try { url = new URL(element.value); } catch (_) { return false;}
        return url.protocol === "http:" || url.protocol === "https:";
    }
};

const ValidationMessages = typeof window.ValidationMessages  !== "undefined" ? window.ValidationMessages : {
    'required'  : 'The field is required',
    'email'     : 'Email is not valid',
    'min'       : 'Min length is {x}',
    'uppercase' : 'The field must contain an uppercase',
    'lowercase' : 'The field must contain a lowercase',
    'number'    : 'The field must contain a number',
    'special'   : 'The field must contain a special',
    'url'       : 'URL is not valid',
};

export { Validations, ValidationMessages };
