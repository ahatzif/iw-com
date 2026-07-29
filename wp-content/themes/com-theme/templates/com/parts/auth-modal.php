<?php
$asset_url = $asset_url ?? '../assets';
$auth_input_classes = 'h-[5.8rem] w-full rounded-[.8rem] border border-blue-soft/60 bg-white px-20 text-[1.6rem] text-blue transition-colors focus:border-blue';
$auth_label_classes = 'flex flex-col gap-10 text-[1.2rem] font-medium tracking-[.03em] text-blue';
$auth_choice_button_classes = 'flex h-[6.2rem] w-full items-center justify-center rounded-[1rem] bg-blue px-30 py-20 text-center text-[1.6rem] font-normal leading-[2.2rem] text-white transition-colors hover:bg-white hover:text-blue hover:shadow-[inset_0_0_0_1px_#173276]';
$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : com_theme_page_url( 'my-account' );
$ajax_url = admin_url( 'admin-ajax.php' );
$activation_method = class_exists( 'IW_Custom_Auth_Activation' ) ? IW_Custom_Auth_Activation::get_method() : 'email';
$uses_sms_activation = class_exists( 'IW_Custom_Auth_Activation' ) && IW_Custom_Auth_Activation::uses_sms( $activation_method );
$google_login_available = defined( 'NSL_PATH_FILE' );
$google_login_url = $google_login_available
    ? add_query_arg( 'loginSocial', 'google', wp_login_url( $account_url ) )
    : '';
?>
<div
    id="com-auth-modal"
    data-module-auth-modal
    data-account-url="<?= esc_url( $account_url ) ?>"
    data-show-password-label="<?= esc_attr__( 'Εμφάνιση κωδικού', 'com-theme' ) ?>"
    data-hide-password-label="<?= esc_attr__( 'Απόκρυψη κωδικού', 'com-theme' ) ?>"
    class="invisible fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto p-15 opacity-0 transition-[opacity,visibility] duration-300 [&.active]:visible [&.active]:opacity-100 [&.active_.auth-panel]:translate-y-0"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-label="<?= esc_attr__( 'Σύνδεση ή δημιουργία λογαριασμού', 'com-theme' ) ?>"
