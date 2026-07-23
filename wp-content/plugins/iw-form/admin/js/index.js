jQuery($ => {
    const fbTemplate = document.getElementById('build-wrap');



    let config = $('#form_config').val();

    let formData = config ? JSON.parse( config ) : [];

    let wrapperClass = { label: ["Wrapper class" ], value: '' };
    let validate= { label: ["Validations" ], value: '', placeholder: 'eg: min:3|max:4|number|url|password|max_files|min_files' };
    let searchField = { label: ["Search field" ], value: '', type : 'checkbox' };
    let hideLabel = { label: ["Hide Label" ], value: '', type : 'checkbox' };
    let dontExport = { label: ["Don't Export" ], value: '', type : 'checkbox' };



    let builder = $(fbTemplate).formBuilder( {


        formData,

        onAddOption: (optionTemplate, optionIndex) => {
            //console.info( optionTemplate );
            optionTemplate.selected = false;
            return optionTemplate
        },



        controlOrder: [


            'text', // Ready
            'select',
            'checkbox-group', // Ready
            'radio-group', // TODO: ALLOW NO SELECTION
            'textarea', // Ready
            'button', // Ready
            'hidden', // Ready


            'header', // Ready
            'paragraph', // Ready
            'wysiwyg', // Ready
            'html',
            'file'

        ],

        disableFields: ['autocomplete', 'number', 'date' ],

        typeUserAttrs : {
            '*' : { wrapperClass },
            'text': {wrapperClass, validate, hideLabel, dontExport},
            'select': {wrapperClass,validate, searchField, hideLabel, dontExport },
            'textarea': {wrapperClass,validate, hideLabel, dontExport},
            'wysiwyg' : { wrapperClass, html : { label: ["HTML" ], value: '', type: 'textarea' } },
            'html' : { wrapperClass, html : { label: ["HTML" ], value: '', type: 'textarea' } },

            'checkbox-group' : { wrapperClass, hideLabel, dontExport},
            'radio-group' : { wrapperClass, hideLabel, dontExport},

            'autocomplete' : { wrapperClass },
            'button' : { wrapperClass },
            'date' : { wrapperClass },
            'file' : { wrapperClass },
            'header' : { wrapperClass },
            'number' : { wrapperClass, dontExport },
            'paragraph' : { wrapperClass },




        },

        typeUserDisabledAttrs: {
            'wysiwyg': [ 'description', 'placeholder'  ],
            'textarea': [ 'subtype' ],
            'paragraph': [ 'subtype' ],
            'spacer': [ 'required', 'description', 'placeholder',  'name', 'value', 'value' ],
            'turnstile': [ 'required', 'description', 'placeholder',  'name', 'value', 'value' ],
            'html': [ 'required', 'description', 'placeholder',  'name'],
            'form-messages': [ 'required', 'description', 'placeholder',  'name', 'value' ],
            'button' : [ 'name' ]
        },

        disabledSubtypes: {
            text: ['color'],
            textarea: ['tinymce','quill'],
            header: ['h1'],
        },

        /*replaceFields: [
            {
                type: "checkbox-group",
                label: "Checkbox",
            },
        ],*/

        /*subtypes: {
            text: ['textarea']
        },*/



        /*controlConfig: {
            'textarea.quill': {
                placeholder: "",
                modules: {
                    toolbar: [[{ header: [1, 2, false] }], ['bold', 'italic', 'underline', 'link'] , ['code-block']],
                },
            }
        },*/





        disabledActionButtons: ['data', 'save'],
        disabledAttrs: ["style",'access' ],




    });




    $('#publish').on('click', function(e) {
        $( '#form_config' ).val( JSON.stringify( builder.actions.getData() ) );
    });


    $( '[data-add-form-field]').click( function(e){

        let field = this.dataset;
        field[ 'class' ] = '';
        if( this.dataset.required === '1' ){
            field[ 'required' ] = true;
        }

        builder.actions.addField( field );
    });




});
