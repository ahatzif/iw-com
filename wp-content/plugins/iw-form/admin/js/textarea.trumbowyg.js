
if (!window.fbControls) window.fbControls = []
let utils = {
    splitObject : (obj, keys) => {
        const reconstructObj = initialObj => (result, key) => {
            result[key] = initialObj[key]
            return result
        }
        const kept = Object.keys(obj)
            .filter(key => keys.includes(key))
            .reduce(reconstructObj(obj), {})
        const rest = Object.keys(obj)
            .filter(key => !keys.includes(key))
            .reduce(reconstructObj(obj), {})
        return [kept, rest]
    }
}

window.fbControls.push(function(controlClass, allClasses) {

    class controlWYSIWYG extends allClasses.textarea {

        static get definition() {
            return {
                icon: '<svg height="16" viewBox="0 0 494.936 494.936" width="16" xmlns="http://www.w3.org/2000/svg"><path d="m389.844 182.85c-6.743 0-12.21 5.467-12.21 12.21v222.968c0 23.562-19.174 42.735-42.736 42.735h-267.741c-23.562 0-42.736-19.174-42.736-42.735v-267.743c0-23.562 19.174-42.735 42.736-42.735h267.741c6.743 0 12.21-5.467 12.21-12.21s-5.467-12.21-12.21-12.21h-267.741c-37.031 0-67.157 30.125-67.157 67.155v267.743c0 37.029 30.126 67.155 67.157 67.155h267.741c37.03 0 67.156-30.126 67.156-67.155v-222.967c0-6.743-5.467-12.211-12.21-12.211z"/><path d="m483.876 20.791c-14.72-14.72-38.669-14.714-53.377 0l-209.147 209.153c-.28.28-3.434 3.559-4.251 5.396l-28.963 65.069c-2.057 4.619-1.056 10.027 2.521 13.6 2.337 2.336 5.461 3.576 8.639 3.576 1.675 0 3.362-.346 4.96-1.057l65.07-28.963c1.83-.815 5.114-3.97 5.396-4.25l209.152-209.146c7.131-7.131 11.06-16.61 11.06-26.692 0-10.081-3.929-19.562-11.06-26.686zm-17.266 36.106-209.153 209.153c-.035.036-.055.078-.089.107l-33.989 15.131 15.131-33.988c.03-.036.071-.055.107-.09l209.148-209.152c5.038-5.039 13.819-5.033 18.846.005 2.518 2.51 3.905 5.855 3.905 9.414s-1.389 6.903-3.906 9.42z"/></svg>',
                i18n: { default: 'WYSIWYG', },
            }
        }

        configure() {
            const defaultClassConfig = {
                js: 'https://cdn.quilljs.com/1.2.4/quill.js',
                css: 'https://cdn.quilljs.com/1.2.4/quill.snow.css',
            }

            const defaultEditorConfig = {
                modules: {
                    toolbar: [[{ header: [2, 3, 4, 5, 6, false] }], ['bold', 'italic', 'underline', 'link', 'h1' ], ['code-block']],
                },
                placeholder: this.config.placeholder || '',
                theme: 'snow',
            }

            const [customClassConfig, customEditorConfig] = utils.splitObject(this.classConfig, ['css', 'js'])
            Object.assign(this, { ...defaultClassConfig, ...customClassConfig })
            this.editorConfig = { ...defaultEditorConfig, ...customEditorConfig }


        }


        build(  ) {




            const { value = '', ...attrs } = this.config
            delete attrs['type']
            this.field = this.markup('div', null, attrs)
            if (this.field.classList.contains('form-control')) {
                this.field.classList.remove('form-control')
            }



            return this.field;
        }


        onRender(evt) {

            //this.htmlField = jQuery(this.element).closest( '[data-field-id]' ).next('[data-field-id]' ).find( '[name="html"]');
            this.htmlField = jQuery(this.element).closest( '.form-field' ).find( '[name="html"]');




            const Delta = window.Quill.import('delta')
            window.fbEditors.quill[this.id] = {}
            const editor = window.fbEditors.quill[this.id]
            editor.instance = new window.Quill(this.field, this.editorConfig)
            editor.data = new Delta();

            editor.instance.root.innerHTML = this.htmlField.val();
            editor.instance.on('text-change', (delta) => {
                editor.data = editor.data.compose(delta)
                this.htmlField.val( editor.instance.root.innerHTML );
            })


            return evt
        }

    }


    controlClass.register('wysiwyg', controlWYSIWYG)
    return controlWYSIWYG
});