>
    <button type="button" data-auth-modal-action="close" class="fixed inset-0 size-full cursor-default bg-blue/30" aria-label="<?= esc_attr__( 'Κλείσιμο παραθύρου', 'com-theme' ) ?>"></button>

    <div data-auth-modal-panel class="auth-panel relative my-auto w-full max-w-[48rem] translate-y-20 rounded-[1.5rem] bg-white text-blue shadow-[0_0_1.7rem_rgba(0,0,0,.2)] transition-transform duration-300">
        <button type="button" data-auth-modal-action="close" class="absolute right-20 top-20 z-2 flex size-40 items-center justify-center rounded-full border border-blue bg-white transition-colors hover:bg-blue hover:text-white sm:right-30 sm:top-30" aria-label="<?= esc_attr__( 'Κλείσιμο', 'com-theme' ) ?>">
            <svg class="size-[1.7rem] fill-current" aria-hidden="true">
                <use xlink:href="#icon-close-modal"></use>
            </svg>
        </button>

        <section data-auth-view="choice" class="flex flex-col gap-40 p-30 pt-[8rem] text-[#313133] sm:h-[64.1rem] sm:p-60" aria-hidden="false">
            <p class="text-[1.6rem] font-bold leading-[2.2rem] text-blue-soft">ΕΙΣΟΔΟΣ</p>

            <div class="flex w-full flex-col gap-20">
                <h2 class="text-[2.4rem] font-normal leading-[1.2]">Έχετε λογαριασμό ή είστε μέλος;</h2>
                <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="<?= $auth_choice_button_classes ?>">Σύνδεση</button>
            </div>

            <div class="flex w-full flex-col gap-20">
                <h2 class="text-[2.4rem] font-normal leading-[1.2]">Δεν έχετε λογαριασμό;</h2>
                <p class="text-[1.6rem] leading-[2.2rem]">Συνεχίστε ως επισκέπτης ή δημιουργήστε έναν λογαριασμό για να αποκτήσετε πρόσβαση σε αποκλειστικά προνόμια.</p>
                <button type="button" data-auth-modal-action="show" data-auth-view-target="signup" class="<?= $auth_choice_button_classes ?>">Εγγραφή</button>
                <button type="button" data-auth-modal-action="close" class="flex h-[6.2rem] w-full items-center justify-center rounded-[1rem] px-30 py-20 text-center text-[1.6rem] font-normal leading-[2.2rem] text-blue shadow-[inset_0_0_0_1px_#173276] transition-colors hover:bg-blue hover:text-white">Συνέχεια ως Επισκέπτης</button>
            </div>
        </section>

        <section data-auth-view="login" class="hidden p-30 pt-[8rem] sm:p-60" aria-hidden="true" aria-labelledby="auth-login-title">
            <p class="text-[1.4rem] font-bold tracking-[.04em] text-blue-soft">ΕΙΣΟΔΟΣ</p>
            <h2 id="auth-login-title" class="mt-20 text-[3rem] font-bold leading-[1.15]">ΣΥΝΔΕΣΗ</h2>
            <p class="mt-10 max-w-[38rem] text-[1.6rem] leading-[1.45]">Συμπληρώστε τα στοιχεία για να συνδεθείτε στον λογαριασμό σας.</p>

            <form data-module-form data-auth-form="login" data-request-error="<?= esc_attr__( 'Κάτι πήγε στραβά. Παρακαλούμε δοκιμάστε ξανά.', 'com-theme' ) ?>" action="<?= esc_url( $ajax_url ) ?>" method="post" novalidate class="group mt-40">
                <input type="hidden" name="action" value="iw-auth-login">
                <input type="hidden" name="security" value="<?= esc_attr( wp_create_nonce( 'iw-auth-login' ) ) ?>">
                <div class="grid gap-25">
                    <label data-module-validate data-rules="required|email" class="group/field <?= $auth_label_classes ?>">
                        EMAIL*
                        <input data-validate="target" type="email" name="user_email" autocomplete="email" class="<?= $auth_input_classes ?>">
                        <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                    </label>

                    <label data-module-validate data-rules="required" class="group/field <?= $auth_label_classes ?>">
                        ΚΩΔΙΚΟΣ*
                        <span data-auth-password-field class="relative block">
                            <input data-validate="target" type="password" name="user_password" autocomplete="current-password" class="<?= $auth_input_classes ?> pr-60">
                            <button type="button" data-auth-modal-action="password" class="absolute right-20 top-1/2 flex size-30 -translate-y-1/2 items-center justify-center" aria-label="<?= esc_attr__( 'Εμφάνιση κωδικού', 'com-theme' ) ?>" aria-pressed="false">
                                <svg class="h-[1.6rem] w-[1.8rem] fill-current" aria-hidden="true">
                                    <use xlink:href="#icon-show-password"></use>
                                </svg>
                            </button>
                        </span>
                        <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                    </label>
                </div>

                <p data-form="error-message" class="mt-20 hidden text-[1.4rem] leading-[1.4] text-red-700 group-[.error]:block" role="status" aria-live="polite"></p>

                <div class="mt-25 flex flex-wrap items-center justify-between gap-20">
                    <button type="button" data-auth-modal-action="show" data-auth-view-target="forgot" class="text-[1.4rem] underline underline-offset-4">Υπενθύμιση κωδικού</button>
                    <?php get_template_part( 'templates/parts/com-button', null, [
                        'tag'        => 'button',
                        'type'       => 'submit',
                        'label'      => __( 'Σύνδεση', 'com-theme' ),
                        'variant'    => 'blue',
                        'loading'    => true,
                        'classes'    => 'min-w-[16rem] font-bold',
                        'attributes' => [ 'data-auth-submit' => true ],
                    ] ); ?>
                </div>
            </form>

            <?php if ( $google_login_available ) : ?>
            <div class="mt-40 border-t border-blue-soft/40 pt-30">
                <p class="text-[1.2rem] font-medium">ΣΥΝΔΕΣΗ ΜΕ</p>
                <button type="button" data-auth-modal-action="google" data-auth-provider-url="<?= esc_url( $google_login_url ) ?>" class="mt-15 flex min-h-[5.8rem] w-full items-center justify-center gap-10 rounded-[.8rem] border border-blue text-[1.6rem] font-bold transition-colors hover:bg-blue hover:text-white">
                    <svg class="size-20" aria-hidden="true">
                        <use xlink:href="#icon-google-login"></use>
                    </svg>
                    GOOGLE
                </button>
            </div>
            <?php endif; ?>

            <p class="mt-40 text-center text-[1.4rem]">ΔΕΝ ΕΧΕΤΕ <strong>ΛΟΓΑΡΙΑΣΜΟ;</strong> <button type="button" data-auth-modal-action="show" data-auth-view-target="signup" class="font-bold underline underline-offset-4">ΕΓΓΡΑΦΗ</button></p>
        </section>

        <section data-auth-view="forgot" class="hidden p-30 pt-[8rem] sm:p-60" aria-hidden="true" aria-labelledby="auth-forgot-title">
            <h2 id="auth-forgot-title" class="pr-40 text-[3rem] font-bold leading-[1.15]">ΞΕΧΑΣΑΤΕ ΤΟΝ ΚΩΔΙΚΟ ΣΑΣ;</h2>
            <p class="mt-20 text-[1.6rem] leading-[1.5]">Εισάγετε τη διεύθυνση ηλεκτρονικού ταχυδρομείου που συνδέεται με τον λογαριασμό σας. Θα σας αποσταλεί ένας σύνδεσμος επαναφοράς κωδικού.</p>

            <form data-module-form data-auth-form="forgot" data-request-error="<?= esc_attr__( 'Κάτι πήγε στραβά. Παρακαλούμε δοκιμάστε ξανά.', 'com-theme' ) ?>" action="<?= esc_url( $ajax_url ) ?>" method="post" novalidate class="group mt-40">
                <input type="hidden" name="action" value="iw-auth-lost-password">
                <input type="hidden" name="security" value="<?= esc_attr( wp_create_nonce( 'iw-auth-lost-password' ) ) ?>">
                <label data-module-validate data-rules="required|email" class="group/field <?= $auth_label_classes ?>">
                    EMAIL*
                    <input data-validate="target" type="email" name="user_email" autocomplete="email" class="<?= $auth_input_classes ?>">
                    <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                </label>

                <p data-form="error-message" class="mt-20 hidden text-[1.4rem] leading-[1.4] text-red-700 group-[.error]:block" role="status" aria-live="polite"></p>

                <div class="mt-30 flex flex-wrap items-center justify-between gap-20">
                    <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="text-[1.4rem] underline underline-offset-4">Επιστροφή</button>
                    <?php get_template_part( 'templates/parts/com-button', null, [
                        'tag'        => 'button',
                        'type'       => 'submit',
                        'label'      => __( 'Αποστολή', 'com-theme' ),
                        'variant'    => 'blue',
                        'loading'    => true,
                        'classes'    => 'min-w-[16rem] font-bold',
                        'attributes' => [ 'data-auth-submit' => true ],
                    ] ); ?>
                </div>
            </form>

            <p class="mt-50 text-center text-[1.4rem]">ΔΕΝ ΕΧΕΤΕ <strong>ΛΟΓΑΡΙΑΣΜΟ;</strong> <button type="button" data-auth-modal-action="show" data-auth-view-target="signup" class="font-bold underline underline-offset-4">ΕΓΓΡΑΦΗ</button></p>
        </section>

        <section data-auth-view="forgot-success" class="hidden p-30 pt-[8rem] text-center sm:p-60" aria-hidden="true" aria-labelledby="auth-forgot-success-title">
            <span class="mx-auto flex size-60 items-center justify-center rounded-full bg-blue text-[3rem] text-white" aria-hidden="true">✓</span>
            <h2 id="auth-forgot-success-title" class="mt-30 text-[3rem] font-bold leading-[1.15]">ΕΛΕΓΞΤΕ ΤΟ EMAIL ΣΑΣ</h2>
            <p class="mt-15 text-[1.6rem] leading-[1.5]">Στείλαμε οδηγίες επαναφοράς κωδικού στο <strong data-auth-reset-email></strong>.</p>
            <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="mt-30 flex min-h-[5.8rem] w-full items-center justify-center rounded-[.8rem] border border-blue bg-blue px-25 text-center text-[1.6rem] font-bold text-white transition-colors hover:bg-white hover:text-blue">Επιστροφή στη σύνδεση</button>
        </section>

        <section data-auth-view="signup" class="hidden p-30 pt-[8rem] sm:p-60" aria-hidden="true" aria-labelledby="auth-signup-title">
            <div class="grid <?= $google_login_available ? 'lg:grid-cols-[minmax(0,1fr)_30rem] lg:gap-60' : '' ?>">
                <div class="min-w-0">
                    <p class="text-[1.4rem] font-bold tracking-[.04em] text-blue-soft">ΕΓΓΡΑΦΗ</p>
                    <h2 id="auth-signup-title" class="mt-20 pr-40 text-[3rem] font-bold leading-[1.15]">ΣΥΜΠΛΗΡΩΣΤΕ ΤΑ ΣΤΟΙΧΕΙΑ ΣΑΣ</h2>

                    <form data-module-form data-auth-form="signup" data-request-error="<?= esc_attr__( 'Κάτι πήγε στραβά. Παρακαλούμε δοκιμάστε ξανά.', 'com-theme' ) ?>" action="<?= esc_url( $ajax_url ) ?>" method="post" novalidate class="group mt-40">
                        <input type="hidden" name="action" value="iw-auth-register">
                        <input type="hidden" name="security" value="<?= esc_attr( wp_create_nonce( 'iw-auth-register' ) ) ?>">
                        <div class="grid gap-25 sm:grid-cols-2">
                            <label data-module-validate data-rules="required" class="group/field <?= $auth_label_classes ?>">
                                ΟΝΟΜΑ*
                                <input data-validate="target" type="text" name="first_name" autocomplete="given-name" class="<?= $auth_input_classes ?>">
                                <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                            </label>

                            <label data-module-validate data-rules="required" class="group/field <?= $auth_label_classes ?>">
                                ΕΠΩΝΥΜΟ*
                                <input data-validate="target" type="text" name="last_name" autocomplete="family-name" class="<?= $auth_input_classes ?>">
                                <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                            </label>

                            <label data-module-validate data-rules="required|email" class="group/field <?= $auth_label_classes ?> sm:col-span-2">
                                EMAIL*
                                <input data-validate="target" type="email" name="user_email" autocomplete="email" class="<?= $auth_input_classes ?>">
                                <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                            </label>

                            <label data-module-validate data-rules="<?= $uses_sms_activation ? 'required|' : '' ?>phone" class="group/field <?= $auth_label_classes ?> sm:col-span-2">
                                ΚΙΝΗΤΟ ΤΗΛΕΦΩΝΟ<?= $uses_sms_activation ? '*' : '' ?>
                                <input data-validate="target" type="tel" name="activation_phone" autocomplete="tel" placeholder="π.χ. +3069XXXXXXXX" class="<?= $auth_input_classes ?>">
                                <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                            </label>

                            <label data-module-validate data-rules="required|min:8|lowercase|special" class="group/field <?= $auth_label_classes ?>">
                                ΚΩΔΙΚΟΣ*
                                <span data-auth-password-field class="relative block">
                                    <input data-validate="target" type="password" name="user_password" autocomplete="new-password" class="<?= $auth_input_classes ?> pr-60">
                                    <button type="button" data-auth-modal-action="password" class="absolute right-20 top-1/2 flex size-30 -translate-y-1/2 items-center justify-center" aria-label="<?= esc_attr__( 'Εμφάνιση κωδικού', 'com-theme' ) ?>" aria-pressed="false">
                                        <svg class="h-[1.6rem] w-[1.8rem] fill-current" aria-hidden="true">
                                            <use xlink:href="#icon-show-password"></use>
                                        </svg>
                                    </button>
                                </span>
                                <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                            </label>

                            <label data-module-validate data-rules="required|match:user_password" class="group/field <?= $auth_label_classes ?>">
                                ΕΠΙΒΕΒΑΙΩΣΗ ΚΩΔΙΚΟΥ*
                                <span data-auth-password-field class="relative block">
                                    <input data-validate="target" type="password" name="user_password_confirm" autocomplete="new-password" class="<?= $auth_input_classes ?> pr-60">
                                    <button type="button" data-auth-modal-action="password" class="absolute right-20 top-1/2 flex size-30 -translate-y-1/2 items-center justify-center" aria-label="<?= esc_attr__( 'Εμφάνιση κωδικού', 'com-theme' ) ?>" aria-pressed="false">
                                        <svg class="h-[1.6rem] w-[1.8rem] fill-current" aria-hidden="true">
                                            <use xlink:href="#icon-show-password"></use>
                                        </svg>
                                    </button>
                                </span>
                                <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                            </label>
                        </div>

                        <p class="mt-10 text-[1.2rem] leading-[1.4]">Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες, ένα πεζό γράμμα και έναν ειδικό χαρακτήρα.</p>

                        <div class="mt-30 grid gap-15">
                            <label class="flex cursor-pointer items-start gap-12 text-[1.4rem] leading-[1.4]">
                                <span class="relative mt-[.1rem] block size-20 shrink-0">
                                    <input type="checkbox" name="newsletter" class="peer size-full rounded-[.3rem] border border-blue bg-white checked:bg-blue">
                                    <svg class="pointer-events-none absolute inset-[.45rem] fill-white opacity-0 peer-checked:opacity-100" aria-hidden="true">
                                        <use xlink:href="#icon-checkbox-small"></use>
                                    </svg>
                                </span>
                                Εγγραφή στο newsletter
                            </label>

                            <label data-module-validate data-rules="required" class="group/field flex cursor-pointer items-start gap-12 text-[1.4rem] leading-[1.4]">
                                <span class="relative mt-[.1rem] block size-20 shrink-0">
                                    <input data-validate="target" type="checkbox" name="terms" class="peer size-full rounded-[.3rem] border border-blue bg-white checked:bg-blue">
                                    <svg class="pointer-events-none absolute inset-[.45rem] fill-white opacity-0 peer-checked:opacity-100" aria-hidden="true">
                                        <use xlink:href="#icon-checkbox-small"></use>
                                    </svg>
                                </span>
                                <span>Συμφωνώ με τους <a href="<?= esc_url( com_theme_page_url( 'oroi-xrisis' ) ) ?>" class="underline underline-offset-4">Όρους Χρήσης</a> και την <a href="<?= esc_url( com_theme_page_url( 'politiki-aporritou' ) ) ?>" class="underline underline-offset-4">Πολιτική Απορρήτου</a>.</span>
                                <span data-validate="message" class="hidden text-[1.2rem] text-red-700 group-[.error]/field:block">&nbsp;</span>
                            </label>
                        </div>

                        <p data-form="error-message" class="mt-20 hidden text-[1.4rem] leading-[1.4] text-red-700 group-[.error]:block" role="status" aria-live="polite"></p>

                        <div class="mt-35 flex flex-wrap items-center justify-between gap-20">
                            <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="text-[1.4rem] underline underline-offset-4">Έχω ήδη λογαριασμό</button>
                            <?php get_template_part( 'templates/parts/com-button', null, [
                                'tag'        => 'button',
                                'type'       => 'submit',
                                'label'      => __( 'Εγγραφή', 'com-theme' ),
                                'variant'    => 'blue',
                                'loading'    => true,
                                'classes'    => 'min-w-[18rem] font-bold',
                                'attributes' => [ 'data-auth-submit' => true ],
                            ] ); ?>
                        </div>
                    </form>
                </div>

                <?php if ( $google_login_available ) : ?>
                <aside class="mt-40 border-t border-dashed border-blue-soft/60 pt-30 lg:mt-0 lg:border-l lg:border-t-0 lg:pl-60 lg:pt-0" aria-label="<?= esc_attr__( 'Εγγραφή μέσω τρίτου παρόχου', 'com-theme' ) ?>">
                    <p class="text-[1.2rem] font-medium">ΕΓΓΡΑΦΗ ΜΕΣΩ</p>
                    <button type="button" data-auth-modal-action="google" data-auth-provider-url="<?= esc_url( $google_login_url ) ?>" class="mt-15 flex min-h-[5.8rem] w-full items-center justify-center gap-10 rounded-[.8rem] border border-blue text-[1.6rem] font-bold transition-colors hover:bg-blue hover:text-white">
                        <svg class="size-20" aria-hidden="true">
                            <use xlink:href="#icon-google-login"></use>
                        </svg>
                        GOOGLE
                    </button>
                </aside>
                <?php endif; ?>
            </div>
        </section>

        <section data-auth-view="signup-success" class="hidden p-30 pt-[8rem] text-center sm:p-60" aria-hidden="true" aria-labelledby="auth-signup-success-title">
            <span class="mx-auto flex size-60 items-center justify-center rounded-full bg-blue text-[3rem] text-white" aria-hidden="true">✓</span>
            <h2 id="auth-signup-success-title" class="mt-30 text-[3rem] font-bold leading-[1.15]">ΕΛΕΓΞΤΕ ΤΟ EMAIL ΣΑΣ</h2>
            <p data-auth-registration-message class="mt-15 text-[1.6rem] leading-[1.5]">Στείλαμε οδηγίες ενεργοποίησης στο <strong data-auth-registration-email></strong>.</p>
            <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="mt-30 flex min-h-[5.8rem] w-full items-center justify-center rounded-[.8rem] border border-blue bg-blue px-25 text-center text-[1.6rem] font-bold text-white transition-colors hover:bg-white hover:text-blue">Επιστροφή στη σύνδεση</button>
        </section>
    </div>
</div>
