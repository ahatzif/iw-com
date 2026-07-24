console.info(1);
wp.domReady(() => {
    const interval = setInterval(() => {
        const iframe = document.querySelector('iframe[name="editor-canvas"]');

        console.info(2);
        if (iframe && iframe.contentDocument && iframe.contentDocument.head) {
            const head = iframe.contentDocument.head;

            // Only inject once
            if (!head.querySelector('link.acf-editor-styles')) {
                const acfStyles = [
                    'http://local.host/bmw/wp-content/plugins/advanced-custom-fields-pro/assets/build/css/acf-global.min.css',
                    'http://local.host/bmw/wp-content/plugins/advanced-custom-fields-pro/assets/build/css/acf-input.min.css',
                    'http://local.host/bmw/wp-content/plugins/advanced-custom-fields-pro/assets/build/css/acf-field-group.min.css',

                    'http://local.host/bmw/wp-content/plugins/advanced-custom-fields-pro/assets/build/css/pro/acf-pro-field-group.min.css',
                    'http://local.host/bmw/wp-content/plugins/advanced-custom-fields-pro/assets/build/css/pro/acf-pro-input.min.css',
                    'http://local.host/bmw/wp-admin/load-styles.php?c=0&dir=ltr&load%5Bchunk_0%5D=dashicons,admin-bar,wp-jquery-ui-dialog,wp-pointer,buttons,media-views,editor-buttons,wp-components,wp-preferences,wp-block-edit&load%5Bchunk_1%5D=or,wp-reusable-blocks,wp-patterns,wp-editor,common,forms,wp-reset-editor-styles,wp-block-library,wp-block-editor-content,wp-edit&load%5Bchunk_2%5D=or-classic-layout-styles,wp-edit-blocks,wp-commands,wp-edit-post,wp-block-directory,wp-format-library,admin-menu,dashboard,list-&load%5Bchunk_3%5D=tables,edit,revisions,media,themes,about,nav-menus,widgets,site-icon,l10n,wp-auth-check,classic-theme-styles,wp-color-picker,cod&load%5Bchunk_4%5D=e-editor&ver=6.8',




                    // add more URLs if ACF has additional needed files
                ];

                acfStyles.forEach((href) => {
                    const link = iframe.contentDocument.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = href;
                    link.className = 'acf-editor-styles'; // Mark it for easier debugging
                    head.appendChild(link);
                });
            }

            clearInterval(interval); // Stop checking once injected
        }
    }, 300);
});
