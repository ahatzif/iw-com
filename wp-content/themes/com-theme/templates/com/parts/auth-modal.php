<?php
$asset_url = $asset_url ?? '../assets';
$auth_input_classes = 'h-[5.8rem] w-full rounded-[.8rem] border border-blue-soft/60 bg-white px-20 text-[1.6rem] text-blue transition-colors focus:border-blue';
$auth_label_classes = 'flex flex-col gap-10 text-[1.2rem] font-medium tracking-[.03em] text-blue';
$auth_button_classes = 'flex min-h-[5.8rem] items-center justify-center rounded-[.8rem] border border-blue bg-blue px-25 text-center text-[1.6rem] font-bold text-white transition-colors hover:bg-white hover:text-blue';
$auth_choice_button_classes = 'flex h-[6.2rem] w-full items-center justify-center rounded-[1rem] bg-blue px-30 py-20 text-center text-[1.6rem] font-normal leading-[2.2rem] text-white transition-colors hover:bg-white hover:text-blue hover:shadow-[inset_0_0_0_1px_#173276]';
$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : com_theme_page_url( 'my-account' );
?>
<div
    id="com-auth-modal"
    data-module-auth-modal
    data-account-url="<?= esc_url( $account_url ) ?>"
    class="invisible fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto p-15 opacity-0 transition-[opacity,visibility] duration-300 [&.active]:visible [&.active]:opacity-100 [&.active_.auth-panel]:translate-y-0"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-label="Σύνδεση ή δημιουργία λογαριασμού"