window.fbControls.push(function(controlClass, allClasses) {
    class controlSpacer extends controlClass {
        static get definition() {
            return {
                icon: '<svg fill="#000000" width="16px" height="16px" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg"><path d="M3,1V17a2,2,0,0,0,2,2H47a2,2,0,0,0,2-2V1H45V15H7V1ZM49,51V35a2,2,0,0,0-2-2H5a2,2,0,0,0-2,2V51H7V37H45V51ZM12,28H4V24h8Zm4,0h8V24H16Zm20,0H28V24h8Zm4,0h8V24H40Z" fill-rule="evenodd"/></svg>',
                i18n: { default: 'Spacer', },
            }
        }
        build(  ) {
            const { value = '', ...attrs } = this.config;
            this.field = this.markup('div', null , attrs);
            if (this.field.classList.contains('form-control')) {
                this.field.classList.remove('form-control');
            }
            return this.field;
        }
        onRender(evt) {
            return evt
        }
    }
    controlClass.register('spacer', controlSpacer)
    return controlSpacer
});


window.fbControls.push(function(controlClass, allClasses) {
    class controlFormMessages extends controlClass {
        static get definition() {
            return {
                icon: '<svg width="16px" height="16px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 121.86 122.88"><title>comment</title><path d="M30.28,110.09,49.37,91.78A3.84,3.84,0,0,1,52,90.72h60a2.15,2.15,0,0,0,2.16-2.16V9.82a2.16,2.16,0,0,0-.64-1.52A2.19,2.19,0,0,0,112,7.66H9.82A2.24,2.24,0,0,0,7.65,9.82V88.55a2.19,2.19,0,0,0,2.17,2.16H26.46a3.83,3.83,0,0,1,3.82,3.83v15.55ZM28.45,63.56a3.83,3.83,0,1,1,0-7.66h53a3.83,3.83,0,0,1,0,7.66Zm0-24.86a3.83,3.83,0,1,1,0-7.65h65a3.83,3.83,0,0,1,0,7.65ZM53.54,98.36,29.27,121.64a3.82,3.82,0,0,1-6.64-2.59V98.36H9.82A9.87,9.87,0,0,1,0,88.55V9.82A9.9,9.9,0,0,1,9.82,0H112a9.87,9.87,0,0,1,9.82,9.82V88.55A9.85,9.85,0,0,1,112,98.36Z"/></svg>',
                i18n: { default: 'Form Messages', },
            }
        }
        build(  ) {
            const { value = '', ...attrs } = this.config;
            this.field = this.markup('div', null , attrs);
            if (this.field.classList.contains('form-control')) {
                this.field.classList.remove('form-control');
            }
            return this.field;
        }
        onRender(evt) {
            return evt
        }
    }
    controlClass.register('form-messages', controlFormMessages)
    return controlFormMessages
})

window.fbControls.push(function(controlClass, allClasses) {
    class controlTurnstile extends controlClass {
        static get definition() {
            return {
                icon: '<svg width="16" height="16" viewBox="0 0 39 41.4"><path d="M20.1,2.4c-5-.1-9.8,1.6-13.5,4.9L7.8.6l-3.2-.6-2.2,11.8,11.8,2.2.6-3.2-6.1-1.1c6.7-5.9,16.9-5.2,22.8,1.5s5.2,16.9-1.5,22.8c-6.7,5.9-16.9,5.2-22.8-1.5-3.5-4-4.8-9.6-3.5-14.8l-3.1-.8c-2.7, 0.4,3.5,21.1,13.9,23.8,10.4,2.7,21.1-3.5,23.8-13.9,2.7-10.4-3.5-21.1-13.9-23.8-1.4-.4-2.9-.6-4.4-.6Z"/><path d="M31.6,17.1l-2.9-2.9-11.5,11.5-4.6-4.6-2.9,2.9,7.5,7.5,2.9-2.9h0s11.5-11.5,11.5-11.5h0Z"/></svg>',
                i18n: { default: 'Cloudflare Turnstile', },
            }
        }
        build(  ) {
            const { value = '', ...attrs } = this.config;
            this.field = this.markup('div', null , attrs);
            if (this.field.classList.contains('form-control')) {
                this.field.classList.remove('form-control');
            }
            return this.field;
        }
        onRender(evt) {
            return evt
        }
    }
    controlClass.register('turnstile', controlTurnstile)
    return controlTurnstile
})


window.fbControls.push(function(controlClass, allClasses) {
    class controlHTML extends controlClass {
        static get definition() {
            return {
                icon: '<svg fill="#000000" width="16px" height="16px" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg"><path d="M3,1V17a2,2,0,0,0,2,2H47a2,2,0,0,0,2-2V1H45V15H7V1ZM49,51V35a2,2,0,0,0-2-2H5a2,2,0,0,0-2,2V51H7V37H45V51ZM12,28H4V24h8Zm4,0h8V24H16Zm20,0H28V24h8Zm4,0h8V24H40Z" fill-rule="evenodd"/></svg>',
                i18n: { default: 'HTML', },
            }
        }
        build(  ) {
            const { value = '', ...attrs } = this.config;
            this.field = this.markup('div', null , attrs);
            if (this.field.classList.contains('form-control')) {
                this.field.classList.remove('form-control');
            }
            return this.field;
        }
        onRender(evt) {
            return evt
        }
    }
    controlClass.register('html', controlHTML)
    return controlHTML
})