>
    <button type="button" data-auth-modal-action="close" class="fixed inset-0 size-full cursor-default bg-blue/80 backdrop-blur-[.6rem]" aria-label="Κλείσιμο παραθύρου"></button>

    <div data-auth-modal-panel class="auth-panel relative my-auto w-full max-w-[48rem] translate-y-20 rounded-[1.5rem] bg-white text-blue shadow-[0_0_1.7rem_rgba(0,0,0,.2)] transition-transform duration-300">
        <button type="button" data-auth-modal-action="close" class="absolute right-20 top-20 z-2 flex size-40 items-center justify-center rounded-full border border-blue bg-white transition-colors hover:bg-blue hover:text-white sm:right-30 sm:top-30" aria-label="Κλείσιμο">
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

            <form data-auth-form="login" class="mt-40">
                <div class="grid gap-25">
                    <label class="<?= $auth_label_classes ?>">
                        EMAIL*
                        <input type="email" name="login_email" autocomplete="email" required class="<?= $auth_input_classes ?>">
                    </label>

                    <label class="<?= $auth_label_classes ?>">
                        ΚΩΔΙΚΟΣ*
                        <span data-auth-password-field class="relative block">
                            <input type="password" name="login_password" autocomplete="current-password" required class="<?= $auth_input_classes ?> pr-60">
                            <button type="button" data-auth-modal-action="password" class="absolute right-20 top-1/2 flex size-30 -translate-y-1/2 items-center justify-center" aria-label="Εμφάνιση κωδικού" aria-pressed="false">
                                <svg class="h-[1.6rem] w-[1.8rem] fill-current" aria-hidden="true">
                                    <use xlink:href="#icon-show-password"></use>
                                </svg>
                            </button>
                        </span>
                    </label>
                </div>

                <div class="mt-25 flex flex-wrap items-center justify-between gap-20">
                    <button type="button" data-auth-modal-action="show" data-auth-view-target="forgot" class="text-[1.4rem] underline underline-offset-4">Υπενθύμιση κωδικού</button>
                    <button type="submit" class="<?= $auth_button_classes ?> min-w-[16rem]">Σύνδεση</button>
                </div>
            </form>

            <div class="mt-40 border-t border-blue-soft/40 pt-30">
                <p class="text-[1.2rem] font-medium">ΣΥΝΔΕΣΗ ΜΕ</p>
                <button type="button" data-auth-modal-action="google" class="mt-15 flex min-h-[5.8rem] w-full items-center justify-center gap-10 rounded-[.8rem] border border-blue text-[1.6rem] font-bold transition-colors hover:bg-blue hover:text-white">
                    <svg class="size-20" aria-hidden="true">
                        <use xlink:href="#icon-google-login"></use>
                    </svg>
                    GOOGLE
                </button>
            </div>

            <p class="mt-40 text-center text-[1.4rem]">ΔΕΝ ΕΧΕΤΕ <strong>ΛΟΓΑΡΙΑΣΜΟ;</strong> <button type="button" data-auth-modal-action="show" data-auth-view-target="signup" class="font-bold underline underline-offset-4">ΕΓΓΡΑΦΗ</button></p>
        </section>

        <section data-auth-view="forgot" class="hidden p-30 pt-[8rem] sm:p-60" aria-hidden="true" aria-labelledby="auth-forgot-title">
            <h2 id="auth-forgot-title" class="pr-40 text-[3rem] font-bold leading-[1.15]">ΞΕΧΑΣΑΤΕ ΤΟΝ ΚΩΔΙΚΟ ΣΑΣ;</h2>
            <p class="mt-20 text-[1.6rem] leading-[1.5]">Εισάγετε τη διεύθυνση ηλεκτρονικού ταχυδρομείου που συνδέεται με τον λογαριασμό σας. Θα σας αποσταλεί ένας σύνδεσμος επαναφοράς κωδικού.</p>

            <form data-auth-form="forgot" class="mt-40">
                <label class="<?= $auth_label_classes ?>">
                    EMAIL*
                    <input type="email" name="forgot_email" autocomplete="email" required class="<?= $auth_input_classes ?>">
                </label>

                <div class="mt-30 flex flex-wrap items-center justify-between gap-20">
                    <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="text-[1.4rem] underline underline-offset-4">Επιστροφή</button>
                    <button type="submit" class="<?= $auth_button_classes ?> min-w-[16rem]">Αποστολή</button>
                </div>
            </form>

            <p class="mt-50 text-center text-[1.4rem]">ΔΕΝ ΕΧΕΤΕ <strong>ΛΟΓΑΡΙΑΣΜΟ;</strong> <button type="button" data-auth-modal-action="show" data-auth-view-target="signup" class="font-bold underline underline-offset-4">ΕΓΓΡΑΦΗ</button></p>
        </section>

        <section data-auth-view="forgot-success" class="hidden p-30 pt-[8rem] text-center sm:p-60" aria-hidden="true" aria-labelledby="auth-forgot-success-title">
            <span class="mx-auto flex size-60 items-center justify-center rounded-full bg-blue text-[3rem] text-white" aria-hidden="true">✓</span>
            <h2 id="auth-forgot-success-title" class="mt-30 text-[3rem] font-bold leading-[1.15]">ΕΛΕΓΞΤΕ ΤΟ EMAIL ΣΑΣ</h2>
            <p class="mt-15 text-[1.6rem] leading-[1.5]">Στείλαμε οδηγίες επαναφοράς κωδικού στο <strong data-auth-reset-email></strong>.</p>
            <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="<?= $auth_button_classes ?> mt-30 w-full">Επιστροφή στη σύνδεση</button>
        </section>

        <section data-auth-view="signup" class="hidden p-30 pt-[8rem] sm:p-60" aria-hidden="true" aria-labelledby="auth-signup-title">
            <div class="grid lg:grid-cols-[minmax(0,1fr)_30rem] lg:gap-60">
                <div class="min-w-0">
                    <p class="text-[1.4rem] font-bold tracking-[.04em] text-blue-soft">ΕΓΓΡΑΦΗ</p>
                    <h2 id="auth-signup-title" class="mt-20 pr-40 text-[3rem] font-bold leading-[1.15]">ΣΥΜΠΛΗΡΩΣΤΕ ΤΑ ΣΤΟΙΧΕΙΑ ΣΑΣ</h2>

                    <form data-auth-form="signup" class="mt-40">
                        <div class="grid gap-25 sm:grid-cols-2">
                            <label class="<?= $auth_label_classes ?>">
                                ΟΝΟΜΑ*
                                <input type="text" name="signup_first_name" autocomplete="given-name" required class="<?= $auth_input_classes ?>">
                            </label>

                            <label class="<?= $auth_label_classes ?>">
                                ΕΠΩΝΥΜΟ*
                                <input type="text" name="signup_last_name" autocomplete="family-name" required class="<?= $auth_input_classes ?>">
                            </label>

                            <label class="<?= $auth_label_classes ?> sm:col-span-2">
                                EMAIL*
                                <input type="email" name="signup_email" autocomplete="email" required class="<?= $auth_input_classes ?>">
                            </label>

                            <label class="<?= $auth_label_classes ?> sm:col-span-2">
                                ΚΙΝΗΤΟ ΤΗΛΕΦΩΝΟ
                                <input type="tel" name="signup_phone" autocomplete="tel" placeholder="π.χ. +3069XXXXXXXX" class="<?= $auth_input_classes ?>">
                            </label>

                            <label class="<?= $auth_label_classes ?>">
                                ΚΩΔΙΚΟΣ*
                                <span data-auth-password-field class="relative block">
                                    <input type="password" name="signup_password" autocomplete="new-password" minlength="8" required class="<?= $auth_input_classes ?> pr-60">
                                    <button type="button" data-auth-modal-action="password" class="absolute right-20 top-1/2 flex size-30 -translate-y-1/2 items-center justify-center" aria-label="Εμφάνιση κωδικού" aria-pressed="false">
                                        <svg class="h-[1.6rem] w-[1.8rem] fill-current" aria-hidden="true">
                                            <use xlink:href="#icon-show-password"></use>
                                        </svg>
                                    </button>
                                </span>
                            </label>

                            <label class="<?= $auth_label_classes ?>">
                                ΕΠΙΒΕΒΑΙΩΣΗ ΚΩΔΙΚΟΥ*
                                <span data-auth-password-field class="relative block">
                                    <input type="password" name="signup_password_confirmation" autocomplete="new-password" minlength="8" required class="<?= $auth_input_classes ?> pr-60">
                                    <button type="button" data-auth-modal-action="password" class="absolute right-20 top-1/2 flex size-30 -translate-y-1/2 items-center justify-center" aria-label="Εμφάνιση κωδικού" aria-pressed="false">
                                        <svg class="h-[1.6rem] w-[1.8rem] fill-current" aria-hidden="true">
                                            <use xlink:href="#icon-show-password"></use>
                                        </svg>
                                    </button>
                                </span>
                            </label>
                        </div>

                        <p class="mt-10 text-[1.2rem] leading-[1.4]">Ο κωδικός πρέπει να αποτελείται από τουλάχιστον 8 χαρακτήρες.</p>

                        <div class="mt-30 grid gap-15">
                            <label class="flex cursor-pointer items-start gap-12 text-[1.4rem] leading-[1.4]">
                                <span class="relative mt-[.1rem] block size-20 shrink-0">
                                    <input type="checkbox" name="signup_newsletter" class="peer size-full rounded-[.3rem] border border-blue bg-white checked:bg-blue">
                                    <svg class="pointer-events-none absolute inset-[.45rem] fill-white opacity-0 peer-checked:opacity-100" aria-hidden="true">
                                        <use xlink:href="#icon-checkbox-small"></use>
                                    </svg>
                                </span>
                                Εγγραφή στο newsletter
                            </label>

                            <label class="flex cursor-pointer items-start gap-12 text-[1.4rem] leading-[1.4]">
                                <span class="relative mt-[.1rem] block size-20 shrink-0">
                                    <input type="checkbox" name="signup_terms" required class="peer size-full rounded-[.3rem] border border-blue bg-white checked:bg-blue">
                                    <svg class="pointer-events-none absolute inset-[.45rem] fill-white opacity-0 peer-checked:opacity-100" aria-hidden="true">
                                        <use xlink:href="#icon-checkbox-small"></use>
                                    </svg>
                                </span>
                                <span>Συμφωνώ με τους <a href="<?= esc_url( com_theme_page_url( 'terms-of-use' ) ) ?>" class="underline underline-offset-4">Όρους Χρήσης</a> και την <a href="<?= esc_url( com_theme_page_url( 'privacy-policy' ) ) ?>" class="underline underline-offset-4">Πολιτική Απορρήτου</a>.</span>
                            </label>
                        </div>

                        <div class="mt-35 flex flex-wrap items-center justify-between gap-20">
                            <button type="button" data-auth-modal-action="show" data-auth-view-target="login" class="text-[1.4rem] underline underline-offset-4">Έχω ήδη λογαριασμό</button>
                            <button type="submit" class="<?= $auth_button_classes ?> min-w-[18rem]">Εγγραφή</button>
                        </div>
                    </form>
                </div>

                <aside class="mt-40 border-t border-dashed border-blue-soft/60 pt-30 lg:mt-0 lg:border-l lg:border-t-0 lg:pl-60 lg:pt-0" aria-label="Εγγραφή μέσω τρίτου παρόχου">
                    <p class="text-[1.2rem] font-medium">ΕΓΓΡΑΦΗ ΜΕΣΩ</p>
                    <button type="button" data-auth-modal-action="google" class="mt-15 flex min-h-[5.8rem] w-full items-center justify-center gap-10 rounded-[.8rem] border border-blue text-[1.6rem] font-bold transition-colors hover:bg-blue hover:text-white">
                        <svg class="size-20" aria-hidden="true">
                            <use xlink:href="#icon-google-login"></use>
                        </svg>
                        GOOGLE
                    </button>
                </aside>
            </div>
        </section>
    </div>
</div>
